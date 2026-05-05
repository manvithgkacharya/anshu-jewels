<?php
require_once __DIR__ . '/../config/config.php';

// Mock search term
$searchTerm = isset($_GET['q']) ? $_GET['q'] : 'all';

echo "Version 3<br>";
echo "Searching for: " . htmlspecialchars($searchTerm) . "<br>";

// Force Insert dummy order (ignore if exists)
try {
    $db->prepare("INSERT IGNORE INTO users (name, email, password, phone) VALUES ('Test User', 'test@example.com', 'pass', '9876543210')")->execute();
    $userId = $db->lastInsertId();
    if ($userId == 0) { // If ignored, find existing
        $stmt = $db->prepare("SELECT id FROM users WHERE email='test@example.com'");
        $stmt->execute();
        $userId = $stmt->fetchColumn();
    }
    
    $db->prepare("INSERT IGNORE INTO orders (user_id, order_number, total_amount, final_amount, shipping_name, shipping_phone) VALUES (?, 'ORD-TEST-001', 1000, 1000, 'John Doe', '5551234567')")->execute([$userId]);
    echo "Ensured Order ORD-TEST-001 matches User ID $userId<br>";
} catch (Exception $e) {
    echo "Insert Error: " . $e->getMessage() . "<br>";
}

try {
    $query = "SELECT o.id, o.order_number, u.name, u.phone, o.shipping_name, o.shipping_phone
              FROM orders o
              LEFT JOIN users u ON o.user_id = u.id
              LEFT JOIN order_items oi ON o.id = oi.order_id
              WHERE 1=1";
    
    $params = [];
    
    if ($searchTerm !== 'all') {
        $query .= " AND (o.order_number LIKE ? OR u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ? OR o.shipping_name LIKE ? OR o.shipping_phone LIKE ?)";
        $searchParam = "%$searchTerm%";
        $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
    }
    
    $query .= " GROUP BY o.id ORDER BY o.created_at DESC";
    
    // echo "Query: <pre>" . $query . "</pre>";
    // echo "Params: <pre>" . print_r($params, true) . "</pre>";
    
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($orders) . " orders:<br>";
    echo "<table border='1'><tr><th>Order #</th><th>User Name</th><th>User Phone</th><th>Ship Name</th><th>Ship Phone</th></tr>";
    foreach ($orders as $o) {
        echo "<tr>";
        echo "<td>" . $o['order_number'] . "</td>";
        echo "<td>" . $o['name'] . "</td>";
        echo "<td>" . $o['phone'] . "</td>";
        echo "<td>" . $o['shipping_name'] . "</td>";
        echo "<td>" . $o['shipping_phone'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
