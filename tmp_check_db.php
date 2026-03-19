<?php
require_once 'includes/db.php';

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "Tables:\n";
foreach ($tables as $table) {
    if (strpos($table, 'lms_') === 0 || strpos($table, 'job_') === 0 || $table === 'users') {
        echo "- $table\n";
        $columns = $pdo->query("DESCRIBE $table")->fetchAll();
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']})\n";
        }
    }
}
