<?php
// api_edit_stats.php — Update a player's aggregate stats in the player_stats table
header('Content-Type: application/json');

require_once 'auth.php';
require_once 'db_connection.php';

if (!is_role_logged_in('registration_admin') && !is_role_logged_in('auction_admin') && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$stat_id = intval($_POST['stat_id'] ?? 0);
if ($stat_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid stat record ID.']);
    exit;
}

// Verify the record exists
$check = $pdo->prepare("SELECT id FROM player_stats WHERE id = ?");
$check->execute([$stat_id]);
if (!$check->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Stats record not found.']);
    exit;
}

try {
    $sql = "UPDATE player_stats SET
        matches_played = ?,
        innings        = ?,
        runs_scored    = ?,
        highest_score  = ?,
        batting_avg    = ?,
        strike_rate    = ?,
        fifties        = ?,
        hundreds       = ?,
        wickets        = ?,
        bowling_avg    = ?,
        economy        = ?,
        best_bowling   = ?,
        catches        = ?,
        stumpings      = ?
    WHERE id = ?";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        intval($_POST['matches_played'] ?? 0),
        intval($_POST['innings']        ?? 0),
        intval($_POST['runs_scored']    ?? 0),
        intval($_POST['highest_score']  ?? 0),
        floatval($_POST['batting_avg']  ?? 0),
        floatval($_POST['strike_rate']  ?? 0),
        intval($_POST['fifties']        ?? 0),
        intval($_POST['hundreds']       ?? 0),
        intval($_POST['wickets']        ?? 0),
        floatval($_POST['bowling_avg']  ?? 0),
        floatval($_POST['economy']      ?? 0),
        trim($_POST['best_bowling']     ?? ''),
        intval($_POST['catches']        ?? 0),
        intval($_POST['stumpings']      ?? 0),
        $stat_id
    ]);

    echo json_encode([
        'success' => true,
        'message' => '✓ Stats updated successfully.',
        'stat_id' => $stat_id
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
exit;
