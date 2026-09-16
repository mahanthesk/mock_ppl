<?php
require_once '../auth.php';
check_access('auction_admin');

// sold.php
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sold Players - Pretium Premier League</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
        .filter-bar {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            padding: 14px 22px;
            box-shadow: 0 10px 25px rgba(15, 42, 74, 0.05);
            backdrop-filter: blur(16px);
        }
        #category-filter {
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f2a4a;
            font-weight: 600;
            border-radius: 10px;
        }
        #category-filter option {
            background: #ffffff;
            color: #0f2a4a;
        }
        #player-count-badge {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.3);
            color: #0284c7;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <!-- Section Header -->
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: rgba(16, 185, 129, 0.12); border: 1px solid rgba(16, 185, 129, 0.3);">
                <i class="fas fa-circle-check" style="color: #059669;"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: #059669; letter-spacing: 1.5px; text-transform: uppercase;">Auction Results</span>
            </div>
            <h1 class="display-6 fw-bold mb-2" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">SOLD PLAYERS <span style="color: #0284c7;">ROSTER</span></h1>
            <p class="text-secondary small mx-auto" style="max-width: 550px;">
                Complete record of players auctioned off to franchise squads in the Pretium Premier League.
            </p>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <label class="info-label mb-0">Filter by Category:</label>
                <select id="category-filter" class="form-select form-select-sm w-auto">
                    <option value="all">All Categories</option>
                    <?php
                    if (!empty($globalCategories)) {
                        foreach ($globalCategories as $cat) {
                            echo '<option value="' . htmlspecialchars($cat) . '">' . htmlspecialchars($cat) . '</option>';
                        }
                    } else {
                        echo '<option value="Top 20 Batters (Marquee A)">Top 20 Batters (Marquee A)</option>';
                        echo '<option value="Top 20 Bowlers (Marquee B)">Top 20 Bowlers (Marquee B)</option>';
                        echo '<option value="PPL PLayers (Marquee C)">PPL PLayers (Marquee C)</option>';
                        echo '<option value="New Players (Marquee D)">New Players (Marquee D)</option>';
                    }
                    ?>
                    <option value="Celebrity Icon">Celebrity Icon</option>
                </select>
            </div>
            <div>
                <span id="player-count-badge">0 Players</span>
            </div>
        </div>

        <div class="row g-4" id="soldGrid">
            <?php
            try {
                $sql = "SELECT pr.player_id, pr.full_name, pr.profile_pic, pr.bid_points, pr.category, pr.role, t.team_name, t.team_logo
                        FROM registrations pr
                        JOIN team_master t ON pr.team_id = t.id
                        WHERE pr.booking_status = 1
                        UNION ALL
                        SELECT CONCAT('ICON-', i.id) as player_id, i.name as full_name, i.image as profile_pic, i.bid_points, 'Celebrity Icon' as category, 'Icon Player' as role, t.team_name, t.team_logo
                        FROM icon_players i
                        JOIN team_master t ON i.team_id = t.id
                        WHERE i.booking_status = 1
                        ORDER BY bid_points DESC";
                $stmt = $pdo->query($sql);

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        $roleName = !empty($row['role']) ? htmlspecialchars($row['role']) : 'Official Player';
                        $imgSrc = !empty($row['profile_pic']) ? htmlspecialchars($row['profile_pic']) : 'assets/image-not-found.png';
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 player-card-item" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                            <div class="ppl-player-card">
                                <!-- Top Bar -->
                                <div class="ppl-card-header-bar">
                                    <span class="ppl-player-id-pill"><?php echo htmlspecialchars($row['player_id']); ?></span>
                                    <span class="ppl-player-role-pill">
                                        <i class="fas fa-cricket-bat-ball text-cyan me-1"></i><?php echo $roleName; ?>
                                    </span>
                                </div>

                                <!-- Photo Box -->
                                <div class="ppl-card-image-box">
                                    <div class="stamp sold-stamp active">SOLD</div>
                                    <img src="<?php echo $imgSrc; ?>" alt="<?php echo htmlspecialchars($row['full_name']); ?>"
                                        onerror="this.onerror=null;this.src='assets/ppl-logo-transparent.png';">
                                    <div class="ppl-card-image-overlay"></div>
                                    <span class="ppl-category-pill"><?php echo htmlspecialchars($row['category']); ?></span>
                                </div>

                                <!-- Body -->
                                <div class="ppl-card-body">
                                    <h4 class="ppl-player-name" title="<?php echo htmlspecialchars($row['full_name']); ?>">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </h4>

                                    <div class="ppl-card-team-row">
                                        <div class="ppl-card-team-info">
                                            <?php if (!empty($row['team_logo'])): ?>
                                                <img src="team_assets/<?php echo htmlspecialchars($row['team_logo']); ?>" alt="Team Logo" class="ppl-card-team-logo" onerror="this.style.display='none';" onerror="this.onerror=null;this.src=\'assets/image-not-found.png\';">
                                            <?php endif; ?>
                                            <span class="ppl-card-team-name"><?php echo htmlspecialchars($row['team_name']); ?></span>
                                        </div>
                                        <div class="text-end">
                                            <span class="ppl-card-stat-label d-block">BOUGHT FOR</span>
                                            <span class="ppl-card-stat-value emerald">&#8377;<?php echo formatPoints(floatval($row['bid_points'])); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center text-muted py-5"><h3>No players sold yet.</h3></div>';
                }
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            function updateCount() {
                var visibleCount = $('.player-card-item:visible').length;
                $('#player-count-badge').text(visibleCount + ' Player' + (visibleCount !== 1 ? 's' : ''));
            }

            $('#category-filter').on('change', function() {
                var selectedCategory = $(this).val();
                if (selectedCategory === 'all') {
                    $('.player-card-item').fadeIn(200, updateCount);
                } else {
                    $('.player-card-item').hide();
                    $('.player-card-item[data-category="' + selectedCategory + '"]').fadeIn(200, updateCount);
                }
            });

            updateCount();
        });
    </script>
</body>

</html>