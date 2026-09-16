<?php

require_once 'auth.php';
check_access('registration_admin');

require 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';

// Note: CSV Upload for Player Stats has been merged into the Unified CSV Upload Screen (admin_uploads.php).


// Handle delete stats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_stat_id'])) {
    $del_id = intval($_POST['delete_stat_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM player_stats WHERE id = ?");
        $stmt->execute([$del_id]);
        $message = "Player stats deleted successfully.";
    } catch (Exception $e) {
        $error = "Failed to delete stats: " . $e->getMessage();
    }
}

// Handle bulk delete stats
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bulk_delete_stat_ids']) && is_array($_POST['bulk_delete_stat_ids'])) {
    $ids = array_map('intval', $_POST['bulk_delete_stat_ids']);
    $ids = array_filter($ids, function($val) { return $val > 0; });
    if (!empty($ids)) {
        try {
            $in_clause = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("DELETE FROM player_stats WHERE id IN ($in_clause)");
            $stmt->execute(array_values($ids));
            $count = $stmt->rowCount();
            $message = "$count player stats deleted successfully.";
        } catch (Exception $e) {
            $error = "Failed to bulk delete stats: " . $e->getMessage();
        }
    }
}

// Fetch all stats with player info
$stats = [];
try {
    $stats_stmt = $pdo->query("
        SELECT ps.*, r.id as reg_id, r.full_name, r.category, r.role, r.profile_pic, r.batting_type, r.bowling_type
        FROM player_stats ps
        JOIN registrations r ON ps.player_id = r.player_id
        WHERE r.deleted_at IS NULL
        ORDER BY ps.player_id ASC
    ");
    $stats = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Table might not exist yet
}

// Get unique categories for filter
$categories = $globalCategories;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Statistics - PPL Admin</title>
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

        .stat-card {
            background: #ffffff !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.07) !important;
            padding: 28px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .stat-title {
            font-family: 'Outfit', sans-serif;
            color: var(--primary-navy);
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.2px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(2, 132, 199, 0.12);
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-gold {
            background: var(--teal-gradient) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 700;
            border-radius: 50px;
            padding: 9px 24px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
        }

        .btn-outline-gold {
            background: #ffffff !important;
            color: var(--accent-teal) !important;
            border: 1.5px solid var(--accent-teal) !important;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.25s ease;
        }

        .btn-outline-gold:hover {
            background: rgba(2, 132, 199, 0.08) !important;
            color: #0369a1 !important;
        }

        .form-control, .form-control:focus {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f2a4a !important;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.9rem;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .form-select {
            background-color: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f2a4a;
            border-radius: 10px;
            font-size: 0.9rem;
        }
        .form-select:focus {
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }
        .form-select option { background: #ffffff; color: #0f2a4a; }

        .table-custom-wrapper {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            border: 1px solid var(--card-border);
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.06);
        }

        .table-stats {
            color: #0f2a4a;
            background: transparent;
            font-size: 0.85rem;
            margin-bottom: 0;
            width: 100%;
        }

        .table-stats thead th {
            border: none !important;
            color: #ffffff !important;
            font-family: 'Outfit', sans-serif;
            text-transform: uppercase;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 14px 10px;
            background: var(--header-teal) !important;
            white-space: nowrap;
            position: sticky;
            top: 0;
            z-index: 10;
            box-shadow: 0 2px 8px rgba(15, 42, 74, 0.1);
            vertical-align: middle;
        }

        .table-stats tbody td {
            border-bottom: 1px solid rgba(2, 132, 199, 0.08) !important;
            padding: 12px 10px;
            vertical-align: middle;
            color: #0f2a4a !important;
            background: #ffffff;
            font-size: 0.88rem;
        }

        .table-stats tbody tr:nth-of-type(even) td {
            background: #f8fafc;
        }

        .table-stats tbody tr:hover td {
            background: rgba(2, 132, 199, 0.06) !important;
        }

        .table-stats th:last-child,
        .table-stats td:last-child {
            text-align: center;
            white-space: nowrap !important;
            min-width: 110px;
        }

        .stats-count-badge {
            background: #e0f2fe;
            border: 1px solid rgba(2, 132, 199, 0.3);
            color: var(--accent-teal);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .badge-cat {
            background: #e0f2fe;
            color: var(--accent-teal);
            border: 1px solid rgba(2, 132, 199, 0.25);
            font-size: 0.72rem;
            padding: 4px 10px;
            white-space: nowrap;
            border-radius: 20px;
            display: inline-block;
            font-weight: 700;
        }

        .stat-mini-label {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #64748b;
            font-weight: 700;
        }

        .stat-mini-value {
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f2a4a;
            margin-top: 2px;
        }

        .stat-box {
            background: #f8fafc;
            border: 1px solid rgba(2, 132, 199, 0.15);
            border-radius: 12px;
            padding: 12px 8px;
            text-align: center;
            transition: all 0.2s ease;
        }
        .stat-box:hover {
            border-color: var(--accent-teal);
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.1);
        }

        .modal-content {
            background: #ffffff !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 24px !important;
            box-shadow: 0 25px 60px rgba(15, 42, 74, 0.2) !important;
            color: #0f2a4a !important;
            overflow: hidden;
        }

        .search-input {
            background: #f8fafc !important;
            border: 1.5px solid #cbd5e1 !important;
            color: #0f2a4a !important;
            border-radius: 10px;
            padding: 8px 14px;
            font-size: 0.88rem;
        }
        .search-input:focus {
            outline: none !important;
            border-color: var(--accent-teal) !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
            background: #ffffff !important;
        }
        .search-input::placeholder { color: #94a3b8 !important; }

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

        /* Combined edit modal tab pills */
        #esModalTabs .nav-link {
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            padding: 8px 18px;
            transition: all 0.25s;
            font-weight: 600;
        }
        #esModalTabs .nav-link.active {
            background: var(--teal-gradient);
            border-color: transparent;
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }
        #esModalTabs .nav-link:hover:not(.active) {
            background: #e2e8f0;
            color: #0f2a4a;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="admin_dashboard.php">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height:46px; filter:drop-shadow(0 2px 8px rgba(2, 132, 199, 0.25));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                <div class="d-flex flex-column lh-1">
                    <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.12rem; letter-spacing: 0.5px; color: #0f2a4a;">PRETIUM PREMIER LEAGUE</span>
                    <span style="font-size: 0.72rem; letter-spacing: 1.5px; color: #0284c7; font-weight: 700; margin-top: 2px;">REGISTRATION ADMIN</span>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
                <i class="fas fa-bars fs-4" style="color: #0284c7;"></i>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarContent">
                <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                    <div class="d-none d-sm-flex align-items-center px-3 py-1 rounded-pill" style="background: rgba(2, 132, 199, 0.08); border: 1px solid rgba(2, 132, 199, 0.2);">
                        <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 22px; width: 22px; object-fit: contain;">
                        <span class="ms-2 fw-semibold" style="font-size: 0.8rem; color: #0f2a4a;">PPL Season 2</span>
                    </div>
                    <span class="text-secondary small">Welcome, <strong style="color: #0f2a4a;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></strong></span>
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
                        <a class="nav-link active" href="admin_player_stats.php">
                            <i class="fas fa-table"></i> View Stats Table
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_theme_settings.php">
                            <i class="fas fa-paint-brush"></i> Theme Settings
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 dashboard-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 class="font-heading m-0" style="font-family: 'Outfit', sans-serif; font-weight: 800; color: #0f2a4a; letter-spacing: -0.5px;">PLAYER <span style="color: #0284c7;">STATISTICS</span></h2>
                        <p class="text-muted mb-0 small mt-1">Detailed batting, bowling, and fielding performance metrics</p>
                    </div>
                    <a href="admin_uploads.php" class="btn btn-gold d-inline-flex align-items-center gap-2">
                        <i class="fas fa-cloud-upload-alt"></i> Bulk Upload Stats
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" style="background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important;">
                        <i class="fas fa-check-circle fs-5 text-success"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($message); ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                        <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($error); ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Upload Section Notice -->
                <div class="stat-card mb-4" style="background: #e0f2fe !important; border-color: rgba(2, 132, 199, 0.25) !important;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div>
                            <div class="stat-title mb-1 border-0 pb-0" style="color: #0369a1;"><i class="fas fa-info-circle me-2"></i>CSV Uploads Merged into Unified Upload</div>
                            <p class="small mb-0" style="color: #0c4a6e;">Player Statistics and Registration imports have been combined. Please use the <strong>Unified Upload Screen</strong> to import or update player statistics via CSV or Excel.</p>
                        </div>
                        <a href="admin_uploads.php" class="btn btn-gold px-4 py-2">
                            <i class="fas fa-cloud-upload-alt me-2"></i> Go to Uploads
                        </a>
                    </div>
                </div>

                <!-- Stats Table -->
                <div class="stat-card">
                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
                        <div class="stat-title mb-0 pb-0 border-0">
                            <i class="fas fa-table text-primary"></i> Player Stats Overview
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3">
                            <button type="button" id="bulk-delete-btn" class="btn btn-sm btn-danger d-none align-items-center gap-2 rounded-pill px-3 shadow-sm" style="font-size: 0.8rem; font-weight: 700;" data-bs-toggle="modal" data-bs-target="#bulkDeleteConfirmModal">
                                <i class="fas fa-trash-alt me-1"></i> Delete Selected (<span id="selected-count">0</span>)
                            </button>
                            <input type="text" id="stats-search" class="search-input" placeholder="Search player name or ID..." style="min-width: 220px;">
                            <select id="stats-category-filter" class="form-select form-select-sm w-auto" style="min-width: 160px;">
                                <option value="all">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>"><?php echo htmlspecialchars($cat); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <span class="stats-count-badge" id="stats-count"><?php echo count($stats); ?> Players</span>
                        </div>
                    </div>

                    <?php if (empty($stats)): ?>
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-line fa-3x mb-3 d-block text-primary" style="opacity: 0.3;"></i>
                            <h5>No player stats uploaded yet</h5>
                            <p class="small">Upload a CSV file above to see player statistics here.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-custom-wrapper">
                            <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                                <table class="table table-stats" id="statsTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 40px; text-align: center;">
                                                <input class="form-check-input" type="checkbox" id="select-all-checkbox" title="Select / Deselect All">
                                            </th>
                                            <th>#</th>
                                            <th>Player ID</th>
                                            <th>Name</th>
                                            <th>Category</th>
                                            <th>Mat</th>
                                            <th>Inn</th>
                                            <th>Runs</th>
                                            <th>HS</th>
                                            <th>Avg</th>
                                            <th>SR</th>
                                            <th>50s</th>
                                            <th>100s</th>
                                            <th>Wkts</th>
                                            <th>B.Avg</th>
                                            <th>Eco</th>
                                            <th>BB</th>
                                            <th>Ct</th>
                                            <th>St</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats as $i => $s): ?>
                                        <tr class="stats-row" 
                                            data-category="<?php echo htmlspecialchars($s['category']); ?>"
                                            data-name="<?php echo htmlspecialchars($s['full_name']); ?>"
                                            data-playerid="<?php echo htmlspecialchars($s['player_id']); ?>"
                                            data-matches="<?php echo $s['matches_played']; ?>"
                                            data-innings="<?php echo $s['innings']; ?>"
                                            data-runs="<?php echo $s['runs_scored']; ?>"
                                            data-highest="<?php echo $s['highest_score']; ?>"
                                            data-batavg="<?php echo $s['batting_avg']; ?>"
                                            data-sr="<?php echo $s['strike_rate']; ?>"
                                            data-fifties="<?php echo $s['fifties']; ?>"
                                            data-hundreds="<?php echo $s['hundreds']; ?>"
                                            data-wickets="<?php echo $s['wickets']; ?>"
                                            data-bowlavg="<?php echo $s['bowling_avg']; ?>"
                                            data-economy="<?php echo $s['economy']; ?>"
                                            data-bestbowl="<?php echo htmlspecialchars($s['best_bowling']); ?>"
                                            data-catches="<?php echo $s['catches']; ?>"
                                            data-stumpings="<?php echo $s['stumpings']; ?>"
                                            data-role="<?php echo htmlspecialchars($s['role']); ?>"
                                            data-image="<?php echo htmlspecialchars($s['profile_pic']); ?>"
                                            data-battype="<?php echo htmlspecialchars($s['batting_type']); ?>"
                                            data-bowltype="<?php echo htmlspecialchars($s['bowling_type']); ?>"
                                            data-statid="<?php echo $s['id']; ?>"
                                            data-regid="<?php echo intval($s['reg_id']); ?>">
                                            <td style="text-align: center;" onclick="event.stopPropagation();">
                                                <input class="form-check-input player-stat-checkbox" type="checkbox" value="<?php echo $s['id']; ?>" data-player-id="<?php echo htmlspecialchars($s['player_id']); ?>" data-player-name="<?php echo htmlspecialchars($s['full_name']); ?>">
                                            </td>
                                            <td class="text-muted fw-semibold row-num"><?php echo $i + 1; ?></td>
                                            <td><span class="fw-bold" style="color: #0f2a4a;"><?php echo htmlspecialchars($s['player_id']); ?></span></td>
                                            <td><span class="fw-semibold" style="color: #0f2a4a;"><?php echo htmlspecialchars($s['full_name']); ?></span></td>
                                            <td><span class="badge-cat"><?php echo htmlspecialchars($s['category']); ?></span></td>
                                            <td><?php echo $s['matches_played']; ?></td>
                                            <td><?php echo $s['innings']; ?></td>
                                            <td class="fw-bold" style="color: #0284c7;"><?php echo $s['runs_scored']; ?></td>
                                            <td><?php echo $s['highest_score']; ?></td>
                                            <td><?php echo number_format($s['batting_avg'], 2); ?></td>
                                            <td><?php echo number_format($s['strike_rate'], 2); ?></td>
                                            <td><?php echo $s['fifties']; ?></td>
                                            <td><?php echo $s['hundreds']; ?></td>
                                            <td class="fw-bold" style="color: #dc2626;"><?php echo $s['wickets']; ?></td>
                                            <td><?php echo number_format($s['bowling_avg'], 2); ?></td>
                                            <td><?php echo number_format($s['economy'], 2); ?></td>
                                            <td><?php echo htmlspecialchars($s['best_bowling'] ?: '—'); ?></td>
                                            <td><?php echo $s['catches']; ?></td>
                                            <td><?php echo $s['stumpings']; ?></td>
                                            <td>
                                                <div class="d-flex align-items-center justify-content-center gap-1" style="white-space: nowrap;">
                                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill view-stats-btn py-1 px-2" style="font-size: 0.75rem;"
                                                        data-bs-toggle="modal" data-bs-target="#statsDetailModal" title="View Details">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill edit-stats-btn py-1 px-2" style="font-size: 0.75rem;"
                                                        title="Edit Stats">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill py-1 px-2 single-delete-btn" style="font-size: 0.75rem;" title="Delete" data-statid="<?php echo $s['id']; ?>" data-playername="<?php echo htmlspecialchars($s['full_name']); ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>

    <!-- Player Stats Detail Modal -->
    <div class="modal fade" id="statsDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0" style="background: #f8fafc; padding: 22px 28px;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="fas fa-chart-pie text-primary fs-5"></i>
                        <h5 class="modal-title fw-bold" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; letter-spacing: 0.5px;">PLAYER STATISTICS</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4">
                        <!-- Player Info -->
                        <div class="col-md-4 text-center">
                            <div style="border: 2px solid rgba(2, 132, 199, 0.25); border-radius: 16px; overflow: hidden; box-shadow: 0 8px 25px rgba(15, 42, 74, 0.1);">
                                <img id="modal-stat-image" src="assets/image-not-found.png" onerror="this.onerror=null;this.src='assets/image-not-found.png';" alt="Player" class="img-fluid w-100" style="height: 250px; object-fit: cover;">
                            </div>
                            <div class="mt-3">
                                <span id="modal-stat-id" class="px-3 py-1 fw-bold rounded-pill" style="background: #e0f2fe; color: #0284c7; font-size: 0.85rem; border: 1px solid rgba(2, 132, 199, 0.2);"></span>
                            </div>
                            <h4 id="modal-stat-name" class="fw-bold mt-2 mb-0" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;"></h4>
                            <p class="text-muted small mb-1">
                                <span id="modal-stat-role" class="fw-semibold text-primary"></span> · <span id="modal-stat-category"></span>
                            </p>
                            <p class="text-secondary small">
                                <span id="modal-stat-battype"></span> · <span id="modal-stat-bowltype"></span>
                            </p>
                        </div>

                        <!-- Stats Grid -->
                        <div class="col-md-8">
                            <!-- Batting -->
                            <h6 class="text-primary fw-bold mb-2" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; font-size: 0.82rem;">
                                <i class="fas fa-baseball-bat-ball me-1"></i> BATTING
                            </h6>
                            <div class="row g-2 mb-3">
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Matches</div><div class="stat-mini-value" id="md-matches"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Innings</div><div class="stat-mini-value" id="md-innings"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Runs</div><div class="stat-mini-value" id="md-runs" style="color: #0284c7;"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Highest</div><div class="stat-mini-value" id="md-highest"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Average</div><div class="stat-mini-value" id="md-batavg"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">SR</div><div class="stat-mini-value" id="md-sr"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">50s</div><div class="stat-mini-value" id="md-fifties"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">100s</div><div class="stat-mini-value" id="md-hundreds"></div></div></div>
                            </div>

                            <!-- Bowling -->
                            <h6 class="text-danger fw-bold mb-2" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; font-size: 0.82rem;">
                                <i class="fas fa-baseball me-1"></i> BOWLING
                            </h6>
                            <div class="row g-2 mb-3">
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Wickets</div><div class="stat-mini-value" id="md-wickets" style="color: #dc2626;"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Average</div><div class="stat-mini-value" id="md-bowlavg"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Economy</div><div class="stat-mini-value" id="md-economy"></div></div></div>
                                <div class="col-3"><div class="stat-box"><div class="stat-mini-label">Best</div><div class="stat-mini-value" id="md-bestbowl"></div></div></div>
                            </div>

                            <!-- Fielding -->
                            <h6 class="text-success fw-bold mb-2" style="font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; font-size: 0.82rem;">
                                <i class="fas fa-hands me-1"></i> FIELDING
                            </h6>
                            <div class="row g-2">
                                <div class="col-6"><div class="stat-box"><div class="stat-mini-label">Catches</div><div class="stat-mini-value" id="md-catches"></div></div></div>
                                <div class="col-6"><div class="stat-box"><div class="stat-mini-label">Stumpings</div><div class="stat-mini-value" id="md-stumpings"></div></div></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0" style="background: #f8fafc; padding: 16px 28px;">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ======= COMBINED EDIT MODAL (Player Details + Stats) ======= -->
    <div class="modal fade" id="editStatsModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <form id="editStatsForm" enctype="multipart/form-data" class="modal-content">
                <input type="hidden" name="id" id="esReg_id">
                <div class="modal-header border-0 pb-0" style="background: #f8fafc; padding: 22px 28px;">
                    <h5 class="modal-title fw-bold" style="font-family:'Outfit',sans-serif; color:#0f2a4a;">
                        <i class="fas fa-edit me-2 text-primary"></i>Edit — <span id="esPlayerLabel" style="color:#0284c7;"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body p-0">
                    <div id="esErrorAlert" class="alert alert-danger d-none m-3 mb-0 rounded-4"></div>
                    <div id="esSuccessAlert" class="alert alert-success d-none m-3 mb-0 rounded-4"></div>

                    <!-- TAB NAV -->
                    <ul class="nav nav-pills px-4 pt-3 pb-2 gap-2" id="esModalTabs">
                        <li class="nav-item">
                            <button class="nav-link" id="es-tab-stats-btn" type="button" data-es-tab="es-tab-stats"
                                style="font-size:0.85rem;">
                                <i class="fas fa-chart-bar me-1"></i> Player Stats
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="es-tab-details-btn" type="button" data-es-tab="es-tab-details"
                                style="font-size:0.85rem;">
                                <i class="fas fa-id-card me-1"></i> Player Details
                                <span id="esDetailsSpinner" class="spinner-border spinner-border-sm ms-1 d-none" style="width:.8rem;height:.8rem;"></span>
                            </button>
                        </li>
                    </ul>
                    <div style="height:1px; background:rgba(2, 132, 199, 0.15); margin:0 1.5rem;"></div>

                    <div class="p-4">
                        <!-- ── TAB: PLAYER STATS (default open) ── -->
                        <div id="es-tab-stats">
                            <h6 class="fw-bold mb-3" style="color:#0284c7; font-size:0.85rem; border-bottom:1px dashed rgba(2, 132, 199, 0.3); padding-bottom:8px;">
                                <i class="fas fa-baseball-bat-ball me-2"></i>BATTING
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Matches</label><input type="number" name="matches_played" id="es_matches" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Innings</label><input type="number" name="innings" id="es_innings" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Runs Scored</label><input type="number" name="runs_scored" id="es_runs" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Highest Score</label><input type="number" name="highest_score" id="es_highest" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Batting Avg</label><input type="number" step="0.01" name="batting_avg" id="es_batavg" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Strike Rate</label><input type="number" step="0.01" name="strike_rate" id="es_sr" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Fifties (50s)</label><input type="number" name="fifties" id="es_fifties" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Hundreds (100s)</label><input type="number" name="hundreds" id="es_hundreds" class="form-control" min="0"></div>
                            </div>
                            <h6 class="fw-bold mb-3" style="color:#dc2626; font-size:0.85rem; border-bottom:1px dashed rgba(220, 38, 38, 0.3); padding-bottom:8px;">
                                <i class="fas fa-baseball me-2"></i>BOWLING
                            </h6>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Wickets</label><input type="number" name="wickets" id="es_wickets" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Bowling Avg</label><input type="number" step="0.01" name="bowling_avg" id="es_bowlavg" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Economy</label><input type="number" step="0.01" name="economy" id="es_economy" class="form-control" min="0"></div>
                                <div class="col-md-3 col-6"><label class="form-label small fw-bold text-secondary">Best Bowling</label><input type="text" name="best_bowling" id="es_bestbowl" class="form-control" placeholder="e.g. 3/18"></div>
                            </div>
                            <h6 class="fw-bold mb-3" style="color:#16a34a; font-size:0.85rem; border-bottom:1px dashed rgba(22, 163, 74, 0.3); padding-bottom:8px;">
                                <i class="fas fa-hands me-2"></i>FIELDING
                            </h6>
                            <div class="row g-3">
                                <div class="col-md-6 col-6"><label class="form-label small fw-bold text-secondary">Catches</label><input type="number" name="catches" id="es_catches" class="form-control" min="0"></div>
                                <div class="col-md-6 col-6"><label class="form-label small fw-bold text-secondary">Stumpings</label><input type="number" name="stumpings" id="es_stumpings" class="form-control" min="0"></div>
                            </div>
                        </div>

                        <!-- ── TAB: PLAYER DETAILS (loaded on demand) ── -->
                        <div id="es-tab-details" style="display:none;">
                            <div id="esDetailsLoadingMsg" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Loading player details...
                            </div>
                            <div id="esDetailsFormContent" style="display:none;">
                                <div class="row g-3">
                                    <div class="col-md-2"><label class="form-label small fw-bold text-secondary">Salutation</label>
                                        <select name="salutation" id="esd_salutation" class="form-select">
                                            <option value="Mr.">Mr.</option><option value="Ms.">Ms.</option><option value="Dr.">Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5"><label class="form-label small fw-bold text-secondary">Player Name *</label><input type="text" name="full_name" id="esd_full_name" class="form-control" required></div>
                                    <div class="col-md-5"><label class="form-label small fw-bold text-secondary">Mobile *</label><input type="text" name="mobile" id="esd_mobile" class="form-control" required maxlength="15"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Email</label><input type="email" name="email" id="esd_email" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Date of Birth</label><input type="date" name="dob" id="esd_dob" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Category *</label>
                                        <select name="category" id="esd_category" class="form-select" required>
                                            <option value="">Select Category</option>
                                            <?php
                                            if (!empty($globalCategories)) {
                                                foreach ($globalCategories as $cat) {
                                                    echo '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
                                                }
                                            } else {
                                                echo '<option value="Celebrities">Celebrities</option>';
                                                echo '<option value="Female Celebrities">Female Celebrities</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Role *</label>
                                        <select name="role" id="esd_role" class="form-select" required>
                                            <option value="All-rounder">All-rounder</option><option value="Batsman">Batsman</option><option value="Bowler">Bowler</option><option value="Wicketkeeper">Wicketkeeper</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Experience</label>
                                        <select name="experience" id="esd_experience" class="form-select">
                                            <option value="Professional">Professional</option><option value="Amateur">Amateur</option><option value="Beginner">Beginner</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Batting Style</label>
                                        <select name="batting_type" id="esd_batting_type" class="form-select">
                                            <option value="Right Hand">Right Hand</option><option value="Left Hand">Left Hand</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Bowling Style</label>
                                        <select name="bowling_type" id="esd_bowling_type" class="form-select">
                                            <option value="Right Arm Fast">Right Arm Fast</option><option value="Right Arm Medium">Right Arm Medium</option>
                                            <option value="Right Arm Spin">Right Arm Spin</option><option value="Left Arm Fast">Left Arm Fast</option>
                                            <option value="Left Arm Medium">Left Arm Medium</option><option value="Left Arm Spin">Left Arm Spin</option>
                                            <option value="None">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Jersey Number</label><input type="number" name="jersey_number" id="esd_jersey_number" class="form-control" min="1" max="99"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Jersey Nickname</label><input type="text" name="jersey_nickname" id="esd_jersey_nickname" class="form-control"></div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Jersey Size</label>
                                        <select name="jersey_size" id="esd_jersey_size" class="form-select">
                                            <option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="XXL">XXL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4"><label class="form-label small fw-bold text-secondary">Track Pant Size</label><input type="number" name="track_pant_size" id="esd_track_pant_size" class="form-control" placeholder="e.g. 32"></div>
                                    <div class="col-md-6"><label class="form-label small fw-bold text-secondary">Change Photo (optional)</label>
                                        <input type="file" name="profile_pic" id="esd_profile_pic" class="form-control" accept="image/*">
                                        <div id="esd_photo_preview" class="mt-2"></div>
                                    </div>
                                    <div class="col-md-6"><label class="form-label small fw-bold text-secondary">Address</label><textarea name="address" id="esd_address" class="form-control" rows="3"></textarea></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer border-0" style="background:#f8fafc; padding: 16px 28px;">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="esSaveBtn" class="btn btn-gold rounded-pill px-4 fw-bold">
                        <i class="fas fa-save me-2"></i>Save All Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- End Combined Edit Modal -->

    <!-- Single Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: 1px solid rgba(220, 53, 69, 0.3) !important;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #dc3545, #ff6b6b, #dc3545);"></div>
                <div class="modal-header border-0 pb-0" style="padding: 24px 24px 12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(220, 53, 69, 0.2);">
                            <i class="fas fa-trash-alt" style="font-size: 1.2rem; color: #dc2626;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 1.15rem; color: #0f2a4a;">Confirm Deletion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST">
                    <div class="modal-body" style="padding: 20px 24px 16px;">
                        <input type="hidden" name="delete_stat_id" id="single-delete-stat-id">
                        <p style="color: #475569; font-size: 0.95rem; margin-bottom: 16px;">Are you sure you want to permanently delete statistics for <strong class="text-primary fs-5" id="delete-player-name"></strong>?</p>
                        <div class="d-flex align-items-center gap-2" style="color: #dc2626; font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>This action cannot be undone.</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 8px 24px 24px; gap: 10px;">
                        <button type="button" class="btn px-4 rounded-pill" data-bs-dismiss="modal" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600;">Cancel</button>
                        <button type="submit" class="btn px-4 rounded-pill" style="background: linear-gradient(135deg, #dc3545, #b91c1c); color: #fff; border: none; font-weight: 600; box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);">
                            <i class="fas fa-trash-alt me-1"></i> Yes, Delete
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Delete Confirmation Modal -->
    <div class="modal fade" id="bulkDeleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="border: 1px solid rgba(220, 53, 69, 0.3) !important;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #dc3545, #ff6b6b, #dc3545);"></div>
                <div class="modal-header border-0 pb-0" style="padding: 24px 24px 12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(220, 53, 69, 0.2);">
                            <i class="fas fa-trash-alt" style="font-size: 1.2rem; color: #dc2626;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 1.15rem; color: #0f2a4a;">Confirm Bulk Deletion</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" id="bulk-delete-form">
                    <div class="modal-body" style="padding: 20px 24px 16px;">
                        <p style="color: #475569; font-size: 0.95rem; margin-bottom: 12px;">Are you sure you want to permanently delete statistics for <strong class="text-danger fs-5" id="confirm-selected-count">0</strong> selected players?</p>
                        <div class="mb-2 small fw-bold text-secondary">Selected Players List:</div>
                        <div class="p-3 rounded-3" style="max-height: 220px; overflow-y: auto; background: #f8fafc; border: 1px solid #cbd5e1;" id="confirm-selected-list">
                            <!-- Populated via JavaScript -->
                        </div>
                        <div id="bulk-hidden-inputs-container">
                            <!-- Hidden input delete ids populated via JS -->
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-3" style="color: #dc2626; font-size: 0.85rem;">
                            <i class="fas fa-exclamation-triangle"></i>
                            <span>This action cannot be undone.</span>
                        </div>
                    </div>
                    <div class="modal-footer border-0" style="padding: 8px 24px 24px; gap: 10px;">
                        <button type="button" class="btn px-4 rounded-pill" data-bs-dismiss="modal" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600;">Cancel</button>
                        <button type="submit" class="btn px-4 rounded-pill" style="background: linear-gradient(135deg, #dc3545, #b91c1c); color: #fff; border: none; font-weight: 600; box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);">
                            <i class="fas fa-trash-alt me-1"></i> Yes, Delete Selected
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    $(document).ready(function() {
        // Auto-dismiss notification alert messages after 5 seconds (5000ms)
        setTimeout(function() {
            $('.alert').fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);

        // Bulk selection logic
        $('#select-all-checkbox').on('change', function() {
            const isChecked = $(this).is(':checked');
            $('.stats-row:visible .player-stat-checkbox').prop('checked', isChecked);
            updateBulkSelection();
        });

        $(document).on('change', '.player-stat-checkbox', function(e) {
            e.stopPropagation();
            updateBulkSelection();
        });

        function updateBulkSelection() {
            const checkedBoxes = $('.player-stat-checkbox:checked');
            const count = checkedBoxes.length;

            if (count > 0) {
                $('#bulk-delete-btn').removeClass('d-none').css('display', 'inline-flex');
                $('#selected-count').text(count);
            } else {
                $('#bulk-delete-btn').addClass('d-none').hide();
            }

            const totalVisible = $('.stats-row:visible .player-stat-checkbox').length;
            $('#select-all-checkbox').prop('checked', totalVisible > 0 && count === totalVisible);
        }

        $('#bulk-delete-btn').on('click', function() {
            const checkedBoxes = $('.player-stat-checkbox:checked');
            $('#confirm-selected-count').text(checkedBoxes.length);

            let listHtml = '';
            let hiddenInputsHtml = '';

            checkedBoxes.each(function() {
                const id = $(this).val();
                const pid = $(this).data('player-id');
                const name = $(this).data('player-name');

                listHtml += `<div class="d-flex justify-content-between align-items-center border-bottom border-secondary border-opacity-25 py-2">
                    <div>
                        <span class="badge bg-warning text-dark me-2">${pid}</span>
                        <span class="text-white fw-medium">${name}</span>
                    </div>
                    <span class="badge bg-danger bg-opacity-75">Delete</span>
                </div>`;

                hiddenInputsHtml += `<input type="hidden" name="bulk_delete_stat_ids[]" value="${id}">`;
            });

            $('#confirm-selected-list').html(listHtml);
            $('#bulk-hidden-inputs-container').html(hiddenInputsHtml);
        });

        // View stats button click — populate modal
        $(document).on('click', '.view-stats-btn', function(e) {
            e.stopPropagation();
            const row = $(this).closest('tr');
            $('#modal-stat-name').text(row.data('name'));
            $('#modal-stat-id').text(row.data('playerid'));
            $('#modal-stat-category').text(row.data('category'));
            $('#modal-stat-role').text(row.data('role'));
            $('#modal-stat-battype').text(row.data('battype') || 'N/A');
            $('#modal-stat-bowltype').text(row.data('bowltype') || 'N/A');

            const img = row.data('image');
            $('#modal-stat-image').attr('src', img ? img : 'assets/image-not-found.png');

            $('#md-matches').text(row.data('matches'));
            $('#md-innings').text(row.data('innings'));
            $('#md-runs').text(row.data('runs'));
            $('#md-highest').text(row.data('highest'));
            $('#md-batavg').text(parseFloat(row.data('batavg')).toFixed(2));
            $('#md-sr').text(parseFloat(row.data('sr')).toFixed(2));
            $('#md-fifties').text(row.data('fifties'));
            $('#md-hundreds').text(row.data('hundreds'));
            $('#md-wickets').text(row.data('wickets'));
            $('#md-bowlavg').text(parseFloat(row.data('bowlavg')).toFixed(2));
            $('#md-economy').text(parseFloat(row.data('economy')).toFixed(2));
            $('#md-bestbowl').text(row.data('bestbowl') || '—');
            $('#md-catches').text(row.data('catches'));
            $('#md-stumpings').text(row.data('stumpings'));
        });

        // Single delete button click
        $(document).on('click', '.single-delete-btn', function(e) {
            e.stopPropagation();
            const statId = $(this).data('statid');
            const playerName = $(this).data('playername');
            $('#single-delete-stat-id').val(statId);
            $('#delete-player-name').text(playerName);
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        });

        // Edit stats button click — open combined edit modal pre-filled
        $(document).on('click', '.edit-stats-btn', function(e) {
            e.stopPropagation();
            const row   = $(this).closest('tr');
            const name  = row.data('name');
            const pid   = row.data('playerid');
            const regId = row.data('regid');   // registration table id

            // Populate hidden registration id and modal title
            $('#esReg_id').val(regId);
            $('#esPlayerLabel').text(pid + ' — ' + name);

            // Batting fields
            $('#es_matches').val(row.data('matches'));
            $('#es_innings').val(row.data('innings'));
            $('#es_runs').val(row.data('runs'));
            $('#es_highest').val(row.data('highest'));
            $('#es_batavg').val(parseFloat(row.data('batavg') || 0).toFixed(2));
            $('#es_sr').val(parseFloat(row.data('sr') || 0).toFixed(2));
            $('#es_fifties').val(row.data('fifties'));
            $('#es_hundreds').val(row.data('hundreds'));

            // Bowling fields
            $('#es_wickets').val(row.data('wickets'));
            $('#es_bowlavg').val(parseFloat(row.data('bowlavg') || 0).toFixed(2));
            $('#es_economy').val(parseFloat(row.data('economy') || 0).toFixed(2));
            $('#es_bestbowl').val(row.data('bestbowl') || '');

            // Fielding fields
            $('#es_catches').val(row.data('catches'));
            $('#es_stumpings').val(row.data('stumpings'));

            // Reset details-loaded flag
            esDetailsLoaded = false;

            // Clear alerts & set default tab active on open
            $('#esErrorAlert, #esSuccessAlert').addClass('d-none').text('');
            $('#esSaveBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save All Changes');

            // Reset tabs: Stats is default
            $('#esModalTabs button').removeClass('active');
            $('#es-tab-stats-btn').addClass('active');
            $('#es-tab-stats').show();
            $('#es-tab-details').hide();

            // Show modal
            new bootstrap.Modal(document.getElementById('editStatsModal')).show();
        });

        // ES Tab switching
        var esDetailsLoaded = false;
        $('#esModalTabs').on('click', 'button', function() {
            var tab = $(this).data('es-tab');
            $('#esModalTabs button').removeClass('active');
            $(this).addClass('active');
            $('#es-tab-stats, #es-tab-details').hide();
            $('#' + tab).show();

            if (tab === 'es-tab-details' && !esDetailsLoaded) {
                fetchDetailsForEditStats();
            }
        });

        function fetchDetailsForEditStats() {
            var regId = $('#esReg_id').val();
            if (!regId) return;
            $('#esDetailsSpinner').removeClass('d-none');
            $('#esDetailsLoadingMsg').show();
            $('#esDetailsFormContent').hide();

            $.ajax({
                url: 'api_get_player_full.php',
                type: 'GET',
                data: { id: regId },
                dataType: 'json',
                success: function(res) {
                    $('#esDetailsSpinner').addClass('d-none');
                    if (res.success && res.player) {
                        var p = res.player;
                        $('#esd_salutation').val(p.salutation || 'Mr.');
                        $('#esd_full_name').val(p.full_name || '');
                        $('#esd_mobile').val(p.mobile || '');
                        $('#esd_email').val(p.email || '');
                        $('#esd_dob').val(p.dob || '');
                        $('#esd_category').val(p.category || '');
                        $('#esd_role').val(p.role || '');
                        $('#esd_experience').val(p.experience || 'Professional');
                        $('#esd_batting_type').val(p.batting_type || 'Right Hand');
                        $('#esd_bowling_type').val(p.bowling_type || 'Right Arm Fast');
                        $('#esd_jersey_number').val(p.jersey_number || '');
                        $('#esd_jersey_nickname').val(p.jersey_nickname || '');
                        $('#esd_jersey_size').val(p.jersey_size || 'M');
                        $('#esd_track_pant_size').val(p.track_pant_size || '');
                        $('#esd_address').val(p.address || '');

                        if (p.profile_pic) {
                            $('#esd_photo_preview').html(
                                '<div class="d-flex align-items-center gap-2 mt-1">' +
                                '<img src="' + p.profile_pic + '" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--gold);" onerror="this.style.display=\'none\'">' +
                                '<span class="small text-muted">Current photo — upload new to replace</span></div>'
                            );
                        }
                        esDetailsLoaded = true;
                        $('#esDetailsLoadingMsg').hide();
                        $('#esDetailsFormContent').show();
                    } else {
                        $('#esDetailsLoadingMsg').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Could not load player details.</span>');
                    }
                },
                error: function() {
                    $('#esDetailsSpinner').addClass('d-none');
                    $('#esDetailsLoadingMsg').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Server error loading details.</span>');
                }
            });
        }

        // Reset on close
        $('#editStatsModal').on('hidden.bs.modal', function() {
            $('#editStatsForm')[0].reset();
            $('#esErrorAlert, #esSuccessAlert').addClass('d-none').text('');
            $('#esSaveBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save All Changes');
            esDetailsLoaded = false;
            $('#esDetailsFormContent').hide();
            $('#esDetailsLoadingMsg').show().html('<span class="spinner-border spinner-border-sm me-2"></span> Loading player details...');
            $('#esd_photo_preview').html('');
            // Reset tabs
            $('#esModalTabs button').removeClass('active');
            $('#es-tab-stats-btn').addClass('active');
            $('#es-tab-stats').show();
            $('#es-tab-details').hide();
        });

        // Submit combined form via AJAX (multipart for photo support)
        $('#editStatsForm').on('submit', function(e) {
            e.preventDefault();
            const btn = $('#esSaveBtn');
            const origHtml = '<i class="fas fa-save me-2"></i>Save All Changes';
            $('#esErrorAlert, #esSuccessAlert').addClass('d-none').text('');
            btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Saving...');

            var formData = new FormData(this);

            $.ajax({
                url: 'api_edit_combined.php',
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        $('#esSuccessAlert').removeClass('d-none').text(res.message || '✓ Updated successfully.');
                        setTimeout(function() {
                            $('#editStatsModal').modal('hide');
                            location.reload();
                        }, 1400);
                    } else {
                        $('#esErrorAlert').removeClass('d-none').text(res.message || 'Failed to update.');
                        btn.prop('disabled', false).html(origHtml);
                    }
                },
                error: function() {
                    $('#esErrorAlert').removeClass('d-none').text('Server error. Please try again.');
                    btn.prop('disabled', false).html(origHtml);
                }
            });
        });

        // Search filter
        $('#stats-search').on('keyup', function() {
            filterTable();
        });

        // Category filter
        $('#stats-category-filter').on('change', function() {
            filterTable();
        });

        function filterTable() {
            const search = $('#stats-search').val().toLowerCase();
            const category = $('#stats-category-filter').val();
            let visible = 0;

            $('.stats-row').each(function() {
                const name = $(this).data('name').toString().toLowerCase();
                const pid = $(this).data('playerid').toString().toLowerCase();
                const cat = $(this).data('category');

                const matchSearch = name.includes(search) || pid.includes(search);
                const matchCat = category === 'all' || cat === category;

                if (matchSearch && matchCat) {
                    $(this).show();
                    visible++;
                } else {
                    $(this).hide();
                }
            });

            // Re-number visible rows
            let i = 1;
            $('.stats-row:visible').each(function() {
                $(this).find('.row-num').text(i++);
            });

            $('#stats-count').text(visible + ' Player' + (visible !== 1 ? 's' : ''));
            updateBulkSelection();
        }
    });
    </script>
</body>
</html>
