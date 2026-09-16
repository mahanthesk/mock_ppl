<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$message = '';
$error = '';
$error_log = [];
$upload_summary = null;

// Ensure upload_history table has required columns for unified upload summary & error logs
try {
    $cols = array_column($pdo->query("SHOW COLUMNS FROM upload_history")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    if (!in_array('successful_registrations', $cols)) {
        $pdo->exec("ALTER TABLE upload_history ADD COLUMN successful_registrations INT(11) DEFAULT 0 AFTER total_records");
    }
    if (!in_array('successful_stats', $cols)) {
        $pdo->exec("ALTER TABLE upload_history ADD COLUMN successful_stats INT(11) DEFAULT 0 AFTER successful_registrations");
    }
    if (!in_array('error_log', $cols)) {
        $pdo->exec("ALTER TABLE upload_history ADD COLUMN error_log LONGTEXT NULL AFTER failed_records");
    }
} catch (Exception $e) {
    // Ignore schema upgrade error if occurs
}

// Handle delete history
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_history_id'])) {
    $del_id = intval($_POST['delete_history_id']);
    try {
        $stmt = $pdo->prepare("DELETE FROM upload_history WHERE id = ?");
        $stmt->execute([$del_id]);
        // PRG pattern: redirect to avoid "Resubmit form?" on refresh
        header('Location: ' . $_SERVER['PHP_SELF'] . '?deleted=1');
        exit;
    } catch (Exception $e) {
        $error = "Failed to delete record: " . $e->getMessage();
    }
}

// Show success message after redirect
if (isset($_GET['deleted']) && $_GET['deleted'] == '1') {
    $message = "Upload history record deleted successfully.";
}

