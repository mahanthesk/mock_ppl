<?php
// theme_css.php
require_once 'db_connection.php'; // This also fetches $theme via ThemeService

// Caching Strategy
$cacheFile = __DIR__ . '/theme_settings.json';
if (file_exists($cacheFile)) {
    $lastModified = filemtime($cacheFile);
    $etag = md5_file($cacheFile);
    
    header("Last-Modified: " . gmdate("D, d M Y H:i:s", $lastModified) . " GMT");
    header("Etag: $etag");
    header("Cache-Control: public, max-age=3600"); // Cache for 1 hour locally
    
    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) >= $lastModified) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
    if (isset($_SERVER['HTTP_IF_NONE_MATCH']) && trim($_SERVER['HTTP_IF_NONE_MATCH']) == $etag) {
        header("HTTP/1.1 304 Not Modified");
        exit;
    }
}

header("Content-type: text/css; charset: UTF-8");

// Fallback defaults if something goes wrong
$primary_color = $theme['primary_color'] ?? '#0284c7';
$secondary_color = $theme['secondary_color'] ?? '#06b6d4';
$accent_color = $theme['accent_color'] ?? '#0f2a4a';
$success_color = $theme['success_color'] ?? '#059669';
$danger_color = $theme['danger_color'] ?? '#dc2626';
$warning_color = $theme['warning_color'] ?? '#d97706';
$info_color = $theme['info_color'] ?? '#0ea5e9';
$background_color = $theme['background_color'] ?? '#f8fafc';
$card_color = $theme['card_color'] ?? '#ffffff';
$text_color = $theme['text_color'] ?? '#0f172a';
$border_color = $theme['border_color'] ?? '#e2e8f0';
$sidebar_color = $theme['sidebar_color'] ?? '#ffffff';
$sidebar_text_color = $theme['sidebar_text_color'] ?? '#475569';
$sidebar_active_color = $theme['sidebar_active_color'] ?? '#0284c7';
$navbar_color = $theme['navbar_color'] ?? 'rgba(255, 255, 255, 0.95)';
$navbar_text_color = $theme['navbar_text_color'] ?? '#0f2a4a';
$footer_color = $theme['footer_color'] ?? 'rgba(255, 255, 255, 0.9)';
$footer_text_color = $theme['footer_text_color'] ?? '#64748b';
$button_radius = $theme['button_radius'] ?? '4px';
$card_radius = $theme['card_radius'] ?? '12px';
$font_family = $theme['font_family'] ?? 'Poppins, sans-serif';
$base_font_size = $theme['base_font_size'] ?? '16px';
$heading_font_size = $theme['heading_font_size'] ?? '24px';

// Minification function
ob_start(function($buffer) {
    // Remove comments
    $buffer = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $buffer);
    // Remove space after colons
    $buffer = str_replace(': ', ':', $buffer);
    // Remove whitespace
    $buffer = str_replace(array("\r\n", "\r", "\n", "\t", '  ', '    ', '    '), '', $buffer);
    return $buffer;
});
?>

:root {
    --primary-color: <?= $primary_color ?>;
    --secondary-color: <?= $secondary_color ?>;
    --accent-color: <?= $accent_color ?>;
    --success-color: <?= $success_color ?>;
    --danger-color: <?= $danger_color ?>;
    --warning-color: <?= $warning_color ?>;
    --info-color: <?= $info_color ?>;
    --background-color: <?= $background_color ?>;
    --card-color: <?= $card_color ?>;
    --text-color: <?= $text_color ?>;
    --border-color: <?= $border_color ?>;
    --sidebar-color: <?= $sidebar_color ?>;
    --sidebar-text-color: <?= $sidebar_text_color ?>;
    --sidebar-active-color: <?= $sidebar_active_color ?>;
    --navbar-color: <?= $navbar_color ?>;
    --navbar-text-color: <?= $navbar_text_color ?>;
    --footer-color: <?= $footer_color ?>;
    --footer-text-color: <?= $footer_text_color ?>;
    --button-radius: <?= $button_radius ?>;
    --card-radius: <?= $card_radius ?>;
    --font-family: <?= $font_family ?>;
    --base-font-size: <?= $base_font_size ?>;
    --heading-font-size: <?= $heading_font_size ?>;
    --glass: rgba(255, 255, 255, 0.05);
}

body {
    background-color: var(--background-color);
    color: var(--text-color);
    font-family: var(--font-family);
    font-size: var(--base-font-size);
}

h1, h2, h3, h4, h5, h6, .font-heading {
    font-family: 'Cinzel', serif;
}

.navbar { background: var(--navbar-color); border-bottom: 1px solid var(--border-color); }
.navbar-brand { color: var(--navbar-text-color) !important; }
.sidebar { background: var(--sidebar-color); border-right: 1px solid var(--border-color); }
.sidebar .nav-link { color: var(--sidebar-text-color) !important; white-space: nowrap; transition: all 0.3s; padding: 12px 15px; }
.sidebar .nav-link i { margin-right: 10px; width: 20px; text-align: center; }
.sidebar .nav-link:hover, .sidebar .nav-link.active { color: var(--sidebar-active-color) !important; background: rgba(255, 255, 255, 0.05); }
.card-custom, .stat-card { background: var(--card-color); border: 1px solid var(--border-color); border-radius: var(--card-radius); }
.btn-gold, .btn-primary-theme { background: linear-gradient(135deg, var(--secondary-color) 0%, var(--primary-color) 100%) !important; color: #000 !important; border: none; border-radius: var(--button-radius); }
.form-select option { background-color: #1a1a1a; color: #fff; }
.form-control::file-selector-button { color: #fff; background-color: #2b2b2b; border: none; border-right: 1px solid rgba(255, 255, 255, 0.1); margin-right: 15px; padding: 0.375rem 0.75rem; transition: background-color 0.2s ease; cursor: pointer; }
.form-control::file-selector-button:hover { background-color: #3b3b3b; }

<?php ob_end_flush(); ?>
