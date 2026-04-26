<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'inventory_manager.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireLogin();

$role = $auth->getRole();
$userId = $_SESSION['user_id'];
$isAdmin = $role === 'admin';

$inventory = new InventoryManager($db);

// Handle AJAX requests for product details
if (isset($_GET['get_product'])) {
    $productId = (int)$_GET['get_product'];
    $product = $inventory->getProductById($productId);
    header('Content-Type: application/json');
    echo json_encode($product ?: ['error' => 'Product not found']);
    exit();
}

// Handle CRUD operations
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_product'])) {
        $data = [
            'barcode' => Functions::sanitizeInput($_POST['barcode']),
            'name' => Functions::sanitizeInput($_POST['name']),
            'category_id' => (int)$_POST['category_id'],
            'unit' => $_POST['unit'] ?: 'Pcs',
            'cost_price' => (float)($_POST['cost_price'] ?? 0),
            'sell_price' => (float)$_POST['sell_price'],
            'stock' => (int)$_POST['stock'],
            'min_stock' => (int)($_POST['min_stock'] ?? 0),
            'status' => $_POST['status'] ?: 'active'
        ];

        if ($inventory->addProduct($data)) {
            Functions::logStockChange($db, $db->insert_id, $userId, 'in', $data['stock'], 'Penambahan produk baru');
            header("Location: inventory.php?success=Product added successfully");
        } else {
            header("Location: inventory.php?error=Failed to add product");
        }
        exit();
    } elseif (isset($_POST['update_product'])) {
        $id = (int)$_POST['id'];
        $oldProduct = $inventory->getProductById($id);
        $data = [
            'barcode' => Functions::sanitizeInput($_POST['barcode']),
            'name' => Functions::sanitizeInput($_POST['name']),
            'category_id' => (int)$_POST['category_id'],
            'unit' => $_POST['unit'] ?: 'Pcs',
            'cost_price' => (float)($_POST['cost_price'] ?? 0),
            'sell_price' => (float)$_POST['sell_price'],
            'stock' => (int)$_POST['stock'],
            'min_stock' => (int)($_POST['min_stock'] ?? 0),
            'status' => $_POST['status'] ?: 'active'
        ];

        if ($inventory->updateProduct($id, $data)) {
            $stockChange = $data['stock'] - $oldProduct['stock'];
            if ($stockChange != 0) {
                $changeType = $stockChange > 0 ? 'in' : 'out';
                Functions::logStockChange($db, $id, $userId, $changeType, abs($stockChange), 'Update stok produk');
            }
            header("Location: inventory.php?success=Product updated successfully");
        } else {
            header("Location: inventory.php?error=Failed to update product");
        }
        exit();
    } elseif (isset($_POST['stock_opname']) && $role == 'admin') {
        $id = (int)$_POST['id'];
        $newStock = (int)$_POST['new_stock'];
        $reason = Functions::sanitizeInput($_POST['reason']);

        if ($inventory->updateStock($id, $newStock, $userId, $reason)) {
            header("Location: inventory.php?success=Stock updated successfully");
        } else {
            header("Location: inventory.php?error=Failed to update stock");
        }
        exit();
    }
}

