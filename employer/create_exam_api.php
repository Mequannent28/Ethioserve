<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in and is an employer
if (!isLoggedIn() || $_SESSION['role'] !== 'employer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = getCurrentUserId();
    
    // Get company details
    $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();
    
    if (!$company) {
        echo json_encode(['success' => false, 'message' => 'Company profile not found']);
        exit();
    }
    
    $company_id = $company['id'];
    $title = sanitize($_POST['title'] ?? '');
    $description = sanitize($_POST['description'] ?? '');
    $duration = (int)($_POST['duration_minutes'] ?? 30);
    $questions = $_POST['questions'] ?? [];

    if (empty($title) || empty($questions)) {
        echo json_encode(['success' => false, 'message' => 'Title and questions are required']);
        exit();
    }

    try {
        $pdo->beginTransaction();

        // Insert Exam
        $stmt = $pdo->prepare("INSERT INTO job_exams (company_id, title, description, duration_minutes) VALUES (?, ?, ?, ?)");
        $stmt->execute([$company_id, $title, $description, $duration]);
        $exam_id = $pdo->lastInsertId();

        // Insert Questions
        $stmt_q = $pdo->prepare("INSERT INTO job_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_option) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($questions as $q) {
            if (!empty($q['text']) && !empty($q['opt_a']) && !empty($q['opt_b']) && !empty($q['correct'])) {
                $stmt_q->execute([
                    $exam_id,
                    sanitize($q['text']),
                    sanitize($q['opt_a']),
                    sanitize($q['opt_b']),
                    sanitize($q['opt_c'] ?? ''),
                    sanitize($q['opt_d'] ?? ''),
                    strtoupper(sanitize($q['correct']))
                ]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'exam_id' => $exam_id, 'title' => $title]);
    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
