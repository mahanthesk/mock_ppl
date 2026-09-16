<?php
require 'db_connection.php';
$stmt = $pdo->query("SELECT * FROM registrations WHERE email='arjun.kulkarni1@example.com'");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
