<?php
/**
 * Payment Gateway Integration API
 * Handles Razorpay, Stripe, and PayPal payments
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Get payment method and order details
$method = $_POST['method'] ?? '';
$orderId = $_POST['order_id'] ?? 0;
$amount = (float)($_POST['amount'] ?? 0);

// Fetch order details
try {
    $stmt = $db->prepare("SELECT * FROM orders WHERE id = ?");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();
    
    if (!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit;
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
    exit;
}

// Fetch payment gateway settings
try {
    $stmt = $db->query("SELECT setting_key, setting_value FROM site_settings WHERE setting_key LIKE '%_key%' OR setting_key LIKE '%_secret%' OR setting_key LIKE '%_enabled'");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    $settings = [];
}

switch ($method) {
    case 'razorpay':
        handleRazorpay($order, $settings);
        break;
    
    case 'stripe':
        handleStripe($order, $settings);
        break;
    
    case 'paypal':
        handlePayPal($order, $settings);
        break;
    
    case 'cod':
        handleCOD($order);
        break;
    
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid payment method']);
        break;
}

/**
 * Razorpay Payment Integration
 */
function handleRazorpay($order, $settings) {
    if (!isset($settings['razorpay_enabled']) || !$settings['razorpay_enabled']) {
        echo json_encode(['success' => false, 'message' => 'Razorpay is not enabled']);
        return;
    }
    
    $keyId = $settings['razorpay_key_id'] ?? '';
    $keySecret = $settings['razorpay_key_secret'] ?? '';
    
    if (empty($keyId) || empty($keySecret)) {
        echo json_encode(['success' => false, 'message' => 'Razorpay credentials not configured']);
        return;
    }
    
    // Razorpay integration
    // You would use Razorpay PHP SDK here
    // Example: https://razorpay.com/docs/payments/server-integration/php/
    
    echo json_encode([
        'success' => true,
        'method' => 'razorpay',
        'key_id' => $keyId,
        'order_id' => $order['order_number'],
        'amount' => $order['final_amount'] * 100, // Razorpay uses paise
        'currency' => 'INR',
        'name' => 'Anshu Jewels',
        'description' => 'Order #' . $order['order_number'],
        'prefill' => [
            'name' => $order['shipping_name'],
            'email' => $order['user_id'], // You'd fetch user email here
        ]
    ]);
}

/**
 * Stripe Payment Integration
 */
function handleStripe($order, $settings) {
    if (!isset($settings['stripe_enabled']) || !$settings['stripe_enabled']) {
        echo json_encode(['success' => false, 'message' => 'Stripe is not enabled']);
        return;
    }
    
    $publicKey = $settings['stripe_public_key'] ?? '';
    $secretKey = $settings['stripe_secret_key'] ?? '';
    
    if (empty($publicKey) || empty($secretKey)) {
        echo json_encode(['success' => false, 'message' => 'Stripe credentials not configured']);
        return;
    }
    
    // Stripe integration
    // You would use Stripe PHP SDK here
    // Example: https://stripe.com/docs/payments/accept-a-payment
    
    echo json_encode([
        'success' => true,
        'method' => 'stripe',
        'public_key' => $publicKey,
        'client_secret' => 'pi_xxx_secret_xxx', // Create payment intent
        'amount' => $order['final_amount'] * 100, // Stripe uses cents
        'currency' => 'inr'
    ]);
}

/**
 * PayPal Payment Integration
 */
function handlePayPal($order, $settings) {
    if (!isset($settings['paypal_enabled']) || !$settings['paypal_enabled']) {
        echo json_encode(['success' => false, 'message' => 'PayPal is not enabled']);
        return;
    }
    
    $clientId = $settings['paypal_client_id'] ?? '';
    $secret = $settings['paypal_secret'] ?? '';
    
    if (empty($clientId) || empty($secret)) {
        echo json_encode(['success' => false, 'message' => 'PayPal credentials not configured']);
        return;
    }
    
    // PayPal integration
    // You would use PayPal SDK here
    // Example: https://developer.paypal.com/docs/checkout/
    
    echo json_encode([
        'success' => true,
        'method' => 'paypal',
        'client_id' => $clientId,
        'order_id' => $order['order_number'],
        'amount' => $order['final_amount'],
        'currency' => 'INR'
    ]);
}

/**
 * Cash on Delivery
 */
function handleCOD($order) {
    global $db;
    
    try {
        $stmt = $db->prepare("UPDATE orders SET payment_method = 'cod', payment_status = 'pending', order_status = 'processing' WHERE id = ?");
        $stmt->execute([$order['id']]);
        
        echo json_encode([
            'success' => true,
            'method' => 'cod',
            'message' => 'Order placed successfully! Pay on delivery.',
            'redirect' => '/user/orders.php?success=1'
        ]);
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to process order']);
    }
}
?>
