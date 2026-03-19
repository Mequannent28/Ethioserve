<?php
require_once 'includes/db.php';
$stmt = $pdo->query("DESCRIBE sms_student_profiles");
$cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
file_put_contents('schema_profiles.txt', print_r($cols, true));
?>
