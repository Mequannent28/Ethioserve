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
} catch (Exception $e) {
    // Tables may already exist
}

$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$subject = sanitize($_GET['subject'] ?? '');
$view_type = sanitize($_GET['view'] ?? 'list');
$chapter_id = isset($_GET['chapter_id']) ? (int) $_GET['chapter_id'] : 0;

// Get user info if logged in
$user_id = $_SESSION['user_id'] ?? null;
$user_grade = 0;
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

if ($user_id) {
    try {
        // First check users table for grade and role
        $stmt = $pdo->prepare("SELECT grade, role FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $u = $stmt->fetch();
        $user_grade = (int) ($u['grade'] ?? 0);
        $user_role = $u['role'] ?? '';

        // If grade is not set in users table and role is student, check student profile
        if ($user_grade === 0 && $user_role === 'student') {
            $stmt = $pdo->prepare("SELECT c.class_name, p.class_id FROM sms_student_profiles p 
                                 JOIN sms_classes c ON p.class_id = c.id 
                                 WHERE p.user_id = ?");
            $stmt->execute([$user_id]);
            $profile_data = $stmt->fetch();
            if ($profile_data) {
                $class_id = (int)$profile_data['class_id'];
                $className = $profile_data['class_name'];
                // Extract grade number from class name (e.g. "Grade 10" or "Grdae 10")
                if (preg_match('/\d+/', $className, $matches)) {
                    $user_grade = (int)$matches[0];
                }
            }
        }
    } catch (Exception $e) {}
}

if ($user_grade > 0) {
    $grade = $user_grade; // Force override
}
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
    'ICT' => '#387da0ff'
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

// Fetch Chapters for selected grade + subject
$chapters = [];
$reading_progress = [];
if ($grade > 0 && !empty($subject)) {
    $query = "SELECT * FROM lms_chapters WHERE grade = ? AND subject = ? ";
    $params = [$grade, $subject];
    if ($class_id) {
        $query .= " AND (class_id = ? OR class_id IS NULL) ";
        $params[] = $class_id;
    }
    $query .= " ORDER BY chapter_number ASC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $chapters = $stmt->fetchAll();

    if ($user_id && !empty($chapters)) {
        $stmt = $pdo->prepare("SELECT chapter_id FROM lms_reading_progress WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $reading_progress = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}

// Fetch single chapter if viewing
$target_chapter = null;
if ($view_type === 'chapter' && $chapter_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM lms_chapters WHERE id = ?");
    $stmt->execute([$chapter_id]);
    $target_chapter = $stmt->fetch();
    
    // Mark as read if user visits
    if ($target_chapter && $user_id) {
        try {
            $pdo->prepare("INSERT IGNORE INTO lms_reading_progress (user_id, chapter_id) VALUES (?,?)")->execute([$user_id, $chapter_id]);
        } catch (Exception $e) {}
    }
}

// Fetch exams for selected grade + subject
$exams = [];
$user_attempts = [];
if ($grade > 0 && !empty($subject)) {
    $query = "SELECT * FROM lms_exams WHERE grade = ? AND subject = ? AND status = 'active' ";
    $params = [$grade, $subject];
    if ($class_id) {
        $query .= " AND (class_id = ? OR class_id IS NULL) ";
        $params[] = $class_id;
    }
    $query .= " ORDER BY chapter ASC";

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $exams = $stmt->fetchAll();

    // Map exams to chapters
    $chapter_exams = [];
    foreach($exams as $e) {
        if ($e['chapter_id']) $chapter_exams[$e['chapter_id']] = $e;
        // Also map by chapter number if id not linked
        else $chapter_exams['num_' . $e['chapter']] = $e;
    }

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
if (isset($_GET['iframe'])) {
    echo '<style> header, nav, .navbar, footer, .site-footer { display: none !important; } body { padding-top: 0 !important; } </style>';
    echo "<script>document.addEventListener('DOMContentLoaded', function() { document.querySelectorAll('a').forEach(a => { if(a.href && !a.href.startsWith('javascript:') && !a.href.startsWith('#')) { a.href += (a.href.includes('?') ? '&' : '?') + 'iframe=1'; } }); });</script>";
}
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
            <?php if ($grade > 0): ?>
                <span style="color:rgba(255,255,255,0.4);">/</span>
                <a href="lms.php?grade=<?php echo $grade; ?>" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:.85rem;">
                    Grade <?php echo $grade; ?>
                </a>
            <?php endif; ?>
            <?php if (!empty($subject)): ?>
                <span style="color:rgba(255,255,255,0.4);">/</span>
                <a href="lms.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subject); ?>" style="color:rgba(255,255,255,0.7);text-decoration:none;font-size:.85rem;">
                    <?php echo htmlspecialchars($subject); ?>
                </a>
            <?php endif; ?>
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
                <a href="<?php echo isLoggedIn() ? 'set_grade.php?redirect=lms&grade=' . $g : '?grade=' . $g; ?>"
                    class="grade-card">
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
        <?php if ($user_grade == 0): ?>
            <a href="?grade=0" class="back-link"><i class="fas fa-arrow-left"></i> Back to Grades</a>
        <?php endif; ?>
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

    <?php elseif ($view_type === 'chapter' && $target_chapter): ?>
        <!-- CHAPTER CONTENT VIEW -->
        <a href="?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subject); ?>" class="back-link">
            <i class="fas fa-arrow-left"></i> Back to Chapters
        </a>

        <div class="card border-0 shadow-sm p-0 mb-4 overflow-hidden" style="background:var(--lms-card);">
            <?php if (!empty($target_chapter['video_url'])): ?>
                <div class="ratio ratio-16x9 bg-dark">
                    <iframe src="<?php echo str_replace('watch?v=', 'embed/', $target_chapter['video_url']); ?>" allowfullscreen></iframe>
                </div>
            <?php elseif (!empty($target_chapter['local_video_path'])): ?>
                <div class="ratio ratio-16x9 bg-dark">
                    <video controls class="w-100 h-100">
                        <source src="../uploads/lms/videos/<?php echo $target_chapter['local_video_path']; ?>" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
            <?php endif; ?>
            <div class="p-4 p-md-5">
                <span class="exam-chapter mb-3">Chapter <?php echo $target_chapter['chapter_number']; ?></span>
                <h1 class="text-white fw-bold mb-4"><?php echo htmlspecialchars($target_chapter['title']); ?></h1>
                
                <div class="chapter-html-content text-white opacity-90" style="line-height:1.8; font-size:1.1rem;">
                    <?php echo $target_chapter['content']; // Trusted content from admin ?>
                </div>

                <?php if (!empty($target_chapter['document_path'])): ?>
                    <div class="mt-4 p-3 rounded-3" style="background:rgba(255,255,255,0.05); border:1px dashed rgba(255,255,255,0.1);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-3 bg-primary bg-opacity-10 p-3 text-primary">
                                    <i class="fas fa-file-pdf fs-4"></i>
                                </div>
                                <div>
                                    <h6 class="text-white mb-1">Download Study Material</h6>
                                    <p class="text-muted small mb-0">PDF/Document provided by instructor</p>
                                </div>
                            </div>
                            <a href="../uploads/lms/docs/<?php echo $target_chapter['document_path']; ?>" 
                               target="_blank" 
                               class="btn btn-sm btn-outline-light rounded-pill px-4">
                               <i class="fas fa-download me-2"></i>Download
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <hr class="my-5 border-secondary opacity-25">

                <!-- Associated Exam -->
                <?php 
                $this_exam = $chapter_exams[$target_chapter['id']] ?? ($chapter_exams['num_' . $target_chapter['chapter_number']] ?? null);
                if ($this_exam): 
                    $attempt = $user_attempts[$this_exam['id']] ?? null;
                    $passed = $attempt && $attempt['best_score'] >= $this_exam['pass_percentage'];
                ?>
                    <div class="p-4 rounded-4" style="background:rgba(99,102,241,0.1); border:1px solid rgba(99,102,241,0.2);">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h4 class="text-white fw-bold mb-2">Chapter Assessment</h4>
                                <p class="text-muted mb-md-0">Did you finish reading? Test your knowledge with the chapter exam.</p>
                            </div>
                            <div class="col-md-4 text-md-end">
                                <?php if (!$user_id): ?>
                                    <a href="../login.php" class="btn-take-exam">Login to Take Exam</a>
                                <?php elseif ($passed): ?>
                                    <div class="d-flex flex-column align-items-md-end gap-2">
                                        <span class="score-badge score-pass"><i class="fas fa-check-circle me-1"></i>Passed: <?php echo $attempt['best_score']; ?>%</span>
                                        <a href="take_exam.php?id=<?php echo $this_exam['id']; ?>" class="btn-take-exam btn-retake">Retake Exam</a>
                                    </div>
                                <?php else: ?>
                                    <a href="take_exam.php?id=<?php echo $this_exam['id']; ?>" class="btn-take-exam">
                                        <?php echo $attempt ? 'Retake Exam' : 'Start Exam'; ?>
                                        <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php else: ?>
        <!-- STEP 3: Chapter List for Subject -->
        <a href="?grade=<?php echo $grade; ?>" class="back-link"><i class="fas fa-arrow-left"></i> Back to Subjects</a>

        <?php $color = $subject_colors[$subject] ?? '#1565C0'; ?>
        <h3 class="lms-section-title">
            <span style="color:<?php echo $color; ?>;"><i class="fas fa-<?php echo $subject_icons[$subject] ?? 'book'; ?> me-2"></i></span>
            Grade <?php echo $grade; ?> — <?php echo htmlspecialchars($subject); ?> Courses
        </h3>

        <?php if (!$user_id): ?>
            <div class="login-prompt">
                <p><i class="fas fa-lock me-2"></i>You need to log in to track your reading progress and take exams</p>
                <a href="../login.php"><i class="fas fa-sign-in-alt"></i> Login / Register</a>
            </div>
        <?php endif; ?>

        <?php if (empty($chapters) && empty($exams)): ?>
            <div class="empty-state">
                <i class="fas fa-clipboard-list"></i>
                <h4>No Content Yet</h4>
                <p>Courses for Grade <?php echo $grade; ?> <?php echo htmlspecialchars($subject); ?> haven't been added yet.</p>
                <a href="education.php?grade=<?php echo $grade; ?>" class="btn-take-exam mt-3"><i class="fas fa-book-reader"></i> Study Textbook First</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php 
                // We show Chapters primarily if they exist, otherwise show standalone exams
                if (!empty($chapters)):
                    foreach ($chapters as $ch):
                        $is_read = in_array($ch['id'], $reading_progress);
                        $this_exam = $chapter_exams[$ch['id']] ?? ($chapter_exams['num_' . $ch['chapter_number']] ?? null);
                        $attempt = $this_exam ? ($user_attempts[$this_exam['id']] ?? null) : null;
                        $passed = $attempt && $attempt['best_score'] >= $this_exam['pass_percentage'];
                ?>
                    <div class="col-lg-6">
                        <div class="exam-card <?php echo $is_read ? 'border-success' : ''; ?>" style="height:100%; display:flex; flex-direction:column;">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="exam-chapter"><i class="fas fa-bookmark"></i> Chapter <?php echo $ch['chapter_number']; ?></span>
                                <?php if ($is_read): ?>
                                    <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-bold" style="font-size:.65rem;">
                                        <i class="fas fa-check-circle me-1"></i> COMPLETED
                                    </span>
                                <?php endif; ?>
                            </div>
                            <h5 class="text-white fw-bold"><?php echo htmlspecialchars($ch['title']); ?></h5>
                            <p class="exam-desc flex-grow-1">Learn about the key concepts of chapter <?php echo $ch['chapter_number']; ?> for <?php echo htmlspecialchars($subject); ?>.</p>
                            
                            <div class="d-flex align-items-center justify-content-between mt-auto pt-3 border-top border-secondary border-opacity-25">
                                <a href="?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subject); ?>&view=chapter&chapter_id=<?php echo $ch['id']; ?>" class="btn-take-exam btn-retake">
                                    <i class="fas fa-book-open me-2"></i> Read Content
                                </a>
                                <?php if ($this_exam): ?>
                                    <?php if ($passed): ?>
                                        <span class="score-badge score-pass"><i class="fas fa-check me-1"></i><?php echo $attempt['best_score']; ?>%</span>
                                    <?php else: ?>
                                        <a href="take_exam.php?id=<?php echo $this_exam['id']; ?>" class="btn-take-exam py-2">
                                            <i class="fas fa-pencil-alt me-2"></i> Take Exam
                                        </a>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; 
                else: // Fallback to old exam-only view if no chapters
                    foreach ($exams as $exam):
                        $attempt = $user_attempts[$exam['id']] ?? null;
                        $passed = $attempt && $attempt['best_score'] >= $exam['pass_percentage'];
                ?>
                    <div class="col-lg-6">
                        <div class="exam-card">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="exam-chapter"><i class="fas fa-bookmark"></i> Chapter <?php echo $exam['chapter']; ?></span>
                                <span class="difficulty-badge diff-<?php echo $exam['difficulty']; ?>"><?php echo $exam['difficulty']; ?></span>
                            </div>
                            <h5><?php echo htmlspecialchars($exam['chapter_title']); ?></h5>
                            <p class="exam-desc"><?php echo htmlspecialchars($exam['description'] ?? 'Test your knowledge on this chapter'); ?></p>
                            <div class="exam-meta">
                                <div class="exam-meta-item"><i class="fas fa-question-circle"></i> <?php echo $exam['total_questions']; ?> Qs</div>
                                <div class="exam-meta-item"><i class="fas fa-clock"></i> <?php echo $exam['duration_minutes']; ?> Min</div>
                                <div class="exam-meta-item"><i class="fas fa-trophy"></i> Pass: <?php echo $exam['pass_percentage']; ?>%</div>
                            </div>
                            <div class="d-flex align-items-center justify-content-between border-top border-secondary border-opacity-10 pt-3 mt-3">
                                <?php if ($passed): ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="score-badge score-pass"><i class="fas fa-check-circle"></i> Passed (<?php echo $attempt['best_score']; ?>%)</span>
                                        <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn-take-exam btn-retake">Retake</a>
                                    </div>
                                <?php elseif ($attempt): ?>
                                    <div class="d-flex align-items-center gap-3">
                                        <span class="score-badge score-fail"><i class="fas fa-times-circle"></i> Failed (<?php echo $attempt['best_score']; ?>%)</span>
                                        <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn-take-exam">Retake</a>
                                    </div>
                                <?php else: ?>
                                    <a href="take_exam.php?id=<?php echo $exam['id']; ?>" class="btn-take-exam">Take Exam <i class="fas fa-arrow-right ms-2"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>