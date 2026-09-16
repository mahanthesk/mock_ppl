<?php
require_once '../auth.php';
check_access('auction_admin');

// mark_unsold.php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (isset($_POST['player_id'])) {
    $playerId = $_POST['player_id'];

    try {
        // Fetch current player state
        $pStmt = $pdo->prepare("SELECT team_id, bid_points, booking_status FROM registrations WHERE id = ? OR player_id = ?");
        $pStmt->execute([$playerId, $playerId]);
        $player = $pStmt->fetch();

        if ($player && $player['booking_status'] == 1 && !empty($player['team_id']) && floatval($player['bid_points']) > 0) {
            $prevTeamId = $player['team_id'];
            $prevBidPoints = floatval($player['bid_points']);
            $remStmt = $pdo->prepare("UPDATE team_master SET remaining_purse = remaining_purse + ? WHERE id = ? AND remaining_purse IS NOT NULL");
            $remStmt->execute([$prevBidPoints, $prevTeamId]);
        }

        $stmt = $pdo->prepare("
            UPDATE registrations
            SET booking_status = 2, team_id = NULL, bid_points = NULL
            WHERE id = ? OR player_id = ?
        ");

        if ($stmt->execute([$playerId, $playerId])) {
            echo json_encode([
                'success' => true,
                'message' => 'Player marked as unsold.'
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update status.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No player ID provided.']);
}
?>
