<?php
require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'message' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$code = $input['code'] ?? '';
$cartTotal = (float)($input['total'] ?? 0);

if (empty($code)) {
    echo json_encode(['valid' => false, 'message' => 'Coupon code is required']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
    $stmt->execute([$code]);
    $coupon = $stmt->fetch();

    if (!$coupon) {
        echo json_encode(['valid' => false, 'message' => 'Invalid coupon code']);
        exit;
    }

    // Check expiry
    $now = new DateTime();
    if ($coupon['valid_from'] && new DateTime($coupon['valid_from']) > $now) {
        echo json_encode(['valid' => false, 'message' => 'Coupon is not yet valid']);
        exit;
    }
    if ($coupon['valid_until'] && new DateTime($coupon['valid_until']) < $now) {
        echo json_encode(['valid' => false, 'message' => 'Coupon has expired']);
        exit;
    }

    // Check usage limit
    if ($coupon['usage_limit'] > 0 && $coupon['used_count'] >= $coupon['usage_limit']) {
        echo json_encode(['valid' => false, 'message' => 'Coupon usage limit reached']);
        exit;
    }

    // Check minimum purchase
    if ($coupon['min_purchase_amount'] > 0 && $cartTotal < $coupon['min_purchase_amount']) {
        echo json_encode([
            'valid' => false, 
            'message' => 'Minimum purchase of ' . number_format($coupon['min_purchase_amount'], 2) . ' required'
        ]);
        exit;
    }

    // Calculate discount
    $discount = 0;
    if ($coupon['discount_type'] === 'percentage') {
        $discount = ($cartTotal * $coupon['discount_value']) / 100;
        if ($coupon['max_discount'] > 0 && $discount > $coupon['max_discount']) {
            $discount = $coupon['max_discount'];
        }
    } else {
        $discount = $coupon['discount_value'];
    }

    // Ensure discount doesn't exceed total
    if ($discount > $cartTotal) {
        $discount = $cartTotal;
    }

    echo json_encode([
        'valid' => true,
        'coupon_id' => $coupon['id'],
        'code' => $coupon['code'],
        'discount_type' => $coupon['discount_type'],
        'discount_value' => $coupon['discount_value'],
        'calculated_discount' => $discount,
        'message' => 'Coupon applied successfully!'
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['valid' => false, 'message' => 'Server error']);
}
?>
