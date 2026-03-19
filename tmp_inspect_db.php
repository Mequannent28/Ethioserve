<?php
require 'includes/db.php';
$tables = ['real_estate_properties', 'listings', 'users'];
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
$stmt = $pdo->query("SELECT DISTINCT role FROM users");
echo "\n--- User Roles ---\n";
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['role'] . "\n";
}
