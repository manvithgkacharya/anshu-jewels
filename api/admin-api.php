<?php
/**
 * Admin API Endpoints
 * RESTful API for admin operations
 */

require_once __DIR__ . '/../config/config.php';

header('Content-Type: application/json');

// Check admin authentication
if (!isAdminLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$endpoint = $_GET['endpoint'] ?? '';

try {
    switch ($endpoint) {
        case 'stats':
            handleStats();
            break;
        
        case 'products':
            handleProducts($method);
            break;
        
        case 'orders':
            handleOrders($method);
            break;
        
        case 'users':
            handleUsers($method);
            break;
        
        case 'coupons':
            handleCoupons($method);
            break;
        
        default:
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Endpoint not found']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error']);
}

/**
 * Get dashboard statistics
 */
function handleStats() {
    global $db;
    
    $stats = [];
    
    // Total revenue
    $stmt = $db->query("SELECT COALESCE(SUM(final_amount), 0) as total FROM orders WHERE payment_status = 'completed'");
    $stats['total_revenue'] = $stmt->fetch()['total'];
    
    // Total orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders");
    $stats['total_orders'] = $stmt->fetch()['count'];
    
    // Total users
    $stmt = $db->query("SELECT COUNT(*) as count FROM users");
    $stats['total_users'] = $stmt->fetch()['count'];
    
    // Total products
    $stmt = $db->query("SELECT COUNT(*) as count FROM products WHERE is_active = 1");
    $stats['total_products'] = $stmt->fetch()['count'];
    
    // Recent orders (last 7 days)
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
    $stats['recent_orders'] = $stmt->fetch()['count'];
    
    // Pending orders
    $stmt = $db->query("SELECT COUNT(*) as count FROM orders WHERE order_status = 'pending'");
    $stats['pending_orders'] = $stmt->fetch()['count'];
    
    echo json_encode(['success' => true, 'data' => $stats]);
}

/**
 * Handle product operations
 */
function handleProducts($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $stmt = $db->prepare("SELECT p.*, c.name as category_name,
                                  (SELECT image_url FROM product_images WHERE product_id = p.id AND is_primary = 1 LIMIT 1) as image_url
                                  FROM products p
                                  LEFT JOIN categories c ON p.category_id = c.id
                                  ORDER BY p.created_at DESC
                                  LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            $products = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $products]);
            break;
        
        case 'POST':
            // Create product logic here
            echo json_encode(['success' => true, 'message' => 'Product created']);
            break;
        
        case 'PUT':
            // Update product logic here
            echo json_encode(['success' => true, 'message' => 'Product updated']);
            break;
        
        case 'DELETE':
            $id = (int)($_GET['id'] ?? 0);
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Product deleted']);
            break;
    }
}

/**
 * Handle order operations
 */
function handleOrders($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $status = $_GET['status'] ?? 'all';
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $query = "SELECT o.*, u.name as customer_name, u.email as customer_email
                      FROM orders o
                      LEFT JOIN users u ON o.user_id = u.id";
            
            if ($status !== 'all') {
                $query .= " WHERE o.payment_status = ?";
                $params = [$status, $limit, $offset];
            } else {
                $params = [$limit, $offset];
            }
            
            $query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
            
            $stmt = $db->prepare($query);
            $stmt->execute($params);
            $orders = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $orders]);
            break;
        
        case 'PUT':
            // Update order status
            $id = (int)($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE orders SET order_status = ?, payment_status = ? WHERE id = ?");
            $stmt->execute([$data['order_status'], $data['payment_status'], $id]);
            
            require_once __DIR__ . '/../includes/email-notification.php';
            $emailService = new EmailNotification($db);
            $emailService->sendOrderStatusUpdate($id, $data['order_status']);
            
            echo json_encode(['success' => true, 'message' => 'Order updated']);
            break;
    }
}

/**
 * Handle user operations
 */
function handleUsers($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            
            $stmt = $db->prepare("SELECT u.*,
                                  COUNT(DISTINCT o.id) as order_count,
                                  COALESCE(SUM(o.final_amount), 0) as total_spent
                                  FROM users u
                                  LEFT JOIN orders o ON u.id = o.user_id
                                  GROUP BY u.id
                                  ORDER BY u.created_at DESC
                                  LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
            $users = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $users]);
            break;
        
        case 'PUT':
            // Block/unblock user
            $id = (int)($_GET['id'] ?? 0);
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $db->prepare("UPDATE users SET is_active = ? WHERE id = ?");
            $stmt->execute([$data['is_active'], $id]);
            
            echo json_encode(['success' => true, 'message' => 'User updated']);
            break;
    }
}

/**
 * Handle coupon operations
 */
function handleCoupons($method) {
    global $db;
    
    switch ($method) {
        case 'GET':
            $stmt = $db->query("SELECT c.*, COUNT(o.id) as usage_count
                               FROM coupons c
                               LEFT JOIN orders o ON c.code = o.coupon_code
                               GROUP BY c.id
                               ORDER BY c.created_at DESC");
            $coupons = $stmt->fetchAll();
            
            echo json_encode(['success' => true, 'data' => $coupons]);
            break;
        
        case 'POST':
            // Validate coupon
            $code = strtoupper($_POST['code'] ?? '');
            
            $stmt = $db->prepare("SELECT * FROM coupons WHERE code = ? AND is_active = 1");
            $stmt->execute([$code]);
            $coupon = $stmt->fetch();
            
            if (!$coupon) {
                echo json_encode(['success' => false, 'message' => 'Invalid coupon code']);
                return;
            }
            
            // Check expiry
            if ($coupon['valid_until'] && strtotime($coupon['valid_until']) < time()) {
                echo json_encode(['success' => false, 'message' => 'Coupon has expired']);
                return;
            }
            
            // Check usage limit
            if ($coupon['usage_limit']) {
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM orders WHERE coupon_code = ?");
                $stmt->execute([$code]);
                $usageCount = $stmt->fetch()['count'];
                
                if ($usageCount >= $coupon['usage_limit']) {
                    echo json_encode(['success' => false, 'message' => 'Coupon usage limit reached']);
                    return;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'code' => $coupon['code'],
                    'discount_type' => $coupon['discount_type'],
                    'discount_value' => $coupon['discount_value'],
                    'min_purchase_amount' => $coupon['min_purchase_amount']
                ]
            ]);
            break;
    }
}
?>
