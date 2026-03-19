<?php
require 'includes/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM sms_student_profiles");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
