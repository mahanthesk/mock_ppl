<?php
// api_edit_combined.php — Update both player registration details AND player stats in one request
header('Content-Type: application/json');

require_once 'auth.php';
require_once 'db_connection.php';

if (!is_role_logged_in('registration_admin') && !is_role_logged_in('auction_admin') && !isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

function sendErr($msg) {
    echo json_encode(['success' => false, 'message' => $msg]);
    exit;
}

// ── Required player fields ──────────────────────────────────────────────────
$reg_id    = intval($_POST['id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$category  = trim($_POST['category']  ?? '');

if ($reg_id <= 0)       sendErr('Invalid player registration ID.');
if (empty($full_name))  sendErr('Player Name is required.');
if (empty($category))   sendErr('Category is required.');

// Fetch existing record
$exist = $pdo->prepare("SELECT * FROM registrations WHERE id = ? AND deleted_at IS NULL");
$exist->execute([$reg_id]);
$existing = $exist->fetch(PDO::FETCH_ASSOC);
if (!$existing) sendErr('Player not found.');

// Jersey uniqueness in team (exclude current)
$jersey_number = trim($_POST['jersey_number'] ?? '');
$team_id = $existing['team_id'];
if ($jersey_number !== '' && $team_id) {
    $jChk = $pdo->prepare("SELECT id FROM registrations WHERE team_id=? AND jersey_number=? AND jersey_number>0 AND id!=? AND deleted_at IS NULL");
    $jChk->execute([intval($team_id), intval($jersey_number), $reg_id]);
    if ($jChk->fetch()) sendErr('Jersey Number already taken by another player in this team.');
}

// ── Handle photo upload ─────────────────────────────────────────────────────
$profile_pic_path = $existing['profile_pic'];
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
        $dest = $uploadDir . $existing['player_id'] . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
            $profile_pic_path = $dest;
        }
    }
}

try {
    $pdo->beginTransaction();

    // ── 1. Update registrations ─────────────────────────────────────────────
    $updReg = $pdo->prepare("UPDATE registrations SET
        salutation=?, full_name=?, dob=?, category=?, profile_pic=?, experience=?,
        jersey_nickname=?,
        jersey_number=?, jersey_size=?, track_pant_size=?
        WHERE id=? AND deleted_at IS NULL");

    $updReg->execute([
        trim($_POST['salutation']      ?? 'Mr.'),
        $full_name,
        !empty($_POST['dob'])          ? $_POST['dob'] : null,
        $category,
        $profile_pic_path,
        trim($_POST['experience']      ?? 'Professional'),
        trim($_POST['jersey_nickname'] ?? ''),
        $jersey_number !== '' ? intval($jersey_number) : null,
        trim($_POST['jersey_size']     ?? 'M'),
        !empty($_POST['track_pant_size']) ? intval($_POST['track_pant_size']) : 0,
        $reg_id
    ]);

    // ── 2. Upsert player_stats ──────────────────────────────────────────────
    // Check if stats record exists
    $chkStats = $pdo->prepare("SELECT id FROM player_stats WHERE player_id = ?");
    $chkStats->execute([$existing['player_id']]);
    $statsRow = $chkStats->fetch(PDO::FETCH_ASSOC);

    $matches    = intval($_POST['matches_played'] ?? 0);
    $innings    = intval($_POST['innings']        ?? 0);
    $runs       = intval($_POST['runs_scored']    ?? 0);
    $highest    = intval($_POST['highest_score']  ?? 0);
    $bat_avg    = floatval($_POST['batting_avg']  ?? 0);
    $sr         = floatval($_POST['strike_rate']  ?? 0);
    $fifties    = intval($_POST['fifties']        ?? 0);
    $hundreds   = intval($_POST['hundreds']       ?? 0);
    $wickets    = intval($_POST['wickets']        ?? 0);
    $bowl_avg   = floatval($_POST['bowling_avg']  ?? 0);
    $economy    = floatval($_POST['economy']      ?? 0);
    $best_bowl  = trim($_POST['best_bowling']     ?? '');
    $catches    = intval($_POST['catches']        ?? 0);
    $stumpings  = intval($_POST['stumpings']      ?? 0);

    if ($statsRow) {
        $updStats = $pdo->prepare("UPDATE player_stats SET
            matches_played=?, innings=?, runs_scored=?, highest_score=?,
            batting_avg=?, strike_rate=?, fifties=?, hundreds=?,
            wickets=?, bowling_avg=?, economy=?, best_bowling=?,
            catches=?, stumpings=?
            WHERE player_id=?");
        $updStats->execute([
            $matches,$innings,$runs,$highest,$bat_avg,$sr,$fifties,$hundreds,
            $wickets,$bowl_avg,$economy,$best_bowl,$catches,$stumpings,
            $existing['player_id']
        ]);
    } else {
        // Create stats record if it doesn't exist yet
        $insStats = $pdo->prepare("INSERT INTO player_stats
            (player_id, matches_played, innings, runs_scored, highest_score,
             batting_avg, strike_rate, fifties, hundreds, wickets,
             bowling_avg, economy, best_bowling, catches, stumpings)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $insStats->execute([
            $existing['player_id'],
            $matches,$innings,$runs,$highest,$bat_avg,$sr,$fifties,$hundreds,
            $wickets,$bowl_avg,$economy,$best_bowl,$catches,$stumpings
        ]);
    }

    $pdo->commit();

    echo json_encode([
        'success'   => true,
        'message'   => '✓ Player details and statistics updated successfully.',
        'id'        => $reg_id,
        'full_name' => ucwords($full_name)
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
exit;
