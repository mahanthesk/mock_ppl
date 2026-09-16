<?php
require_once '../auth.php';
check_access('auction_admin');

// fetch_icons.php
require_once 'db_connection.php';
header('Content-Type: application/json');

$id = isset($_GET['id']) ? intval($_GET['id']) : null;

try {
    if ($id) {
        $stmt = $pdo->prepare("SELECT i.*, t.team_name 
                                FROM icon_players i 
                                LEFT JOIN team_master t ON i.team_id = t.id 
                                WHERE i.id = ?");
        $stmt->execute([$id]);
    } else {
        $stmt = $pdo->prepare("SELECT i.*, t.team_name 
                                FROM icon_players i 
                                LEFT JOIN team_master t ON i.team_id = t.id 
                                ORDER BY i.id ASC");
        $stmt->execute();
    }
    $icons = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($icons);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>
