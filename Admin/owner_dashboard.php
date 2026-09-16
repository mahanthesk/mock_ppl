<?php
require_once '../auth.php';
check_access('team_owner');

require_once 'db_connection.php';

if (!isset($_SESSION['owner_team_id'])) {
    header('Location: owner_login.php');
    exit;
}

$teamId = $_SESSION['owner_team_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM team_master WHERE id = ?");
    $stmt->execute([$teamId]);
    $team = $stmt->fetch();

    if (!$team) {
        die("Team not found.");
    }

    $totalPointsPerTeam = (isset($team['total_purse']) && floatval($team['total_purse']) > 0) ? floatval($team['total_purse']) : 10000000;

    // spent on regular players
    $playerStmt = $pdo->prepare("SELECT * FROM registrations WHERE team_id = ? AND booking_status = 1 ORDER BY bid_points DESC, player_id ASC");
    $playerStmt->execute([$teamId]);
    $players = $playerStmt->fetchAll();

    $spent = 0;
    foreach ($players as $p) $spent += floatval($p['bid_points']);

    // spent on icons
    $iconStmt = $pdo->prepare("SELECT * FROM icon_players WHERE team_id = ? AND booking_status = 1");
    $iconStmt->execute([$teamId]);
    $teamIcons = $iconStmt->fetchAll();
    foreach ($teamIcons as $ic) $spent += floatval($ic['bid_points']);

    $remaining = ($team['remaining_purse'] !== null) ? floatval($team['remaining_purse']) : max(0, $totalPointsPerTeam - $spent);
    $totalMembers = count($players);

    $owners = json_decode($team['owner'], true);
    if (!is_array($owners)) $owners = [$team['owner']];
    $owner_imgs = json_decode($team['owner_img'], true);
    if (!is_array($owner_imgs)) $owner_imgs = [$team['owner_img']];

    $ic_players = json_decode($team['ic_player'], true);
    if (!is_array($ic_players)) $ic_players = [$team['ic_player']];
    $ic_player_imgs = json_decode($team['ic_player_img'], true);
    if (!is_array($ic_player_imgs)) $ic_player_imgs = [$team['ic_player_img']];

    // Split team name for two-tone typography matching Mockup 2
    $tName = trim($team['team_name']);
    $tParts = explode(' ', $tName);
    if (count($tParts) > 1) {
        $lastWord = array_pop($tParts);
        $firstPart = implode(' ', $tParts);
    } else {
        $firstPart = $tName;
        $lastWord = '';
    }

    $iconDisplayName = $teamIcons ? implode(', ', array_column($teamIcons, 'name')) : (!empty(array_filter($ic_players)) ? implode(', ', array_filter($ic_players)) : "MYSTERY ICON");

    // Fetch all teams summary data for All Teams Dashboard
    $allTeamsStmt = $pdo->query("
        SELECT t.id, t.team_name, t.team_logo, t.owner, t.owner_img, t.total_purse, t.max_strength,
               (SELECT COALESCE(SUM(bid_points), 0) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS reg_points,
               (SELECT COUNT(id) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS total_players
        FROM team_master t
        ORDER BY t.team_name ASC
    ");
    $allTeams = $allTeamsStmt->fetchAll();

    // Fetch all icon players grouped by team_id
    $allIconsStmt = $pdo->query("SELECT * FROM icon_players WHERE booking_status = 1");
    $allIconsRaw = $allIconsStmt->fetchAll();
    $allIconsByTeam = [];
    foreach ($allIconsRaw as $ir) {
        $allIconsByTeam[$ir['team_id']][] = $ir;
    }

    // Fetch all sold regular players grouped by team_id
    $allSoldStmt = $pdo->query("
        SELECT * FROM registrations WHERE booking_status = 1 ORDER BY bid_points DESC, player_id ASC
    ");
    $allSoldRaw = $allSoldStmt->fetchAll();
    $allSoldByTeam = [];
    foreach ($allSoldRaw as $sr) {
        $allSoldByTeam[$sr['team_id']][] = $sr;
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - <?php echo htmlspecialchars($team['team_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
        body {
            background-image: url('assets/ppl-cricket-bg.png') !important;
            background-size: cover !important;
            background-position: center center !important;
            background-attachment: fixed !important;
            background-color: #f8fafc !important;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .owner-navbar {
            background: rgba(255, 255, 255, 0.96) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-bottom: 1px solid #e2e8f0 !important;
            box-shadow: 0 4px 20px -2px rgba(15, 23, 42, 0.05);
            z-index: 1000;
        }

        /* Toggle Tab Navigation */
        .view-toggle-container {
            background: rgba(255, 255, 255, 0.96);
            padding: 6px;
            border-radius: 50px;
            border: 1.5px solid #e2e8f0;
            box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);
            display: inline-flex;
            gap: 6px;
        }

        .btn-tab-toggle {
            padding: 10px 24px;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            font-size: 0.88rem;
            border: none;
            background: transparent;
            color: #64748b;
            transition: all 0.25s ease;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-tab-toggle.active {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        .btn-tab-toggle:not(.active):hover {
            background: #f1f5f9;
            color: #0f2a4a;
        }

        /* Hero Team Card (Mockup 2) */
        .hero-team-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 32px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);
            margin-bottom: 28px;
        }

        .team-logo-frame {
            height: 170px;
            width: 170px;
            border-radius: 20px;
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px;
            margin: 0 auto;
        }

        .team-title-hero {
            font-family: 'Outfit', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
            letter-spacing: -0.5px;
            line-height: 1.15;
            margin-bottom: 18px;
        }

        .team-title-hero .brand-dark { color: #0f2a4a; }
        .team-title-hero .brand-teal { color: #0284c7; }

        .meta-pill-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 14px 18px;
            display: flex;
            align-items: center;
            gap: 14px;
            transition: all 0.25s ease;
        }

        .meta-pill-card:hover {
            border-color: #cbd5e1;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        /* Stat Cards (Mockup 2) */
        .stat-card-v3 {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px 22px;
            box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-card-v3:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px -5px rgba(15, 23, 42, 0.09);
        }

        .stat-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: #475569;
            letter-spacing: 0.3px;
        }

        .stat-value {
            font-family: 'Outfit', sans-serif;
            font-size: 2.1rem;
            font-weight: 800;
            line-height: 1.1;
            margin: 10px 0 6px;
        }

        .stat-sub {
            font-size: 0.78rem;
            font-weight: 600;
            color: #94a3b8;
            letter-spacing: 0.2px;
        }

        /* Circular Stat Icon Badges */
        .stat-icon-circle {
            width: 46px;
            height: 46px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            flex-shrink: 0;
        }

        .stat-icon-blue {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
        }

        .stat-icon-green {
            background: #dcfce7;
            color: #059669;
            border: 1px solid #bbf7d0;
        }

        .stat-icon-purple {
            background: #f3e8ff;
            color: #7c3aed;
            border: 1px solid #e9d5ff;
        }

        .stat-icon-amber {
            background: #fef3c7;
            color: #d97706;
            border: 1px solid #fde68a;
        }

        /* Table Card (Mockup 2) */
        .table-card-v3 {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 24px;
            padding: 30px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);
            margin-bottom: 40px;
        }

        .table-custom thead {
            background: #28779b !important;
        }

        .table-custom thead th {
            background: #28779b !important;
            color: #ffffff !important;
            font-family: 'Outfit', sans-serif;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            padding: 16px;
            border: none;
            white-space: nowrap;
        }

        .table-custom thead th:first-child {
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .table-custom thead th:last-child {
            border-top-right-radius: 12px;
            border-bottom-right-radius: 12px;
        }

        .table-custom tbody tr {
            background: #ffffff;
            transition: background-color 0.2s ease;
            border-bottom: 1px solid #f1f5f9;
        }

        .table-custom tbody tr:nth-of-type(even) {
            background: #f8fafc;
        }

        .table-custom tbody tr:hover {
            background: #f0fdf4 !important;
        }

        .table-custom td {
            padding: 16px;
            vertical-align: middle;
            font-size: 0.92rem;
            color: #1e293b;
            border: none;
        }

        .cat-pill {
            display: inline-block;
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            font-family: 'Outfit', sans-serif;
            font-size: 0.74rem;
            font-weight: 700;
            padding: 4px 12px;
            border-radius: 50px;
            letter-spacing: 0.5px;
        }

        /* All Teams Franchise Cards */
        .all-team-card {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px;
            box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.05);
            transition: transform 0.25s ease, box-shadow 0.25s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .all-team-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 14px 35px -4px rgba(15, 23, 42, 0.1);
        }

        .btn-pill-primary {
            background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            color: #ffffff;
            border: none;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
            transition: all 0.25s ease;
        }

        .btn-pill-primary:hover {
            background: linear-gradient(135deg, #0369a1 0%, #075985 100%);
            color: #ffffff;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    
    <nav class="navbar navbar-expand-lg owner-navbar p-3">
        <div class="container-fluid">
            <div class="d-flex align-items-center">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 50px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
                <div class="ms-3 d-none d-md-flex flex-column">
                    <span class="fw-bold" style="font-family: 'Outfit', sans-serif; font-size: 1.15rem; letter-spacing: 0.5px; color: #0f2a4a;">PRETIUM PREMIER LEAGUE</span>
                    <span style="font-family: 'Outfit', sans-serif; font-size: 0.72rem; color: #0284c7; letter-spacing: 1.5px; text-transform: uppercase; font-weight: 700;">Team Owner Portal</span>
                </div>
            </div>
            <div class="ms-auto d-flex align-items-center gap-3">
                <div class="d-none d-sm-flex align-items-center px-2 py-1 rounded-pill" style="background: #f1f5f9; border: 1px solid #e2e8f0;">
                    <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 24px; width: 24px; object-fit: contain;">
                </div>
                <span class="text-secondary me-2 d-none d-md-inline" style="font-size: 0.9rem;">Logged in as: <span class="fw-bold" style="color: #0284c7;"><?php echo htmlspecialchars($_SESSION['owner_username']); ?></span></span>
                <a href="owner_logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill" style="font-family: 'Outfit', sans-serif; font-weight: 600;">
                    <i class="fas fa-sign-out-alt me-1"></i> Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container my-4 my-md-5">
        <div class="d-flex justify-content-center mb-4">
            <div class="view-toggle-container">
                <button type="button" class="btn-tab-toggle active" id="btn-my-team" onclick="switchOwnerTab('my-team')">
                    <i class="fas fa-shield-alt"></i> My Team (<?php echo htmlspecialchars($team['team_name']); ?>)
                </button>
                <button type="button" class="btn-tab-toggle" id="btn-all-teams" onclick="switchOwnerTab('all-teams')">
                    <i class="fas fa-users"></i> All Teams Dashboard
                </button>
            </div>
        </div>

        <!-- Section 1: My Team Dashboard -->
        <div id="section-my-team">
            <!-- Hero Team Card (Mockup 2 Layout) -->
            <div class="hero-team-card">
                <div class="row align-items-center g-4">
                    <div class="col-md-3 text-center">
                        <div class="team-logo-frame">
                            <?php if(!empty($team['team_logo'])): ?>
                                <img src="team_assets/<?php echo $team['team_logo']; ?>" alt="Logo" class="img-fluid" style="max-height: 140px; object-fit: contain;" onerror="this.onerror=null;this.src='assets/image-not-found.png';">
                            <?php else: ?>
                                <div style="color: #94a3b8; font-weight: 600;">No Logo</div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-9 text-center text-md-start">
                        <h1 class="team-title-hero">
                            <span class="brand-dark"><?php echo htmlspecialchars($firstPart); ?></span> 
                            <?php if(!empty($lastWord)): ?><span class="brand-teal"><?php echo htmlspecialchars($lastWord); ?></span><?php endif; ?>
                        </h1>
                        <div class="row g-3 mt-1">
                            <div class="col-sm-6">
                                <div class="meta-pill-card">
                                    <div class="stat-icon-circle stat-icon-blue">
                                        <i class="fas fa-user-tie"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="small text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.8px;">TEAM OWNER</div>
                                        <div class="fw-bold" style="font-size: 1.15rem; color: #0f2a4a;"><?php echo htmlspecialchars(implode(', ', array_filter($owners))); ?></div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="meta-pill-card">
                                    <div class="stat-icon-circle stat-icon-green">
                                        <i class="fas fa-star"></i>
                                    </div>
                                    <div class="text-start">
                                        <div class="small text-secondary fw-bold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.8px;">ICON PLAYER</div>
                                        <div class="fw-bold" style="font-size: 1.15rem; color: #0284c7;"><?php echo htmlspecialchars($iconDisplayName); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 4 Metric Cards (Mockup 2) -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card-v3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="stat-title">Team Purse</span>
                            <div class="stat-icon-circle stat-icon-blue">
                                <i class="fas fa-coins"></i>
                            </div>
                        </div>
                        <div class="stat-value" style="color: #0f2a4a;"><?php echo formatPoints($totalPointsPerTeam); ?></div>
                        <div class="stat-sub">Total Budget</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card-v3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="stat-title">Remaining Purse</span>
                            <div class="stat-icon-circle stat-icon-green">
                                <i class="fas fa-wallet"></i>
                            </div>
                        </div>
                        <div class="stat-value" style="color: #059669;"><?php echo formatPoints($remaining); ?></div>
                        <div class="stat-sub">Available to Spend</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card-v3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="stat-title">Squad Strength</span>
                            <div class="stat-icon-circle stat-icon-purple">
                                <i class="fas fa-users"></i>
                            </div>
                        </div>
                        <div class="stat-value" style="color: #7c3aed;"><?php echo $totalMembers; ?> / <?php echo isset($team['max_strength']) ? $team['max_strength'] : 13; ?></div>
                        <div class="stat-sub">Players Selected</div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card-v3">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="stat-title">Slots Remaining</span>
                            <div class="stat-icon-circle stat-icon-amber">
                                <i class="fas fa-user-plus"></i>
                            </div>
                        </div>
                        <div class="stat-value" style="color: #d97706;"><?php echo ((isset($team['max_strength']) ? $team['max_strength'] : 13) - $totalMembers); ?></div>
                        <div class="stat-sub">Open Roster Spots</div>
                    </div>
                </div>
            </div>

            <!-- Selected Players Table (Mockup 2 Ocean Teal Header) -->
            <div class="table-card-v3">
                <div class="d-flex justify-content-between align-items-center mb-4 pb-3" style="border-bottom: 1.5px solid #f1f5f9;">
                    <h3 class="m-0 fw-bold" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 1.35rem;">
                        <i class="fas fa-users me-2" style="color: #0284c7;"></i> Selected Players
                    </h3>
                    <button onclick="exportToPDF()" class="btn-pill-primary" style="padding: 8px 18px; font-size: 0.84rem;">
                        <i class="fas fa-file-pdf"></i> Export to PDF
                    </button>
                </div>
                
                <div class="table-responsive">
                    <table class="table table-custom mb-0">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Player ID</th>
                                <th>Name</th>
                                <th>Phone Number</th>
                                <th>Category</th>
                                <th>Role</th>
                                <th class="text-end">Bought For</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($players) > 0): ?>
                                <?php foreach ($players as $index => $p): ?>
                                <tr style="cursor: pointer;" onclick='showPlayerModal(<?php echo json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE); ?>)'>
                                    <td><span class="text-muted fw-bold"><?php echo $index + 1; ?></span></td>
                                    <td><span class="fw-bold" style="color: #0284c7;"><?php echo htmlspecialchars($p['player_id']); ?></span></td>
                                    <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($p['full_name']); ?></span></td>
                                    <td><span class="text-secondary"><?php echo htmlspecialchars($p['mobile']); ?></span></td>
                                    <td>
                                        <span class="cat-pill">
                                            <?php echo htmlspecialchars($p['category']); ?>
                                        </span>
                                    </td>
                                    <td><span class="text-secondary fw-semibold"><?php echo htmlspecialchars($p['role']); ?></span></td>
                                    <td class="text-end fw-bold" style="color: #0284c7; font-size: 1rem;"><?php echo formatPoints($p['bid_points']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="7" class="text-center py-5 text-secondary">No players have been bought in the auction yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div> <!-- end section-my-team -->

        <!-- Section 2: All Teams Dashboard -->
        <div id="section-all-teams" style="display: none;">
            <div class="text-center mb-4">
                <h2 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 2rem;">All Teams Dashboard</h2>
                <p class="text-secondary small mt-1">Live purse, squad strength, and roster status across all PPL franchises</p>
            </div>

            <div class="row g-4 mb-5">
                <?php foreach ($allTeams as $t): 
                    if ($t['id'] == $teamId) continue;
                    $tSpent = $t['reg_points'];
                    $tPurse = floatval($t['total_purse'] ?? 10000000);
                    $tRemaining = max(0, $tPurse - $tSpent);
                    $tPlayersCount = $t['total_players'];
                    $tLogo = !empty($t['team_logo']) ? 'team_assets/' . $t['team_logo'] : 'assets/ppl-logo-transparent.png';
                    $tIcons = $allIconsByTeam[$t['id']] ?? [];
                    
                    // Parse Owner for display
                    $tOwnerArr = json_decode($t['owner'], true);
                    $tOwnerDisplay = is_array($tOwnerArr) ? implode(', ', array_filter($tOwnerArr)) : $t['owner'];
                    
                    // Parse Icon for display
                    $tIconArr = isset($t['ic_player']) ? json_decode($t['ic_player'], true) : [];
                    $tIconFallback = is_array($tIconArr) ? implode(', ', array_filter($tIconArr)) : ($t['ic_player'] ?? 'Mystery Icon');
                    
                    $tIconName = $tIcons ? htmlspecialchars(implode(', ', array_column($tIcons, 'name'))) : htmlspecialchars($tIconFallback);
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="all-team-card" onclick="window.location.href='team_dashboard.php?team_id=<?php echo $t['id']; ?>'" style="cursor: pointer;">
                        <div>
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="badge rounded-pill px-3 py-1" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: 'Outfit', sans-serif; font-size: 0.74rem;">
                                    <i class="fas fa-crown me-1"></i> <?php echo htmlspecialchars($tOwnerDisplay ?: 'Owner'); ?>
                                </span>
                            </div>
                            <div class="text-center my-3" style="height: 120px; display: flex; align-items: center; justify-content: center;">
                                <img src="<?php echo htmlspecialchars($tLogo); ?>" alt="<?php echo htmlspecialchars($t['team_name']); ?>" onerror="this.onerror=null;this.src='assets/image-not-found.png';" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                            </div>
                            <h4 class="text-center fw-bold mb-2" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 1.25rem;">
                                <?php echo htmlspecialchars($t['team_name']); ?>
                            </h4>
                            <div class="text-center text-secondary small mb-4">
                                Icon: <strong class="text-dark"><?php echo $tIconName; ?></strong>
                            </div>
                            <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small fw-bold">TEAM PURSE</span>
                                    <span class="fw-bold text-dark"><?php echo formatPoints($tPurse); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small fw-bold">REMAINING PURSE</span>
                                    <span class="fw-bold text-success"><?php echo formatPoints($tRemaining); ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-secondary small fw-bold">SQUAD STRENGTH</span>
                                    <span class="fw-bold" style="color: #7c3aed;"><?php echo $tPlayersCount; ?> / <?php echo isset($t['max_strength']) ? $t['max_strength'] : 13; ?></span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="text-secondary small fw-bold">SLOTS REMAINING</span>
                                    <span class="fw-bold" style="color: #d97706;"><?php echo ((isset($t['max_strength']) ? $t['max_strength'] : 13) - $tPlayersCount); ?></span>
                                </div>
                            </div>
                        </div>
                        <a href="team_dashboard.php?team_id=<?php echo $t['id']; ?>" class="btn-pill-primary w-100 mt-4 py-2" onclick="event.stopPropagation();">
                            <i class="fas fa-eye"></i> View Dashboard
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div> <!-- end section-all-teams -->
    </div>

    <!-- Player Details Modal (Light Modern Theme) -->
    <div class="modal fade" id="playerDetailModal" tabindex="-1" aria-labelledby="playerDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15); overflow: hidden;">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="playerDetailModalLabel" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; letter-spacing: 0.5px;">PLAYER DETAILS</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-4 align-items-center">
                        <div class="col-md-5 text-center">
                            <div style="border: 2px solid #e2e8f0; border-radius: 16px; overflow: hidden; background: #f8fafc;">
                                <img id="modal-player-image" src="assets/image-not-found.png" onerror="this.onerror=null;this.src='assets/image-not-found.png';" alt="Player" class="img-fluid w-100" style="height: 320px; object-fit: cover;">
                            </div>
                            <div class="mt-3">
                                <span id="modal-player-id-badge" class="px-3 py-1 fw-bold rounded-pill" style="background: #e0f2fe; color: #0369a1; font-size: 0.9rem; letter-spacing: 0.5px;"></span>
                            </div>
                        </div>
                        <div class="col-md-7">
                            <h2 id="modal-player-name" class="fw-bold mb-1" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;"></h2>
                            <p id="modal-player-category" class="text-secondary mb-3 fw-semibold" style="font-size: 0.88rem;"></p>

                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 1px;">Role</div>
                                        <div id="modal-player-role" class="fw-bold text-dark mt-1" style="font-size: 1rem;"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 1px;">Batting</div>
                                        <div id="modal-player-batting" class="fw-bold text-dark mt-1" style="font-size: 1rem;"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 1px;">Bowling</div>
                                        <div id="modal-player-bowling" class="fw-bold text-dark mt-1" style="font-size: 1rem;"></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                        <div class="text-uppercase text-secondary fw-bold" style="font-size: 0.68rem; letter-spacing: 1px;">Department</div>
                                        <div id="modal-player-department" class="fw-bold text-dark mt-1" style="font-size: 1rem;"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3 rounded-3 text-center" style="background: #f0fdf4; border: 1.5px solid #bbf7d0;">
                                <div class="text-uppercase fw-bold" style="font-size: 0.72rem; letter-spacing: 1.5px; color: #059669;">BOUGHT FOR</div>
                                <div id="modal-player-bid" class="fw-bold mt-1" style="font-size: 1.75rem; color: #059669; font-family: 'Outfit', sans-serif;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf-autotable/3.5.28/jspdf.plugin.autotable.min.js"></script>
    <script>
        function exportToPDF() {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('p', 'pt', 'a4');
            
            doc.setFontSize(16);
            doc.text("<?php echo addslashes($team['team_name']); ?> - Selected Players", 40, 40);
            
            const playersData = [
                <?php foreach ($players as $p): ?>
                [
                    "<?php echo addslashes($p['player_id']); ?>",
                    "<?php echo addslashes($p['full_name']); ?>",
                    "<?php echo addslashes($p['mobile']); ?>",
                    "<?php echo addslashes($p['category']); ?>",
                    "<?php echo addslashes($p['role']); ?>",
                    "<?php echo addslashes(formatPoints($p['bid_points'])); ?>"
                ],
                <?php endforeach; ?>
            ];

            doc.autoTable({
                startY: 60,
                head: [['Player ID', 'Name', 'Phone Number', 'Category', 'Role', 'Bought For']],
                body: playersData,
                theme: 'grid',
                styles: { fontSize: 10 },
                headStyles: { fillColor: [40, 119, 155], textColor: [255, 255, 255] }
            });
            
            doc.save("<?php echo preg_replace('/[^a-zA-Z0-9]/', '_', $team['team_name']); ?>_Players.pdf");
        }

        function showPlayerModal(playerData) {
            document.getElementById('modal-player-name').textContent = playerData.full_name || playerData.player_name || '';
            document.getElementById('modal-player-id-badge').textContent = playerData.player_id || '';
            
            const catRaw = (playerData.category || playerData.player_category || '').toUpperCase();
            let catShort = playerData.category || playerData.player_category || 'Official Player';
            const mq = catRaw.match(/MARQUEE\s+([ABCD])/);
            if (mq) catShort = 'Marquee ' + mq[1];
            document.getElementById('modal-player-category').textContent = catShort;
            
            document.getElementById('modal-player-role').textContent = playerData.role || playerData.player_type || 'N/A';
            document.getElementById('modal-player-batting').textContent = playerData.batting_type || 'N/A';
            document.getElementById('modal-player-bowling').textContent = playerData.bowling_type || 'N/A';
            
            var bp = parseFloat(playerData.bid_points || 0);
            document.getElementById('modal-player-bid').textContent = '₹' + formatPoints(bp);
            
            document.getElementById('modal-player-department').textContent = playerData.department || 'N/A';

            const imgEl = document.getElementById('modal-player-image');
            let imgPath = playerData.profile_pic || playerData.player_image || '';
            if (imgPath) {
                if (!imgPath.startsWith('http') && !imgPath.startsWith('../') && !imgPath.startsWith('/')) {
                    imgPath = '../' + imgPath;
                }
                imgEl.src = imgPath;
            } else {
                imgEl.src = 'assets/image-not-found.png';
            }

            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('playerDetailModal'));
            modal.show();
        }

        function switchOwnerTab(tab) {
            const btnMyTeam = document.getElementById('btn-my-team');
            const btnAllTeams = document.getElementById('btn-all-teams');
            const secMyTeam = document.getElementById('section-my-team');
            const secAllTeams = document.getElementById('section-all-teams');

            if (tab === 'my-team') {
                btnMyTeam.classList.add('active');
                btnAllTeams.classList.remove('active');
                secMyTeam.style.display = 'block';
                secAllTeams.style.display = 'none';
                if (history.replaceState) {
                    history.replaceState(null, null, '#my-team');
                }
            } else {
                btnAllTeams.classList.add('active');
                btnMyTeam.classList.remove('active');
                secAllTeams.style.display = 'block';
                secMyTeam.style.display = 'none';
                if (history.replaceState) {
                    history.replaceState(null, null, '#all-teams');
                }
            }
        }

        window.addEventListener('DOMContentLoaded', () => {
            if (window.location.hash === '#all-teams') {
                switchOwnerTab('all-teams');
            } else {
                switchOwnerTab('my-team');
            }
        });
    </script>
</body>
</html>
