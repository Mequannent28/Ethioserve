<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE rental_requests ADD COLUMN duration_months INT DEFAULT 1 AFTER message");
    echo "Added duration_months to rental_requests";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
