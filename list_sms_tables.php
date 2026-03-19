<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SHOW TABLES LIKE 'sms%'");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
