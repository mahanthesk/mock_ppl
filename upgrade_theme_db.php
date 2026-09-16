<?php
// upgrade_theme_db.php
require_once 'db_connection.php';

try {
    // 1. Add theme_name column if it doesn't exist
    $checkCol = $pdo->query("SHOW COLUMNS FROM theme_settings LIKE 'theme_name'");
    if ($checkCol->rowCount() == 0) {
        $pdo->exec("ALTER TABLE theme_settings ADD COLUMN theme_name VARCHAR(100) DEFAULT 'Default Theme' AFTER id");
        echo "Column 'theme_name' added successfully.<br>";
        
        // Name the original theme
        $pdo->exec("UPDATE theme_settings SET theme_name = 'Gold Luxury' WHERE id = 1");
    } else {
        echo "Column 'theme_name' already exists.<br>";
    }

    // 2. Insert Blue Theme
    $checkBlue = $pdo->query("SELECT * FROM theme_settings WHERE theme_name = 'Blue Ocean'");
    if ($checkBlue->rowCount() == 0) {
        $insertBlue = "INSERT INTO theme_settings (
            theme_name, app_name, app_tagline, primary_color, secondary_color, accent_color, 
            success_color, danger_color, warning_color, info_color, background_color, 
            card_color, text_color, border_color, sidebar_color, sidebar_text_color, 
            sidebar_active_color, navbar_color, navbar_text_color, footer_color, footer_text_color,
            button_radius, card_radius, font_family, base_font_size, heading_font_size, 
            dark_mode, active_theme
        ) VALUES (
            'Blue Ocean', 'PPL', 'Pretium Premier League', '#007bff', '#66b0ff', '#0056b3', 
            '#28a745', '#dc3545', '#ffc107', '#17a2b8', '#f4f6f9', 
            '#ffffff', '#333333', 'rgba(0, 123, 255, 0.2)', '#343a40', '#c2c7d0', 
            '#ffffff', '#ffffff', '#007bff', '#343a40', '#ffffff',
            '4px', '8px', 'Poppins, sans-serif', '16px', '24px', 
            0, 0
        )";
        $pdo->exec($insertBlue);
        echo "Blue Ocean theme inserted.<br>";
    }

    // 3. Insert Minimal Light Theme
    $checkMinimal = $pdo->query("SELECT * FROM theme_settings WHERE theme_name = 'Minimal Light'");
    if ($checkMinimal->rowCount() == 0) {
        $insertMinimal = "INSERT INTO theme_settings (
            theme_name, app_name, app_tagline, primary_color, secondary_color, accent_color, 
            success_color, danger_color, warning_color, info_color, background_color, 
            card_color, text_color, border_color, sidebar_color, sidebar_text_color, 
            sidebar_active_color, navbar_color, navbar_text_color, footer_color, footer_text_color,
            button_radius, card_radius, font_family, base_font_size, heading_font_size, 
            dark_mode, active_theme
        ) VALUES (
            'Minimal Light', 'PPL', 'Pretium Premier League', '#333333', '#666666', '#111111', 
            '#198754', '#dc3545', '#ffc107', '#0dcaf0', '#ffffff', 
            '#f8f9fa', '#212529', '#e9ecef', '#ffffff', '#212529', 
            '#000000', '#ffffff', '#000000', '#f8f9fa', '#212529',
            '2px', '4px', 'Inter, sans-serif', '15px', '22px', 
            0, 0
        )";
        $pdo->exec($insertMinimal);
        echo "Minimal Light theme inserted.<br>";
    }

    echo "Database upgrade for Multiple Themes completed successfully.";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
