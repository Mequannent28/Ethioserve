<?php
require_once 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE recycle_bin ADD COLUMN reason VARCHAR(100) DEFAULT NULL AFTER data_json");
    echo "Column 'reason' added.";
}
catch (Exception $e) {
    echo "Note: " . $e->getMessage();
}
