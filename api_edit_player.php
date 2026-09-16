<?php
// api_edit_player.php — Update an existing player's registration details
header('Content-Type: application/json');

require_once 'auth.php';
require_once 'db_connection.php';

// Authorization check
if (!is_role_logged_in('registration_admin') && !is_role_logged_in('auction_admin') && !isset($_SESSION['admin_id'])) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => 'Unauthorized access.']);
    exit;
}

function sendError($msg) {
    echo json_encode(['status' => 'error', 'success' => false, 'message' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Invalid request method.');
}

// Required fields
$id = intval($_POST['id'] ?? 0);
if ($id <= 0) {
    sendError('Invalid player ID.');
}

$full_name = trim($_POST['full_name'] ?? '');
if (empty($full_name)) {
    sendError('Player Name is required.');
}

$category = trim($_POST['category'] ?? '');
if (empty($category)) {
    sendError('Category is required.');
}

// Fetch the existing player to get current photo path & player_id
$stmt = $pdo->prepare("SELECT * FROM registrations WHERE id = ? AND deleted_at IS NULL");
$stmt->execute([$id]);
$existing = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$existing) {
    sendError('Player not found.');
}

// Jersey number uniqueness within same team (exclude current player)
$jersey_number = trim($_POST['jersey_number'] ?? '');
$team_id = $existing['team_id'];
if ($jersey_number !== '' && $team_id) {
    $jCheck = $pdo->prepare("SELECT id FROM registrations WHERE team_id = ? AND jersey_number = ? AND jersey_number > 0 AND id != ? AND deleted_at IS NULL");
    $jCheck->execute([intval($team_id), intval($jersey_number), $id]);
    if ($jCheck->fetch()) {
        sendError('Jersey Number already taken by another player in this team.');
    }
}

// Handle Photo Upload (optional)
$profile_pic_path = $existing['profile_pic']; // Keep old photo by default
if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = 'uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $ext = strtolower(pathinfo($_FILES['profile_pic']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        $dest = $uploadDir . $existing['player_id'] . '_' . time() . '.' . $ext;
        if (move_uploaded_file($_FILES['profile_pic']['tmp_name'], $dest)) {
            $profile_pic_path = $dest;
        }
    }
}

// Update the player record
try {
    $sql = "UPDATE registrations SET
        salutation       = ?,
        full_name        = ?,
        dob              = ?,
        category         = ?,
        profile_pic      = ?,
        experience       = ?,
        jersey_nickname  = ?,
        jersey_number    = ?,
        jersey_size      = ?,
        track_pant_size  = ?,
        updated_at       = NOW()
    WHERE id = ? AND deleted_at IS NULL";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
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
        $id
    ]);

    // Ensure updated_at column exists (auto-handle)
} catch (Exception $e) {
    // Try without updated_at in case the column doesn't exist
    try {
        $sql2 = "UPDATE registrations SET
            salutation       = ?,
            full_name        = ?,
            dob              = ?,
            category         = ?,
            profile_pic      = ?,
            experience       = ?,
            jersey_nickname  = ?,
            jersey_number    = ?,
            jersey_size      = ?,
            track_pant_size  = ?
        WHERE id = ? AND deleted_at IS NULL";

        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([
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
            $id
        ]);
    } catch (Exception $e2) {
        sendError('Failed to update player: ' . $e2->getMessage());
    }
}

echo json_encode([
    'status'    => 'success',
    'success'   => true,
    'message'   => '✓ Player updated successfully.',
    'id'        => $id,
    'full_name' => ucwords($full_name),
]);
exit;
