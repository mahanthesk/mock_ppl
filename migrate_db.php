<?php
// migrate_db.php
$host = '127.0.0.1';
$username = 'root';
$password = '';
$main_db = 'parvamco_spl_season2_2026';
$dash_db = 'parvamco_spl_season2_2026_dashboard';

try {
    $pdo = new PDO("mysql:host=$host;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "1. Adding columns to registrations...\n";
    $pdo->exec("USE `$main_db`");
    // Add columns if they don't exist. Ignore errors if they do.
    try {
        $pdo->exec("ALTER TABLE registrations 
                    ADD COLUMN team_id INT NULL,
                    ADD COLUMN bid_points INT NULL,
                    ADD COLUMN booking_status INT DEFAULT 0");
    } catch (PDOException $e) {
        echo "Columns might already exist. " . $e->getMessage() . "\n";
    }

    echo "2. Copying team_master...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$main_db`.team_master LIKE `$dash_db`.team_master");
    $pdo->exec("TRUNCATE TABLE `$main_db`.team_master");
    $pdo->exec("INSERT INTO `$main_db`.team_master SELECT * FROM `$dash_db`.team_master");

    echo "3. Copying icon_players...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$main_db`.icon_players LIKE `$dash_db`.icon_players");
    $pdo->exec("TRUNCATE TABLE `$main_db`.icon_players");
    $pdo->exec("INSERT INTO `$main_db`.icon_players SELECT * FROM `$dash_db`.icon_players");

    echo "4. Copying team_owners...\n";
    $pdo->exec("CREATE TABLE IF NOT EXISTS `$main_db`.team_owners LIKE `$dash_db`.team_owners");
    $pdo->exec("TRUNCATE TABLE `$main_db`.team_owners");
    $pdo->exec("INSERT INTO `$main_db`.team_owners SELECT * FROM `$dash_db`.team_owners");

    echo "5. Migrating auction data...\n";
    // We update the main registrations table based on the dashboard's registrations table where booking_status > 0 or bid_points > 0
    $stmt = $pdo->query("SELECT player_id, team_id, bid_points, booking_status FROM `$dash_db`.registrations WHERE booking_status > 0 OR team_id IS NOT NULL");
    $auctioned_players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $updateStmt = $pdo->prepare("UPDATE `$main_db`.registrations SET team_id = ?, bid_points = ?, booking_status = ? WHERE player_id = ?");
    $count = 0;
    foreach ($auctioned_players as $player) {
        $updateStmt->execute([$player['team_id'], $player['bid_points'], $player['booking_status'], $player['player_id']]);
        $count++;
    }
    echo "   Migrated $count player records.\n";

    echo "6. Copying auction_admin credentials...\n";
    // Get auction admins from dashboard DB
    $adminStmt = $pdo->query("SELECT username, password FROM `$dash_db`.admins");
    $admins = $adminStmt->fetchAll(PDO::FETCH_ASSOC);

    $insertAdmin = $pdo->prepare("INSERT IGNORE INTO `$main_db`.admins (username, password) VALUES (?, ?)");
    foreach ($admins as $admin) {
        // Assuming unique usernames, INSERT IGNORE prevents duplicating if it exists
        // Wait, 'username' might not be UNIQUE in the schema. Let's check if it exists first.
        $check = $pdo->prepare("SELECT id FROM `$main_db`.admins WHERE username = ?");
        $check->execute([$admin['username']]);
        if (!$check->fetch()) {
            $insertAdmin->execute([$admin['username'], $admin['password']]);
        }
    }

    echo "Migration Complete!\n";

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
