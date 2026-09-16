<?php

require_once 'auth.php';
check_access('registration_admin');

require 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo "Player not found.";
    exit;
}

// Fetch Player Stats
$stats_stmt = $pdo->prepare("SELECT * FROM player_stats WHERE player_id = ?");
$stats_stmt->execute([$player['player_id']]);
$stats_row = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$stats = [
    'matches_played' => intval($stats_row['matches_played'] ?? 0),
    'innings'        => intval($stats_row['innings'] ?? 0),
    'runs_scored'    => intval($stats_row['runs_scored'] ?? 0),
    'highest_score'  => intval($stats_row['highest_score'] ?? 0),
    'batting_avg'    => number_format(floatval($stats_row['batting_avg'] ?? 0), 2),
    'strike_rate'    => number_format(floatval($stats_row['strike_rate'] ?? 0), 2),
    'fifties'        => intval($stats_row['fifties'] ?? 0),
    'hundreds'       => intval($stats_row['hundreds'] ?? 0),
    'wickets'        => intval($stats_row['wickets'] ?? 0),
    'bowling_avg'    => number_format(floatval($stats_row['bowling_avg'] ?? 0), 2),
    'economy'        => number_format(floatval($stats_row['economy'] ?? 0), 2),
    'best_bowling'   => !empty($stats_row['best_bowling']) ? htmlspecialchars($stats_row['best_bowling']) : '-',
    'catches'        => intval($stats_row['catches'] ?? 0),
    'stumpings'      => intval($stats_row['stumpings'] ?? 0)
];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Player - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 24px;
            background: rgba(255, 255, 255, 0.96);
            border-bottom: 1px solid rgba(2, 132, 199, 0.18);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);
        }

        .back-btn-static {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #0284c7;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.25s;
            padding: 8px 18px;
            background: #ffffff;
            border: 1.5px solid rgba(2, 132, 199, 0.25);
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(15, 42, 74, 0.05);
        }

        .back-btn-static:hover {
            color: #ffffff;
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            transform: translateX(-4px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        /* Player Profile Card (Card 1 - Left) */
        #player-card {
            width: 100%;
            max-width: 420px;
            background: #ffffff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            color: #0f2a4a;
            padding: 0;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
            border: 1px solid rgba(2, 132, 199, 0.18);
        }

        .card-top-right-badge {
            position: absolute;
            top: 14px;
            right: 14px;
            background: #0f2a4a;
            color: #ffffff;
            padding: 4px 14px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 800;
            z-index: 10;
            letter-spacing: 0.5px;
            box-shadow: 0 4px 10px rgba(15, 42, 74, 0.2);
        }

        .card-header-v2 {
            padding: 24px 15px 5px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            position: relative;
            z-index: 2;
        }

        .card-spl-info {
            text-align: center;
        }

        .card-spl-info .spl-text {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 1.35rem;
            letter-spacing: 1px;
            font-weight: 800;
            display: block;
            margin-bottom: 2px;
        }

        .card-spl-info .season-text {
            font-size: 0.68rem;
            color: #0284c7;
            letter-spacing: 2px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .card-body-v2 {
            padding: 0 24px;
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .card-photo-wrapper {
            width: 220px;
            height: 250px;
            position: relative;
            margin: 15px auto 16px;
            padding: 5px;
            border-radius: 16px;
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            box-shadow: 0 8px 20px rgba(15, 42, 74, 0.12);
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .card-profile-photo {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            background: #f1f5f9;
            cursor: pointer;
            transition: transform 0.4s ease;
        }

        .card-profile-photo:hover {
            transform: scale(1.04);
        }

        .batting-badge-gold {
            color: #0284c7;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            text-align: center;
        }

        .card-name-v2 {
            text-align: center;
            margin-bottom: 18px;
        }

        .card-fullname {
            font-family: 'Poppins', sans-serif;
            font-size: 1.45rem;
            color: #0f2a4a;
            margin: 0;
            line-height: 1.2;
            font-weight: 800;
        }

        .card-nickname {
            font-size: 0.82rem;
            color: #0284c7;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            font-weight: 600;
            display: block;
            margin-top: 4px;
        }

        .card-info-box {
            width: 100%;
            background: #f8fafc;
            border: 1px solid rgba(2, 132, 199, 0.12);
            border-radius: 14px;
            padding: 14px 10px;
            margin-bottom: 18px;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 8px;
        }

        .info-box {
            text-align: center;
        }

        .info-label {
            font-size: 0.62rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 700;
            display: block;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f2a4a;
        }

        .card-footer-v2 {
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 12px 16px 20px;
            background: #f8fafc;
            border-top: 1px solid rgba(2, 132, 199, 0.1);
            position: relative;
            z-index: 2;
        }

        .jersey-pill-badge {
            background: #ffffff;
            border: 1.5px solid rgba(2, 132, 199, 0.25);
            border-radius: 30px;
            padding: 8px 24px;
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 4px 12px rgba(15, 42, 74, 0.06);
        }

        .jersey-num-text {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0284c7;
            font-family: 'Poppins', sans-serif;
        }

        /* Detail List Cards */
        .detail-card {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.18);
            border-radius: 20px;
            padding: 28px 25px;
            width: 100%;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        .card-header-title {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            border-bottom: 2px solid rgba(2, 132, 199, 0.15);
            padding-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .header-icon-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.25);
            border-radius: 10px;
            color: #0284c7;
            margin-right: 14px;
            font-size: 1.05rem;
        }

        /* Full Details Rows */
        .detail-row-box {
            background: #f8fafc;
            border: 1px solid rgba(2, 132, 199, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.25s ease;
        }

        .detail-row-box:hover {
            border-color: rgba(2, 132, 199, 0.35);
            background: #f1f5f9;
        }

        .row-label {
            color: #64748b;
            font-size: 0.88rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .row-label i {
            color: #0284c7;
            font-size: 0.95rem;
            width: 20px;
            text-align: center;
        }

        .row-val {
            color: #0f2a4a;
            font-weight: 700;
            font-size: 0.92rem;
            text-align: right;
        }

        .badge-pill-green {
            background: #f0fdf4;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            border-radius: 20px;
            padding: 4px 16px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        .badge-pill-blue {
            background: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            border-radius: 20px;
            padding: 4px 16px;
            font-size: 0.82rem;
            font-weight: 700;
        }

        /* Player Statistics Card */
        .stats-header-title {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 1.25rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            border-bottom: 2px solid rgba(2, 132, 199, 0.15);
            padding-bottom: 14px;
            letter-spacing: -0.5px;
        }

        .title-line {
            flex: 1;
            height: 2px;
            background: rgba(2, 132, 199, 0.15);
        }

        .stats-highlight-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .stat-highlight-box {
            background: #f8fafc;
            border: 1px solid rgba(2, 132, 199, 0.12);
            border-radius: 12px;
            padding: 12px 6px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .stat-highlight-num {
            font-size: 1.35rem;
            font-weight: 800;
            color: #0f2a4a;
            line-height: 1.2;
            font-family: 'Poppins', sans-serif;
        }

        .stat-highlight-label {
            font-size: 0.65rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-top: 4px;
        }

        .stat-sec-title {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 0.85rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            border-bottom: 1px solid rgba(2, 132, 199, 0.12);
            padding-bottom: 8px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
        }

        .stat-sec-title i {
            color: #0284c7;
        }

        .stat-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 6px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.86rem;
        }

        .stat-item-label {
            color: #64748b;
            font-weight: 600;
        }

        .stat-item-value {
            color: #0f2a4a;
            font-weight: 700;
        }

        /* Modal Custom Styling */
        .modal-content {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.25);
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(15, 42, 74, 0.2);
        }

        .modal-header {
            border-bottom: 1px solid rgba(2, 132, 199, 0.15);
            padding: 20px 24px;
            background: #f8fafc;
            border-top-left-radius: 20px;
            border-top-right-radius: 20px;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .modal-body {
            padding: 24px;
            text-align: center;
        }

        #idCardViewer {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 12px;
            border: 1px solid #cbd5e1;
        }

        #pdfViewer {
            width: 100%;
            height: 80vh;
            border: none;
            border-radius: 12px;
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .custom-header {
                flex-wrap: wrap;
                padding: 10px;
                gap: 8px;
            }

            #player-card {
                max-width: 100%;
            }

            .detail-card {
                padding: 20px 16px;
                margin-top: 20px;
            }

            .detail-row-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }

            .row-val {
                text-align: left;
            }
        }
    </style>
</head>

<body>
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2">
        <div class="header-left d-flex align-items-center">
            <a href="admin_dashboard.php" class="text-decoration-none">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 52px; filter: drop-shadow(0 2px 8px rgba(2, 132, 199, 0.3));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
            </a>
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.5rem; letter-spacing: -0.5px; color: #0f2a4a;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.72rem; color: #0284c7;">Player Profile & Statistics</span>
        </div>
        <div class="header-right d-flex align-items-center gap-3">
            <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 36px; width: 36px; object-fit: contain;">
        </div>
    </div>

    <div class="container-fluid px-3 px-lg-5 mt-4 mb-5" style="max-width: 1750px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="admin_players.php" class="back-btn-static">
                <i class="fas fa-arrow-left"></i> BACK TO LIST
            </a>
        </div>

        <div class="row align-items-stretch g-4">
            <!-- 1. Player Profile Card (Left) -->
            <div class="col-xl-4 col-lg-4 col-md-12 d-flex justify-content-center">
                <div id="player-card">
                    <div class="card-top-right-badge">
                        <?php echo $player['player_id']; ?>
                    </div>

                    <div class="card-header-v2">
                        <div class="card-spl-info">
                            <span class="spl-text">PRETIUM</span>
                            <span class="season-text">PREMIER LEAGUE</span>
                        </div>
                    </div>

                    <div class="card-body-v2">
                        <div class="card-photo-wrapper">
                            <?php if (!empty($player['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($player['profile_pic']); ?>"
                                    class="card-profile-photo view-profile-pic"
                                    data-file="<?php echo htmlspecialchars($player['profile_pic']); ?>"
                                    alt="<?php echo htmlspecialchars($player['full_name']); ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="card-profile-photo d-flex align-items-center justify-content-center bg-light" style="display: none !important;">
                                    <i class="fas fa-user-tie" style="font-size: 5.5rem; color: #94a3b8;"></i>
                                </div>
                            <?php else: ?>
                                <div class="card-profile-photo d-flex align-items-center justify-content-center bg-light">
                                    <i class="fas fa-user-tie" style="font-size: 5.5rem; color: #94a3b8;"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="batting-badge-gold mb-2">
                            <i class="fas fa-cricket me-2"></i><span><?php echo strtoupper($player['batting_type'] ? $player['batting_type'] . ' BATSMAN' : 'RIGHT HANDED BATSMAN'); ?></span>
                        </div>

                        <div class="card-name-v2">
                            <h2 class="card-fullname">
                                <?php echo strtoupper($player['full_name']); ?>
                            </h2>
                            <span class="card-nickname">
                                <?php echo strtoupper($player['jersey_nickname']); ?>
                            </span>
                        </div>

                        <div class="card-info-box">
                            <div class="info-box">
                                <i class="fas fa-users mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                                <span class="info-label">Category</span>
                                <span class="info-value">
                                    <?php echo $player['category']; ?>
                                </span>
                            </div>
                            <div class="info-box">
                                <i class="fas fa-baseball-ball mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                                <span class="info-label">Role</span>
                                <span class="info-value">
                                    <?php echo $player['role']; ?>
                                </span>
                            </div>
                            <div class="info-box">
                                <i class="fas fa-shield-alt mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                                <span class="info-label">Experience</span>
                                <span class="info-value">
                                    <?php echo $player['experience']; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer-v2">
                        <div class="jersey-pill-badge">
                            <i class="fas fa-tshirt" style="color: #0284c7;"></i>
                            <span style="color: #64748b; font-size: 0.8rem; letter-spacing: 1.5px; font-weight: 700;">JERSEY</span>
                            <span class="jersey-num-text"># <?php echo $player['jersey_number']; ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Full Details Card (Middle) -->
            <div class="col-xl-4 col-lg-4 col-md-12">
                <div class="detail-card h-100">
                    <div class="card-header-title mb-4">
                        <span class="header-icon-box"><i class="fas fa-id-card"></i></span> FULL <span style="color: #0284c7; margin-left: 6px;">DETAILS</span>
                    </div>

                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-phone-alt"></i> Mobile Number</span>
                        <span class="row-val">
                            <?php echo $player['mobile']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-envelope"></i> Email ID</span>
                        <span class="row-val">
                            <?php echo $player['email']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-running"></i> Batting Type</span>
                        <span class="badge-pill-green">
                            <?php echo $player['batting_type']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-baseball-ball"></i> Bowling Type</span>
                        <span class="badge-pill-blue">
                            <?php echo $player['bowling_type']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-tshirt"></i> Jersey Size</span>
                        <span class="row-val">
                            <?php echo $player['jersey_size']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-walking"></i> Track Pant Size</span>
                        <span class="row-val">
                            <?php echo $player['track_pant_size']; ?>
                        </span>
                    </div>
                    <div class="detail-row-box">
                        <span class="row-label"><i class="fas fa-map-marker-alt"></i> Address</span>
                        <span class="row-val">
                            <?php echo nl2br($player['address']); ?>
                        </span>
                    </div>
                    <?php if ($player['prod_media_id']): ?>
                        <div class="detail-row-box">
                            <span class="row-label"><i class="fas fa-id-badge"></i> ID Proof</span>
                            <span class="row-val">
                                <a href="javascript:void(0)" class="fw-bold text-decoration-none view-id-card" style="color: #0284c7;"
                                    data-file="<?php echo $player['prod_media_id']; ?>">
                                    View ID Proof <i class="fas fa-expand-alt ms-1"></i>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 3. Player Statistics Card (Right) -->
            <div class="col-xl-4 col-lg-4 col-md-12">
                <div class="detail-card h-100">
                    <div class="stats-header-title mb-4">
                        <span class="title-line"></span>
                        <i class="fas fa-chart-bar" style="color: #0284c7;"></i> PLAYER <span style="color: #0284c7; margin-left: 6px;">STATISTICS</span>
                        <span class="title-line"></span>
                    </div>

                    <!-- Top Summary Stats Grid -->
                    <div class="stats-highlight-grid">
                        <div class="stat-highlight-box">
                            <i class="fas fa-cricket mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                            <span class="stat-highlight-num"><?php echo $stats['matches_played']; ?></span>
                            <span class="stat-highlight-label">Matches</span>
                        </div>
                        <div class="stat-highlight-box">
                            <i class="fas fa-running mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                            <span class="stat-highlight-num"><?php echo $stats['runs_scored']; ?></span>
                            <span class="stat-highlight-label">Runs</span>
                        </div>
                        <div class="stat-highlight-box">
                            <i class="fas fa-baseball-ball mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                            <span class="stat-highlight-num"><?php echo $stats['wickets']; ?></span>
                            <span class="stat-highlight-label">Wickets</span>
                        </div>
                        <div class="stat-highlight-box">
                            <i class="fas fa-star mb-1" style="color: #0284c7; font-size: 1.1rem;"></i>
                            <span class="stat-highlight-num"><?php echo $stats['highest_score']; ?></span>
                            <span class="stat-highlight-label">Highest</span>
                        </div>
                    </div>

                    <!-- Side-by-Side: Batting & Career and Bowling -->
                    <div class="row g-3 mb-2">
                        <!-- Batting & Career (Left column) -->
                        <div class="col-6">
                            <h6 class="stat-sec-title">
                                <i class="fas fa-cricket me-2"></i> BATTING & CAREER
                            </h6>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Innings</span>
                                <span class="stat-item-value"><?php echo $stats['innings']; ?></span>
                            </div>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Batting Avg</span>
                                <span class="stat-item-value"><?php echo $stats['batting_avg']; ?></span>
                            </div>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Strike Rate</span>
                                <span class="stat-item-value"><?php echo $stats['strike_rate']; ?></span>
                            </div>
                            <div class="stat-item-row">
                                <span class="stat-item-label">50s / 100s</span>
                                <span class="stat-item-value">
                                    <span style="color: #0284c7; font-weight: 800;"><?php echo $stats['fifties']; ?></span> / <span style="color: #0284c7; font-weight: 800;"><?php echo $stats['hundreds']; ?></span>
                                </span>
                            </div>
                        </div>

                        <!-- Bowling (Right column) -->
                        <div class="col-6">
                            <h6 class="stat-sec-title">
                                <i class="fas fa-bowling-ball me-2"></i> BOWLING
                            </h6>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Bowling Avg</span>
                                <span class="stat-item-value"><?php echo $stats['bowling_avg']; ?></span>
                            </div>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Economy</span>
                                <span class="stat-item-value"><?php echo $stats['economy']; ?></span>
                            </div>
                            <div class="stat-item-row">
                                <span class="stat-item-label">Best Bowling</span>
                                <span class="stat-item-value"><?php echo $stats['best_bowling']; ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Fielding Section (Bottom) -->
                    <h6 class="stat-sec-title mt-4">
                        <i class="fas fa-hand-paper me-2"></i> FIELDING
                    </h6>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="stat-item-row" style="border-bottom: none;">
                                <span class="stat-item-label">Catches</span>
                                <span class="stat-item-value"><?php echo $stats['catches']; ?></span>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="stat-item-row" style="border-bottom: none;">
                                <span class="stat-item-label">Stumpings</span>
                                <span class="stat-item-value"><?php echo $stats['stumpings']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal for ID Card -->
    <div class="modal fade" id="idCardModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">ID PROOF PREVIEW</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <img id="idCardViewer" src="" class="img-fluid d-none" alt="ID Proof"
                        style="max-height: 80vh; margin: 0 auto;">
                    <iframe id="pdfViewer" src="" class="d-none" style="width: 100%; height: 80vh;"></iframe>
                    <div id="fileDownloadLink" class="mt-3 d-none">
                        <a href="" class="btn btn-primary rounded-pill px-4" download style="background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%); border: none;">Download File</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            $('.view-id-card, .view-profile-pic').on('click', function () {
                const file = $(this).data('file');
                const ext = file.split('.').pop().toLowerCase();
                const $img = $('#idCardViewer');
                const $pdf = $('#pdfViewer');
                const $download = $('#fileDownloadLink');
                const myModal = new bootstrap.Modal(document.getElementById('idCardModal'));

                // Reset
                $img.addClass('d-none').attr('src', '');
                $pdf.addClass('d-none').attr('src', '');
                $download.addClass('d-none').find('a').attr('href', '');

                if (['jpg', 'jpeg', 'png', 'webp', 'gif'].includes(ext)) {
                    $img.attr('src', file).removeClass('d-none');
                } else if (ext === 'pdf') {
                    $pdf.attr('src', file).removeClass('d-none');
                } else {
                    $download.removeClass('d-none').find('a').attr('href', file);
                }

                myModal.show();
            });
        });
    </script>
</body>

</html>