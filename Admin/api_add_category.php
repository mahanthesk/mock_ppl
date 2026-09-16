<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$categoryName = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';

if (empty($categoryName)) {
    echo json_encode(['status' => 'error', 'message' => 'Category name is required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("INSERT INTO categories (category_name) VALUES (?)");
    $stmt->execute([$categoryName]);
    echo json_encode(['status' => 'success', 'message' => 'Category added successfully.']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) { // Integrity constraint violation (unique)
        echo json_encode(['status' => 'error', 'message' => 'Category name already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
