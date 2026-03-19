<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE rental_payment_proofs");
foreach ($stmt as $row) {
    echo $row['Field'] . " (" . $row['Type'] . ")\n";
}
