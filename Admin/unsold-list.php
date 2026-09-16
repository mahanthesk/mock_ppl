<?php
require_once '../auth.php';
check_access('auction_admin');

// unsold-list.php
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsold Players List - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #dc2626;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 700;
        }

        .table-container {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            overflow: hidden;
            margin-top: 20px;
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
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            padding: 14px 10px;
            background-color: #28779b !important;
            white-space: nowrap;
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

        .view-btn {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid #0284c7;
            color: #0284c7;
            padding: 6px 18px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.3s;
        }

        .view-btn:hover {
            background: #0284c7;
            color: #ffffff;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: rgba(239, 68, 68, 0.12); border: 1px solid rgba(239, 68, 68, 0.3);">
                <i class="fas fa-clock-rotate-left" style="color: #dc2626;"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: #dc2626; letter-spacing: 1.5px; text-transform: uppercase;">Available For Re-Auction</span>
            </div>
            <h1 class="display-6 fw-bold mb-2" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">UNSOLD PLAYERS <span style="color: #0284c7;">LIST</span></h1>
            <p class="text-secondary small mx-auto" style="max-width: 550px;">
                Complete roster of unsold players in table view with category filtering.
            </p>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <label class="info-label mb-0" style="color: #0f2a4a; font-weight: 600;">Filter by Category:</label>
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
                </select>
                <a href="unsold.php" class="view-btn">
                    <i class="fas fa-th me-2"></i>View Grid
                </a>
            </div>
            <div>
                <span id="player-count-badge">0 Players</span>
            </div>
        </div>

        <div class="table-container">
            <table class="table" id="unsoldTable">
                <thead>
                    <tr>
                        <th>#</th>
                        <!-- <th>Photo</th> -->
                        <th>Player ID</th>
                        <th>Name</th>
                        <th>Category</th>
                        <th>Role</th>
                        <th>Batting</th>
                        <th>Bowling</th>
                        <th>Jersey #</th>
                        <th>Nickname</th>
                        <th>Jersey Size</th>
                        <th>Track Pant</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    try {
                        $sql = "SELECT * FROM registrations WHERE booking_status = 2 AND deleted_at IS NULL ORDER BY created_at DESC, id DESC";
                        $stmt = $pdo->query($sql);
                        $count = 0;

                        while ($row = $stmt->fetch()) {
                            $count++;
                            ?>
                            <tr class="player-row" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                                <td class="text-secondary"><?php echo $count; ?></td>
                                <!-- <td>
                                    <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="" class="avatar-mini" onerror="this.src='assets/image-not-found.png'">
                                </td> -->
                                <td><span class="fw-bold" style="color: #0284c7;"><?php echo htmlspecialchars($row['player_id']); ?></span></td>
                                <td>
                                    <a href="bid_player.php?id=<?php echo $row['player_id']; ?>"
                                        class="text-decoration-none fw-bold" style="color: #0f2a4a;">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </a>
                                </td>
                                <td><span class="badge-cat"><?php echo htmlspecialchars($row['category']); ?></span></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($row['role']); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($row['batting_type'] ?: '—'); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($row['bowling_type'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($row['jersey_number'] ?: '—'); ?></td>
                                <td style="color: #334155;"><?php echo htmlspecialchars($row['jersey_nickname'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($row['jersey_size'] ?: '—'); ?></td>
                                <td class="text-center" style="color: #334155;"><?php echo htmlspecialchars($row['track_pant_size'] ?: '—'); ?></td>
                            </tr>
                            <?php
                        }
                        if ($count === 0) {
                            echo '<tr><td colspan="12" class="text-center text-muted p-5">No unsold players.</td></tr>';
                        }
                    } catch (PDOException $e) {
                        echo "<tr><td colspan='12' class='text-danger'>Error: " . $e->getMessage() . "</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            function updateCount() {
                const visible = $('.player-row:visible').length;
                $('#player-count-badge').text(visible + ' Player' + (visible !== 1 ? 's' : ''));

                // Update row numbers for visible rows
                let i = 1;
                $('.player-row:visible').each(function () {
                    $(this).find('td:first').text(i++);
                });
            }

            // Initial count
            updateCount();

            $('#category-filter').on('change', function () {
                const selected = $(this).val();
                if (selected === 'all') {
                    $('.player-row').show();
                } else {
                    $('.player-row').hide();
                    $(`.player-row[data-category="${selected}"]`).show();
                }
                updateCount();
            });
        });
    </script>
</body>

</html>