// Handle file upload or Google Sheets link
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['upload_submit']) || isset($_FILES['upload_file']) || !empty($_POST['google_sheets_link']))) {
    $file_name = '';
        $tmp_name = '';
        $is_csv = false;

        // Check if file is uploaded
        if (isset($_FILES['upload_file']) && $_FILES['upload_file']['error'] === UPLOAD_ERR_OK) {
            $file_name = $_FILES['upload_file']['name'];
            $tmp_name = $_FILES['upload_file']['tmp_name'];
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

            if ($ext === 'csv') {
                $is_csv = true;
            } elseif (in_array($ext, ['xls', 'xlsx'])) {
                // If they really upload excel, we can't parse it natively without PhpSpreadsheet.
                // We'll throw an error and guide them to use CSV.
                $error = "Excel files (.xlsx, .xls) are currently not supported natively. Please open your Excel file, choose 'Save As', and select 'CSV (Comma delimited)' format, then upload the CSV file.";
            } else {
                $error = "Invalid file format. Please upload a CSV file.";
            }
        } 
        // Check if Google Sheets link is provided
        elseif (!empty($_POST['google_sheets_link'])) {
            $link = trim($_POST['google_sheets_link']);
            if (filter_var($link, FILTER_VALIDATE_URL) && strpos($link, 'google.com') !== false) {
                // If it's a standard sharing link, try to convert it to a CSV export link
                if (preg_match('/\/d\/([a-zA-Z0-9-_]+)/', $link, $matches)) {
                    $sheet_id = $matches[1];
                    // Also check for gid in case they are sharing a specific sub-sheet
                    $gid_param = '';
                    if (preg_match('/[?&]gid=([0-9]+)/', $link, $gid_matches)) {
                        $gid_param = '&gid=' . $gid_matches[1];
                    }
                    $link = "https://docs.google.com/spreadsheets/d/{$sheet_id}/export?format=csv{$gid_param}";
                }
                
                // Try to download the content
                $content = @file_get_contents($link);
                if ($content !== false) {
                    $tmp_name = tempnam(sys_get_temp_dir(), 'spl_');
                    file_put_contents($tmp_name, $content);
                    $file_name = 'Google_Sheets_Import.csv';
                    $is_csv = true;
                } else {
                    $error = "Could not fetch data from the provided Google Sheets link. Please ensure it is a valid 'Publish to web' CSV link.";
                }
            } else {
                $error = "Invalid Google Sheets link.";
            }
        } else {
            $error = "Please upload a file or provide a Google Sheets link.";
        }

        // Process CSV
        if ($is_csv && empty($error) && $tmp_name) {
            $handle = fopen($tmp_name, "r");
            if ($handle !== FALSE) {
                $header = fgetcsv($handle, 4000, ",");
                if ($header) {
                    // Clean header (remove BOM if present)
                    $header[0] = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $header[0]);
                    
                    // Map expected columns for both Registration and Player Stats
                    $reg_expected = ['Player ID', 'Salutation', 'Full Name', 'Mobile', 'Email', 'Address', 'Category', 'Role', 'Experience', 'Batting Type', 'Bowling Type', 'Jersey Nickname', 'Jersey Number', 'Jersey Size', 'Track Pant Size'];
                    $stats_expected = ['Matches Played', 'Innings', 'Runs Scored', 'Highest Score', 'Batting Avg', 'Strike Rate', 'Fifties', 'Hundreds', 'Wickets', 'Bowling Avg', 'Economy', 'Best Bowling', 'Catches', 'Stumpings'];
                    $all_expected = array_merge($reg_expected, $stats_expected);

                    $col_map = [];
                    $header_lower = array_map('strtolower', array_map('trim', $header));
                    foreach ($all_expected as $col) {
                        $idx = array_search(strtolower($col), $header_lower);
                        $col_map[$col] = $idx !== false ? $idx : -1;
                    }

                    // At minimum, we should have either registration columns OR stats columns
                    $has_reg_cols = ($col_map['Full Name'] !== -1 && $col_map['Mobile'] !== -1 && $col_map['Email'] !== -1);
                    $has_stats_cols = ($col_map['Matches Played'] !== -1 || $col_map['Runs Scored'] !== -1 || $col_map['Wickets'] !== -1);
                    $has_player_id_col = ($col_map['Player ID'] !== -1);

                    if (!$has_reg_cols && !$has_stats_cols && !$has_player_id_col) {
                        $error = "Missing required columns in the CSV. Please download the sample template and ensure required headers are present.";
                    } else {
                        $total = 0;
                        $reg_success = 0;
                        $stats_success = 0;
                        $failed = 0;
                        $error_log = [];

                        // Pre-load existing registrations into memory for zero-query lookups
                        $stmt_existing = $pdo->query("SELECT player_id, mobile, email FROM registrations WHERE deleted_at IS NULL");
                        $existing_records = $stmt_existing->fetchAll(PDO::FETCH_ASSOC);
                        $players_by_id = [];
                        $players_by_mobile = [];
                        $players_by_email = [];
                        foreach ($existing_records as $rec) {
                            $players_by_id[$rec['player_id']] = $rec['player_id'];
                            if (!empty($rec['mobile'])) $players_by_mobile[$rec['mobile']] = $rec['player_id'];
                            if (!empty($rec['email'])) $players_by_email[strtolower($rec['email'])] = $rec['player_id'];
                        }

                        // Get next player ID sequence number (for new registrations)
                        $stmt_max = $pdo->query("SELECT MAX(CAST(SUBSTRING(player_id, 6) AS UNSIGNED)) as max_id FROM registrations WHERE player_id LIKE 'PPL-%'");
                        $max_row = $stmt_max->fetch(PDO::FETCH_ASSOC);
                        $next_id_num = ($max_row && $max_row['max_id']) ? intval($max_row['max_id']) + 1 : 1;

                        // Prepare SQL statements once before loop
                        $stmt_reg_insert = $pdo->prepare("INSERT INTO registrations (player_id, salutation, full_name, mobile, email, address, category, role, experience, batting_type, bowling_type, jersey_nickname, jersey_number, jersey_size, track_pant_size) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        
                        $stmt_reg_update = $pdo->prepare("UPDATE registrations SET salutation = COALESCE(NULLIF(?,''), salutation), full_name = COALESCE(NULLIF(?,''), full_name), mobile = COALESCE(NULLIF(?,''), mobile), email = COALESCE(NULLIF(?,''), email), address = COALESCE(NULLIF(?,''), address), category = COALESCE(NULLIF(?,''), category), role = COALESCE(NULLIF(?,''), role), experience = COALESCE(NULLIF(?,''), experience), batting_type = COALESCE(NULLIF(?,''), batting_type), bowling_type = COALESCE(NULLIF(?,''), bowling_type), jersey_nickname = COALESCE(NULLIF(?,''), jersey_nickname), jersey_number = COALESCE(?, jersey_number), jersey_size = COALESCE(NULLIF(?,''), jersey_size), track_pant_size = COALESCE(?, track_pant_size) WHERE player_id = ?");

                        $stmt_stats_upsert = $pdo->prepare("
                            INSERT INTO player_stats 
                                (player_id, matches_played, innings, runs_scored, highest_score, batting_avg, strike_rate, fifties, hundreds, wickets, bowling_avg, economy, best_bowling, catches, stumpings)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE
                                matches_played = VALUES(matches_played),
                                innings = VALUES(innings),
                                runs_scored = VALUES(runs_scored),
                                highest_score = VALUES(highest_score),
                                batting_avg = VALUES(batting_avg),
                                strike_rate = VALUES(strike_rate),
                                fifties = VALUES(fifties),
                                hundreds = VALUES(hundreds),
                                wickets = VALUES(wickets),
                                bowling_avg = VALUES(bowling_avg),
                                economy = VALUES(economy),
                                best_bowling = VALUES(best_bowling),
                                catches = VALUES(catches),
                                stumpings = VALUES(stumpings)
                        ");

                        $row_num = 1; // Header is row 1, first data row is 2
                        while (($data = fgetcsv($handle, 4000, ",")) !== FALSE) {
                            $row_num++;
                            // Skip completely empty rows
                            if (empty(array_filter($data))) continue;
                            $total++;

                            // Extract input fields
                            $input_player_id = $col_map['Player ID'] !== -1 ? trim($data[$col_map['Player ID']] ?? '') : '';
                            $full_name = $col_map['Full Name'] !== -1 ? trim($data[$col_map['Full Name']] ?? '') : '';
                            $mobile = $col_map['Mobile'] !== -1 ? trim($data[$col_map['Mobile']] ?? '') : '';
                            $email = $col_map['Email'] !== -1 ? trim($data[$col_map['Email']] ?? '') : '';
                            $salutation = $col_map['Salutation'] !== -1 ? trim($data[$col_map['Salutation']] ?? '') : '';
                            $address = $col_map['Address'] !== -1 ? trim($data[$col_map['Address']] ?? '') : '';
                            $category = $col_map['Category'] !== -1 ? trim($data[$col_map['Category']] ?? '') : '';
                            $role = $col_map['Role'] !== -1 ? trim($data[$col_map['Role']] ?? '') : '';
                            $experience = $col_map['Experience'] !== -1 ? trim($data[$col_map['Experience']] ?? '') : '';
                            $batting_type = $col_map['Batting Type'] !== -1 ? trim($data[$col_map['Batting Type']] ?? '') : '';
                            $bowling_type = $col_map['Bowling Type'] !== -1 ? trim($data[$col_map['Bowling Type']] ?? '') : '';
                            $jersey_nickname = $col_map['Jersey Nickname'] !== -1 ? trim($data[$col_map['Jersey Nickname']] ?? '') : '';
                            
                            $jersey_number = $col_map['Jersey Number'] !== -1 && trim($data[$col_map['Jersey Number']] ?? '') !== '' ? trim($data[$col_map['Jersey Number']] ?? '') : null;
                            $jersey_number = is_numeric($jersey_number) ? (int)$jersey_number : null;
                            
                            $jersey_size = $col_map['Jersey Size'] !== -1 ? trim($data[$col_map['Jersey Size']] ?? '') : '';
                            
                            $track_pant_size = $col_map['Track Pant Size'] !== -1 && trim($data[$col_map['Track Pant Size']] ?? '') !== '' ? trim($data[$col_map['Track Pant Size']] ?? '') : null;
                            $track_pant_size = is_numeric($track_pant_size) ? (int)$track_pant_size : null;

                            // Determine if existing player
                            $is_existing = false;
                            $target_player_id = null;

                            if (!empty($input_player_id) && isset($players_by_id[$input_player_id])) {
                                $is_existing = true;
                                $target_player_id = $input_player_id;
                            } elseif (!empty($mobile) && isset($players_by_mobile[$mobile])) {
                                $is_existing = true;
                                $target_player_id = $players_by_mobile[$mobile];
                            } elseif (!empty($email) && isset($players_by_email[strtolower($email)])) {
                                $is_existing = true;
                                $target_player_id = $players_by_email[strtolower($email)];
                            }

                            // Validation checks
                            if (!$is_existing) {
                                // New player requires Full Name, Mobile, Email
                                $missing = [];
                                if (empty($full_name)) $missing[] = 'Full Name';
                                if (empty($mobile)) $missing[] = 'Mobile';
                                if (empty($email)) $missing[] = 'Email';
                                if (!empty($missing)) {
                                    $failed++;
                                    $error_log[] = [
                                        'row' => $row_num,
                                        'message' => 'Missing mandatory registration fields for new player.',
                                        'fields' => implode(', ', $missing)
                                    ];
                                    continue;
                                }
                            }

                            // Check email format if email is provided
                            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                $failed++;
                                $error_log[] = [
                                     'row' => $row_num,
                                     'message' => "Invalid email address format: '$email'",
                                     'fields' => 'Email'
                                ];
                                continue;
                            }

                            // Check for conflicts on mobile / email if changing or adding
                            if (!empty($mobile) && isset($players_by_mobile[$mobile]) && $players_by_mobile[$mobile] !== $target_player_id) {
                                $failed++;
                                $error_log[] = [
                                    'row' => $row_num,
                                    'message' => "Mobile number '$mobile' is already assigned to another player (" . $players_by_mobile[$mobile] . ").",
                                    'fields' => 'Mobile'
                                ];
                                continue;
                            }
                            if (!empty($email) && isset($players_by_email[strtolower($email)]) && $players_by_email[strtolower($email)] !== $target_player_id) {
                                $failed++;
                                $error_log[] = [
                                    'row' => $row_num,
                                    'message' => "Email '$email' is already assigned to another player (" . $players_by_email[strtolower($email)] . ").",
                                    'fields' => 'Email'
                                ];
                                continue;
                            }

                            // Determine player ID for new player
                            if (!$is_existing) {
                                if (!empty($input_player_id)) {
                                    $target_player_id = $input_player_id;
                                } else {
                                    $target_player_id = 'PPL-' . str_pad($next_id_num, 3, '0', STR_PAD_LEFT);
                                    $next_id_num++;
                                }
                            }

                            // Extract Player Stats fields
                            $matches_played = $col_map['Matches Played'] !== -1 ? intval($data[$col_map['Matches Played']] ?? 0) : 0;
                            $innings = $col_map['Innings'] !== -1 ? intval($data[$col_map['Innings']] ?? 0) : 0;
                            $runs_scored = $col_map['Runs Scored'] !== -1 ? intval($data[$col_map['Runs Scored']] ?? 0) : 0;
                            $highest_score = $col_map['Highest Score'] !== -1 ? intval($data[$col_map['Highest Score']] ?? 0) : 0;
                            $batting_avg = $col_map['Batting Avg'] !== -1 ? floatval($data[$col_map['Batting Avg']] ?? 0) : 0;
                            $strike_rate = $col_map['Strike Rate'] !== -1 ? floatval($data[$col_map['Strike Rate']] ?? 0) : 0;
                            $fifties = $col_map['Fifties'] !== -1 ? intval($data[$col_map['Fifties']] ?? 0) : 0;
                            $hundreds = $col_map['Hundreds'] !== -1 ? intval($data[$col_map['Hundreds']] ?? 0) : 0;
                            $wickets = $col_map['Wickets'] !== -1 ? intval($data[$col_map['Wickets']] ?? 0) : 0;
                            $bowling_avg = $col_map['Bowling Avg'] !== -1 ? floatval($data[$col_map['Bowling Avg']] ?? 0) : 0;
                            $economy = $col_map['Economy'] !== -1 ? floatval($data[$col_map['Economy']] ?? 0) : 0;
                            $best_bowling = $col_map['Best Bowling'] !== -1 ? trim($data[$col_map['Best Bowling']] ?? '') : '';
                            $catches = $col_map['Catches'] !== -1 ? intval($data[$col_map['Catches']] ?? 0) : 0;
                            $stumpings = $col_map['Stumpings'] !== -1 ? intval($data[$col_map['Stumpings']] ?? 0) : 0;

                            // Check if any stats data or columns were present in this row
                            $has_stats_data = ($has_stats_cols || $matches_played > 0 || $runs_scored > 0 || $wickets > 0);

                            // Execute row inside its own transaction for safe atomic per-row import
                            $pdo->beginTransaction();
                            try {
                                if ($is_existing) {
                                    // Only update registration if at least one registration column was supplied in the CSV
                                    if ($has_reg_cols) {
                                        $stmt_reg_update->execute([
                                            $salutation, $full_name, $mobile, $email, $address, $category, $role, $experience, $batting_type, $bowling_type, $jersey_nickname, $jersey_number, $jersey_size, $track_pant_size, $target_player_id
                                        ]);
                                        $reg_success++;
                                    }
                                } else {
                                    $stmt_reg_insert->execute([
                                        $target_player_id, $salutation, $full_name, $mobile, $email, $address, $category, $role, $experience, $batting_type, $bowling_type, $jersey_nickname, $jersey_number, $jersey_size, $track_pant_size
                                    ]);
                                    $reg_success++;
                                }

                                // Upsert stats
                                $stmt_stats_upsert->execute([
                                    $target_player_id, $matches_played, $innings, $runs_scored, $highest_score, $batting_avg, $strike_rate,
                                    $fifties, $hundreds, $wickets, $bowling_avg, $economy, $best_bowling,
                                    $catches, $stumpings
                                ]);
                                if ($has_stats_data) {
                                    $stats_success++;
                                }

                                $pdo->commit();

                                // Update in-memory lookups
                                $players_by_id[$target_player_id] = $target_player_id;
                                if (!empty($mobile)) $players_by_mobile[$mobile] = $target_player_id;
                                if (!empty($email)) $players_by_email[strtolower($email)] = $target_player_id;

                            } catch (Exception $e) {
                                $pdo->rollBack();
                                $failed++;
                                $error_log[] = [
                                    'row' => $row_num,
                                    'message' => 'Database Error: ' . $e->getMessage(),
                                    'fields' => 'Database Transaction'
                                ];
                            }
                        }

                        // Sync missing categories from registrations to categories table
                        try {
                            $pdo->exec("INSERT INTO categories (category_name) 
                                        SELECT DISTINCT category FROM registrations 
                                        WHERE category IS NOT NULL AND category != '' 
                                        AND category NOT IN (SELECT category_name FROM categories)");
                        } catch (PDOException $e) {
                            // Silently ignore if something goes wrong with sync
                        }

                        // Save to upload history
                        $hist_stmt = $pdo->prepare("INSERT INTO upload_history (file_name, uploaded_by, total_records, successful_records, successful_registrations, successful_stats, failed_records, status, error_log) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                        $error_log_json = !empty($error_log) ? json_encode($error_log) : null;
                        $status_text = $failed > 0 ? 'Completed with Errors' : 'Completed';
                        $hist_stmt->execute([
                            $file_name,
                            $_SESSION['admin_username'] ?? 'admin',
                            $total,
                            max($reg_success, $stats_success),
                            $reg_success,
                            $stats_success,
                            $failed,
                            $status_text,
                            $error_log_json
                        ]);

                        $upload_summary = [
                            'total' => $total,
                            'reg_success' => $reg_success,
                            'stats_success' => $stats_success,
                            'failed' => $failed
                        ];

                        $message = ($failed > 0) ? "Import completed with $failed error(s). Please check the log below." : "Import completed successfully!";
                    }
                } else {
                    $error = "Could not read header row from CSV.";
                }
                fclose($handle);
            } else {
                $error = "Could not open the uploaded file.";
            }
        }
    }

// Fetch history
$history = [];
try {
    $hist_stmt = $pdo->query("SELECT * FROM upload_history ORDER BY upload_date DESC");
    $history = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $error = "Could not fetch upload history: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bulk Upload Players - PPL Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/bootstrap.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800;900&family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="assets/pretium-glass-theme.css">
    <style>
        :root {
            --primary-navy: #0f2a4a;
            --accent-teal: #0284c7;
            --header-teal: #28779b;
            --teal-gradient: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
            --card-border: rgba(2, 132, 199, 0.18);
        }

        body {
            background-color: #f1f5f9;
            background-image: url('assets/ppl-cricket-bg.png');
            background-position: center top;
            background-repeat: no-repeat;
            background-attachment: fixed;
            background-size: cover;
            color: #0f2a4a;
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
        }

        .navbar {
            background: rgba(255, 255, 255, 0.94) !important;
            border-bottom: 1px solid var(--card-border) !important;
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            box-shadow: 0 4px 24px rgba(15, 42, 74, 0.08);
        }

        .navbar-brand {
            font-family: 'Outfit', sans-serif;
            color: var(--primary-navy) !important;
            font-weight: 800;
            letter-spacing: 0.5px;
        }

        .dashboard-container {
            padding: 32px 24px;
        }

        .sidebar {
            background: rgba(255, 255, 255, 0.92);
            border-right: 1px solid var(--card-border);
            min-height: calc(100vh - 76px);
            padding: 24px 16px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        .sidebar .nav-link {
            color: #475569;
            font-weight: 600;
            font-size: 0.95rem;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 8px;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .sidebar .nav-link i {
            color: var(--accent-teal);
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar .nav-link:hover {
            background: rgba(2, 132, 199, 0.08);
            color: var(--accent-teal) !important;
            transform: translateX(4px);
        }

        .sidebar .nav-link.active {
            background: linear-gradient(135deg, #0f2a4a 0%, #0284c7 100%) !important;
            color: #ffffff !important;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
        }

        .sidebar .nav-link.active i {
            color: #ffffff !important;
        }

        .stat-card {
            background: #ffffff !important;
            border: 1px solid var(--card-border) !important;
            border-radius: 20px !important;
            box-shadow: 0 10px 30px rgba(15, 42, 74, 0.07) !important;
            padding: 28px;
            margin-bottom: 30px;
            transition: all 0.3s ease;
        }

        .stat-title {
            font-family: 'Outfit', sans-serif;
            color: var(--primary-navy);
            font-weight: 800;
            font-size: 1.15rem;
            letter-spacing: -0.2px;
            margin-bottom: 20px;
            border-bottom: 2px solid rgba(2, 132, 199, 0.12);
            padding-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .table-custom-wrapper {
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(15, 42, 74, 0.06);
            border: 1px solid var(--card-border);
        }

        .table-custom {
            margin-bottom: 0;
            width: 100%;
        }

        .table-custom thead th {
            background: var(--header-teal) !important;
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.82rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            padding: 14px 16px;
            border: none !important;
            vertical-align: middle;
        }

        .table-custom tbody td {
            color: #0f2a4a !important;
            background: #ffffff;
            border-bottom: 1px solid rgba(2, 132, 199, 0.08);
            padding: 14px 16px;
            vertical-align: middle;
            font-size: 0.92rem;
        }

        .table-custom tbody tr:nth-of-type(even) td {
            background: #f8fafc;
        }

        .table-custom tbody tr:hover td {
            background: rgba(2, 132, 199, 0.05);
        }

        .btn-gold {
            background: var(--teal-gradient) !important;
            color: #ffffff !important;
            border: none !important;
            font-weight: 700;
            border-radius: 50px;
            padding: 10px 28px;
            box-shadow: 0 4px 14px rgba(2, 132, 199, 0.25);
            transition: all 0.3s ease;
        }

        .btn-gold:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(2, 132, 199, 0.35);
            color: #ffffff !important;
        }

        .btn-outline-gold {
            background: #ffffff !important;
            color: var(--accent-teal) !important;
            border: 1.5px solid var(--accent-teal) !important;
            border-radius: 50px;
            font-weight: 600;
            padding: 8px 22px;
            transition: all 0.3s ease;
        }

        .btn-outline-gold:hover {
            background: rgba(2, 132, 199, 0.08) !important;
            color: #0369a1 !important;
            transform: translateY(-1px);
        }

        .form-control, .form-control:focus {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #0f2a4a !important;
            border-radius: 12px;
            padding: 10px 16px;
            font-size: 0.92rem;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--accent-teal);
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
        }

        .form-control::file-selector-button {
            color: #ffffff;
            background: var(--teal-gradient);
            border: none;
            padding: 8px 18px;
            margin-right: 15px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.25s ease;
            cursor: pointer;
        }

        .form-control::file-selector-button:hover {
            filter: brightness(1.1);
        }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            .sidebar {
                min-height: auto;
                border-right: none;
                border-bottom: 1px solid var(--card-border);
            }
            .dashboard-container {
                padding: 20px 14px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container-fluid px-3 px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-3 text-decoration-none" href="admin_dashboard.php">
                <img src="assets/PPL-Season-2.png" alt="PPL Logo" style="height:44px; filter:drop-shadow(0 2px 8px rgba(2, 132, 199, 0.25));" onerror="this.onerror=null;this.src='../assets/ppl-logo-transparent.png';">
                <div class="d-flex flex-column lh-1">
                    <span style="font-family: 'Outfit', sans-serif; font-weight: 800; font-size: 1.15rem; letter-spacing: 0.5px; color: #0f2a4a;">PRETIUM PREMIER LEAGUE</span>
                    <span style="font-size: 0.72rem; letter-spacing: 1.5px; color: #0284c7; font-weight: 700; margin-top: 2px;">ADMIN CONTROL PORTAL</span>
                </div>
            </a>
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminSidebar">
                <i class="fas fa-bars fs-4" style="color: #0284c7;"></i>
            </button>
            <div class="collapse navbar-collapse" id="adminNavbarContent">
                <div class="ms-auto d-flex align-items-center gap-3 mt-2 mt-lg-0">
                    <div class="d-none d-sm-flex align-items-center px-3 py-1 rounded-pill" style="background: rgba(2, 132, 199, 0.08); border: 1px solid rgba(2, 132, 199, 0.2);">
                        <img src="../assets/pretium-company-logo.png" alt="Company Logo" style="height: 22px; width: 22px; object-fit: contain;" onerror="this.style.display='none';">
                        <span class="ms-2 fw-semibold" style="font-size: 0.8rem; color: #0f2a4a;">PPL Season 2</span>
                    </div>
                    <span class="text-secondary small">Welcome, <strong style="color: #0f2a4a;"><?php echo htmlspecialchars($_SESSION['admin_username'] ?? ''); ?></strong></span>
                    <a href="admin_logout.php" class="btn btn-outline-danger btn-sm px-3 rounded-pill">
                        <i class="fas fa-arrow-right-from-bracket me-1"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-lg-2 col-md-3 collapse d-md-block sidebar" id="adminSidebar">
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="admin_players.php">
                            <i class="fas fa-users"></i> All Players
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="admin_uploads.php">
                            <i class="fas fa-file-upload"></i> Uploads
                        </a>
                    </li>
                </ul>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10 col-md-9 dashboard-container">
                <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                    <div>
                        <h2 class="font-heading m-0" style="font-family: 'Outfit', sans-serif; font-weight: 800; color: #0f2a4a; letter-spacing: -0.5px;">BULK UPLOAD <span style="color: #0284c7;">(REGISTRATION &amp; STATS)</span></h2>
                        <p class="text-muted mb-0 small mt-1">Import player rosters, stats, and registration records seamlessly</p>
                    </div>
                    <a href="template/unified_upload_template.csv" class="btn btn-outline-gold d-inline-flex align-items-center gap-2" download>
                        <i class="fas fa-download"></i> Download Unified Template
                    </a>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" id="successAlert" style="background: #f0fdf4; color: #166534; border-left: 4px solid #16a34a !important;">
                        <i class="fas fa-check-circle fs-5 text-success"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($message); ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm rounded-4 d-flex align-items-center gap-2" role="alert" style="background: #fef2f2; color: #991b1b; border-left: 4px solid #dc2626 !important;">
                        <i class="fas fa-exclamation-circle fs-5 text-danger"></i>
                        <span class="fw-semibold"><?php echo htmlspecialchars($error); ?></span>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_log)): ?>
                <div class="stat-card mb-4" style="border-color: rgba(220,53,69,0.3) !important;">
                    <div class="stat-title text-danger mb-2">
                        <i class="fas fa-exclamation-triangle"></i> Upload Error Log (Skipped Rows)
                    </div>
                    <p class="small text-muted mb-3">The following rows failed validation or encountered database errors and were skipped. All valid rows in the CSV were successfully imported.</p>
                    <div class="table-custom-wrapper" style="max-height: 300px; overflow-y: auto;">
                        <table class="table table-custom table-sm">
                            <thead>
                                <tr>
                                    <th style="width: 110px;">Row #</th>
                                    <th>Error Message</th>
                                    <th>Failed Field(s)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($error_log as $err): ?>
                                <tr>
                                    <td><span class="badge rounded-pill bg-danger">Row <?php echo intval($err['row']); ?></span></td>
                                    <td class="fw-semibold text-danger"><?php echo htmlspecialchars($err['message']); ?></td>
                                    <td style="color: #b45309; font-weight: 600;"><?php echo htmlspecialchars($err['fields']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-12">
                        <div class="stat-card">
                            <div class="stat-title">
                                <i class="fas fa-cloud-upload-alt text-primary"></i> Upload Data (Registration &amp; Statistics)
                            </div>
                            
                            <form action="" method="POST" enctype="multipart/form-data" id="uploadForm">
                                <input type="hidden" name="upload_submit" value="1">
                                <div class="row align-items-end">
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label" style="color: #475569; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px;">Upload Unified CSV File</label>
                                        <input type="file" name="upload_file" class="form-control" accept=".csv, .xlsx, .xls">
                                        <small class="text-muted d-block mt-1">* CSV containing Registration fields and/or Player Stats fields.</small>
                                    </div>
                                    <div class="col-md-2 mb-3 text-center">
                                        <span class="badge rounded-pill" style="background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 6px 14px; font-size: 0.85rem;">OR</span>
                                    </div>
                                    <div class="col-md-5 mb-3">
                                        <label class="form-label" style="color: #475569; font-weight: 700; font-size: 0.82rem; text-transform: uppercase; letter-spacing: 0.5px;">Google Sheets Link</label>
                                        <input type="url" name="google_sheets_link" class="form-control" placeholder="Paste the View/Edit link or Publish to Web link">
                                    </div>
                                    
                                    <div class="col-12 mt-2 d-none" id="uploadProgressDiv">
                                        <div class="alert alert-info py-3 px-4 d-flex align-items-center mb-0 rounded-4 border-0 shadow-sm" style="background: #e0f2fe; color: #0369a1;">
                                            <div class="spinner-border spinner-border-sm me-3" role="status"></div>
                                            <span class="fw-semibold">Processing CSV upload... Extracting Registration and Player Stats data...</span>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-3 text-end">
                                        <button type="submit" name="upload_submit" id="uploadSubmitBtn" class="btn btn-gold px-5">
                                            <i class="fas fa-cloud-upload-alt me-2"></i> Process Import
                                        </button>
                                    </div>
                                </div>
                            </form>
                            
                        </div>
                    </div>
                </div>

                <!-- Upload History -->
                <div class="stat-card">
                    <div class="stat-title">
                        <i class="fas fa-history text-primary"></i> Upload History
                    </div>
                    <div class="table-custom-wrapper">
                        <div class="table-responsive">
                            <table class="table table-custom">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>File Name</th>
                                        <th>Uploaded By</th>
                                        <th>Total Rows</th>
                                        <th>Reg. Success</th>
                                        <th>Stats Success</th>
                                        <th>Failed/Skipped</th>
                                        <th class="text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($history)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-4">No upload history found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($history as $row): ?>
                                            <?php 
                                                $reg_cnt = isset($row['successful_registrations']) && $row['successful_registrations'] !== null ? $row['successful_registrations'] : $row['successful_records'];
                                                $stats_cnt = isset($row['successful_stats']) && $row['successful_stats'] !== null ? $row['successful_stats'] : $row['successful_records'];
                                                $has_err_log = !empty($row['error_log']);
                                            ?>
                                            <tr>
                                                <td><span class="fw-bold" style="color: #0f2a4a;"><?php echo date('d M Y, H:i', strtotime($row['upload_date'])); ?></span></td>
                                                <td><span class="fw-semibold text-primary"><?php echo htmlspecialchars($row['file_name']); ?></span></td>
                                                <td><span class="badge rounded-pill" style="background: #f1f5f9; color: #475569; font-weight: 600; padding: 5px 12px;"><?php echo htmlspecialchars($row['uploaded_by']); ?></span></td>
                                                <td><span class="fw-bold" style="color: #0f2a4a;"><?php echo $row['total_records']; ?></span></td>
                                                <td><span class="badge rounded-pill" style="background: #f0fdf4; color: #16a34a; font-weight: 700; padding: 5px 12px; border: 1px solid rgba(22, 163, 74, 0.2);"><?php echo $reg_cnt; ?></span></td>
                                                <td><span class="badge rounded-pill" style="background: #e0f2fe; color: #0284c7; font-weight: 700; padding: 5px 12px; border: 1px solid rgba(2, 132, 199, 0.2);"><?php echo $stats_cnt; ?></span></td>
                                                <td><span class="badge rounded-pill" style="background: #fef2f2; color: #dc2626; font-weight: 700; padding: 5px 12px; border: 1px solid rgba(220, 38, 38, 0.2);"><?php echo $row['failed_records']; ?></span></td>
                                                <td class="text-center">
                                                    <?php if ($has_err_log): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill me-1" title="View Error Log" data-bs-toggle="modal" data-bs-target="#errorModal<?php echo $row['id']; ?>" style="border-width: 1.5px;">
                                                            <i class="fas fa-exclamation-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <form method="POST" style="display:inline;" id="deleteForm<?php echo $row['id']; ?>">
                                                        <input type="hidden" name="delete_history_id" value="<?php echo $row['id']; ?>">
                                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" title="Delete Record" onclick="showDeleteModal(<?php echo $row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['file_name']), ENT_QUOTES); ?>')" style="border-width: 1.5px;">
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
    <?php foreach ($history as $row): ?>
        <?php if (!empty($row['error_log'])): ?>
            <?php $saved_errors = json_decode($row['error_log'], true); ?>
            <?php if (is_array($saved_errors)): ?>
            <div class="modal fade" id="errorModal<?php echo $row['id']; ?>" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                    <div class="modal-content" style="background: #ffffff; border: 1px solid var(--card-border); border-radius: 20px; box-shadow: 0 20px 60px rgba(15, 42, 74, 0.2); color: #0f2a4a; overflow: hidden;">
                        <div class="modal-header border-0 pb-0" style="background: #f8fafc; padding: 22px 28px;">
                            <h5 class="modal-title text-danger fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Error Log - <?php echo htmlspecialchars($row['file_name']); ?></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body" style="padding: 24px 28px; max-height: 400px; overflow-y: auto;">
                            <div class="table-custom-wrapper">
                                <table class="table table-custom table-sm">
                                    <thead>
                                        <tr>
                                            <th style="width: 110px;">Row #</th>
                                            <th>Error Message</th>
                                            <th>Failed Field(s)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($saved_errors as $err): ?>
                                        <tr>
                                            <td><span class="badge rounded-pill bg-danger">Row <?php echo intval($err['row'] ?? 0); ?></span></td>
                                            <td class="text-danger fw-semibold"><?php echo htmlspecialchars($err['message'] ?? ''); ?></td>
                                            <td style="color: #b45309; font-weight: 600;"><?php echo htmlspecialchars($err['fields'] ?? ''); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="modal-footer border-0" style="background: #f8fafc; padding: 16px 28px;">
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content" style="background: #ffffff; border: 1px solid rgba(220, 53, 69, 0.3); border-radius: 20px; box-shadow: 0 25px 60px rgba(15, 42, 74, 0.2); color: #0f2a4a; overflow: hidden;">
                <div style="position: absolute; top: 0; left: 0; right: 0; height: 4px; background: linear-gradient(90deg, #dc3545, #ff6b6b, #dc3545);"></div>
                <div class="modal-header border-0 pb-0" style="padding: 24px 24px 12px;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width: 48px; height: 48px; background: #fef2f2; border-radius: 12px; display: flex; align-items: center; justify-content: center; border: 1px solid rgba(220, 53, 69, 0.2);">
                            <i class="fas fa-trash-alt" style="font-size: 1.2rem; color: #dc2626;"></i>
                        </div>
                        <h5 class="modal-title fw-bold mb-0" style="font-size: 1.15rem; color: #0f2a4a;">Delete Record</h5>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="padding: 20px 24px 16px;">
                    <p style="color: #475569; font-size: 0.95rem; margin-bottom: 16px;">Are you sure you want to permanently delete this upload history record?</p>
                    <div style="background: #f8fafc; border: 1px solid rgba(2, 132, 199, 0.15); border-radius: 12px; padding: 14px 16px;">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-file-csv" style="color: #0284c7; font-size: 1.2rem;"></i>
                            <span id="deleteFileName" class="fw-bold" style="color: #0f2a4a; font-size: 0.92rem;"></span>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3" style="color: #dc2626; font-size: 0.85rem;">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span>This action cannot be undone.</span>
                    </div>
                </div>
                <div class="modal-footer border-0" style="padding: 8px 24px 24px; gap: 10px;">
                    <button type="button" class="btn px-4 rounded-pill" data-bs-dismiss="modal" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 600;">Cancel</button>
                    <button type="button" class="btn px-4 rounded-pill" id="confirmDeleteBtn" style="background: linear-gradient(135deg, #dc3545, #b91c1c); color: #fff; border: none; font-weight: 600; box-shadow: 0 4px 14px rgba(220, 53, 69, 0.3);">
                        <i class="fas fa-trash-alt me-2"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/bootstrap.bundle.min.js"></script>
    <script>
    let deleteFormId = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteConfirmModal'));

    function showDeleteModal(id, fileName) {
        deleteFormId = id;
        document.getElementById('deleteFileName').textContent = fileName;
        deleteModal.show();
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (deleteFormId) {
            document.getElementById('deleteForm' + deleteFormId).submit();
        }
    });

    document.addEventListener('DOMContentLoaded', function() {
        const uploadForm = document.getElementById('uploadForm');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function() {
                const btn = document.getElementById('uploadSubmitBtn');
                if (btn) {
                    setTimeout(function() {
                        btn.disabled = true;
                        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Processing Import...';
                    }, 10);
                }
                const progressDiv = document.getElementById('uploadProgressDiv');
                if (progressDiv) {
                    progressDiv.classList.remove('d-none');
                }
            });
        }

        const successAlert = document.getElementById('successAlert');
        if (successAlert) {
            setTimeout(function() {
                successAlert.style.transition = 'opacity 0.5s ease';
                successAlert.style.opacity = '0';
                setTimeout(function() {
                    successAlert.style.display = 'none';
                    successAlert.remove();
                }, 500);
            }, 4000);
        }
    });
    </script>
</body>
</html>
