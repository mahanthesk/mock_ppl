<?php
require_once '../auth.php';
check_access('auction_admin');

require 'db_connection.php';

$totalPointsPerTeam = 10000000;

$teamId = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

if ($teamId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch team
$tStmt = $pdo->prepare("SELECT * FROM team_master WHERE id = ?");
$tStmt->execute([$teamId]);
$team = $tStmt->fetch(PDO::FETCH_ASSOC);

if (!$team) {
    die("Team not found.");
}

$totalPointsPerTeam = (isset($team['total_purse']) && floatval($team['total_purse']) > 0) ? floatval($team['total_purse']) : 10000000;

// Fetch sold players
$pStmt = $pdo->prepare("SELECT * FROM registrations WHERE team_id = ? AND booking_status = 1 ORDER BY category ASC, player_id ASC");
$pStmt->execute([$teamId]);
$players = $pStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch icon players
$iStmt = $pdo->prepare("SELECT * FROM icon_players WHERE team_id = ? AND booking_status = 1");
$iStmt->execute([$teamId]);
$icons = $iStmt->fetchAll(PDO::FETCH_ASSOC);

$iconSpent = array_sum(array_column($icons, 'bid_points'));
$spent = array_sum(array_column($players, 'bid_points')) + $iconSpent;
$remaining = ($team['remaining_purse'] !== null) ? floatval($team['remaining_purse']) : max(0, $totalPointsPerTeam - $spent);

$iconName = $icons ? htmlspecialchars(implode(', ', array_column($icons, 'name'))) : 'Mystery Icon';
$iconImg = $icons ? htmlspecialchars($icons[0]['image']) : 'assets/image-not-found.png';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> – Final Squad · PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        :root {
            --gold: #0284c7;
            --gold-gradient: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            --glass: #ffffff;
            --glass-border: rgba(2, 132, 199, 0.2);
        }

        body {
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        /* ── HEADER ── */
        .custom-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background: rgba(255, 255, 255, 0.95);
            border-bottom: 1px solid rgba(2, 132, 199, 0.2);
            position: sticky;
            top: 0;
            z-index: 1000;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);
        }

        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .top-logo {
            height: 60px;
        }

        .left-logo {
            height: 60px;
        }

        .right-logo {
            height: 50px;
        }

        .header-center h1 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.6rem;
            font-weight: 800;
            margin: 0;
            color: #0f2a4a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            line-height: 1.2;
            text-align: center;
        }

        /* ── TEAM HERO BANNER ── */
        .team-hero {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 24px;
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 20px;
            padding: 24px;
            margin-bottom: 24px;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        /* Left – Owner */
        .hero-person {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            text-align: center;
        }

        .hero-person.right-side {
            flex-direction: column;
        }

        .hero-person img {
            height: 180px;
            width: 180px;
            object-fit: cover;
            border-radius: 14px;
            border: 2px solid #0284c7;
            flex-shrink: 0;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.15);
        }

        .hero-person .person-label {
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            color: #0284c7;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .hero-person .person-name {
            font-weight: 700;
            font-size: 1.4rem;
            color: #0f2a4a;
        }

        .icon-name-gold {
            color: #0284c7;
        }

        /* Center – Team logo + name */
        .hero-center {
            text-align: center;
        }

        .hero-center img {
            height: 140px;
            object-fit: contain;
            filter: drop-shadow(0 4px 10px rgba(15, 42, 74, 0.1));
            margin-bottom: 10px;
        }

        .hero-center h2 {
            font-family: 'Poppins', sans-serif;
            font-size: 1.8rem;
            font-weight: 800;
            color: #0f2a4a;
            letter-spacing: -0.5px;
            text-transform: uppercase;
            margin: 0 0 8px;
        }

        .hero-stats {
            display: flex;
            gap: 20px;
            justify-content: center;
            font-size: 0.85rem;
            color: #64748b;
            font-weight: 600;
        }

        .hero-stats span {
            white-space: nowrap;
        }

        .hero-stats .highlight {
            color: #059669;
        }

        /* ── TABLE ── */
        .table-container {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        .table {
            color: #0f2a4a;
            margin-bottom: 0;
            background-color: #ffffff !important;
            font-size: 0.85rem;
        }

        .table thead th {
            border-bottom: none !important;
            color: #ffffff !important;
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 14px 10px;
            background-color: #28779b !important;
            white-space: nowrap;
            text-transform: uppercase;
        }

        .table tbody td {
            border-bottom: 1px solid #f1f5f9 !important;
            padding: 12px 10px;
            vertical-align: middle;
            color: #334155 !important;
            background-color: #ffffff !important;
        }

        .player-row:hover td {
            background-color: #f8fafc !important;
        }

        .avatar-mini {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #0284c7;
        }

        .badge-cat {
            background: rgba(2, 132, 199, 0.1);
            color: #0284c7;
            border: 1px solid rgba(2, 132, 199, 0.25);
            font-size: 0.7rem;
            font-weight: 600;
            padding: 4px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }

        .points-badge {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.25);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .empty-note {
            text-align: center;
            padding: 44px;
            color: #94a3b8;
        }

        .back-btn {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f2a4a;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: all 0.2s;
        }

        .back-btn:hover {
            background: #f1f5f9;
            color: #0284c7;
            border-color: #0284c7;
        }

        @media (max-width: 768px) {
            .custom-header {
                flex-wrap: wrap;
                gap: 5px;
                padding: 10px;
            }

            .header-center h1 {
                font-size: 1.3rem;
            }

            .team-hero {
                grid-template-columns: 1fr;
                grid-template-rows: auto auto auto;
                text-align: center;
            }

            .hero-person,
            .hero-person.right-side {
                flex-direction: column;
                text-align: center;
            }

            .hero-center {
                order: -1;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>

    <!-- ── HEADER ── -->
    <div class="custom-header">
        <div class="header-left d-flex align-items-center">
            <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 55px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.6rem; color: #0f2a4a; letter-spacing: -0.5px;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-bold text-uppercase" style="letter-spacing: 2px; font-size: 0.75rem; color: #64748b;">Final Roster Summary</span>
        </div>
        <div class="header-right d-flex align-items-center">
            <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 42px; width: 42px; object-fit: contain;">
        </div>
    </div>

    <!-- <div class="container-fluid p-2" style="max-width: 1400px; margin: auto;"> -->
    <div class="p-2" style="margin: auto;">

        <!-- Back link -->
        <div class="mb-4 d-flex align-items-center gap-3 flex-wrap">
            <a href="team_details.php?id=<?php echo $teamId; ?>" class="back-btn">
                <i class="fas fa-arrow-left me-1"></i> Back to Team Details
            </a>
            <a href="team_grid.php?team_id=<?php echo $teamId; ?>" class="back-btn">
                <i class="fas fa-grip me-1"></i> Squad Grid
            </a>
            <h3 class="mb-0" style="font-family:'Cinzel',serif;color:var(--gold);font-size:1.1rem;letter-spacing:2px;">
                FINAL SQUAD
            </h3>
        </div>

        <!-- ── TEAM HERO BANNER ── -->
        <div class="team-hero">

            <!-- LEFT: Owner -->
            <div class="hero-person">
                <div>
                    <div class="person-label">Owner</div>
                    <div class="person-name"><?php echo htmlspecialchars($team['owner']); ?></div>
                </div>
            </div>

            <!-- CENTER: Team Logo + Name + Stats -->
            <div class="hero-center">
                <?php if ($team['team_logo']): ?>
                    <img src="team_assets/<?php echo htmlspecialchars($team['team_logo']); ?>"
                        alt="<?php echo htmlspecialchars($team['team_name']); ?>">
                <?php endif; ?>
                <h2><?php echo htmlspecialchars($team['team_name']); ?></h2>
                <div class="hero-stats">
                    <span><i class="fas fa-users me-1"></i><?php echo count($players); ?> / <?php echo isset($team['max_strength']) ? $team['max_strength'] : 13; ?> Players</span>
                    <span class="highlight"><i class="fas fa-coins me-1"></i><?php echo formatPoints($remaining); ?>
                        Remaining</span>
                </div>
            </div>

            <!-- RIGHT: Icon Player -->
            <div class="hero-person right-side">
                <div>
                    <div class="person-label">Icon Player</div>
                    <div class="person-name icon-name-gold"><?php echo $iconName; ?></div>
                </div>
            </div>

        </div>

        <!-- ── PLAYER TABLE ── -->
        <div class="table-container">
            <?php if (empty($players)): ?>
                <div class="empty-note">
                    <i class="fas fa-inbox fa-2x mb-3 d-block" style="color:#555;"></i>
                    No players purchased yet.
                </div>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <!-- <th>Photo</th> -->
                            <th>Player ID</th>
                            <th>Name</th>
                            <th>Phone Number</th>
                            <th>Category</th>
                            <th>Role</th>
                            <th>Batting</th>
                            <th>Bowling</th>
                            <th>Jersey #</th>
                            <th>Nickname</th>
                            <th>Jersey Size</th>
                            <th>Track Pant</th>
                            <th>Bid Points</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($players as $i => $p): ?>
                            <tr class="player-row">
                                <td class="text-secondary"><?php echo $i + 1; ?></td>
                                <td><span class="fw-bold" style="color: #0284c7;"><?php echo htmlspecialchars($p['player_id']); ?></span></td>
                                <td class="fw-bold" style="color: #0f2a4a;"><?php echo htmlspecialchars($p['full_name']); ?></td>
                                <td style="color: #475569; font-weight: 500;"><?php echo htmlspecialchars($p['mobile']); ?></td>
                                <td><span class="badge-cat"><?php echo htmlspecialchars($p['category']); ?></span></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($p['role'] ?: '—'); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($p['batting_type'] ?: '—'); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($p['bowling_type'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($p['jersey_number'] ?: '—'); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($p['jersey_nickname'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($p['jersey_size'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($p['track_pant_size'] ?: '—'); ?></td>
                                <td><span class="points-badge"><?php echo formatPoints($p['bid_points']); ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

            <?php include 'footer.php'; ?>
        </div>

    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>