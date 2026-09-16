<?php
require_once '../auth.php';
check_access('auction_admin');
require_once 'bidding_config.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid Player - PPL Auction</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <link rel="stylesheet" href="auction_style.css">
    <style>
        body {
            background-image: url('assets/ppl-cricket-bg.png') !important;
            background-size: cover !important;
            background-position: center center !important;
            background-attachment: fixed !important;
            background-color: #f8fafc !important;
            color: #0f172a;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .search-container {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border: 1px solid #e2e8f0;
            border-radius: 20px;
            padding: 20px 24px;
            box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);
        }

        .player-img-container {
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .player-img-container:hover {
            box-shadow: 0 14px 35px -4px rgba(2, 132, 199, 0.18);
            transform: translateY(-2px);
            border-color: #0284c7;
        }

        .info-label {
            color: #64748b;
            font-family: 'Outfit', sans-serif;
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            font-weight: 700;
        }

        .info-value {
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f2a4a !important;
            font-family: 'Outfit', sans-serif;
        }

        #player-name-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 5px;
        }

        #player-display-id-badge {
            background: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 4px 14px;
            border-radius: 50px;
            font-weight: 800;
            font-size: 0.9rem;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 10px;
        }

        /* Layout Transitions */
        .col-transition {
            transition: all 0.7s cubic-bezier(0.25, 1, 0.5, 1);
        }

        .transition-height {
            transition: all 0.6s cubic-bezier(0.25, 1, 0.5, 1);
            transform-origin: top;
            overflow: hidden;
        }

        /* Default State */
        #player-card.layout-default #col-image { width: 41.666667%; }
        #player-card.layout-default #col-stats { width: 0%; opacity: 0; padding: 0 !important; border: none !important; }
        #player-card.layout-default #col-details { width: 58.333333%; }
        #player-card.layout-default #player-info-section { opacity: 1; max-height: 500px; transform: scaleY(1); }
        #player-card.layout-default #bottom-tabs { opacity: 0; pointer-events: none; transform: translateY(10px); }

        /* Stats State */
        #player-card.layout-stats #col-image { width: 28%; }
        #player-card.layout-stats #col-stats { width: 42%; opacity: 1; padding: 0 20px !important; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; }
        #player-card.layout-stats #col-details { width: 30%; border-start: none !important; padding-left: 20px !important; }
        #player-card.layout-stats #player-info-section { opacity: 0; max-height: 0; transform: scaleY(0); margin: 0 !important; }
        #player-card.layout-stats #bottom-tabs { opacity: 1; pointer-events: auto; transform: translateY(0); }
        #player-card.layout-stats .border-start { border-left: none !important; }

        /* Bottom Tabs Styling */
        .tab-btn {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            margin: 0 15px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 5px;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-family: 'Outfit', sans-serif;
        }
        .tab-btn.active {
            color: #0284c7;
            border-bottom: 2px solid #0284c7;
        }
        .tab-btn:hover { color: #0f2a4a; }

        .stats-card-container {
            background: #ffffff;
            border-radius: 18px;
            padding: 22px;
            color: #0f172a;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.05);
            min-width: 380px;
        }

        .stats-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1.5px solid #f1f5f9;
            padding-bottom: 10px;
            margin-bottom: 12px;
        }

        .stats-card-title {
            color: #0f2a4a;
            font-family: 'Outfit', sans-serif;
            font-size: 1.05rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
            margin-bottom: 8px;
        }

        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 8px 6px;
            text-align: center;
            transition: all 0.2s ease;
        }

        .stat-box:hover {
            border-color: #0284c7;
            background: #f0f9ff;
            transform: translateY(-2px);
        }

        .stat-box.highlight-stat {
            background: #e0f2fe;
            border: 1px solid #bae6fd;
        }

        .stat-label {
            font-size: 0.68rem;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            font-weight: 700;
            font-family: 'Outfit', sans-serif;
        }

        .stat-val {
            font-size: 1.15rem;
            font-weight: 800;
            color: #0f2a4a;
            line-height: 1.2;
            font-family: 'Outfit', sans-serif;
        }

        .stat-box.highlight-stat .stat-val {
            color: #0284c7;
            font-size: 1.25rem;
        }

        /* Stamps */
        .stamp-container {
            position: relative;
        }
        .stamp {
            position: absolute;
            top: 40px;
            right: 40px;
            font-size: 3rem;
            font-weight: 900;
            font-family: 'Outfit', sans-serif;
            border-width: 6px;
            border-style: solid;
            border-radius: 12px;
            padding: 8px 24px;
            text-transform: uppercase;
            letter-spacing: 4px;
            opacity: 0;
            pointer-events: none;
            z-index: 100;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform: scale(2) rotate(-15deg);
        }
        .stamp.active {
            opacity: 1;
            transform: scale(1) rotate(-15deg);
        }
        .sold-stamp {
            color: #059669;
            border-color: #059669;
            background: rgba(220, 252, 231, 0.85);
            box-shadow: 0 0 30px rgba(5, 150, 105, 0.3);
        }
        .unsold-stamp {
            color: #dc2626;
            border-color: #dc2626;
            background: rgba(254, 226, 226, 0.85);
            box-shadow: 0 0 30px rgba(220, 38, 38, 0.3);
        }

        /* Quick bid pills */
        .quick-bid-btn {
            background: #f0f9ff;
            color: #0284c7;
            border: 1.5px solid #bae6fd;
            font-weight: 700;
            border-radius: 50px;
            transition: all 0.2s ease;
        }
        .quick-bid-btn:hover {
            background: #0284c7;
            color: #ffffff;
            border-color: #0284c7;
        }
    </style>
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-4 my-md-5">
        <!-- Search -->
        <div class="row justify-content-center mb-3">
            <div class="col-auto">
                <div class="badge rounded-pill px-3 py-2 pulse-animation" style="background: #dc2626; color: #fff; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;">
                    <i class="fas fa-circle me-1" style="font-size: 0.6rem;"></i> LIVE AUCTION
                </div>
            </div>
        </div>

        <!-- Search Section -->
        <div class="row justify-content-center align-items-center mb-4 g-3">
            <div class="col-md-2 text-center d-none d-md-block">
                <img src="assets/ppl-logo-transparent.png" alt="PPL Logo" style="max-height: 75px;" onerror="this.onerror=null;this.src='assets/PPL-Season-2.png';">
            </div>
            <div class="col-md-8">
                <div class="search-container">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-8">
                            <label class="info-label mb-1 d-flex align-items-center gap-2">
                                <i class="fas fa-search" style="color: #0284c7;"></i>
                                <span>Player ID or Name</span>
                            </label>
                            <input type="text" id="search-serial"
                                class="form-control" placeholder="e.g. 004, PL0004, or Player Name" style="height: 48px; border-radius: 12px; background: #f8fafc; border-color: #cbd5e1; color: #0f172a; font-weight: 600;">
                        </div>
                        <div class="col-md-4">
                            <label class="d-none d-md-block mb-1">&nbsp;</label>
                            <button id="search-button" class="btn w-100" style="height: 48px; border-radius: 50px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-family: 'Outfit', sans-serif; font-weight: 700; letter-spacing: 0.5px; border: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25);">
                                <i class="fas fa-magnifying-glass me-1"></i> Search Player
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-2 text-center d-none d-md-block">
                <img src="assets/pretium-company-logo.png" alt="Company Logo" style="max-height: 60px; object-fit: contain;">
            </div>
        </div>

        <!-- Player Card -->
        <div id="player-bid-container" class="d-none">
            <div class="row p-4 mx-0 stamp-container layout-default flex-nowrap" id="player-card" style="background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(16px); border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);">
                <!-- Stamps -->
                <div id="sold-stamp" class="stamp sold-stamp">SOLD</div>
                <div id="unsold-stamp" class="stamp unsold-stamp">UNSOLD</div>

                <!-- Left Column (Image) -->
                <div id="col-image" class="col-transition">
                    <div class="player-img-container" id="player-image-trigger" title="Click to view statistics">
                        <img id="player-image" src="assets/image-not-found.png" onerror="this.onerror=null;this.src='assets/image-not-found.png';" alt="Player" class="img-fluid w-100" style="height: 480px; object-fit: cover;">
                    </div>
                </div>

                <!-- Center Column (Stats) -->
                <div id="col-stats" class="col-transition overflow-hidden">
                    <div class="stats-card-container w-100">
                        <div class="stats-card-header">
                            <h4 class="stats-card-title">
                                <i class="fas fa-chart-bar" style="color: #0284c7;"></i> PLAYER STATISTICS
                            </h4>
                        </div>

                        <div class="stats-grid">
                            <div class="stat-box">
                                <div class="stat-label">Matches</div>
                                <div class="stat-val" id="stat-matches">0</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Innings</div>
                                <div class="stat-val" id="stat-innings">0</div>
                            </div>
                            <div class="stat-box highlight-stat">
                                <div class="stat-label">Runs</div>
                                <div class="stat-val" id="stat-runs">0</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Highest</div>
                                <div class="stat-val" id="stat-highest">0</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Bat Avg</div>
                                <div class="stat-val" id="stat-batavg">0.00</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Strike Rate</div>
                                <div class="stat-val" id="stat-sr">0.00</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">50s / 100s</div>
                                <div class="stat-val"><span id="stat-50s">0</span> / <span id="stat-100s">0</span></div>
                            </div>
                            <div class="stat-box highlight-stat">
                                <div class="stat-label">Wickets</div>
                                <div class="stat-val" id="stat-wickets">0</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Best Bowl</div>
                                <div class="stat-val" id="stat-bestbowl">-</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Bowl Avg</div>
                                <div class="stat-val" id="stat-bowlavg">0.00</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Economy</div>
                                <div class="stat-val" id="stat-economy">0.00</div>
                            </div>
                            <div class="stat-box">
                                <div class="stat-label">Ct / St</div>
                                <div class="stat-val"><span id="stat-catches">0</span> / <span id="stat-stumpings">0</span></div>
                            </div>
                        </div>

                        <!-- Bottom Tabs -->
                        <div id="bottom-tabs" class="mt-4 text-center transition-opacity" style="transition: opacity 0.5s ease 0.3s;">
                            <span class="tab-btn active" id="tab-stats">STATISTICS</span>
                            <span class="tab-btn" id="tab-photo">PHOTO</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column (Details + Bid) -->
                <div id="col-details" class="col-transition border-start ps-md-4" style="border-color: #e2e8f0 !important;">
                    <div id="player-info-section" class="transition-height">
                        <div id="player-name-row">
                            <div>
                                <h1 id="player-name" class="fw-bold mb-0" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 2.2rem;">Player Name</h1>
                                <p id="player-category" class="text-secondary fw-semibold mb-0" style="font-size: 1rem;">Category</p>
                            </div>
                            <div id="player-display-id-badge">---</div>
                        </div>

                        <hr class="mt-2 mb-3" style="border-color: #f1f5f9;">

                        <div class="row g-3 mb-4 text-center text-md-start">
                            <div class="col-6 col-md-4">
                                <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="info-label">Role</div>
                                    <div id="player-role" class="info-value">---</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="info-label">Batting</div>
                                    <div id="player-batting" class="info-value">---</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="info-label">Bowling</div>
                                    <div id="player-bowling" class="info-value">---</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="info-label">PPL Edition</div>
                                    <div id="player-ppl-edition" class="info-value">---</div>
                                </div>
                            </div>
                            <div class="col-6 col-md-4">
                                <div class="p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="info-label">Department</div>
                                    <div id="player-department" class="info-value">---</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="bid-wrapper">
                        <div id="bid-form-container" class="p-4 mt-2" style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 20px; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);">
                            <h3 class="mb-3 text-center fw-bold" style="font-size: 1.15rem; color: #0f2a4a; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px;">PLACE YOUR BID</h3>

                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label class="info-label d-block mb-1">Select Team</label>
                                    <select id="team-dropdown" class="form-select fs-6 py-2" style="border-radius: 12px; border-color: #cbd5e1; background: #f8fafc; color: #0f172a; font-weight: 600;">
                                        <option value="">Select a Team</option>
                                        <?php
                                        if (isset($teams)) {
                                            foreach ($teams as $team) {
                                                echo "<option value='{$team['id']}'>{$team['team_name']}</option>";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="info-label d-block mb-1">Bid Points</label>
                                    <div class="position-relative">
                                        <input type="text" id="bid-points-input"
                                            class="form-control fs-5"
                                            style="padding-right: 32px; border-radius: 12px; border-color: #cbd5e1; background: #f8fafc; color: #0f172a; font-weight: 700;"
                                            placeholder="Enter bid amount" inputmode="numeric" autocomplete="off">
                                        <div class="position-absolute end-0 top-0 bottom-0 d-flex flex-column my-1 me-1 overflow-hidden" style="width: 22px; z-index: 5; background: #ffffff; border-radius: 6px; border: 1px solid #cbd5e1;">
                                            <button id="bid-increment" type="button" class="btn p-0 border-0 flex-fill d-flex align-items-center justify-content-center" style="background: #ffffff; color: #334155; font-size: 8px; line-height: 1; border-bottom: 1px solid #e2e8f0 !important; border-radius: 0;" title="Increase bid">▲</button>
                                            <button id="bid-decrement" type="button" class="btn p-0 border-0 flex-fill d-flex align-items-center justify-content-center" style="background: #ffffff; color: #334155; font-size: 8px; line-height: 1; border-radius: 0;" title="Decrease bid">▼</button>
                                        </div>
                                    </div>
                                    <!-- Quick Increment Badges -->
                                    <div class="d-flex flex-wrap gap-1 mt-2 justify-content-center">
                                        <button type="button" class="btn btn-sm py-1 px-2 quick-bid-btn" data-add="25000" style="font-size: 0.75rem;">+25K</button>
                                        <button type="button" class="btn btn-sm py-1 px-2 quick-bid-btn" data-add="50000" style="font-size: 0.75rem;">+50K</button>
                                        <button type="button" class="btn btn-sm py-1 px-2 quick-bid-btn" data-add="100000" style="font-size: 0.75rem;">+1L</button>
                                        <button type="button" class="btn btn-sm py-1 px-2 quick-bid-btn" data-add="200000" style="font-size: 0.75rem;">+2L</button>
                                        <button type="button" class="btn btn-sm py-1 px-2 quick-bid-btn" data-add="500000" style="font-size: 0.75rem;">+5L</button>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div id="team-status" class="small mb-3 text-center p-2 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                    <div class="fw-bold" style="color: #0284c7;" id="rem-points">Remaining Points: ---</div>
                                    <div class="fw-bold" style="color: #d97706;" id="max-allowed">Max Bid Allowed: ---</div>
                                    <div class="text-secondary fw-semibold" id="team-strength">Players: ---</div>
                                </div>
                                <button id="submit-bid" class="btn w-100 fw-bold py-3 fs-5" style="border-radius: 50px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border: none; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);">
                                    <i class="fas fa-gavel me-2"></i> SUBMIT BID
                                </button>
                            </div>

                            <div class="mt-3 pt-3 border-top d-flex justify-content-center" style="border-color: #f1f5f9 !important;">
                                <button id="mark-unsold-btn" class="btn btn-sm px-4 rounded-pill" style="border: 1.5px solid #fca5a5; color: #dc2626; background: #fef2f2; font-family: 'Outfit', sans-serif; font-weight: 700;">
                                    <i class="fas fa-ban me-1"></i> Mark as Unsold
                                </button>
                            </div>
                        </div>

                        <!-- Bidding Info (if already sold) -->
                        <div id="bidding-details" class="mt-4 d-none">
                            <div class="text-center p-4 rounded-4" style="background: #f0fdf4; border: 2px solid #86efac;">
                                <div class="mb-3">
                                    <img id="sold-team-logo" src="assets/image-not-found.png" onerror="this.onerror=null;this.src='assets/image-not-found.png';" alt="Team Logo" style="height: 90px; object-fit: contain;">
                                </div>
                                <div class="fw-bold mb-1 text-uppercase" style="letter-spacing: 1.5px; font-size: 0.8rem; color: #059669; font-family: 'Outfit', sans-serif;">SOLD TO</div>
                                <div class="h4 mb-3 fw-bold" id="sold-to-team" style="color: #0f2a4a; font-family: 'Outfit', sans-serif;">---</div>
                                <div class="d-inline-block px-4 py-2 rounded-3" style="background: #ffffff; border: 1.5px solid #bbf7d0;">
                                    <div class="text-secondary fw-bold" style="letter-spacing: 1.5px; font-size: 0.72rem; font-family: 'Outfit', sans-serif;">BID AMOUNT</div>
                                    <div class="fw-bold" style="font-size: 2rem; font-family: 'Outfit', sans-serif; color: #059669;">&#8377;<span id="sold-for-points">---</span></div>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <button id="revert-bid-btn" class="btn btn-sm rounded-pill px-4" style="border: 1.5px solid #cbd5e1; background: #ffffff; color: #475569; font-family: 'Outfit', sans-serif; font-weight: 700;">
                                    <i class="fas fa-edit me-1"></i> Correct / Revert Bid
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="bidSuccessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-body text-center p-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 72px; height: 72px; background: #dcfce7; color: #059669; font-size: 2rem;">
                        <i class="fas fa-check"></i>
                    </div>
                    <h2 class="fw-bold mb-2" style="font-family: 'Outfit', sans-serif; color: #059669;">CONGRATULATIONS!</h2>
                    <p class="h5 text-secondary mb-4">Player has been <span class="fw-bold" style="color: #0284c7;">SOLD</span> successfully.</p>
                    <button type="button" class="btn px-4 rounded-pill" data-bs-dismiss="modal" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; font-family: 'Outfit', sans-serif; font-weight: 700;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="bidErrorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-body text-center p-5">
                    <div class="d-inline-flex align-items-center justify-content-center rounded-circle mb-3" style="width: 72px; height: 72px; background: #fee2e2; color: #dc2626; font-size: 2rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2 class="fw-bold mb-2 text-danger" style="font-family: 'Outfit', sans-serif;">ATTENTION!</h2>
                    <p class="h6 text-secondary mb-4" id="bidErrorText"></p>
                    <button type="button" class="btn btn-danger px-4 rounded-pill" data-bs-dismiss="modal" style="font-family: 'Outfit', sans-serif; font-weight: 700;">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Unsold Confirmation Modal -->
    <div class="modal fade" id="unsoldConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-header border-0 pb-0 justify-content-end">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center px-4 pb-4 pt-0">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 76px; height: 76px; background: #fee2e2; border: 2px solid #fca5a5;">
                            <i class="fas fa-user-slash text-danger" style="font-size: 2rem;"></i>
                        </div>
                    </div>
                    <h3 class="modal-title fw-bold mb-2" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">MARK AS UNSOLD?</h3>
                    <p class="text-secondary fs-6 mb-3">
                        Are you sure you want to mark <span id="unsoldModalPlayerName" class="fw-bold text-dark"></span> 
                        (<span id="unsoldModalPlayerId" class="fw-bold" style="color: #0284c7;"></span>) as <span class="text-danger fw-bold">UNSOLD</span>?
                    </p>
                    <div class="alert text-start small mb-4 py-2 px-3 text-secondary" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;">
                        <i class="fas fa-info-circle me-1" style="color: #0284c7;"></i> This player will be moved to the unsold pool for future auction rounds.
                    </div>
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal" style="min-width: 110px; font-family: 'Outfit', sans-serif; font-weight: 600;">Cancel</button>
                        <button type="button" id="confirm-unsold-btn" class="btn btn-danger rounded-pill px-4 fw-bold" style="min-width: 160px; font-family: 'Outfit', sans-serif;">
                            <i class="fas fa-check-circle me-1"></i> Yes, Mark Unsold
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>

    <script>
        $(document).ready(function () {

            function showErrorModal(message) {
                $('#bidErrorText').text(message);
                $('#bidErrorModal').modal('show');
            }

            // Points formatter: 1L, 10L, 1Cr
            function formatPoints(val) {
                if (val >= 10000000) return (val / 10000000).toFixed(2).replace(/\.00$/, '') + ' Cr';
                if (val >= 100000) return (val / 100000).toFixed(2).replace(/\.00$/, '') + ' L';
                return val.toLocaleString();
            }

            let lastFetchedData = null;
            let lastFetchedOriginalData = null;
            let isRevertMode = false;

            // Load teams into dropdown
            $.getJSON('fetch_teams.php', function (data) {
                const $dropdown = $('#team-dropdown');
                data.forEach(function (team) {
                    $dropdown.append($('<option>', {
                        value: team.id,
                        text: team.team_name
                    }));
                });
            });

            $('#search-button').on('click', function () {
                const serial = $('#search-serial').val().trim();
                if (!serial) return;

                let fullId = serial;

                // Player IDs are stored as PL0001, PL0002, PL0003, etc.
                if (/^\d+$/.test(serial)) {
                    // Pure number: pad to 4 digits and prefix with PL
                    fullId = 'PL' + serial.padStart(4, '0');
                } else {
                    const upper = serial.toUpperCase();
                    // Handle formats: PL004, PL0004, PL4 → normalize to PL0004
                    const plMatch = upper.match(/^PL(\d+)$/);
                    if (plMatch) {
                        fullId = 'PL' + plMatch[1].padStart(4, '0');
                    } else {
                        // Send as-is (could be a player name search)
                        fullId = upper;
                    }
                }

                fetchPlayer(fullId);
            });

            $('#search-serial').on('keypress', function (e) {
                if (e.which === 13) $('#search-button').click();
            });

            // Bidding Configuration Injected from Backend
            const biddingConfig = <?php echo getBiddingConfigJSON(); ?>;

            let currentDynamicBid = 0;
            let nextDynamicBid = 0;

            function formatCurrency(val) {
                return new Intl.NumberFormat('en-IN').format(val);
            }

            function getBasePrice(category) {
                const cat = (category || '').toUpperCase().trim();
                for (const key in biddingConfig.basePrices) {
                    if (cat.includes(key)) {
                        return biddingConfig.basePrices[key];
                    }
                }
                return 50000; // default
            }

            function calculateNextBid(currentBid) {
                // Slabs: boundary values belong to the HIGHER slab (>= check)
                if (currentBid < 500000) {
                    return currentBid + 25000;
                } else if (currentBid < 1000000) {
                    return currentBid + 50000;
                } else if (currentBid < 2000000) {
                    return currentBid + 100000;
                } else if (currentBid < 5000000) {
                    return currentBid + 250000;
                } else {
                    return currentBid + 500000;
                }
            }

            function getPrevBid(currentBid) {
                // Reverse: figure out what the previous step was
                if (currentBid <= 500000) {
                    return Math.max(0, currentBid - 25000);
                } else if (currentBid <= 1000000) {
                    return Math.max(0, currentBid - 50000);
                } else if (currentBid <= 2000000) {
                    return Math.max(0, currentBid - 100000);
                } else if (currentBid <= 5000000) {
                    return Math.max(0, currentBid - 250000);
                } else {
                    return Math.max(0, currentBid - 500000);
                }
            }

            // Helper: read raw numeric value from formatted bid input
            function getBidRawVal() {
                return parseFloat(($('#bid-points-input').val() || '').replace(/,/g, '')) || 0;
            }

            // Helper: set bid input with Indian number formatting
            function formatBidInput(val) {
                $('#bid-points-input').val(new Intl.NumberFormat('en-IN').format(val));
            }

            $('#bid-increment').on('click', function () {
                const next = calculateNextBid(getBidRawVal());
                formatBidInput(next);
                checkTeamBalance();
            });

            $('#bid-decrement').on('click', function () {
                const prev = getPrevBid(getBidRawVal());
                formatBidInput(prev);
                checkTeamBalance();
            });

            $(document).on('click', '.quick-bid-btn', function () {
                const addVal = parseFloat($(this).data('add')) || 0;
                const next = getBidRawVal() + addVal;
                formatBidInput(next);
                checkTeamBalance();
            });

            function updateBiddingUI(basePrice, currentBid) {
                currentDynamicBid = currentBid;
                nextDynamicBid = calculateNextBid(currentBid);

                // Pre-fill the manual bid input with the base/current price
                formatBidInput(currentBid);

                // If user changes team, check balance
                checkTeamBalance();
            }

            function checkTeamBalance() {
                if (!lastFetchedData) return;
                const bidVal = getBidRawVal();
                $('#rem-points').html('Remaining Points: <span class="text-white fw-bold">₹' + formatPoints(lastFetchedData.remaining_points) + '</span>');

                if (bidVal > lastFetchedData.max_bid) {
                    $('#rem-points').append(' <span class="text-danger small d-block">(Bid exceeds Max Allowed)</span>');
                    $('#submit-bid').prop('disabled', true);
                } else {
                    $('#submit-bid').prop('disabled', false);
                }
            }

            // Re-check balance whenever bid input changes
            $(document).on('input', '#bid-points-input', function() {
                checkTeamBalance();
            });

            $(document).on('keydown', '#bid-points-input', function(e) {
                if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    $('#bid-increment').click();
                } else if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    $('#bid-decrement').click();
                }
            });

            function fetchPlayer(id) {
                $.ajax({
                    url: 'fetch_player.php',
                    type: 'GET',
                    data: { id: id },
                    dataType: 'json',
                    success: function(data) {
                        if (!data || !data.success) {
                            showErrorModal((data && data.error) ? data.error : 'Player not found.');
                            return;
                        }

                        lastFetchedOriginalData = data;
                        $('#player-bid-container').removeClass('d-none');
                        $('#player-name').text(data.player_name);
                        // Show just 'Marquee A/B/C/D' from the full category string
                        const catRaw = (data.player_category || '').toUpperCase();
                        let catShort = data.player_category || 'Official Player';
                        const mq = catRaw.match(/MARQUEE\s+([ABCD])/);
                        if (mq) catShort = 'Marquee ' + mq[1];
                        $('#player-category').text(catShort);
                        $('#player-display-id-badge').text(data.player_id);
                        $('#player-role').text(data.player_type || 'N/A');
                        $('#player-batting').text(data.batting_type || 'N/A');
                        $('#player-bowling').text(data.bowling_type || 'N/A');
                        $('#player-ppl-edition').text(data.ppl_edition || 'None');
                        $('#player-department').text(data.department || 'N/A');
                        $('#player-image').attr('src', data.player_image || 'assets/image-not-found.png');

                        // Populate Stats
                        if (data.stats) {
                            $('#stat-matches').text(data.stats.matches_played || 0);
                            $('#stat-innings').text(data.stats.innings || 0);
                            $('#stat-runs').text(data.stats.runs_scored || 0);
                            $('#stat-highest').text(data.stats.highest_score || 0);
                            $('#stat-batavg').text(data.stats.batting_avg || '0.00');
                            $('#stat-sr').text(data.stats.strike_rate || '0.00');
                            $('#stat-50s').text(data.stats.fifties || 0);
                            $('#stat-100s').text(data.stats.hundreds || 0);
                            $('#stat-wickets').text(data.stats.wickets || 0);
                            $('#stat-bestbowl').text(data.stats.best_bowling || '-');
                            $('#stat-bowlavg').text(data.stats.bowling_avg || '0.00');
                            $('#stat-economy').text(data.stats.economy || '0.00');
                            $('#stat-catches').text(data.stats.catches || 0);
                            $('#stat-stumpings').text(data.stats.stumpings || 0);
                        } else {
                            $('#stat-matches, #stat-innings, #stat-runs, #stat-highest, #stat-50s, #stat-100s, #stat-wickets, #stat-catches, #stat-stumpings').text('0');
                            $('#stat-batavg, #stat-sr, #stat-bowlavg, #stat-economy').text('0.00');
                            $('#stat-bestbowl').text('-');
                        }
                        // Reset animation state to default layout when loading new player
                        $('#player-card').removeClass('layout-stats').addClass('layout-default');
                        $('#tab-photo').removeClass('active');
                        $('#tab-stats').addClass('active');

                        // Reset stamps
                        $('.stamp').removeClass('active');

                        if (data.booking_status == 1) {
                            $('#bid-form-container').addClass('d-none');
                            $('#bidding-details').removeClass('d-none');
                            $('#sold-to-team').text(data.team_name);
                            $('#sold-for-points').text(formatPoints(data.bid_points));
                            if (data.team_logo) {
                                $('#sold-team-logo').attr('src', 'team_assets/' + data.team_logo).show();
                            } else {
                                $('#sold-team-logo').hide();
                            }
                            $('#sold-stamp').addClass('active');
                        } else if (data.booking_status == 2) {
                            $('#bid-form-container').removeClass('d-none');
                            $('#bidding-details').addClass('d-none');
                            $('#unsold-stamp').addClass('active');
                        } else {
                            $('#bid-form-container').removeClass('d-none');
                            $('#bidding-details').addClass('d-none');
                        }

                        // Dynamic Bidding Logic Initialization
                        const bp = getBasePrice(data.player_category);
                        const cb = (data.bid_points && parseFloat(data.bid_points) > 0) ? parseFloat(data.bid_points) : bp;

                        // If the player has a team assigned already (active bid), set the dropdown
                        if (data.team_id && data.booking_status == 0) {
                            $('#team-dropdown').val(data.team_id).trigger('change');
                        } else if (data.booking_status == 0) {
                            $('#team-dropdown').val(''); // clear if no team
                        }

                        updateBiddingUI(bp, cb);
                    },
                    error: function(xhr, status, error) {
                        showErrorModal('Error loading player. Please try again. (Session may have expired — try refreshing the page.)');
                    }
                });
            }

            $('#team-dropdown').on('change', function () {
                const teamId = $(this).val();
                if (!teamId) return;

                $.post('update_bid.php', { team_id: teamId }, function (data) {
                    if (data.success) {
                        lastFetchedData = data;
                        $('#rem-points').html('Remaining Points: <span class="text-white fw-bold">₹' + formatPoints(data.remaining_points) + '</span>');
                        $('#max-allowed').text('Max Bid Allowed: ₹' + formatPoints(data.max_bid));
                        var maxPlayers = data.max_players ? data.max_players : 13;
                        $('#team-strength').text('Players in Team: ' + data.player_count + ' / ' + maxPlayers);
                        checkTeamBalance();
                    }
                });
            });

            $('#submit-bid').on('click', function () {
                const playerId = $('#player-display-id-badge').text();
                const teamId = $('#team-dropdown').val();
                const bidPoints = getBidRawVal();

                if (!teamId) {
                    showErrorModal('Please select a team before submitting bid.');
                    return;
                }

                if (!bidPoints || bidPoints <= 0) {
                    showErrorModal('Please enter a valid bid amount.');
                    return;
                }

                $.post('update_bid.php', {
                    player_id: playerId,
                    team_id: teamId,
                    bid_points: bidPoints
                }, function (data) {
                    if (data.success) {
                        $('#sold-stamp').addClass('active');
                        $('#bid-form-container').fadeOut(300, function () {
                            $('#bidSuccessModal').modal('show');
                            fetchPlayer(playerId);
                        });
                    } else {
                        showErrorModal(data.message);
                    }
                });
            });

            $('#mark-unsold-btn').on('click', function () {
                const playerId = $('#player-display-id-badge').text();
                const playerName = $('#player-name').text();
                $('#unsoldModalPlayerId').text(playerId);
                $('#unsoldModalPlayerName').text(playerName);
                $('#unsoldConfirmModal').modal('show');
            });

            $('#confirm-unsold-btn').on('click', function () {
                const playerId = $('#player-display-id-badge').text();
                const $btn = $(this);
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Processing...');

                $.post('mark_unsold.php', { player_id: playerId }, function (data) {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Yes, Mark Unsold');
                    $('#unsoldConfirmModal').modal('hide');

                    if (data.success) {
                        $('#unsold-stamp').addClass('active');
                        fetchPlayer(playerId);
                    } else {
                        showErrorModal(data.message);
                    }
                }).fail(function() {
                    $btn.prop('disabled', false).html('<i class="fas fa-check-circle me-1"></i> Yes, Mark Unsold');
                    $('#unsoldConfirmModal').modal('hide');
                    showErrorModal('Error connecting to server. Please try again.');
                });
            });

            $('#revert-bid-btn').on('click', function () {
                // Remove sold stamp immediately
                $('#sold-stamp').removeClass('active');

                // Hide sold details panel
                $('#bidding-details').hide().addClass('d-none');

                // Show bid form — must first remove d-none before jQuery fadeIn works
                $('#bid-form-container').removeClass('d-none').hide().fadeIn(400);

                // Pre-fill existing bid data for correction
                if (lastFetchedOriginalData) {
                    if (lastFetchedOriginalData.team_id) {
                        $('#team-dropdown').val(lastFetchedOriginalData.team_id).trigger('change');
                        const bp = getBasePrice(lastFetchedOriginalData.player_category);
                        const cb = (lastFetchedOriginalData.bid_points && parseFloat(lastFetchedOriginalData.bid_points) > 0) ? parseFloat(lastFetchedOriginalData.bid_points) : bp;
                        formatBidInput(cb);
                    }
                }

                // Scroll to form
                setTimeout(function () {
                    $('html, body').animate({
                        scrollTop: $('#bid-form-container').offset().top - 150
                    }, 500);
                }, 100);
            });

            // Check if ID is in URL
            const urlParams = new URLSearchParams(window.location.search);
            const urlId = urlParams.get('id');
            if (urlId) {
                fetchPlayer(urlId);
            }

            // Layout Animation Toggles
            $('#player-image-trigger, #tab-stats').on('click', function() {
                $('#player-card').removeClass('layout-default').addClass('layout-stats');
                $('#tab-photo').removeClass('active');
                $('#tab-stats').addClass('active');
            });

            $('#tab-photo').on('click', function() {
                $('#player-card').removeClass('layout-stats').addClass('layout-default');
                $('#tab-stats').removeClass('active');
                $('#tab-photo').addClass('active');
            });
        });
    </script>
</body>

</html>