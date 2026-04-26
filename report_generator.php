<?php
require_once 'db_connect.php';
require_once 'functions.php';

class ReportGenerator {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getSalesReport($startDate, $endDate, $groupBy = 'day') {
        $groupFormat = $groupBy === 'month' ? '%Y-%m' : '%Y-%m-%d';
        $stmt = $this->conn->prepare("
            SELECT 
                DATE_FORMAT(created_at, ?) as period,
                SUM(total) as total_sales,
                SUM(discount) as total_discount,
                SUM(tax) as total_tax,
                COUNT(*) as transaction_count
            FROM transactions 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY period
            ORDER BY period DESC
        ");
        $stmt->bind_param("sss", $groupFormat, $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProfitReport($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                DATE_FORMAT(t.created_at, '%Y-%m-%d') as date,
                SUM((td.price - p.cost_price) * td.qty) as profit,
                SUM(td.price * td.qty) as revenue,
                SUM(p.cost_price * td.qty) as cost
            FROM transactions t
            JOIN transaction_details td ON t.id = td.transaction_id
            JOIN products p ON td.product_id = p.id
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY date
            ORDER BY date DESC
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getTopProducts($startDate, $endDate, $limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT 
                p.name,
                SUM(td.qty) as total_qty,
                SUM(td.price * td.qty) as total_revenue
            FROM transaction_details td
            JOIN products p ON td.product_id = p.id
            JOIN transactions t ON td.transaction_id = t.id
            WHERE DATE(t.created_at) BETWEEN ? AND ?
            GROUP BY p.id
            ORDER BY total_qty DESC
            LIMIT ?
        ");
        $stmt->bind_param("ssi", $startDate, $endDate, $limit);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function exportToCSV($data, $filename) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
            foreach ($data as $row) {
                fputcsv($output, $row);
            }
        }
        fclose($output);
        exit;
    }

    public function generatePDF($data, $title) {
        // Placeholder for PDF generation - would require a library like TCPDF or FPDF
        // For now, return HTML that can be printed
        $html = "<h1>$title</h1><table border='1'>";
        if (!empty($data)) {
            $html .= "<tr>";
            foreach (array_keys($data[0]) as $header) {
                $html .= "<th>$header</th>";
            }
            $html .= "</tr>";
            foreach ($data as $row) {
                $html .= "<tr>";
                foreach ($row as $cell) {
                    $html .= "<td>$cell</td>";
                }
                $html .= "</tr>";
            }
        }
        $html .= "</table>";
        return $html;
    }
}
?>