<?php
require 'includes/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM education_resources");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($cols as $col)
    echo $col['Field'] . "\n";
echo "</pre>";
