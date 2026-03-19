<?php
require_once __DIR__ . '/../includes/db.php';

try {
    echo "Listing tables:\n";
    $stmt = $pdo->query('SHOW TABLES');
    while($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }

    echo "\nListing users count:\n";
    $stmt = $pdo->query('SELECT role, count(*) as count FROM users GROUP BY role');
    while($row = $stmt->fetch()) {
        echo $row['role'] . ": " . $row['count'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
