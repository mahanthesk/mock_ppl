<?php
require_once '../auth.php';
check_access('auction_admin');

// team_details.php
require_once 'db_connection.php';

$teamId = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($teamId <= 0) {
    header('Location: dashboard.php');
    exit;
}

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

    // Split team name for two-tone typography
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

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> - Team Details</title>
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

        /* Hero Team Card */
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
        }

        /* Stat Cards */
        .stat-card-v3 {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 24px 22px;
            box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .stat-title {
            font-family: 'Outfit', sans-serif;
            font-size: 0.88rem;
            font-weight: 700;
            color: #475569;
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
        }

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

        .stat-icon-blue { background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; }
        .stat-icon-green { background: #dcfce7; color: #059669; border: 1px solid #bbf7d0; }
        .stat-icon-purple { background: #f3e8ff; color: #7c3aed; border: 1px solid #e9d5ff; }
        .stat-icon-amber { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }

        /* Action Buttons */
        .btn-pill-action {
            padding: 8px 18px;
            border-radius: 50px;
            font-family: 'Outfit', sans-serif;
            font-size: 0.82rem;
            font-weight: 700;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: all 0.2s ease;
        }

        .btn-pill-outline-teal {
            background: #f0f9ff;
            color: #0284c7;
            border: 1.5px solid #bae6fd;
        }

        .btn-pill-outline-teal:hover {
            background: #0284c7;
            color: #ffffff;
        }

        .btn-pill-outline-amber {
            background: #fffbeb;
            color: #d97706;
            border: 1.5px solid #fde68a;
        }

        .btn-pill-outline-amber:hover {
            background: #d97706;
            color: #ffffff;
        }

        /* Table Card */
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
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container my-4 my-md-5">
        <!-- Hero Team Card -->
        <div class="hero-team-card">
            <div class="row align-items-center g-4">
                <div class="col-md-3 text-center">
                    <div class="team-logo-frame">
                        <?php if(!empty($team['team_logo'])): ?>
                            <img src="team_assets/<?php echo htmlspecialchars($team['team_logo']); ?>" alt="Logo" class="img-fluid" style="max-height: 140px; object-fit: contain;" onerror="this.onerror=null;this.src='assets/image-not-found.png';">
                        <?php else: ?>
                            <div style="color: #94a3b8; font-weight: 600;">No Logo</div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-9 text-center text-md-start">
                    <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3 mb-3">
                        <h1 class="team-title-hero m-0">
                            <span class="brand-dark"><?php echo htmlspecialchars($firstPart); ?></span> 
                            <?php if(!empty($lastWord)): ?><span class="brand-teal"><?php echo htmlspecialchars($lastWord); ?></span><?php endif; ?>
                        </h1>
                        <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                            <a href="final_list.php?team_id=<?php echo $teamId; ?>" class="btn-pill-action btn-pill-outline-teal">
                                <i class="fas fa-list-ul"></i> Final List
                            </a>
                            <a href="team_grid.php?team_id=<?php echo $teamId; ?>" class="btn-pill-action btn-pill-outline-teal">
                                <i class="fas fa-grip"></i> Squad Grid
                            </a>
                            <?php
                            $teamDataB64 = base64_encode(json_encode([
                                'id' => $team['id'],
                                'name' => $team['team_name'],
                                'owner' => $team['owner'],
                                'ic_player' => $team['ic_player'],
                                'total_purse' => $team['total_purse'],
                                'spent' => $spent,
                                'remaining_purse' => $remaining,
                                'max_strength' => $team['max_strength']
                            ]));
                            ?>
                            <button type="button" class="btn-pill-action btn-pill-outline-amber edit-team-btn" onclick="openEditModalFromBtn(this);" data-team="<?php echo $teamDataB64; ?>">
                                <i class="fas fa-edit"></i> Edit Purse
                            </button>
                        </div>
                    </div>
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
                    <i class="fas fa-users me-2" style="color: #0284c7;"></i> Players List (<?php echo count($players); ?>/<?php echo isset($team['max_strength']) ? $team['max_strength'] : 13; ?>)
                </h3>
            </div>
            
            <div class="table-responsive">
                <table class="table table-custom mb-0">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Player ID</th>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Role</th>
                            <th>Bid Points</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($players) > 0): ?>
                            <?php foreach ($players as $index => $p): ?>
                            <tr>
                                <td><span class="text-muted fw-bold"><?php echo $index + 1; ?></span></td>
                                <td><span class="fw-bold" style="color: #0284c7;"><?php echo htmlspecialchars($p['player_id']); ?></span></td>
                                <td><span class="fw-bold text-dark"><?php echo htmlspecialchars($p['full_name']); ?></span></td>
                                <td>
                                    <span class="cat-pill">
                                        <?php echo htmlspecialchars($p['category']); ?>
                                    </span>
                                </td>
                                <td><span class="text-secondary fw-semibold"><?php echo htmlspecialchars($p['role']); ?></span></td>
                                <td class="fw-bold" style="color: #0284c7; font-size: 0.95rem;"><?php echo formatPoints($p['bid_points']); ?></td>
                                <td class="text-end">
                                    <button class="btn-pill-action btn-pill-outline-teal view-player-btn"
                                        style="padding: 5px 14px; font-size: 0.78rem;"
                                        data-bs-toggle="modal" data-bs-target="#playerDetailModal"
                                        data-name="<?php echo htmlspecialchars($p['full_name']); ?>"
                                        data-playerid="<?php echo htmlspecialchars($p['player_id']); ?>"
                                        data-category="<?php echo htmlspecialchars($p['category']); ?>"
                                        data-role="<?php echo htmlspecialchars($p['role']); ?>"
                                        data-batting="<?php echo htmlspecialchars($p['batting_type']); ?>"
                                        data-bowling="<?php echo htmlspecialchars($p['bowling_type']); ?>"
                                        data-bid="<?php echo formatPoints($p['bid_points']); ?>"
                                        data-image="<?php echo htmlspecialchars($p['profile_pic']); ?>"
                                        data-department="<?php echo htmlspecialchars($p['department'] ?? ''); ?>">
                                        <i class="fas fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="7" class="text-center py-5 text-secondary">No players bought yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
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

    <!-- Edit Team / Purse Modal (Clean Light Theme) -->
    <div class="modal fade" id="editTeamModal" tabindex="-1" aria-labelledby="editTeamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="editTeamModalLabel" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">
                        <i class="fas fa-edit me-2" style="color: #0284c7;"></i>Edit Team Details & Purse
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editTeamForm" enctype="multipart/form-data">
                    <div class="modal-body p-4">
                        <div id="editTeamErrorAlert" class="alert alert-danger d-none" role="alert" style="border-radius: 12px;"></div>
                        <div id="editTeamSuccessAlert" class="alert alert-success d-none" role="alert" style="border-radius: 12px;"></div>
                        <input type="hidden" id="edit_team_id" name="team_id" value="">

                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold small">Team Name</label>
                                <input type="text" class="form-control" id="edit_team_name" name="team_name" required style="border-radius: 12px; border-color: #cbd5e1;">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-secondary fw-bold small">Team Logo (Optional Replace)</label>
                                <input type="file" class="form-control" name="team_logo" accept="image/*" style="border-radius: 12px; border-color: #cbd5e1;">
                            </div>
                        </div>

                        <div class="p-3 mb-4 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">Purse & Squad Configuration</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label text-secondary fw-bold small">Total Purse (₹)</label>
                                    <input type="number" class="form-control" id="edit_total_purse" name="total_purse" required min="0" step="1000" style="border-radius: 12px; border-color: #cbd5e1;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary fw-bold small">Current Spent (Calculated)</label>
                                    <div id="edit_spent_display" class="form-control bg-light text-muted fw-bold" style="border-radius: 12px; border-color: #e2e8f0;">₹0</div>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary fw-bold small">Remaining Purse (₹)</label>
                                    <input type="number" class="form-control" id="edit_remaining_purse" name="remaining_purse" required min="0" step="1000" style="border-radius: 12px; border-color: #cbd5e1;">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label text-secondary fw-bold small">Max Squad Strength</label>
                                    <input type="number" class="form-control" id="edit_max_strength" name="max_strength" min="1" max="50" style="border-radius: 12px; border-color: #cbd5e1;">
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label text-secondary fw-bold small">Team Owners</label>
                            <div id="editTeamOwners"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" onclick="addEditOwnerRow()">
                                <i class="fas fa-plus me-1"></i> Add Another Owner
                            </button>
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-secondary fw-bold small">Icon Players</label>
                            <div id="editTeamIconPlayers"></div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill mt-2" onclick="addEditIconPlayerRow()">
                                <i class="fas fa-plus me-1"></i> Add Another Icon
                            </button>
                        </div>
                    </div>
                    <div class="modal-footer border-0 p-4 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4" id="updateTeamBtn" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                            <i class="fas fa-save me-2"></i> Update Team
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            function formatPoints(val) {
                if (val >= 10000000) return (val / 10000000).toFixed(2).replace(/\.00$/, '') + ' Cr';
                if (val >= 100000) return (val / 100000).toFixed(2).replace(/\.00$/, '') + ' L';
                return Number(val).toLocaleString();
            }

            window.currentTeamSpent = 0;

            window.openEditModalFromBtn = function(btn) {
                try {
                    var raw = typeof btn === 'string' ? btn : ($(btn).attr('data-team') || $(btn).data('team'));
                    if (!raw) return;
                    var jsonStr = (typeof raw === 'string' && raw.trim().indexOf('{') === 0) ? raw : atob(raw);
                    var data = typeof jsonStr === 'object' ? jsonStr : JSON.parse(jsonStr);
                    if (data) {
                        openEditModal(data);
                    }
                } catch(err) {
                    console.error('Failed to parse team data:', err);
                }
            };

            window.openEditModal = function(data) {
                var spent = parseFloat(data.spent || 0);
                var total = parseFloat(data.total_purse || 0);
                var remaining = data.remaining_purse !== undefined ? parseFloat(data.remaining_purse) : Math.max(0, total - spent);

                window.currentTeamSpent = spent;
                $('#edit_team_id').val(data.id);
                $('#edit_team_name').val(data.name);
                $('#edit_spent_display').text('₹' + formatPoints(spent));
                $('#edit_remaining_purse').val(Math.round(remaining));
                $('#edit_total_purse').val(Math.round(total));
                $('#edit_max_strength').val(data.max_strength);
                $('#editTeamErrorAlert, #editTeamSuccessAlert').addClass('d-none');

                $('#editTeamOwners').empty();
                $('#editTeamIconPlayers').empty();

                var owners = [];
                try { owners = JSON.parse(data.owner); } catch(e) { if(data.owner) owners = [data.owner]; }
                if (owners.length === 0) owners = [''];
                owners.forEach(function(own) {
                    var html = '<div class="row g-3 mb-3 owner-row"><div class="col-md-5"><input type="text" name="owner[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" required placeholder="Owner Name" value="' + (own ? own.replace(/"/g, '&quot;') : '') + '"></div><div class="col-md-5"><input type="file" name="owner_img[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" accept="image/*"></div><div class="col-md-2 text-center"><button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="$(this).closest(\'.row\').remove();"><i class="fas fa-trash"></i></button></div></div>';
                    $('#editTeamOwners').append(html);
                });

                var iconPlayers = [];
                try { iconPlayers = JSON.parse(data.ic_player); } catch(e) { if(data.ic_player) iconPlayers = [data.ic_player]; }
                if (iconPlayers.length === 0) iconPlayers = [''];
                iconPlayers.forEach(function(ic) {
                    var html = '<div class="row g-3 mb-3 icon-player-row"><div class="col-md-5"><input type="text" name="ic_player[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" required placeholder="Icon Player Name" value="' + (ic ? ic.replace(/"/g, '&quot;') : '') + '"></div><div class="col-md-5"><input type="file" name="ic_player_img[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" accept="image/*"></div><div class="col-md-2 text-center"><button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="$(this).closest(\'.row\').remove();"><i class="fas fa-trash"></i></button></div></div>';
                    $('#editTeamIconPlayers').append(html);
                });

                var modalEl = document.getElementById('editTeamModal');
                if (modalEl) {
                    var modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalObj.show();
                }
            };

            window.addEditOwnerRow = function() {
                var html = '<div class="row g-3 mb-3 owner-row"><div class="col-md-5"><input type="text" name="owner[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" required placeholder="Owner Name"></div><div class="col-md-5"><input type="file" name="owner_img[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" accept="image/*"></div><div class="col-md-2 text-center"><button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="$(this).closest(\'.row\').remove();"><i class="fas fa-trash"></i></button></div></div>';
                $('#editTeamOwners').append(html);
            };

            window.addEditIconPlayerRow = function() {
                var html = '<div class="row g-3 mb-3 icon-player-row"><div class="col-md-5"><input type="text" name="ic_player[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" required placeholder="Icon Player Name"></div><div class="col-md-5"><input type="file" name="ic_player_img[]" class="form-control" style="border-radius:12px;border-color:#cbd5e1;" accept="image/*"></div><div class="col-md-2 text-center"><button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="$(this).closest(\'.row\').remove();"><i class="fas fa-trash"></i></button></div></div>';
                $('#editTeamIconPlayers').append(html);
            };

            $(document).on('click', '.edit-team-btn', function(e) {
                e.preventDefault();
                openEditModalFromBtn(this);
            });

            $('#editTeamForm').on('submit', function (e) {
                e.preventDefault();
                var form = this;
                var submitBtn = $('#updateTeamBtn');

                $('#editTeamErrorAlert, #editTeamSuccessAlert').addClass('d-none').text('');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...');

                var formData = new FormData(form);

                $.ajax({
                    url: 'api_edit_team.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        try {
                            var res = typeof response === 'object' ? response : JSON.parse(response);
                            if (res.status === 'success') {
                                $('#editTeamSuccessAlert').removeClass('d-none').text(res.message);
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1200);
                            } else {
                                $('#editTeamErrorAlert').removeClass('d-none').text(res.message);
                                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                            }
                        } catch (err) {
                            $('#editTeamErrorAlert').removeClass('d-none').text('Invalid server response.');
                            submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                        }
                    },
                    error: function () {
                        $('#editTeamErrorAlert').removeClass('d-none').text('An error occurred during the request.');
                        submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                    }
                });
            });
        });

        // Populate modal with player data when View button is clicked
        document.querySelectorAll('.view-player-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                const name = this.getAttribute('data-name');
                const playerId = this.getAttribute('data-playerid');
                const category = this.getAttribute('data-category');
                const role = this.getAttribute('data-role');
                const batting = this.getAttribute('data-batting');
                const bowling = this.getAttribute('data-bowling');
                const bid = this.getAttribute('data-bid');
                const image = this.getAttribute('data-image');
                const department = this.getAttribute('data-department');

                document.getElementById('modal-player-name').textContent = name;
                document.getElementById('modal-player-id-badge').textContent = playerId;
                document.getElementById('modal-player-category').textContent = category;
                document.getElementById('modal-player-role').textContent = role || 'N/A';
                document.getElementById('modal-player-batting').textContent = batting || 'N/A';
                document.getElementById('modal-player-bowling').textContent = bowling || 'N/A';
                document.getElementById('modal-player-bid').textContent = '₹' + bid;
                document.getElementById('modal-player-department').textContent = department || 'N/A';

                const imgEl = document.getElementById('modal-player-image');
                if (image) {
                    imgEl.src = '../' + image;
                } else {
                    imgEl.src = 'assets/image-not-found.png';
                }
            });
        });
    </script>
</body>
</html>
