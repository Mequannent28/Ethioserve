<?php
require_once 'includes/db.php';

// Check users table for phone column
$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'phone'")->fetch();
if (!$columns) {
    $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) AFTER email");
    echo "Added 'phone' column to users table\n";
} else {
    echo "users.phone already exists\n";
}

// Check if users table has email column
$columns = $pdo->query("SHOW COLUMNS FROM users LIKE 'email'")->fetch();
if (!$columns) {
    $pdo->exec("ALTER TABLE users ADD COLUMN email VARCHAR(100) AFTER full_name");
    echo "Added 'email' column to users table\n";
} else {
    echo "users.email already exists\n";
}

echo "\nDone! Users table columns:\n";
$cols = $pdo->query("SHOW COLUMNS FROM users");
while ($col = $cols->fetch()) {
    echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
}
?>