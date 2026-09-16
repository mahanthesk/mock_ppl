<?php

require_once '../auth.php';
check_access('auction_admin');

require 'db_connection.php';

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    echo "Player not found.";
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Player - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link
        href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;700&family=Poppins:wght@300;400;600&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            color: #0284c7;
            text-decoration: none;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            font-size: 0.9rem;
            transition: all 0.25s;
            padding: 8px 16px;
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 30px;
            box-shadow: 0 2px 8px rgba(15, 42, 74, 0.05);
        }

        .back-btn:hover {
            color: #ffffff;
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            transform: translateX(-4px);
            box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);
        }

        /* Player Card Styles (White Sports Dashboard Theme) */
        #player-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            color: #0f2a4a;
            padding: 0;
            margin: 0 auto 30px;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
            border: 1px solid rgba(2, 132, 199, 0.18);
        }

        .card-header-v2 {
            padding: 24px 20px 10px;
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
            position: relative;
            margin-bottom: 20px;
            padding: 5px;
            background: linear-gradient(135deg, #0284c7 0%, #0f2a4a 100%);
            border-radius: 16px;
            box-shadow: 0 8px 20px rgba(15, 42, 74, 0.12);
        }

        .card-profile-photo {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 12px;
            display: block;
            background: #f1f5f9;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .card-profile-photo:hover {
            transform: scale(1.04);
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
            box-shadow: 0 4px 10px rgba(15, 42, 74, 0.2);
        }

        .card-name-v2 {
            text-align: center;
            margin-bottom: 18px;
        }

        .card-fullname {
            font-family: 'Poppins', sans-serif;
            font-size: 1.4rem;
            font-weight: 800;
            color: #0f2a4a;
            margin: 0;
            line-height: 1.2;
        }

        .card-nickname {
            font-size: 0.82rem;
            color: #0284c7;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 600;
            margin-top: 4px;
            display: block;
        }

        .card-info-grid {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding: 16px;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid rgba(2, 132, 199, 0.12);
            margin-bottom: 20px;
        }

        .info-box {
            text-align: center;
        }

        .info-label {
            font-size: 0.62rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
            margin-bottom: 4px;
            display: block;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 700;
            color: #0f2a4a;
        }

        .card-footer-v2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px 24px;
            background: #f8fafc;
            border-top: 1px solid rgba(2, 132, 199, 0.1);
            position: relative;
            z-index: 2;
        }

        .jersey-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .jersey-num-text {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0284c7;
            font-family: 'Poppins', sans-serif;
        }

        .card-city-text {
            font-size: 0.72rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
        }

        /* Detail Card */
        .detail-card {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.18);
            border-radius: 20px;
            padding: 32px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .detail-label {
            color: #64748b;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #0f2a4a;
            font-weight: 700;
            text-align: right;
            font-size: 0.95rem;
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

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 4px;
            }

            .detail-value {
                text-align: left;
            }
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2">
        <div class="header-left d-flex align-items-center">
            <a href="dashboard.php" class="text-decoration-none">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 52px; filter: drop-shadow(0 2px 8px rgba(2, 132, 199, 0.3));" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
            </a>
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.5rem; letter-spacing: -0.5px; color: #0f2a4a;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-semibold text-uppercase" style="letter-spacing: 2px; font-size: 0.72rem; color: #0284c7;">Player Profile</span>
        </div>
        <div class="header-right d-flex align-items-center gap-3">
            <img src="../assets/pretium-company-logo.png" alt="Company Logo" style="height: 36px; width: 36px; object-fit: contain;" onerror="this.onerror=null;this.src='assets/pretium-company-logo.png';">
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <a href="admin_players.php" class="back-btn mb-4">
            <i class="fas fa-arrow-left"></i> BACK TO LIST
        </a>
        <div class="row">
            <div class="col-md-6">
                <!-- Player Card -->
                <div id="player-card">
                    <div class="card-bg-glow"></div>
                    <div class="card-accent-bar"></div>

                    <div class="card-header-v2">
                        <div class="card-spl-info">
                            <span class="spl-text">PRETIUM</span>
                            <span class="season-text">PREMIER LEAGUE</span>
                        </div>
                    </div>

                    <div class="card-body-v2">
                        <div class="card-photo-wrapper">
                            <img src="<?php echo $player['profile_pic'] ?: 'assets/image-not-found.png'; ?>"
                                class="card-profile-photo view-profile-pic"
                                data-file="<?php echo $player['profile_pic'] ?: 'assets/image-not-found.png'; ?>
                                title="Click to view full image">
                            <div class="card-player-id">
                                <?php echo $player['player_id']; ?>
                            </div>
                        </div>

                        <div class="card-name-v2">
                            <h2 class="card-fullname">
                                <?php echo $player['full_name']; ?>
                            </h2>
                            <span class="card-nickname">
                                <?php echo $player['jersey_nickname']; ?>
                            </span>
                        </div>

                        <div class="card-info-grid">
                            <div class="info-box">
                                <span class="info-label">Category</span>
                                <span class="info-value">
                                    <?php echo $player['category']; ?>
                                </span>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Role</span>
                                <span class="info-value">
                                    <?php echo $player['role']; ?>
                                </span>
                            </div>
                            <div class="info-box">
                                <span class="info-label">Experience</span>
                                <span class="info-value">
                                    <?php echo $player['experience']; ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer-v2">
                        <div class="jersey-badge">
                            <span class="info-label me-2">Jersey</span>
                            <span class="jersey-num-text">#
                                <?php echo $player['jersey_number']; ?>
                            </span>
                        </div>
                        <div class="card-city-text">Official Player</div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <!-- Additional Details -->
                <div class="detail-card">
                    <h3 class="fw-bold mb-4"
                        style="font-family: 'Poppins', sans-serif; color: #0f2a4a; border-bottom: 2px solid rgba(2, 132, 199, 0.2); padding-bottom: 12px; letter-spacing: -0.5px;">
                        Full <span style="color: #0284c7;">Details</span></h3>

                    <div class="detail-row">
                        <span class="detail-label">Mobile Number</span>
                        <span class="detail-value">
                            <?php echo $player['mobile']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Email ID</span>
                        <span class="detail-value">
                            <?php echo $player['email']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Batting Type</span>
                        <span class="detail-value">
                            <?php echo $player['batting_type']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Bowling Type</span>
                        <span class="detail-value">
                            <?php echo $player['bowling_type']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Jersey Size</span>
                        <span class="detail-value">
                            <?php echo $player['jersey_size']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Pant Size</span>
                        <span class="detail-value">
                            <?php echo $player['track_pant_size']; ?>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Address</span>
                        <span class="detail-value">
                            <?php echo nl2br($player['address']); ?>
                        </span>
                    </div>
                    <?php if ($player['prod_media_id']): ?>
                        <div class="detail-row">
                            <span class="detail-label">ID Proof</span>
                            <span class="detail-value">
                                <a href="javascript:void(0)" class="fw-bold text-decoration-none view-id-card" style="color: #0284c7;"
                                    data-file="<?php echo $player['prod_media_id']; ?>">
                                    View ID Proof <i class="fas fa-expand-alt ms-1"></i>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                    <div class="detail-row">
                        <span class="detail-label">Registered On</span>
                        <span class="detail-value">
                            <?php echo date('M d, Y h:i A', strtotime($player['created_at'])); ?>
                        </span>
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
                        <a href="" class="btn btn-outline-warning" download>Download File</a>
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