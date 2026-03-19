<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SHOW TABLES");
foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $table) {
    if (strpos($table, 'job') !== false) echo "$table\n";
}
