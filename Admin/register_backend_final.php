<?php
// register_backend.php
header('Content-Type: application/json');

require 'db_connection.php';

function sendResponse($status, $message, $data = [], $errorField = null)
{
    $response = ['status' => $status, 'message' => $message];
    if (!empty($data)) {
        $response = array_merge($response, $data);
    }
    if ($errorField) {
        $response['error_field'] = $errorField;
    }
    echo json_encode($response);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendResponse('error', 'Invalid request method');
}

// 1. Validate Inputs with specific field tracking
$errors = [];

// Full Name validation
if (empty($_POST['full_name'])) {
    $errors['full_name'] = 'Full name is required.';
} elseif (strlen($_POST['full_name']) < 2) {
    $errors['full_name'] = 'Full name must be at least 2 characters.';
}

// Mobile validation
if (empty($_POST['mobile'])) {
    $errors['mobile'] = 'Mobile number is required.';
} elseif (!preg_match('/^[0-9]{10}$/', $_POST['mobile'])) {
    $errors['mobile'] = 'Please enter a valid 10-digit mobile number.';
}

// Email validation
if (empty($_POST['email'])) {
    $errors['email'] = 'Email address is required.';
} else {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format.';
    }
}

// Category validation
if (empty($_POST['category'])) {
    $errors['category'] = 'Please select a category.';
}

// Role validation
if (empty($_POST['role'])) {
    $errors['role'] = 'Please select your role.';
}

// Batting type validation
if (empty($_POST['batting_type'])) {
    $errors['batting_type'] = 'Please select batting type.';
}

// Bowling type validation
if (empty($_POST['bowling_type'])) {
    $errors['bowling_type'] = 'Please select bowling type.';
}

// Jersey number validation
if (empty($_POST['jersey_number'])) {
    $errors['jersey_number'] = 'Jersey number is required.';
} elseif ($_POST['jersey_number'] < 1 || $_POST['jersey_number'] > 99) {
    $errors['jersey_number'] = 'Jersey number must be between 1 and 99.';
}

// Jersey nickname validation
if (empty($_POST['jersey_nickname'])) {
    $errors['jersey_nickname'] = 'Nickname is required.';
}

// Experience validation
if (empty($_POST['experience'])) {
    $errors['experience'] = 'Please select experience level.';
}

// Track pant size validation
if (empty($_POST['track_pant_size'])) {
    $errors['track_pant_size'] = 'Track pant size is required.';
}

// If there are validation errors, return them
if (!empty($errors)) {
    $firstErrorField = array_key_first($errors);
    sendResponse('error', 'Please fix the validation errors.', ['errors' => $errors], $firstErrorField);
}

// 2. Check for Duplicates (Email or Mobile)
$mobile = $_POST['mobile'];
try {
    $stmt = $pdo->prepare("SELECT id FROM registrations WHERE (email = ? OR mobile = ?) AND deleted_at IS NULL");
    $stmt->execute([$email, $mobile]);
    if ($stmt->fetch()) {
        $dupErrors = ['mobile' => 'User with this Mobile Number or Email ID already exists.'];
        sendResponse('error', 'User already exists.', ['errors' => $dupErrors], 'mobile');
    }
} catch (PDOException $e) {
    sendResponse('error', 'Database error checking duplicates: ' . $e->getMessage());
}

// 2b. Conditional Validation for Producer/Media
$category = $_POST['category'];
if (($category === 'Producers' || $category === 'Media') && empty($_FILES['prod_media_id_file']['name'])) {
    $idErrors = ['prod_media_id_file' => "ID Card is required for $category."];
    sendResponse('error', "ID Card is required.", ['errors' => $idErrors], 'prod_media_id_file');
}

// 3. Handle File Uploads
$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

// Helper to upload file
function uploadFile($fileInputName, $targetDir, $suffix = '')
{
    if (isset($_FILES[$fileInputName]) && $_FILES[$fileInputName]['error'] === UPLOAD_ERR_OK) {
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $fileTmpPath = $_FILES[$fileInputName]['tmp_name'];
        $fileName = $_FILES[$fileInputName]['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Custom naming: fullname_timestamp.ext
        $fullName = $_POST['full_name'] ?? 'user';
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $fullName);
        $safeName = preg_replace('/_+/', '_', $safeName);

        $newFileName = $safeName . '_' . time() . '_' . uniqid() . $suffix . '.' . $fileExtension;

        $allowedExtensions = ['jpg', 'gif', 'png', 'jpeg', 'webp', 'pdf'];

        if (in_array($fileExtension, $allowedExtensions)) {
            $dest_path = $targetDir . $newFileName;
            if (move_uploaded_file($fileTmpPath, $dest_path)) {
                return $dest_path;
            }
        }
    }
    return null;
}

