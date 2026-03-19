<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE referrals ADD COLUMN request_id INT NULL AFTER order_id");
    $pdo->exec("ALTER TABLE referrals ADD INDEX (request_id)");
    echo "Referrals table updated with request_id.\n";
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
