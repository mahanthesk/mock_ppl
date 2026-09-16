<?php
// api_add_player.php - Single API endpoint to handle both player registration and player statistics in a transaction
header('Content-Type: application/json');

require_once 'auth.php';
require_once 'db_connection.php';

// Check authorization (Registration Admin, Auction Admin, or valid admin session)
if (!is_role_logged_in('registration_admin') && !is_role_logged_in('auction_admin') && !isset($_SESSION['admin_id'])) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => 'Unauthorized access.',
        'error' => 'Unauthorized access.'
    ]);
    exit;
}

function sendError($msg) {
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => $msg,
        'error' => $msg
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method.');
}

// Ensure registrations table has dob column
try {
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS dob DATE NULL AFTER email");
} catch (Exception $e) {
    // Ignore if column already exists or syntax not supported by older mysql
}

// Ensure registrations table has registered_by column
try {
    $pdo->exec("ALTER TABLE registrations ADD COLUMN IF NOT EXISTS registered_by VARCHAR(100) NULL AFTER created_at");
} catch (Exception $e) {
    // Ignore if column already exists
}

// Ensure players_stats table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `players_stats` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `player_id` VARCHAR(20) NOT NULL,
        `match_name` VARCHAR(100) DEFAULT NULL,
        `opponent` VARCHAR(100) DEFAULT NULL,
        `tournament` VARCHAR(100) DEFAULT NULL,
        `ground` VARCHAR(100) DEFAULT NULL,
        `match_date` DATE DEFAULT NULL,
        `runs` INT DEFAULT 0,
        `balls` INT DEFAULT 0,
        `fours` INT DEFAULT 0,
        `sixes` INT DEFAULT 0,
        `strike_rate` DECIMAL(6,2) DEFAULT 0.00,
        `overs` DECIMAL(4,1) DEFAULT 0.0,
        `runs_conceded` INT DEFAULT 0,
        `wickets` INT DEFAULT 0,
        `economy` DECIMAL(6,2) DEFAULT 0.00,
        `maidens` INT DEFAULT 0,
        `no_balls` INT DEFAULT 0,
        `wides` INT DEFAULT 0,
        `catches` INT DEFAULT 0,
        `run_outs` INT DEFAULT 0,
        `stumpings` INT DEFAULT 0,
        `fifties` INT DEFAULT 0,
        `hundreds` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_player_id` (`player_id`),
        CONSTRAINT `fk_players_stats_reg` FOREIGN KEY (`player_id`) REFERENCES `registrations` (`player_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
} catch (Exception $e) {
    // Ignore table creation error if already exists
}

try {
    $pdo->exec("ALTER TABLE players_stats ADD COLUMN IF NOT EXISTS fifties INT DEFAULT 0 AFTER strike_rate");
    $pdo->exec("ALTER TABLE players_stats ADD COLUMN IF NOT EXISTS hundreds INT DEFAULT 0 AFTER fifties");
} catch (Exception $e) {}


// Also ensure registration view exists for backward/alternative naming compatibility
try {
    $pdo->exec("CREATE OR REPLACE VIEW `registration` AS SELECT * FROM `registrations`");
} catch (Exception $e) {
    // Ignore view creation error
}

// 1. Validate Required Player Details
$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    sendError('Player Name is required.');
}

$team_id = trim($_POST['team_id'] ?? '');
// Team is now optional.


$category = trim($_POST['category'] ?? '');
if (empty($category)) {
    sendError('Category is required.');
}

// 2. Prevent Duplicate Jersey Number within the same Team
$jersey_number = trim($_POST['jersey_number'] ?? '');
if ($jersey_number !== '' && $team_id !== '') {
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE team_id = ? AND jersey_number = ? AND jersey_number > 0 AND deleted_at IS NULL");
    $stmt->execute([intval($team_id), intval($jersey_number)]);
    if ($stmt->fetch()) {
        sendError('Jersey Number already exists for this Team.');
    }
}

