<?php
require_once 'includes/db.php';

$student_usernames = ['student1', 'student1_school', 'student2_school'];
$class_id = 1;

foreach ($student_usernames as $username) {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $sid = $user['id'];
        $stmt = $pdo->prepare("SELECT id FROM sms_student_profiles WHERE user_id = ?");
        $stmt->execute([$sid]);
        $exists = $stmt->fetch();

        if (!$exists) {
            $id_number = 'STU-' . strtoupper(substr($username, 0, 3)) . '-' . str_pad($sid, 3, '0', STR_PAD_LEFT);
            $stmt = $pdo->prepare("INSERT INTO sms_student_profiles (user_id, class_id, student_id_number) VALUES (?, ?, ?)");
            $stmt->execute([$sid, $class_id, $id_number]);
            echo "Created profile for $username (ID: $sid)\n";
        } else {
            $stmt = $pdo->prepare("UPDATE sms_student_profiles SET class_id = ? WHERE user_id = ?");
            $stmt->execute([$class_id, $sid]);
            echo "Verified profile for $username (ID: $sid)\n";
        }
    } else {
        echo "User $username not found.\n";
    }
}
?>
