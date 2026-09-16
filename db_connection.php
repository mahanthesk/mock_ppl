<?php
// db_connection.php

$host = '127.0.0.1';   
$dbname = 'parvamco_spl_season2_2026'; // Ensure this database exists or change to your preferred DB
$username = 'root';      // Default XAMPP username
$password = '';          // Default XAMPP empty password


try {
    // Added port=$port to the DSN (removed as it's undefined)
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);

    // Set PDO to throw exceptions on error
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // If database doesn't exist, try to connect without dbname and create it
    if ($e->getCode() == 1049) { // Unknown database
        try {
            // Added port=$port here too (removed as it's undefined)
            $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
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

// Global Theme Sharing (Phase 5)
require_once __DIR__ . '/ThemeService.php';
$globalThemeService = new ThemeService($pdo);
$theme = $globalThemeService->getTheme();

// Global Categories Fetch
$globalCategories = [];
try {
    $catStmt = $pdo->query("SELECT category_name FROM categories ORDER BY category_name ASC");
    if ($catStmt) {
        $globalCategories = $catStmt->fetchAll(PDO::FETCH_COLUMN);
    }
} catch (PDOException $e) {
    // Silently ignore if table doesn't exist yet
}

?>