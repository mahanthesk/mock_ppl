<?php
require_once '../auth.php';
check_access('auction_admin');

// unsold.php
require_once 'db_connection.php';

// Fetch teams for direct assignment dropdown
$teams = [];
try {
    $teamsStmt = $pdo->query("SELECT id, team_name, total_purse, (SELECT COALESCE(SUM(bid_points), 0) FROM registrations WHERE team_id = team_master.id AND booking_status = 1) as spent FROM team_master ORDER BY team_name ASC");
    $teams = $teamsStmt->fetchAll();
} catch (PDOException $e) {
    // Error silently handled, teams will be empty
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unsold Players - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <style>
        .filter-bar {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(2, 132, 199, 0.2);
            border-radius: 16px;
            padding: 14px 22px;
            box-shadow: 0 10px 25px rgba(15, 42, 74, 0.05);
            backdrop-filter: blur(16px);
        }
        .category-count {
            font-size: 0.75rem;
            color: #64748b;
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
            <h1 class="display-6 fw-bold mb-2" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">UNSOLD PLAYERS <span style="color: #0284c7;">POOL</span></h1>
            <p class="text-secondary small mx-auto" style="max-width: 550px;">
                Players not acquired during their initial auction round. Ready for re-bid or team assignment.
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
                        // Fallback just in case
                        echo '<option value="Top 20 Batters (Marquee A)">Top 20 Batters (Marquee A)</option>';
                        echo '<option value="Top 20 Bowlers (Marquee B)">Top 20 Bowlers (Marquee B)</option>';
                        echo '<option value="PPL PLayers (Marquee C)">PPL PLayers (Marquee C)</option>';
                        echo '<option value="New Players (Marquee D)">New Players (Marquee D)</option>';
                    }
                    ?>
                </select>
                <a href="unsold-list.php" class="view-btn rounded-pill px-3 py-1 fw-bold text-decoration-none" style="background: rgba(2, 132, 199, 0.1); border: 1px solid #0284c7; color: #0284c7; font-size: 0.85rem; transition: all 0.3s;">
                    <i class="fas fa-list me-2"></i>View List
                </a>
            </div>
            <div>
                <span id="player-count-badge">0 Players</span>
            </div>
        </div>

        <div class="row g-4" id="unsoldGrid">
            <?php
            try {
                $sql = "SELECT * FROM registrations WHERE booking_status = 2 AND deleted_at IS NULL ORDER BY created_at DESC, id DESC";
                $stmt = $pdo->query($sql);
                $total = 0;

                if ($stmt->rowCount() > 0) {
                    while ($row = $stmt->fetch()) {
                        $total++;
                        ?>
                        <div class="col-xl-3 col-lg-4 col-md-6 col-sm-6 player-card-item" data-category="<?php echo htmlspecialchars($row['category']); ?>">
                            <div class="ppl-player-card">
                                <!-- Top Bar -->
                                <div class="ppl-card-header-bar">
                                    <span class="ppl-player-id-pill"><?php echo htmlspecialchars($row['player_id']); ?></span>
                                    <span class="ppl-player-role-pill">
                                        <i class="fas fa-cricket-bat-ball text-cyan me-1"></i><?php echo htmlspecialchars($row['role'] ?: 'Player'); ?>
                                    </span>
                                </div>

                                <!-- Photo Box -->
                                <div class="ppl-card-image-box">
                                    <div class="stamp unsold-stamp active">UNSOLD</div>
                                    <img src="<?php echo htmlspecialchars($row['profile_pic']); ?>" alt="<?php echo htmlspecialchars($row['full_name']); ?>" onerror="this.onerror=null;this.src=\'assets/image-not-found.png\';"
                                        onerror="this.onerror=null;this.src='assets/ppl-logo-transparent.png';">
                                    <div class="ppl-card-image-overlay"></div>
                                    <span class="ppl-category-pill"><?php echo htmlspecialchars($row['category']); ?></span>
                                </div>

                                <!-- Body -->
                                <div class="ppl-card-body">
                                    <h4 class="ppl-player-name" title="<?php echo htmlspecialchars($row['full_name']); ?>">
                                        <?php echo htmlspecialchars($row['full_name']); ?>
                                    </h4>

                                    <div class="ppl-player-styles my-1">
                                        <span><i class="fas fa-baseball-bat-ball text-cyan"></i> <?php echo htmlspecialchars($row['batting_type'] ?? 'Batting N/A'); ?></span>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="d-flex gap-2 mt-auto pt-2 border-top" style="border-color: #e2e8f0 !important;">
                                        <button type="button" class="btn btn-outline-primary btn-sm rounded-pill flex-fill fw-bold" title="Add to Auction Again" onclick="event.preventDefault(); event.stopPropagation(); rebidPlayer('<?php echo $row['player_id']; ?>', '<?php echo addslashes($row['full_name']); ?>')">
                                            <i class="fas fa-sync-alt me-1"></i> Re-Bid
                                        </button>
                                        <button type="button" class="btn btn-success btn-sm rounded-pill flex-fill fw-bold shadow-sm" style="background: linear-gradient(135deg, #10b981, #059669); border: none;" title="Assign to Team" onclick="event.preventDefault(); event.stopPropagation(); openAssignModal('<?php echo $row['player_id']; ?>', '<?php echo addslashes($row['full_name']); ?>', '<?php echo $row['category']; ?>', '<?php echo $row['role']; ?>')">
                                            <i class="fas fa-user-plus me-1"></i> Assign
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php
                    }
                } else {
                    echo '<div class="col-12 text-center text-muted py-5"><h3>No unsold players.</h3></div>';
                }
            } catch (PDOException $e) {
                echo "<div class='alert alert-danger'>Error: " . $e->getMessage() . "</div>";
            }
            ?>
        </div>
    </div>

    <?php include 'footer.php'; ?>

    <!-- Assign Team Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1" aria-labelledby="assignModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.2); color: #0f2a4a; border-radius: 20px; box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);">
                <div class="modal-header border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="modal-title fw-bold" style="color: #0f2a4a;" id="assignModalLabel"><i class="fas fa-user-plus me-2" style="color: #059669;"></i>Assign Player to Team</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="assignAlert" class="alert d-none" role="alert"></div>
                    
                    <div class="mb-3 text-center p-3 rounded-3" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <h4 id="assignPlayerName" class="fw-bold mb-1" style="color: #0f2a4a;"></h4>
                        <div class="text-secondary small" id="assignPlayerDetails"></div>
                    </div>
                    
                    <form id="assignForm">
                        <input type="hidden" id="assignPlayerId" name="player_id">
                        
                        <div class="mb-3">
                            <label class="form-label small text-uppercase fw-bold" style="color: #64748b;">Select Team *</label>
                            <select id="assignTeamId" name="team_id" class="form-select" style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f2a4a; font-weight: 500; border-radius: 10px;" required>
                                <option value="" disabled selected>-- Select a Team --</option>
                                <?php foreach ($teams as $t): ?>
                                    <option value="<?php echo $t['id']; ?>" data-remaining="<?php echo ($t['total_purse'] - $t['spent']); ?>">
                                        <?php echo htmlspecialchars($t['team_name']); ?> (Remaining: <?php echo formatPoints($t['total_purse'] - $t['spent']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="mb-4">
                            <label class="form-label small text-uppercase fw-bold" style="color: #64748b;">Final Selling Price (₹) *</label>
                            <input type="number" id="assignSoldPrice" name="sold_price" class="form-control" style="background: #f8fafc; border: 1px solid #cbd5e1; color: #0f2a4a; font-weight: 500; border-radius: 10px;" required min="0" step="1000">
                        </div>
                        
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" id="submitAssignBtn" class="btn rounded-pill px-4 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #10b981, #059669); border: none;">Confirm Assignment</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Re-Bid Confirm Modal -->
    <div class="modal fade" id="rebidModal" tabindex="-1" aria-labelledby="rebidModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid rgba(2, 132, 199, 0.2); color: #0f2a4a; border-radius: 20px; box-shadow: 0 25px 50px rgba(15, 42, 74, 0.2);">
                <div class="modal-header border-bottom" style="border-color: #f1f5f9 !important;">
                    <h5 class="modal-title fw-bold" style="color: #0f2a4a;" id="rebidModalLabel"><i class="fas fa-sync-alt me-2" style="color: #0284c7;"></i>Re-Bid Player</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 text-center">
                    <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width: 64px; height: 64px; border-radius: 50%; background: rgba(2, 132, 199, 0.1); color: #0284c7; font-size: 1.8rem;">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <h5 class="mb-2 fw-bold" style="color: #0f2a4a;">Add <span id="rebidPlayerNameDisplay" class="text-primary fw-bold"></span> back to active auction?</h5>
                    <p class="text-secondary small mb-4">This will move the player out of the Unsold list and make them available for bidding again.</p>
                    <input type="hidden" id="rebidPlayerId">
                    <div class="d-flex justify-content-center gap-3">
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" id="confirmRebidBtn" class="btn rounded-pill px-4 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); border: none;">Confirm Re-Bid</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
        <div id="unsoldToast" class="toast align-items-center text-white bg-dark border" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="unsoldToastBody"></div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function () {
            function updateCount() {
                const visible = $('.player-card-item:visible').length;
                $('#player-count-badge').text(visible + ' Player' + (visible !== 1 ? 's' : ''));
            }

            updateCount();

            $('#category-filter').on('change', function () {
                const selected = $(this).val();
                if (selected === 'all') {
                    $('.player-card-item').fadeIn(300);
                } else {
                    $('.player-card-item').hide();
                    $(`.player-card-item[data-category="${selected}"]`).fadeIn(300);
                }
                setTimeout(updateCount, 350);
            });

            window.showToast = function (msg, isSuccess) {
                var toastEl = $('#unsoldToast');
                var toastBody = $('#unsoldToastBody');
                toastEl.removeClass('border-success border-danger bg-dark bg-success bg-danger');
                if (isSuccess) {
                    toastEl.addClass('border-success bg-dark');
                    toastBody.html('<i class="fas fa-check-circle text-success fs-5"></i> <span class="fw-bold">' + msg + '</span>');
                } else {
                    toastEl.addClass('border-danger bg-dark');
                    toastBody.html('<i class="fas fa-exclamation-circle text-danger fs-5"></i> <span class="fw-bold">' + msg + '</span>');
                }
                var toast = new bootstrap.Toast(toastEl[0], { delay: 4000 });
                toast.show();
            };

            window.rebidPlayer = function(playerId, playerName) {
                $('#rebidPlayerId').val(playerId);
                $('#rebidPlayerNameDisplay').text(playerName);
                var rebidModal = new bootstrap.Modal(document.getElementById('rebidModal'));
                rebidModal.show();
            };

            $('#confirmRebidBtn').on('click', function() {
                var playerId = $('#rebidPlayerId').val();
                var btn = $(this);
                btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Processing...');

                $.ajax({
                    url: 'api_rebid_player.php',
                    type: 'POST',
                    data: { player_id: playerId },
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            var modal = bootstrap.Modal.getInstance(document.getElementById('rebidModal'));
                            modal.hide();
                            showToast(res.message, true);
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            showToast(res.message, false);
                            btn.prop('disabled', false).text('Confirm Re-Bid');
                        }
                    },
                    error: function() {
                        showToast('Failed to connect to server.', false);
                        btn.prop('disabled', false).text('Confirm Re-Bid');
                    }
                });
            });

            window.openAssignModal = function(playerId, playerName, category, role) {
                $('#assignPlayerId').val(playerId);
                $('#assignPlayerName').text(playerName);
                $('#assignPlayerDetails').text(playerId + ' | ' + category + ' | ' + role);
                $('#assignForm')[0].reset();
                $('#assignAlert').addClass('d-none');
                
                var assignModal = new bootstrap.Modal(document.getElementById('assignModal'));
                assignModal.show();
            };

            $('#assignForm').on('submit', function(e) {
                e.preventDefault();
                const submitBtn = $('#submitAssignBtn');
                
                const selectedTeam = $('#assignTeamId option:selected');
                const remainingPurse = parseFloat(selectedTeam.data('remaining'));
                const soldPrice = parseFloat($('#assignSoldPrice').val());

                if (soldPrice > remainingPurse) {
                    $('#assignAlert').removeClass('d-none alert-success').addClass('alert-danger')
                        .html('<i class="fas fa-exclamation-triangle me-2"></i><strong>Budget Exceeded!</strong> This team only has ₹' + remainingPurse.toLocaleString() + ' remaining.');
                    return;
                }

                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Assigning...');
                $('#assignAlert').addClass('d-none');

                $.ajax({
                    url: 'api_assign_player.php',
                    type: 'POST',
                    data: $(this).serialize(),
                    dataType: 'json',
                    success: function(res) {
                        if (res.status === 'success') {
                            $('#assignAlert').removeClass('d-none alert-danger').addClass('alert-success')
                                .html('<i class="fas fa-check-circle me-2"></i>' + res.message);
                            showToast(res.message, true);
                            setTimeout(() => window.location.reload(), 1500);
                        } else {
                            $('#assignAlert').removeClass('d-none alert-success').addClass('alert-danger')
                                .html('<i class="fas fa-exclamation-circle me-2"></i>' + res.message);
                            submitBtn.prop('disabled', false).text('Confirm Assignment');
                        }
                    },
                    error: function() {
                        $('#assignAlert').removeClass('d-none alert-success').addClass('alert-danger')
                            .html('<i class="fas fa-wifi me-2"></i>Network Error. Please try again.');
                        submitBtn.prop('disabled', false).text('Confirm Assignment');
                    }
                });
            });
        });
    </script>
</body>

</html>
