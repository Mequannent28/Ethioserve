<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS job_exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        company_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration_minutes INT DEFAULT 30,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(255) NOT NULL,
        option_b VARCHAR(255) NOT NULL,
        option_c VARCHAR(255),
        option_d VARCHAR(255),
        correct_option CHAR(1) NOT NULL,
        points INT DEFAULT 1,
        FOREIGN KEY (exam_id) REFERENCES job_exams(id) ON DELETE CASCADE
    );");

    $pdo->exec("CREATE TABLE IF NOT EXISTS job_exam_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        candidate_id INT NOT NULL,
        company_id INT NOT NULL,
        status ENUM('pending', 'ongoing', 'completed') DEFAULT 'pending',
        score INT DEFAULT 0,
        total_score INT DEFAULT 0,
        started_at DATETIME NULL,
        completed_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES job_exams(id) ON DELETE CASCADE,
        FOREIGN KEY (candidate_id) REFERENCES users(id) ON DELETE CASCADE
    );");

    echo "Tables created successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
