<?php
// setup_player_stats.php — Run once to create the player_stats table
require 'db_connection.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `player_stats` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `player_id` VARCHAR(20) NOT NULL,
            `matches_played` INT DEFAULT 0,
            `innings` INT DEFAULT 0,
            `runs_scored` INT DEFAULT 0,
            `highest_score` INT DEFAULT 0,
            `batting_avg` DECIMAL(6,2) DEFAULT 0.00,
            `strike_rate` DECIMAL(6,2) DEFAULT 0.00,
            `fifties` INT DEFAULT 0,
            `hundreds` INT DEFAULT 0,
            `wickets` INT DEFAULT 0,
            `bowling_avg` DECIMAL(6,2) DEFAULT 0.00,
            `economy` DECIMAL(6,2) DEFAULT 0.00,
            `best_bowling` VARCHAR(10) DEFAULT NULL,
            `catches` INT DEFAULT 0,
            `stumpings` INT DEFAULT 0,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `unique_player` (`player_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    echo "<h2 style='color: green;'>✅ Table 'player_stats' created successfully!</h2>";
    echo "<p><a href='admin_dashboard.php'>← Back to Dashboard</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>
