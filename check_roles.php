<?php
require_once 'includes/db.php';

echo "USER ROLES:\n";
try {
    $stmt = $pdo->query("SELECT DISTINCT role FROM users");
    $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
    print_r($roles);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\nTABLES TO CHECK FOR PROVIDERS:\n";
$tables = ['taxi_companies', 'hotels', 'restaurants', 'transport_companies', 'job_companies', 'health_providers', 'home_service_providers', 'brokers'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        echo "- $table: " . $stmt->fetchColumn() . "\n";
    } catch (Exception $e) {
        // Table might not exist
    }
}
