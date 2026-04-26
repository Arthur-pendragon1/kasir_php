<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Tampilkan error kecuali Notice agar tampilan tidak pecah saat development.
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '1');

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: auth.php');
        exit();
    }
}

function redirectIfAuthenticated() {
    if (isLoggedIn()) {
        header('Location: dashboard.php');
        exit();
    }
}
?>