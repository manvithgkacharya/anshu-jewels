<?php
require_once '../config.php';

header('Content-Type: application/json');

try {
    $stmt = $conn->prepare("SELECT id, name, slug, description FROM categories WHERE is_active = 1 ORDER BY name");
    $stmt->execute();
    $result = $stmt->get_result();
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'status' => 'success',
        'categories' => $categories
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
?>
