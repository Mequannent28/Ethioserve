<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("SELECT id, name FROM health_providers");
    echo "Health Providers:\n";
    foreach ($stmt->fetchAll() as $row) {
        echo "{$row['id']}: {$row['name']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
