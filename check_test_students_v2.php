<?php
require_once 'includes/db.php';
$stmt = $pdo->query("SELECT id, username, full_name, role FROM users WHERE role = 'student' LIMIT 5");
$students = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT id, class_name, section FROM sms_classes LIMIT 5");
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

$output = "STUDENT USERS:\n" . print_r($students, true);
$output .= "\nCLASSES:\n" . print_r($classes, true);

file_put_contents('test_students_info.txt', $output);
?>
