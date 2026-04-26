# Panduan Lengkap Mengaktifkan Sistem POS untuk Akses Jaringan Berbeda

## Persiapan Awal

### 1. Pastikan XAMPP Berjalan
- Buka XAMPP Control Panel
- Start Apache (pastikan port 80 tidak konflik)
- Pastikan MySQL juga aktif jika diperlukan
- Buka `http://localhost/kasir-1/` di browser untuk test lokal

### 2. Buat SSH Key (Jika Belum Ada)
SSH key diperlukan untuk autentikasi tunnel Localhost.run.

Jalankan di PowerShell:
```
ssh-keygen -t rsa -b 4096 -f $env:USERPROFILE\.ssh\id_rsa -N '""'
```

Ini akan membuat key di `C:\Users\[Username]\.ssh\id_rsa` dan `id_rsa.pub`.

## Mengaktifkan Tunnel

### 3. Jalankan Reverse Tunnel
Gunakan perintah ini di terminal (PowerShell atau Command Prompt):

```
ssh -R 80:localhost:80 ssh.localhost.run
```

- **Pertama kali**: Akan muncul prompt "Are you sure you want to continue connecting (yes/no/[fingerprint])?" - ketik `yes`
- Tunggu hingga muncul pesan seperti:
  ```
  authenticated as anonymous user
  [random].lhr.life tunneled with tls termination, https://[random].lhr.life
  ```

### 4. Dapatkan URL Publik
Dari output terminal, cari baris:
```
https://[random].lhr.life
```

Contoh: `https://abc123.lhr.life`

URL ini adalah alamat publik untuk mengakses sistem POS Anda.

## Mengakses Sistem

### 5. Akses dari Browser
- Buka URL publik di browser mana saja (laptop, HP, dll.)
- Sistem akan otomatis redirect ke aplikasi POS
- Login dengan akun yang sudah ada

### 6. Menjaga Tunnel Tetap Aktif
- **JANGAN tutup terminal** yang menjalankan SSH tunnel
- Jika terminal ditutup, tunnel akan mati
- Untuk menjalankan di background (opsional):
  - Gunakan `ssh -R 80:localhost:80 ssh.localhost.run &` (di Linux/Mac)
  - Atau tinggalkan terminal terbuka

## Troubleshooting

### Jika "Permission denied (publickey)"
- Pastikan SSH key sudah dibuat (lihat langkah 2)
- Coba jalankan ulang perintah SSH

### Jika URL Menampilkan "no tunnel here :("
- Tunnel sudah mati, jalankan ulang langkah 3
- Pastikan Apache XAMPP masih aktif

### Jika Tidak Bisa Akses Lokal
- Cek XAMPP: Apache harus hijau
- Buka `http://localhost/kasir-1/` dulu
- Jika error, cek log Apache di XAMPP

### Jika Port 80 Konflik
- Buka Command Prompt sebagai Admin
- Jalankan: `netstat -ano | findstr :80`
- Matikan proses yang menggunakan port 80 (kecuali Apache)

## Tips Tambahan

- **Domain Tetap**: Daftar akun di https://localhost.run/docs/forever-free/ untuk domain yang tidak berubah
- **Keamanan**: Jangan bagikan URL ke orang yang tidak dipercaya
- **Alternatif**: Jika Localhost.run bermasalah, gunakan Serveo: `ssh -R 80:localhost:80 serveo.net`

## File Penting
- Aplikasi POS: `C:\xampp1\htdocs\kasir-1\`
- SSH Key: `C:\Users\[Username]\.ssh\id_rsa`
- Log XAMPP: `C:\xampp\apache\logs\error.log`

Jika ada masalah, berikan pesan error yang muncul.</content>
<parameter name="filePath">c:\xampp1\htdocs\kasir-1\TUNNEL_GUIDE.md