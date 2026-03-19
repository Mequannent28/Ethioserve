<?php
/**
 * SMS Migration - Automatically creates School Management System tables if missing.
 * This is crucial for deployment on Render/Production.
 */
function migrateSMS($pdo) {
    try {
        // 1. Ensure users table has all required roles
        $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'hotel', 'broker', 'property_owner', 'transport', 'customer', 'restaurant', 'taxi', 'student', 'teacher', 'parent', 'school_admin', 'employer', 'doctor', 'dating') DEFAULT 'customer'");

        // 2. Check if a core table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'sms_student_profiles'");
        if ($stmt->fetch()) return; // Already migrated

        $queries = [
            "CREATE TABLE IF NOT EXISTS `sms_classes` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `class_name` varchar(50) NOT NULL,
              `section` varchar(20) DEFAULT NULL,
              `capacity` int(11) DEFAULT 40,
              `room_number` varchar(20) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_subjects` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `subject_name` varchar(100) NOT NULL,
              `subject_code` varchar(20) DEFAULT NULL,
              `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_parents` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) DEFAULT NULL,
              `occupation` varchar(100) DEFAULT NULL,
              `address` text DEFAULT NULL,
              `alternate_phone` varchar(20) DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `user_id` (`user_id`),
              CONSTRAINT `sms_parents_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_teachers` (
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
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_student_profiles` (
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
              UNIQUE KEY `student_id_number` (`student_id_number`),
              KEY `parent_id` (`parent_id`),
              KEY `class_id` (`class_id`),
              CONSTRAINT `sms_student_profiles_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
              CONSTRAINT `sms_student_profiles_ibfk_2` FOREIGN KEY (`parent_id`) REFERENCES `sms_parents` (`id`) ON DELETE SET NULL,
              CONSTRAINT `sms_student_profiles_ibfk_3` FOREIGN KEY (`class_id`) REFERENCES `sms_classes` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_assignments` (
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
              KEY `subject_id` (`subject_id`),
              CONSTRAINT `sms_assignments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `sms_classes` (`id`),
              CONSTRAINT `sms_assignments_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `sms_subjects` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_assignment_submissions` (
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
              KEY `student_id` (`student_id`),
              CONSTRAINT `sms_assignment_submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `sms_assignments` (`id`) ON DELETE CASCADE,
              CONSTRAINT `sms_assignment_submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_class_subjects` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `class_id` int(11) DEFAULT NULL,
              `subject_id` int(11) DEFAULT NULL,
              `teacher_id` int(11) DEFAULT NULL,
              PRIMARY KEY (`id`),
              KEY `class_id` (`class_id`),
              KEY `subject_id` (`subject_id`),
              KEY `teacher_id` (`teacher_id`),
              CONSTRAINT `sms_class_subjects_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `sms_classes` (`id`) ON DELETE CASCADE,
              CONSTRAINT `sms_class_subjects_ibfk_2` FOREIGN KEY (`subject_id`) REFERENCES `sms_subjects` (`id`) ON DELETE CASCADE,
              CONSTRAINT `sms_class_subjects_ibfk_3` FOREIGN KEY (`teacher_id`) REFERENCES `sms_teachers` (`id`) ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_attendance` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `student_id` int(11) DEFAULT NULL,
              `class_id` int(11) DEFAULT NULL,
              `status` enum('Present','Absent','Late','Excused') DEFAULT NULL,
              `attendance_date` date DEFAULT NULL,
              `marked_by` int(11) DEFAULT NULL,
              `remarks` text DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `unique_attendance` (`student_id`,`class_id`,`attendance_date`),
              KEY `class_id` (`class_id`),
              CONSTRAINT `sms_attendance_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`),
              CONSTRAINT `sms_attendance_ibfk_2` FOREIGN KEY (`class_id`) REFERENCES `sms_classes` (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;",

            "CREATE TABLE IF NOT EXISTS `sms_notifications` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_id` int(11) NOT NULL,
              `title` varchar(255) NOT NULL,
              `message` text NOT NULL,
              `type` varchar(50) DEFAULT 'info',
              `status` enum('unread','read') DEFAULT 'unread',
              `created_at` datetime DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `user_id` (`user_id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
        ];

        foreach ($queries as $q) {
            $pdo->exec($q);
        }
    } catch (Exception $e) {
        // Silently log or handle error
        error_log("SMS Migration Error: " . $e->getMessage());
    }
}
