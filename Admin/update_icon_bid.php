<?php
require_once '../auth.php';
check_access('auction_admin');

// update_icon_bid.php
require_once 'db_connection.php';
header('Content-Type: application/json');

$icon_id = $_POST['icon_id'] ?? null;
$team_id = $_POST['team_id'] ?? null;

if (!$icon_id || !$team_id) {
    echo json_encode(['success' => false, 'message' => 'Icon ID and Team ID are required.']);
    exit;
}

try {
    // No points involved for Icons as per user request
    $bid_points = 0;

    // 1. Update icon_players table
    $stmt = $pdo->prepare("UPDATE icon_players SET team_id = ?, bid_points = ?, booking_status = 1 WHERE id = ?");
    $stmt->execute([$team_id, $bid_points, $icon_id]);

    // 2. Update team_master with the icon name and image
    $stmt = $pdo->prepare("SELECT name, image FROM icon_players WHERE id = ?");
    $stmt->execute([$icon_id]);
    $icon = $stmt->fetch();

    $stmt = $pdo->prepare("UPDATE team_master SET ic_player = ?, ic_player_img = ? WHERE id = ?");
    $stmt->execute([$icon['name'], $icon['image'], $team_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