$profile_pic_path = uploadFile('profile_pic', $uploadDir, '_profile');

// Upload Prod/Media ID to separate folder
$prod_media_id_path = null;
if ($category === 'Producers' || $category === 'Media') {
    $idProofDir = 'id_proof/';
    $prod_media_id_path = uploadFile('prod_media_id_file', $idProofDir, '_idproof');

    if (!$prod_media_id_path) {
        $uploadErrors = ['prod_media_id_file' => 'Failed to upload ID Card. Allowed formats: JPG, PNG, PDF.'];
        sendResponse('error', 'Upload failed.', ['errors' => $uploadErrors], 'prod_media_id_file');
    }
}


// 4. Generate Sequential Player ID
// Logic: Get the last created player_id, extract number, increment.
// This handles gaps in auto-increment ID.
try {
    $pdo->beginTransaction();

    // Lock the table or just select for update to be safe(r) in low concurrency
    // Find the highest current player_id
    // Assuming format PPL-001, PPL-002...

    $stmt = $pdo->query("SELECT player_id FROM registrations WHERE player_id LIKE 'PPL-%' ORDER BY LENGTH(player_id) DESC, player_id DESC LIMIT 1");
    $last_row = $stmt->fetch();

    $next_num = 1;
    if ($last_row && !empty($last_row['player_id'])) {
        $last_id = $last_row['player_id']; // e.g. PPL-005
        $parts = explode('-', $last_id);
        if (count($parts) == 2 && is_numeric($parts[1])) {
            $next_num = intval($parts[1]) + 1;
        }
    }

    // Pad with zeros (e.g., 001, 002, 010, 100)
    $new_player_id = 'PPL-' . str_pad($next_num, 3, '0', STR_PAD_LEFT);

    // 5. Insert Data
    $sql = "INSERT INTO registrations (
        player_id, salutation, full_name, mobile, email, address, category, profile_pic, 
        role, experience, batting_type, bowling_type, jersey_nickname, jersey_number, 
        jersey_size, track_pant_size, prod_media_id
    ) VALUES (
        ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?
    )";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $new_player_id,
        $_POST['salutation'] ?? '',
        $_POST['full_name'],
        $mobile,
        $email,
        $_POST['address'] ?? '',
        $_POST['category'],
        $profile_pic_path,
        $_POST['role'],
        $_POST['experience'],
        $_POST['batting_type'],
        $_POST['bowling_type'],
        $_POST['jersey_nickname'] ?? '',
        $_POST['jersey_number'],
        $_POST['jersey_size'] ?? '',
        $_POST['track_pant_size'] ?? 0,
        $prod_media_id_path
    ]);

    $pdo->commit();

    // Prepare Response Data
    $responseData = [
        'player_id' => $new_player_id,
        'full_name' => ucwords($_POST['full_name']),
        'category' => $_POST['category'],
        'role' => $_POST['role'],
        'jersey_number' => $_POST['jersey_number'],
        'jersey_nickname' => strtoupper($_POST['jersey_nickname'] ?? ''),
        'batting_type' => $_POST['batting_type'],
        'bowling_type' => $_POST['bowling_type'],
        'experience' => $_POST['experience'],
        'jersey_size' => $_POST['jersey_size'] ?? '',
        'mobile' => $mobile,
        'profile_pic' => $profile_pic_path
    ];

    sendResponse('success', 'Registration Successful!', ['data' => $responseData]);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // Duplicate check for player_id (rare race condition)
    if ($e->getCode() == 23000) {
        sendResponse('error', 'Registration conflicts (duplicate entry). Please try again.');
    }
    sendResponse('error', 'Database Error: ' . $e->getMessage());
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    sendResponse('error', 'System Error: ' . $e->getMessage());
}
?>