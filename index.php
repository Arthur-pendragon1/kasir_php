<?php
session_start();
require_once 'db_connect.php';
require_once 'auth.php';

$database = new Database();
$db = $database->getConnection();
$auth = new Auth($db);

if ($auth->isLoggedIn()) {
    header("Location: dashboard.php");
    exit();
} else {
    header("Location: auth.php");
    exit();
}
?>