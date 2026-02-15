<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Create LMS tables if they don't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        chapter INT NOT NULL DEFAULT 1,
        chapter_title VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        duration_minutes INT DEFAULT 30,
        pass_percentage INT DEFAULT 50,
        total_questions INT DEFAULT 10,
        difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
        status ENUM('active','draft','archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_text TEXT NOT NULL,
        option_a VARCHAR(500) NOT NULL,
        option_b VARCHAR(500) NOT NULL,
        option_c VARCHAR(500) NOT NULL,
        option_d VARCHAR(500) NOT NULL,
        correct_answer CHAR(1) NOT NULL,
        explanation TEXT,
        points INT DEFAULT 1,
        sort_order INT DEFAULT 0,
        FOREIGN KEY (exam_id) REFERENCES lms_exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        exam_id INT NOT NULL,
        score DECIMAL(5,2) DEFAULT 0,
        total_points INT DEFAULT 0,
        earned_points INT DEFAULT 0,
        status ENUM('in_progress','completed','abandoned') DEFAULT 'in_progress',
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        completed_at TIMESTAMP NULL,
        time_spent_seconds INT DEFAULT 0,
        FOREIGN KEY (exam_id) REFERENCES lms_exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_answers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        attempt_id INT NOT NULL,
        question_id INT NOT NULL,
        selected_answer CHAR(1),
        is_correct TINYINT(1) DEFAULT 0,
        FOREIGN KEY (attempt_id) REFERENCES lms_attempts(id) ON DELETE CASCADE,
        FOREIGN KEY (question_id) REFERENCES lms_questions(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
    // Tables may already exist
}

$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$subject = sanitize($_GET['subject'] ?? '');

// Get user info if logged in
$user_id = $_SESSION['user_id'] ?? null;

// Subjects by grade (matches education portal)
$subjects_by_grade = [
    1 => ['Amharic', 'English', 'Mathematics', 'Environmental Science'],
    2 => ['Amharic', 'English', 'Mathematics', 'Environmental Science'],
    3 => ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Afan Oromo'],
    4 => ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Afan Oromo'],
    5 => ['Amharic', 'English', 'Mathematics', 'General Science', 'Social Studies', 'Civics'],
    6 => ['Amharic', 'English', 'Mathematics', 'General Science', 'Social Studies', 'Civics'],
    7 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics'],
    8 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Civics'],
    9 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Economics', 'ICT'],
    10 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Economics', 'ICT'],
    11 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Economics', 'ICT'],
    12 => ['Amharic', 'English', 'Mathematics', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Economics', 'ICT'],
];

$subject_colors = [
    'Amharic' => '#E65100',
    'English' => '#1565C0',
    'Mathematics' => '#2E7D32',
    'Environmental Science' => '#00897B',
    'Afan Oromo' => '#6A1B9A',
    'Social Studies' => '#AD1457',
    'Civics' => '#4527A0',
    'General Science' => '#00838F',
    'Biology' => '#1B5E20',
    'Physics' => '#0D47A1',
    'Chemistry' => '#B71C1C',
    'Geography' => '#E65100',
    'History' => '#5D4037',
    'Economics' => '#F57F17',
    'ICT' => '#263238'
];

$subject_icons = [
    'Amharic' => 'language',
    'English' => 'book-open',
    'Mathematics' => 'calculator',
    'Environmental Science' => 'leaf',
    'Afan Oromo' => 'globe-africa',
    'Social Studies' => 'users',
    'Civics' => 'landmark',
    'General Science' => 'flask',
    'Biology' => 'dna',
    'Physics' => 'atom',
    'Chemistry' => 'vial',
    'Geography' => 'globe-americas',
    'History' => 'monument',
    'Economics' => 'chart-line',
    'ICT' => 'laptop-code'
];

// Fetch exams for selected grade + subject
$exams = [];
$user_attempts = [];
if ($grade > 0 && !empty($subject)) {
    $stmt = $pdo->prepare("SELECT * FROM lms_exams WHERE grade = ? AND subject = ? AND status = 'active' ORDER BY chapter ASC");
    $stmt->execute([$grade, $subject]);
    $exams = $stmt->fetchAll();

    // Get user's best attempts
    if ($user_id && !empty($exams)) {
        $exam_ids = array_column($exams, 'id');
        $placeholders = implode(',', array_fill(0, count($exam_ids), '?'));
        $stmt = $pdo->prepare("SELECT exam_id, MAX(score) as best_score, COUNT(*) as attempt_count 
                               FROM lms_attempts 
                               WHERE user_id = ? AND exam_id IN ($placeholders) AND status = 'completed'
                               GROUP BY exam_id");
        $stmt->execute(array_merge([$user_id], $exam_ids));
        foreach ($stmt->fetchAll() as $a) {
            $user_attempts[$a['exam_id']] = $a;
        }
    }
}

// Stats
$total_exams = 0;
$total_questions = 0;
try {
    $total_exams = $pdo->query("SELECT COUNT(*) FROM lms_exams WHERE status='active'")->fetchColumn();
    $total_questions = $pdo->query("SELECT COUNT(*) FROM lms_questions")->fetchColumn();
} catch (Exception $e) {
}

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<style>
    :root {
        --lms-bg: #0a0f1a;
        --lms-surface: #111827;
        --lms-card: #1a2332;
        --lms-border: rgba(255, 255, 255, 0.08);
        --lms-text: #e5e7eb;
        --lms-muted: #6b7280;
        --lms-accent: #6366f1;
        --lms-success: #10b981;
        --lms-warning: #f59e0b;
        --lms-danger: #ef4444;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--lms-bg);
        margin: 0;
    }

    .lms-hero {
        background: linear-gradient(135deg, #1e1b4b 0%, #312e81 40%, #4338ca 100%);
        padding: 48px 0 36px;
        position: relative;
        overflow: hidden;
    }

    .lms-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }

    .lms-hero .container {
        position: relative;
        z-index: 1;
    }

    .lms-hero h1 {
        color: #fff;
        font-weight: 900;
        font-size: 2.2rem;
        margin-bottom: 8px;
    }

    .lms-hero p {
        color: rgba(255, 255, 255, 0.7);
        font-size: 1rem;
    }

    .lms-stats {
        display: flex;
        gap: 20px;
        margin-top: 20px;
        flex-wrap: wrap;
    }

    .lms-stat {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        padding: 14px 22px;
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .lms-stat-icon {
        width: 42px;
        height: 42px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
    }

    .lms-stat-val {
        color: #fff;
        font-weight: 800;
        font-size: 1.5rem;
        line-height: 1;
    }

    .lms-stat-label {
        color: rgba(255, 255, 255, 0.6);
        font-size: .75rem;
        font-weight: 500;
    }

    .lms-breadcrumb {
        padding: 16px 0;
        border-bottom: 1px solid var(--lms-border);
        margin-bottom: 28px;
    }

    .lms-breadcrumb a {
        color: var(--lms-accent);
        text-decoration: none;
        font-size: .85rem;
        font-weight: 500;
    }

    .lms-breadcrumb a:hover {
        text-decoration: underline;
    }

    .lms-breadcrumb span {
        color: var(--lms-muted);
        font-size: .85rem;
    }

    .lms-section-title {
        color: #fff;
        font-weight: 800;
        font-size: 1.4rem;
        margin-bottom: 24px;
    }

    /* Grade selector */
    .grade-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(130px, 1fr));
        gap: 12px;
        margin-bottom: 32px;
    }

    .grade-card {
        background: var(--lms-card);
        border: 1px solid var(--lms-border);
        border-radius: 16px;
        padding: 24px 16px;
        text-align: center;
        text-decoration: none;
        transition: all .3s ease;
        cursor: pointer;
    }

    .grade-card:hover {
        transform: translateY(-4px);
        border-color: var(--lms-accent);
        box-shadow: 0 8px 30px rgba(99, 102, 241, 0.2);
    }

    .grade-card.active {
        border-color: var(--lms-accent);
        background: rgba(99, 102, 241, 0.15);
    }

    .grade-num {
        color: #fff;
        font-weight: 900;
        font-size: 1.8rem;
        line-height: 1;
    }

    .grade-label {
        color: var(--lms-muted);
        font-size: .72rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 6px;
    }

    /* Subject cards for exam selection */
    .subject-exam-card {
        background: var(--lms-card);
        border: 1px solid var(--lms-border);
        border-radius: 16px;
        padding: 20px;
        text-decoration: none;
        display: block;
        transition: all .3s ease;
        position: relative;
        overflow: hidden;
    }

    .subject-exam-card:hover {
        transform: translateY(-3px);
        border-color: rgba(255, 255, 255, 0.15);
    }

    .subject-exam-card .subj-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
    }

    .subject-exam-card h6 {
        color: #fff;
        font-weight: 700;
        font-size: .95rem;
        margin: 0;
    }

    .subject-exam-card p {
        color: var(--lms-muted);
        font-size: .75rem;
        margin: 4px 0 0;
    }

    /* Exam list cards */
    .exam-card {
        background: var(--lms-card);
        border: 1px solid var(--lms-border);
        border-radius: 16px;
        padding: 22px;
        transition: all .3s ease;
        position: relative;
    }

    .exam-card:hover {
        border-color: rgba(255, 255, 255, 0.15);
        transform: translateY(-2px);
    }

    .exam-chapter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(99, 102, 241, 0.15);
        color: var(--lms-accent);
        font-size: .72rem;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 50px;
        margin-bottom: 12px;
    }

    .exam-card h5 {
        color: #fff;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 6px;
    }

    .exam-card .exam-desc {
        color: var(--lms-muted);
        font-size: .82rem;
        margin-bottom: 14px;
        line-height: 1.5;
    }

    .exam-meta {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 16px;
    }

    .exam-meta-item {
        background: rgba(255, 255, 255, 0.05);
        border-radius: 8px;
        padding: 6px 12px;
        display: flex;
        align-items: center;
        gap: 6px;
        color: var(--lms-text);
        font-size: .75rem;
        font-weight: 500;
    }

    .exam-meta-item i {
        font-size: .7rem;
    }

    .btn-take-exam {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: var(--lms-accent);
        color: #fff;
        border: none;
        padding: 10px 24px;
        border-radius: 50px;
        font-weight: 700;
        font-size: .82rem;
        cursor: pointer;
        transition: all .3s;
        text-decoration: none;
    }

    .btn-take-exam:hover {
        background: #4f46e5;
        color: #fff;
        transform: translateY(-1px);
    }

    .btn-retake {
        background: transparent;
        border: 1px solid var(--lms-accent);
        color: var(--lms-accent);
    }

    .btn-retake:hover {
        background: var(--lms-accent);
        color: #fff;
    }

    .score-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 50px;
        font-size: .78rem;
        font-weight: 700;
    }

    .score-pass {
        background: rgba(16, 185, 129, 0.15);
        color: var(--lms-success);
    }

    .score-fail {
        background: rgba(239, 68, 68, 0.15);
        color: var(--lms-danger);
    }

    .difficulty-badge {
        font-size: .65rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 50px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .diff-easy {
        background: rgba(16, 185, 129, 0.15);
        color: var(--lms-success);
    }

    .diff-medium {
        background: rgba(245, 158, 11, 0.15);
        color: var(--lms-warning);
    }

    .diff-hard {
        background: rgba(239, 68, 68, 0.15);
        color: var(--lms-danger);
    }

    .empty-state {
        text-align: center;
        padding: 60px 20px;
        color: var(--lms-muted);
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 16px;
        display: block;
        color: var(--lms-accent);
        opacity: .5;
    }

    .empty-state h4 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .login-prompt {
        background: linear-gradient(135deg, rgba(99, 102, 241, 0.1), rgba(139, 92, 246, 0.1));
        border: 1px solid rgba(99, 102, 241, 0.3);
        border-radius: 16px;
        padding: 24px;
        text-align: center;
        margin-bottom: 24px;
    }

    .login-prompt p {
        color: var(--lms-text);
        font-size: .9rem;
        margin-bottom: 12px;
    }

    .login-prompt a {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: var(--lms-accent);
        color: #fff;
        padding: 10px 24px;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none;
        font-size: .85rem;
    }

    .back-link {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        color: var(--lms-accent);
        text-decoration: none;
        font-size: .85rem;
        font-weight: 500;
        margin-bottom: 20px;
    }

    .back-link:hover {
        text-decoration: underline;
        color: #818cf8;
    }

    @media(max-width:768px) {
        .lms-hero h1 {
            font-size: 1.5rem;
        }

        .grade-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
        }

        .grade-card {
            padding: 16px 8px;
        }

        .grade-num {
            font-size: 1.3rem;
        }

        .lms-stats {
            gap: 10px;
        }

        .lms-stat {
            padding: 10px 14px;
        }
    }
