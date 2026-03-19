<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE listings");
foreach ($stmt->fetchAll() as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
