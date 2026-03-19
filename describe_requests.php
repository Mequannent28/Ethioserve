<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE rental_requests");
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
