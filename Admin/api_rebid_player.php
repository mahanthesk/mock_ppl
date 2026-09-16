<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$playerId = isset($_POST['player_id']) ? trim($_POST['player_id']) : '';

if (empty($playerId)) {
    echo json_encode(['status' => 'error', 'message' => 'Player ID is required.']);
    exit;
}

try {
    $pdo->beginTransaction();

    // Verify player is unsold
    $stmt = $pdo->prepare("SELECT id, booking_status FROM registrations WHERE player_id = ? AND deleted_at IS NULL");
    $stmt->execute([$playerId]);
    $player = $stmt->fetch();

    if (!$player) {
        throw new Exception("Player not found.");
    }

    if ($player['booking_status'] != 2) {
        throw new Exception("Only unsold players can be added back to the auction.");
    }

    // Update player status
    $updateStmt = $pdo->prepare("UPDATE registrations SET booking_status = 0, team_id = NULL, bid_points = 0 WHERE player_id = ?");
    $updateStmt->execute([$playerId]);

    // Record in history
    $histStmt = $pdo->prepare("INSERT INTO auction_history (player_id, action_type) VALUES (?, 'REBID')");
    $histStmt->execute([$playerId]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Player successfully added back to auction.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
