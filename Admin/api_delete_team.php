<?php
require_once '../auth.php';
check_access('auction_admin');

require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$team_id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($team_id <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid Team ID.']);
    exit;
}

try {
    // Check if team has registered players
    $checkStmt = $pdo->prepare("SELECT COUNT(id) as total FROM registrations WHERE team_id = ? AND booking_status = 1");
    $checkStmt->execute([$team_id]);
    $result = $checkStmt->fetch();
    
    if ($result && $result['total'] > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete this team because it has ' . $result['total'] . ' player(s) assigned to it.']);
        exit;
    }

    // Delete Team
    $stmt = $pdo->prepare("DELETE FROM team_master WHERE id = ?");
    $stmt->execute([$team_id]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Team deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Team not found or already deleted.']);
    }
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
