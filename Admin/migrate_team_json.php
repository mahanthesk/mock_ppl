<?php
require_once 'c:/xampp/htdocs/PretiumPremierLeague/Admin/db_connection.php';

try {
    // 1. Alter columns to TEXT
    $pdo->exec("ALTER TABLE team_master MODIFY owner TEXT");
    $pdo->exec("ALTER TABLE team_master MODIFY owner_img TEXT");
    $pdo->exec("ALTER TABLE team_master MODIFY ic_player TEXT");
    $pdo->exec("ALTER TABLE team_master MODIFY ic_player_img TEXT");

    // 2. Fetch all teams
    $stmt = $pdo->query("SELECT id, owner, owner_img, ic_player, ic_player_img FROM team_master");
    $teams = $stmt->fetchAll();

    foreach ($teams as $team) {
        $updateData = [];
        
        $fields = ['owner', 'owner_img', 'ic_player', 'ic_player_img'];
        foreach ($fields as $field) {
            $val = $team[$field];
            // Check if already JSON array
            if ($val !== null && $val !== '' && !str_starts_with(trim($val), '[')) {
                $updateData[$field] = json_encode([$val]);
            } else if ($val === '' || $val === null) {
                $updateData[$field] = json_encode([]);
            }
        }

        if (!empty($updateData)) {
            $setClause = [];
            $params = [];
            foreach ($updateData as $k => $v) {
                $setClause[] = "$k = :$k";
                $params[":$k"] = $v;
            }
            $params[':id'] = $team['id'];
            
            $sql = "UPDATE team_master SET " . implode(', ', $setClause) . " WHERE id = :id";
            $updStmt = $pdo->prepare($sql);
            $updStmt->execute($params);
        }
    }
    echo "Migration completed successfully!\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
