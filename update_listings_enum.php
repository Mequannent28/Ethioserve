<?php
require 'includes/config.php';
require 'includes/db.php';

try {
    // Add 'product' and 'exchange' to the type enum
    $sql = "ALTER TABLE listings COMPACT; ALTER TABLE listings MODIFY COLUMN type ENUM('house_rent','car_rent','bus_ticket','home_service','product','exchange') NOT NULL DEFAULT 'product'";
    // Note: older MySQL versions might need separate ALTER TABLE commands or specific syntax.
    // Let's try a safer update.
    $sql = "ALTER TABLE listings MODIFY COLUMN type ENUM('house_rent','car_rent','bus_ticket','home_service','product','exchange') NOT NULL";

    $pdo->exec($sql);
    echo "Successfully updated listings table type enum.\n";
} catch (PDOException $e) {
    echo "Error updating table: " . $e->getMessage();
}
?>