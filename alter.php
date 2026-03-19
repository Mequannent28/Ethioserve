<?php
require_once 'includes/db.php';
$pdo->exec("ALTER TABLE listings MODIFY COLUMN status ENUM('available', 'taken', 'pending', 'rented', 'not_available', 'on_process') DEFAULT 'available'");
echo "Table altered.\n";
// Clean up existing dirty data now it works
$pdo->exec("UPDATE listings SET status = 'available' WHERE status = '' OR status IS NULL");
echo "Data cleaned.\n";
$stmt = $pdo->query("DESCRIBE listings status");
print_r($stmt->fetchAll());
$stmt = $pdo->query("SELECT DISTINCT status FROM listings");
print_r($stmt->fetchAll(PDO::FETCH_COLUMN));
