<?php
// One-time migration: remove the requested player fields while retaining player_id.
require_once __DIR__ . '/db_connection.php';

$tables = ['registrations', 'registrations_filtered', 'registrations_filtered_bkp'];
$columns = ['role', 'batting_type', 'bowling_type', 'mobile', 'email', 'address'];

try {
    $pdo->beginTransaction();

    foreach ($tables as $table) {
        $tableExists = $pdo->prepare(
            'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ?'
        );
        $tableExists->execute([$table]);
        if (!$tableExists->fetchColumn()) {
            continue;
        }

        $columnStmt = $pdo->prepare(
            'SELECT column_name FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?'
        );

        foreach ($columns as $column) {
            $columnStmt->execute([$table, $column]);
            if ($columnStmt->fetchColumn()) {
                $quotedTable = '`' . str_replace('`', '``', $table) . '`';
                $quotedColumn = '`' . str_replace('`', '``', $column) . '`';
                $pdo->exec("ALTER TABLE {$quotedTable} DROP COLUMN {$quotedColumn}");
            }
        }
    }

    $pdo->commit();
    echo 'Requested player fields removed. player_id and all image/theme/team fields were retained.';
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    http_response_code(500);
    echo 'Migration failed: ' . $e->getMessage();
}
?>
