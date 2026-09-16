<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$playerId = isset($_POST['player_id']) ? trim($_POST['player_id']) : '';
$teamId = isset($_POST['team_id']) ? intval($_POST['team_id']) : 0;
$soldPrice = isset($_POST['sold_price']) ? floatval($_POST['sold_price']) : 0;

if (empty($playerId) || $teamId <= 0 || $soldPrice <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Player ID, Team, and valid Sold Price are required.']);
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
        throw new Exception("Only unsold players can be directly assigned.");
    }

    // Get team info
    $teamStmt = $pdo->prepare("SELECT id, total_purse, max_strength FROM team_master WHERE id = ?");
    $teamStmt->execute([$teamId]);
    $team = $teamStmt->fetch();

    if (!$team) {
        throw new Exception("Team not found.");
    }

    // Check spent amount
    $spentStmt = $pdo->prepare("SELECT COALESCE(SUM(bid_points), 0) as total_spent FROM registrations WHERE team_id = ? AND booking_status = 1");
    $spentStmt->execute([$teamId]);
    $spent = $spentStmt->fetch()['total_spent'];

    $remainingPurse = $team['total_purse'] - $spent;
    if ($soldPrice > $remainingPurse) {
        throw new Exception("Team does not have enough remaining purse for this assignment.");
    }

    // Check strength
    $playersStmt = $pdo->prepare("SELECT COUNT(id) as total_players FROM registrations WHERE team_id = ? AND booking_status = 1");
    $playersStmt->execute([$teamId]);
    $totalPlayers = $playersStmt->fetch()['total_players'];

    if ($totalPlayers >= $team['max_strength']) {
        throw new Exception("Team has already reached its maximum strength limit.");
    }

    // Update player status
    $updateStmt = $pdo->prepare("UPDATE registrations SET booking_status = 1, team_id = ?, bid_points = ? WHERE player_id = ?");
    $updateStmt->execute([$teamId, $soldPrice, $playerId]);

    // Record in history
    $histStmt = $pdo->prepare("INSERT INTO auction_history (player_id, action_type, team_id, bid_points) VALUES (?, 'DIRECT_ASSIGN', ?, ?)");
    $histStmt->execute([$playerId, $teamId, $soldPrice]);

    $pdo->commit();

    echo json_encode(['status' => 'success', 'message' => 'Player successfully assigned to team.']);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