</style>

<!-- Hero Section -->
<div class="lms-hero">
    <div class="container">
        <div class="d-flex align-items-center gap-3 mb-2">
            <a href="education.php" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:.85rem;">
                <i class="fas fa-arrow-left me-1"></i> Education Portal
            </a>
        </div>
        <h1><i class="fas fa-brain me-2"></i>Learning Management System</h1>
        <p>Test your knowledge with auto-graded exams for every grade and subject</p>
        <div class="lms-stats">
            <div class="lms-stat">
                <div class="lms-stat-icon" style="background:rgba(99,102,241,0.3);">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div>
                    <div class="lms-stat-val">
                        <?php echo $total_exams; ?>
                    </div>
                    <div class="lms-stat-label">Total Exams</div>
                </div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-icon" style="background:rgba(16,185,129,0.3);">
                    <i class="fas fa-question-circle"></i>
                </div>
                <div>
                    <div class="lms-stat-val">
                        <?php echo $total_questions; ?>
                    </div>
                    <div class="lms-stat-label">Questions</div>
                </div>
            </div>
            <div class="lms-stat">
                <div class="lms-stat-icon" style="background:rgba(245,158,11,0.3);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <div class="lms-stat-val">12</div>
                    <div class="lms-stat-label">Grades</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">

    <?php if ($grade == 0): ?>
        <!-- STEP 1: Select Grade -->
        <h3 class="lms-section-title"><i class="fas fa-th-large me-2" style="color:var(--lms-accent);"></i>Select Your Grade
        </h3>
        <div class="grade-grid">
            <?php for ($g = 1; $g <= 12; $g++):
                // Count exams per grade
                $exam_count = 0;
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lms_exams WHERE grade=? AND status='active'");
                    $stmt->execute([$g]);
                    $exam_count = $stmt->fetchColumn();
                } catch (Exception $e) {
                }
                ?>
                <a href="?grade=<?php echo $g; ?>" class="grade-card">
                    <div class="grade-num">
                        <?php echo $g; ?>
                    </div>
                    <div class="grade-label">Grade
                        <?php echo $g; ?>
                    </div>
                    <?php if ($exam_count > 0): ?>
                        <div style="margin-top:8px;">
                            <span
                                style="background:rgba(99,102,241,0.2);color:var(--lms-accent);font-size:.65rem;padding:3px 10px;border-radius:50px;font-weight:700;">
                                <?php echo $exam_count; ?> Exams
                            </span>
                        </div>
                    <?php endif; ?>
                </a>
            <?php endfor; ?>
        </div>

    <?php elseif (empty($subject)): ?>
        <!-- STEP 2: Select Subject -->
        <a href="?grade=0" class="back-link"><i class="fas fa-arrow-left"></i> Back to Grades</a>
        <h3 class="lms-section-title">
            <i class="fas fa-book me-2" style="color:var(--lms-accent);"></i>
            Grade
            <?php echo $grade; ?> — Choose Subject
        </h3>
        <div class="row g-3">
            <?php foreach ($subjects_by_grade[$grade] ?? [] as $subj):
                $icon = $subject_icons[$subj] ?? 'book';
                $color = $subject_colors[$subj] ?? '#1565C0';
                // Count exams for this subject
                $subj_exam_count = 0;
                try {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM lms_exams WHERE grade=? AND subject=? AND status='active'");
                    $stmt->execute([$grade, $subj]);
                    $subj_exam_count = $stmt->fetchColumn();
                } catch (Exception $e) {
                }
                ?>
                <div class="col-lg-4 col-md-6">
                    <a href="?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subj); ?>" class="subject-exam-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="subj-icon" style="background:<?php echo $color; ?>;">
                                <i class="fas fa-<?php echo $icon; ?>"></i>
                            </div>
                            <div class="flex-grow-1">
                                <h6>
                                    <?php echo $subj; ?>
                                </h6>
                                <p>
                                    <?php echo $subj_exam_count; ?> chapter exam
                                    <?php echo $subj_exam_count != 1 ? 's' : ''; ?> available
                                </p>
                            </div>
                            <i class="fas fa-chevron-right" style="color:var(--lms-muted);font-size:.8rem;"></i>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>

    <?php else: ?>
        <!-- STEP 3: Exam List for Subject -->
        <a href="?grade=<?php echo $grade; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Subjects</a>

        <?php $color = $subject_colors[$subject] ?? '#1565C0'; ?>
        <h3 class="lms-section-title">
            <span style="color:<?php echo $color; ?>;"><i
                    class="fas fa-<?php echo $subject_icons[$subject] ?? 'book'; ?> me-2"></i></span>
            Grade
            <?php echo $grade; ?> —
            <?php echo htmlspecialchars($subject); ?> Exams
        </h3>

        <?php if (!$user_id): ?>
            <div class="login-prompt">
                <p><i class="fas fa-lock me-2"></i>You need to log in to take exams and track your progress</p>
                <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login / Register</a>
            </div>
        <?php endif; ?>

        <?php if (empty($exams)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h4>No Exams Yet</h4>
                <p>Exams for Grade
                    <?php echo $grade; ?>
                    <?php echo htmlspecialchars($subject); ?> haven't been added yet.<br>Please ask the admin to run the exam
                    seeder.
                </p>
                <a href="education.php?grade=<?php echo $grade; ?>" class="btn-take-exam mt-3"><i
                        class="fas fa-book-reader"></i> Study Textbook First</a>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($exams as $exam):
                    $attempt = $user_attempts[$exam['id']] ?? null;
                    $passed = $attempt && $attempt['best_score'] >= $exam['pass_percentage'];
                    ?>
                    <div class="col-lg-6">
                        <div class="exam-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="exam-chapter"><i class="fas fa-bookmark"></i> Chapter
                                    <?php echo $exam['chapter']; ?>
                                </span>
                                <span class="difficulty-badge diff-<?php echo $exam['difficulty']; ?>">
                                    <?php echo $exam['difficulty']; ?>
                                </span>
                            </div>
                            <h5>
                                <?php echo htmlspecialchars($exam['chapter_title']); ?>
                            </h5>
                            <p class="exam-desc">
                                <?php echo htmlspecialchars($exam['description'] ?? 'Test your knowledge on this chapter'); ?>
                            </p>

                            <div class="exam-meta">
                                <div class="exam-meta-item"><i class="fas fa-question-circle" style="color:var(--lms-accent);"></i>
                                    <?php echo $exam['total_questions']; ?> Questions
                                </div>
                                <div class="exam-meta-item"><i class="fas fa-clock" style="color:var(--lms-warning);"></i>
                                    <?php echo $exam['duration_minutes']; ?> min
                                </div>
                                <div class="exam-meta-item"><i class="fas fa-trophy" style="color:var(--lms-success);"></i> Pass:
                                    <?php echo $exam['pass_percentage']; ?>%
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <?php if ($attempt): ?>
                                        <span class="score-badge <?php echo $passed ? 'score-pass' : 'score-fail'; ?>">
                                            <i class="fas fa-<?php echo $passed ? 'check-circle' : 'times-circle'; ?>"></i>
                                            Best:
                                            <?php echo number_format($attempt['best_score'], 0); ?>%
                                            (
                                            <?php echo $attempt['attempt_count']; ?> attempt
                                            <?php echo $attempt['attempt_count'] > 1 ? 's' : ''; ?>)
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-2">
                                    <?php if ($attempt): ?>
                                        <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn-take-exam btn-retake">
                                            <i class="fas fa-redo"></i> Retake
                                        </a>
                                    <?php endif; ?>
                                    <a href="take_exam.php?exam_id=<?php echo $exam['id']; ?>" class="btn-take-exam">
                                        <i class="fas fa-play"></i>
                                        <?php echo $attempt ? 'Take Again' : 'Start Exam'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>