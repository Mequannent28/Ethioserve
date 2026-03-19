<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT image_url FROM hotel_rooms LIMIT 5");
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rooms);
