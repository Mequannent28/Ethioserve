<?php
require_once 'includes/db.php';

$students_to_fix = [92, 93]; // student1_school and student2_school
$class_id = 1;

foreach ($students_to_fix as $sid) {
    // Check if profile exists
    $stmt = $pdo->prepare("SELECT id FROM sms_student_profiles WHERE user_id = ?");
    $stmt->execute([$sid]);
    $exists = $stmt->fetch();

    if (!$exists) {
        $id_number = 'STU-' . str_pad($sid, 4, '0', STR_PAD_LEFT);
        $stmt = $pdo->prepare("INSERT INTO sms_student_profiles (user_id, class_id, student_id_number, admission_date) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$sid, $class_id, $id_number]);
        echo "Created profile for user ID $sid\n";
    } else {
        // Update class just in case
        $stmt = $pdo->prepare("UPDATE sms_student_profiles SET class_id = ? WHERE user_id = ?");
        $stmt->execute([$class_id, $sid]);
        echo "Updated profile for user ID $sid\n";
    }
}
?>
