<?php
require 'includes/db.php';
$stmt = $pdo->query('DESCRIBE recycle_bin');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
