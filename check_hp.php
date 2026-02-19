<?php
require_once 'includes/db.php';
try {
    $stmt = $pdo->query("DESC health_providers");
    echo "Columns in health_providers:\n";
    foreach ($stmt->fetchAll() as $row) {
        echo "- " . $row['Field'] . " (" . $row['Type'] . ")\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
