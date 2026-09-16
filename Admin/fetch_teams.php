<?php
require_once '../auth.php';
check_access('auction_admin');

// fetch_teams.php
require_once 'db_connection.php';
header('Content-Type: application/json');

try {
    $stmt = $pdo->query("SELECT id, team_name FROM team_master ORDER BY team_name ASC");
    $teams = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($teams);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
