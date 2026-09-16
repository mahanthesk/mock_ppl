<?php
require_once __DIR__ . '/Admin/db_connection.php';

try {
    // 1. Create the users table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `users` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `username` VARCHAR(100) NOT NULL UNIQUE,
            `email` VARCHAR(100) UNIQUE NULL,
            `password` VARCHAR(255) NOT NULL,
            `full_name` VARCHAR(255) NULL,
            `role` VARCHAR(50) NOT NULL,
            `team_id` INT NULL,
            `team_name` VARCHAR(255) NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    echo "1. Created unified `users` table.\n";

    // 2. Migrate admins
    $adminsStmt = $pdo->query("SELECT * FROM `admins`");
    $admins = $adminsStmt->fetchAll();
    
    $insertUser = $pdo->prepare("
        INSERT IGNORE INTO `users` (username, password, role, created_at)
        VALUES (?, ?, ?, ?)
    ");
    
    $adminCount = 0;
    foreach ($admins as $admin) {
        $insertUser->execute([
            $admin['username'],
            $admin['password'],
            'admin',
            $admin['created_at']
        ]);
        $adminCount++;
    }
    
    echo "2. Migrated $adminCount admins to `users` table.\n";

    // 3. Migrate team_owners / team_master
    $teamsStmt = $pdo->query("SELECT id, team_name, owner FROM `team_master` ORDER BY id ASC");
    $teams = $teamsStmt->fetchAll();
    
    $insertOwner = $pdo->prepare("
        INSERT INTO `users` (username, password, role, team_id, team_name, full_name)
        VALUES (?, ?, 'team_owner', ?, ?, ?)
        ON DUPLICATE KEY UPDATE team_id = VALUES(team_id), team_name = VALUES(team_name), full_name = VALUES(full_name)
    ");
    
    $defaultPwd = password_hash('owner123', PASSWORD_DEFAULT);
    $ownerCount = 0;
    foreach ($teams as $team) {
        $username = 'owner' . $team['id'];
        $ownerDecoded = json_decode($team['owner'], true);
        $full_name = is_array($ownerDecoded) ? implode(', ', $ownerDecoded) : $team['owner'];

        $insertOwner->execute([
            $username,
            $defaultPwd,
            $team['id'],
            $team['team_name'],
            $full_name
        ]);
        $ownerCount++;
    }
    
    echo "3. Migrated $ownerCount team owners to `users` table.\n";
    
    echo "Migration completed successfully!\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage() . "\n");
}
?>
