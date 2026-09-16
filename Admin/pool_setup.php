<?php
require_once '../auth.php';
check_access('auction_admin');

require_once 'db_connection.php';

// Ensure pool column exists
try {
    $pdo->exec("ALTER TABLE team_master ADD COLUMN IF NOT EXISTS pool VARCHAR(2) DEFAULT NULL");
} catch (PDOException $e) { /* already exists */
}

// Handle POST save
$successMsg = '';
$errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pool_a'], $_POST['pool_b'])) {
    $poolA = array_filter($_POST['pool_a']);
    $poolB = array_filter($_POST['pool_b']);
    $overlap = array_intersect($poolA, $poolB);
    if (!empty($overlap)) {
        $errorMsg = 'A team cannot be in both Pool A and Pool B.';
    } else {
        try {
            $pdo->exec("UPDATE team_master SET pool = NULL");
            $stmtU = $pdo->prepare("UPDATE team_master SET pool = ? WHERE id = ?");
            foreach ($poolA as $id)
                $stmtU->execute(['A', intval($id)]);
            foreach ($poolB as $id)
                $stmtU->execute(['B', intval($id)]);
            $successMsg = 'Pools saved successfully!';
        } catch (PDOException $e) {
            $errorMsg = 'Database error: ' . $e->getMessage();
        }
    }
}

