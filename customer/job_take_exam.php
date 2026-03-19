<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
requireLogin();
$user_id = getCurrentUserId();

// Ensure the user is a candidate (not an employer)
// You might have a specific role check if needed, but any logged-in user can be a candidate for a job exam.
if(hasRole('employer')){
    // If employer tries to take an exam, maybe redirect them to their dashboard
    header("Location: ../employer/dashboard.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: jobs.php");
    exit();
}

$exam_id = (int)$_GET['id'];
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : null;

if ($job_id) {
    // Fetch Job Details for context
    $stmt = $pdo->prepare("SELECT title FROM job_listings WHERE id = ?");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
}

// Fetch Exam
$stmt = $pdo->prepare("SELECT * FROM job_exams WHERE id = ?");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();

if (!$exam) {
    header("Location: jobs.php");
    exit();
}

// Check if candidate already has an attempt
$stmt = $pdo->prepare("SELECT * FROM job_exam_attempts WHERE exam_id = ? AND candidate_id = ?");
$stmt->execute([$exam_id, $user_id]);
$attempt = $stmt->fetch();

if ($attempt && $attempt['status'] == 'completed') {
    $msg = "You have already completed this exam.";
    // Need a page to show exam result, or redirect back to jobs
    echo "<script>alert('$msg'); window.location.href='jobs.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['start_exam'])) {
    if (!$attempt) {
        $stmt = $pdo->prepare("INSERT INTO job_exam_attempts (exam_id, candidate_id, company_id, status, started_at) VALUES (?, ?, ?, 'ongoing', NOW())");
        $stmt->execute([$exam_id, $user_id, $exam['company_id']]);
        $_SESSION['exam_attempt_id'] = $pdo->lastInsertId();
    } else {
        $_SESSION['exam_attempt_id'] = $attempt['id'];
        $stmt = $pdo->prepare("UPDATE job_exam_attempts SET status = 'ongoing', started_at = NOW() WHERE id = ?");
        $stmt->execute([$attempt['id']]);
    }
    $job_param = $job_id ? "&job_id=" . $job_id : "";
    header("Location: take_exam_on.php?id=" . $exam_id . $job_param);
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Take Exam - <?php echo htmlspecialchars($exam['title']); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fdfdfd; }
        .exam-hero {
            background: linear-gradient(135deg, #1B5E20, #4CAF50);
            color: white;
            padding: 80px 0;
            text-align: center;
        }
        .exam-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            padding: 40px;
            margin-top: -50px;
            position: relative;
        }
    </style>
</head>
<body>
    <?php include('../includes/header.php'); ?>

    <div class="exam-hero">
        <div class="container">
            <h1 class="display-4 fw-bold">Online Assessment</h1>
            <?php if(isset($job) && $job): ?>
                <p class="lead">Required for: <strong><?php echo htmlspecialchars($job['title']); ?></strong></p>
            <?php else: ?>
                <p class="lead">Prepare to demonstrate your skills.</p>
            <?php endif; ?>
        </div>
    </div>

    <div class="container mb-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="exam-card text-center">
                    <h2 class="fw-bold mb-3"><?php echo htmlspecialchars($exam['title']); ?></h2>
                    <p class="text-muted mb-4"><?php echo nl2br(htmlspecialchars($exam['description'])); ?></p>
                    
                    <div class="d-flex justify-content-center gap-4 mb-4">
                        <div class="p-3 bg-light rounded-3">
                            <i class="fas fa-clock text-primary fs-3 mb-2"></i>
                            <div class="fw-bold fs-5"><?php echo $exam['duration_minutes']; ?> Mins</div>
                            <div class="text-muted small">Duration</div>
                        </div>
                        <?php
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_questions WHERE exam_id = ?");
                            $stmt->execute([$exam_id]);
                            $q_count = $stmt->fetchColumn();
                        ?>
                        <div class="p-3 bg-light rounded-3">
                            <i class="fas fa-list-ol text-success fs-3 mb-2"></i>
                            <div class="fw-bold fs-5"><?php echo $q_count; ?></div>
                            <div class="text-muted small">Questions</div>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="start_exam" value="1">
                        <button type="submit" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-sm" <?php echo ($q_count == 0) ? 'disabled' : ''; ?>>
                            <i class="fas fa-play me-2"></i> Start Exam Now
                        </button>
                    </form>
                    <?php if($q_count == 0): ?>
                        <div class="text-danger mt-3 small"><i class="fas fa-exclamation-triangle"></i> This exam has no questions yet.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
