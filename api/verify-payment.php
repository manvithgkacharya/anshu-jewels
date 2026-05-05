<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/email-notification.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'not_logged_in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: $_POST;
    $orderId = $data['order_id'] ?? 0;
    
    try {
        $db->beginTransaction();
        
        $stmt = $db->prepare("SELECT payment_status FROM orders WHERE id = ? AND user_id = ? FOR UPDATE");
        $stmt->execute([$orderId, $_SESSION['user_id']]);
        $order = $stmt->fetch();
        
        if ($order && $order['payment_status'] === 'pending') {
            $updateStmt = $db->prepare("UPDATE orders SET payment_status = 'completed', order_status = 'processing' WHERE id = ?");
            $updateStmt->execute([$orderId]);
            
            $db->commit();
            
            $emailService = new EmailNotification($db);
            $emailService->sendOrderConfirmation($orderId);
            
            echo json_encode(['success' => true, 'order_id' => $orderId]);
        } else {
            $db->rollBack();
            echo json_encode(['success' => false, 'error' => 'invalid_order']);
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'error' => 'system_error']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'invalid_method']);
}
