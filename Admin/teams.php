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
    <title>Teams Management - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="auction_style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-4 my-md-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold m-0" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; font-size: 2rem;">Teams Management</h2>
                <p class="text-secondary small mt-1">Manage PPL franchises, owners, icons, and starting purses</p>
            </div>
            <button class="btn btn-pill-primary px-4 py-2" data-bs-toggle="modal" data-bs-target="#addTeamModal" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: #ffffff; border: none; border-radius: 50px; font-family: 'Outfit', sans-serif; font-weight: 700; box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);">
                <i class="fas fa-plus me-2"></i> Add Team
            </button>
        </div>

        <div class="row g-4">
            <?php
            try {
                $sql = "SELECT t.id, t.team_name, t.team_logo, t.owner, t.owner_img, t.ic_player, t.ic_player_img, t.total_purse, t.remaining_purse, t.max_strength,
                               (SELECT COALESCE(SUM(bid_points), 0) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS reg_points,
                               (SELECT COALESCE(SUM(bid_points), 0) FROM icon_players WHERE team_id = t.id AND booking_status = 1) AS icon_points,
                               (SELECT COUNT(id) FROM registrations WHERE team_id = t.id AND booking_status = 1) AS total_players
                        FROM team_master t
                        ORDER BY t.team_name ASC";
                $stmt = $pdo->query($sql);

                while ($row = $stmt->fetch()) {
                    $totalSpent = floatval($row['reg_points']) + floatval($row['icon_points']);
                    $remainingPoints = ($row['remaining_purse'] !== null) ? floatval($row['remaining_purse']) : max(0, floatval($row['total_purse']) - $totalSpent);
                    $totalPlayers = $row['total_players'];
                    $maxStrength = $row['max_strength'] ?: 13;
                    $logo = !empty($row['team_logo']) ? 'team_assets/' . $row['team_logo'] : 'assets/ppl-logo-transparent.png';
                    $ownerArr = json_decode($row['owner'] ?? '', true);
                    $ownerDisplay = is_array($ownerArr) ? implode(', ', array_filter($ownerArr)) : ($row['owner'] ?? '');
                    ?>
                    <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                        <div class="h-100 d-flex flex-column justify-content-between position-relative" style="background: rgba(255, 255, 255, 0.96); backdrop-filter: blur(16px); border: 1px solid #e2e8f0; border-radius: 20px; box-shadow: 0 8px 25px -4px rgba(15, 23, 42, 0.05); overflow: hidden; cursor: pointer; transition: transform 0.25s ease, box-shadow 0.25s ease;" onclick="window.location.href='team_details.php?id=<?php echo $row['id']; ?>'">
                            <div class="p-4 text-center">
                                <?php if (!empty($ownerDisplay)): ?>
                                    <span class="badge rounded-pill mb-3 px-3 py-1" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: 'Outfit', sans-serif; font-size: 0.74rem;">
                                        <i class="fas fa-crown me-1" style="color: #0284c7;"></i> <?php echo htmlspecialchars($ownerDisplay); ?>
                                    </span>
                                <?php else: ?>
                                    <div style="height: 25px;"></div>
                                <?php endif; ?>
                                <div class="mb-3" style="height: 140px; display: flex; align-items: center; justify-content: center;">
                                    <img src="<?php echo htmlspecialchars($logo); ?>" alt="<?php echo htmlspecialchars($row['team_name']); ?>" onerror="this.onerror=null;this.src='assets/image-not-found.png';" class="img-fluid" style="max-height: 100%; object-fit: contain;">
                                </div>
                                <h4 class="mb-3 fw-bold" style="font-size: 1.25rem; color: #0f2a4a; font-family: 'Outfit', sans-serif;">
                                    <?php echo htmlspecialchars($row['team_name']); ?>
                                </h4>
                                <div class="w-100 pt-3" style="border-top: 1.5px solid #f1f5f9;">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary small fw-bold">TOTAL PURSE</span>
                                        <span class="fw-bold text-dark fs-6"><?php echo formatPoints(floatval($row['total_purse'])); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-secondary small fw-bold">REMAINING</span>
                                        <span class="fw-bold text-success fs-6"><?php echo formatPoints($remainingPoints); ?></span>
                                    </div>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-secondary small fw-bold">SQUAD</span>
                                        <span class="fw-bold fs-6" style="color: #7c3aed;"><?php echo $totalPlayers; ?> / <?php echo $maxStrength; ?></span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Action Buttons -->
                            <div class="p-3 d-flex justify-content-center gap-2" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                                <a href="team_details.php?id=<?php echo $row['id']; ?>" class="btn btn-sm rounded-pill px-3" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: 'Outfit', sans-serif; font-weight: 600;" title="View Team" onclick="event.stopPropagation();">
                                    <i class="fas fa-eye me-1"></i> View
                                </a>
                                <?php
                                $teamDataB64 = base64_encode(json_encode([
                                    'id' => $row['id'],
                                    'name' => $row['team_name'],
                                    'owner' => $row['owner'],
                                    'ic_player' => $row['ic_player'],
                                    'total_purse' => $row['total_purse'],
                                    'spent' => $totalSpent,
                                    'remaining_purse' => $remainingPoints,
                                    'max_strength' => $row['max_strength']
                                ]));
                                ?>
                                <button type="button" class="btn btn-sm rounded-pill px-3 edit-team-btn" style="background: #fef3c7; color: #d97706; border: 1px solid #fde68a; font-family: 'Outfit', sans-serif; font-weight: 600;" title="Edit Team" onclick="event.stopPropagation(); event.preventDefault(); openEditModalFromBtn(this);" data-team="<?php echo $teamDataB64; ?>">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button class="btn btn-sm rounded-pill px-2" style="background: #fee2e2; color: #dc2626; border: 1px solid #fca5a5;" title="Delete Team" onclick="event.stopPropagation(); deleteTeam(<?php echo $row['id']; ?>, <?php echo $totalPlayers; ?>)">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
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

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Team Modal -->
    <div class="modal fade" id="addTeamModal" tabindex="-1" aria-labelledby="addTeamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form id="addTeamForm" enctype="multipart/form-data" class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="addTeamModalLabel" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">
                        <i class="fas fa-shield-alt me-2" style="color: #0284c7;"></i> Add New Team
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="addTeamErrorAlert" class="alert alert-danger d-none" role="alert" style="border-radius: 12px;"></div>
                    <div id="addTeamSuccessAlert" class="alert alert-success d-none" role="alert" style="border-radius: 12px;"></div>

                    <div class="row g-4">
                        <!-- Team Details -->
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Team Name *</label>
                            <input type="text" name="team_name" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required placeholder="e.g. Bangalore Blasters">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Team Logo *</label>
                            <input type="file" name="team_logo" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required accept="image/*">
                        </div>

                        <!-- Owner Details -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 6px;">Owner Details
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill float-end py-0" onclick="addOwnerField('addTeamOwners')"><i class="fas fa-plus"></i> Add</button>
                            </h6>
                            <div id="addTeamOwners">
                                <div class="row g-3 mb-3 owner-row">
                                    <div class="col-md-5">
                                        <input type="text" name="owner[]" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required placeholder="Owner Name">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="file" name="owner_img[]" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" accept="image/*">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="removeField(this)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Icon Player Details -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 6px;">Icon Player Details
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill float-end py-0" onclick="addIconPlayerField('addTeamIconPlayers')"><i class="fas fa-plus"></i> Add</button>
                            </h6>
                            <div id="addTeamIconPlayers">
                                <div class="row g-3 mb-3 icon-player-row">
                                    <div class="col-md-5">
                                        <input type="text" name="ic_player[]" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required placeholder="Icon Player Name">
                                    </div>
                                    <div class="col-md-5">
                                        <input type="file" name="ic_player_img[]" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" accept="image/*">
                                    </div>
                                    <div class="col-md-2 text-center">
                                        <button type="button" class="btn btn-outline-danger mt-1 rounded-pill" onclick="removeField(this)"><i class="fas fa-trash"></i></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Starting Purse *</label>
                            <input type="number" name="total_purse" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required value="10000000" min="0" step="100000">
                            <small class="text-muted">Default is 1 Cr (10,000,000)</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Max Strength *</label>
                            <input type="number" name="max_strength" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required value="13" min="1">
                            <small class="text-muted">Default is 13 players</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="saveTeamBtn" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                        <i class="fas fa-save me-2"></i> Save Team
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 11000;">
        <div id="teamToast" class="toast align-items-center bg-white border" role="alert" aria-live="assertive" aria-atomic="true" style="border-radius: 12px; box-shadow: 0 10px 30px rgba(15, 23, 42, 0.1);">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2" id="teamToastBody"></div>
                <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    </div>

    <!-- Edit Team Modal -->
    <div class="modal fade" id="editTeamModal" tabindex="-1" aria-labelledby="editTeamModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <form id="editTeamForm" enctype="multipart/form-data" class="modal-content" style="background: #ffffff; border: 1px solid #e2e8f0; border-radius: 24px; box-shadow: 0 20px 50px rgba(15, 23, 42, 0.15);">
                <div class="modal-header border-0 pb-0 pt-4 px-4">
                    <h5 class="modal-title fw-bold" id="editTeamModalLabel" style="font-family: 'Outfit', sans-serif; color: #0f2a4a;">
                        <i class="fas fa-edit me-2" style="color: #0284c7;"></i> Edit Team
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div id="editTeamErrorAlert" class="alert alert-danger d-none" role="alert" style="border-radius: 12px;"></div>
                    <div id="editTeamSuccessAlert" class="alert alert-success d-none" role="alert" style="border-radius: 12px;"></div>

                    <input type="hidden" name="team_id" id="edit_team_id">

                    <div class="row g-4">
                        <!-- Team Details -->
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Team Name *</label>
                            <input type="text" name="team_name" id="edit_team_name" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Team Logo (Leave blank to keep current)</label>
                            <input type="file" name="team_logo" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" accept="image/*">
                        </div>

                        <!-- Owner Details -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 6px;">Owner Details
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill float-end py-0" onclick="addOwnerField('editTeamOwners')"><i class="fas fa-plus"></i> Add</button>
                            </h6>
                            <div id="editTeamOwners">
                                <!-- Populated dynamically by openEditModal -->
                            </div>
                        </div>

                        <!-- Icon Player Details -->
                        <div class="col-12">
                            <h6 class="fw-bold mb-3" style="font-family: 'Outfit', sans-serif; color: #0f2a4a; border-bottom: 1.5px solid #f1f5f9; padding-bottom: 6px;">Icon Player Details
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill float-end py-0" onclick="addIconPlayerField('editTeamIconPlayers')"><i class="fas fa-plus"></i> Add</button>
                            </h6>
                            <div id="editTeamIconPlayers">
                                <!-- Populated dynamically by openEditModal -->
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Remaining Purse (₹) *</label>
                            <input type="number" name="remaining_purse" id="edit_remaining_purse" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required min="0" step="100000">
                            <div class="form-text text-secondary small mt-1">Current Spent: <span id="edit_spent_display" class="fw-bold" style="color: #0284c7;">₹0</span></div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Total Purse (₹) *</label>
                            <input type="number" name="total_purse" id="edit_total_purse" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required min="0" step="100000">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label text-secondary fw-bold small text-uppercase">Max Strength *</label>
                            <input type="number" name="max_strength" id="edit_max_strength" class="form-control" style="border-radius: 12px; border-color: #cbd5e1;" required min="1">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" id="updateTeamBtn" class="btn btn-primary rounded-pill px-4" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); border: none;">
                        <i class="fas fa-save me-2"></i> Update Team
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            window.showToast = function (msg, isSuccess) {
                var toastEl = $('#teamToast');
                var toastBody = $('#teamToastBody');
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

            $('#addTeamModal').on('hidden.bs.modal', function () {
                $('#addTeamForm')[0].reset();
                $('#addTeamErrorAlert, #addTeamSuccessAlert').addClass('d-none').text('');
                $('#saveTeamBtn').prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Team');
            });

            $('#addTeamForm').on('submit', function (e) {
                e.preventDefault();
                var form = this;
                var submitBtn = $('#saveTeamBtn');

                $('#addTeamErrorAlert, #addTeamSuccessAlert').addClass('d-none').text('');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Saving...');

                var formData = new FormData(form);

                $.ajax({
                    url: 'api_add_team.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        try {
                            var res = typeof response === 'object' ? response : JSON.parse(response);
                            if (res.status === 'success') {
                                $('#addTeamSuccessAlert').removeClass('d-none').text(res.message);
                                showToast(res.message, true);
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                $('#addTeamErrorAlert').removeClass('d-none').text(res.message);
                                showToast(res.message, false);
                                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Team');
                            }
                        } catch (err) {
                            $('#addTeamErrorAlert').removeClass('d-none').text('Invalid server response.');
                            submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Team');
                        }
                    },
                    error: function () {
                        $('#addTeamErrorAlert').removeClass('d-none').text('An error occurred during the request.');
                        showToast('Request failed.', false);
                        submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Save Team');
                    }
                });
            });
            window.currentTeamSpent = 0;

            window.openEditModalFromBtn = function(btn) {
                try {
                    var raw = typeof btn === 'string' ? btn : ($(btn).attr('data-team') || $(btn).data('team'));
                    if (!raw) return;
                    var jsonStr = (typeof raw === 'string' && raw.trim().indexOf('{') === 0) ? raw : atob(raw);
                    var data = typeof jsonStr === 'object' ? jsonStr : JSON.parse(jsonStr);
                    if (data) {
                        openEditModal(data);
                    }
                } catch(err) {
                    console.error('Failed to parse team data:', err);
                }
            };

            // Click handler for Edit Team button
            $(document).on('click', '.edit-team-btn', function(e) {
                e.stopPropagation();
                e.preventDefault();
                openEditModalFromBtn(this);
            });

            // Edit logic
            window.openEditModal = function(data) {
                var spent = parseFloat(data.spent || 0);
                var total = parseFloat(data.total_purse || 0);
                var remaining = data.remaining_purse !== undefined ? parseFloat(data.remaining_purse) : Math.max(0, total - spent);

                window.currentTeamSpent = spent;
                $('#edit_team_id').val(data.id);
                $('#edit_team_name').val(data.name);
                $('#edit_spent_display').text(formatPoints(spent));
                $('#edit_remaining_purse').val(Math.round(remaining));
                $('#edit_total_purse').val(Math.round(total));
                $('#edit_max_strength').val(data.max_strength);
                $('#editTeamErrorAlert, #editTeamSuccessAlert').addClass('d-none');
                
                // Clear dynamic fields
                $('#editTeamOwners').empty();
                $('#editTeamIconPlayers').empty();
                
                // Parse owners
                var owners = [];
                try { owners = JSON.parse(data.owner); } catch(e) { if(data.owner) owners = [data.owner]; }
                if (owners.length === 0) owners = [''];
                
                owners.forEach(function(own) {
                    var html = '<div class="row g-3 mb-3 owner-row">' +
                        '<div class="col-md-5">' +
                            '<input type="text" name="owner[]" class="form-control bg-dark text-white border-secondary" required placeholder="Owner Name" value="' + (own ? own.replace(/"/g, '&quot;') : '') + '">' +
                        '</div>' +
                        '<div class="col-md-5">' +
                            '<input type="file" name="owner_img[]" class="form-control bg-dark text-white border-secondary" accept="image/*">' +
                        '</div>' +
                        '<div class="col-md-2 text-center">' +
                            '<button type="button" class="btn btn-outline-danger mt-1" onclick="removeField(this)"><i class="fas fa-trash"></i></button>' +
                        '</div>' +
                    '</div>';
                    $('#editTeamOwners').append(html);
                });

                // Parse icon players
                var iconPlayers = [];
                try { iconPlayers = JSON.parse(data.ic_player); } catch(e) { if(data.ic_player) iconPlayers = [data.ic_player]; }
                if (iconPlayers.length === 0) iconPlayers = [''];

                iconPlayers.forEach(function(ic) {
                    var html = '<div class="row g-3 mb-3 icon-player-row">' +
                        '<div class="col-md-5">' +
                            '<input type="text" name="ic_player[]" class="form-control bg-dark text-white border-secondary" required placeholder="Icon Player Name" value="' + (ic ? ic.replace(/"/g, '&quot;') : '') + '">' +
                        '</div>' +
                        '<div class="col-md-5">' +
                            '<input type="file" name="ic_player_img[]" class="form-control bg-dark text-white border-secondary" accept="image/*">' +
                        '</div>' +
                        '<div class="col-md-2 text-center">' +
                            '<button type="button" class="btn btn-outline-danger mt-1" onclick="removeField(this)"><i class="fas fa-trash"></i></button>' +
                        '</div>' +
                    '</div>';
                    $('#editTeamIconPlayers').append(html);
                });

                var modalEl = document.getElementById('editTeamModal');
                if (modalEl) {
                    var modalObj = bootstrap.Modal.getOrCreateInstance(modalEl);
                    modalObj.show();
                }
            };

            window.addOwnerField = function(containerId) {
                var html = '<div class="row g-3 mb-3 owner-row">' +
                    '<div class="col-md-5">' +
                        '<input type="text" name="owner[]" class="form-control bg-dark text-white border-secondary" required placeholder="Owner Name">' +
                    '</div>' +
                    '<div class="col-md-5">' +
                        '<input type="file" name="owner_img[]" class="form-control bg-dark text-white border-secondary" accept="image/*">' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                        '<button type="button" class="btn btn-outline-danger mt-1" onclick="removeField(this)"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                '</div>';
                $('#' + containerId).append(html);
            };

            window.addIconPlayerField = function(containerId) {
                var html = '<div class="row g-3 mb-3 icon-player-row">' +
                    '<div class="col-md-5">' +
                        '<input type="text" name="ic_player[]" class="form-control bg-dark text-white border-secondary" required placeholder="Icon Player Name">' +
                    '</div>' +
                    '<div class="col-md-5">' +
                        '<input type="file" name="ic_player_img[]" class="form-control bg-dark text-white border-secondary" accept="image/*">' +
                    '</div>' +
                    '<div class="col-md-2 text-center">' +
                        '<button type="button" class="btn btn-outline-danger mt-1" onclick="removeField(this)"><i class="fas fa-trash"></i></button>' +
                    '</div>' +
                '</div>';
                $('#' + containerId).append(html);
            };

            window.removeField = function(btn) {
                $(btn).closest('.row').remove();
            };

            $('#editTeamForm').on('submit', function (e) {
                e.preventDefault();
                var form = this;
                var submitBtn = $('#updateTeamBtn');

                $('#editTeamErrorAlert, #editTeamSuccessAlert').addClass('d-none').text('');
                submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Updating...');

                var formData = new FormData(form);

                $.ajax({
                    url: 'api_edit_team.php',
                    type: 'POST',
                    data: formData,
                    contentType: false,
                    processData: false,
                    success: function (response) {
                        try {
                            var res = typeof response === 'object' ? response : JSON.parse(response);
                            if (res.status === 'success') {
                                $('#editTeamSuccessAlert').removeClass('d-none').text(res.message);
                                showToast(res.message, true);
                                setTimeout(function () {
                                    window.location.reload();
                                }, 1500);
                            } else {
                                $('#editTeamErrorAlert').removeClass('d-none').text(res.message);
                                showToast(res.message, false);
                                submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                            }
                        } catch (err) {
                            $('#editTeamErrorAlert').removeClass('d-none').text('Invalid server response.');
                            submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                        }
                    },
                    error: function () {
                        $('#editTeamErrorAlert').removeClass('d-none').text('An error occurred during the request.');
                        showToast('Request failed.', false);
                        submitBtn.prop('disabled', false).html('<i class="fas fa-save me-2"></i> Update Team');
                    }
                });
            });

            // Delete logic
            window.deleteTeam = function(teamId, totalPlayers) {
                if (totalPlayers > 0) {
                    showToast('Cannot delete this team because it already has ' + totalPlayers + ' player(s) assigned to it.', false);
                    return;
                }

                if(confirm("Are you sure you want to delete this team? This action cannot be undone.")) {
                    $.ajax({
                        url: 'api_delete_team.php',
                        type: 'POST',
                        data: { id: teamId },
                        success: function(response) {
                            try {
                                var res = typeof response === 'object' ? response : JSON.parse(response);
                                if (res.status === 'success') {
                                    showToast(res.message, true);
                                    setTimeout(function () {
                                        window.location.reload();
                                    }, 1000);
                                } else {
                                    showToast(res.message, false);
                                }
                            } catch (err) {
                                showToast('Invalid server response.', false);
                            }
                        },
                        error: function() {
                            showToast('Failed to delete team due to a network error.', false);
                        }
                    });
                }
            };
        });
    </script>
</body>

</html>