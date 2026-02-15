<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $output = "Tables in database:\n";
    foreach ($tables as $table) {
        $output .= "- $table\n";
        $columnsStmt = $pdo->query("DESCRIBE $table");
        $columns = $columnsStmt->fetchAll();
        foreach ($columns as $column) {
            $output .= "  * {$column['Field']} ({$column['Type']})\n";
        }
    }
    file_put_contents('db_schema_fixed.txt', $output);
    echo "Done.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