// Fetch teams
$allTeams = $pdo->query("SELECT id, team_name, team_logo, pool FROM team_master ORDER BY team_name ASC")->fetchAll();
$poolATeams = array_values(array_filter($allTeams, fn($t) => $t['pool'] === 'A'));
$poolBTeams = array_values(array_filter($allTeams, fn($t) => $t['pool'] === 'B'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pool Setup - PPL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="auction_style.css">
    <style>
        .pool-panel {
            background: #ffffff;
            border: 2px solid rgba(2, 132, 199, 0.2);
            border-radius: 20px;
            padding: 28px;
            min-height: 300px;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.06);
        }

        .pool-a-panel {
            border-color: rgba(2, 132, 199, 0.4);
        }

        .pool-b-panel {
            border-color: rgba(239, 68, 68, 0.4);
        }

        .pool-a-title {
            color: #0284c7;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        .pool-b-title {
            color: #dc2626;
            font-family: 'Poppins', sans-serif;
            font-weight: 700;
        }

        /* VIEW MODE: team row */
        .view-list {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .team-row {
            display: flex;
            align-items: center;
            gap: 18px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 18px;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .team-row:hover {
            transform: translateX(4px);
            box-shadow: 0 4px 15px rgba(15, 42, 74, 0.08);
            border-color: #cbd5e1;
        }

        .team-row img {
            height: 60px;
            width: 60px;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(15, 42, 74, 0.1));
            flex-shrink: 0;
        }

        .team-row .row-num {
            font-family: 'Poppins', sans-serif;
            color: #94a3b8;
            font-weight: 700;
            font-size: 1.1rem;
            min-width: 28px;
            text-align: center;
        }

        .team-row .row-name {
            font-family: 'Poppins', sans-serif;
            color: #0f2a4a;
            font-weight: 700;
            font-size: 1.15rem;
            letter-spacing: -0.2px;
        }

        /* EDIT MODE */
        .pool-slot-select {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            color: #0f2a4a;
            font-weight: 500;
            border-radius: 10px;
            padding: 8px 12px;
            width: 100%;
        }

        .pool-slot-select option {
            background: #ffffff;
            color: #0f2a4a;
        }

        .pool-slot-select:focus {
            outline: none;
            border-color: #0284c7;
        }

        .edit-slot-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .edit-slot-row img {
            height: 42px;
            width: 42px;
            object-fit: contain;
            border-radius: 8px;
            background: #f1f5f9;
            padding: 2px;
            border: 1px solid #e2e8f0;
        }

        .edit-controls {
            display: none;
        }

        .view-list {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .btn-edit-pool {
            background: rgba(2, 132, 199, 0.1);
            border: 1px solid rgba(2, 132, 199, 0.3);
            color: #0284c7;
            border-radius: 20px;
            padding: 5px 16px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }

        .btn-edit-pool:hover {
            background: #0284c7;
            color: #ffffff;
        }

        .pool-divider {
            width: 2px;
            background: linear-gradient(to bottom, transparent, rgba(2, 132, 199, 0.3), transparent);
        }

        .empty-hint {
            color: #94a3b8;
            font-size: 0.85rem;
            text-align: center;
            padding: 20px 0;
        }
    </style>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@400;600;700&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="container my-5">
        <div class="text-center mb-5">
            <div class="d-inline-flex align-items-center gap-2 px-3 py-1 rounded-pill mb-2" style="background: rgba(2, 132, 199, 0.1); border: 1px solid rgba(2, 132, 199, 0.3);">
                <i class="fas fa-layer-group" style="color: #0284c7;"></i>
                <span style="font-size: 0.8rem; font-weight: 700; color: #0284c7; letter-spacing: 1.5px; text-transform: uppercase;">Tournament Configuration</span>
            </div>
            <h1 class="display-6 fw-bold mb-2" style="font-family: 'Poppins', sans-serif; color: #0f2a4a; letter-spacing: -0.5px;">POOL <span style="color: #0284c7;">SETUP</span></h1>
            <p class="text-secondary small mx-auto" style="max-width: 550px;">Assign teams to Elephant Pool &amp; Tiger Pool</p>
        </div>

        <?php if ($successMsg): ?>
            <div class="alert alert-success text-center fw-bold mb-4"><?php echo $successMsg; ?></div>
        <?php endif; ?>
        <?php if ($errorMsg): ?>
            <div class="alert alert-danger text-center fw-bold mb-4"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <form method="POST" id="pool-form">
            <div class="row g-4">

                <!-- ── POOL A ── -->
                <div class="col-md-5">
                    <div class="pool-panel pool-a-panel">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h4 class="pool-a-title mb-0">
                                🐘 Elephant Pool
                                <span class="badge ms-2" id="pool-a-count"
                                    style="background:rgba(13,110,253,0.2);color:#5b9cf3;border:1px solid rgba(13,110,253,0.4);font-size:0.78rem;">
                                    <?php echo count($poolATeams); ?> Teams
                                </span>
                            </h4>
                            <button type="button" class="btn-edit-pool" id="edit-a-btn">
                                <i class="fas fa-pencil me-1"></i> Edit
                            </button>
                        </div>

                        <!-- VIEW MODE -->
                        <div id="pool-a-view" class="view-list">
                            <?php if (empty($poolATeams)): ?>
                                <div class="empty-hint w-100">No teams assigned yet.</div>
                            <?php else: ?>
                                <?php foreach ($poolATeams as $i => $t): ?>
                                    <div class="team-row">
                                        <span class="row-num"><?php echo $i + 1; ?></span>
                                        <img src="<?php echo $t['team_logo'] ? 'team_assets/' . htmlspecialchars($t['team_logo']) : 'assets/ppl-logo-transparent.png'; ?>"
                                            alt="">
                                        <div class="row-name"><?php echo htmlspecialchars($t['team_name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- EDIT MODE -->
                        <div class="edit-controls" id="pool-a-edit">
                            <div id="pool-a-slots"></div>
                            <button type="button" class="btn btn-sm mt-2 w-100" id="add-a-slot"
                                style="border:1px dashed rgba(13,110,253,0.4);color:#5b9cf3;background:rgba(13,110,253,0.05);">
                                <i class="fas fa-plus me-1"></i> Add Slot
                            </button>
                        </div>
                    </div>
                </div>

                <!-- ── DIVIDER ── -->
                <div class="col-md-2 d-flex justify-content-center align-items-stretch">
                    <div class="pool-divider mx-auto d-none d-md-block"></div>
                </div>

                <!-- ── POOL B ── -->
                <div class="col-md-5">
                    <div class="pool-panel pool-b-panel">
                        <div class="d-flex align-items-center justify-content-between mb-4">
                            <h4 class="pool-b-title mb-0">
                                🐯 Tiger Pool
                                <span class="badge ms-2" id="pool-b-count"
                                    style="background:rgba(220,53,69,0.2);color:#ff6b7a;border:1px solid rgba(220,53,69,0.4);font-size:0.78rem;">
                                    <?php echo count($poolBTeams); ?> Teams
                                </span>
                            </h4>
                            <button type="button" class="btn-edit-pool" id="edit-b-btn">
                                <i class="fas fa-pencil me-1"></i> Edit
                            </button>
                        </div>

                        <!-- VIEW MODE -->
                        <div id="pool-b-view" class="view-list">
                            <?php if (empty($poolBTeams)): ?>
                                <div class="empty-hint w-100">No teams assigned yet.</div>
                            <?php else: ?>
                                <?php foreach ($poolBTeams as $i => $t): ?>
                                    <div class="team-row">
                                        <span class="row-num"><?php echo $i + 1; ?></span>
                                        <img src="<?php echo $t['team_logo'] ? 'team_assets/' . htmlspecialchars($t['team_logo']) : 'assets/ppl-logo-transparent.png'; ?>"
                                            alt="">
                                        <div class="row-name"><?php echo htmlspecialchars($t['team_name']); ?></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <!-- EDIT MODE -->
                        <div class="edit-controls" id="pool-b-edit">
                            <div id="pool-b-slots"></div>
                            <button type="button" class="btn btn-sm mt-2 w-100" id="add-b-slot"
                                style="border:1px dashed rgba(220,53,69,0.4);color:#ff6b7a;background:rgba(220,53,69,0.05);">
                                <i class="fas fa-plus me-1"></i> Add Slot
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Save button — only visible when in edit mode -->
            <div class="text-center mt-5" id="save-row" style="display:none !important;">
                <button type="submit" class="btn rounded-pill px-5 py-2 fw-bold text-white shadow-sm" style="background: linear-gradient(135deg, #0284c7, #0ea5e9); border: none;">
                    <i class="fas fa-save me-2"></i> Save Pool Assignment
                </button>
            </div>
        </form>
    </div>

    <?php include 'footer.php'; ?>

    <script>
        const allTeams = <?php echo json_encode(array_values($allTeams)); ?>;
        const savedA = <?php echo json_encode(array_values(array_map(fn($t) => (string) $t['id'], $poolATeams))); ?>;
        const savedB = <?php echo json_encode(array_values(array_map(fn($t) => (string) $t['id'], $poolBTeams))); ?>;

        let editingA = false, editingB = false;

        function buildOptions(selectedId = '') {
            let h = '<option value="">-- Select Team --</option>';
            allTeams.forEach(t => {
                const sel = String(t.id) === String(selectedId) ? 'selected' : '';
                h += `<option value="${t.id}" ${sel}>${t.team_name}</option>`;
            });
            return h;
        }

        function createSlot(pool, selectedId = '') {
            const t = selectedId ? allTeams.find(x => String(x.id) === String(selectedId)) : null;
            const logoSrc = t && t.team_logo ? `team_assets/${t.team_logo}` : (t ? 'assets/ppl-logo-transparent.png' : '');

            const row = document.createElement('div');
            row.className = 'edit-slot-row';
            row.innerHTML = `
                <img src="${logoSrc}" style="display:${logoSrc ? 'block' : 'none'};">
                <select class="pool-slot-select" name="pool_${pool.toLowerCase()}[]">
                    ${buildOptions(selectedId)}
                </select>
                <button type="button" class="btn btn-sm remove-slot" style="color:#888;background:none;border:none;">
                    <i class="fas fa-times"></i>
                </button>
            `;

            row.querySelector('select').addEventListener('change', function () {
                const chosen = allTeams.find(x => String(x.id) === this.value);
                const img = row.querySelector('img');
                if (chosen) {
                    img.src = chosen.team_logo ? `team_assets/${chosen.team_logo}` : 'assets/ppl-logo-transparent.png';
                    img.style.display = 'block';
                } else {
                    img.style.display = 'none';
                }
                updateCounts();
            });

            row.querySelector('.remove-slot').addEventListener('click', () => {
                row.remove();
                updateCounts();
            });

            return row;
        }

        function populateSlots(pool, saved) {
            const container = document.getElementById(`pool-${pool.toLowerCase()}-slots`);
            container.innerHTML = '';
            if (saved.length === 0) {
                container.appendChild(createSlot(pool));
            } else {
                saved.forEach(id => container.appendChild(createSlot(pool, id)));
            }
        }

        function updateCounts() {
            const cA = document.querySelectorAll('#pool-a-slots .edit-slot-row').length;
            const cB = document.querySelectorAll('#pool-b-slots .edit-slot-row').length;
            document.getElementById('pool-a-count').textContent = `${cA} Team${cA !== 1 ? 's' : ''}`;
            document.getElementById('pool-b-count').textContent = `${cB} Team${cB !== 1 ? 's' : ''}`;
        }

        function enterEdit(pool) {
            const up = pool.toUpperCase();
            document.getElementById(`pool-${pool}-view`).style.display = 'none';
            document.getElementById(`pool-${pool}-edit`).style.display = 'block';
            document.getElementById(`edit-${pool}-btn`).innerHTML = `<i class="fas fa-xmark me-1"></i> Cancel`;
            document.getElementById('save-row').style.removeProperty('display');
            populateSlots(up, up === 'A' ? savedA : savedB);
            updateCounts();
            if (pool === 'a') editingA = true; else editingB = true;
        }

        function exitEdit(pool) {
            document.getElementById(`pool-${pool}-view`).style.display = 'flex';
            document.getElementById(`pool-${pool}-view`).style.flexDirection = 'column';
            document.getElementById(`pool-${pool}-edit`).style.display = 'none';
            document.getElementById(`edit-${pool}-btn`).innerHTML = `<i class="fas fa-pencil me-1"></i> Edit`;
            if (pool === 'a') editingA = false; else editingB = false;
            if (!editingA && !editingB) {
                document.getElementById('save-row').style.setProperty('display', 'none', 'important');
            }
        }

        document.getElementById('edit-a-btn').addEventListener('click', () => {
            editingA ? exitEdit('a') : enterEdit('a');
        });
        document.getElementById('edit-b-btn').addEventListener('click', () => {
            editingB ? exitEdit('b') : enterEdit('b');
        });

        document.getElementById('add-a-slot').addEventListener('click', () => {
            document.getElementById('pool-a-slots').appendChild(createSlot('A'));
            updateCounts();
        });
        document.getElementById('add-b-slot').addEventListener('click', () => {
            document.getElementById('pool-b-slots').appendChild(createSlot('B'));
            updateCounts();
        });

        // Client-side validation
        document.getElementById('pool-form').addEventListener('submit', function (e) {
            const aVals = [...document.querySelectorAll('select[name="pool_a[]"]')].map(s => s.value).filter(Boolean);
            const bVals = [...document.querySelectorAll('select[name="pool_b[]"]')].map(s => s.value).filter(Boolean);
            const overlap = aVals.filter(v => bVals.includes(v));
            if (overlap.length > 0) {
                e.preventDefault();
                alert('A team cannot appear in both Pool A and Pool B.');
            }
        });

        // If no assignments yet, auto open edit mode for both
        if (savedA.length === 0 && savedB.length === 0) {
            enterEdit('a');
            enterEdit('b');
        }
    </script>
</body>

</html>