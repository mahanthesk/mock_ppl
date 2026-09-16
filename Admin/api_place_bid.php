<?php
// api_place_bid.php
require_once '../auth.php';
check_access('auction_admin');
require_once 'db_connection.php';
require_once 'bidding_config.php';

header('Content-Type: application/json');

$playerId = isset($_POST['player_id']) ? $_POST['player_id'] : null;
$teamId = isset($_POST['team_id']) ? intval($_POST['team_id']) : null;
$newBid = isset($_POST['bid_points']) ? floatval($_POST['bid_points']) : null;
$currentBid = isset($_POST['current_bid']) ? floatval($_POST['current_bid']) : null;

if (!$playerId || !$teamId || $newBid === null || $currentBid === null) {
    echo json_encode(['success' => false, 'message' => 'Missing parameters.']);
    exit;
}

try {
    // 1. Resolve Player ID
    $stmt_resolve = $pdo->prepare("SELECT id, bid_points, booking_status FROM registrations WHERE id = ? OR player_id = ?");
    $stmt_resolve->execute([$playerId, $playerId]);
    $player = $stmt_resolve->fetch();

    if (!$player) {
        echo json_encode(['success' => false, 'message' => 'Player not found.']);
        exit;
    }

    if ($player['booking_status'] == 1) {
        echo json_encode(['success' => false, 'message' => 'Player is already sold.']);
        exit;
    }

    // Ensure we sync the DB's current bid with frontend's presumed current bid
    // For intermediate bids, we'll store them in bid_points but keep booking_status = 0
    $dbCurrentBid = floatval($player['bid_points']);
    
    // If it's the very first bid (dbCurrentBid == 0 or null), we might bypass this check, 
    // but the frontend passes the base price as currentBid. So we trust currentBid as long as it's valid.
    // Let's enforce that the newBid == calculateNextBid(currentBid).
    $expectedNextBid = calculateNextBid($currentBid);

    if ($newBid != $expectedNextBid) {
        // Formatting function for error message
        $formattedExpected = "₹" . number_format($expectedNextBid);
        echo json_encode(['success' => false, 'message' => "Invalid bid amount. Expected next bid: {$formattedExpected}"]);
        exit;
    }

    // Check Max Bid / Purse logic
    $stmt_team = $pdo->prepare("SELECT total_purse, max_strength FROM team_master WHERE id = ?");
    $stmt_team->execute([$teamId]);
    $res_team = $stmt_team->fetch();

    $totalPoints = ($res_team && floatval($res_team['total_purse']) > 0) ? floatval($res_team['total_purse']) : 10000000;
    
    // Sum registrations
    $stmt_reg = $pdo->prepare("SELECT COALESCE(SUM(bid_points),0) AS total_bid_points, COUNT(id) AS total_players FROM registrations WHERE team_id = ? AND booking_status = 1");
    $stmt_reg->execute([$teamId]);
    $res_reg = $stmt_reg->fetch();

    // Sum icon players
    $stmt_icon = $pdo->prepare("SELECT COALESCE(SUM(bid_points), 0) AS total_icon_points FROM icon_players WHERE team_id = ? AND booking_status = 1");
    $stmt_icon->execute([$teamId]);
    $res_icon = $stmt_icon->fetch();

    $totalBidPoints = floatval($res_reg['total_bid_points']) + floatval($res_icon['total_icon_points']);
    $totalPlayers = intval($res_reg['total_players']);
    $maxPlayersPerTeam = $res_team ? intval($res_team['max_strength']) : 13;

    $remainingPoints = max(0, $totalPoints - $totalBidPoints);
    $remainingPlayersNeeded = max(0, $maxPlayersPerTeam - $totalPlayers);
    $reserve = max(0, ($remainingPlayersNeeded - 1) * 100000);
    $maxBidAllowed = max(0, $remainingPoints - $reserve);

    if ($newBid > $remainingPoints) {
        echo json_encode(['success' => false, 'message' => "Bid of ₹" . number_format($newBid) . " exceeds team's Remaining Purse of ₹" . number_format($remainingPoints) . "."]);
        exit;
    }

    if ($newBid > $maxBidAllowed) {
        echo json_encode(['success' => false, 'message' => "Bid exceeds max allowed bid of ₹" . number_format($maxBidAllowed) . " for this team."]);
        exit;
    }

    // 2. Register Intermediate Bid
    // We update bid_points and team_id, but keep booking_status = 0
    $numericId = $player['id'];
    $stmt_update = $pdo->prepare("UPDATE registrations SET bid_points = ?, team_id = ? WHERE id = ?");
    if ($stmt_update->execute([$newBid, $teamId, $numericId])) {
        // Send back updated data
        echo json_encode([
            'success' => true, 
            'current_bid' => $newBid,
            'next_bid' => calculateNextBid($newBid),
            'message' => 'Bid placed successfully!'
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save active bid.']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
