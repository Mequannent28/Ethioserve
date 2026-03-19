<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN last_active DATETIME NULL");
    echo "Column added";
} catch(Exception $e) {
    echo "Error: " . $e->getMessage();
}
