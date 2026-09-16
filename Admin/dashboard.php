<?php
require_once '../auth.php';
check_access('auction_admin');

// dashboard.php
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teams Auction Dashboard - Pretium Premier League</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <!-- Header Banner -->
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: rgba(58, 189, 217, 0.12); border: 1px solid rgba(58, 189, 217, 0.25);">
                <i class="fas fa-shield-halved" style="color: var(--ppl-cyan-bright);"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: var(--ppl-cyan-bright); letter-spacing: 1.5px; text-transform: uppercase;">Franchise Overview</span>
            </div>
            <h1 class="display-5 fw-bold mb-2">Teams Auction Dashboard</h1>
            <p class="text-secondary small mx-auto" style="max-width: 600px;">
                Real-time purse utilization, squad strength, and roster status across all Pretium Premier League franchises.
            </p>
        </div>

        <div class="row g-4">
            <?php
            try {
                $sql = "SELECT t.id, t.team_name, t.team_logo, t.owner, t.owner_img, t.total_purse, t.remaining_purse, t.max_strength,
                               (SELECT COALESCE(SUM(bid_points), 0) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS reg_points,
                               (SELECT COALESCE(SUM(bid_points), 0) FROM icon_players WHERE team_id = t.id AND booking_status = 1) AS icon_points,
                               (SELECT COUNT(id) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS total_players
                        FROM team_master t
                        ORDER BY t.team_name ASC";
                $stmt = $pdo->query($sql);

                while ($row = $stmt->fetch()) {
                    $totalSpent = floatval($row['reg_points']) + floatval($row['icon_points']);
                    $remainingPoints = ($row['remaining_purse'] !== null) ? floatval($row['remaining_purse']) : max(0, floatval($row['total_purse']) - $totalSpent);
                    $totalPlayers = intval($row['total_players']);
                    $maxStrength = intval($row['max_strength'] ?: 13);
                    $fillPercent = ($maxStrength > 0) ? min(100, round(($totalPlayers / $maxStrength) * 100)) : 0;
                    $logo = !empty($row['team_logo']) ? 'team_assets/' . $row['team_logo'] : 'assets/ppl-logo-transparent.png';
                    
                    // Parse Owner for display
                    $ownerArr = json_decode($row['owner'] ?? '', true);
                    $ownerDisplay = is_array($ownerArr) ? implode(', ', array_filter($ownerArr)) : ($row['owner'] ?? '');
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <a href="team_details.php?id=<?php echo $row['id']; ?>" class="text-decoration-none">
                            <div class="ppl-team-card">
                                <?php if (!empty($ownerDisplay)): ?>
                                    <div class="ppl-team-card-owner">
                                        <i class="fas fa-crown" style="color: var(--ppl-cyan-bright);"></i>
                                        <span><?php echo htmlspecialchars($ownerDisplay); ?></span>
                                    </div>
                                <?php else: ?>
                                    <div class="ppl-team-card-owner" style="visibility: hidden;">&nbsp;</div>
                                <?php endif; ?>

                                <div class="ppl-team-logo-wrapper">
                                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($row['team_name']); ?>" onerror="this.onerror=null;this.src=\'assets/image-not-found.png\';"
                                        onerror="this.onerror=null;this.src='assets/ppl-logo-transparent.png';">
                                </div>

                                <h3 class="ppl-team-card-title">
                                    <?php echo htmlspecialchars($row['team_name']); ?>
                                </h3>

                                <div class="ppl-team-metrics-box">
                                    <div class="ppl-team-metric-row">
                                        <span class="ppl-team-metric-label">TOTAL PURSE</span>
                                        <span class="ppl-team-metric-val">&#8377;<?php echo formatPoints(floatval($row['total_purse'])); ?></span>
                                    </div>
                                    <div class="ppl-team-metric-row">
                                        <span class="ppl-team-metric-label">REMAINING PURSE</span>
                                        <span class="ppl-team-metric-val cyan">&#8377;<?php echo formatPoints($remainingPoints); ?></span>
                                    </div>
                                    <div class="ppl-team-metric-row">
                                        <span class="ppl-team-metric-label">SQUAD STRENGTH</span>
                                        <span class="ppl-team-metric-val emerald"><?php echo $totalPlayers; ?> / <?php echo $maxStrength; ?></span>
                                    </div>
                                    <div class="ppl-progress">
                                        <div class="ppl-progress-bar" style="width: <?php echo $fillPercent; ?>%;"></div>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php
                }
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>