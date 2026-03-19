<?php
require_once 'includes/db.php';

try {
    // 1. Update lms_exams table
    $pdo->exec("ALTER TABLE lms_exams ADD COLUMN IF NOT EXISTS class_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_exams ADD COLUMN IF NOT EXISTS teacher_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_exams ADD COLUMN IF NOT EXISTS school_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_exams ADD INDEX (class_id)");
    
    // 2. Update lms_chapters table
    $pdo->exec("ALTER TABLE lms_chapters ADD COLUMN IF NOT EXISTS class_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_chapters ADD COLUMN IF NOT EXISTS teacher_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_chapters ADD COLUMN IF NOT EXISTS school_id INT DEFAULT NULL");
    $pdo->exec("ALTER TABLE lms_chapters ADD INDEX (class_id)");

    // 3. Update lms_questions table (if needed, but usually linked via exam_id)
    
    echo "LMS tables updated successfully with school/class context.";
} catch (Exception $e) {
    echo "Error updating tables: " . $e->getMessage();
}
?>
