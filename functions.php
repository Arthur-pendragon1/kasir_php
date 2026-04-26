<?php
class Functions {
    public static function generateNotaId() {
        $date = date('Ymd');
        // Untuk production, gunakan counter dari database
        $counter = rand(1, 9999); // Placeholder, sebaiknya dari DB
        return 'POS-' . $date . '-' . str_pad($counter, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateTax($subtotal, $taxRate = 0.11) {
        return $subtotal * $taxRate;
    }

    public static function formatCurrency($amount) {
        return 'Rp ' . number_format($amount, 0, ',', '.');
    }

    public static function sanitizeInput($input) {
        return htmlspecialchars(strip_tags(trim($input)));
    }

    public static function validateBarcode($barcode) {
        return preg_match('/^[a-zA-Z0-9\-]+$/', $barcode);
    }

    public static function barcodeExists($conn, $barcode) {
        $stmt = $conn->prepare("SELECT id FROM products WHERE barcode = ? LIMIT 1");
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result && $result->num_rows > 0;
    }

    public static function generateBarcode($conn, $name = '') {
        $prefix = preg_replace('/[^A-Za-z0-9]/', '', strtoupper($name));
        $prefix = substr($prefix, 0, 3);
        if (empty($prefix)) {
            $prefix = 'PRD';
        }

        do {
            $random = rand(1000, 9999);
            $date = date('ymd');
            $barcode = $prefix . '-' . $date . '-' . str_pad($random, 4, '0', STR_PAD_LEFT);
        } while (self::barcodeExists($conn, $barcode));

        return $barcode;
    }

    public static function logStockChange($conn, $productId, $userId, $changeType, $quantity, $reason) {
        $stmt = $conn->prepare("INSERT INTO stock_log (product_id, user_id, change_type, quantity, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("iisis", $productId, $userId, $changeType, $quantity, $reason);
        return $stmt->execute();
    }
}
?>