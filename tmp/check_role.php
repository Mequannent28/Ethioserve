<?php
require_once 'c:/xampp/htdocs/Ethioserve-main/includes/db.php';
$stmt = $pdo->prepare('SELECT id, username, role FROM users WHERE username = ?');
$stmt->execute(['school_admin1']);
print_r($stmt->fetch());
