<?php
require_once '../auth.php';
check_access('auction_admin');

require_once 'db_connection.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit;
}

$team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';
$owners = isset($_POST['owner']) && is_array($_POST['owner']) ? array_filter(array_map('trim', $_POST['owner'])) : [];
$ic_players = isset($_POST['ic_player']) && is_array($_POST['ic_player']) ? array_filter(array_map('trim', $_POST['ic_player'])) : [];
$total_purse = isset($_POST['total_purse']) ? (int)$_POST['total_purse'] : 10000000;
$max_strength = isset($_POST['max_strength']) ? (int)$_POST['max_strength'] : 13;

if (empty($team_name) || empty($owners) || empty($ic_players)) {
    echo json_encode(['status' => 'error', 'message' => 'Team name, at least one owner name, and at least one icon player name are required.']);
    exit;
}

// Ensure the team_assets directory exists
$uploadDir = 'team_assets/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true)) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to create upload directory.']);
        exit;
    }
}

// Function to handle single file upload safely
function handleFileUpload($fileInputName, $uploadDir) {
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES[$fileInputName];
        
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (!in_array($ext, $allowed)) {
            return ['error' => "Invalid file format for $fileInputName. Allowed formats: JPG, PNG, GIF, WEBP."];
        }

        // Generate unique name
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

// Function to handle multiple file uploads
function handleMultipleFileUploads($fileInputName, $uploadDir, $count) {
    $filenames = [];
    for ($i = 0; $i < $count; $i++) {
        if (isset($_FILES[$fileInputName]['name'][$i]) && $_FILES[$fileInputName]['error'][$i] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES[$fileInputName]['name'][$i], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowed)) {
                $filenames[] = null; // Or handle error
                continue;
            }
            $newFilename = uniqid(str_replace('_img', '', $fileInputName) . '_') . '.' . $ext;
            $destination = $uploadDir . $newFilename;
            if (move_uploaded_file($_FILES[$fileInputName]['tmp_name'][$i], $destination)) {
                $filenames[] = $newFilename;
            } else {
                $filenames[] = null;
            }
        } else {
            $filenames[] = null;
        }
    }
    return $filenames;
}

// Process Uploads
$teamLogoRes = handleFileUpload('team_logo', $uploadDir);
if (isset($teamLogoRes['error'])) {
    echo json_encode(['status' => 'error', 'message' => $teamLogoRes['error']]);
    exit;
}
$team_logo = $teamLogoRes['filename'] ?: '';

// For arrays, process each uploaded file corresponding to an owner/icon player
$owner_count = count($owners);
$ic_player_count = count($ic_players);

$owner_imgs = handleMultipleFileUploads('owner_img', $uploadDir, $owner_count);
$ic_player_imgs = handleMultipleFileUploads('ic_player_img', $uploadDir, $ic_player_count);

$owner_json = json_encode(array_values($owners));
$owner_img_json = json_encode($owner_imgs);
$ic_player_json = json_encode(array_values($ic_players));
$ic_player_img_json = json_encode($ic_player_imgs);

try {
    // Insert into DB
    $stmt = $pdo->prepare("
        INSERT INTO team_master (team_name, team_logo, owner, owner_img, ic_player, ic_player_img, total_purse, max_strength) 
        VALUES (:team_name, :team_logo, :owner, :owner_img, :ic_player, :ic_player_img, :total_purse, :max_strength)
    ");
    
    $stmt->execute([
        ':team_name' => $team_name,
        ':team_logo' => $team_logo,
        ':owner' => $owner_json,
        ':owner_img' => $owner_img_json,
        ':ic_player' => $ic_player_json,
        ':ic_player_img' => $ic_player_img_json,
        ':total_purse' => $total_purse,
        ':max_strength' => $max_strength
    ]);
    
    $new_team_id = $pdo->lastInsertId();
    $new_username = 'owner' . $new_team_id;
    $default_pwd_hash = password_hash('owner123', PASSWORD_DEFAULT);
    $full_owner_name = implode(', ', $owners);

    // Insert into users table
    $userStmt = $pdo->prepare("
        INSERT INTO users (username, password, role, team_id, team_name, full_name)
        VALUES (?, ?, 'team_owner', ?, ?, ?)
        ON DUPLICATE KEY UPDATE password = VALUES(password), team_name = VALUES(team_name), full_name = VALUES(full_name)
    ");
    $userStmt->execute([$new_username, $default_pwd_hash, $new_team_id, $team_name, $full_owner_name]);

    // Insert into team_owners table
    try {
        $ownerStmt = $pdo->prepare("
            INSERT INTO team_owners (id, team_id, username, password)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE password = VALUES(password)
        ");
        $ownerStmt->execute([$new_team_id, $new_team_id, $new_username, $default_pwd_hash]);
    } catch (PDOException $ex) {
        // Silently continue if team_owners table has legacy constraints
    }

    echo json_encode(['status' => 'success', 'message' => "Team added successfully! Owner login: $new_username"]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
