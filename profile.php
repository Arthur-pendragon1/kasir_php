<?php
require_once 'config.php';
require_once 'auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);
$auth->requireLogin();
$auth->requireRole('admin');

$role = $auth->getRole();
$userId = $_SESSION['user_id'];
$viewUserId = isset($_GET['user_id']) ? (int)$_GET['user_id'] : $userId;
$isViewingOther = $viewUserId !== $userId;

// Only admins can view other profiles
if ($isViewingOther && $role !== 'admin') {
    header("Location: profile.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$isViewingOther) {
    $currentPassword = $_POST['current_password'] ?? '';
    $newUsername = trim($_POST['username'] ?? '');
    $newPassword = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Get current user data
    $stmt = $db->prepare("SELECT username, password, role FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        $error = "User tidak ditemukan.";
    } else {
        $storedPassword = $user['password'] ?? '';
        $currentPasswordValid = false;

        // Verifikasi current password
        if (password_verify($currentPassword, $storedPassword)) {
            $currentPasswordValid = true;
        } elseif ($storedPassword === $currentPassword) {
            $currentPasswordValid = true; // legacy plain text support
        } elseif (md5($currentPassword) === $storedPassword) {
            $currentPasswordValid = true; // legacy MD5 support
        }

        if (!$currentPasswordValid) {
            $error = "Password saat ini salah.";
        } elseif (!empty($newPassword) && $newPassword !== $confirmPassword) {
            $error = "Password baru dan konfirmasi tidak cocok.";
        } elseif (!empty($newUsername) && $newUsername !== $user['username']) {
            // Check if new username is unique
            $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->bind_param("si", $newUsername, $userId);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Username sudah digunakan.";
            }
        }

        if (empty($error)) {
            $updates = [];
            $types = '';
            $params = [];

            // Update username if changed
            if (!empty($newUsername) && $newUsername !== $user['username']) {
                $updates[] = "username = ?";
                $types .= 's';
                $params[] = $newUsername;
                $_SESSION['username'] = $newUsername;
            }

            // Update password if provided
            if (!empty($newPassword)) {
                $updates[] = "password = ?";
                $types .= 's';
                $params[] = password_hash($newPassword, PASSWORD_DEFAULT);
            }

            if (!empty($updates)) {
                $query = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
                $types .= 'i';
                $params[] = $userId;

                $stmt = $db->prepare($query);
                $stmt->bind_param($types, ...$params);
                if ($stmt->execute()) {
                    $message = "Profil berhasil diperbarui.";
                } else {
                    $error = "Gagal memperbarui profil.";
                }
            } else {
                $message = "Tidak ada perubahan yang dilakukan.";
            }
        }
    }
}

// Get current data
$stmt = $db->prepare("SELECT username, role FROM users WHERE id = ?");
$stmt->bind_param("i", $viewUserId);
$stmt->execute();
$currentUser = $stmt->get_result()->fetch_assoc();

if (!$currentUser) {
    $error = "User tidak ditemukan.";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php renderHead(($isViewingOther ? 'View Profile' : 'Edit Profile') . ' - POS Enterprise'); ?>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-slate-100">
    <div class="flex h-screen page-wrapper">
        <?php include 'sidebar.php'; ?>

        <div class="flex-1 p-8 main-content flex items-center justify-center">
            <div class="w-full max-w-lg">
                <h1 class="text-3xl font-bold mb-8 text-slate-800 text-center"><?php echo $isViewingOther ? 'View Profile' : 'Edit Profile'; ?></h1>

                <div class="bg-white p-8 rounded-lg shadow-md">
                    <?php if (!$isViewingOther): ?>
                    <form method="POST" id="profileForm">
                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">Current Username</label>
                            <p class="text-slate-600 bg-slate-50 p-3 rounded-md"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                        </div>

                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">Role</label>
                            <p class="text-slate-600 bg-slate-50 p-3 rounded-md"><?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?></p>
                        </div>

                        <?php if ($currentUser['role'] === 'admin'): ?>
                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">New Username</label>
                            <input type="text" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>" required class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>
                        <?php else: ?>
                        <input type="hidden" name="username" value="<?php echo htmlspecialchars($currentUser['username']); ?>">
                        <?php endif; ?>

                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">New Password <span class="text-sm text-slate-500">(leave blank to keep current)</span></label>
                            <input type="password" name="password" class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <div class="mb-6">
                            <label class="block text-slate-700 font-medium mb-2">Current Password <span class="text-red-500">*</span></label>
                            <input type="password" name="current_password" required class="w-full px-4 py-3 border border-slate-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                        </div>

                        <button type="submit" class="w-full bg-indigo-600 text-white py-3 rounded-md hover:bg-indigo-700 transition-colors font-medium">Update Profile</button>
                    </form>
                    <?php else: ?>
                    <div class="mb-6">
                        <label class="block text-slate-700 font-medium mb-2">Username</label>
                        <p class="text-slate-600 bg-slate-50 p-3 rounded-md"><?php echo htmlspecialchars($currentUser['username']); ?></p>
                    </div>

                    <div class="mb-6">
                        <label class="block text-slate-700 font-medium mb-2">Role</label>
                        <p class="text-slate-600 bg-slate-50 p-3 rounded-md"><?php echo ucfirst(htmlspecialchars($currentUser['role'])); ?></p>
                    </div>

                    <a href="users.php" class="w-full bg-gray-600 text-white py-3 rounded-md hover:bg-gray-700 transition-colors font-medium text-center block">Back to Users</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        <?php if ($message): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?php echo addslashes($message); ?>',
            confirmButtonColor: '#4F46E5'
        });
        <?php endif; ?>

        <?php if ($error): ?>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '<?php echo addslashes($error); ?>',
            confirmButtonColor: '#EF4444'
        });
        <?php endif; ?>
    </script>
</body>
</html>