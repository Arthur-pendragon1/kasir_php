# 📦 Fitur Auto-Generate Barcode Produk

## 🎯 Ringkasan Fitur

Fitur ini memungkinkan Anda untuk **otomatis membuat kode barcode numerik 13-digit (EAN-13)** untuk produk yang tidak memiliki kode dari perusahaan (supplier). Barcode akan:
- ✅ Dihasilkan secara otomatis dengan format EAN-13 valid
- ✅ Mencegah duplikasi di database
- ✅ Real-time preview di form
- ✅ Dapat di-generate berkali-kali hingga puas

## 📋 Komponen Implementasi

### 1. **Backend Functions** (`inventory_manager.php`)

```php
// Generate barcode unik dengan check digit EAN-13
$barcode = $inventory->generateUniqueBarcode();

// Output: "260426123456" (format YYMMDDXXXXXX + check digit)
// Contoh: 260426 (26 Apr 2026) + 123456 (sequence) + 3 (check digit) = 2604261234563
```

**Method yang ditambahkan:**
- `generateUniqueBarcode()` - Generate barcode unik
- `calculateEAN13CheckDigit($code)` - Hitung check digit (validasi barcode)
- `barcodeExists($barcode)` - Cek duplikasi barcode

### 2. **AJAX Endpoint** (`generate_code.php`)

- **URL:** `generate_code.php`
- **Method:** POST
- **Parameter:** `action=generate_barcode`
- **Response:** JSON dengan barcode yang di-generate

```json
{
  "success": true,
  "barcode": "2604261234563",
  "message": "Barcode generated successfully"
}
```

### 3. **Frontend Integration** (`inventory.php`)

- **Button:** "Generate" di form Add/Edit Product
- **Behavior:**
  - Tampil loading state saat generate
  - Auto-fill field barcode
  - Highlight hijau & message sukses
  - Reset otomatis setelah 3 detik

## 🚀 Cara Menggunakan

### **Workflow 1: Tambah Produk Baru**

1. Klik **"+ Tambah Produk"** button di halaman Inventory
2. Form modal terbuka
3. Kosongkan field **"Barcode"** atau langsung klik **"Generate"**
4. Barcode numerik 13-digit akan di-isi otomatis
   - ✓ Contoh: `2604261234567`
5. Isi field lain (Nama, Kategori, Harga, Stok)
6. Klik **"Simpan"**

### **Workflow 2: Edit Produk**

1. Klik **"Edit"** pada produk yang ada
2. Di form edit, bisa generate barcode baru dengan klik **"Generate"**
3. Barcode lama akan diganti dengan barcode baru
4. Klik **"Simpan"**

## 📊 Format Barcode

**Format EAN-13:** `YYMMDD` + `6-digit random` + `1 check digit`

Contoh breakdown:
```
Barcode: 2604261234563
├─ 260426 = Tanggal (26 April 2026)
├─ 123456 = Random sequence (1-999999)
└─ 3      = Check digit (validasi EAN-13)
```

**Keunikan:**
- ✅ Setiap barcode unik di database
- ✅ Valid untuk di-scan dengan barcode reader
- ✅ Check digit otomatis mencegah input error

## 🔒 Keamanan

- Hanya **admin** yang bisa generate barcode
- Validasi authentication di `generate_code.php`
- Semua input disanitasi

## 🛠️ Troubleshooting

### **Tombol Generate tidak berfungsi?**
- Pastikan sudah login sebagai **admin**
- Cek browser console (F12) untuk error AJAX
- Pastikan file `generate_code.php` ada

### **Barcode yang dibuat selalu sama?**
- Normal - setiap generate akan berbeda karena ada random sequence
- Duplikasi dicegah otomatis

### **Barcode tidak ter-save di database?**
- Pastikan field barcode tidak kosong saat submit form
- Jika ingin auto-fill kosong, modify code di server

## 📝 Database Integration

Tabel `products` sudah support barcode dengan column `barcode`:

```sql
ALTER TABLE products ADD UNIQUE KEY unique_barcode (barcode);
```

Pastikan ada UNIQUE constraint agar tidak ada duplikasi.

## 🔧 Customization

Jika ingin ubah format barcode, edit di `inventory_manager.php`:

```php
// Ubah format di sini:
$date = date('ymd');  // YYMMDD
$sequence = str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);  // 000001-999999
```

---

**Version:** 1.0 | **Date:** April 26, 2026 | **Status:** ✅ Active
