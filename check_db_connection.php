<?php
require_once 'includes/config.php';

echo "Checking database connection...\n";
echo "Type: " . DB_TYPE . "\n";
echo "Host: " . DB_HOST . "\n";
echo "User: " . DB_USER . "\n";

try {
    if (ENVIRONMENT === 'production' && (DB_HOST === 'localhost' || DB_HOST === '127.0.0.1') && file_exists('/var/run/mysqld/mysqld.sock')) {
        $dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=" . DB_NAME;
    } else {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS);
    echo "✅ SUCCESS! Connected to " . DB_TYPE . " database.\n";
} catch (PDOException $e) {
    echo "❌ ERROR: Could not connect to database.\n";
    echo "Message: " . $e->getMessage() . "\n";
    echo "\nPlease ensure:\n";
    echo "1. MySQL is running in XAMPP.\n";
}
?>