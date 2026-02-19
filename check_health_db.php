<?php
require_once 'includes/db.php';

echo "Table: health_providers\n";
$stmt = $pdo->query("DESCRIBE health_providers");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

echo "\nTable: health_specialties\n";
$stmt = $pdo->query("SELECT id, name FROM health_specialties");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
