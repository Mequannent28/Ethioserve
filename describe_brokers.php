<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE brokers");
print_r($stmt->fetchAll());
