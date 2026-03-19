<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'sms%'");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    $res = $pdo->query("SHOW CREATE TABLE $table")->fetch();
    echo $res['Create Table'] . ";\n\n";
}
