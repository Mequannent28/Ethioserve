<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_chapters (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        chapter_number INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT,
        video_url VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (grade, subject)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_reading_progress (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        chapter_id INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY (user_id, chapter_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    // Add chapter_id to lms_exams if not exists
    $cols = $pdo->query("SHOW COLUMNS FROM lms_exams LIKE 'chapter_id'")->fetch();
    if (!$cols) {
        $pdo->exec("ALTER TABLE lms_exams ADD COLUMN chapter_id INT NULL AFTER id");
    }
    
    echo "LMS Tables updated successfully.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