// 4. Validate Statistics only when 'Add Player Statistics Now' is checked
$add_stats_now = !empty($_POST['add_stats_now']) && $_POST['add_stats_now'] == '1';
$match_name = trim($_POST['match_name'] ?? '');

if ($add_stats_now) {
    // If a player_id was explicitly provided in POST, check for duplicate statistics immediately
    $post_player_id = trim($_POST['player_id'] ?? '');
    if ($post_player_id !== '' && $match_name !== '') {
        $stmt = $pdo->prepare("SELECT id FROM players_stats WHERE player_id = ? AND match_name = ?");
        $stmt->execute([$post_player_id, $match_name]);
        if ($stmt->fetch()) {
            sendError('Statistics already exist for this player and match.');
        }
    }
}

// 4b. Capture registered_by from session
$registered_by = '';
if (!empty($_SESSION['admin_username'])) {
    $registered_by = $_SESSION['admin_username'];
} elseif (!empty($_SESSION['username'])) {
    $registered_by = $_SESSION['username'];
} elseif (!empty($_SESSION['reg_admin_username'])) {
    $registered_by = $_SESSION['reg_admin_username'];
} elseif (!empty($_SESSION['admin_id'])) {
    $registered_by = 'admin_' . $_SESSION['admin_id'];
}

// 5. Start Database Transaction
try {
    $pdo->beginTransaction();
} catch (Exception $e) {
    sendError('Failed to start database transaction.');
}

// 6. Generate sequential player_id or use provided
$new_player_id = trim($_POST['player_id'] ?? '');
if (empty($new_player_id)) {
    $stmt = $pdo->query("SELECT player_id FROM registrations WHERE player_id LIKE 'PPL-%' ORDER BY LENGTH(player_id) DESC, player_id DESC LIMIT 1");
    $last_row = $stmt->fetch(PDO::FETCH_ASSOC);

    $next_num = 1;
    if ($last_row && !empty($last_row['player_id'])) {
        $last_id = $last_row['player_id'];
        $parts = explode('-', $last_id);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            $next_num = intval($parts[1]) + 1;
        }
    }

    $new_player_id = 'PPL-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // Ensure uniqueness
    while (true) {
        $checkStmt = $pdo->prepare("SELECT id FROM registrations WHERE player_id = ?");
        $checkStmt->execute([$new_player_id]);
        if (!$checkStmt->fetch()) break;
        $next_num++;
        $new_player_id = 'PPL-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);
    }
}

// 7. Handle Photo Upload
$profile_pic_path = null;
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $dest = $uploadDir . $new_player_id . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
            $profile_pic_path = $dest;
        }
    }
}

// 8. Insert Player Details into registrations table
try {
    $sql = "INSERT INTO registrations (
        player_id, salutation, full_name, dob, category, profile_pic,
        experience, jersey_nickname, jersey_number,
        jersey_size, track_pant_size, team_id, created_at, registered_by
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $new_player_id,
        trim($_POST['salutation'] ?? ''),
        $full_name,
        !empty($_POST['dob']) ? $_POST['dob'] : null,
        $category,
        $profile_pic_path,
        trim($_POST['experience'] ?? 'Professional'),
        trim($_POST['jersey_nickname'] ?? ''),
        $jersey_number !== '' ? intval($jersey_number) : null,
        trim($_POST['jersey_size'] ?? 'M'),
        !empty($_POST['track_pant_size']) ? intval($_POST['track_pant_size']) : 0,
        $team_id !== '' ? intval($team_id) : null,
        $registered_by !== '' ? $registered_by : null
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    sendError('Failed to save player.');
}

