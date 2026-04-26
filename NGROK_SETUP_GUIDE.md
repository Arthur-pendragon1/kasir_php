# 🌐 Panduan Setup Ngrok untuk POS System

## 📋 Daftar Isi
1. [Download & Install](#download--install)
2. [Setup Auth Token](#setup-auth-token)
3. [Menjalankan Tunnel](#menjalankan-tunnel)
4. [Testing & Sharing Link](#testing--sharing-link)
5. [Troubleshooting](#troubleshooting)

---

## Download & Install

### Step 1: Download Ngrok
- Buka: https://ngrok.com/download
- Download **ngrok for Windows (64-bit)**
- File akan di-download sebagai `ngrok-v3-windows-amd64.zip`

### Step 2: Extract File
1. Buka File Explorer
2. Buka folder `Downloads`
3. Klik kanan pada `ngrok-v3-windows-amd64.zip`
4. Pilih **Extract All...**
5. Extract ke folder yang mudah diakses, misal:
   - `C:\ngrok` (recommended)
   - Atau `C:\Users\YourName\ngrok`

### Step 3: Verifikasi Instalasi
```powershell
# Buka PowerShell dan navigasi ke folder ngrok
cd C:\ngrok

# Jalankan ngrok untuk verifikasi
.\ngrok.exe --version

# Output yang diharapkan:
# ngrok version 3.x.x
```

---

## Setup Auth Token

### Step 1: Buat Akun Ngrok (Gratis)
1. Buka: https://ngrok.com
2. Klik **Sign Up**
3. Daftar dengan email (atau Google/GitHub login)
4. Verifikasi email Anda

### Step 2: Ambil Auth Token
1. Login ke dashboard: https://dashboard.ngrok.com
2. Pergi ke **Your Authtoken** di sidebar
3. Copy token (contoh: `2bP_xxxxxxxxxxxxxxxx`)

### Step 3: Setup Auth Token di Windows
```powershell
# Buka PowerShell sebagai Administrator
# Navigasi ke folder ngrok
cd C:\ngrok

# Setup token (ganti TOKEN_ANDA dengan token dari dashboard)
.\ngrok.exe config add-authtoken TOKEN_ANDA

# Output yang diharapkan:
# Authtoken saved to configuration file: C:\Users\YourName\.ngrok2\ngrok.yml
```

---

## Menjalankan Tunnel

### Prasyarat
1. **Apache XAMPP sudah berjalan**
   - Buka XAMPP Control Panel
   - Klik tombol **Start** di samping Apache
   - Statusnya harus **Running**

2. **Verifikasi Apache**
   - Buka browser, akses: `http://localhost/kasir-1/`
   - Seharusnya muncul halaman login POS

### Step 1: Jalankan Ngrok
```powershell
# Buka PowerShell baru (tidak perlu admin)
cd C:\ngrok

# Jalankan tunnel ke port 80 (Apache)
.\ngrok.exe http 80

# Tunggu sampai muncul tulisan seperti ini:
# Session Status    online
# Account           your-email@gmail.com
# Version           3.x.x
# Region            Singapore
# Forwarding        https://xxxx-xx-xxx-xxx-x.ngrok.io -> http://localhost:80
# Connections       ttl    opn    rt1    rt5    p50    p90
#                   0      0      0.00   0.00   0.00   0.00
# Web Interface     http://127.0.0.1:4040
```

### Step 2: Copy Link Publik
- Link publik Anda: `https://xxxx-xx-xxx-xxx-x.ngrok.io`
- Link untuk POS: `https://xxxx-xx-xxx-xxx-x.ngrok.io/kasir-1/`

**JANGAN TUTUP TERMINAL NGROK INI** - Tunnel akan mati jika ditutup

---

## Testing & Sharing Link

### Testing Akses Lokal
```powershell
# Di terminal/PowerShell berbeda
# Coba akses link publik Anda
Start-Process "https://xxxx-xx-xxx-xxx-x.ngrok.io/kasir-1/"
```

### Sharing Ke Klien
1. Copy link publik: `https://xxxx-xx-xxx-xxx-x.ngrok.io/kasir-1/`
2. Bagikan via:
   - WhatsApp
   - Email
   - Chat aplikasi lainnya

3. Klien akan melihat:
   - Halaman login POS (karena sudah ter-setup)
   - Username/Password sesuai database Anda
   - Semua fitur POS berfungsi normal

### Monitoring Connection
- Buka: `http://127.0.0.1:4040` (Web Interface)
- Lihat semua request dari klien
- Monitor traffic & response time

---

## Troubleshooting

### ❌ Error: "ngrok is not recognized"
**Solusi:**
```powershell
# Pastikan Anda di folder yang benar
cd C:\ngrok

# Jalankan dengan path lengkap
.\ngrok.exe http 80
```

### ❌ Error: "Authtoken not configured"
**Solusi:**
```powershell
# Setup authtoken ulang
.\ngrok.exe config add-authtoken TOKEN_ANDA

# Cek konfigurasi
.\ngrok.exe config
```

### ❌ Error: "Address already in use"
**Solusi:**
- Apache belum running atau port 80 sudah digunakan
- Buka XAMPP Control Panel
- Klik **Start** untuk Apache
- Atau gunakan port berbeda: `.\ngrok.exe http 8080`

### ❌ Browser: ERR_NGROK_222 atau Error 502
**Solusi:**
1. Pastikan Apache XAMPP running
2. Cek: `http://localhost/kasir-1/` bisa akses
3. Restart ngrok
4. Clear browser cache (Ctrl+Shift+Delete)

### ❌ Link berubah setiap kali restart
**Ini normal untuk Ngrok free tier!**

**Solusi:**
- Upgrade ke Ngrok Pro (berbayar, link permanent)
- Atau gunakan Cloudflared (free tier, link permanent)
- Atau catat link baru setiap kali update ke klien

---

## 💡 Tips Penggunaan

### Membuat Link Tidak Berubah (Ngrok Pro)
```powershell
# Jika sudah upgrade ke Ngrok Pro
.\ngrok.exe http 80 --domain your-reserved-domain.ngrok.io

# Link akan tetap sama selamanya
```

### Menutup Tunnel dengan Aman
```powershell
# Tekan Ctrl+C di terminal ngrok
# Tunggu sampai keluar dengan aman
```

### Menjalankan di Background
```powershell
# Jika ingin ngrok jalan di background
Start-Process -NoNewWindow -FilePath "C:\ngrok\ngrok.exe" -ArgumentList "http 80"
```

---

## 🔐 Keamanan

### ✅ Aman
- Database MySQL tetap internal (tidak terbuka)
- Authentication login sudah ada di POS
- HTTPS automatic (Ngrok handle SSL)

### ⚠️ Perhatian
- Jangan share link ke orang yang tidak terpercaya
- Batasi akses ke klien yang sudah dikonfirmasi
- Monitor Web Interface (`http://127.0.0.1:4040`) untuk tracking

---

## 📊 Batas & Limitasi

| Item | Ngrok Free | Ngrok Pro |
|------|-----------|----------|
| Request/detik | ~20 | Unlimited |
| Link berganti | Setiap restart | Permanent |
| Session timeout | 8 jam | Unlimited |
| Cost | Gratis | $5-20/bulan |

---

## 🎯 Checklist Setup

- [ ] Download Ngrok
- [ ] Extract ke C:\ngrok
- [ ] Daftar akun Ngrok (https://ngrok.com)
- [ ] Copy auth token dari dashboard
- [ ] Setup token: `.\ngrok.exe config add-authtoken TOKEN`
- [ ] Verifikasi Apache running di XAMPP
- [ ] Jalankan: `.\ngrok.exe http 80`
- [ ] Copy link publik
- [ ] Test akses: `https://xxxxx.ngrok.io/kasir-1/`
- [ ] Bagikan ke klien

---

**Pertanyaan? Lihat bagian Troubleshooting di atas!**
