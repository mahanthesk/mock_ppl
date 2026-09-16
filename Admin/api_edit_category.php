<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$categoryName = isset($_POST['category_name']) ? trim($_POST['category_name']) : '';

if ($id <= 0 || empty($categoryName)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid ID and Category name are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare("UPDATE categories SET category_name = ? WHERE id = ?");
    $stmt->execute([$categoryName, $id]);
    echo json_encode(['status' => 'success', 'message' => 'Category updated successfully.']);
} catch (PDOException $e) {
    if ($e->getCode() == 23000) {
        echo json_encode(['status' => 'error', 'message' => 'Category name already exists.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
