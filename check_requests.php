<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE rental_requests");
print_r($stmt->fetchAll());
