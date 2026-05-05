<?php
require_once '../config.php';

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$code = sanitize($data['code'] ?? '');

if (empty($code)) {
    json_response('error', 'Coupon code is required');
}

$stmt = $conn->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
$stmt->bind_param("s", $code);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$coupon) {
    json_response('error', 'Invalid coupon code');
}

if ($coupon['expires_at'] && strtotime($coupon['expires_at']) < time()) {
    json_response('error', 'Coupon has expired');
}

if ($coupon['max_uses'] != -1 && $coupon['current_uses'] >= $coupon['max_uses']) {
    json_response('error', 'Coupon usage limit reached');
}

json_response('success', 'Coupon is valid', [
    'code' => $coupon['code'],
    'discount_type' => $coupon['discount_type'],
    'discount_value' => $coupon['discount_value'],
    'min_purchase_amount' => $coupon['min_purchase_amount']
]);
?>
