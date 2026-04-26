<?php
require_once 'db_connect.php';
require_once 'functions.php';

class POSEngine {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function processTransaction($cart, $discount, $paymentAmount, $paymentMethod, $userId) {
        $notaId = Functions::generateNotaId();
        $subtotal = 0;

        foreach ($cart as $item) {
            $subtotal += $item['sell_price'] * $item['qty'];
        }

        $tax = Functions::calculateTax($subtotal);
        $total = $subtotal - $discount + $tax;
        $change = $paymentAmount - $total;

        if ($change < 0) {
            return ['success' => false, 'error' => 'Pembayaran kurang'];
        }

        $this->conn->begin_transaction();
        try {
            // Insert transaction
            $stmt = $this->conn->prepare("INSERT INTO transactions (nota_id, user_id, total, discount, tax, payment_amount, change_amount, payment_method) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("siddddds", $notaId, $userId, $total, $discount, $tax, $paymentAmount, $change, $paymentMethod);
            $stmt->execute();
            $transactionId = $this->conn->insert_id;

            // Insert details and update stock
            foreach ($cart as $item) {
                $stmt = $this->conn->prepare("INSERT INTO transaction_details (transaction_id, product_id, qty, price) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiid", $transactionId, $item['id'], $item['qty'], $item['sell_price']);
                $stmt->execute();

                $stmt = $this->conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $item['qty'], $item['id']);
                $stmt->execute();

                // Log stock change
                Functions::logStockChange($this->conn, $item['id'], $userId, 'out', $item['qty'], 'Penjualan');
            }

            $this->conn->commit();
            return ['success' => true, 'nota_id' => $notaId, 'transaction_id' => $transactionId, 'change' => $change];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    public function validateStock($productId, $requestedQty) {
        $stmt = $this->conn->prepare("SELECT stock FROM products WHERE id = ? AND status = 'active'");
        $stmt->bind_param("i", $productId);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result && $result['stock'] >= $requestedQty;
    }

    public function getTransactionDetails($transactionId) {
        $stmt = $this->conn->prepare("SELECT t.*, u.username, td.qty, td.price, p.name as product_name FROM transactions t JOIN users u ON t.user_id = u.id JOIN transaction_details td ON t.id = td.transaction_id JOIN products p ON td.product_id = p.id WHERE t.id = ?");
        $stmt->bind_param("i", $transactionId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getDailySales($date = null) {
        $date = $date ?? date('Y-m-d');
        $stmt = $this->conn->prepare("SELECT SUM(total) as total_sales, COUNT(*) as transaction_count FROM transactions WHERE DATE(created_at) = ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProfitReport($startDate, $endDate) {
        $stmt = $this->conn->prepare("
            SELECT 
                SUM((td.price - p.cost_price) * td.qty) as total_profit,
                SUM(td.price * td.qty) as total_revenue,
                SUM(p.cost_price * td.qty) as total_cost
            FROM transaction_details td 
            JOIN products p ON td.product_id = p.id 
            JOIN transactions t ON td.transaction_id = t.id 
            WHERE DATE(t.created_at) BETWEEN ? AND ?
        ");
        $stmt->bind_param("ss", $startDate, $endDate);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
}
?>