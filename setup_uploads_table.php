<?php
require 'db_connection.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS `upload_history` (
      `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
      `file_name` varchar(255) NOT NULL,
      `uploaded_by` varchar(100) DEFAULT NULL,
      `upload_date` datetime DEFAULT CURRENT_TIMESTAMP,
      `total_records` int(11) DEFAULT 0,
      `successful_records` int(11) DEFAULT 0,
      `failed_records` int(11) DEFAULT 0,
      `status` varchar(50) DEFAULT 'Completed'
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;";
    
    $pdo->exec($sql);
    echo "Table 'upload_history' created successfully.\n";
} catch (PDOException $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
