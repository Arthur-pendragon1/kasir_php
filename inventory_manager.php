<?php
require_once 'db_connect.php';
require_once 'functions.php';

class InventoryManager {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAllProducts() {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.name");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductById($id) {
        $stmt = $this->conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getProductByBarcode($barcode) {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE barcode = ? AND status = 'active'");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function addProduct($data) {
        $stmt = $this->conn->prepare("INSERT INTO products (barcode, name, category_id, unit, cost_price, sell_price, stock, min_stock, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssisddiis", $data['barcode'], $data['name'], $data['category_id'], $data['unit'], $data['cost_price'], $data['sell_price'], $data['stock'], $data['min_stock'], $data['status']);
        return $stmt->execute();
    }

    public function updateProduct($id, $data) {
        $stmt = $this->conn->prepare("UPDATE products SET barcode=?, name=?, category_id=?, unit=?, cost_price=?, sell_price=?, stock=?, min_stock=?, status=? WHERE id=?");
        $stmt->bind_param("ssisddiisi", $data['barcode'], $data['name'], $data['category_id'], $data['unit'], $data['cost_price'], $data['sell_price'], $data['stock'], $data['min_stock'], $data['status'], $id);
        return $stmt->execute();
    }

    public function updateStock($id, $newStock, $userId, $reason = 'Manual adjustment') {
        $product = $this->getProductById($id);
        $change = $newStock - $product['stock'];
        $changeType = $change > 0 ? 'in' : 'out';

        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("UPDATE products SET stock = ? WHERE id = ?");
            $stmt->bind_param("ii", $newStock, $id);
            $stmt->execute();

            Functions::logStockChange($this->conn, $id, $userId, $changeType, abs($change), $reason);
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            return false;
        }
    }

    public function getLowStockProducts() {
        $stmt = $this->conn->prepare("SELECT * FROM products WHERE stock <= min_stock AND status = 'active' ORDER BY stock ASC");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getStockLog($productId = null, $limit = 50) {
        $query = "SELECT sl.*, p.name as product_name, u.username FROM stock_log sl JOIN products p ON sl.product_id = p.id JOIN users u ON sl.user_id = u.id";
        if ($productId) {
            $query .= " WHERE sl.product_id = ?";
        }
        $query .= " ORDER BY sl.created_at DESC LIMIT ?";

        $stmt = $this->conn->prepare($query);
        if ($productId) {
            $stmt->bind_param("ii", $productId, $limit);
        } else {
            $stmt->bind_param("i", $limit);
        }
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function getCategories() {
        $stmt = $this->conn->prepare("SELECT * FROM categories ORDER BY name");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    // Generate unique barcode with EAN-13 format
    public function generateUniqueBarcode() {
        // Format: YYMMDD + 6 digit random + 1 check digit
        $date = date('ymd');
        $sequence = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
        $barcode = $date . $sequence;
        
        // Calculate EAN-13 check digit
        $checkDigit = $this->calculateEAN13CheckDigit($barcode);
        $barcode = $barcode . $checkDigit;
        
        // Ensure uniqueness - if exists, try again
        while ($this->barcodeExists($barcode)) {
            $sequence = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $barcode = $date . $sequence;
            $checkDigit = $this->calculateEAN13CheckDigit($barcode);
            $barcode = $barcode . $checkDigit;
        }
        
        return $barcode;
    }

    // Calculate EAN-13 check digit
    private function calculateEAN13CheckDigit($code) {
        $sum = 0;
        for ($i = 0; $i < strlen($code); $i++) {
            $digit = (int)$code[$i];
            $sum += ($i % 2 == 0) ? $digit * 1 : $digit * 3;
        }
        $checkDigit = (10 - ($sum % 10)) % 10;
        return $checkDigit;
    }

    // Check if barcode already exists
    public function barcodeExists($barcode) {
        $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM products WHERE barcode = ?");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        return $result['count'] > 0;
    }
}
?>