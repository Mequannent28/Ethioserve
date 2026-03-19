<?php
require 'includes/db.php';
$tables = ['rental_requests', 'real_estate_inquiries'];
foreach ($tables as $table) {
    echo "\n--- $table ---\n";
    $stmt = $pdo->query("DESCRIBE $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo $row['Field'] . " (" . $row['Type'] . ")\n";
    }
}