// 9. If 'Add Player Statistics Now' is checked, validate and insert statistics
if ($add_stats_now) {
    if ($match_name !== '') {
        $stmt = $pdo->prepare("SELECT id FROM players_stats WHERE player_id = ? AND match_name = ?");
        $stmt->execute([$new_player_id, $match_name]);
        if ($stmt->fetch()) {
            $pdo->rollBack();
            sendError('Statistics already exist for this player and match.');
        }
    }

    try {
        $statsSql = "INSERT INTO players_stats (
            player_id, match_name,
            runs, balls, fours, sixes, strike_rate, fifties, hundreds,
            overs, runs_conceded, wickets, economy, maidens, no_balls, wides,
            catches, run_outs, stumpings, created_at
        ) VALUES (
            ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?, ?, ?, ?,
            ?, ?, ?, NOW()
        )";

        $statsStmt = $pdo->prepare($statsSql);
        $statsStmt->execute([
            $new_player_id,
            $match_name !== '' ? $match_name : null,
            intval($_POST['runs'] ?? 0),
            intval($_POST['balls'] ?? 0),
            intval($_POST['fours'] ?? 0),
            intval($_POST['sixes'] ?? 0),
            floatval($_POST['strike_rate'] ?? 0),
            intval($_POST['fifties'] ?? 0),
            intval($_POST['hundreds'] ?? 0),
            floatval($_POST['overs'] ?? 0),
            intval($_POST['runs_conceded'] ?? 0),
            intval($_POST['wickets'] ?? 0),
            floatval($_POST['economy'] ?? 0),
            intval($_POST['maidens'] ?? 0),
            intval($_POST['no_balls'] ?? 0),
            intval($_POST['wides'] ?? 0),
            intval($_POST['catches'] ?? 0),
            intval($_POST['run_outs'] ?? 0),
            intval($_POST['stumpings'] ?? 0)
        ]);

        // For backward compatibility, also insert/update aggregate player_stats table
        try {
            $aggStmt = $pdo->prepare("INSERT INTO player_stats (
                player_id, matches_played, innings, runs_scored, highest_score, batting_avg, strike_rate,
                fifties, hundreds, wickets, bowling_avg, economy, catches, stumpings
            ) VALUES (?, 1, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                matches_played = matches_played + 1,
                innings = innings + 1,
                runs_scored = runs_scored + VALUES(runs_scored),
                highest_score = GREATEST(highest_score, VALUES(highest_score)),
                fifties = fifties + VALUES(fifties),
                hundreds = hundreds + VALUES(hundreds),
                wickets = wickets + VALUES(wickets),
                catches = catches + VALUES(catches),
                stumpings = stumpings + VALUES(stumpings)");

            $r = intval($_POST['runs'] ?? 0);
            $fifties = intval($_POST['fifties'] ?? 0);
            $hundreds = intval($_POST['hundreds'] ?? 0);
            $w = intval($_POST['wickets'] ?? 0);
            $c = intval($_POST['catches'] ?? 0);
            $st = intval($_POST['stumpings'] ?? 0);
            $sr = floatval($_POST['strike_rate'] ?? 0);
            $eco = floatval($_POST['economy'] ?? 0);
            $b_avg = $w > 0 ? (floatval($_POST['runs_conceded'] ?? 0) / $w) : 0;

            $aggStmt->execute([
                $new_player_id, $r, $r, $r, $sr, $fifties, $hundreds, $w, $b_avg, $eco, $c, $st
            ]);
        } catch (Exception $e) {
            // Ignore if player_stats table schema is different
        }

    } catch (Exception $e) {
        $pdo->rollBack();
        sendError('Failed to save statistics.');
    }
}

// 10. Commit Transaction
try {
    $pdo->commit();
} catch (Exception $e) {
    sendError('Failed to commit transaction.');
}

// 11. Send Success Response
$successMsg = $add_stats_now 
    ? '✓ Player and Statistics Saved Successfully.' 
    : '✓ Player Registered Successfully.';

echo json_encode([
    'status' => 'success',
    'success' => true,
    'message' => $successMsg,
    'player_id' => $new_player_id,
    'full_name' => ucwords($full_name),
    'team_id' => $team_id,
    'category' => $category,
    'jersey_number' => $jersey_number
]);
exit;
