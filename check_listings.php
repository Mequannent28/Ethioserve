<?php
require 'includes/config.php';
require 'includes/db.php';

try {
    $stmt = $pdo->query("DESCRIBE listings");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns in listings table:\n";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>