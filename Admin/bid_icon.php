<?php
require_once '../auth.php';
check_access('auction_admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bid Icon Player - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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

        .search-section {
            max-width: 620px;
            margin: 0 auto 40px;
        }

        .search-card-container {
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border: 1.5px solid #cbd5e1;
            border-radius: 50px;
            padding: 6px 14px;
            box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.08);
        }

        .player-img-container {
            border: 2px solid #e2e8f0;
            border-radius: 20px;
            overflow: hidden;
            background: #f8fafc;
            box-shadow: 0 10px 25px -4px rgba(15, 23, 42, 0.08);
            position: relative;
        }

        #icon-search-results {
            list-style: none;
            padding: 0;
            margin: 8px 0 0;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            position: absolute;
            width: 100%;
            z-index: 100;
            border-radius: 16px;
            box-shadow: 0 12px 30px -4px rgba(15, 23, 42, 0.12);
            max-height: 250px;
            overflow-y: auto;
        }

        #icon-search-results li {
            padding: 12px 18px;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            font-weight: 600;
            color: #0f172a;
            transition: all 0.2s ease;
        }

        #icon-search-results li:last-child {
            border-bottom: none;
        }

        #icon-search-results li:hover {
            background: #f0f9ff;
            color: #0284c7;
        }

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
            background: rgba(220, 252, 231, 0.9);
            box-shadow: 0 0 30px rgba(5, 150, 105, 0.3);
        }
    </style>
</head>
<body>
    <?php include 'header.php'; ?>

    <div class="container my-4 my-md-5">
        <div class="text-center mb-4">
            <h2 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 2.2rem;">Icon Player Bidding</h2>
            <p class="text-secondary small mt-1">Assign marquee franchise icon players to their designated teams</p>
        </div>

        <!-- Search Section -->
        <div class="search-section position-relative">
            <div class="input-group input-group-lg search-card-container">
                <span class="input-group-text bg-transparent border-0 text-primary ps-3"><i class="fas fa-search" style="color: #0284c7;"></i></span>
                <input type="text" id="icon-search-input" class="form-control bg-transparent border-0 text-dark" placeholder="Search Icon Player Name..." style="font-size: 1.05rem; font-weight: 600; outline: none; box-shadow: none;">
            </div>
            <ul id="icon-search-results" class="d-none"></ul>
        </div>

        <div id="bidding-section" class="d-none">
            <div class="p-4 stamp-container" id="player-card" style="background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(16px); border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 10px 30px -5px rgba(15, 23, 42, 0.06);">
                <div id="sold-stamp" class="stamp sold-stamp">SOLD</div>
                
                <div class="row g-4 align-items-center">
                    <div class="col-md-5">
                        <div class="player-img-container">
                            <img id="player-image" src="assets/image-not-found.png" onerror="this.onerror=null;this.src='assets/image-not-found.png';" alt="Player" class="img-fluid w-100" style="height: 420px; object-fit: cover;">
                        </div>
                    </div>
                    <div class="col-md-7 ps-md-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <span class="badge rounded-pill px-3 py-1" style="background: #e0f2fe; color: #0369a1; font-family: 'Outfit', sans-serif; font-size: 0.78rem; font-weight: 700;">
                                <i class="fas fa-star me-1"></i> ICON PLAYER
                            </span>
                        </div>
                        <h1 id="player-name" class="fw-bold mb-4" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 2.4rem;">Player Name</h1>
                        
                        <div id="bid-form-container">
                            <div class="p-4 rounded-4" style="background: #ffffff; border: 1.5px solid #e2e8f0; box-shadow: 0 4px 15px rgba(15, 23, 42, 0.05);">
                                <h4 class="mb-3 fw-bold" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">Assign to Team</h4>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="d-block mb-1 text-secondary fw-bold small text-uppercase" style="letter-spacing: 0.8px;">Select Team</label>
                                        <select id="team-dropdown" class="form-select fs-6 py-2" style="border-radius: 12px; border-color: #cbd5e1; background: #f8fafc; color: #0f172a; font-weight: 600;">
                                            <option value="">Select a Team</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mt-4 align-items-center">
                                    <div class="col-md-12">
                                        <button id="submit-bid" class="btn w-100 py-3 fw-bold fs-5" style="border-radius: 50px; background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border: none; font-family: 'Outfit', sans-serif; letter-spacing: 0.5px; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);">Confirm Assignment</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="sold-details" class="mt-4 p-4 rounded-4 d-none" style="background: #f0fdf4; border: 2px solid #86efac;">
                            <h4 class="fw-bold mb-2" style="font-family: 'Outfit', sans-serif; color: #059669;"><i class="fas fa-check-circle me-2"></i> ASSIGNED / SOLD</h4>
                            <div class="text-dark h4 m-0 fw-bold" style="font-family: 'Outfit', sans-serif;">
                                <span>Team: <strong id="sold-to-team" style="color: #0284c7;">---</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        let selectedIconId = null;
        let allIcons = [];

        $(document).ready(function() {
            // Load all icons for search
            $.getJSON('fetch_icons.php', function(data) {
                allIcons = data;
                loadTeams();
            });

            function loadTeams() {
                $.getJSON('fetch_teams.php', function(data) {
                    const $dropdown = $('#team-dropdown');
                    data.forEach(function(team) {
                        $dropdown.append($('<option>', {
                            value: team.id,
                            text: team.team_name
                        }));
                    });
                });
            }

            // Search Logic
            $('#icon-search-input').on('input', function() {
                const query = $(this).val().toLowerCase();
                const $results = $('#icon-search-results');
                
                if(query.length < 1) {
                    $results.addClass('d-none');
                    return;
                }

                const filtered = allIcons.filter(icon => icon.name.toLowerCase().includes(query));
                
                if(filtered.length > 0) {
                    $results.empty().removeClass('d-none');
                    filtered.forEach(icon => {
                        $results.append(`<li onclick="selectIcon(${icon.id})">${icon.name}</li>`);
                    });
                } else {
                    $results.addClass('d-none');
                }
            });

            window.selectIcon = function(id) {
                selectedIconId = id;
                $('#icon-search-results').addClass('d-none');
                const icon = allIcons.find(i => i.id == id);
                
                $('#bidding-section').removeClass('d-none');
                $('#player-name').text(icon.name);
                $('#player-image').attr('src', icon.image);
                $('.stamp').removeClass('active');
                
                if(icon.booking_status == 1) {
                    $('#bid-form-container').addClass('d-none');
                    $('#sold-details').removeClass('d-none');
                    $('#sold-to-team').text(icon.team_name);
                    $('#sold-stamp').addClass('active');
                } else {
                    $('#bid-form-container').removeClass('d-none');
                    $('#sold-details').addClass('d-none');
                }
                
                $('html, body').animate({
                    scrollTop: $("#bidding-section").offset().top - 100
                }, 500);
            };

            $('#submit-bid').on('click', function() {
                const teamId = $('#team-dropdown').val();

                if(!teamId) {
                    alert('Please select a team.');
                    return;
                }

                $.post('update_icon_bid.php', {
                    icon_id: selectedIconId,
                    team_id: teamId
                }, function(data) {
                    if(data.success) {
                        $('#sold-stamp').addClass('active');
                        setTimeout(() => {
                            window.location.href = 'team_details.php?id=' + teamId;
                        }, 1000);
                    } else {
                        alert(data.message);
                    }
                });
            });
        });
    </script>
</body>
</html>
