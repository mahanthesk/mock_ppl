<?php
// setup_theme_settings.php
require_once 'db_connection.php';

try {
    // 1. Create the theme_settings table
    $sql = "CREATE TABLE IF NOT EXISTS theme_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        app_name VARCHAR(255) DEFAULT 'PPL',
        app_tagline VARCHAR(255) DEFAULT 'Pretium Premier League',
        primary_color VARCHAR(50) DEFAULT '#3ABDD9',
        secondary_color VARCHAR(50) DEFAULT '#7EE8FA',
        accent_color VARCHAR(50) DEFAULT '#1D3FA1',
        success_color VARCHAR(50) DEFAULT '#198754',
        danger_color VARCHAR(50) DEFAULT '#dc3545',
        warning_color VARCHAR(50) DEFAULT '#ffc107',
        info_color VARCHAR(50) DEFAULT '#0dcaf0',
        background_color VARCHAR(50) DEFAULT '#000000',
        card_color VARCHAR(50) DEFAULT '#1a1a1a',
        text_color VARCHAR(50) DEFAULT '#ffffff',
        border_color VARCHAR(50) DEFAULT 'rgba(58, 189, 217, 0.3)',
        sidebar_color VARCHAR(50) DEFAULT 'rgba(255, 255, 255, 0.03)',
        sidebar_text_color VARCHAR(50) DEFAULT '#cccccc',
        sidebar_active_color VARCHAR(50) DEFAULT '#3ABDD9',
        navbar_color VARCHAR(50) DEFAULT 'rgba(0, 0, 0, 0.9)',
        navbar_text_color VARCHAR(50) DEFAULT '#3ABDD9',
        footer_color VARCHAR(50) DEFAULT '#111111',
        footer_text_color VARCHAR(50) DEFAULT '#bbbbbb',
        button_radius VARCHAR(20) DEFAULT '4px',
        card_radius VARCHAR(20) DEFAULT '12px',
        font_family VARCHAR(100) DEFAULT 'Poppins, sans-serif',
        base_font_size VARCHAR(20) DEFAULT '16px',
        heading_font_size VARCHAR(20) DEFAULT '24px',
        logo VARCHAR(255) NULL,
        favicon VARCHAR(255) NULL,
        login_background VARCHAR(255) NULL,
        dark_mode TINYINT(1) DEFAULT 1,
        active_theme TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

    $pdo->exec($sql);
    echo "Table 'theme_settings' created successfully or already exists.<br>";

    // 2. Insert default theme record if table is empty
    $checkStmt = $pdo->query("SELECT COUNT(*) FROM theme_settings");
    $count = $checkStmt->fetchColumn();

    if ($count == 0) {
        $insertSql = "INSERT INTO theme_settings (
            app_name, app_tagline, primary_color, secondary_color, accent_color, 
            success_color, danger_color, warning_color, info_color, background_color, 
            card_color, text_color, border_color, sidebar_color, sidebar_text_color, 
            sidebar_active_color, navbar_color, navbar_text_color, footer_color, footer_text_color,
            button_radius, card_radius, font_family, base_font_size, heading_font_size, 
            dark_mode, active_theme
        ) VALUES (
            'PPL', 'Pretium Premier League', '#3ABDD9', '#7EE8FA', '#1D3FA1', 
            '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#000000', 
            '#1a1a1a', '#ffffff', 'rgba(58, 189, 217, 0.3)', 'rgba(255, 255, 255, 0.03)', '#cccccc', 
            '#3ABDD9', 'rgba(0, 0, 0, 0.9)', '#3ABDD9', '#111111', '#bbbbbb',
            '4px', '12px', 'Poppins, sans-serif', '16px', '24px', 
            1, 1
        )";
        $pdo->exec($insertSql);
        echo "Default theme settings inserted successfully.<br>";
    } else {
        echo "Default theme settings already exist.<br>";
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
