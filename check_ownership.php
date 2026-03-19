<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, user_id, title, status FROM listings WHERE title LIKE '%Toyota Land Cruiser%'");
print_r($stmt->fetchAll());
