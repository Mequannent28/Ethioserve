<?php
require_once 'includes/db.php';

try {
    // 1. First, find and delete duplicates keep only the latest one if we wanted to be fancy, 
    // but a simple "keep first" is safer for a schema fix.
    // Or just group by and leave it.
    
    // For safety, we can't easily dedup with a single query without potential data loss.
    // Let's just try to add the unique index. If it fails, we know there are duplicates.
    
    echo "Attempting to add UNIQUE constraint to sms_attendance...\n";
    $sql = "ALTER TABLE sms_attendance ADD UNIQUE KEY `unique_attendance` (student_id, class_id, attendance_date)";
    $pdo->exec($sql);
    echo "Successfully added UNIQUE constraint.\n";
    
} catch (Exception $e) {
    echo "Error adding constraint: " . $e->getMessage() . "\n";
    echo "Duplicates probably exist. We need to clean them up first.\n";
    
    // Simple cleanup: Keep the row with the highest ID for each group
    echo "Cleaning up duplicates (keeping the most recent entry)...\n";
    $cleanup = "
        DELETE t1 FROM sms_attendance t1
        INNER JOIN sms_attendance t2 
        WHERE 
            t1.id < t2.id AND 
            t1.student_id = t2.student_id AND 
            t1.class_id = t2.class_id AND 
            t1.attendance_date = t2.attendance_date
    ";
    $deleted = $pdo->exec($cleanup);
    echo "Deleted $deleted duplicate rows.\n";
    
    // Try again
    try {
        $pdo->exec($sql);
        echo "Successfully added UNIQUE constraint after cleanup.\n";
    } catch (Exception $e2) {
        echo "Final attempt failed: " . $e2->getMessage() . "\n";
    }
}
?>
