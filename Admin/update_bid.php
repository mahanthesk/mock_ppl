<?php
require_once '../auth.php';
check_access('auction_admin');

// update_bid.php
require_once 'db_connection.php';

header('Content-Type: application/json');

$totalPoints = 10000000;
$minBid = 100000;

$playerId = isset($_POST['player_id']) ? $_POST['player_id'] : null;
$teamId = isset($_POST['team_id']) ? intval($_POST['team_id']) : null;
$bidPoints = isset($_POST['bid_points']) ? floatval($_POST['bid_points']) : null;

// If neither teamId nor playerId provided, return error
if (!$teamId && !$playerId) {
    echo json_encode(['success' => false, 'message' => 'Missing team_id or player_id.']);
    exit;
}

try {
    // If teamId is provided, compute totals
    if ($teamId) {
        // 1. Sum registration points
        $stmt_reg = $pdo->prepare("
            SELECT COALESCE(SUM(bid_points),0) AS total_bid_points,
                   COUNT(id) AS total_players
            FROM registrations
            WHERE team_id = ? AND booking_status = 1
        ");
        $stmt_reg->execute([$teamId]);
        $res_reg = $stmt_reg->fetch();

        // 2. Sum icon points
        $stmt_icon = $pdo->prepare("
            SELECT COALESCE(SUM(bid_points), 0) AS total_icon_points
            FROM icon_players
            WHERE team_id = ? AND booking_status = 1
        ");
        $stmt_icon->execute([$teamId]);
        $res_icon = $stmt_icon->fetch();

        $totalBidPoints = floatval($res_reg['total_bid_points']) + floatval($res_icon['total_icon_points']);
        $totalPlayers = intval($res_reg['total_players']);

        // 3. Get team's total_purse, remaining_purse, and max_strength
        $stmt_team = $pdo->prepare("SELECT total_purse, remaining_purse, max_strength FROM team_master WHERE id = ?");
        $stmt_team->execute([$teamId]);
        $res_team = $stmt_team->fetch();
        $totalPoints = ($res_team && floatval($res_team['total_purse']) > 0) ? floatval($res_team['total_purse']) : 10000000;
        $maxPlayersPerTeam = $res_team ? intval($res_team['max_strength']) : 13; // default to 13 if not found
    } else {
        $totalBidPoints = 0;
        $totalPlayers = 0;
        $maxPlayersPerTeam = 13;
        $res_team = null;
    }

    // Compute remaining / max
    $remainingPoints = ($res_team && $res_team['remaining_purse'] !== null) ? floatval($res_team['remaining_purse']) : max(0, $totalPoints - $totalBidPoints);
    $remainingPlayersNeeded = max(0, $maxPlayersPerTeam - $totalPlayers);

    // Logic: Keep 1L min for each remaining player slot to ensure full team can be completed.
    $reserve = max(0, ($remainingPlayersNeeded - 1) * 100000);
    $maxBid = max(0, $remainingPoints - $reserve);

    // If no bidPoints provided, just return team info
    if ($bidPoints === null) {
        echo json_encode([
            'success' => true,
            'remaining_points' => $remainingPoints,
            'max_bid' => $maxBid,
            'player_count' => $totalPlayers,
            'max_players' => $maxPlayersPerTeam
        ]);
        exit;
    }

    // Check max strength
    if ($totalPlayers >= $maxPlayersPerTeam) {
        echo json_encode([
            'success' => false,
            'message' => "Team has already reached its maximum strength of $maxPlayersPerTeam players.",
            'remaining_points' => $remainingPoints,
            'max_bid' => 0,
            'player_count' => $totalPlayers,
            'max_players' => $maxPlayersPerTeam
        ]);
        exit;
    }

    // Validate bid amount against minimum bid
    if ($bidPoints < $minBid) {
        echo json_encode([
            'success' => false,
            'message' => "Minimum bid required is ₹" . number_format($minBid) . ".",
            'remaining_points' => $remainingPoints,
            'max_bid' => $maxBid,
            'player_count' => $totalPlayers,
            'max_players' => $maxPlayersPerTeam
        ]);
        exit;
    }

    // Validate bid amount against team's remaining purse
    if ($bidPoints > $remainingPoints) {
        echo json_encode([
            'success' => false,
            'message' => "Bid of ₹" . number_format($bidPoints) . " exceeds team's available Remaining Purse of ₹" . number_format($remainingPoints) . ".",
            'remaining_points' => $remainingPoints,
            'max_bid' => $maxBid,
            'player_count' => $totalPlayers,
            'max_players' => $maxPlayersPerTeam
        ]);
        exit;
    }

    if ($bidPoints > $maxBid) {
        echo json_encode([
            'success' => false,
            'message' => "Bid of ₹" . number_format($bidPoints) . " exceeds maximum allowed bid of ₹" . number_format($maxBid) . " (reserving funds for remaining players).",
            'remaining_points' => $remainingPoints,
            'max_bid' => $maxBid,
            'player_count' => $totalPlayers,
            'max_players' => $maxPlayersPerTeam
        ]);
        exit;
    }

    // Update the player record
    if ($playerId) {
        // Resolve numeric ID if it's a string player_id
        $stmt_resolve = $pdo->prepare("SELECT id FROM registrations WHERE id = ? OR player_id = ?");
        $stmt_resolve->execute([$playerId, $playerId]);
        $resolved = $stmt_resolve->fetch();

        if (!$resolved) {
            echo json_encode(['success' => false, 'message' => 'Player not found.']);
            exit;
        }
        $numericId = $resolved['id'];

        $stmt_update = $pdo->prepare("
            UPDATE registrations
            SET bid_points = ?, team_id = ?, booking_status = 1
            WHERE id = ?
        ");

        if ($stmt_update->execute([$bidPoints, $teamId, $numericId])) {
            if ($res_team && $res_team['remaining_purse'] !== null) {
                $stmt_rem = $pdo->prepare("UPDATE team_master SET remaining_purse = GREATEST(0, remaining_purse - ?) WHERE id = ?");
                $stmt_rem->execute([$bidPoints, $teamId]);
                $remainingPointsAfter = max(0, floatval($res_team['remaining_purse']) - $bidPoints);
            } else {
                $remainingPointsAfter = $remainingPoints - $bidPoints;
            }
            echo json_encode([
                'success' => true,
                'message' => 'Bid updated successfully!',
                'remaining_points' => $remainingPointsAfter,
                'max_bid' => $maxBid,
                'player_count' => $totalPlayers,
                'max_players' => $maxPlayersPerTeam
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Error updating player registration.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Missing player_id for placing bid.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>