<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'functions.php';
require_once 'report_generator.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireRole('admin');

$reports = new ReportGenerator($db);

// Handle export
if (isset($_GET['export']) && isset($_GET['type'])) {
    $startDate = $_GET['start_date'] ?? date('Y-m-01');
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $type = $_GET['type'];

    if ($type === 'sales') {
        $data = $reports->getSalesReport($startDate, $endDate);
        $reports->exportToCSV($data, 'sales_report.csv');
    } elseif ($type === 'profit') {
        $data = $reports->getProfitReport($startDate, $endDate);
        $reports->exportToCSV($data, 'profit_report.csv');
    }
    exit;
}

// Default date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$salesReport = $reports->getSalesReport($startDate, $endDate);
$profitReport = $reports->getProfitReport($startDate, $endDate);
$topProducts = $reports->getTopProducts($startDate, $endDate);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php renderHead('Reports - POS Enterprise'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen page-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8 main-content">
            <h1 class="text-3xl font-bold mb-8 text-slate-800">Laporan</h1>

            <!-- Date Filter -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <form method="GET" class="flex space-x-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Tanggal Mulai</label>
                        <input type="date" name="start_date" value="<?php echo $startDate; ?>" class="px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Tanggal Akhir</label>
                        <input type="date" name="end_date" value="<?php echo $endDate; ?>" class="px-3 py-2 border rounded">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Filter</button>
                    </div>
                </form>
            </div>

            <!-- Sales Report -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Laporan Penjualan</h2>
                    <a href="?export=1&type=sales&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Export CSV</a>
                </div>
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="px-4 py-2 text-left">Periode</th>
                            <th class="px-4 py-2 text-left">Total Penjualan</th>
                            <th class="px-4 py-2 text-left">Diskon</th>
                            <th class="px-4 py-2 text-left">Pajak</th>
                            <th class="px-4 py-2 text-left">Jumlah Transaksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($salesReport as $row): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo $row['period']; ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['total_sales']); ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['total_discount']); ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['total_tax']); ?></td>
                            <td class="px-4 py-2"><?php echo $row['transaction_count']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Profit Report -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold">Laporan Laba</h2>
                    <a href="?export=1&type=profit&start_date=<?php echo $startDate; ?>&end_date=<?php echo $endDate; ?>" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">Export CSV</a>
                </div>
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="px-4 py-2 text-left">Tanggal</th>
                            <th class="px-4 py-2 text-left">Laba</th>
                            <th class="px-4 py-2 text-left">Pendapatan</th>
                            <th class="px-4 py-2 text-left">Biaya</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($profitReport as $row): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo $row['date']; ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['profit']); ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['revenue']); ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($row['cost']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Top Products -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-bold mb-4">Produk Terlaris</h2>
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="px-4 py-2 text-left">Produk</th>
                            <th class="px-4 py-2 text-left">Jumlah Terjual</th>
                            <th class="px-4 py-2 text-left">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $product): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo $product['name']; ?></td>
                            <td class="px-4 py-2"><?php echo $product['total_qty']; ?></td>
                            <td class="px-4 py-2"><?php echo Functions::formatCurrency($product['total_revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>