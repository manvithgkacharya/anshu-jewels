<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit();
}

$orderId = $data['order_id'] ?? 0;

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Order ID required']);
    exit();
}

try {
    // Verify order exists and belongs to user
    $stmt = $db->prepare("SELECT id, order_status FROM orders WHERE id = ? AND user_id = ?");
    $stmt->execute([$orderId, $_SESSION['user_id']]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$order) {
        echo json_encode(['success' => false, 'error' => 'Order not found or access denied']);
        exit();
    }

    if (!in_array($order['order_status'], ['pending', 'processing'])) {
        echo json_encode(['success' => false, 'error' => 'Only pending or processing orders can be cancelled. Current status: ' . $order['order_status']]);
        exit();
    }

    // Cancel order
    $stmt = $db->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?");
    $result = $stmt->execute([$orderId]);

    if ($result) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Update failed']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    error_log("Cancel Order Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
