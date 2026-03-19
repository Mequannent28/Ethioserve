<?php
require_once 'includes/db.php';
$pdo->query("ALTER TABLE rental_requests ADD COLUMN customer_typing_at DATETIME NULL, ADD COLUMN owner_typing_at DATETIME NULL");
echo "Done\n";
