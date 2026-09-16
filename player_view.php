<?php
session_start();
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
        :root {
            --gold: #0284c7;
            --gold-gradient: linear-gradient(135deg, #0284c7 0%, #0ea5e9 100%);
            --black: #000000;
            --dark-gray: #1a1a1a;
            --glass: #ffffff;
            --glass-border: rgba(2, 132, 199, 0.2);
            --font-heading: 'Poppins', sans-serif;
            --font-body: 'Poppins', sans-serif;
        }

        body {
            color: #0f2a4a;
            font-family: var(--font-body);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

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

        .header-right .right-logo {
            height: 50px;
        }

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            color: #0f2a4a;
            padding: 8px 20px;
            border-radius: 30px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            transition: all 0.3s;
        }

        .back-btn:hover {
            background: #f1f5f9;
            color: #0284c7;
            border-color: #0284c7;
            transform: translateX(-3px);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .custom-header {
                flex-wrap: wrap;
                padding: 10px;
                gap: 5px;
            }

            .header-left {
                order: 1;
                gap: 10px;
            }

            .header-right {
                order: 2;
            }

            .header-center {
                order: 3;
                width: 100%;
                margin-top: 5px;
            }

            .top-logo {
                height: 50px;
            }

            .left-logo,
            .right-logo {
                height: 50px;
            }

            .header-center h1 {
                font-size: 1.3rem;
            }

            #player-card {
                width: 100%;
                max-width: 380px;
            }

            .detail-card {
                padding: 20px 15px;
                margin-top: 20px;
            }

            .detail-row {
                flex-direction: column;
                align-items: flex-start;
                gap: 5px;
            }

            .detail-value {
                text-align: left;
                width: 100%;
                font-size: 0.95rem;
            }
        }

        /* Player Card Styles */
        #player-card {
            width: 380px;
            background: #ffffff;
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            color: #0f2a4a;
            padding: 0;
            margin: 0 auto 30px;
            box-shadow: 0 15px 35px rgba(15, 42, 74, 0.08);
            border: 1px solid rgba(2, 132, 199, 0.2);
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
            height: 80px;
            background: linear-gradient(to bottom, rgba(2, 132, 199, 0.1), transparent);
            clip-path: polygon(0 0, 100% 0, 100% 60%, 0 100%);
        }

        .card-header-v2 {
            padding: 25px 20px 10px;
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
            font-size: 1.4rem;
            letter-spacing: 2px;
            font-weight: 800;
            display: block;
            margin-bottom: 2px;
        }

        .card-spl-info .season-text {
            font-size: 0.65rem;
            color: #0284c7;
            font-weight: 700;
            letter-spacing: 3px;
            text-transform: uppercase;
        }

        .card-body-v2 {
            padding: 0 25px;
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
            background: linear-gradient(135deg, #0284c7, #0ea5e9);
            border-radius: 14px;
            box-shadow: 0 8px 20px rgba(2, 132, 199, 0.2);
        }

        .card-profile-photo {
            width: 140px;
            height: 140px;
            object-fit: cover;
            border-radius: 10px;
            display: block;
            background: #f1f5f9;
            cursor: pointer;
            transition: transform 0.3s;
        }

        .card-profile-photo:hover {
            transform: scale(1.05);
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
            box-shadow: 0 4px 10px rgba(15, 42, 74, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .card-name-v2 {
            text-align: center;
            margin-bottom: 15px;
        }

        .card-fullname {
            font-family: 'Poppins', sans-serif;
            font-size: 1.5rem;
            font-weight: 700;
            color: #0f2a4a;
            margin: 0;
            line-height: 1.1;
        }

        .card-nickname {
            font-size: 0.8rem;
            color: #0284c7;
            letter-spacing: 1px;
            text-transform: uppercase;
            font-weight: 600;
        }

        .card-info-grid {
            width: 100%;
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 10px;
            padding: 15px 0;
            border-top: 1px solid #e2e8f0;
            margin-bottom: 20px;
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
            margin-bottom: 3px;
            display: block;
        }

        .info-value {
            font-size: 0.85rem;
            font-weight: 600;
            color: #0f2a4a;
        }

        .card-footer-v2 {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 25px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            position: relative;
            z-index: 2;
            width: 100%;
        }

        .jersey-badge {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .jersey-num-text {
            font-size: 1.2rem;
            font-weight: 800;
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
        }

        .card-city-text {
            font-size: 0.75rem;
            color: #64748b;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Detail List */
        .detail-card {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            padding: 30px;
            max-width: 600px;
            margin: 0 auto;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.08);
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            border-bottom: 1px solid #f1f5f9;
            padding-bottom: 10px;
        }

        .detail-label {
            color: #0284c7;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .detail-value {
            color: #0f2a4a;
            font-weight: 600;
            text-align: right;
        }

        /* Modal Custom Styling */
        .modal-content {
            background: #ffffff;
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);
            color: #0f2a4a;
        }

        .modal-header {
            border-bottom: 1px solid #f1f5f9;
            padding: 15px 20px;
        }

        .modal-title {
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
            color: #0f2a4a;
            letter-spacing: 0.5px;
        }

        .modal-body {
            padding: 20px;
            text-align: center;
        }

        #idCardViewer {
            max-width: 100%;
            max-height: 80vh;
            border-radius: 10px;
            border: 1px solid #cbd5e1;
        }

        #pdfViewer {
            width: 100%;
            height: 80vh;
            border: none;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <div class="custom-header d-flex align-items-center justify-content-between px-4 py-2">
        <div class="header-left d-flex align-items-center">
            <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="height: 55px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
        </div>
        <div class="header-center text-center">
            <h1 class="m-0 fw-bold" style="font-family: 'Poppins', sans-serif; font-size: 1.6rem; color: #0f2a4a; letter-spacing: -0.5px;">PRETIUM <span style="color: #0284c7;">PREMIER LEAGUE</span></h1>
            <span class="small fw-bold text-uppercase" style="letter-spacing: 2px; font-size: 0.75rem; color: #64748b;">Player Profile</span>
        </div>
        <div class="header-right d-flex align-items-center">
            <img src="assets/pretium-company-logo.png" alt="Company Logo" style="height: 42px; width: 42px; object-fit: contain;">
        </div>
    </div>

    <div class="container mt-4 mb-5">
        <a href="player_list.php" class="back-btn mb-4">
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
                            <?php if (!empty($player['profile_pic'])): ?>
                                <img src="<?php echo htmlspecialchars($player['profile_pic']); ?>"
                                    class="card-profile-photo view-profile-pic"
                                    data-file="<?php echo htmlspecialchars($player['profile_pic']); ?>
                                    alt="<?php echo htmlspecialchars($player['full_name']); ?>"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="card-profile-photo d-flex align-items-center justify-content-center bg-dark" style="display: none !important;">
                                    <i class="fas fa-user-tie" style="font-size: 5.5rem; color: rgba(58, 189, 217, 0.6);"></i>
                                </div>
                            <?php else: ?>
                                <div class="card-profile-photo d-flex align-items-center justify-content-center bg-dark">
                                    <i class="fas fa-user-tie" style="font-size: 5.5rem; color: rgba(58, 189, 217, 0.6);"></i>
                                </div>
                            <?php endif; ?>
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
                    <h3 class="mb-4"
                        style="font-family: 'Poppins', sans-serif; color: #0f2a4a; font-weight: 700; border-bottom: 2px solid rgba(2, 132, 199, 0.2); padding-bottom: 10px;">
                        Full Details</h3>

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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
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