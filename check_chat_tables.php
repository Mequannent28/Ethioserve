<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SHOW TABLES");
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);

if (in_array('messages', $tables)) {
    echo "\nTable 'messages' exists. Columns:\n";
    $stmt = $pdo->query("DESCRIBE messages");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
if (in_array('dating_chats', $tables)) {
    echo "\nTable 'dating_chats' exists. Columns:\n";
    $stmt = $pdo->query("DESCRIBE dating_chats");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
