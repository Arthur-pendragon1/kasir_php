<?php
require_once 'config.php';

class Produk {
    private $conn;
    private $table_name = "produk";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getByBarcode($barcode) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE barcode = :barcode LIMIT 0,1";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":barcode", $barcode);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$database = new Database();
$db = $database->getConnection();
$produk = new Produk($db);

// Handle AJAX request for barcode
if(isset($_GET['barcode'])) {
    $barcode = $_GET['barcode'];
    $result = $produk->getByBarcode($barcode);
    if($result) {
        echo json_encode($result);
    } else {
        echo json_encode(['error' => 'Produk tidak ditemukan']);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kasir POS</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100" onload="document.getElementById('barcode-input').focus()">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-blue-800 text-white h-screen">
            <div class="p-4">
                <h2 class="text-2xl font-bold">POS System</h2>
            </div>
            <nav class="mt-4">
                <a href="kasir.php" class="block py-2 px-4 bg-blue-900">Kasir</a>
                <a href="inventory.php" class="block py-2 px-4 hover:bg-blue-700">Inventaris</a>
            </nav>
        </div>

        <!-- Main Content -->
        <div class="flex-1 p-8">
            <h1 class="text-3xl font-bold mb-8">Kasir POS</h1>

            <!-- Input Barcode -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <label class="block text-gray-700 mb-2">Scan Barcode</label>
                <input type="text" id="barcode-input" class="w-full p-2 border rounded text-xl" placeholder="Scan barcode..." autofocus>
            </div>

            <!-- Daftar Belanja -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-bold mb-4">Daftar Belanja</h2>
                <table class="w-full table-auto" id="cart-table">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="px-4 py-2">Nama</th>
                            <th class="px-4 py-2">Qty</th>
                            <th class="px-4 py-2">Harga</th>
                            <th class="px-4 py-2">Subtotal</th>
                            <th class="px-4 py-2">Aksi</th>
                        </tr>
                    </thead>
                    <tbody id="cart-body">
                        <!-- Items will be added here -->
                    </tbody>
                </table>
                <div class="mt-4 text-right">
                    <strong>Total: Rp <span id="total">0</span></strong>
                </div>
            </div>

            <!-- Tombol Proses Transaksi -->
            <div class="text-right">
                <button onclick="prosesTransaksi()" class="bg-green-500 text-white px-6 py-3 rounded text-xl">Proses Transaksi</button>
            </div>
        </div>
    </div>

    <script>
        let cart = [];
        let total = 0;

        document.getElementById('barcode-input').addEventListener('keypress', function(e) {
            if(e.key === 'Enter') {
                const barcode = this.value.trim();
                if(barcode) {
                    fetchProduk(barcode);
                    this.value = '';
                }
            }
        });

        function fetchProduk(barcode) {
            fetch(`kasir.php?barcode=${encodeURIComponent(barcode)}`)
                .then(response => response.json())
                .then(data => {
                    if(data.error) {
                        alert(data.error);
                    } else {
                        addToCart(data);
                    }
                })
                .catch(error => console.error('Error:', error));
        }

        function addToCart(produk) {
            const existing = cart.find(item => item.id === produk.id);
            if(existing) {
                existing.qty++;
                existing.subtotal = existing.qty * existing.harga_jual;
            } else {
                cart.push({
                    id: produk.id,
                    nama: produk.nama,
                    harga_jual: parseFloat(produk.harga_jual),
                    qty: 1,
                    subtotal: parseFloat(produk.harga_jual)
                });
            }
            updateCartDisplay();
        }

        function updateCartDisplay() {
            const tbody = document.getElementById('cart-body');
            tbody.innerHTML = '';
            total = 0;

            cart.forEach((item, index) => {
                total += item.subtotal;
                const row = `
                    <tr>
                        <td class="border px-4 py-2">${item.nama}</td>
                        <td class="border px-4 py-2">
                            <button onclick="changeQty(${index}, -1)" class="bg-red-500 text-white px-2 py-1 rounded">-</button>
                            ${item.qty}
                            <button onclick="changeQty(${index}, 1)" class="bg-green-500 text-white px-2 py-1 rounded">+</button>
                        </td>
                        <td class="border px-4 py-2">Rp ${item.harga_jual.toLocaleString()}</td>
                        <td class="border px-4 py-2">Rp ${item.subtotal.toLocaleString()}</td>
                        <td class="border px-4 py-2">
                            <button onclick="removeFromCart(${index})" class="bg-red-500 text-white px-2 py-1 rounded">Hapus</button>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += row;
            });

            document.getElementById('total').textContent = total.toLocaleString();
        }

        function changeQty(index, delta) {
            cart[index].qty += delta;
            if(cart[index].qty <= 0) {
                cart.splice(index, 1);
            } else {
                cart[index].subtotal = cart[index].qty * cart[index].harga_jual;
            }
            updateCartDisplay();
        }

        function removeFromCart(index) {
            cart.splice(index, 1);
            updateCartDisplay();
        }

        function prosesTransaksi() {
            if(cart.length === 0) {
                alert('Keranjang kosong!');
                return;
            }

            fetch('proses_transaksi.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ cart: cart, total: total })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert('Transaksi berhasil! Nomor Nota: ' + data.nomor_nota);
                    cart = [];
                    updateCartDisplay();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>