<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, username, full_name, role FROM users WHERE role = 'student' LIMIT 5");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "STUDENT USERS:\n";
print_r($students);

$stmt = $pdo->query("SELECT id, class_name, section FROM sms_classes LIMIT 5");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "\nCLASSES:\n";
print_r($classes);
?>
