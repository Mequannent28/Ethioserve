<?php
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$port = DB_PORT;
$charset = DB_CHARSET;

// DSN Selection Logic
if (ENVIRONMENT === 'production' && ($host === 'localhost' || $host === '127.0.0.1')) {
     // Use unix socket for local MariaDB in self-contained Docker on Render
     if (file_exists('/var/run/mysqld/mysqld.sock')) {
          $dsn = "mysql:unix_socket=/var/run/mysqld/mysqld.sock;dbname=$db;charset=$charset";
     } else {
          // Fallback to TCP if socket is missing
          $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
     }
} else {
     // Standard TCP connection for Local XAMPP or Remote Databases (like Managed Render DB)
     $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";
}

$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     if (ENVIRONMENT === 'production') {
          // TEMPORARY DEBUGGING: Show actual error
          die("Database Error: " . $e->getMessage());
     } else {
          die("Database connection failed. Please ensure the database '$db' exists and is configured correctly in includes/config.php.");
     }
}
?>