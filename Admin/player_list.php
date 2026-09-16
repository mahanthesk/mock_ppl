<?php
require_once '../auth.php';
check_access('auction_admin');

// player_list.php
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Players Gallery - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css?v=<?php echo time(); ?>">
    <style>
        /* Player Card Styles (White Sports Dashboard Theme) */
        .player-card-container {
            width: 100%;
            max-width: 380px;
            background: #ffffff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            color: #0f2a4a;
            padding: 0;
            margin: 0 auto 30px;
            box-shadow: 0 10px 25px rgba(15, 42, 74, 0.08);
            border: 1px solid rgba(2, 132, 199, 0.18);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            cursor: pointer;
            text-decoration: none;
        }

        .player-card-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(15, 42, 74, 0.15);
            border-color: #0284c7;
        }

        .card-bg-glow {
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle at center, rgba(2, 132, 199, 0.04) 0%, transparent 50%);
            pointer-events: none;
        }

        .card-accent-bar {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 60px;
            background: linear-gradient(to bottom, rgba(2, 132, 199, 0.1), transparent);
            clip-path: polygon(0 0, 100% 0, 100% 60%, 0 100%);
        }

        .card-header-v2 {
            padding: 20px 15px 5px;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .spl-text {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-size: 1.1rem;
            letter-spacing: 2px;
            font-weight: 800;
            display: block;
        }

        .card-photo-wrapper {
            position: relative;
            margin: 15px auto;
            padding: 4px;
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border-radius: 14px;
            width: fit-content;
        }

        .card-profile-photo {
            width: 160px;
            height: 160px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #f1f5f9;
        }

        .card-player-id {
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            background: #0f2a4a;
            color: #ffffff;
            font-size: 0.75rem;
            font-weight: 800;
            padding: 2px 12px;
            border-radius: 6px;
            white-space: nowrap;
            letter-spacing: 1px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-fullname {
            font-family: 'Poppins', sans-serif;
            font-size: 1.35rem;
            font-weight: 700;
            color: #0f2a4a;
            margin: 10px 0 0;
            text-align: center;
        }

        .card-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 15px;
            margin-top: 15px;
            border-top: 1px solid #e2e8f0;
            background: #f8fafc;
        }

        .info-box {
            text-align: center;
        }

        .info-label {
            font-size: 0.65rem;
            color: #0284c7;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f2a4a;
        }

        /* Status Badges */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 3;
            font-size: 0.65rem;
            padding: 4px 12px;
            border-radius: 30px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 1px;
        }

        .status-available {
            background: rgba(2, 132, 199, 0.12);
            border: 1px solid rgba(2, 132, 199, 0.4);
            color: #0284c7;
        }

        .status-sold {
            background: #10b981;
            color: #fff;
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.3);
        }

        .status-unsold {
            background: #ef4444;
            color: #fff;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }

        /* Nav Pills Styling */
        .nav-pills .nav-link {
            color: #475569;
            border: 1px solid #cbd5e1;
            margin: 0 5px 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 8px 20px;
            background: #ffffff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.03);
            transition: all 0.3s ease;
        }

        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            color: #ffffff;
            border-color: #0284c7;
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.3);
        }

        .nav-pills .nav-link:hover:not(.active) {
            background: #f1f5f9;
            color: #0284c7;
            border-color: #0284c7;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container-fluid my-5 px-lg-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: rgba(2, 132, 199, 0.1); border: 1px solid rgba(2, 132, 199, 0.3);">
                <i class="fas fa-users" style="color: #0284c7;"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: #0284c7; letter-spacing: 1.5px; text-transform: uppercase;">Player Database</span>
            </div>
            <h1 class="display-6 fw-bold mb-2" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">PLAYERS <span style="color: #0284c7;">GALLERY</span></h1>
            <p class="text-secondary small mx-auto" style="max-width: 550px;">
                Complete directory of registered players and celebrity icons across all tournament pools.
            </p>
        </div>

        <?php
        // Aggregate stats per category
        $statsQuery = "
            SELECT category,
                COUNT(*) as total,
                SUM(CASE WHEN booking_status = 1 THEN 1 ELSE 0 END) as sold,
                SUM(CASE WHEN booking_status = 2 THEN 1 ELSE 0 END) as unsold,
                SUM(CASE WHEN booking_status IS NULL OR (booking_status != 1 AND booking_status != 2) THEN 1 ELSE 0 END) as available
            FROM registrations
            WHERE deleted_at IS NULL
            GROUP BY category
            UNION ALL
            SELECT 'Celebrity Icon' as category,
                COUNT(*) as total,
                SUM(CASE WHEN booking_status = 1 THEN 1 ELSE 0 END) as sold,
                SUM(CASE WHEN booking_status = 2 THEN 1 ELSE 0 END) as unsold,
                0 as available
            FROM icon_players
        ";
        $statsStmt = $pdo->query($statsQuery);
        $statsData = [];
        $grandTotal = $grandSold = $grandUnsold = $grandAvail = 0;
        while ($s = $statsStmt->fetch()) {
            $avail = $s['total'] - $s['sold'] - $s['unsold'];
            $statsData[$s['category']] = [
                'total'    => $s['total'],
                'sold'     => $s['sold'],
                'unsold'   => $s['unsold'],
                'available'=> max(0, $avail)
            ];
            $grandTotal  += $s['total'];
            $grandSold   += $s['sold'];
            $grandUnsold += $s['unsold'];
            $grandAvail  += max(0, $avail);
        }
        ?>

        <!-- Stats Summary Cards -->
        <div class="row g-3 justify-content-center mb-4">
            <div class="col-6 col-md-3">
                <div class="text-center ppl-card py-3" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.15); border-radius: 16px; box-shadow: 0 4px 15px rgba(15, 42, 74, 0.05);">
                    <div class="h2 fw-bold mb-0" style="color: #0f2a4a; font-family: 'Poppins', sans-serif;"><?php echo $grandTotal; ?></div>
                    <div class="small fw-bold text-uppercase" style="color: #64748b; font-size: 0.75rem; letter-spacing: 1px;">TOTAL PLAYERS</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center ppl-card py-3" style="background: #ffffff; border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 16px; box-shadow: 0 4px 15px rgba(16, 185, 129, 0.06);">
                    <div class="h2 fw-bold mb-0" style="color: #059669; font-family: 'Poppins', sans-serif;"><?php echo $grandSold; ?></div>
                    <div class="small fw-bold text-uppercase" style="color: #059669; font-size: 0.75rem; letter-spacing: 1px;">SOLD</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center ppl-card py-3" style="background: #ffffff; border: 1px solid rgba(239, 68, 68, 0.2); border-radius: 16px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.06);">
                    <div class="h2 fw-bold mb-0" style="color: #dc2626; font-family: 'Poppins', sans-serif;"><?php echo $grandUnsold; ?></div>
                    <div class="small fw-bold text-uppercase" style="color: #dc2626; font-size: 0.75rem; letter-spacing: 1px;">UNSOLD</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="text-center ppl-card py-3" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.2); border-radius: 16px; box-shadow: 0 4px 15px rgba(2, 132, 199, 0.06);">
                    <div class="h2 fw-bold mb-0" style="color: #0284c7; font-family: 'Poppins', sans-serif;"><?php echo $grandAvail; ?></div>
                    <div class="small fw-bold text-uppercase" style="color: #0284c7; font-size: 0.75rem; letter-spacing: 1px;">AVAILABLE</div>
                </div>
            </div>
        </div>

        <!-- Category Navigation -->
        <div class="d-flex justify-content-center mb-3">
            <ul class="nav nav-pills" id="categoryTabs">
                <li class="nav-item"><a class="nav-link active" data-category="all" href="#">All Players</a></li>
                <?php
                $navCats = !empty($globalCategories) ? $globalCategories : [
                    'Top 20 Batters (Marquee A)',
                    'Top 20 Bowlers (Marquee B)',
                    'PPL PLayers (Marquee C)',
                    'New Players (Marquee D)'
                ];
                usort($navCats, function($a, $b) {
                    preg_match('/Marquee\s+([A-Z])/', $a, $mA);
                    preg_match('/Marquee\s+([A-Z])/', $b, $mB);
                    if (!empty($mA) && !empty($mB)) {
                        return strcmp($mA[1], $mB[1]);
                    }
                    return strcmp($a, $b);
                });
                foreach ($navCats as $catName) {
                    echo '<li class="nav-item"><a class="nav-link" data-category="' . htmlspecialchars($catName) . '" href="#">' . htmlspecialchars($catName) . '</a></li>';
                }
                if (isset($statsData['Celebrity Icon']) && $statsData['Celebrity Icon']['total'] > 0) {
                    echo '<li class="nav-item"><a class="nav-link" data-category="Celebrity Icon" href="#">Celebrity Icon</a></li>';
                }
                ?>
            </ul>
        </div>

        <!-- Per-Category Counts (PHP JSON passed to JS) -->
        <script>
            const categoryStats = <?php echo json_encode($statsData, JSON_NUMERIC_CHECK); ?>;
            const grandStats = { total: <?php echo $grandTotal; ?>, sold: <?php echo $grandSold; ?>, unsold: <?php echo $grandUnsold; ?>, available: <?php echo $grandAvail; ?> };
        </script>

        <!-- Live Stats Strip below nav pills -->
        <div id="category-stat-strip" class="d-flex justify-content-center gap-4 mb-4 py-2 px-4 rounded-pill shadow-sm mx-auto" style="background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(2, 132, 199, 0.2); width: fit-content; font-size: 0.85rem;">
            <span style="color: #0f2a4a; font-weight: 500;">Total: <strong id="stat-total" style="color: #0f2a4a;"><?php echo $grandTotal; ?></strong></span>
            <span style="color: #059669; font-weight: 500;">Sold: <strong id="stat-sold"><?php echo $grandSold; ?></strong></span>
            <span style="color: #dc2626; font-weight: 500;">Unsold: <strong id="stat-unsold"><?php echo $grandUnsold; ?></strong></span>
            <span style="color: #0284c7; font-weight: 500;">Available: <strong id="stat-avail"><?php echo $grandAvail; ?></strong></span>
        </div>

        <div class="row g-4" id="playersGallery">
            <?php
            try {
                $sql = "SELECT pr.player_id, pr.full_name, pr.profile_pic, pr.role, pr.category, pr.batting_type, pr.bowling_type, pr.jersey_nickname, pr.booking_status, t.team_name 
                        FROM registrations pr 
                        LEFT JOIN team_master t ON pr.team_id = t.id 
                        WHERE pr.deleted_at IS NULL 
                        UNION ALL
                        SELECT CONCAT('ICON-', i.id) as player_id, i.name as full_name, i.image as profile_pic, 'ICON' as role, 'Celebrity Icon' as category, 'N/A' as batting_type, 'N/A' as bowling_type, i.name as jersey_nickname, i.booking_status, t.team_name
                        FROM icon_players i
                        LEFT JOIN team_master t ON i.team_id = t.id
                        ORDER BY player_id ASC";
                $stmt = $pdo->query($sql);

                while ($row = $stmt->fetch()) {
                    $status = 'Available';
                    $statusClass = 'status-available';
                    if ($row['booking_status'] == 1) {
                        $status = 'SOLD (' . $row['team_name'] . ')';
                        $statusClass = 'status-sold';
                    } else if ($row['booking_status'] == 2 && strpos($row['player_id'], 'ICON-') === false) {
                        // Regular players can be unsold explicitly
                    }
                    
                    $link = (strpos($row['player_id'], 'ICON-') !== false) ? 'bid_icon.php' : 'bid_player.php?id=' . $row['player_id'];
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 player-item"
                        data-category="<?php echo htmlspecialchars($row['category']); ?>">
                        <a href="<?php echo $link; ?>" class="player-card-container">
                            <div class="card-bg-glow"></div>
                            <div class="card-accent-bar"></div>
                            <div class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></div>

                            <div class="card-header-v2">
                                <span class="spl-text">PRETIUM</span>
                                <small class="text-secondary small" style="letter-spacing: 2px;">PREMIER LEAGUE S2</small>
                            </div>

                            <div class="card-photo-wrapper">
                                <img src="<?php echo htmlspecialchars($row['profile_pic'] ?: 'assets/image-not-found.png'); ?>"
                                    class="card-profile-photo" onerror="this.onerror=null;this.src='assets/image-not-found.png';">
                                <div class="card-player-id"><?php echo htmlspecialchars($row['player_id']); ?></div>
                            </div>

                            <div class="card-name-v2 px-3">
                                <h3 class="card-fullname"><?php echo htmlspecialchars($row['full_name']); ?></h3>
                                <div class="text-warning small text-uppercase" style="letter-spacing: 1px;">
                                    <?php echo htmlspecialchars($row['jersey_nickname']); ?>
                                </div>
                            </div>

                            <div class="card-info-grid">
                                <div class="info-box">
                                    <span class="info-label">Role</span>
                                    <span class="info-value"><?php echo htmlspecialchars($row['role']); ?></span>
                                </div>
                                <div class="info-box">
                                    <span class="info-label">Category</span>
                                    <span class="info-value"><?php echo htmlspecialchars($row['category']); ?></span>
                                </div>
                                <div class="info-box">
                                    <span class="info-label">Batting</span>
                                    <span
                                        class="info-value text-truncate d-block px-1"><?php echo htmlspecialchars($row['batting_type']); ?></span>
                                </div>
                                <div class="info-box">
                                    <span class="info-label">Bowling</span>
                                    <span
                                        class="info-value text-truncate d-block px-1"><?php echo htmlspecialchars($row['bowling_type']); ?></span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo "<div class='col-12 text-center text-danger'>Error: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script>
        $(document).ready(function () {
            function updateStatStrip(category) {
                let stats;
                if (category === 'all') {
                    stats = grandStats;
                } else {
                    stats = categoryStats[category] || { total: 0, sold: 0, unsold: 0, available: 0 };
                }
                $('#stat-total').text(stats.total);
                $('#stat-sold').text(stats.sold);
                $('#stat-unsold').text(stats.unsold);
                $('#stat-avail').text(stats.available !== undefined ? stats.available : 0);
            }

            $('#categoryTabs .nav-link').on('click', function (e) {
                e.preventDefault();
                $('#categoryTabs .nav-link').removeClass('active');
                $(this).addClass('active');

                const category = $(this).data('category');
                if (category === 'all') {
                    $('.player-item').fadeIn(400);
                } else {
                    $('.player-item').hide();
                    $(`.player-item[data-category="${category}"]`).fadeIn(400);
                }
                updateStatStrip(category);
            });
        });
    </script>
</body>

</html>