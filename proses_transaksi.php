<?php
require_once 'config.php';

class Transaksi {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($nomor_nota, $total_bayar, $user_id) {
        $query = "INSERT INTO transaksi SET nomor_nota=:nomor_nota, total_bayar=:total_bayar, user_id=:user_id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":nomor_nota", $nomor_nota);
        $stmt->bindParam(":total_bayar", $total_bayar);
        $stmt->bindParam(":user_id", $user_id);

        if($stmt->execute()) {
            return $this->conn->lastInsertId();
        }
        return false;
    }

    public function addDetail($transaksi_id, $produk_id, $qty, $harga) {
        $subtotal = $qty * $harga;
        $query = "INSERT INTO detail_transaksi SET transaksi_id=:transaksi_id, produk_id=:produk_id, qty=:qty, harga=:harga, subtotal=:subtotal";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":transaksi_id", $transaksi_id);
        $stmt->bindParam(":produk_id", $produk_id);
        $stmt->bindParam(":qty", $qty);
        $stmt->bindParam(":harga", $harga);
        $stmt->bindParam(":subtotal", $subtotal);
        $stmt->execute();
    }

    public function updateStok($produk_id, $qty) {
        $query = "UPDATE produk SET stok = stok - :qty WHERE id = :id";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(":qty", $qty);
        $stmt->bindParam(":id", $produk_id);
        $stmt->execute();
    }
}

$database = new Database();
$db = $database->getConnection();
$transaksi = new Transaksi($db);

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $cart = $data['cart'];
    $total = $data['total'];

    // Generate nomor nota
    $nomor_nota = 'TRX-' . date('YmdHis');

    // Asumsi user_id = 1 (kasir1), bisa diubah untuk login session
    $user_id = 1;

    $transaksi_id = $transaksi->create($nomor_nota, $total, $user_id);

    if($transaksi_id) {
        foreach($cart as $item) {
            $transaksi->addDetail($transaksi_id, $item['id'], $item['qty'], $item['harga_jual']);
            $transaksi->updateStok($item['id'], $item['qty']);
        }
        echo json_encode(['success' => true, 'nomor_nota' => $nomor_nota]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal membuat transaksi']);
    }
}
?>