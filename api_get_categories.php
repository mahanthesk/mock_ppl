<?php
require_once 'db_connection.php'; // This is root db_connection.php

header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC");
    $categories = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode(['status' => 'success', 'data' => $categories]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
