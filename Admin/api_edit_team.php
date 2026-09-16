<?php
require_once '../auth.php';
check_access('auction_admin');

require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$team_id = isset($_POST['team_id']) ? (int)$_POST['team_id'] : 0;
$team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
$owners = isset($_POST['owner']) && is_array($_POST['owner']) ? array_filter(array_map('trim', $_POST['owner'])) : [];
$ic_players = isset($_POST['ic_player']) && is_array($_POST['ic_player']) ? array_filter(array_map('trim', $_POST['ic_player'])) : [];
$remaining_purse = (isset($_POST['remaining_purse']) && $_POST['remaining_purse'] !== '') ? floatval($_POST['remaining_purse']) : null;
$total_purse = isset($_POST['total_purse']) ? floatval($_POST['total_purse']) : 10000000;
$max_strength = isset($_POST['max_strength']) ? (int)$_POST['max_strength'] : 13;

if ($team_id <= 0 || empty($team_name) || empty($owners) || empty($ic_players)) {
    echo json_encode(['status' => 'error', 'message' => 'Team ID, Name, at least one Owner, and at least one Icon Player are required.']);
    exit;
}

$uploadDir = 'team_assets/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Fetch current team to get existing images
$stmt = $pdo->prepare("SELECT team_logo, owner_img, ic_player_img FROM team_master WHERE id = ?");
$stmt->execute([$team_id]);
$currentTeam = $stmt->fetch();

if (!$currentTeam) {
    echo json_encode(['status' => 'error', 'message' => 'Team not found.']);
    exit;
}

function handleFileUpload($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileInputName];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            return ['error' => "Invalid file format for $fileInputName. Allowed formats: JPG, PNG, GIF, WEBP."];
        }

        $newFilename = uniqid(str_replace('_img', '', $fileInputName) . '_') . '.' . $ext;
        $destination = $uploadDir . $newFilename;

        if (move_uploaded_file($file['tmp_name'], $destination)) {
            $altDir = (strpos(__DIR__, 'Pretium_Cricket_Application_NewDesign') !== false)
                ? str_replace('Pretium_Cricket_Application_NewDesign', 'Pretium_Cricket_Application', $uploadDir)
                : str_replace('Pretium_Cricket_Application', 'Pretium_Cricket_Application_NewDesign', $uploadDir);
            if (is_dir(dirname($altDir))) {
                if (!is_dir($altDir)) @mkdir($altDir, 0777, true);
                @copy($destination, $altDir . $newFilename);
            }
            return ['filename' => $newFilename];
        } else {
            return ['error' => "Failed to move uploaded file for $fileInputName."];
        }
    }
    return ['filename' => null];
}

$updateFields = [
    'team_name' => $team_name,
    'total_purse' => $total_purse,
    'remaining_purse' => $remaining_purse,
    'max_strength' => $max_strength
];

// Handle Team Logo
$teamLogoRes = handleFileUpload('team_logo', $uploadDir);
if (isset($teamLogoRes['error'])) {
    echo json_encode(['status' => 'error', 'message' => $teamLogoRes['error']]);
    exit;
}
if ($teamLogoRes['filename']) {
    $updateFields['team_logo'] = $teamLogoRes['filename'];
}

// Existing images from DB
$current_owner_imgs = json_decode($currentTeam['owner_img'], true) ?: [];
$current_ic_player_imgs = json_decode($currentTeam['ic_player_img'], true) ?: [];

// We need to re-index or match based on array positions
$new_owner_imgs = [];
$owner_count = count($owners);
$owner_keys = array_keys($owners);

for ($i = 0; $i < $owner_count; $i++) {
    $original_key = $owner_keys[$i];
    if (isset($_FILES['owner_img']['name'][$original_key]) && $_FILES['owner_img']['error'][$original_key] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['owner_img']['name'][$original_key], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('owner_') . '.' . $ext;
            if (move_uploaded_file($_FILES['owner_img']['tmp_name'][$original_key], $uploadDir . $newFilename)) {
                $new_owner_imgs[] = $newFilename;
                continue;
            }
        }
    }
    // Fallback to existing image if any
    $new_owner_imgs[] = isset($current_owner_imgs[$i]) ? $current_owner_imgs[$i] : null;
}

$new_ic_player_imgs = [];
$ic_player_count = count($ic_players);
$ic_player_keys = array_keys($ic_players);

for ($i = 0; $i < $ic_player_count; $i++) {
    $original_key = $ic_player_keys[$i];
    if (isset($_FILES['ic_player_img']['name'][$original_key]) && $_FILES['ic_player_img']['error'][$original_key] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['ic_player_img']['name'][$original_key], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($ext, $allowed)) {
            $newFilename = uniqid('ic_player_') . '.' . $ext;
            if (move_uploaded_file($_FILES['ic_player_img']['tmp_name'][$original_key], $uploadDir . $newFilename)) {
                $new_ic_player_imgs[] = $newFilename;
                continue;
            }
        }
    }
    // Fallback to existing image if any
    $new_ic_player_imgs[] = isset($current_ic_player_imgs[$i]) ? $current_ic_player_imgs[$i] : null;
}

$updateFields['owner'] = json_encode(array_values($owners));
$updateFields['owner_img'] = json_encode($new_owner_imgs);
$updateFields['ic_player'] = json_encode(array_values($ic_players));
$updateFields['ic_player_img'] = json_encode($new_ic_player_imgs);

try {
    $setClause = [];
    $params = [];
    foreach ($updateFields as $field => $value) {
        $setClause[] = "$field = :$field";
        $params[":$field"] = $value;
    }
    $params[':id'] = $team_id;

    $sql = "UPDATE team_master SET " . implode(', ', $setClause) . " WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    
    echo json_encode(['status' => 'success', 'message' => 'Team updated successfully!']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
