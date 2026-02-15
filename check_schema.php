<?php
$pdo = new PDO('mysql:host=localhost;dbname=ethioserve', 'root', '');
$stmt = $pdo->query('DESCRIBE hotels');
$fields = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $fields);
?>