<?php
require_once '../auth.php';
check_access('auction_admin');

// fetch_player.php
require_once 'db_connection.php';

header('Content-Type: application/json');

if (isset($_GET['id'])) {
    $player_id = $_GET['id'];

    try {
        // Search by numeric id, player_id (e.g. PL0001) case-insensitively, or by full name
        $stmt = $pdo->prepare("SELECT pr.*, t.team_name, t.team_logo, pr.booking_status, pr.bid_points,
                                      ps.matches_played, ps.innings, ps.runs_scored, ps.highest_score,
                                      ps.batting_avg, ps.strike_rate, ps.fifties, ps.hundreds,
                                      ps.wickets, ps.bowling_avg, ps.economy, ps.best_bowling,
                                      ps.catches, ps.stumpings
                                FROM registrations pr 
                                LEFT JOIN team_master t ON pr.team_id = t.id 
                                LEFT JOIN player_stats ps ON pr.player_id = ps.player_id
                                WHERE pr.id = ? OR UPPER(pr.player_id) = UPPER(?) OR pr.full_name LIKE ?
                                LIMIT 1");
        $stmt->execute([$player_id, $player_id, '%' . $player_id . '%']);
        $player = $stmt->fetch();

        if ($player) {
            $booking_status = $player['booking_status'];
            $bidding_block_display = ($booking_status == 1) ? 'block' : 'none';

            $stats = [
                'matches_played' => intval($player['matches_played'] ?? 0),
                'innings'        => intval($player['innings'] ?? 0),
                'runs_scored'    => intval($player['runs_scored'] ?? 0),
                'highest_score'  => intval($player['highest_score'] ?? 0),
                'batting_avg'    => number_format(floatval($player['batting_avg'] ?? 0), 2),
                'strike_rate'    => number_format(floatval($player['strike_rate'] ?? 0), 2),
                'fifties'        => intval($player['fifties'] ?? 0),
                'hundreds'       => intval($player['hundreds'] ?? 0),
                'wickets'        => intval($player['wickets'] ?? 0),
                'bowling_avg'    => number_format(floatval($player['bowling_avg'] ?? 0), 2),
                'economy'        => number_format(floatval($player['economy'] ?? 0), 2),
                'best_bowling'   => !empty($player['best_bowling']) ? htmlspecialchars($player['best_bowling']) : '-',
                'catches'        => intval($player['catches'] ?? 0),
                'stumpings'      => intval($player['stumpings'] ?? 0)
            ];

            echo json_encode([
                'success' => true,
                'player_name'     => htmlspecialchars($player['full_name'] ?? ''),
                'player_id'       => htmlspecialchars($player['player_id'] ?? ''),
                'id'              => htmlspecialchars((string)($player['id'] ?? '')),
                'player_type'     => htmlspecialchars($player['role'] ?? ''),
                'batting_type'    => htmlspecialchars($player['batting_type'] ?? ''),
                'bowling_type'    => htmlspecialchars($player['bowling_type'] ?? ''),
                'player_image'    => htmlspecialchars($player['profile_pic'] ?? ''),
                'team_name'       => htmlspecialchars($player['team_name'] ?? ''),
                'team_id'         => $player['team_id'],
                'team_logo'       => $player['team_logo'],
                'bid_points'      => $player['bid_points'],
                'player_category' => htmlspecialchars($player['category'] ?? ''),
                'booking_status'  => $booking_status,
                'bidding_block_display' => $bidding_block_display,
                'department'      => htmlspecialchars($player['department'] ?? ''),
                'ppl_edition'     => htmlspecialchars($player['experience'] ?? ''),
                'stats'           => $stats
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Player not found.']);
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'No ID provided.']);
}
?>
