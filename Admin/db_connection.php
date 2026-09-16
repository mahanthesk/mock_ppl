<?php
// db_connection.php

$host = '127.0.0.1';
$port = '3306';          // Updated port
$dbname = 'parvamco_spl_season2_2026'; // Ensure this database exists or change to your preferred DB
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP empty password


try {
    // Added port=$port to the DSN
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);

    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If database doesn't exist, try to connect without dbname and create it
    if ($e->getCode() == 1049) { // Unknown database
        try {
            // Added port=$port here too
            $pdo = new PDO("mysql:host=$host;port=$port;charset=utf8", $username, $password);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbname`");
            $pdo->exec("USE `$dbname`");
        } catch (PDOException $ex) {
            die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $ex->getMessage()]));
        }
    } else {
        die(json_encode(["status" => "error", "message" => "Database Connection Failed: " . $e->getMessage()]));
    }
}

// Global points formatter
function formatPoints($val) {
    if ($val >= 10000000) {
        return round($val / 10000000, 2) . ' Cr';
    } else if ($val >= 100000) {
        return round($val / 100000, 2) . ' L';
    }
    return number_format($val);
}

// Global Categories Fetch
$globalCategories = [];
try {
    $catStmt = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC");
    $globalCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    // Silently ignore if table doesn't exist yet
}
?>