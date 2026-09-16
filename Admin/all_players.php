<?php
require_once '../auth.php';
check_access('auction_admin');

// all_players.php
require_once 'db_connection.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Players Gallery - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
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
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: pointer;
            text-decoration: none;
        }

        .player-card-container:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(15, 42, 74, 0.15);
            border-color: #0284c7;
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
            letter-spacing: 1px;
            font-weight: 800;
            display: block;
        }

        .card-photo-wrapper {
            position: relative;
            margin: 15px auto;
            padding: 4px;
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
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
            padding: 3px 14px;
            border-radius: 20px;
            white-space: nowrap;
            letter-spacing: 0.5px;
            box-shadow: 0 3px 8px rgba(15, 42, 74, 0.2);
        }

        .card-fullname {
            font-family: 'Poppins', sans-serif;
            font-size: 1.25rem;
            font-weight: 700;
            color: #0f2a4a;
            margin: 10px 0 0;
            text-align: center;
        }

        .card-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            padding: 16px;
            margin-top: 15px;
            border-top: 1px solid rgba(2, 132, 199, 0.12);
            background: #f8fafc;
        }

        .info-box { text-align: center; }
        .info-label { font-size: 0.65rem; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; letter-spacing: 0.5px; }
        .info-value { font-size: 0.85rem; font-weight: 700; color: #0f2a4a; }

        /* Status Badges */
        .status-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            z-index: 3;
            font-size: 0.68rem;
            padding: 4px 12px;
            border-radius: 30px;
            text-transform: uppercase;
            font-weight: 800;
            letter-spacing: 0.5px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .status-available { background: #e0f2fe; border: 1px solid #bae6fd; color: #0284c7; }
        .status-sold { background: #dcfce7; border: 1px solid #bbf7d0; color: #15803d; }
        .status-unsold { background: #fee2e2; border: 1px solid #fecaca; color: #b91c1c; }

        /* Nav Pills Styling */
        .nav-pills .nav-link {
            color: #475569;
            border: 1px solid rgba(2, 132, 199, 0.2);
            margin: 0 5px 10px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.88rem;
            font-weight: 600;
            border-radius: 30px;
            padding: 8px 18px;
            background: #ffffff;
            transition: all 0.25s;
            box-shadow: 0 2px 8px rgba(15, 42, 74, 0.04);
        }
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, #0f2a4a 0%, #0284c7 100%);
            color: #ffffff;
            border-color: transparent;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.3);
        }
        .nav-pills .nav-link:hover:not(.active) {
            background: rgba(2, 132, 199, 0.08);
            border-color: #0284c7;
            color: #0284c7;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container-fluid my-5 px-lg-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold display-6" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">PLAYERS <span style="color: #0284c7;">GALLERY</span></h2>
            <p class="text-secondary small fw-medium">Explore all registered players and roster statistics</p>
        </div>
        
        <!-- Category Navigation -->
        <div class="d-flex justify-content-center mb-5">
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
                ?>
            </ul>
        </div>

        <div class="row g-4" id="playersGallery">
            <?php
            try {
                $sql = "SELECT pr.*, t.team_name 
                        FROM registrations pr 
                        LEFT JOIN team_master t ON pr.team_id = t.id 
                        WHERE pr.deleted_at IS NULL 
                        ORDER BY pr.created_at DESC, pr.id DESC";
                $stmt = $pdo->query($sql);
                
                while ($row = $stmt->fetch()) {
                    $status = 'Available';
                    $statusClass = 'status-available';
                    if ($row['booking_status'] === 1) {
                        $status = 'SOLD (' . $row['team_name'] . ')';
                        $statusClass = 'status-sold';
                    } else if ($row['booking_status'] == 2) {
                        $status = 'UNSOLD';
                        $statusClass = 'status-unsold';
                    }
                    ?>
                    <div class="col-xl-3 col-lg-4 col-md-6 player-item" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                        <a href="bid_player.php?id=<?php echo $row['player_id']; ?>" class="player-card-container">
                            <div class="status-badge <?php echo $statusClass; ?>"><?php echo $status; ?></div>
                            
                            <div class="card-header-v2">
                                <span class="spl-text">PRETIUM</span>
                                <small class="text-secondary small" style="letter-spacing: 1.5px; font-weight: 600;">PREMIER LEAGUE S2</small>
                            </div>

                            <div class="card-photo-wrapper">
                                <img src="<?php echo htmlspecialchars($row['profile_pic'] ?: 'assets/image-not-found.png'); ?>" class="card-profile-photo" onerror="this.onerror=null;this.src='assets/image-not-found.png';">
                                <div class="card-player-id"><?php echo htmlspecialchars($row['player_id']); ?></div>
                            </div>

                            <div class="card-name-v2 px-3">
                                <h3 class="card-fullname"><?php echo htmlspecialchars($row['full_name']); ?></h3>
                                <div class="small text-uppercase fw-semibold" style="letter-spacing: 1px; color: #0284c7; text-align: center;"><?php echo htmlspecialchars($row['jersey_nickname']); ?></div>
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
                                    <span class="info-value text-truncate d-block px-1"><?php echo htmlspecialchars($row['batting_type']); ?></span>
                                </div>
                                <div class="info-box">
                                    <span class="info-label">Bowling</span>
                                    <span class="info-value text-truncate d-block px-1"><?php echo htmlspecialchars($row['bowling_type']); ?></span>
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
        $(document).ready(function() {
            $('#categoryTabs .nav-link').on('click', function(e) {
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
            });
        });
    </script>
</body>
</html>
