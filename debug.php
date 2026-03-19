<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT * FROM sms_classes");
print_r($stmt->fetchAll());
echo "\n====\n";
$stmt = $pdo->query("SELECT * FROM education_resources");
print_r($stmt->fetchAll());
echo "\n====\n";
$stmt = $pdo->query("SELECT id, title, grade, subject FROM education_resources");
print_r($stmt->fetchAll());
