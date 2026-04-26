<?php
require_once 'config.php';
require_once 'auth.php';
require_once 'functions.php';
require_once 'inventory_manager.php';
require_once 'pos_engine.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireLogin();

$role = $auth->getRole();
$userId = $_SESSION['user_id'];

$inventory = new InventoryManager($db);
$pos = new POSEngine($db);

// Get low stock alerts
$lowStockProducts = $inventory->getLowStockProducts();

// Stats untuk admin
if ($role == 'admin') {
    $today = date('Y-m-d');
    $sales = $pos->getDailySales($today);
    $totalSales = $sales['total_sales'] ?? 0;
    $transactionCount = $sales['transaction_count'] ?? 0;

    // Top product
    $stmt = $db->prepare("SELECT p.name, SUM(td.qty) as total_qty FROM transaction_details td JOIN products p ON td.product_id = p.id JOIN transactions t ON td.transaction_id = t.id WHERE DATE(t.created_at) = ? GROUP BY p.id ORDER BY total_qty DESC LIMIT 1");
    $stmt->bind_param("s", $today);
    $stmt->execute();
    $topProduct = $stmt->get_result()->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php renderHead('Dashboard - POS Enterprise'); ?>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen page-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8 main-content">
            <h1 class="text-3xl font-bold mb-8 text-slate-800">Dashboard</h1>

            <?php if (!empty($lowStockProducts)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <h3 class="font-bold">Alert Stok Rendah!</h3>
                <ul class="mt-2">
                    <?php foreach ($lowStockProducts as $product): ?>
                    <li><?php echo htmlspecialchars($product['name']); ?> - Stok: <?php echo htmlspecialchars($product['stock']); ?> (Min: <?php echo htmlspecialchars($product['min_stock']); ?>)</li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <?php if ($role == 'admin'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-slate-700">Total Penjualan Hari Ini</h3>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo Functions::formatCurrency($totalSales); ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-slate-700">Jumlah Transaksi Hari Ini</h3>
                    <p class="text-3xl font-bold text-indigo-600"><?php echo $transactionCount; ?></p>
                </div>
                <div class="bg-white p-6 rounded-lg shadow-md">
                    <h3 class="text-lg font-semibold text-slate-700">Produk Terlaris Hari Ini</h3>
                    <p class="text-xl font-bold text-indigo-600"><?php echo $topProduct ? htmlspecialchars($topProduct['name'] . ' (' . $topProduct['total_qty'] . ' pcs)') : 'Tidak ada'; ?></p>
                </div>
            </div>
            <?php endif; ?>

            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4 text-slate-800">Transaksi Terbaru</h2>
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="px-4 py-2 text-left">Nota ID</th>
                            <th class="px-4 py-2 text-left">Total</th>
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Kasir</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $db->prepare("SELECT t.nota_id, t.total, t.created_at, u.username FROM transactions t JOIN users u ON t.user_id = u.id ORDER BY t.created_at DESC LIMIT 10");
                        $stmt->execute();
                        $recentTransactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                        foreach ($recentTransactions as $transaction):
                        ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($transaction['nota_id']); ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($transaction['total']); ?></td>
                            <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($transaction['created_at'])); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($transaction['username']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>