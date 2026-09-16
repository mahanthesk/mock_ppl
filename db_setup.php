<?php
// db_setup.php
require 'db_connection.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        player_id VARCHAR(20) UNIQUE,
        salutation VARCHAR(10),
        full_name VARCHAR(100) NOT NULL,
        category VARCHAR(50),
        profile_pic VARCHAR(255),
        prod_media_id VARCHAR(255),
        experience VARCHAR(20),
        jersey_nickname VARCHAR(50),
        jersey_number INT,
        jersey_size VARCHAR(5),
        track_pant_size INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        deleted_at TIMESTAMP NULL
    )";

    $pdo->exec($sql);
    echo "Table 'registrations' created or already exists successfully.";

} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
?>