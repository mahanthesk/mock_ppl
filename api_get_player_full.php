<?php
// api_get_player_full.php — Returns both registration details and aggregate stats for a player
header('Content-Type: application/json');

require_once 'auth.php';
require_once 'db_connection.php';

if (!is_role_logged_in('registration_admin') && !is_role_logged_in('auction_admin') && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$player_id = trim($_GET['player_id'] ?? '');
$reg_id    = intval($_GET['id'] ?? 0);

if (empty($player_id) && $reg_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'player_id or id required.']);
    exit;
}

// Fetch registration
if ($reg_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ? AND deleted_at IS NULL");
    $stmt->execute([$reg_id]);
} else {
    $stmt = $pdo->prepare("SELECT * FROM registrations WHERE player_id = ? AND deleted_at IS NULL");
    $stmt->execute([$player_id]);
}
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo json_encode(['success' => false, 'message' => 'Player not found.']);
    exit;
}

// Fetch stats
$sStmt = $pdo->prepare("SELECT * FROM player_stats WHERE player_id = ? LIMIT 1");
$sStmt->execute([$player['player_id']]);
$stats = $sStmt->fetch(PDO::FETCH_ASSOC) ?: [];

echo json_encode([
    'success' => true,
    'player'  => $player,
    'stats'   => $stats
]);
exit;
