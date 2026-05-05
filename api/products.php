<?php
require_once '../config.php';

header('Content-Type: application/json');

$action = sanitize($_GET['action'] ?? '');

if ($action === 'featured') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.is_active = 1 
                            ORDER BY p.downloads DESC, p.created_at DESC 
                            LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'products' => $products]);
    exit;
}

if ($action === 'new') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.is_active = 1 
                            ORDER BY p.created_at DESC 
                            LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'products' => $products]);
    exit;
}

if ($action === 'bestsellers') {
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 6;
    
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.is_active = 1 
                            ORDER BY p.downloads DESC 
                            LIMIT ?");
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'products' => $products]);
    exit;
}

if ($action === 'search') {
    $search = sanitize($_GET['q'] ?? '');
    
    if (strlen($search) < 2) {
        echo json_encode(['status' => 'error', 'message' => 'Search term too short']);
        exit;
    }
    
    $search_term = "%$search%";
    $stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p 
                            LEFT JOIN categories c ON p.category_id = c.id 
                            WHERE p.is_active = 1 AND (p.name LIKE ? OR p.description LIKE ?)
                            LIMIT 10");
    $stmt->bind_param("ss", $search_term, $search_term);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo json_encode(['status' => 'success', 'products' => $products]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
?>
