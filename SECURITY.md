# 🔐 Security Policy

## Supported Versions

| Version | Supported          | Status       |
|---------|-------------------|--------------|
| 1.0.x   | ✅ Yes            | Current      |
| < 1.0   | ❌ No             | Deprecated   |

Kami merekomendasikan selalu menggunakan versi terbaru untuk keamanan maksimal.

## Reporting a Vulnerability

Kami menghargai laporan keamanan yang bertanggung jawab. **JANGAN** membuat public issue untuk vulnerability.

### Cara Melaporkan

1. **Email**: Kirim laporan ke `security@kasir-pos.local` atau kontak maintainer
   
2. **Informasi yang Diperlukan**:
   - Deskripsi vulnerability
   - Lokasi file dan kode yang affected
   - Langkah reproduksi (jika ada)
   - Potensi impact
   - Saran fix (opsional)

3. **Timeframe Respons**:
   - Initial acknowledgment: 24-48 jam
   - Fix dan patch: 7-30 hari (tergantung severity)

4. **Disclosure Policy**:
   - Koordinasi release date sebelum publikasi
   - Credit reporter dalam release notes (jika diinginkan)
   - Minimal 30 hari embargoed period sebelum disclosure publik

## Security Best Practices

Saat menggunakan Kasir POS, implementasikan praktik keamanan berikut:

### 1. Installation & Setup

```php
// ✅ GOOD - Secure database setup
define('DB_HOST', 'localhost');
define('DB_USER', 'kasir_user');         // Jangan 'root'
define('DB_PASS', 'StrongPassword123!'); // Password kompleks
define('DB_NAME', 'kasir_db');

// ❌ BAD - Dangerous defaults
define('DB_USER', 'root');
define('DB_PASS', '');
```

### 2. Database Security

- **Separate User**: Gunakan database user khusus (bukan root)
- **Strong Password**: Password 16+ karakter dengan mix alphanumeric & symbols
- **Limited Privileges**: Hanya berikan permissions yang diperlukan
- **Backups**: Regular backup database dengan enkripsi
- **Access Control**: Restrict database access dari IP tertentu saja

```bash
# Example: Create secure database user
CREATE USER 'kasir_user'@'localhost' IDENTIFIED BY 'SecureP@ssw0rd!';
GRANT SELECT, INSERT, UPDATE, DELETE ON kasir_db.* TO 'kasir_user'@'localhost';
FLUSH PRIVILEGES;
```

### 3. Authentication & Authorization

- **Session Timeout**: Set session timeout sesuai kebutuhan bisnis
- **Password Policy**: Enforce strong passwords (minimum 8 karakter)
- **Two-Factor Auth**: Pertimbangkan implementasi 2FA untuk admin
- **Role-Based Access**: Enforce proper role-based access control

### 4. Input Validation

```php
// ✅ GOOD - Always sanitize input
$product_name = trim($_POST['product_name']);
$product_name = htmlspecialchars($product_name);
$product_name = filter_var($product_name, FILTER_SANITIZE_STRING);

// ✅ GOOD - Use prepared statements
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();

// ❌ BAD - SQL Injection vulnerability
$query = "SELECT * FROM products WHERE id = " . $_GET['id'];
$result = $conn->query($query);
```

### 5. File Upload Security

```php
// ✅ GOOD - Secure file upload
$allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
$max_size = 5 * 1024 * 1024; // 5MB

if (!in_array($_FILES['image']['type'], $allowed_types)) {
    die('Invalid file type');
}

if ($_FILES['image']['size'] > $max_size) {
    die('File too large');
}

// Generate unique filename
$new_filename = uniqid() . '_' . basename($_FILES['image']['name']);
$upload_path = 'uploads/' . $new_filename;

// ❌ BAD - Dangerous practices
move_uploaded_file($_FILES['file']['tmp_name'], 'uploads/' . $_FILES['file']['name']);
```

### 6. HTTPS & Encryption

- **HTTPS**: Selalu gunakan HTTPS, terutama untuk production
- **SSL Certificate**: Gunakan valid SSL certificate
- **Data Encryption**: Enkripsi sensitive data (passwords, PII)

```php
// Use ngrok HTTPS tunnel
// ✅ https://xxxxx.ngrok.io (HTTPS)
// ❌ http://xxxxx.ngrok.io (HTTP - avoid)
```

### 7. Logging & Monitoring

```php
// Log suspicious activities
function log_security_event($event_type, $details) {
    $log_entry = date('Y-m-d H:i:s') . " | " . $event_type . " | " . $details . "\n";
    file_put_contents('logs/security.log', $log_entry, FILE_APPEND);
}

// Example: Log failed login attempts
log_security_event('FAILED_LOGIN', 'User: ' . $_POST['username'] . ' | IP: ' . $_SERVER['REMOTE_ADDR']);
```

### 8. Regular Updates

- **PHP Version**: Gunakan PHP 7.4+ (preferably 8.0+)
- **Dependencies**: Update semua dependencies secara berkala
- **Security Patches**: Apply security patches segera

### 9. Configuration Hardening

```php
// ✅ GOOD - Secure configuration
error_reporting(E_ALL);
ini_set('display_errors', 0);              // Jangan tampilkan error ke user
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php_errors.log');

session_set_cookie_params([
    'secure' => true,                       // HTTPS only
    'httponly' => true,                     // No JavaScript access
    'samesite' => 'Strict'                  // CSRF protection
]);

// ❌ BAD - Exposed errors
error_reporting(0);
display_errors = On;
```

### 10. Ngrok Security

Saat menggunakan Ngrok untuk remote access:

```bash
# ✅ GOOD - Ngrok dengan basic auth
ngrok http --basic-auth="user:password" 8000

# ✅ GOOD - Limit access ke IP tertentu
ngrok http --auth="token" 8000

# ❌ BAD - Public tanpa authentication
ngrok http 8000
```

## Known Security Issues

Saat ini tidak ada known security issues yang outstanding.

## Security Headers

Tambahkan security headers di `header.php`:

```php
// Content Security Policy
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'");

// Prevent clickjacking
header("X-Frame-Options: SAMEORIGIN");

// Prevent MIME type sniffing
header("X-Content-Type-Options: nosniff");

// Enable XSS protection
header("X-XSS-Protection: 1; mode=block");

// Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");
```

## Dependencies Security

Selalu keep dependencies updated:

```bash
# Check for vulnerable dependencies
composer audit

# Update composer packages
composer update --with-all-dependencies
```

## Incident Response

Jika terjadi security incident:

1. **Identify**: Tentukan scope dan severity
2. **Isolate**: Isolate affected systems
3. **Notify**: Hubungi affected users
4. **Remediate**: Fix vulnerability
5. **Review**: Post-incident analysis

## Compliance

Sistem ini dirancang untuk memenuhi:
- ✅ Basic data protection standards
- ✅ PHP security best practices
- ✅ OWASP Top 10 awareness

## Additional Resources

- [OWASP Top 10](https://owasp.org/Top10/)
- [PHP Security](https://www.php.net/manual/en/security.php)
- [CWE Top 25](https://cwe.mitre.org/top25/)

---

**Last Updated**: 2026-04-26

Terima kasih atas komitmen Anda terhadap keamanan Kasir POS! 🔐
