<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';
require_once 'inventory_manager.php';
require_once 'pos_engine.php';
require_once 'functions.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireLogin();

$role = $auth->getRole();
$userId = $_SESSION['user_id'];

if ($role === 'admin') {
    header('Location: dashboard.php');
    exit();
}

$inventory = new InventoryManager($db);
$pos = new POSEngine($db);

// Handle AJAX for barcode
if (isset($_GET['barcode'])) {
    $barcode = Functions::sanitizeInput($_GET['barcode']);
    if (!Functions::validateBarcode($barcode)) {
        echo json_encode(['error' => 'Barcode tidak valid']);
        exit;
    }

    $product = $inventory->getProductByBarcode($barcode);
    if ($product) {
        echo json_encode($product);
    } else {
        echo json_encode(['error' => 'Produk tidak ditemukan']);
    }
    exit;
}

// Handle AJAX for stock validation
if (isset($_GET['validate_stock'])) {
    $productId = (int)$_GET['product_id'];
    $qty = (int)$_GET['qty'];
    $valid = $pos->validateStock($productId, $qty);
    $product = $inventory->getProductById($productId);
    $stock = $product ? (int)$product['stock'] : 0;
    echo json_encode(['valid' => $valid, 'stock' => $stock]);
    exit;
}

// Handle checkout
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['checkout'])) {
    $cart = json_decode($_POST['cart'], true);
    $discount = 0;
    $subtotal = 0;
    foreach ($cart as $item) {
        $subtotal += $item['sell_price'] * $item['qty'];
    }
    $tax = Functions::calculateTax($subtotal);
    $paymentAmount = $subtotal + $tax;
    $paymentMethod = 'cash';

    $result = $pos->processTransaction($cart, $discount, $paymentAmount, $paymentMethod, $userId);
    echo json_encode($result);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS - POS Enterprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
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
                <a href="pos.php" class="block py-2 px-6 bg-slate-900">POS</a>
                <a href="inventory.php" class="block py-2 px-6 hover:bg-slate-700">Inventory</a>
                <?php if ($role == 'admin'): ?>
                <a href="reports.php" class="block py-2 px-6 hover:bg-slate-700">Reports</a>
                <a href="profile.php" class="block py-2 px-6 hover:bg-slate-700">Profile</a>
                <?php endif; ?>
                <a href="auth.php?logout=1" class="block py-2 px-6 hover:bg-slate-700">Logout</a>
            </nav>
        </div>

        <!-- Main POS Interface - Split Screen -->
        <div class="flex-1 flex">
            <!-- Left Side: Cart/Transaction List -->
            <div class="w-2/3 p-6 bg-white">
                <div class="mb-6">
                    <input type="text" id="barcode-input" placeholder="Scan barcode atau ketik..." 
                           class="w-full px-4 py-3 text-xl border-2 border-indigo-300 rounded-lg focus:border-indigo-500 focus:outline-none"
                           onkeypress="handleBarcodeInput(event)">
                </div>

                <div class="bg-slate-50 rounded-lg p-4 mb-6">
                    <h3 class="text-lg font-semibold mb-4">Daftar Belanja</h3>
                    <div id="cart-items" class="space-y-2 max-h-96 overflow-y-auto">
                        <!-- Cart items will be added here -->
                    </div>
                </div>
            </div>

            <!-- Right Side: Summary & Payment -->
            <div class="w-1/3 bg-slate-800 text-white p-6">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold mb-2">Total Belanja</h2>
                    <div id="total-display" class="text-5xl font-bold text-yellow-400">Rp 0</div>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Metode Pembayaran</label>
                        <div class="px-3 py-2 rounded bg-slate-700">Cash</div>
                    </div>

                    <div class="text-sm text-slate-200 bg-slate-700 p-4 rounded">
                        Transaksi hanya tunai. Total sudah termasuk PPN 11%.
                    </div>

                    <button onclick="processCheckout()" class="w-full bg-yellow-500 text-black py-4 rounded-lg font-bold text-xl hover:bg-yellow-400 transition-colors">
                        BAYAR (F8)
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let lastBarcodeTime = 0;

        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('barcode-input').focus();
            document.addEventListener('keydown', handleHotkeys);
        });

        function handleHotkeys(event) {
            if (event.key === 'F2') {
                event.preventDefault();
                document.getElementById('barcode-input').focus();
            } else if (event.key === 'F8') {
                event.preventDefault();
                processCheckout();
            }
        }

        function handleBarcodeInput(event) {
            if (event.key === 'Enter') {
                const now = Date.now();
                if (now - lastBarcodeTime < 100) return; // Debounce for scanner bouncing
                lastBarcodeTime = now;

                const barcode = event.target.value.trim();
                if (barcode) {
                    addToCart(barcode);
                    event.target.value = '';
                }
            }
        }

        function addToCart(barcode) {
            fetch(`pos.php?barcode=${encodeURIComponent(barcode)}`)
                .then(response => response.json())
                .then(product => {
                    if (product.error) {
                        Swal.fire('Error', product.error, 'error');
                        return;
                    }

                    // Check if product already in cart
                    const existingItem = cart.find(item => item.id === product.id);
                    if (existingItem) {
                        existingItem.qty++;
                    } else {
                        cart.push({
                            id: product.id,
                            barcode: product.barcode,
                            name: product.name,
                            sell_price: parseFloat(product.sell_price),
                            qty: 1
                        });
                    }

                    // Validate stock
                    validateStock(product.id, cart.find(item => item.id === product.id).qty);
                    updateCartDisplay();
                    updateTotal();
                })
                .catch(error => {
                    Swal.fire('Error', 'Gagal mengambil data produk', 'error');
                });
        }

        function validateStock(productId, qty) {
            fetch(`pos.php?validate_stock=1&product_id=${productId}&qty=${qty}`)
                .then(response => response.json())
                .then(result => {
                    if (!result.valid) {
                        Swal.fire('Stok Tidak Mencukupi', 'Stok produk tidak mencukupi untuk jumlah yang diminta', 'error');
                        const item = cart.find(item => item.id === productId);
                        if (item) {
                            item.qty = Math.min(item.qty, result.stock || 0);
                            if (item.qty <= 0) {
                                cart = cart.filter(cartItem => cartItem.id !== productId);
                            }
                            updateCartDisplay();
                            updateTotal();
                        }
                    }
                });
        }

        function updateCartDisplay() {
            const cartContainer = document.getElementById('cart-items');
            cartContainer.innerHTML = '';

            cart.forEach((item, index) => {
                const itemDiv = document.createElement('div');
                itemDiv.className = 'flex justify-between items-center p-3 bg-white rounded border';
                itemDiv.innerHTML = `
                    <div>
                        <div class="font-semibold">${item.name}</div>
                        <div class="text-sm text-slate-600">${Functions.formatCurrency(item.sell_price)} x ${item.qty}</div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <button onclick="changeQty(${index}, -1)" class="px-2 py-1 bg-red-500 text-white rounded">-</button>
                        <span>${item.qty}</span>
                        <button onclick="changeQty(${index}, 1)" class="px-2 py-1 bg-green-500 text-white rounded">+</button>
                        <button onclick="removeFromCart(${index})" class="px-2 py-1 bg-red-600 text-white rounded">X</button>
                    </div>
                `;
                cartContainer.appendChild(itemDiv);
            });
        }

        function changeQty(index, delta) {
            cart[index].qty += delta;
            if (cart[index].qty <= 0) {
                cart.splice(index, 1);
            } else {
                validateStock(cart[index].id, cart[index].qty);
            }
            updateCartDisplay();
            updateTotal();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
            updateTotal();
        }

        function updateTotal() {
            let subtotal = cart.reduce((sum, item) => sum + (item.sell_price * item.qty), 0);
            const tax = subtotal * 0.11; // PPN 11%
            const total = subtotal + tax;

            document.getElementById('total-display').textContent = Functions.formatCurrency(total);
        }

        function processCheckout() {
            if (cart.length === 0) {
                Swal.fire('Error', 'Keranjang kosong', 'error');
                return;
            }

            const total = parseFloat(document.getElementById('total-display').textContent.replace(/[^\d]/g, '')) || 0;

            fetch('pos.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `checkout=1&cart=${encodeURIComponent(JSON.stringify(cart))}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    Swal.fire('Berhasil!', `Transaksi berhasil. Nota: ${result.nota_id}`, 'success');
                    cart = [];
                    updateCartDisplay();
                    updateTotal();
                    document.getElementById('barcode-input').focus();
                } else {
                    Swal.fire('Error', result.error, 'error');
                }
            })
            .catch(error => {
                Swal.fire('Error', 'Gagal memproses transaksi', 'error');
            });
        }

        // Utility function for formatting currency
        const Functions = {
            formatCurrency: function(amount) {
                return 'Rp ' + amount.toLocaleString('id-ID');
            }
        };
    </script>
</body>
</html>
