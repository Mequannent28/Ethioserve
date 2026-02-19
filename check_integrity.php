<?php
require_once 'includes/db.php';

function checkTable($pdo, $tableName)
{
    echo "Checking table: $tableName -> ";
    try {
        $pdo->query("SELECT 1 FROM $tableName LIMIT 1");
        echo "✅ EXISTS\n";
    } catch (Exception $e) {
        echo "❌ MISSING (" . $e->getMessage() . ")\n";
    }
}

$tables = ['users', 'job_companies', 'job_listings', 'job_categories', 'health_providers', 'doctor_messages'];
foreach ($tables as $t) {
    checkTable($pdo, $t);
}

echo "\nChecking provider for dr_dawit:\n";
try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = 'dr_dawit'");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        echo "User dr_dawit ID: " . $user['id'] . "\n";
        $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $provider = $stmt->fetch();
        if ($provider) {
            echo "✅ health_providers entry found! ID: " . $provider['id'] . "\n";
        } else {
            echo "❌ NO health_providers entry for dr_dawit.\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking dr_dawit: " . $e->getMessage() . "\n";
}

echo "\nChecking company for cloud_company:\n";
try {
    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE username = 'cloud_company'");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        echo "User cloud_company ID: " . $user['id'] . "\n";
        $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        $company = $stmt->fetch();
        if ($company) {
            echo "✅ job_companies entry found! ID: " . $company['id'] . "\n";
        } else {
            echo "❌ NO job_companies entry for cloud_company.\n";
        }
    }
} catch (Exception $e) {
    echo "Error checking cloud_company: " . $e->getMessage() . "\n";
}
