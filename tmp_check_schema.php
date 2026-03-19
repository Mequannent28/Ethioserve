<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE job_messages");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
