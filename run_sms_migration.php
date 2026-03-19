<?php
/**
 * Emergency Migration Script
 * Run this once on Render to create all School Management System tables.
 * URL: https://ethioserve-j88x.onrender.com/run_sms_migration.php
 */
require_once 'includes/db.php';
require_once 'includes/sms_migration.php';

echo "<pre style='font-family:monospace;background:#111;color:#0f0;padding:20px;'>";
echo "🚀 Running SMS Full Migration...\n\n";

// Force run all individual creates
$tables = [
    'sms_classes' => "CREATE TABLE IF NOT EXISTS `sms_classes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_name` varchar(50) NOT NULL,
        `section` varchar(20) DEFAULT NULL,
        `capacity` int(11) DEFAULT 40,
        `room_number` varchar(20) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_subjects' => "CREATE TABLE IF NOT EXISTS `sms_subjects` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `subject_name` varchar(100) NOT NULL,
        `subject_code` varchar(20) DEFAULT NULL,
        `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_parents' => "CREATE TABLE IF NOT EXISTS `sms_parents` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `occupation` varchar(100) DEFAULT NULL,
        `address` text DEFAULT NULL,
        `alternate_phone` varchar(20) DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_teachers' => "CREATE TABLE IF NOT EXISTS `sms_teachers` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `employee_id` varchar(50) DEFAULT NULL,
        `specialization` varchar(100) DEFAULT NULL,
        `qualification` text DEFAULT NULL,
        `joining_date` date DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_student_profiles' => "CREATE TABLE IF NOT EXISTS `sms_student_profiles` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) DEFAULT NULL,
        `parent_id` int(11) DEFAULT NULL,
        `class_id` int(11) DEFAULT NULL,
        `student_id_number` varchar(50) DEFAULT NULL,
        `date_of_birth` date DEFAULT NULL,
        `gender` enum('Male','Female','Other') DEFAULT NULL,
        `parent_name` varchar(100) DEFAULT NULL,
        `parent_phone` varchar(50) DEFAULT NULL,
        `emergency_contact` varchar(50) DEFAULT NULL,
        `previous_school` varchar(100) DEFAULT NULL,
        `health_conditions` text DEFAULT NULL,
        `blood_group` varchar(10) DEFAULT NULL,
        `home_address` text DEFAULT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `user_id` (`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_assignments' => "CREATE TABLE IF NOT EXISTS `sms_assignments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) DEFAULT NULL,
        `subject_id` int(11) DEFAULT NULL,
        `teacher_id` int(11) DEFAULT NULL,
        `title` varchar(255) DEFAULT NULL,
        `description` text DEFAULT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `due_date` datetime DEFAULT NULL,
        `max_points` int(11) DEFAULT 100,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_assignment_submissions' => "CREATE TABLE IF NOT EXISTS `sms_assignment_submissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `assignment_id` int(11) NOT NULL,
        `student_id` int(11) DEFAULT NULL,
        `file_path` varchar(255) DEFAULT NULL,
        `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
        `grade` varchar(10) DEFAULT NULL,
        `teacher_feedback` text DEFAULT NULL,
        `submission_text` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_class_subjects' => "CREATE TABLE IF NOT EXISTS `sms_class_subjects` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) DEFAULT NULL,
        `subject_id` int(11) DEFAULT NULL,
        `teacher_id` int(11) DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_attendance' => "CREATE TABLE IF NOT EXISTS `sms_attendance` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) DEFAULT NULL,
        `class_id` int(11) DEFAULT NULL,
        `status` enum('Present','Absent','Late','Excused') DEFAULT NULL,
        `attendance_date` date DEFAULT NULL,
        `marked_by` int(11) DEFAULT NULL,
        `remarks` text DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_timetables' => "CREATE TABLE IF NOT EXISTS `sms_timetables` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) DEFAULT NULL,
        `subject_id` int(11) DEFAULT NULL,
        `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
        `start_time` time DEFAULT NULL,
        `end_time` time DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_fee_structure' => "CREATE TABLE IF NOT EXISTS `sms_fee_structure` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) DEFAULT NULL,
        `fee_type` varchar(100) DEFAULT NULL,
        `amount` decimal(10,2) DEFAULT NULL,
        `due_date` date DEFAULT NULL,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_fee_payments' => "CREATE TABLE IF NOT EXISTS `sms_fee_payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) DEFAULT NULL,
        `fee_id` int(11) DEFAULT NULL,
        `amount_paid` decimal(10,2) DEFAULT NULL,
        `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
        `payment_method` varchar(50) DEFAULT NULL,
        `transaction_id` varchar(100) DEFAULT NULL,
        `status` enum('Paid','Partial','Pending') DEFAULT 'Paid',
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    'sms_notifications' => "CREATE TABLE IF NOT EXISTS `sms_notifications` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `title` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `type` varchar(50) DEFAULT 'info',
        `status` enum('unread','read') DEFAULT 'unread',
        `created_at` datetime DEFAULT current_timestamp(),
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($tables as $table => $sql) {
    try {
        $pdo->exec($sql);
        // Verify it exists
        $pdo->query("SELECT 1 FROM `$table` LIMIT 1");
        echo "✅ $table — OK\n";
    } catch (Exception $e) {
        echo "❌ $table — ERROR: " . $e->getMessage() . "\n";
    }
}

// Seed demo class if none exist
try {
    $count = $pdo->query("SELECT COUNT(*) FROM sms_classes")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO sms_classes (class_name, section, capacity, room_number) VALUES ('Grade 10', 'A', 40, 'Room 101'), ('Grade 11', 'B', 35, 'Room 205'), ('Grade 12', 'A', 30, 'Room 301')");
        echo "\n✅ Seeded 3 demo classes\n";
    } else {
        echo "\n✅ Classes already exist ($count found)\n";
    }
} catch (Exception $e) {
    echo "❌ Seeding classes: " . $e->getMessage() . "\n";
}

// Seed demo subjects
try {
    $count = $pdo->query("SELECT COUNT(*) FROM sms_subjects")->fetchColumn();
    if ($count == 0) {
        $pdo->exec("INSERT INTO sms_subjects (subject_name, subject_code) VALUES ('Mathematics','MATH'),('English','ENG'),('Biology','BIO'),('Physics','PHY'),('Chemistry','CHEM'),('History','HIST')");
        echo "✅ Seeded 6 demo subjects\n";
    } else {
        echo "✅ Subjects already exist ($count found)\n";
    }
} catch (Exception $e) {
    echo "❌ Seeding subjects: " . $e->getMessage() . "\n";
}

echo "\n\n🎉 Migration complete!\n";
echo "Next step → seed test users: <a href='/tmp/setup_school_users.php' style='color:#4fc;'>/tmp/setup_school_users.php</a>\n";
echo "</pre>";
