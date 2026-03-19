<?php
require_once 'includes/db.php';
$stmt = $pdo->prepare("SELECT u.id, u.full_name, sp.class_id FROM users u JOIN sms_student_profiles sp ON u.id = sp.user_id WHERE u.full_name LIKE '%Selam Almaz%'");
$stmt->execute();
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
