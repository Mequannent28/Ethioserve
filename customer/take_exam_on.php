<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();
$user_id = getCurrentUserId();

if (!isset($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$exam_id = (int)$_GET['id'];
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;
$attempt_id = $_SESSION['exam_attempt_id'] ?? null;

if (!$attempt_id) {
    header("Location: job_take_exam.php?id=" . $exam_id);
    exit();
}

// Ensure the attempt belongs to this user and is ongoing
$stmt = $pdo->prepare("SELECT * FROM job_exam_attempts WHERE id = ? AND candidate_id = ? AND status = 'ongoing'");
$stmt->execute([$attempt_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    // Already submitted or invalid
    $msg = "You have already completed this exam.";
    echo "<script>alert('$msg'); window.location.href='jobs.php';</script>";
    exit();
}

// Fetch exam
$stmt = $pdo->prepare("SELECT * FROM job_exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

// Calculate time remaining
$started_at = strtotime($attempt['started_at']);
$now = time();
$duration_seconds = $exam['duration_minutes'] * 60;
$time_spent = $now - $started_at;
$time_remaining = $duration_seconds - $time_spent;

if ($time_remaining <= 0) {
    $time_remaining = 0;
    // Auto-submit could go here or let JS submit
}

// Get Questions
$stmt = $pdo->prepare("SELECT * FROM job_questions WHERE exam_id = ?");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_exam'])) {
    $score = 0;
    $total_points = 0;
    foreach ($questions as $q) {
        $total_points += $q['points'];
    }
    
    // Evaluate answers
    foreach ($questions as $q) {
        $q_id = $q['id'];
        $user_ans = $_POST['answers'][$q_id] ?? null;
        if ($user_ans && $user_ans == $q['correct_option']) {
            $score += $q['points'];
        }
    }
    
    $percentage = ($total_points > 0) ? ($score / $total_points) * 100 : 0;
    
    $stmt = $pdo->prepare("UPDATE job_exam_attempts SET status = 'completed', completed_at = NOW(), score = ?, total_score = ? WHERE id = ?");
    $stmt->execute([$score, $total_points, $attempt_id]);
    
    // Notify candidate by email
    try {
        require_once '../includes/email_service.php';
        $user_email = $_SESSION['email'] ?? '';
        if (!$user_email) {
            $stmt_u = $pdo->prepare("SELECT email FROM users WHERE id = ?");
            $stmt_u->execute([$user_id]);
            $user_email = $stmt_u->fetchColumn();
        }
        
        if ($user_email) {
            $status_text = ($percentage >= 50) ? "PASSED" : "FAILED";
            $title = "Exam Result: " . $exam['title'];
            $msg = "Hello " . getCurrentUserName() . ",\n\nYou have completed the assessment: <strong>{$exam['title']}</strong>.\n\n<strong>Your Score:</strong> {$score} / {$total_points} (" . round($percentage, 2) . "%)\n<strong>Status:</strong> {$status_text}\n\n" . ($percentage >= 50 ? "Congratulations! You can now proceed with your application." : "Unfortunately, you did not meet the minimum score requirement for this position.");
            sendDirectNotification($user_email, $title, $msg, 'View Jobs', '/customer/jobs.php');
        }
    } catch (Exception $e) { /* Silently fail email */ }

    unset($_SESSION['exam_attempt_id']);
    
    if ($percentage < 50) {
        $_SESSION['error_message'] = "Score: $score / $total_points ($percentage%). You did not pass the assessment. You can not applyy this jop.";
        if ($job_id) {
            header("Location: jobs.php?tab=jobs&job_id=$job_id&failed_exam=1");
        } else {
            header("Location: jobs.php");
        }
        exit();
    }
    
    $_SESSION['success_message'] = "Congratulations! You passed with " . round($percentage, 2) . "%. You can now proceed with your application.";
    
    if ($job_id) {
        // Redirect back to job page with auto-apply trigger
        header("Location: jobs.php?tab=jobs&job_id=$job_id&applied_after_exam=1");
    } else {
        header("Location: jobs.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exam: <?php echo htmlspecialchars($exam['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdfdfd; padding-top: 80px; }
        .exam-header {
            position: fixed;
            top: 0; left: 0; right: 0;
            background: #1B5E20; color: white;
            padding: 15px 30px;
            display: flex; justify-content: space-between; align-items: center;
            z-index: 1000; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .timer { font-size: 1.5rem; font-weight: bold; background: rgba(255,255,255,0.2); padding: 5px 15px; border-radius: 5px; }
        .timer.danger { color: #f44336; background: rgba(244,67,54,0.2); }
        .q-card { background: white; border: 1px solid #eee; border-radius: 10px; padding: 25px; margin-bottom: 25px; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        .q-text { font-size: 1.1rem; font-weight: 500; color: #333; margin-bottom: 20px; }
        .opt-label { display: block; padding: 12px 20px; border: 1px solid #ddd; border-radius: 8px; margin-bottom: 15px; cursor: pointer; transition: all 0.3s; }
        .opt-label:hover { background: #f9f9f9; border-color: #1B5E20; }
        .opt-label input { margin-right: 15px; }
        .opt-label.selected { background: #E8F5E9; border-color: #1B5E20; font-weight: 500; }
    </style>
</head>
<body>
    <div class="exam-header">
        <div>
            <h4 class="mb-0"><?php echo htmlspecialchars($exam['title']); ?></h4>
            <div class="small fw-light">Candidate: <?php echo htmlspecialchars(getCurrentUserName()); ?></div>
        </div>
        <div class="timer" id="timerDisplay">
            Loading...
        </div>
    </div>

    <div class="container py-4">
        <form method="POST" id="examForm">
            <input type="hidden" name="submit_exam" value="1">
            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
            
            <?php foreach ($questions as $index => $q): ?>
                <div class="q-card">
                    <div class="q-text">
                        <span class="text-muted fw-bold me-2"><?php echo $index + 1; ?>.</span>
                        <?php echo nl2br(htmlspecialchars($q['question_text'])); ?>
                        <span class="badge bg-light text-dark ms-2 fw-normal" style="font-size: 0.8rem;"><?php echo $q['points']; ?> Points</span>
                    </div>
                    
                    <div class="options-group">
                        <label class="opt-label">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="A"> A) <?php echo htmlspecialchars($q['option_a']); ?>
                        </label>
                        <label class="opt-label">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="B"> B) <?php echo htmlspecialchars($q['option_b']); ?>
                        </label>
                        <?php if(!empty($q['option_c'])): ?>
                        <label class="opt-label">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="C"> C) <?php echo htmlspecialchars($q['option_c']); ?>
                        </label>
                        <?php endif; ?>
                        <?php if(!empty($q['option_d'])): ?>
                        <label class="opt-label">
                            <input type="radio" name="answers[<?php echo $q['id']; ?>]" value="D"> D) <?php echo htmlspecialchars($q['option_d']); ?>
                        </label>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>

            <div class="text-center mt-5 mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow"><i class="fas fa-paper-plane me-2"></i> Submit Exam</button>
            </div>
        </form>
    </div>

    <script>
        // JS for timer
        let timeRemaining = <?php echo $time_remaining; ?>;
        const display = document.getElementById('timerDisplay');
        const form = document.getElementById('examForm');
        
        function updateTimer() {
            let m = Math.floor(timeRemaining / 60);
            let s = timeRemaining % 60;
            display.textContent = (m < 10 ? '0' : '') + m + ':' + (s < 10 ? '0' : '') + s;
            
            if (timeRemaining <= 60) {
                display.classList.add('danger');
            }
            
            if (timeRemaining <= 0) {
                display.textContent = '00:00';
                clearInterval(timerInterval);
                form.submit();
            }
            timeRemaining--;
        }
        
        updateTimer();
        const timerInterval = setInterval(updateTimer, 1000);

        // Styling for radio selection
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const name = this.name;
                // remove selected class from all labels in this group
                document.querySelectorAll(`input[name="${name}"]`).forEach(r => {
                    r.parentElement.classList.remove('selected');
                });
                // add to current
                if(this.checked) this.parentElement.classList.add('selected');
            });
        });
    </script>
</body>
</html>
