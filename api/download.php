<?php
/**
 * Secure File Download System
 * Handles secure downloads with expiry and attempt limits
 */

require_once __DIR__ . '/../config/config.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect(SITE_URL . '/user/login.php');
}

$token = $_GET['token'] ?? '';

if (empty($token)) {
    die('Invalid download link');
}

try {
    // Verify download token
    $stmt = $db->prepare("SELECT d.*, p.title as product_title, oi.product_id, o.user_id
                          FROM downloads d
                          JOIN order_items oi ON d.order_item_id = oi.id
                          JOIN orders o ON oi.order_id = o.id
                          JOIN products p ON oi.product_id = p.id
                          WHERE d.download_token = ?");
    $stmt->execute([$token]);
    $download = $stmt->fetch();
    
    if (!$download) {
        die('Invalid download token');
    }
    
    // Check if user owns this download
    if ($download['user_id'] != $_SESSION['user_id']) {
        die('Unauthorized access');
    }
    
    // Check expiry
    if ($download['expires_at'] && strtotime($download['expires_at']) < time()) {
        die('Download link has expired. Please contact support.');
    }
    
    // Check download attempts
    if ($download['max_downloads'] && $download['download_count'] >= $download['max_downloads']) {
        die('Download limit reached. Please contact support for assistance.');
    }
    
    // Get file path (you would store this in database)
    $filePath = PRODUCT_IMAGE_PATH . $download['product_id'] . '.zip'; // Example
    
    if (!file_exists($filePath)) {
        die('File not found. Please contact support.');
    }
    
    // Increment download count
    $stmt = $db->prepare("UPDATE downloads SET download_count = download_count + 1, last_downloaded_at = NOW() WHERE id = ?");
    $stmt->execute([$download['id']]);
    
    // Log download
    $stmt = $db->prepare("INSERT INTO download_logs (download_id, user_id, ip_address, user_agent) VALUES (?, ?, ?, ?)");
    $stmt->execute([
        $download['id'],
        $_SESSION['user_id'],
        $_SERVER['REMOTE_ADDR'],
        $_SERVER['HTTP_USER_AGENT']
    ]);
    
    // Serve file
    header('Content-Type: application/octet-stream');
    header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Read file in chunks to handle large files
    $file = fopen($filePath, 'rb');
    while (!feof($file)) {
        echo fread($file, 8192);
        flush();
    }
    fclose($file);
    exit;
    
} catch (PDOException $e) {
    die('Database error. Please try again later.');
}
?>
