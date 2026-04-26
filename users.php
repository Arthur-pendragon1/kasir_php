<?php
require_once 'config.php';
require_once 'db_connect.php';
require_once 'auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireRole('admin');
$role = $auth->getRole();

$statusColumn = false;
$stmt = $db->prepare("SHOW COLUMNS FROM users LIKE 'status'");
if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    $statusColumn = $result && $result->num_rows > 0;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_user'])) {
        $newUsername = trim($_POST['username']);
        $newPassword = $_POST['password'];
        $newRole = $_POST['role'];

        if (empty($newUsername) || empty($newPassword) || empty($newRole)) {
            $error = "Semua field harus diisi untuk menambahkan user baru.";
        } else {
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $newUsername);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $error = "Username sudah digunakan.";
            } else {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                if ($statusColumn) {
                    $stmt = $db->prepare("INSERT INTO users (username, password, role, status) VALUES (?, ?, ?, 'pending')");
                    $stmt->bind_param("sss", $newUsername, $hashedPassword, $newRole);
                } else {
                    $stmt = $db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $newUsername, $hashedPassword, $newRole);
                }

                if ($stmt && $stmt->execute()) {
                    $message = "User baru berhasil ditambahkan.";
                } else {
                    $error = "Gagal menambahkan user baru.";
                }
            }
        }
    } elseif (isset($_POST['approve'])) {
        $userId = $_POST['user_id'];
        if ($statusColumn) {
            $stmt = $db->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt && $stmt->execute()) {
                $message = "User approved successfully.";
            } else {
                $message = "Error approving user.";
            }
        } else {
            $error = "Kolom status tidak tersedia pada database. Silakan tambahkan kolom status terlebih dahulu.";
        }
    } elseif (isset($_POST['deactivate'])) {
        $userId = $_POST['user_id'];
        if ($statusColumn) {
            $stmt = $db->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt && $stmt->execute()) {
                $message = "User deactivated successfully.";
            } else {
                $message = "Error deactivating user.";
            }
        } else {
            $error = "Kolom status tidak tersedia pada database. Silakan tambahkan kolom status terlebih dahulu.";
        }
    } elseif (isset($_POST['reset_password'])) {
        $userId = $_POST['user_id'];
        $newPassword = password_hash('password123', PASSWORD_DEFAULT); // Default password
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->bind_param("si", $newPassword, $userId);
        if ($stmt && $stmt->execute()) {
            $message = "Password reset successfully. New password: password123";
        } else {
            $error = "Error resetting password.";
        }
    } elseif (isset($_POST['delete_user'])) {
        $userId = $_POST['user_id'];
        // Prevent admin from deleting themselves
        if ($userId == $_SESSION['user_id']) {
            $error = "You cannot delete your own account.";
        } else {
            $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->bind_param("i", $userId);
            if ($stmt && $stmt->execute()) {
                $message = "User deleted successfully.";
            } else {
                $error = "Error deleting user.";
            }
        }
    }
}

$selectQuery = $statusColumn ? "SELECT id, username, role, status, created_at FROM users ORDER BY created_at DESC" : "SELECT id, username, role, created_at FROM users ORDER BY created_at DESC";
$stmt = $db->prepare($selectQuery);
if ($stmt) {
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} else {
    $users = [];
    $error = "Error fetching users: " . $db->error;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php renderHead('Manage Users - POS Enterprise'); ?>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen page-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8 main-content">
            <h1 class="text-3xl font-bold mb-8 text-slate-800">Manage Users</h1>

            <?php if ($message): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <!-- Add User Form -->
            <div class="bg-white p-6 rounded-lg shadow-md mb-8">
                <h2 class="text-xl font-semibold mb-4">Add New User</h2>
                <form method="POST">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-slate-700 font-medium mb-2">Username</label>
                            <input type="text" name="username" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-slate-700 font-medium mb-2">Password</label>
                            <input type="password" name="password" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-slate-700 font-medium mb-2">Role</label>
                            <select name="role" required class="w-full px-3 py-2 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="kasir">Kasir</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" name="add_user" class="mt-4 bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">Add User</button>
                </form>
            </div>

            <!-- Users List -->
            <div class="bg-white p-6 rounded-lg shadow-md">
                <h2 class="text-xl font-semibold mb-4">Users List</h2>
                <table class="w-full table-auto">
                    <thead>
                        <tr class="bg-slate-200">
                            <th class="px-4 py-2 text-left">Username</th>
                            <th class="px-4 py-2 text-left">Role</th>
                            <?php if ($statusColumn): ?><th class="px-4 py-2 text-left">Status</th><?php endif; ?>
                            <th class="px-4 py-2 text-left">Created At</th>
                            <th class="px-4 py-2 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['username']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></td>
                            <?php if ($statusColumn): ?>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded-full text-xs <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : ($user['status'] === 'pending' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800'); ?>">
                                    <?php echo htmlspecialchars(ucfirst($user['status'])); ?>
                                </span>
                            </td>
                            <?php endif; ?>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="px-4 py-2">
                                <?php if ($statusColumn && isset($user['status']) && $user['status'] === 'pending'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="approve" class="bg-green-600 text-white px-3 py-1 rounded-md hover:bg-green-700 text-sm">Approve</button>
                                    </form>
                                <?php elseif ($statusColumn && isset($user['status']) && $user['status'] === 'active'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <button type="submit" name="deactivate" class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm">Deactivate</button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="inline ml-2">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="reset_password" onclick="return confirm('Reset password to default?')" class="bg-yellow-600 text-white px-3 py-1 rounded-md hover:bg-yellow-700 text-sm">Reset Password</button>
                                </form>
                                <form method="POST" class="inline ml-2">
                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                    <button type="submit" name="delete_user" onclick="return confirm('Are you sure you want to delete this user?')" class="bg-red-600 text-white px-3 py-1 rounded-md hover:bg-red-700 text-sm">Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>