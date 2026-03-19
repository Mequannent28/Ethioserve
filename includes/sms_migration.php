<?php
/**
 * SMS Migration - Automatically creates School Management System tables if missing.
 * Each statement has its own try/catch so one failure does NOT block the others.
 */
function migrateSMS($pdo) {

    // 1. Ensure users table has all required roles (separate try/catch)
    try {
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM(
            'admin','hotel','broker','property_owner','transport','customer',
            'restaurant','taxi','student','teacher','parent','school_admin',
            'employer','doctor','dating'
        ) DEFAULT 'customer'");
    } catch (Exception $e) {
        error_log("SMS role ENUM update: " . $e->getMessage());
    }

    // 2. Core class/subject tables (no foreign key deps)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_classes` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_name` varchar(50) NOT NULL,
          `section` varchar(20) DEFAULT NULL,
          `capacity` int(11) DEFAULT 40,
          `room_number` varchar(20) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_classes: " . $e->getMessage()); }

    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_subjects` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `subject_name` varchar(100) NOT NULL,
          `subject_code` varchar(20) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_subjects: " . $e->getMessage()); }

    // 3. Parents table (depends on users)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_parents` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL,
          `occupation` varchar(100) DEFAULT NULL,
          `address` text DEFAULT NULL,
          `alternate_phone` varchar(20) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`),
          CONSTRAINT `sms_parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_parents: " . $e->getMessage()); }

    // 4. Teachers table (depends on users)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_teachers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) DEFAULT NULL,
          `employee_id` varchar(50) DEFAULT NULL,
          `specialization` varchar(100) DEFAULT NULL,
          `qualification` text DEFAULT NULL,
          `joining_date` date DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `user_id` (`user_id`),
          UNIQUE KEY `employee_id` (`employee_id`),
          CONSTRAINT `sms_teachers_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_teachers: " . $e->getMessage()); }

    // 5. Student profiles (depends on users, sms_parents, sms_classes)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_student_profiles` (
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
          UNIQUE KEY `user_id` (`user_id`),
          KEY `parent_id` (`parent_id`),
          KEY `class_id` (`class_id`),
          CONSTRAINT `sms_sp_user_fk` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
          CONSTRAINT `sms_sp_parent_fk` FOREIGN KEY (`parent_id`) REFERENCES `sms_parents` (`id`) ON DELETE SET NULL,
          CONSTRAINT `sms_sp_class_fk` FOREIGN KEY (`class_id`) REFERENCES `sms_classes` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_student_profiles: " . $e->getMessage()); }

    // 6. Assignments (depends on sms_classes, sms_subjects)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_assignments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_id` int(11) DEFAULT NULL,
          `subject_id` int(11) DEFAULT NULL,
          `teacher_id` int(11) DEFAULT NULL,
          `title` varchar(255) DEFAULT NULL,
          `description` text DEFAULT NULL,
          `file_path` varchar(255) DEFAULT NULL,
          `due_date` datetime DEFAULT NULL,
          `max_points` int(11) DEFAULT 100,
          PRIMARY KEY (`id`),
          KEY `class_id` (`class_id`),
          KEY `subject_id` (`subject_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_assignments: " . $e->getMessage()); }

    // 7. Assignment submissions (depends on sms_assignments, users)
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_assignment_submissions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `assignment_id` int(11) NOT NULL,
          `student_id` int(11) DEFAULT NULL,
          `file_path` varchar(255) DEFAULT NULL,
          `submission_date` timestamp NOT NULL DEFAULT current_timestamp(),
          `grade` varchar(10) DEFAULT NULL,
          `teacher_feedback` text DEFAULT NULL,
          `submission_text` text DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `assignment_id` (`assignment_id`),
          KEY `student_id` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_assignment_submissions: " . $e->getMessage()); }

    // 8. Class-subject linking table
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_class_subjects` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_id` int(11) DEFAULT NULL,
          `subject_id` int(11) DEFAULT NULL,
          `teacher_id` int(11) DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `class_id` (`class_id`),
          KEY `subject_id` (`subject_id`),
          KEY `teacher_id` (`teacher_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_class_subjects: " . $e->getMessage()); }

    // 9. Attendance
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_attendance` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `student_id` int(11) DEFAULT NULL,
          `class_id` int(11) DEFAULT NULL,
          `status` enum('Present','Absent','Late','Excused') DEFAULT NULL,
          `attendance_date` date DEFAULT NULL,
          `marked_by` int(11) DEFAULT NULL,
          `remarks` text DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `unique_attendance` (`student_id`,`class_id`,`attendance_date`),
          KEY `class_id` (`class_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_attendance: " . $e->getMessage()); }

    // 10. Timetable
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_timetables` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_id` int(11) DEFAULT NULL,
          `subject_id` int(11) DEFAULT NULL,
          `day_of_week` enum('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') DEFAULT NULL,
          `start_time` time DEFAULT NULL,
          `end_time` time DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `class_id` (`class_id`),
          KEY `subject_id` (`subject_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_timetables: " . $e->getMessage()); }

    // 11. Fee structure
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_fee_structure` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `class_id` int(11) DEFAULT NULL,
          `fee_type` varchar(100) DEFAULT NULL,
          `amount` decimal(10,2) DEFAULT NULL,
          `due_date` date DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `class_id` (`class_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_fee_structure: " . $e->getMessage()); }

    // 12. Fee payments
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_fee_payments` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `student_id` int(11) DEFAULT NULL,
          `fee_id` int(11) DEFAULT NULL,
          `amount_paid` decimal(10,2) DEFAULT NULL,
          `payment_date` timestamp NOT NULL DEFAULT current_timestamp(),
          `payment_method` varchar(50) DEFAULT NULL,
          `transaction_id` varchar(100) DEFAULT NULL,
          `status` enum('Paid','Partial','Pending') DEFAULT 'Paid',
          PRIMARY KEY (`id`),
          KEY `student_id` (`student_id`),
          KEY `fee_id` (`fee_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_fee_payments: " . $e->getMessage()); }

    // 13. Notifications
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS `sms_notifications` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `user_id` int(11) NOT NULL,
          `title` varchar(255) NOT NULL,
          `message` text NOT NULL,
          `type` varchar(50) DEFAULT 'info',
          `status` enum('unread','read') DEFAULT 'unread',
          `created_at` datetime DEFAULT current_timestamp(),
          PRIMARY KEY (`id`),
          KEY `user_id` (`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    } catch (Exception $e) { error_log("SMS sms_notifications: " . $e->getMessage()); }
}
