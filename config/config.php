<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Site Configuration
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$domainName = $_SERVER['HTTP_HOST'] ?? 'localhost';
$basePath = strpos($domainName, 'localhost') !== false ? '/anshu-jewels' : '';

define('SITE_NAME', 'Anshu Jewels');
define('SITE_URL', $protocol . $domainName . $basePath);
define('ADMIN_URL', SITE_URL . '/admin');
define('ADMIN_EMAIL', 'admin@anshujewels.com');

// Directory paths
define('ROOT_PATH', dirname(__DIR__));
define('UPLOAD_PATH', ROOT_PATH . '/assets/uploads/');
define('PRODUCT_IMAGE_PATH', UPLOAD_PATH . 'products/');
define('SLIDER_IMAGE_PATH', UPLOAD_PATH . 'sliders/');

// URL paths
define('ASSETS_URL', SITE_URL . '/assets/');
define('CSS_URL', ASSETS_URL . 'css/');
define('JS_URL', ASSETS_URL . 'js/');
define('IMG_URL', ASSETS_URL . 'images/');
define('UPLOAD_URL', ASSETS_URL . 'uploads/');
define('SLIDER_IMAGE_URL', UPLOAD_URL . 'sliders/');

// Create upload directories if they don't exist
if (!file_exists(UPLOAD_PATH)) {
    mkdir(UPLOAD_PATH, 0755, true);
}
if (!file_exists(PRODUCT_IMAGE_PATH)) {
    mkdir(PRODUCT_IMAGE_PATH, 0755, true);
}
if (!file_exists(SLIDER_IMAGE_PATH)) {
    mkdir(SLIDER_IMAGE_PATH, 0755, true);
}

// Timezone
date_default_timezone_set('Asia/Kolkata');

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once ROOT_PATH . '/config/database.php';

// Helper function to get site settings from database
function getSiteSetting($key, $default = '') {
    global $db;
    try {
        $stmt = $db->prepare("SELECT setting_value FROM site_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        return $default;
    }
}

// Helper function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

// Helper function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Helper function to check if admin is logged in
function isAdminLoggedIn() {
    return isset($_SESSION['admin_id']);
}

// Helper function to redirect
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Helper function to generate CSRF token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Helper function to verify CSRF token
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Helper function to format currency
function formatCurrency($amount) {
    $symbol = getSiteSetting('currency_symbol', '₹');
    return $symbol . number_format($amount, 2);
}

// Helper function to generate order number
function generateOrderNumber() {
    return 'AJ' . date('Ymd') . rand(1000, 9999);
}

// Helper function to send email (basic implementation)
function sendEmail($to, $subject, $message) {
    $headers = "From: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Reply-To: " . ADMIN_EMAIL . "\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

// Global Security Script Injection
function injectSecurityScript($buffer) {
    // Only inject if it's an HTML page and has a </head> tag
    if (stripos($buffer, '</head>') !== false) {
        $script = <<<EOT
    <!-- Security Script -->
    <script>
    (function() {
      function handleSecurityEvent(e) {
        e.preventDefault();
        showSecurityWarning();
      }

      function showSecurityWarning() {
        if (document.getElementById('security-warning-popup')) return;

        const warning = document.createElement('div');
        warning.id = 'security-warning-popup';
        warning.innerHTML = `
          <div style="
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            padding: 2rem;
            border-radius: 8px;
            z-index: 2147483647;
            text-align: center;
            font-family: inherit;
            font-weight: bold;
            font-size: 1.25rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            animation: flashWarning 0.3s infinite;
            border: 4px solid red;
          ">
            <div style="font-size: 3rem; margin-bottom: 1rem;">⚠️</div>
            This feature is disabled for security purpose!
          </div>
        `;

        const style = document.createElement('style');
        style.id = 'security-warning-style';
        style.textContent = `
          @keyframes flashWarning {
            0% { background-color: #220000; color: #ff0000; border-color: red;}
            50% { background-color: #ff0000; color: #ffffff; border-color: white;}
            100% { background-color: #220000; color: #ff0000; border-color: red;}
          }
        `;

        document.head.appendChild(style);
        document.body.appendChild(warning);

        setTimeout(() => {
          if (warning.parentNode) warning.parentNode.removeChild(warning);
          if (style.parentNode) style.parentNode.removeChild(style);
        }, 3000);
      }

      document.addEventListener('contextmenu', handleSecurityEvent);
      document.addEventListener('keydown', function(e) {
        if (
          e.key === 'F12' ||
          (e.ctrlKey && e.shiftKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c')) ||
          (e.ctrlKey && (e.key === 'U' || e.key === 'u')) ||
          (e.metaKey && e.altKey && (e.key === 'I' || e.key === 'i' || e.key === 'J' || e.key === 'j' || e.key === 'C' || e.key === 'c'))
        ) {
          handleSecurityEvent(e);
        }
      });
    })();
    </script>
EOT;
        // Inject right before </head>
        return str_ireplace('</head>', $script . "\n</head>", $buffer);
    }
    return $buffer;
}

// Start output buffering to inject the script globally
ob_start('injectSecurityScript');
?>
