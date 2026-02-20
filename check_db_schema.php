<?php
require_once 'includes/db.php';

$stmt = $pdo->query("SELECT id, degree_type, certification FROM home_service_providers WHERE id = 1");
$provider = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($provider);
