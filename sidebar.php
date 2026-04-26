<?php
$role = $_SESSION['role'] ?? 'user';
?>
<div class="w-64 bg-slate-800 text-white sidebar">
    <div class="p-6">
        <h2 class="text-xl font-bold">POS Enterprise</h2>
        <p class="text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($role); ?>)</p>
    </div>
    <nav class="mt-6">
        <a href="dashboard.php" class="block py-2 px-6 hover:bg-slate-700">Dashboard</a>
        <?php if ($role !== 'admin'): ?>
        <a href="pos.php" class="block py-2 px-6 hover:bg-slate-700">POS</a>
        <?php endif; ?>
        <a href="inventory.php" class="block py-2 px-6 hover:bg-slate-700">Inventory</a>
        <?php if ($role == 'admin'): ?>
        <a href="reports.php" class="block py-2 px-6 hover:bg-slate-700">Reports</a>
        <a href="users.php" class="block py-2 px-6 hover:bg-slate-700">Users</a>
        <a href="profile.php" class="block py-2 px-6 hover:bg-slate-700">Profile</a>
        <?php endif; ?>
        <a href="auth.php?logout=1" class="block py-2 px-6 hover:bg-slate-700">Logout</a>
    </nav>
</div>