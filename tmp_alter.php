<?php
require 'includes/db.php';
try {
    $pdo->exec("ALTER TABLE sms_student_profiles 
        ADD COLUMN parent_name VARCHAR(100) NULL,
        ADD COLUMN parent_phone VARCHAR(50) NULL,
        ADD COLUMN emergency_contact VARCHAR(50) NULL,
        ADD COLUMN previous_school VARCHAR(100) NULL,
        ADD COLUMN health_conditions TEXT NULL,
        ADD COLUMN blood_group VARCHAR(10) NULL,
        ADD COLUMN home_address TEXT NULL
    ");
    echo "Columns added successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
