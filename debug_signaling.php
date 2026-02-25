<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE app_signaling");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($cols, JSON_PRETTY_PRINT);
