<?php
require_once 'auth.php';
check_access('registration_admin');

require_once 'db_connection.php';
require_once 'ThemeService.php';

$themeService = new ThemeService($pdo);
$theme = $themeService->getTheme();
$message = '';
$error = '';

$themeSettingObj = new ThemeSetting($pdo);
$allThemes = $themeSettingObj->getAllThemes();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'update';

    if ($action === 'switch') {
        $themeId = intval($_POST['switch_theme_id']);
        if ($themeSettingObj->setActiveTheme($themeId)) {
            $themeService->clearThemeCache();
            $message = "Theme switched successfully!";
            $theme = $themeService->getTheme(); // refresh
        } else {
            $error = "Failed to switch theme.";
        }
    } elseif ($action === 'export') {
        // Handle Export
        $exportTheme = $theme;
        unset($exportTheme['id'], $exportTheme['active_theme']); // Remove DB-specific IDs
        
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="theme_export_' . date('Y-m-d') . '.json"');
        echo json_encode($exportTheme, JSON_PRETTY_PRINT);
        exit;
    } elseif ($action === 'import') {
        // Handle Import
        if (isset($_FILES['import_file']) && $_FILES['import_file']['error'] === UPLOAD_ERR_OK) {
            $jsonContent = file_get_contents($_FILES['import_file']['tmp_name']);
            $importedData = json_decode($jsonContent, true);
            
            if (is_array($importedData)) {
                // Ensure theme_name exists
                $themeName = $importedData['theme_name'] ?? 'Imported Theme';
                unset($importedData['id'], $importedData['active_theme']); // ensure safe
                
                if ($themeSettingObj->createTheme($themeName, $importedData)) {
                    $message = "Theme imported successfully as a new theme!";
                    $allThemes = $themeSettingObj->getAllThemes(); // refresh list
                } else {
                    $error = "Failed to save imported theme to database.";
                }
            } else {
                $error = "Invalid JSON file.";
            }
        } else {
            $error = "Please upload a valid JSON file.";
        }
    } else {
        $updateData = [];
        $allowedFields = [
            'app_name', 'app_tagline', 'primary_color', 'secondary_color', 'accent_color',
            'success_color', 'danger_color', 'warning_color', 'info_color', 'background_color',
            'card_color', 'text_color', 'border_color', 'sidebar_color', 'sidebar_text_color',
            'sidebar_active_color', 'navbar_color', 'navbar_text_color', 'footer_color', 'footer_text_color',
            'button_radius', 'card_radius', 'font_family', 'base_font_size', 'heading_font_size',
            'dark_mode'
        ];

        foreach ($allowedFields as $field) {
            if (isset($_POST[$field])) {
                $updateData[$field] = $_POST[$field];
            }
        }

        // Handle File Uploads (Logo & Favicon)
        $uploadDir = 'uploads/theme/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $logoName = time() . '_' . basename($_FILES['logo']['name']);
            if (move_uploaded_file($_FILES['logo']['tmp_name'], $uploadDir . $logoName)) {
                $updateData['logo'] = $uploadDir . $logoName;
            }
        }

        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $faviconName = time() . '_' . basename($_FILES['favicon']['name']);
            if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadDir . $faviconName)) {
                $updateData['favicon'] = $uploadDir . $faviconName;
            }
        }
        
        if (isset($_FILES['login_background']) && $_FILES['login_background']['error'] === UPLOAD_ERR_OK) {
            $bgName = time() . '_' . basename($_FILES['login_background']['name']);
            if (move_uploaded_file($_FILES['login_background']['tmp_name'], $uploadDir . $bgName)) {
                $updateData['login_background'] = $uploadDir . $bgName;
            }
        }

        if ($action === 'save_as_new') {
            $newThemeName = $_POST['new_theme_name'] ?? 'New Theme';
            if ($themeSettingObj->createTheme($newThemeName, $updateData)) {
                $message = "New theme created successfully! You can switch to it from the dropdown.";
                $allThemes = $themeSettingObj->getAllThemes(); // refresh list
            } else {
                $error = "Failed to create new theme.";
            }
        } elseif ($action === 'update') {
            if ($themeService->updateTheme($theme['id'], $updateData)) {
                $message = "Theme settings updated successfully!";
                $theme = $themeService->getTheme(); // refresh
            } else {
                $error = "Failed to update theme settings.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Theme Settings - PPL Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
        :root {
            --primary-navy: #0f2a4a;
            --accent-teal: #0284c7;
            --header-teal: #28779b;
            --teal-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            --card-border: rgba(2, 132, 199, 0.18);
        }

        body {
            background-color: #f1f5f9;
            background-image: url('assets/ppl-cricket-bg.png');
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.94) !important;
            border-bottom: 1px solid var(--card-border) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 24px rgba(15, 42, 74, 0.08);
        }

        .navbar-brand {
            font-family: 'Outfit', sans-serif;
            color: var(--primary-navy) !important;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .dashboard-container {
            padding: 32px 24px;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.92);
            border-right: 1px solid var(--card-border);
            min-height: calc(100vh - 76px);
            padding: 24px 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .sidebar .nav-link {
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link i {
            color: var(--accent-teal);
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar .nav-link:hover {
            background: rgba(2, 132, 199, 0.08);
            color: var(--accent-teal) !important;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #0f2a4a 0%, #0284c7 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        }

        .sidebar .nav-link.active i {
            color: #ffffff !important;
        }

        .card-custom {
            background: #ffffff !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.07) !important;
            margin-bottom: 24px;
            overflow: hidden;
        }

        .card-header-custom {
            background: #f8fafc !important;
            border-bottom: 1px solid var(--card-border) !important;
            color: var(--primary-navy) !important;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            font-size: 1.05rem;
            padding: 16px 24px;
            border-radius: 20px 20px 0 0;
        }

        .form-label {
            color: #475569;
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .form-control, .form-select {
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f2a4a !important;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.9rem;
        }

        .form-control:focus, .form-select:focus {
            background-color: #ffffff;
            border-color: var(--accent-teal);
            color: #0f2a4a !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .form-select option {
            background-color: #ffffff;
            color: #0f2a4a;
        }

        .form-control[type="color"] {
            height: 42px;
            padding: 4px;
            cursor: pointer;
            border-radius: 10px;
        }

        .btn-gold {
            background: var(--teal-gradient) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 700;
            border-radius: 50px;
            padding: 10px 28px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--card-border);
            }
            .dashboard-container {
                padding: 20px 14px;
            }
        }
    </style>
</head>
<body>

    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="admin_dashboard.php">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height:46px; filter:drop-shadow(0 2px 8px rgba(2, 132, 199, 0.25));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                <div class="d-flex flex-column lh-1">
                    <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.12rem; letter-spacing: 0.5px; color: #0f2a4a;">PRETIUM PREMIER LEAGUE</span>
                    <span style="font-size: 0.72rem; letter-spacing: 1.5px; color: #0284c7; font-weight: 700; margin-top: 2px;">REGISTRATION ADMIN</span>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar"
                aria-controls="adminSidebar" aria-expanded="false" aria-label="Toggle navigation">
                <i class="fas fa-bars fs-4" style="color: #0284c7;"></i>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarContent">
                <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                    <div class="d-none d-sm-flex align-items-center px-3 py-1 rounded-pill" style="background: rgba(2, 132, 199, 0.08); border: 1px solid rgba(2, 132, 199, 0.2);">
                        <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 22px; width: 22px; object-fit: contain;">
                        <span class="ms-2 fw-semibold" style="font-size: 0.8rem; color: #0f2a4a;">PPL Season 2</span>
                    </div>
                    <span class="text-secondary small">Welcome, <strong style="color: #0f2a4a;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                    <a href="admin_logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                        <i class="fas fa-arrow-right-from-bracket me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 collapse d-md-block sidebar" id="adminSidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_players.php">
                            <i class="fas fa-users"></i> All Players
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_uploads.php">
                            <i class="fas fa-file-upload"></i> Uploads
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_player_stats.php">
                            <i class="fas fa-table"></i> View Stats Table
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_theme_settings.php">
                            <i class="fas fa-paint-brush"></i> Theme Settings
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-md-10 dashboard-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 style="font-family: 'Outfit', sans-serif; font-weight: 800; color: #0f2a4a; letter-spacing: -0.5px; margin: 0;">THEME <span style="color: #0284c7;">SETTINGS</span></h2>
                        <p class="text-muted mb-0 small mt-1">Configure layout, colors, typography, and visual branding</p>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" style="background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important;">
                        <i class="fas fa-check-circle fs-5 text-success"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($message) ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                        <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
                        <span class="fw-semibold"><?= htmlspecialchars($error) ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Theme Switcher & Actions -->
                <div class="card card-custom mb-4">
                    <div class="card-body p-4">
                        <div class="row align-items-center">
                            <div class="col-md-5">
                                <form method="POST" class="d-flex align-items-center">
                                    <input type="hidden" name="action" value="switch">
                                    <label class="form-label me-3 mb-0 text-nowrap" style="color: #0f2a4a;"><strong>Active Theme:</strong></label>
                                    <select name="switch_theme_id" class="form-select w-50 me-2" required>
                                        <?php foreach ($allThemes as $t): ?>
                                            <option value="<?= $t['id'] ?>" <?= $t['active_theme'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($t['theme_name']) ?> <?= $t['active_theme'] ? '(Active)' : '' ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">Switch</button>
                                </form>
                            </div>
                            <div class="col-md-7 mt-3 mt-md-0 d-flex justify-content-md-end align-items-center flex-wrap gap-2">
                                <span class="badge rounded-pill me-2 px-3 py-2" style="background: #e0f2fe; color: #0284c7; font-weight: 700; font-size: 0.85rem;">
                                    Editing: <?= htmlspecialchars($theme['theme_name'] ?? 'Theme') ?>
                                </span>
                                
                                <form method="POST" class="d-inline-block">
                                    <input type="hidden" name="action" value="export">
                                    <button type="submit" class="btn btn-outline-info btn-sm rounded-pill px-3"><i class="fas fa-download me-1"></i> Export</button>
                                </form>
                                
                                <form method="POST" enctype="multipart/form-data" class="d-inline-flex align-items-center gap-2">
                                    <input type="hidden" name="action" value="import">
                                    <input type="file" name="import_file" accept=".json" class="form-control form-control-sm" style="width: 220px;" required>
                                    <button type="submit" class="btn btn-outline-success btn-sm rounded-pill px-3"><i class="fas fa-upload me-1"></i> Import</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="admin_theme_settings.php" enctype="multipart/form-data">
                    <div class="row">
                        <!-- Branding & General -->
                        <div class="col-md-6">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom"><i class="fas fa-tag me-2 text-primary"></i> 1. Branding</div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label">App Name</label>
                                        <input type="text" name="app_name" class="form-control" value="<?= htmlspecialchars($theme['app_name'] ?? '') ?>" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">App Tagline</label>
                                        <input type="text" name="app_tagline" class="form-control" value="<?= htmlspecialchars($theme['app_tagline'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Logo Upload (Leave empty to keep current)</label>
                                        <input type="file" name="logo" class="form-control" accept="image/*">
                                        <?php if (!empty($theme['logo'])): ?>
                                            <div class="mt-2 p-2 rounded" style="background: #f1f5f9; display: inline-block;"><img src="<?= htmlspecialchars($theme['logo']) ?>" height="40" alt="Logo"></div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Favicon Upload (Leave empty to keep current)</label>
                                        <input type="file" name="favicon" class="form-control" accept="image/*">
                                        <?php if (!empty($theme['favicon'])): ?>
                                            <div class="mt-2 p-2 rounded" style="background: #f1f5f9; display: inline-block;"><img src="<?= htmlspecialchars($theme['favicon']) ?>" height="32" alt="Favicon"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="card card-custom">
                                <div class="card-header card-header-custom"><i class="fas fa-font me-2 text-primary"></i> 3. Typography</div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label">Font Family</label>
                                        <input type="text" name="font_family" class="form-control" value="<?= htmlspecialchars($theme['font_family'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Base Font Size</label>
                                        <input type="text" name="base_font_size" class="form-control" value="<?= htmlspecialchars($theme['base_font_size'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Heading Font Size</label>
                                        <input type="text" name="heading_font_size" class="form-control" value="<?= htmlspecialchars($theme['heading_font_size'] ?? '') ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card card-custom">
                                <div class="card-header card-header-custom"><i class="fas fa-shapes me-2 text-primary"></i> 4 &amp; 5. Elements &amp; Layout</div>
                                <div class="card-body p-4">
                                    <div class="mb-3">
                                        <label class="form-label">Button Border Radius</label>
                                        <input type="text" name="button_radius" class="form-control" value="<?= htmlspecialchars($theme['button_radius'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Card Border Radius</label>
                                        <input type="text" name="card_radius" class="form-control" value="<?= htmlspecialchars($theme['card_radius'] ?? '') ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Theme Mode</label>
                                        <select name="dark_mode" class="form-select">
                                            <option value="1" <?= (isset($theme['dark_mode']) && $theme['dark_mode'] == 1) ? 'selected' : '' ?>>Dark Mode</option>
                                            <option value="0" <?= (isset($theme['dark_mode']) && $theme['dark_mode'] == 0) ? 'selected' : '' ?>>Light Mode</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Login Background Image</label>
                                        <input type="file" name="login_background" class="form-control" accept="image/*">
                                        <?php if (!empty($theme['login_background'])): ?>
                                            <div class="mt-2 p-2 rounded" style="background: #f1f5f9; display: inline-block;"><img src="<?= htmlspecialchars($theme['login_background']) ?>" height="60" alt="Login Background"></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Colors -->
                        <div class="col-md-6">
                            <div class="card card-custom">
                                <div class="card-header card-header-custom"><i class="fas fa-palette me-2 text-primary"></i> 2. Colors (Global &amp; Structural)</div>
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Primary Color</label>
                                            <input type="color" name="primary_color" class="form-control w-100" value="<?= htmlspecialchars($theme['primary_color'] ?? '#3ABDD9') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Secondary Color</label>
                                            <input type="color" name="secondary_color" class="form-control w-100" value="<?= htmlspecialchars($theme['secondary_color'] ?? '#5cd1eb') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Accent Color</label>
                                            <input type="color" name="accent_color" class="form-control w-100" value="<?= htmlspecialchars($theme['accent_color'] ?? '#1e3aa7') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Background Color</label>
                                            <input type="color" name="background_color" class="form-control w-100" value="<?= htmlspecialchars($theme['background_color'] ?? '#000000') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Card Background</label>
                                            <input type="color" name="card_color" class="form-control w-100" value="<?= htmlspecialchars($theme['card_color'] ?? '#1a1a1a') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Global Text Color</label>
                                            <input type="color" name="text_color" class="form-control w-100" value="<?= htmlspecialchars($theme['text_color'] ?? '#ffffff') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Border Color</label>
                                            <input type="text" name="border_color" class="form-control" value="<?= htmlspecialchars($theme['border_color'] ?? 'rgba(58, 189, 217, 0.3)') ?>" placeholder="e.g. #333333 or rgba(...)">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="card card-custom">
                                <div class="card-header card-header-custom"><i class="fas fa-swatchbook me-2 text-primary"></i> 2. Colors (State &amp; Layout)</div>
                                <div class="card-body p-4">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Success Color</label>
                                            <input type="color" name="success_color" class="form-control w-100" value="<?= htmlspecialchars($theme['success_color'] ?? '#198754') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Danger Color</label>
                                            <input type="color" name="danger_color" class="form-control w-100" value="<?= htmlspecialchars($theme['danger_color'] ?? '#dc3545') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Warning Color</label>
                                            <input type="color" name="warning_color" class="form-control w-100" value="<?= htmlspecialchars($theme['warning_color'] ?? '#ffc107') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Info Color</label>
                                            <input type="color" name="info_color" class="form-control w-100" value="<?= htmlspecialchars($theme['info_color'] ?? '#0dcaf0') ?>">
                                        </div>
                                        
                                        <!-- Sidebar Colors -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sidebar Background</label>
                                            <input type="text" name="sidebar_color" class="form-control" value="<?= htmlspecialchars($theme['sidebar_color'] ?? 'rgba(255, 255, 255, 0.03)') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sidebar Text</label>
                                            <input type="color" name="sidebar_text_color" class="form-control w-100" value="<?= htmlspecialchars($theme['sidebar_text_color'] ?? '#cccccc') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Sidebar Active Item</label>
                                            <input type="color" name="sidebar_active_color" class="form-control w-100" value="<?= htmlspecialchars($theme['sidebar_active_color'] ?? '#3ABDD9') ?>">
                                        </div>

                                        <!-- Navbar Colors -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Navbar Background</label>
                                            <input type="text" name="navbar_color" class="form-control" value="<?= htmlspecialchars($theme['navbar_color'] ?? 'rgba(0, 0, 0, 0.9)') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Navbar Text</label>
                                            <input type="color" name="navbar_text_color" class="form-control w-100" value="<?= htmlspecialchars($theme['navbar_text_color'] ?? '#3ABDD9') ?>">
                                        </div>
                                        
                                        <!-- Footer Colors -->
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Footer Background</label>
                                            <input type="color" name="footer_color" class="form-control w-100" value="<?= htmlspecialchars($theme['footer_color'] ?? '#111111') ?>">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Footer Text</label>
                                            <input type="color" name="footer_text_color" class="form-control w-100" value="<?= htmlspecialchars($theme['footer_text_color'] ?? '#bbbbbb') ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    
                    <div class="card card-custom mt-4 p-4">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div>
                                <h5 class="mb-2 fw-bold" style="color: #0f2a4a; font-family: 'Outfit', sans-serif;">Save as New Theme</h5>
                                <div class="d-flex gap-2">
                                    <input type="text" name="new_theme_name" class="form-control" placeholder="Enter new theme name" style="min-width: 220px;">
                                    <button type="submit" name="action" value="save_as_new" class="btn btn-outline-primary rounded-pill text-nowrap"><i class="fas fa-copy me-1"></i> Save as New</button>
                                </div>
                            </div>
                            <div class="d-flex align-items-center flex-wrap gap-2">
                                <button type="button" class="btn btn-outline-danger rounded-pill px-4" onclick="if(confirm('Discard changes and reload?')) window.location.reload();">Cancel</button>
                                <button type="button" class="btn btn-outline-secondary rounded-pill px-4" id="resetLivePreview">Reset Preview</button>
                                <button type="submit" name="action" value="update" class="btn btn-gold btn-lg px-4"><i class="fas fa-save me-2"></i> Update Active Theme</button>
                            </div>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="color"], input[name$="_color"], input[name$="_radius"], input[name$="_font_size"], input[name="font_family"]');
            let hasUnsavedChanges = false;

            // Live Preview
            inputs.forEach(input => {
                // Store initial value
                input.dataset.originalValue = input.value;

                input.addEventListener('input', function(e) {
                    hasUnsavedChanges = true;
                    let varName = '--' + e.target.name.replace(/_/g, '-');
                    document.documentElement.style.setProperty(varName, e.target.value);
                });
            });

            // Prevent accidental navigation
            window.addEventListener('beforeunload', function (e) {
                if (hasUnsavedChanges) {
                    e.preventDefault();
                    e.returnValue = '';
                }
            });

            // Allow form submission without warning
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function() {
                    hasUnsavedChanges = false;
                });
            });

            // Reset Preview
            document.getElementById('resetLivePreview')?.addEventListener('click', function() {
                inputs.forEach(input => {
                    input.value = input.dataset.originalValue;
                    let varName = '--' + input.name.replace(/_/g, '-');
                    document.documentElement.style.setProperty(varName, input.dataset.originalValue);
                });
                hasUnsavedChanges = false;
            });
        });
    </script>
</body>
</html>
