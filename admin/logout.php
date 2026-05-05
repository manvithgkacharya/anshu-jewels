<?php
require_once '../config.php';

if (is_admin()) {
    log_activity($_SESSION['admin_id'], 'admin_logout', 'Admin logged out');
}

session_destroy();
redirect(SITE_URL . 'admin/login.php');
?>
