<?php

require_once 'auth.php';
check_access('registration_admin');

require 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM registrations WHERE deleted_at IS NULL ORDER BY created_at DESC, id DESC");
$stmt->execute();
$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

$teamsStmt = $pdo->query("SELECT id, team_name FROM team_master ORDER BY team_name ASC");
$allTeamsList = $teamsStmt ? $teamsStmt->fetchAll(PDO::FETCH_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Players List - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="theme_css.php">
    <style>
        .dashboard-container {
            padding: 40px 24px;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.88);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-right: 1px solid rgba(2, 132, 199, 0.15);
            min-height: calc(100vh - 70px);
            padding: 24px 16px;
        }

        .sidebar .nav-link {
            color: #475569 !important;
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
            color: #0284c7;
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar .nav-link:hover {
            background: rgba(2, 132, 199, 0.08);
            color: #0284c7 !important;
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

        .table-container {
            background: #ffffff !important;
            border: 1px solid rgba(2, 132, 199, 0.18) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08) !important;
            padding: 24px;
            overflow-x: auto;
        }

        .table {
            color: #0f2a4a;
            margin-bottom: 0;
            border-collapse: separate;
            border-spacing: 0;
        }

        .table thead th {
            background-color: #28779b !important;
            color: #ffffff !important;
            font-family: 'Poppins', sans-serif;
            text-transform: uppercase;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 0.8px;
            padding: 14px 16px;
            border: none !important;
            vertical-align: middle;
        }

        .table thead th:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table thead th:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .table tbody td {
            background-color: #ffffff !important;
            color: #334155 !important;
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.92rem;
        }

        .table tbody tr:hover td {
            background-color: #f8fafc !important;
        }

        .btn-view {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
            color: #0284c7;
            font-size: 0.8rem;
            padding: 6px 14px;
            font-weight: 700;
            border-radius: 20px;
            transition: all 0.25s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .btn-view:hover {
            background: #0284c7;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        .btn-edit-player {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            font-size: 0.8rem;
            padding: 6px 14px;
            font-weight: 700;
            border-radius: 20px;
            transition: all 0.25s;
            display: inline-flex;
            align-items: center;
        }

        .btn-edit-player:hover {
            background: #16a34a;
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(22, 163, 74, 0.25);
        }

        /* Combined edit modal tab pills */
        #editModalTabs .nav-link {
            color: #64748b;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.25s;
        }
        #editModalTabs .nav-link.active {
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            border-color: transparent;
            color: #ffffff;
            font-weight: 700;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }
        #editModalTabs .nav-link:hover:not(.active) {
            background: #e2e8f0;
            color: #0f2a4a;
        }

        .avatar-mini {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            object-fit: cover;
            border: 2px solid rgba(2, 132, 199, 0.25);
            box-shadow: 0 2px 8px rgba(15, 42, 74, 0.08);
        }

        .badge-category {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            font-weight: 700;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
        }

        /* DataTables Custom Styles */
        .dataTables_wrapper .dataTables_length,
        .dataTables_wrapper .dataTables_filter,
        .dataTables_wrapper .dataTables_info,
        .dataTables_wrapper .dataTables_paginate {
            color: #475569 !important;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .dataTables_wrapper .dataTables_filter input,
        .dataTables_wrapper .dataTables_length select {
            background-color: #ffffff !important;
            border: 1.5px solid rgba(2, 132, 199, 0.25) !important;
            color: #0f2a4a !important;
            border-radius: 10px;
            padding: 6px 12px;
            font-weight: 500;
        }

        .page-link {
            background-color: #ffffff !important;
            border-color: rgba(2, 132, 199, 0.2) !important;
            color: #0284c7 !important;
            font-weight: 600;
            border-radius: 8px !important;
            margin: 0 3px;
        }

        .page-item.active .page-link {
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%) !important;
            border-color: transparent !important;
            color: #ffffff !important;
            box-shadow: 0 4px 10px rgba(2, 132, 199, 0.3);
        }

        /* Modal Styles */
        .modal-content.custom-modal {
            background: #ffffff !important;
            border: 1px solid rgba(2, 132, 199, 0.2) !important;
            color: #0f2a4a !important;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(15, 42, 74, 0.2);
        }
        
        .custom-modal .modal-header {
            border-bottom: 1px solid rgba(2, 132, 199, 0.12) !important;
            padding: 24px 32px;
            background: #f8fafc;
            border-top-left-radius: 24px;
            border-top-right-radius: 24px;
        }
        
        .custom-modal .modal-footer {
            border-top: 1px solid rgba(2, 132, 199, 0.12) !important;
            padding: 20px 32px;
            background: #f8fafc;
            border-bottom-left-radius: 24px;
            border-bottom-right-radius: 24px;
        }

        .custom-input {
            background-color: #ffffff !important;
            border: 1.5px solid rgba(2, 132, 199, 0.25) !important;
            color: #0f2a4a !important;
            font-size: 0.95rem !important;
            font-weight: 600 !important;
            border-radius: 12px;
            padding: 10px 16px;
            transition: all 0.25s ease;
        }
        
        .custom-input:focus {
            background-color: #ffffff !important;
            border-color: #0284c7 !important;
            color: #0f2a4a !important;
            box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.15) !important;
        }

        .custom-section-card {
            background: #f8fafc !important;
            border: 1px solid rgba(2, 132, 199, 0.15) !important;
            border-radius: 18px !important;
            padding: 24px !important;
            margin-bottom: 24px;
        }
        
        .custom-section-title {
            color: #0f2a4a !important;
            font-family: 'Poppins', sans-serif;
            font-size: 1.15rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            border-bottom: 1px solid rgba(2, 132, 199, 0.12);
            padding-bottom: 12px;
        }
        
        .custom-section-title i {
            color: #0284c7;
            margin-right: 10px;
            font-size: 1.1rem;
        }
        
        .stats-toggle-card {
            background: #e0f2fe !important;
            border: 1.5px solid #bae6fd !important;
            border-radius: 16px !important;
            padding: 18px 24px !important;
            cursor: pointer;
            transition: all 0.25s ease;
        }

        #statisticsSectionContainer {
            display: none;
            opacity: 0;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.6s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.5s ease-in-out;
        }
        
        #statisticsSectionContainer.expanded {
            max-height: 4000px;
            opacity: 1;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <nav class="navbar navbar-expand-lg sticky-top" style="background: rgba(255, 255, 255, 0.96); border-bottom: 1px solid rgba(2, 132, 199, 0.18); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="admin_dashboard.php">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height:48px; filter: drop-shadow(0 2px 8px rgba(2, 132, 199, 0.3));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                <div class="d-flex flex-column lh-1">
                    <span style="font-family: 'Poppins', sans-serif; font-weight: 800; font-size: 1.1rem; letter-spacing: 0.5px; color: #0f2a4a;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></span>
                    <span style="font-size: 0.72rem; letter-spacing: 1.5px; color: #0284c7; font-weight: 700; margin-top: 3px;">REGISTRATION ADMIN</span>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
                <i class="fas fa-bars fs-4" style="color: #0284c7;"></i>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarContent">
                <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                    <div class="d-none d-sm-flex align-items-center px-2 py-1 rounded-pill" style="background: rgba(2, 132, 199, 0.08); border: 1px solid rgba(2, 132, 199, 0.2);">
                        <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 22px; width: 22px; object-fit: contain;">
                    </div>
                    <span class="text-secondary me-2 small">Welcome, <strong style="color: #0f2a4a;"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></strong></span>
                    <a href="admin_logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill fw-semibold">
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
                        <a class="nav-link active" href="admin_players.php">
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
                        <a class="nav-link" href="admin_theme_settings.php">
                            <i class="fas fa-paint-brush"></i> Theme Settings
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 dashboard-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <h2 class="fw-bold mb-0" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">Registered <span style="color: #0284c7;">Players</span></h2>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <button type="button" class="btn btn-primary rounded-pill px-4 py-2 fw-semibold" data-bs-toggle="modal" data-bs-target="#addPlayerModal" style="background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%); border: none; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);">
                            <i class="fas fa-plus me-2"></i>Add Player
                        </button>
                        <select id="categoryFilter" class="form-select w-auto"
                            style="background-color: #ffffff; border: 1.5px solid rgba(2, 132, 199, 0.25); color: #0f2a4a; border-radius: 12px; font-weight: 600; padding: 8px 16px;">
                            <option value="">All Categories</option>
                            <?php
                            if (!empty($globalCategories)) {
                                foreach ($globalCategories as $cat) {
                                    echo "<option value='" . htmlspecialchars($cat) . "'>" . htmlspecialchars($cat) . "</option>";
                                }
                            }
                            ?>
                        </select>
                        <span class="badge px-3 py-2 text-nowrap" style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; border-radius: 20px; font-weight: 700; font-size: 0.9rem;">
                            <?php echo count($players); ?> total
                        </span>
                    </div>
                </div>

                <div class="table-container">
                    <table class="table" id="playersTable">
                        <thead>
                            <tr>
                                <th>Photo</th>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Emp ID</th>
                                <th>Department</th>
                                <th>Mobile</th>
                                <th>Category</th>
                                <th>Role</th>
                                <th>Jersey #</th>
                                <th>Action</th>
                                <th>Registered By</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($players as $player): ?>
                                <tr class="player-row">
                                    <td>
                                        <?php if ($player['profile_pic']): ?>
                                            <img src="<?php echo $player['profile_pic']; ?>" class="avatar-mini" onerror="this.onerror=null;this.src='assets/image-not-found.png';">
                                        <?php else: ?>
                                            <div class="avatar-mini d-flex align-items-center justify-content-center bg-light text-secondary">
                                                <i class="fas fa-user"></i>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold" style="color: #0284c7;">
                                        <?php echo $player['player_id']; ?>
                                    </td>
                                    <td class="fw-semibold text-dark">
                                        <?php echo $player['full_name']; ?>
                                    </td>
                                    <td class="text-secondary fw-medium">
                                        <?php echo $player['emp_id'] ?? '-'; ?>
                                    </td>
                                    <td class="text-secondary fw-medium">
                                        <?php echo $player['department'] ?? '-'; ?>
                                    </td>
                                    <td class="text-secondary fw-medium">
                                        <?php echo $player['mobile']; ?>
                                    </td>
                                    <td><span class="badge badge-category">
                                            <?php echo $player['category']; ?>
                                        </span></td>
                                    <td>
                                        <?php echo $player['role']; ?>
                                    </td>
                                    <td class="text-center fw-bold text-secondary">
                                        <?php echo $player['jersey_number']; ?>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-2">
                                            <a href="admin_player_view.php?id=<?php echo $player['id']; ?>"
                                                class="btn btn-view">
                                                <i class="fas fa-eye me-1"></i> View
                                            </a>
                                            <button type="button" class="btn btn-edit-player"
                                                data-id="<?php echo $player['id']; ?>"
                                                data-player='<?php echo htmlspecialchars(json_encode($player), ENT_QUOTES, "UTF-8"); ?>'>
                                                <i class="fas fa-edit me-1"></i> Edit
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if (!empty($player['registered_by'])): ?>
                                            <span style="display:inline-flex;align-items:center;gap:6px;">
                                                <i class="fas fa-user-shield" style="color:#0284c7;font-size:0.8rem;"></i>
                                                <span style="color:#0f2a4a;font-weight:600;font-size:0.85rem;"><?php echo htmlspecialchars($player['registered_by']); ?></span>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted" style="font-size:0.8rem;">—</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($players)): ?>
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">No players registered yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
        <div id="splToast" class="toast align-items-center bg-white text-dark border-0 shadow-lg" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="splToastBody"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- ======= COMBINED EDIT MODAL (Player Details + Stats) ======= -->
    <div class="modal fade" id="editPlayerModal" tabindex="-1" aria-labelledby="editPlayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <form id="editPlayerForm" enctype="multipart/form-data" class="modal-content custom-modal">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="editPlayerModalLabel" style="font-family: 'Poppins', sans-serif; color: #0f2a4a;">
                        <i class="fas fa-edit me-2" style="color: #0284c7;"></i>Edit — <span id="editModalPlayerName" style="color: #0284c7;"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body p-0">
                    <div id="editPlayerErrorAlert" class="alert alert-danger d-none m-3 mb-0" role="alert"></div>
                    <div id="editPlayerSuccessAlert" class="alert alert-success d-none m-3 mb-0" role="alert"></div>

                    <!-- TAB NAVIGATION -->
                    <ul class="nav nav-pills px-4 pt-3 pb-2 gap-2" id="editModalTabs">
                        <li class="nav-item">
                            <button class="nav-link active" id="tab-details-btn" type="button" data-tab="tab-details"
                                style="font-family:'Cinzel',serif;font-size:0.8rem;letter-spacing:1px;">
                                <i class="fas fa-id-card me-1"></i> Player Details
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="tab-stats-btn" type="button" data-tab="tab-stats"
                                style="font-family:'Cinzel',serif;font-size:0.8rem;letter-spacing:1px;">
                                <i class="fas fa-chart-bar me-1"></i> Player Stats
                                <span id="statsLoadingSpinner" class="spinner-border spinner-border-sm ms-1 d-none" style="width:.8rem;height:.8rem;"></span>
                            </button>
                        </li>
                    </ul>
                    <div style="height:1px;background:rgba(58, 189, 217, 0.15);margin:0 1rem;"></div>

                    <div class="p-4">
                        <!-- ── TAB 1: PLAYER DETAILS ── -->
                        <div id="tab-details">
                            <div class="custom-section-card">
                                <div class="custom-section-title"><i class="fas fa-id-card"></i>Personal & Contact</div>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Salutation</label>
                                        <select name="salutation" id="edit_salutation" class="form-select custom-input">
                                            <option value="Mr.">Mr.</option><option value="Ms.">Ms.</option><option value="Dr.">Dr.</option>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted">Player Name *</label>
                                        <input type="text" name="full_name" id="edit_full_name" class="form-control custom-input" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label small text-muted">Mobile Number *</label>
                                        <input type="text" name="mobile" id="edit_mobile" class="form-control custom-input" required maxlength="15">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Email</label>
                                        <input type="email" name="email" id="edit_email" class="form-control custom-input">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Date of Birth</label>
                                        <input type="date" name="dob" id="edit_dob" class="form-control custom-input">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Category *</label>
                                        <select name="category" id="edit_category" class="form-select custom-input" required>
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
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Role *</label>
                                        <select name="role" id="edit_role" class="form-select custom-input" required>
                                            <option value="All-rounder">All-rounder</option><option value="Batsman">Batsman</option>
                                            <option value="Bowler">Bowler</option><option value="Wicketkeeper">Wicketkeeper</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Experience</label>
                                        <select name="experience" id="edit_experience" class="form-select custom-input">
                                            <option value="Professional">Professional</option><option value="Amateur">Amateur</option><option value="Beginner">Beginner</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Batting Style</label>
                                        <select name="batting_type" id="edit_batting_type" class="form-select custom-input">
                                            <option value="Right Hand">Right Hand</option><option value="Left Hand">Left Hand</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Bowling Style</label>
                                        <select name="bowling_type" id="edit_bowling_type" class="form-select custom-input">
                                            <option value="Right Arm Fast">Right Arm Fast</option><option value="Right Arm Medium">Right Arm Medium</option>
                                            <option value="Right Arm Spin">Right Arm Spin</option><option value="Left Arm Fast">Left Arm Fast</option>
                                            <option value="Left Arm Medium">Left Arm Medium</option><option value="Left Arm Spin">Left Arm Spin</option>
                                            <option value="None">None</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Jersey Number</label>
                                        <input type="number" name="jersey_number" id="edit_jersey_number" class="form-control custom-input" min="1" max="99">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Jersey Nickname</label>
                                        <input type="text" name="jersey_nickname" id="edit_jersey_nickname" class="form-control custom-input">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Jersey Size</label>
                                        <select name="jersey_size" id="edit_jersey_size" class="form-select custom-input">
                                            <option value="S">S</option><option value="M">M</option><option value="L">L</option><option value="XL">XL</option><option value="XXL">XXL</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Track Pant Size</label>
                                        <input type="number" name="track_pant_size" id="edit_track_pant_size" class="form-control custom-input" placeholder="e.g. 32">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Change Photo (optional)</label>
                                        <input type="file" name="profile_pic" id="edit_profile_pic" class="form-control custom-input" accept="image/*">
                                        <div id="edit_current_photo_preview" class="mt-2"></div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Address</label>
                                        <textarea name="address" id="edit_address" class="form-control custom-input" rows="3"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 2: PLAYER STATS ── -->
                        <div id="tab-stats" style="display:none;">
                            <div id="statsLoadingMsg" class="text-center py-4 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span> Loading stats...
                            </div>
                            <div id="statsFormContent" style="display:none;">
                                <!-- BATTING -->
                                <div class="custom-section-card">
                                    <div class="custom-section-title"><i class="fas fa-cricket"></i>Batting Statistics</div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Matches</label><input type="number" name="matches_played" id="es_matches" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Innings</label><input type="number" name="innings" id="es_innings" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Runs Scored</label><input type="number" name="runs_scored" id="es_runs" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Highest Score</label><input type="number" name="highest_score" id="es_highest" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Batting Avg</label><input type="number" step="0.01" name="batting_avg" id="es_batavg" class="form-control custom-input" min="0" value="0.00"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Strike Rate</label><input type="number" step="0.01" name="strike_rate" id="es_sr" class="form-control custom-input" min="0" value="0.00"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Fifties (50s)</label><input type="number" name="fifties" id="es_fifties" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Hundreds (100s)</label><input type="number" name="hundreds" id="es_hundreds" class="form-control custom-input" min="0" value="0"></div>
                                    </div>
                                </div>
                                <!-- BOWLING -->
                                <div class="custom-section-card">
                                    <div class="custom-section-title"><i class="fas fa-baseball-ball"></i>Bowling Statistics</div>
                                    <div class="row g-3">
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Wickets</label><input type="number" name="wickets" id="es_wickets" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Bowling Avg</label><input type="number" step="0.01" name="bowling_avg" id="es_bowlavg" class="form-control custom-input" min="0" value="0.00"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Economy</label><input type="number" step="0.01" name="economy" id="es_economy" class="form-control custom-input" min="0" value="0.00"></div>
                                        <div class="col-md-3 col-6"><label class="form-label small text-muted">Best Bowling</label><input type="text" name="best_bowling" id="es_bestbowl" class="form-control custom-input" placeholder="e.g. 3/18"></div>
                                    </div>
                                </div>
                                <!-- FIELDING -->
                                <div class="custom-section-card">
                                    <div class="custom-section-title"><i class="fas fa-hands"></i>Fielding Statistics</div>
                                    <div class="row g-3">
                                        <div class="col-md-6"><label class="form-label small text-muted">Catches</label><input type="number" name="catches" id="es_catches" class="form-control custom-input" min="0" value="0"></div>
                                        <div class="col-md-6"><label class="form-label small text-muted">Stumpings</label><input type="number" name="stumpings" id="es_stumpings" class="form-control custom-input" min="0" value="0"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveEditPlayerBtn" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%); border: none; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);">
                        <i class="fas fa-save me-2"></i>Save All Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
    <!-- ======= END COMBINED EDIT MODAL ======= -->


    <!-- Single Add Player Modal (Section 1: Details & Section 2: Stats) -->
    <div class="modal fade" id="addPlayerModal" tabindex="-1" aria-labelledby="addPlayerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
            <form id="addPlayerForm" enctype="multipart/form-data" class="modal-content custom-modal">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="addPlayerModalLabel" style="font-family: 'Poppins', sans-serif; color: #0f2a4a;">
                        <i class="fas fa-user-plus me-2" style="color: #0284c7;"></i>Add <span style="color: #0284c7;">Player</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                        <!-- Alert Banners -->
                        <div id="addPlayerErrorAlert" class="alert alert-danger d-none" role="alert"></div>
                        <div id="addPlayerSuccessAlert" class="alert alert-success d-none" role="alert"></div>

                        <!-- SECTION 1: Player Registration Details -->
                        <div class="custom-section-card">
                            <div class="custom-section-title">
                                <i class="fas fa-id-card"></i>Section 1: Player Details
                            </div>
                            <div class="row g-3">
                                <div class="col-md-2">
                                    <label class="form-label small text-muted">Salutation</label>
                                    <select name="salutation" class="form-select custom-input">
                                        <option value="Mr.">Mr.</option>
                                        <option value="Ms.">Ms.</option>
                                        <option value="Dr.">Dr.</option>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small text-muted">Player Name *</label>
                                    <input type="text" name="full_name" class="form-control custom-input" required placeholder="Full name">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label small text-muted">Mobile Number *</label>
                                    <input type="text" name="mobile" class="form-control custom-input" required placeholder="10-digit mobile number" maxlength="15">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Email</label>
                                    <input type="email" name="email" class="form-control custom-input" placeholder="player@example.com">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Date of Birth</label>
                                    <input type="date" name="dob" class="form-control custom-input">
                                </div>


                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Category *</label>
                                    <select name="category" class="form-select custom-input" required>
                                        <option value="">Select Category *</option>
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
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Role *</label>
                                    <select name="role" class="form-select custom-input" required>
                                        <option value="">Select Role *</option>
                                        <option value="All-rounder">All-rounder</option>
                                        <option value="Batsman">Batsman</option>
                                        <option value="Bowler">Bowler</option>
                                        <option value="Wicketkeeper">Wicketkeeper</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Experience</label>
                                    <select name="experience" class="form-select custom-input">
                                        <option value="Professional">Professional</option>
                                        <option value="Amateur">Amateur</option>
                                        <option value="Beginner">Beginner</option>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Batting Style</label>
                                    <select name="batting_type" class="form-select custom-input">
                                        <option value="Right Hand">Right Hand</option>
                                        <option value="Left Hand">Left Hand</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Bowling Style</label>
                                    <select name="bowling_type" class="form-select custom-input">
                                        <option value="Right Arm Fast">Right Arm Fast</option>
                                        <option value="Right Arm Medium">Right Arm Medium</option>
                                        <option value="Right Arm Spin">Right Arm Spin</option>
                                        <option value="Left Arm Fast">Left Arm Fast</option>
                                        <option value="Left Arm Medium">Left Arm Medium</option>
                                        <option value="Left Arm Spin">Left Arm Spin</option>
                                        <option value="None">None</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Jersey Number</label>
                                    <input type="number" name="jersey_number" class="form-control custom-input" min="1" max="99" placeholder="1 - 99">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Jersey Nickname</label>
                                    <input type="text" name="jersey_nickname" class="form-control custom-input" placeholder="Name on jersey">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Jersey Size</label>
                                    <select name="jersey_size" class="form-select custom-input">
                                        <option value="M">M</option>
                                        <option value="S">S</option>
                                        <option value="L">L</option>
                                        <option value="XL">XL</option>
                                        <option value="XXL">XXL</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label small text-muted">Track Pant Size</label>
                                    <input type="number" name="track_pant_size" class="form-control custom-input" placeholder="e.g. 32">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Photo Upload</label>
                                    <input type="file" name="profile_pic" class="form-control custom-input" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small text-muted">Address</label>
                                    <textarea name="address" class="form-control custom-input" rows="1" placeholder="Optional full address"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- TOGGLE CHECKBOX FOR SECTION 2 -->
                        <label for="addStatsNowCheckbox" class="stats-toggle-card mb-4 d-flex justify-content-between align-items-center w-100 m-0 text-decoration-none">
                            <div class="d-flex flex-column">
                                <span class="fw-bold fs-5" style="color: #0f2a4a; font-family: 'Poppins', sans-serif;">
                                    <i class="fas fa-chart-line me-2" style="color: #0284c7;"></i> Add Player Statistics
                                </span>
                                <span class="small mt-1" style="font-size: 0.85rem; color: #64748b;">
                                    Enable this to log match performance details immediately along with registration.
                                </span>
                            </div>
                            <div class="form-check form-switch m-0 ms-3">
                                <input class="form-check-input" type="checkbox" role="switch" id="addStatsNowCheckbox" name="add_stats_now" value="1" style="width: 3.2em; height: 1.7em; cursor: pointer;">
                            </div>
                        </label>

                        <!-- SECTION 2: Player Statistics -->
                        <div id="statisticsSectionContainer">
                            <div class="custom-section-card">
                                <div class="custom-section-title">
                                    <i class="fas fa-calendar-alt"></i>Match Details
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Match</label>
                                        <input type="text" name="match_name" class="form-control custom-input" placeholder="e.g. Match 1 - League Stage">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Opponent</label>
                                        <input type="text" name="opponent" class="form-control custom-input" placeholder="e.g. Royal Tigers">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Tournament</label>
                                        <input type="text" name="tournament" class="form-control custom-input" value="PPL" placeholder="Tournament Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Ground</label>
                                        <input type="text" name="ground" class="form-control custom-input" placeholder="e.g. Stadium / Ground Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small text-muted">Match Date</label>
                                        <input type="date" name="match_date" class="form-control custom-input">
                                    </div>
                                </div>
                            </div>

                            <!-- Batting Statistics -->
                            <div class="custom-section-card">
                                <div class="custom-section-title">
                                    <i class="fas fa-baseball-ball"></i>Batting Statistics
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Runs</label>
                                        <input type="number" name="runs" id="statRuns" class="form-control custom-input stat-calc-bat" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Balls</label>
                                        <input type="number" name="balls" id="statBalls" class="form-control custom-input stat-calc-bat" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Fours (4s)</label>
                                        <input type="number" name="fours" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Sixes (6s)</label>
                                        <input type="number" name="sixes" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Strike Rate</label>
                                        <input type="number" step="0.01" name="strike_rate" id="statStrikeRate" class="form-control custom-input" min="0" value="0.00">
                                    </div>
                                </div>
                            </div>

                            <!-- Bowling Statistics -->
                            <div class="custom-section-card">
                                <div class="custom-section-title">
                                    <i class="fas fa-bowling-ball"></i>Bowling Statistics
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Overs</label>
                                        <input type="number" step="0.1" name="overs" id="statOvers" class="form-control custom-input stat-calc-bowl" min="0" value="0.0">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small text-muted">Runs Conceded</label>
                                        <input type="number" name="runs_conceded" id="statRunsConceded" class="form-control custom-input stat-calc-bowl" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Wickets</label>
                                        <input type="number" name="wickets" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label small text-muted">Economy</label>
                                        <input type="number" step="0.01" name="economy" id="statEconomy" class="form-control custom-input" min="0" value="0.00">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-muted">Maidens</label>
                                        <input type="number" name="maidens" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-muted">No Balls</label>
                                        <input type="number" name="no_balls" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-1">
                                        <label class="form-label small text-muted">Wides</label>
                                        <input type="number" name="wides" class="form-control custom-input" min="0" value="0">
                                    </div>
                                </div>
                            </div>

                            <!-- Fielding Statistics -->
                            <div class="custom-section-card">
                                <div class="custom-section-title">
                                    <i class="fas fa-hand-paper"></i>Fielding Statistics
                                </div>
                                <div class="row g-3">
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Catches</label>
                                        <input type="number" name="catches" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Run Outs</label>
                                        <input type="number" name="run_outs" class="form-control custom-input" min="0" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label small text-muted">Stumpings</label>
                                        <input type="number" name="stumpings" class="form-control custom-input" min="0" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="savePlayerBtn" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%); border: none; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);">
                            <i class="fas fa-save me-2"></i>Save Player
                        </button>
                    </div>
            </form>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function () {
            var table = $('#playersTable').DataTable({
                "pageLength": 25,
                "ordering": true,
                "order": [], // Retain server-side sorting (created_at DESC)
                "language": {
                    "search": "Search Players:",
                    "lengthMenu": "Display _MENU_ per page"
                }
            });

            // Handle URL query parameters for role filter
            const urlParams = new URLSearchParams(window.location.search);
            const roleParam = urlParams.get('role');
            
            if (roleParam) {
                // Clear any saved category if we are filtering by role from dashboard
                sessionStorage.removeItem('spl_category_filter');
                $('#categoryFilter').val('');
                
                // Perform smart search on Role column (index 5)
                table.column(5).search(roleParam).draw();
            } else {
                // Restore category filter from sessionStorage
                var savedCategory = sessionStorage.getItem('spl_category_filter');
                if (savedCategory) {
                    $('#categoryFilter').val(savedCategory);
                    var val = $.fn.dataTable.util.escapeRegex(savedCategory);
                    table.column(4).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
                }
            }

            // Handle Category Filter
            $('#categoryFilter').on('change', function () {
                var selectedValue = $(this).val();
                sessionStorage.setItem('spl_category_filter', selectedValue);
                var val = $.fn.dataTable.util.escapeRegex(selectedValue);
                table.column(4).search(val ? '^\\s*' + val + '\\s*$' : '', true, false).draw();
            });

            // Expand/Collapse Statistics Section
            $('#addStatsNowCheckbox').on('change', function () {
                var container = $('#statisticsSectionContainer');
                if ($(this).is(':checked')) {
                    container.css('display', 'block');
                    setTimeout(function () {
                        container.addClass('expanded');
                    }, 10);
                } else {
                    container.removeClass('expanded');
                    setTimeout(function () {
                        if (!$('#addStatsNowCheckbox').is(':checked')) {
                            container.css('display', 'none');
                        }
                    }, 500);
                }
            });

            // Auto-calculate Batting Strike Rate
            $('.stat-calc-bat').on('input', function () {
                var r = parseFloat($('#statRuns').val()) || 0;
                var b = parseFloat($('#statBalls').val()) || 0;
                if (b > 0) {
                    var sr = ((r / b) * 100).toFixed(2);
                    $('#statStrikeRate').val(sr);
                }
            });

            // Auto-calculate Bowling Economy
            $('.stat-calc-bowl').on('input', function () {
                var overs = parseFloat($('#statOvers').val()) || 0;
                var rc = parseFloat($('#statRunsConceded').val()) || 0;
                if (overs > 0) {
                    var whole = Math.floor(overs);
                    var frac = Math.round((overs - whole) * 10);
                    var totalBalls = (whole * 6) + frac;
                    if (totalBalls > 0) {
                        var eco = (rc / (totalBalls / 6)).toFixed(2);
                        $('#statEconomy').val(eco);
                    }
                }
            });

            // Reusable Toast Notification Helper
            window.showToast = function (msg, isSuccess) {
                var toastEl = $('#splToast');
                var toastBody = $('#splToastBody');
                toastEl.removeClass('border-success border-danger bg-dark bg-success bg-danger');
                if (isSuccess) {
                    toastEl.addClass('border-success bg-dark');
                    toastBody.html('<i class="fas fa-check-circle text-success fs-5"></i> <span class="fw-bold">' + msg + '</span>');
                } else {
                    toastEl.addClass('border-danger bg-dark');
                    toastBody.html('<i class="fas fa-exclamation-circle text-danger fs-5"></i> <span class="fw-bold">' + msg + '</span>');
                }
                var toast = new bootstrap.Toast(toastEl[0], { delay: 4000 });
                toast.show();
            };

            // Reset modal on close
            $('#addPlayerModal').on('hidden.bs.modal', function () {
                $('#addPlayerForm')[0].reset();
                $('#addStatsNowCheckbox').prop('checked', false).trigger('change');
                $('#addPlayerErrorAlert, #addPlayerSuccessAlert').addClass('d-none').text('');
                $('#savePlayerBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save Player');
            });

            // Handle Single Form Submit for Registration + Statistics
            $('#addPlayerForm').on('submit', function (e) {
                e.preventDefault();
                var form = this;
                var submitBtn = $('#savePlayerBtn');
                var originalHtml = '<i class="fas fa-save me-2"></i>Save Player';

                $('#addPlayerErrorAlert, #addPlayerSuccessAlert').addClass('d-none').text('');

                var formData = new FormData(form);

                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

                $.ajax({
                    url: 'api_add_player.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (res) {
                        if (res.status === 'success' || res.success === true) {
                            var successMsg = res.message || '✓ Player Registered Successfully.';
                            $('#addPlayerSuccessAlert').removeClass('d-none').text(successMsg);
                            showToast(successMsg, true);

                            setTimeout(function () {
                                $('#addPlayerModal').modal('hide');
                                window.location.reload();
                            }, 1500);
                        } else {
                            var errorMsg = res.message || res.error || 'Failed to save player.';
                            $('#addPlayerErrorAlert').removeClass('d-none').text(errorMsg);
                            showToast(errorMsg, false);
                            submitBtn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function (xhr) {
                        var errText = 'Failed to save player.';
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.message || res.error) {
                                errText = res.message || res.error;
                            }
                        } catch (ex) {}
                        $('#addPlayerErrorAlert').removeClass('d-none').text(errText);
                        showToast(errText, false);
                        submitBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

            // ===== COMBINED EDIT MODAL (Details + Stats) =====

            var editStatsLoaded = false; // Track if stats have been fetched already

            // --- Tab switching ---
            $('#editModalTabs').on('click', 'button', function () {
                var tab = $(this).data('tab');
                // Update active state
                $('#editModalTabs button').removeClass('active');
                $(this).addClass('active');
                // Show correct tab panel
                $('#tab-details, #tab-stats').hide();
                $('#' + tab).show();

                // Fetch stats on first switch to Stats tab
                if (tab === 'tab-stats' && !editStatsLoaded) {
                    fetchStatsForEdit();
                }
            });

            function fetchStatsForEdit() {
                var regId = $('#edit_id').val();
                if (!regId) return;
                $('#statsLoadingSpinner').removeClass('d-none');
                $('#statsLoadingMsg').show();
                $('#statsFormContent').hide();

                $.ajax({
                    url: 'api_get_player_full.php',
                    type: 'GET',
                    data: { id: regId },
                    dataType: 'json',
                    success: function (res) {
                        $('#statsLoadingSpinner').addClass('d-none');
                        if (res.success) {
                            var s = res.stats || {};
                            $('#es_matches').val(s.matches_played || 0);
                            $('#es_innings').val(s.innings || 0);
                            $('#es_runs').val(s.runs_scored || 0);
                            $('#es_highest').val(s.highest_score || 0);
                            $('#es_batavg').val(parseFloat(s.batting_avg || 0).toFixed(2));
                            $('#es_sr').val(parseFloat(s.strike_rate || 0).toFixed(2));
                            $('#es_fifties').val(s.fifties || 0);
                            $('#es_hundreds').val(s.hundreds || 0);
                            $('#es_wickets').val(s.wickets || 0);
                            $('#es_bowlavg').val(parseFloat(s.bowling_avg || 0).toFixed(2));
                            $('#es_economy').val(parseFloat(s.economy || 0).toFixed(2));
                            $('#es_bestbowl').val(s.best_bowling || '');
                            $('#es_catches').val(s.catches || 0);
                            $('#es_stumpings').val(s.stumpings || 0);
                            editStatsLoaded = true;
                            $('#statsLoadingMsg').hide();
                            $('#statsFormContent').show();
                        } else {
                            $('#statsLoadingMsg').html('<span class="text-warning"><i class="fas fa-info-circle me-1"></i>' + (res.message || 'No stats found. You can enter new ones below.') + '</span>');
                            $('#statsFormContent').show();
                        }
                    },
                    error: function () {
                        $('#statsLoadingSpinner').addClass('d-none');
                        $('#statsLoadingMsg').html('<span class="text-danger"><i class="fas fa-exclamation-circle me-1"></i>Failed to load stats. You can still enter values below.</span>');
                        $('#statsFormContent').show();
                    }
                });
            }

            // --- Open Edit Modal and populate player details ---
            $(document).on('click', '.btn-edit-player', function () {
                var player = $(this).data('player');
                if (typeof player === 'string') {
                    try { player = JSON.parse(player); } catch(e) { return; }
                }

                // Reset tab state
                editStatsLoaded = false;
                $('#editModalTabs button').removeClass('active');
                $('#tab-details-btn').addClass('active');
                $('#tab-details').show();
                $('#tab-stats').hide();
                $('#statsLoadingMsg').show();
                $('#statsFormContent').hide();
                $('#statsLoadingSpinner').addClass('d-none');

                // Populate hidden id & title
                $('#edit_id').val(player.id);
                $('#editModalPlayerName').text(player.full_name || '');

                // Text/number fields
                $('#edit_full_name').val(player.full_name || '');
                $('#edit_mobile').val(player.mobile || '');
                $('#edit_email').val(player.email || '');
                $('#edit_dob').val(player.dob || '');
                $('#edit_jersey_number').val(player.jersey_number || '');
                $('#edit_jersey_nickname').val(player.jersey_nickname || '');
                $('#edit_track_pant_size').val(player.track_pant_size || '');
                $('#edit_address').val(player.address || '');

                // Select fields
                $('#edit_salutation').val(player.salutation || 'Mr.');
                $('#edit_category').val(player.category || '');
                $('#edit_role').val(player.role || '');
                $('#edit_experience').val(player.experience || 'Professional');
                $('#edit_batting_type').val(player.batting_type || 'Right Hand');
                $('#edit_bowling_type').val(player.bowling_type || 'Right Arm Fast');
                $('#edit_jersey_size').val(player.jersey_size || 'M');

                // Current photo preview
                var previewHtml = '';
                if (player.profile_pic) {
                    previewHtml = '<div class="d-flex align-items-center gap-2 mt-1">' +
                        '<img src="' + player.profile_pic + '" style="width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid var(--gold);" onerror="this.style.display=\'none\'">' +
                        '<span class="small text-muted">Current photo — upload a new one to replace</span>' +
                        '</div>';
                }
                $('#edit_current_photo_preview').html(previewHtml);

                // Clear alerts and re-enable button
                $('#editPlayerErrorAlert, #editPlayerSuccessAlert').addClass('d-none').text('');
                $('#saveEditPlayerBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save All Changes');
                $('#edit_profile_pic').val('');

                // Show modal
                var editModal = new bootstrap.Modal(document.getElementById('editPlayerModal'));
                editModal.show();
            });

            // --- Reset on close ---
            $('#editPlayerModal').on('hidden.bs.modal', function () {
                $('#editPlayerForm')[0].reset();
                $('#editPlayerErrorAlert, #editPlayerSuccessAlert').addClass('d-none').text('');
                $('#edit_current_photo_preview').html('');
                $('#saveEditPlayerBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i>Save All Changes');
                editStatsLoaded = false;
                $('#statsFormContent').hide();
                $('#statsLoadingMsg').show();
            });

            // --- Submit combined form ---
            $('#editPlayerForm').on('submit', function (e) {
                e.preventDefault();
                var submitBtn = $('#saveEditPlayerBtn');
                var originalHtml = '<i class="fas fa-save me-2"></i>Save All Changes';

                $('#editPlayerErrorAlert, #editPlayerSuccessAlert').addClass('d-none').text('');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

                var formData = new FormData(this);

                $.ajax({
                    url: 'api_edit_combined.php',
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    dataType: 'json',
                    success: function (res) {
                        if (res.success === true) {
                            var msg = res.message || '✓ Player updated successfully.';
                            $('#editPlayerSuccessAlert').removeClass('d-none').text(msg);
                            showToast(msg, true);
                            setTimeout(function () {
                                $('#editPlayerModal').modal('hide');
                                window.location.reload();
                            }, 1500);
                        } else {
                            var err = res.message || 'Failed to update player.';
                            $('#editPlayerErrorAlert').removeClass('d-none').text(err);
                            showToast(err, false);
                            submitBtn.prop('disabled', false).html(originalHtml);
                        }
                    },
                    error: function (xhr) {
                        var errText = 'Failed to update player.';
                        try {
                            var r = JSON.parse(xhr.responseText);
                            if (r.message || r.error) errText = r.message || r.error;
                        } catch (ex) {}
                        $('#editPlayerErrorAlert').removeClass('d-none').text(errText);
                        showToast(errText, false);
                        submitBtn.prop('disabled', false).html(originalHtml);
                    }
                });
            });

        });
    </script>
</body>

</html>