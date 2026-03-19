<?php
require_once '../includes/db.php';
$stmt = $pdo->query("SELECT DISTINCT role FROM users");
echo json_encode($stmt->fetchAll(PDO::FETCH_COLUMN), JSON_PRETTY_PRINT);
