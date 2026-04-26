<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

$debugInfo = [];
$testResult = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['test_login'])) {
        $username = $_POST['test_username'];
        $password = $_POST['test_password'];

        $debugInfo[] = "Testing login for username: $username";

        if (!$db) {
            $testResult = "FAIL: Database connection failed";
        } else {
            // Check if status column exists
            $statusExists = false;
            $statusCheck = $db->query("SHOW COLUMNS FROM users LIKE 'status'");
            if ($statusCheck && $statusCheck->num_rows > 0) {
                $statusExists = true;
            }

            $query = $statusExists
                ? "SELECT id, username, password, role, status FROM users WHERE username = ?"
                : "SELECT id, username, password, role FROM users WHERE username = ?";

            $stmt = $db->prepare($query);
            if (!$stmt) {
                $testResult = "FAIL: Failed to prepare query - " . $db->error;
            } else {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $user = $stmt->get_result()->fetch_assoc();

                if ($user) {
                    $statusText = $statusExists ? ($user['status'] ?? 'N/A') : 'N/A';
                    $debugInfo[] = "User found: ID={$user['id']}, Role={$user['role']}, Status={$statusText}";
                    $storedPassword = $user['password'];

                    $passwordValid = false;
                    if (password_verify($password, $storedPassword)) {
                        $passwordValid = true;
                        $debugInfo[] = "Password verified with password_verify()";
                    } elseif ($storedPassword === $password) {
                        $passwordValid = true;
                        $debugInfo[] = "Password matched as plain text";
                    } elseif (md5($password) === $storedPassword) {
                        $passwordValid = true;
                        $debugInfo[] = "Password matched as MD5";
                    } else {
                        $debugInfo[] = "Password does not match any method";
                    }

                    if ($passwordValid) {
                        $debugInfo[] = "Password is valid";
                        if (isset($user['status']) && $user['status'] !== 'active') {
                            $testResult = "FAIL: Account not active (status: {$user['status']})";
                        } else {
                            $testResult = "SUCCESS: Login would succeed";
                        }
                    } else {
                        $testResult = "FAIL: Invalid password";
                    }
                } else {
                    $debugInfo[] = "User not found in database";
                    $testResult = "FAIL: User not found";
                }
            }
        }
    }
}

// General debug info
$debugInfo[] = "Database connection: " . ($db ? "OK" : "FAILED");

if ($db) {
    // Check tables
    $tables = ['users', 'products', 'transactions', 'transaction_details', 'categories', 'stock_log'];
    foreach ($tables as $table) {
        $result = $db->query("SHOW TABLES LIKE '$table'");
        $debugInfo[] = "Table '$table': " . ($result && $result->num_rows > 0 ? "EXISTS" : "MISSING");
    }

    // Check admin user
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
    if ($stmt) {
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $debugInfo[] = "Admin user count: " . $result['count'];
    } else {
        $debugInfo[] = "Admin user count: FAILED - " . $db->error;
    }
} else {
    $debugInfo[] = "Cannot check tables and admin user - Database connection failed";
}

?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Page - POS Enterprise</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="bg-slate-100">
    <div class="min-h-screen p-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold mb-8 text-slate-800">Debug & Testing Page</h1>

            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-semibold mb-4">System Information</h2>
                <div class="space-y-2">
                    <?php foreach ($debugInfo as $info): ?>
                        <div class="text-sm text-slate-600"><?php echo htmlspecialchars($info); ?></div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-semibold mb-4">Test Login</h2>
                <form method="POST" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Username</label>
                        <input type="text" name="test_username" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <input type="password" name="test_password" required class="w-full px-3 py-2 border rounded">
                    </div>
                    <button type="submit" name="test_login" class="px-4 py-2 bg-indigo-600 text-white rounded hover:bg-indigo-700">
                        Test Login
                    </button>
                </form>

                <?php if ($testResult): ?>
                    <div class="mt-4 p-4 rounded <?php echo strpos($testResult, 'SUCCESS') === 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                        <?php echo htmlspecialchars($testResult); ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="bg-white p-6 rounded-lg shadow-md mb-6">
                <h2 class="text-xl font-semibold mb-4">Manual SQL untuk Admin</h2>
                <p class="text-sm text-slate-600 mb-3">Jika Anda ingin menambahkan user admin secara manual melalui SQL, gunakan contoh berikut:</p>
                <pre class="bg-slate-100 p-4 rounded overflow-x-auto text-sm">
-- Tambah kolom status jika belum ada
ALTER TABLE users ADD COLUMN status VARCHAR(20) DEFAULT 'active';

-- Tambah user admin baru dengan password terenkripsi
INSERT INTO users (username, password, role, status)
VALUES ('admin', 'PASSWORD_HASH_DI_DATABASE', 'admin', 'active');

-- Contoh jika ingin buat password plain text (kurang aman):
INSERT INTO users (username, password, role, status)
VALUES ('admin', 'admin123', 'admin', 'active');
                </pre>
            </div>

            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                <strong>Warning:</strong> This debug page should be removed or protected in production environment.
            </div>
        </div>
    </div>
</body>
</html>