<?php
require_once 'db_connect.php';

$database = new Database();
$db = $database->getConnection();

// Create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'kasir') NOT NULL,
        status ENUM('pending', 'active', 'inactive') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) UNIQUE NOT NULL
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        barcode VARCHAR(100) UNIQUE NOT NULL,
        name VARCHAR(255) NOT NULL,
        category_id INT,
        unit ENUM('Pcs', 'Box') DEFAULT 'Pcs',
        cost_price DECIMAL(10,2) NOT NULL,
        sell_price DECIMAL(10,2) NOT NULL,
        stock INT NOT NULL DEFAULT 0,
        min_stock INT NOT NULL DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        image VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS stock_log (
        id INT AUTO_INCREMENT PRIMARY KEY,
        product_id INT NOT NULL,
        user_id INT NOT NULL,
        change_type ENUM('in', 'out', 'adjustment') NOT NULL,
        quantity INT NOT NULL,
        reason TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS transactions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nota_id VARCHAR(20) UNIQUE NOT NULL,
        user_id INT NOT NULL,
        total DECIMAL(10,2) NOT NULL,
        discount DECIMAL(10,2) DEFAULT 0,
        tax DECIMAL(10,2) DEFAULT 0,
        payment_amount DECIMAL(10,2) NOT NULL,
        change_amount DECIMAL(10,2) DEFAULT 0,
        payment_method ENUM('cash', 'qris', 'transfer') DEFAULT 'cash',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",

    "CREATE TABLE IF NOT EXISTS transaction_details (
        id INT AUTO_INCREMENT PRIMARY KEY,
        transaction_id INT NOT NULL,
        product_id INT NOT NULL,
        qty INT NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB"
];

foreach ($tables as $sql) {
    if ($db->query($sql)) {
        echo "Table created successfully<br>";
    } else {
        echo "Error creating table: " . $db->error . "<br>";
    }
}

// Insert sample data
$sampleData = [
    "INSERT IGNORE INTO users (username, password, role, status) VALUES
    ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'active'),
    ('kasir1', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'kasir', 'pending')",

    "INSERT IGNORE INTO categories (name) VALUES
    ('Makanan'), ('Minuman'), ('Elektronik')",

    "INSERT IGNORE INTO products (barcode, name, category_id, unit, cost_price, sell_price, stock, min_stock, status) VALUES
    ('123456789', 'Produk A', 1, 'Pcs', 40000, 50000, 10, 5, 'active'),
    ('987654321', 'Produk B', 2, 'Box', 25000, 30000, 5, 2, 'active')"
];

foreach ($sampleData as $sql) {
    if ($db->query($sql)) {
        echo "Sample data inserted successfully<br>";
    } else {
        echo "Error inserting sample data: " . $db->error . "<br>";
    }
}

echo "Database setup completed!";
?>