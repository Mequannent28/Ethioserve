<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/config.php';

echo "<h2>Database Debug</h2>";
echo "ENVIRONMENT: " . ENVIRONMENT . "<br>";
echo "DB_HOST: " . DB_HOST . "<br>";
echo "DB_NAME: " . DB_NAME . "<br>";
echo "DB_USER: " . DB_USER . "<br>";
echo "IP Access: " . $_SERVER['SERVER_ADDR'] . "<br>";

$host = DB_HOST;
$db = DB_NAME;
$user = DB_USER;
$pass = DB_PASS;
$port = DB_PORT;

echo "<h3>Testing Connections...</h3>";

// Test 1: Standard Host
try {
    $dsn1 = "mysql:host=$host;port=$port;dbname=$db";
    echo "Testing DSN: $dsn1 ... ";
    $p1 = new PDO($dsn1, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
    echo "<span style='color:green'>SUCCESS (TCP)</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}

// Test 2: Localhost (socket trigger)
try {
    $dsn2 = "mysql:host=localhost;dbname=$db";
    echo "Testing DSN: $dsn2 ... ";
    $p2 = new PDO($dsn2, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
    echo "<span style='color:green'>SUCCESS (Localhost Socket)</span><br>";
} catch (Exception $e) {
    echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
}

// Test 3: System Sockets
$sockets = [
    '/var/run/mysqld/mysqld.sock',
    '/tmp/mysql.sock',
    '/var/lib/mysql/mysql.sock'
];

foreach ($sockets as $sock) {
    if (file_exists($sock)) {
        try {
            $dsnS = "mysql:unix_socket=$sock;dbname=$db";
            echo "Testing Socket: $sock ... ";
            $pS = new PDO($dsnS, $user, $pass, [PDO::ATTR_TIMEOUT => 2]);
            echo "<span style='color:green'>SUCCESS</span><br>";
        } catch (Exception $e) {
            echo "<span style='color:red'>FAILED: " . $e->getMessage() . "</span><br>";
        }
    } else {
        echo "Socket not found: $sock<br>";
    }
}
?>