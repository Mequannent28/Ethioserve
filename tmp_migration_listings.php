<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS rented_duration INT DEFAULT NULL AFTER status");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS rented_at TIMESTAMP NULL DEFAULT NULL AFTER rented_duration");
    echo "Added rented_duration and rented_at to listings";
}
catch (Exception $e) {

    echo "Error: " . $e->getMessage();
}
