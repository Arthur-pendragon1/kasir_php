# 🏪 Kasir - Point of Sale (POS) System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)
[![Made with PHP](https://img.shields.io/badge/Made%20with-PHP-blue.svg)](https://www.php.net)
[![Security Policy](https://img.shields.io/badge/Security-Policy-brightgreen.svg)](SECURITY.md)

Sistem Point of Sale (POS) Indonesia yang dirancang untuk bisnis retail dan toko. Kasir menyediakan solusi lengkap manajemen penjualan, inventori, laporan, dan profil pengguna dengan antarmuka yang user-friendly.

## 🎯 Fitur Utama

### 💳 Penjualan & Transaksi
- **POS Engine**: Engine transaksi real-time dengan perhitungan cepat
- **Multi-Payment**: Dukungan berbagai metode pembayaran
- **Receipt Printing**: Cetak struk otomatis
- **Transaction History**: Riwayat transaksi lengkap

### 📦 Manajemen Inventori
- **Product Management**: Tambah, edit, hapus produk
- **Stock Control**: Monitoring stok real-time
- **Auto-Generate Barcode**: Generate barcode EAN-13 otomatis
- **Barcode Scanning**: Support scanning barcode produk

### 📊 Laporan & Analytics
- **Sales Reports**: Laporan penjualan detail
- **Inventory Reports**: Laporan persediaan barang
- **Daily Summary**: Ringkasan transaksi harian
- **Export Data**: Export ke format yang diperlukan

### 👤 Manajemen User
- **User Profiles**: Profil pengguna dengan role-based access
- **Authentication**: Sistem login aman dengan session management
- **User Dashboard**: Dashboard personal untuk setiap user

### 🌐 Remote Access
- **Ngrok Integration**: Setup untuk remote access via ngrok tunnel
- **Secure Connection**: HTTPS tunnel untuk keamanan data

## 🚀 Instalasi

### Prerequisites
- XAMPP (PHP 7.4+)
- MySQL/MariaDB
- Web Browser modern

### Step-by-Step Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/Arthur-pendragon1/kasir.git
   cd kasir
   ```

2. **Setup XAMPP**
   - Buka XAMPP Control Panel
   - Jalankan `Apache` dan `MySQL`

3. **Database Setup**
   ```bash
   # Buka phpMyAdmin: http://localhost/phpmyadmin
   # Import file database
   mysql -u root -p < database/database.sql
   ```

4. **Konfigurasi Database**
   - Edit `config.php`:
   ```php
   $DB_HOST = 'localhost';
   $DB_USER = 'root';
   $DB_PASS = '';
   $DB_NAME = 'kasir_db';
   ```

5. **Akses Aplikasi**
   - Buka browser: `http://localhost/kasir-1/`
   - Login dengan kredensial default atau buat user baru

## 📁 Struktur Project

```
kasir-1/
├── index.php                 # Halaman login
├── dashboard.php             # Dashboard utama
├── pos.php                   # Interface POS
├── inventory.php             # Manajemen inventori
├── reports.php               # Laporan & analytics
├── profile.php               # Profil pengguna
├── auth.php                  # Authentication logic
├── config.php                # Konfigurasi database
├── db_connect.php            # Database connection
├── functions.php             # Helper functions
├── pos_engine.php            # Engine transaksi
├── inventory_manager.php     # Inventory operations
├── report_generator.php      # Report generation
├── generate_code.php         # Barcode generation API
├── header.php                # Header template
├── sidebar.php               # Sidebar template
├── style.css                 # Styling utama
├── database/
│   └── database.sql          # Database schema
├── uploads/                  # User uploads folder
└── README.md                 # This file
```

## 🔧 Konfigurasi

### Database Connection (`config.php`)
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'kasir_db');
```

### Remote Access dengan Ngrok
Lihat panduan lengkap di [NGROK_SETUP_GUIDE.md](NGROK_SETUP_GUIDE.md)

## 📖 Fitur Detail

### Barcode Generation
- Generate barcode EAN-13 otomatis dengan check digit
- Cegah duplikasi barcode
- Real-time preview di form
- Lihat: [BARCODE_FEATURE.md](BARCODE_FEATURE.md)

### Tunnel & Remote Access
- Setup Ngrok untuk akses dari mana saja
- Lihat: [TUNNEL_GUIDE.md](TUNNEL_GUIDE.md)

## 🔐 Security

Sistem ini mengimplementasikan praktik keamanan terbaik:
- Input validation & sanitization
- SQL injection prevention
- Session management yang aman
- Password hashing dengan bcrypt/MD5
- CSRF protection

Untuk melaporkan vulnerability, lihat [SECURITY.md](SECURITY.md)

## 📝 Penggunaan

### Login
1. Buka `http://localhost/kasir-1/`
2. Masukkan username dan password
3. Klik tombol Login

### Tambah Produk
1. Buka menu Inventori
2. Klik "Tambah Produk"
3. Isi detail produk
4. Klik "Generate Barcode" untuk auto-generate
5. Simpan

### Proses Penjualan
1. Buka menu POS
2. Scan/Cari produk
3. Tambahkan ke keranjang
4. Atur jumlah dan discount
5. Lakukan pembayaran
6. Cetak struk

### Lihat Laporan
1. Buka menu Laporan
2. Pilih jenis laporan (Penjualan/Inventori)
3. Atur filter tanggal
4. View atau export data

## 🛠️ Development

### Menjalankan Locally
```bash
cd kasir-1
# Gunakan XAMPP atau PHP built-in server
php -S localhost:8000
```

### Debug Mode
Edit `debug.php` untuk mengaktifkan debug logging

## 📞 Support & Contact

- **Issues**: Buat issue di GitHub repository
- **Pull Requests**: Kontribusi sangat diterima
- **Security Issues**: Lihat [SECURITY.md](SECURITY.md) untuk pelaporan

## 📄 License

Project ini dilisensikan di bawah [MIT License](LICENSE). Lihat file LICENSE untuk detail lengkap.

## 🤝 Kontribusi

Kontribusi sangat diterima! Silakan:

1. Fork repository
2. Buat feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit changes (`git commit -m 'Add some AmazingFeature'`)
4. Push ke branch (`git push origin feature/AmazingFeature`)
5. Buka Pull Request

## 📊 Changelog

### Version 1.0.0
- Initial release
- Core POS functionality
- Inventory management
- Reporting system
- Barcode generation
- Ngrok integration

## 🎓 Pembelajaran & Tutorial

- [Setup Ngrok](NGROK_SETUP_GUIDE.md)
- [Barcode Feature](BARCODE_FEATURE.md)
- [Tunnel Setup](TUNNEL_GUIDE.md)

---

**Made with ❤️ for Indonesian businesses**
