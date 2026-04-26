<?php
require_once 'config.php';

class Auth {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $queryWithStatus = "SELECT id, username, password, role, status FROM users WHERE username = ?";
        $queryWithoutStatus = "SELECT id, username, password, role FROM users WHERE username = ?";
        $stmt = $this->conn->prepare($queryWithStatus);
        $hasStatus = true;

        if (!$stmt) {
            $stmt = $this->conn->prepare($queryWithoutStatus);
            $hasStatus = false;
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if ($user) {
            $storedPassword = $user['password'] ?? '';
            $passwordValid = false;

            if (!empty($storedPassword)) {
                if (password_verify($password, $storedPassword)) {
                    $passwordValid = true;
                } elseif ($storedPassword === $password) {
                    $passwordValid = true;
                } elseif (md5($password) === $storedPassword) {
                    $passwordValid = true;
                }
            }

            if ($passwordValid) {
                if ($hasStatus && $user['status'] !== 'active') {
                    return false;
                }
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                return true;
            }
        }
        return false;
    }

    public function logout() {
        session_destroy();
        header("Location: auth.php");
        exit();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }

    public function getRole() {
        return $_SESSION['role'] ?? null;
    }

    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header("Location: auth.php");
            exit();
        }
    }

    public function redirectIfAuthenticated() {
        if ($this->isLoggedIn()) {
            header("Location: dashboard.php");
            exit();
        }
    }

    public function requireRole($role) {
        if (!$this->isLoggedIn() || $this->getRole() !== $role) {
            header("Location: auth.php");
            exit();
        }
    }
}

if (basename(__FILE__) == basename($_SERVER['SCRIPT_FILENAME'])) {
    $database = new Database();
    $db = $database->getConnection();
    $auth = new Auth($db);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
        $username = $_POST['username'];
        $password = $_POST['password'];

        if ($auth->login($username, $password)) {
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Username atau password salah, atau akun belum diaktifkan oleh admin";
        }
    }

    if (isset($_GET['logout'])) {
        $auth->logout();
    }

    $auth->redirectIfAuthenticated();
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <?php renderHead('Login - POS Enterprise'); ?>
    </head>
    <body class="bg-slate-100 min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md login-card">
            <h1 class="text-2xl font-bold text-center mb-6 text-slate-800">POS Enterprise Login</h1>
            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-4">
                    <label class="block text-slate-700 font-medium mb-2">Username</label>
                    <input type="text" name="username" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500" required>
                </div>
                <div class="mb-6">
                    <label class="block text-slate-700 font-medium mb-2">Password</label>
                    <input type="password" name="password" class="w-full px-3 py-2 border border-slate-300 rounded-lg focus:outline-none focus:border-indigo-500" required>
                </div>
                <button type="submit" name="login" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition-colors">Login</button>
            </form>
            <div class="mt-4 text-center">
                <p class="text-sm text-slate-600">Lupa password? Hubungi admin untuk reset password.</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
