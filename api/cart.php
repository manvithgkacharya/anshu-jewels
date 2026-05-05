<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    json_response('error', 'Please login first');
}

$user_id = get_user_id();
$action = sanitize($_GET['action'] ?? $_POST['action'] ?? '');

if ($action === 'count') {
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'count' => $result['count']]);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);

if ($action === 'add') {
    $product_id = $data['product_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;

    if ($product_id === 0) {
        json_response('error', 'Invalid product');
    }

    $stmt = $conn->prepare("SELECT id FROM products WHERE id = ? AND is_active = 1");
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        $stmt->close();
        json_response('error', 'Product not found');
    }
    $stmt->close();

    $stmt = $conn->prepare("SELECT id, quantity FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        $new_quantity = $existing['quantity'] + $quantity;
        $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $existing['id']);
        $stmt->execute();
        $stmt->close();
    } else {
        $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
        $stmt->bind_param("iii", $user_id, $product_id, $quantity);
        $stmt->execute();
        $stmt->close();
    }

    json_response('success', 'Product added to cart');

} elseif ($action === 'remove') {
    $cart_id = $data['cart_id'] ?? 0;

    if ($cart_id === 0) {
        json_response('error', 'Invalid cart item');
    }

    $stmt = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();

    json_response('success', 'Item removed from cart');

} elseif ($action === 'update') {
    $cart_id = $data['cart_id'] ?? 0;
    $quantity = $data['quantity'] ?? 1;

    if ($cart_id === 0 || $quantity < 1) {
        json_response('error', 'Invalid request');
    }

    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("iii", $quantity, $cart_id, $user_id);
    $stmt->execute();
    $stmt->close();

    json_response('success', 'Cart updated');

} else {
    json_response('error', 'Invalid action');
}
?>
