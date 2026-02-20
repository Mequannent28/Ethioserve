<?php
require_once 'includes/db.php';
$stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE full_name LIKE '%dawit%' OR full_name LIKE '%slam%'");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
