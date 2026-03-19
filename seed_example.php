<?php
require_once 'includes/db.php';

$student_id = 93; // Selam Almaz
$class_id = 1;
$marked_by = 1; // Arbitrary teacher or system ID

$dates = [
    date('Y-m-d', strtotime('-1 day')),
    date('Y-m-d', strtotime('-2 days')),
    date('Y-m-d', strtotime('-5 days')),
    date('Y-m-d', strtotime('-10 days')),
    date('Y-m-d', strtotime('-15 days')),
    date('Y-m-d', strtotime('-20 days'))
];

foreach ($dates as $date) {
    $stmt = $pdo->prepare("INSERT INTO sms_attendance (student_id, class_id, status, attendance_date, marked_by) 
                           VALUES (?, ?, 'Absent', ?, ?) 
                           ON DUPLICATE KEY UPDATE status = 'Absent'");
    $stmt->execute([$student_id, $class_id, $date, $marked_by]);
}

echo "Seeded 6 absences for Selam Almaz.";
?>
