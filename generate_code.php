<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'inventory_manager.php';

header('Content-Type: application/json');

// Check authentication
$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Check if admin
if ($auth->getRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit();
}

// Handle generate barcode request
if (isset($_POST['action']) && $_POST['action'] === 'generate_barcode') {
    try {
        $inventory = new InventoryManager($db);
        $barcode = $inventory->generateUniqueBarcode();
        
        echo json_encode([
            'success' => true,
            'barcode' => $barcode,
            'message' => 'Barcode generated successfully'
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request']);
}
?>
