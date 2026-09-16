<?php
require_once '../auth.php';
check_access('auction_admin');

require 'db_connection.php';

$teamId = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;

if ($teamId <= 0) {
    header('Location: dashboard.php');
    exit;
}

// Fetch team
$tStmt = $pdo->prepare("SELECT * FROM team_master WHERE id = ?");
$tStmt->execute([$teamId]);
$team = $tStmt->fetch(PDO::FETCH_ASSOC);
if (!$team)
    die("Team not found.");

// Fetch sold players
$pStmt = $pdo->prepare("SELECT * FROM registrations WHERE team_id = ? AND booking_status = 1 ORDER BY category ASC, player_id ASC");
$pStmt->execute([$teamId]);
$players = $pStmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch icon players
$iStmt = $pdo->prepare("SELECT * FROM icon_players WHERE team_id = ? AND booking_status = 1");
$iStmt->execute([$teamId]);
$icons = $iStmt->fetchAll(PDO::FETCH_ASSOC);

$iconName = $icons ? htmlspecialchars(implode(', ', array_column($icons, 'name'))) : 'Mystery Icon';
$iconImg = $icons ? htmlspecialchars($icons[0]['image']) : 'assets/image-not-found.png';
$iconSpent = array_sum(array_column($icons, 'bid_points'));
$spent = array_sum(array_column($players, 'bid_points')) + $iconSpent;
$totalPurse = (isset($team['total_purse']) && floatval($team['total_purse']) > 0) ? floatval($team['total_purse']) : 10000000;
$remaining = max(0, $totalPurse - $spent);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($team['team_name']); ?> – Squad Grid · PPL</title>
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

        * {
            box-sizing: border-box;
        }

        body {
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        /* ── TOP HEADER ── */
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
            text-align: center;
        }

        /* ── TEAM HERO ── */
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

        .hero-person {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-direction: column;
            text-align: center;
        }

        .hero-person.right-side {
            flex-direction: column;
            text-align: center;
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

        .person-label {
            font-family: 'Poppins', sans-serif;
            font-size: 0.75rem;
            color: #0284c7;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 4px;
        }

        .person-name {
            font-weight: 700;
            font-size: 1.4rem;
            color: #0f2a4a;
        }

        .hero-center {
            text-align: center;
        }

        .hero-center .team-logo-img {
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

        .hero-stats .highlight {
            color: #059669;
        }

        /* ── PLAYER GRID ── */
        .section-title {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 1.1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin-bottom: 20px;
            padding-bottom: 8px;
            border-bottom: 2px solid rgba(2, 132, 199, 0.2);
        }

        .players-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 18px;
            justify-content: center;
        }

        .player-card {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.18);
            border-radius: 16px;
            overflow: hidden;
            transition: transform 0.25s, box-shadow 0.25s;
            position: relative;
            width: 165px;
            flex-shrink: 0;
            box-shadow: 0 4px 15px rgba(15, 42, 74, 0.06);
        }

        .player-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 16px 30px rgba(15, 42, 74, 0.12);
            border-color: #0284c7;
        }

        .player-card .card-photo {
            width: 100%;
            aspect-ratio: 3 / 4;
            object-fit: cover;
            display: block;
            background: #f1f5f9;
        }

        .player-card .card-body-inner {
            padding: 10px 10px 12px;
            background: #ffffff;
            border-top: 1px solid #f1f5f9;
        }

        .player-card .card-name {
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            color: #0f2a4a;
            font-weight: 700;
            margin-bottom: 2px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-card .card-meta {
            font-size: 0.7rem;
            color: #64748b;
            font-weight: 500;
            margin-bottom: 4px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .player-card .card-points {
            font-size: 0.8rem;
            color: #059669;
            font-weight: 700;
        }

        /* All Together view – centred name + role */
        .card-body-center {
            text-align: center;
            padding: 10px 8px 14px;
        }

        .card-name-gold {
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            color: #0f2a4a !important;
            font-weight: 700;
            margin-bottom: 5px;
            white-space: normal;
            line-height: 1.3;
        }

        .card-role {
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .cat-pill {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.3);
            color: #0284c7;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 20px;
            font-family: 'Poppins', sans-serif;
            display: inline-block;
        }

        .jersey-badge {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #0f2a4a;
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Poppins', sans-serif;
            box-shadow: 0 2px 6px rgba(0,0,0,0.2);
        }

        /* Icon player special card */
        .icon-card {
            border-color: #0284c7;
            box-shadow: 0 4px 20px rgba(2, 132, 199, 0.15);
        }

        .icon-card .card-name {
            color: #0284c7;
        }

        .icon-ribbon {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: #ffffff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.65rem;
            letter-spacing: 1px;
            text-align: center;
            padding: 4px 0;
            font-weight: 700;
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

        .empty-note {
            text-align: center;
            padding: 60px;
            color: #94a3b8;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
        }

        /* ── VIEW TOGGLE ── */
        .view-toggle {
            display: flex;
            align-items: center;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            padding: 2px;
        }

        .toggle-btn {
            padding: 6px 18px;
            font-size: 0.8rem;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            border: none;
            border-radius: 25px;
            background: transparent;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
        }

        .toggle-btn.active {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: #ffffff;
            box-shadow: 0 2px 8px rgba(2, 132, 199, 0.3);
        }

        .toggle-btn:hover:not(.active) {
            color: #0284c7;
            background: #f8fafc;
        }

        #all-grid {
            display: none;
        }

        #grouped-view .section-separator {
            display: block;
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
                text-align: center;
            }

            .hero-person,
            .hero-person.right-side {
                flex-direction: column;
            }

            .hero-center {
                order: -1;
            }

            .players-grid {
                gap: 12px;
            }

            .player-card {
                width: 140px;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>

    <!-- ── HEADER ── -->
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2" style="background: rgba(255, 255, 255, 0.95); border-bottom: 1px solid rgba(2, 132, 199, 0.2); box-shadow: 0 4px 20px rgba(15, 42, 74, 0.05);">
        <div class="header-left d-flex align-items-center">
            <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 55px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.6rem; color: #0f2a4a; letter-spacing: -0.5px;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-bold text-uppercase" style="letter-spacing: 2px; font-size: 0.75rem; color: #64748b;">Squad Roster Grid</span>
        </div>
        <div class="header-right d-flex align-items-center">
            <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 42px; width: 42px; object-fit: contain;">
        </div>
    </div>

    <div class="container-fluid px-4 py-5" style="max-width: 1400px; margin: auto;">

        <!-- Back + nav -->
        <div class="mb-4 d-flex align-items-center gap-3 flex-wrap">
            <a href="team_details.php?id=<?php echo $teamId; ?>" class="back-btn">
                <i class="fas fa-arrow-left me-1"></i> Team Details
            </a>
            <a href="final_list.php?team_id=<?php echo $teamId; ?>" class="back-btn">
                <i class="fas fa-list-ul me-1"></i> Final List
            </a>
            <!-- View toggle -->
            <div class="view-toggle ms-auto">
                <button class="toggle-btn active" id="btn-grouped" onclick="setView('grouped')">
                    <i class="fas fa-layer-group me-1"></i> By Category
                </button>
                <button class="toggle-btn" id="btn-all" onclick="setView('all')">
                    <i class="fas fa-th me-1"></i> All Together
                </button>
            </div>
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

            <!-- CENTER: Logo + name + stats -->
            <div class="hero-center">
                <?php if ($team['team_logo']): ?>
                    <img src="team_assets/<?php echo htmlspecialchars($team['team_logo']); ?>"
                        alt="<?php echo htmlspecialchars($team['team_name']); ?>" class="team-logo-img" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
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
                    <div class="person-name"><?php echo $iconName; ?></div>
                </div>
            </div>
        </div>

        <!-- ── PLAYER GRID ── -->
        <?php if (empty($players) && empty($icons)): ?>
            <div class="empty-note">
                <i class="fas fa-inbox fa-3x mb-3 d-block" style="color:#444;"></i>
                No players purchased yet.
            </div>
        <?php else: ?>

            <?php
            $grouped = [];
            foreach ($players as $p) {
                $grouped[$p['category']][] = $p;
            }
            ?>

            <!-- ═══ GROUPED VIEW (by category) ═══ -->
            <div id="grouped-view">
                <?php foreach ($grouped as $category => $catPlayers): ?>
                    <div class="section-title">
                        <?php echo htmlspecialchars($category); ?>
                        <span
                            style="color:#555;font-size:0.75rem;letter-spacing:1px;margin-left:10px;">(<?php echo count($catPlayers); ?>)</span>
                    </div>
                    <div class="players-grid mb-5">
                        <?php foreach ($catPlayers as $p): ?>
                            <div class="player-card">
                                <?php if ($p['category']): ?>
                                    <div class="cat-pill"><?php echo htmlspecialchars($p['category']); ?></div><?php endif; ?>
                                <?php if ($p['jersey_number']): ?>
                                    <div class="jersey-badge"><?php echo htmlspecialchars($p['jersey_number']); ?></div><?php endif; ?>
                                <img src="<?php echo htmlspecialchars($p['profile_pic']); ?>"
                                    alt="<?php echo htmlspecialchars($p['full_name']); ?>" onerror="this.onerror=null;this.src=\'assets/image-not-found.png\';" class="card-photo"
                                    onerror="this.src='assets/image-not-found.png'">
                                <div class="card-body-inner">
                                    <div class="card-name"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                    <div class="card-meta"><?php echo htmlspecialchars($p['player_id']); ?><?php if ($p['role']): ?>
                                            · <?php echo htmlspecialchars($p['role']); ?><?php endif; ?></div>
                                    <div class="card-points">&#8377;<?php echo formatPoints($p['bid_points']); ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!empty($icons)): ?>
                    <div class="section-title">Celebrity Icon</div>
                    <div class="players-grid mb-5">
                        <?php foreach ($icons as $icon): ?>
                            <div class="player-card icon-card">
                                <div class="icon-ribbon">★ ICON PLAYER</div>
                                <img src="<?php echo htmlspecialchars($icon['image']); ?>"
                                    alt="<?php echo htmlspecialchars($icon['name']); ?>" class="card-photo"
                                    onerror="this.src='assets/image-not-found.png'">
                                <div class="card-body-inner">
                                    <div class="card-name"><?php echo htmlspecialchars($icon['name']); ?></div>
                                    <div class="card-meta">Icon Player</div>
                                    <?php if ($icon['bid_points']): ?>
                                        <div class="card-points">&#8377;<?php echo formatPoints($icon['bid_points']); ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ═══ ALL TOGETHER VIEW (flat grid) ═══ -->
            <div id="all-grid">
                <!-- <div class="section-title">
                    All Players
                    <span
                        style="color:#555;font-size:0.75rem;letter-spacing:1px;margin-left:10px;">(<?php echo count($players); ?>)</span>
                </div> -->
                <div class="players-grid mb-5">
                    <?php foreach ($players as $p): ?>
                        <div class="player-card">
                            <?php if ($p['category']): ?>
                                <img src="<?php echo htmlspecialchars($p['profile_pic']); ?>"
                                    alt="<?php echo htmlspecialchars($p['full_name']); ?>" onerror="this.onerror=null;this.src=\'assets/image-not-found.png\';" class="card-photo"
                                    onerror="this.src='assets/image-not-found.png'">
                                <div class="card-body-inner card-body-center">
                                    <div class="cat-pill mb-2"><?php echo htmlspecialchars($p['category']); ?></div><?php endif; ?>
                                    <div class="card-name card-name-gold"><?php echo htmlspecialchars($p['full_name']); ?></div>
                                <?php if ($p['role']): ?>
                                    <div class="card-role"><?php echo htmlspecialchars($p['role']); ?></div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setView(mode) {
            const grouped = document.getElementById('grouped-view');
            const all = document.getElementById('all-grid');
            const btnG = document.getElementById('btn-grouped');
            const btnA = document.getElementById('btn-all');
            if (mode === 'all') {
                grouped.style.display = 'none';
                all.style.display = 'block';
                btnA.classList.add('active');
                btnG.classList.remove('active');
            } else {
                all.style.display = 'none';
                grouped.style.display = 'block';
                btnG.classList.add('active');
                btnA.classList.remove('active');
            }
        }
    </script>
</body>

</html>