// Get data
$products = $inventory->getAllProducts();
usort($products, function ($a, $b) {
    $aOut = $a['stock'] <= 0 ? 0 : 1;
    $bOut = $b['stock'] <= 0 ? 0 : 1;
    if ($aOut !== $bOut) {
        return $aOut - $bOut;
    }
    if ($a['stock'] !== $b['stock']) {
        return $a['stock'] - $b['stock'];
    }
    return strcmp($a['name'], $b['name']);
});
$categories = $inventory->getCategories();
$stockLog = $inventory->getStockLog();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory - POS Enterprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }

        /* Modal Scroll Enhancements */
        #productModal .modal-content {
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        #productModal .modal-body {
            flex: 1;
            overflow-y: auto;
            padding: 1.5rem;
        }

        #productModal .modal-header,
        #productModal .modal-footer {
            flex-shrink: 0;
            background: white;
            z-index: 10;
        }

        /* Custom scrollbar for modal */
        #productModal .modal-body::-webkit-scrollbar {
            width: 6px;
        }

        #productModal .modal-body::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }

        #productModal .modal-body::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }

        #productModal .modal-body::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            #productModal .modal-dialog {
                margin: 0.5rem;
                max-width: calc(100vw - 1rem);
            }

            #productModal .modal-body {
                padding: 1rem;
            }
        }
    </style>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <div class="w-64 bg-slate-800 text-white">
            <div class="p-6">
                <h2 class="text-xl font-bold">POS Enterprise</h2>
                <p class="text-sm">Welcome, <?php echo $_SESSION['username']; ?> (<?php echo $role; ?>)</p>
            </div>
            <nav class="mt-6">
                <a href="dashboard.php" class="block py-2 px-6 hover:bg-slate-700">Dashboard</a>
                <?php if ($role !== 'admin'): ?>
                <a href="pos.php" class="block py-2 px-6 hover:bg-slate-700">POS</a>
                <?php endif; ?>
                <a href="inventory.php" class="block py-2 px-6 bg-slate-900">Inventory</a>
                <?php if ($role == 'admin'): ?>
                <a href="reports.php" class="block py-2 px-6 hover:bg-slate-700">Reports</a>
                <a href="users.php" class="block py-2 px-6 hover:bg-slate-700">Users</a>
                <a href="profile.php" class="block py-2 px-6 hover:bg-slate-700">Profile</a>
                <?php endif; ?>
                <a href="auth.php?logout=1" class="block py-2 px-6 hover:bg-slate-700">Logout</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-3xl font-bold text-slate-800"><?php echo $isAdmin ? 'Manajemen Inventory' : 'Daftar Stok Barang'; ?></h1>
                <?php if ($isAdmin): ?>
                <button onclick="showAddProductModal()" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">Tambah Produk</button>
                <?php endif; ?>
            </div>

            <?php if (isset($_GET['success'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <?php echo $_GET['success']; ?>
            </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?php echo $_GET['error']; ?>
            </div>
            <?php endif; ?>

            <!-- Products Table -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Daftar Produk</h2>
                    <?php if (!$isAdmin): ?>
                    <div class="bg-slate-100 border border-slate-300 text-slate-700 p-4 rounded mb-4">
                        Anda hanya dapat melihat stok barang. Tidak ada akses untuk menambah atau mengubah data.
                    </div>
                    <?php endif; ?>
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-slate-200">
                                <th class="px-4 py-2 text-left">Barcode</th>
                                <th class="px-4 py-2 text-left">Nama</th>
                                <th class="px-4 py-2 text-left">Kategori</th>
                                <th class="px-4 py-2 text-left">Satuan</th>
                                <th class="px-4 py-2 text-left">Harga Jual</th>
                                <th class="px-4 py-2 text-left">Stok</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <?php if ($isAdmin): ?><th class="px-4 py-2 text-left">Aksi</th><?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr class="border-b <?php echo $product['stock'] <= 0 ? 'bg-red-50' : ''; ?>">
                                <td class="px-4 py-2"><?php echo $product['barcode']; ?></td>
                                <td class="px-4 py-2"><?php echo $product['name']; ?></td>
                                <td class="px-4 py-2"><?php echo $product['category_name']; ?></td>
                                <td class="px-4 py-2"><?php echo $product['unit']; ?></td>
                                <td class="px-4 py-2"><?php echo Functions::formatCurrency($product['sell_price']); ?></td>
                                <td class="px-4 py-2 text-center <?php echo $product['stock'] <= 0 ? 'text-red-600 font-bold' : ($product['stock'] < 5 ? 'text-yellow-600 font-semibold' : ''); ?>">
                                    <?php echo $product['stock']; ?><?php if ($product['stock'] <= 0): ?> <span class="text-sm text-red-700">(Habis)</span><?php endif; ?>
                                </td>
                                <td class="px-4 py-2">
                                    <span class="px-2 py-1 rounded text-xs <?php echo $product['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($product['status']); ?>
                                    </span>
                                </td>
                                <?php if ($isAdmin): ?>
                                <td class="px-4 py-2 space-x-2">
                                    <button onclick="editProduct(<?php echo $product['id']; ?>)" class="px-3 py-1 bg-blue-500 text-white rounded text-sm">Edit</button>
                                    <button onclick="stockOpname(<?php echo $product['id']; ?>, '<?php echo $product['name']; ?>', <?php echo $product['stock']; ?>)" class="px-3 py-1 bg-yellow-500 text-white rounded text-sm">Stock Opname</button>
                                </td>
                                <?php endif; ?>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($isAdmin): ?>
            <!-- Stock Log -->
            <div class="bg-white rounded-lg shadow-md mt-8">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Log Perubahan Stok</h2>
                    <table class="w-full table-auto">
                        <thead>
                            <tr class="bg-slate-200">
                                <th class="px-4 py-2 text-left">Produk</th>
                                <th class="px-4 py-2 text-left">Tipe</th>
                                <th class="px-4 py-2 text-left">Jumlah</th>
                                <th class="px-4 py-2 text-left">User</th>
                                <th class="px-4 py-2 text-left">Alasan</th>
                                <th class="px-4 py-2 text-left">Tanggal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stockLog as $log): ?>
                            <tr class="border-b">
                                <td class="px-4 py-2"><?php echo $log['product_name']; ?></td>
                                <td class="px-4 py-2"><?php echo ucfirst($log['change_type']); ?></td>
                                <td class="px-4 py-2"><?php echo $log['quantity']; ?></td>
                                <td class="px-4 py-2"><?php echo $log['username']; ?></td>
                                <td class="px-4 py-2"><?php echo $log['reason']; ?></td>
                                <td class="px-4 py-2"><?php echo date('d/m/Y H:i', strtotime($log['created_at'])); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($isAdmin): ?>
    <!-- Add/Edit Product Modal -->
    <div id="productModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] flex flex-col">
                <!-- Modal Header (Sticky) -->
                <div class="px-6 py-4 border-b border-slate-200 bg-white rounded-t-lg sticky top-0 z-10">
                    <h3 id="modalTitle" class="text-xl font-bold text-slate-800">Tambah Produk</h3>
                </div>

                <!-- Modal Body (Scrollable) -->
                <div class="flex-1 overflow-y-auto px-6 py-4">
                    <form id="productForm" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" id="productId">

                        <!-- Row 1: Barcode and Name -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="block text-sm font-medium mb-2 text-slate-700">Barcode</label>
                                <div class="flex items-center gap-2">
                                    <input type="text" name="barcode" id="barcode" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" placeholder="Kosongkan untuk auto-generate">
                                    <button type="button" onclick="generateBarcode()" class="px-3 py-2 bg-slate-600 text-white rounded hover:bg-slate-700 whitespace-nowrap">Generate</button>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Kosongkan untuk membuat kode otomatis berdasarkan nama produk.</p>
                            </div>
                            <div class="col-md-6">
                                <label class="block text-sm font-medium mb-2 text-slate-700">Nama Produk</label>
                                <input type="text" name="name" id="name" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                        </div>

                        <!-- Row 2: Category and Sell Price -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="block text-sm font-medium mb-2 text-slate-700">Kategori</label>
                                <select name="category_id" id="category_id" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo $category['name']; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="block text-sm font-medium mb-2 text-slate-700">Harga Jual</label>
                                <input type="number" name="sell_price" id="sell_price" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                        </div>

                        <!-- Row 3: Stock and Advanced Toggle -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <label class="block text-sm font-medium mb-2 text-slate-700">Stok</label>
                                <input type="number" name="stock" id="stock" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500" required>
                            </div>
                            <div class="col-md-6 flex items-end">
                                <button type="button" id="toggleAdvanced" onclick="toggleAdvancedOptions()" class="w-full px-3 py-2 border border-slate-300 rounded-md bg-slate-50 hover:bg-slate-100 text-slate-700 font-medium transition-colors">
                                    Opsi Lanjutan
                                </button>
                            </div>
                        </div>

                        <!-- Advanced Options -->
                        <div id="advancedOptions" class="hidden border border-slate-200 rounded-lg p-4 bg-slate-50 mb-4">
                            <!-- Row 4: Unit and Cost Price -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="block text-sm font-medium mb-2 text-slate-700">Satuan</label>
                                    <select name="unit" id="unit" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="Pcs">Pcs</option>
                                        <option value="Box">Box</option>
                                        <option value="Kg">Kg</option>
                                        <option value="Liter">Liter</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="block text-sm font-medium mb-2 text-slate-700">Harga Modal</label>
                                    <input type="number" name="cost_price" id="cost_price" step="0.01" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                            </div>

                            <!-- Row 5: Min Stock and Status -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <label class="block text-sm font-medium mb-2 text-slate-700">Stok Minimal</label>
                                    <input type="number" name="min_stock" id="min_stock" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                </div>
                                <div class="col-md-6">
                                    <label class="block text-sm font-medium mb-2 text-slate-700">Status</label>
                                    <select name="status" id="status" class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <option value="active">Aktif</option>
                                        <option value="inactive">Non-aktif</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Modal Footer (Sticky) -->
                <div class="px-6 py-4 border-t border-slate-200 bg-white rounded-b-lg sticky bottom-0 z-10">
                    <div class="flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">Batal</button>
                        <button type="submit" form="productForm" name="create_product" id="submitBtn" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">Simpan</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Stock Opname Modal -->
    <div id="stockOpnameModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-xl font-bold mb-4">Stock Opname</h3>
                <form method="POST">
                    <input type="hidden" name="id" id="opnameProductId">
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Produk</label>
                        <input type="text" id="opnameProductName" class="w-full px-3 py-2 border rounded" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Stok Saat Ini</label>
                        <input type="number" id="currentStock" class="w-full px-3 py-2 border rounded" readonly>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Stok Baru</label>
                        <input type="number" name="new_stock" id="newStock" class="w-full px-3 py-2 border rounded" required>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium mb-2">Alasan</label>
                        <textarea name="reason" class="w-full px-3 py-2 border rounded" placeholder="Alasan perubahan stok" required></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeOpnameModal()" class="px-4 py-2 bg-gray-500 text-white rounded">Batal</button>
                        <button type="submit" name="stock_opname" class="px-4 py-2 bg-yellow-600 text-white rounded">Update Stok</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function showAddProductModal() {
            document.getElementById('modalTitle').textContent = 'Tambah Produk';
            document.getElementById('productForm').reset();
            document.getElementById('submitBtn').name = 'create_product';
            document.getElementById('advancedOptions').classList.add('hidden');
            document.getElementById('toggleAdvanced').textContent = 'Opsi lanjutan';
            document.getElementById('productModal').classList.remove('hidden');
        }

        function editProduct(id) {
            // Fetch product data and populate modal
            fetch(`inventory.php?get_product=${id}`)
                .then(response => response.json())
                .then(product => {
                    document.getElementById('modalTitle').textContent = 'Edit Produk';
                    document.getElementById('productId').value = product.id;
                    document.getElementById('barcode').value = product.barcode;
                    document.getElementById('name').value = product.name;
                    document.getElementById('category_id').value = product.category_id;
                    document.getElementById('unit').value = product.unit;
                    document.getElementById('cost_price').value = product.cost_price;
                    document.getElementById('sell_price').value = product.sell_price;
                    document.getElementById('stock').value = product.stock;
                    document.getElementById('min_stock').value = product.min_stock;
                    document.getElementById('status').value = product.status;
                    document.getElementById('submitBtn').name = 'update_product';
                    document.getElementById('advancedOptions').classList.remove('hidden');
                    document.getElementById('toggleAdvanced').textContent = 'Sembunyikan opsi lanjutan';
                    document.getElementById('productModal').classList.remove('hidden');
                });
        }

        function toggleAdvancedOptions() {
            const options = document.getElementById('advancedOptions');
            const button = document.getElementById('toggleAdvanced');
            if (options.classList.contains('hidden')) {
                options.classList.remove('hidden');
                button.textContent = 'Sembunyikan opsi lanjutan';
            } else {
                options.classList.add('hidden');
                button.textContent = 'Opsi lanjutan';
            }
        }

        function closeModal() {
            document.getElementById('productModal').classList.add('hidden');
        }

        function generateBarcode() {
            const barcodeInput = document.getElementById('barcode');
            const button = event.target;
            const originalText = button.textContent;
            
            // Show loading state
            button.disabled = true;
            button.textContent = 'Generating...';
            
            // Call AJAX endpoint to generate barcode
            fetch('generate_code.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=generate_barcode'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    barcodeInput.value = data.barcode;
                    barcodeInput.classList.add('bg-green-50');
                    
                    // Show success message
                    const message = document.createElement('div');
                    message.className = 'text-xs text-green-600 mt-1';
                    message.textContent = '✓ Barcode berhasil di-generate';
                    
                    // Remove old message if exists
                    const oldMsg = barcodeInput.parentElement.nextElementSibling;
                    if (oldMsg && oldMsg.classList.contains('text-green-600')) {
                        oldMsg.remove();
                    }
                    
                    barcodeInput.parentElement.parentElement.appendChild(message);
                    
                    // Remove background color and message after 3 seconds
                    setTimeout(() => {
                        barcodeInput.classList.remove('bg-green-50');
                        if (message.parentElement) {
                            message.remove();
                        }
                    }, 3000);
                } else {
                    alert('Error: ' + (data.error || 'Gagal membuat barcode'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Gagal menghubungi server');
            })
            .finally(() => {
                // Restore button state
                button.disabled = false;
                button.textContent = originalText;
            });
        }

        function stockOpname(id, name, currentStock) {
            document.getElementById('opnameProductId').value = id;
            document.getElementById('opnameProductName').value = name;
            document.getElementById('currentStock').value = currentStock;
            document.getElementById('newStock').value = currentStock;
            document.getElementById('stockOpnameModal').classList.remove('hidden');
        }

        function closeOpnameModal() {
            document.getElementById('stockOpnameModal').classList.add('hidden');
        }
    </script>
</body>
</html>
