<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'anshu_jewels');

// Website Configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = strpos($domainName, 'localhost') !== false ? '/anshu-jewels' : '';

define('SITE_NAME', 'Anshu Jewels');
define('SITE_URL', $protocol . $domainName . $basePath . '/');
define('ADMIN_URL', SITE_URL . 'admin/');

// Payment Gateway Configuration
define('PAYMENT_GATEWAY', 'razorpay'); // razorpay, stripe, paypal
define('RAZORPAY_KEY', '');
define('RAZORPAY_SECRET', '');
define('STRIPE_KEY', '');
define('STRIPE_SECRET', '');
define('PAYPAL_CLIENT_ID', '');
define('PAYPAL_SECRET', '');

// Tax Configuration
define('GST_PERCENTAGE', 18);
define('VAT_PERCENTAGE', 0);
define('CURRENCY', 'INR');

// Session Configuration
ini_set('session.gc_maxlifetime', 86400);
session_start();

// Database Connection
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("Database error: " . $e->getMessage());
}

// Helper Functions
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function validate_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function is_admin() {
    return isset($_SESSION['admin_id']);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function json_response($status, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => $status,
        'message' => $message,
        'data' => $data
    ]);
    exit();
}

function get_user_id() {
    return $_SESSION['user_id'] ?? null;
}

function get_admin_id() {
    return $_SESSION['admin_id'] ?? null;
}

function log_activity($user_id, $action, $details = '') {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->bind_param("iss", $user_id, $action, $details);
    $stmt->execute();
    $stmt->close();
}
?>
