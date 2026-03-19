<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE listings");
print_r($stmt->fetchAll());
