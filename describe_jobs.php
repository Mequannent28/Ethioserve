<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE job_listings");
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
