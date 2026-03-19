<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE referrals");
print_r($stmt->fetchAll());
