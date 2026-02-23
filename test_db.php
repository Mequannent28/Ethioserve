<?php
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=ethioserve", "root", "");
    echo "Connection works with 127.0.0.1\n";
} catch (Exception $e) {
    echo "Connection failed with 127.0.0.1: " . $e->getMessage() . "\n";
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=ethioserve", "root", "");
    echo "Connection works with localhost\n";
} catch (Exception $e) {
    echo "Connection failed with localhost: " . $e->getMessage() . "\n";
}
?>