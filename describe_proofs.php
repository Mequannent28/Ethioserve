<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE rental_payment_proofs");
print_r($stmt->fetchAll());
