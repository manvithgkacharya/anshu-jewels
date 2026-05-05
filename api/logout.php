<?php
require_once __DIR__ . '/../config/config.php';

// Check if admin logout
$isAdmin = isset($_GET['admin']) && $_GET['admin'] == '1';

if ($isAdmin) {
    // Clear admin session
    unset($_SESSION['admin_id']);
    unset($_SESSION['admin_username']);
    unset($_SESSION['admin_role']);
    
    header('Location: ' . SITE_URL . '/admin/login.php');
    exit();
}

// User logout
if (isset($_SESSION['user_id'])) {
    session_unset();
    session_destroy();
    header('Location: ' . SITE_URL . '/user/index.php');
    exit();
}

// Default redirect if no session or already logged out
header('Location: ' . SITE_URL . '/user/index.php');
exit();
?>
