<?php
require_once __DIR__ . '/config.php';

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$port = DB_PORT;
$charset = DB_CHARSET;

$options = [
     PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
     PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
     PDO::ATTR_EMULATE_PREPARES => false,
     PDO::ATTR_TIMEOUT => 5,
];

// Connection algorithm: Try diverse methods until one works
$success = false;
$pdo = null;

// List of DSNs to try
$dsns = [];

if (ENVIRONMENT === 'production') {
     // 1. Try Unix Socket (Best for self-contained Docker)
     $sockets = ['/var/run/mysqld/mysqld.sock', '/tmp/mysql.sock', '/var/lib/mysql/mysql.sock'];
     foreach ($sockets as $sock) {
          if (file_exists($sock)) {
               $dsns[] = "mysql:unix_socket=$sock;dbname=$db;charset=$charset";
          }
     }

     // 2. Try Localhost (triggers internal socket in many PHP versions)
     $dsns[] = "mysql:host=localhost;dbname=$db;charset=$charset";
}

// 3. Fallback to configured host
$dsns[] = "mysql:host=$host;port=$port;dbname=$db;charset=$charset";

// Attempt connections
$last_error = "";
foreach ($dsns as $dsn) {
     try {
          $pdo = new PDO($dsn, $user, $pass, $options);
          $success = true;
          break;
     } catch (PDOException $e) {
          $last_error = $e->getMessage();
          continue;
     }
}

if (!$success) {
     if (ENVIRONMENT === 'production') {
          die("<h3>System Maintenance</h3>The database connection is temporarily unavailable. Error: " . htmlspecialchars($last_error));
     } else {
          die("<h3>Database Error</h3>Please ensure XAMPP MySQL is running and the database '$db' exists.<br>Error: " . htmlspecialchars($last_error));
     }
}
?>