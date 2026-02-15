<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireRole('admin');

// Ensure tables exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade INT NOT NULL, subject VARCHAR(100) NOT NULL,
        chapter INT NOT NULL DEFAULT 1, chapter_title VARCHAR(255) NOT NULL,
        title VARCHAR(255) NOT NULL, description TEXT,
        duration_minutes INT DEFAULT 30, pass_percentage INT DEFAULT 50,
        total_questions INT DEFAULT 10, difficulty ENUM('easy','medium','hard') DEFAULT 'medium',
        status ENUM('active','draft','archived') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $pdo->exec("CREATE TABLE IF NOT EXISTS lms_questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL, question_text TEXT NOT NULL,
        option_a VARCHAR(500) NOT NULL, option_b VARCHAR(500) NOT NULL,
        option_c VARCHAR(500) NOT NULL, option_d VARCHAR(500) NOT NULL,
        correct_answer CHAR(1) NOT NULL, explanation TEXT,
        points INT DEFAULT 1, sort_order INT DEFAULT 0,
        FOREIGN KEY (exam_id) REFERENCES lms_exams(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (Exception $e) {
}

// ============================
// HANDLE POST ACTIONS
// ============================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CREATE EXAM
    if ($action === 'create_exam') {
        $grade = (int) $_POST['grade'];
        $subject = sanitize($_POST['subject']);
        $chapter = (int) $_POST['chapter'];
        $chapter_title = sanitize($_POST['chapter_title']);
        $description = sanitize($_POST['description'] ?? '');
        $duration = (int) $_POST['duration_minutes'];
        $pass_pct = (int) $_POST['pass_percentage'];
        $difficulty = sanitize($_POST['difficulty']);
        $status = sanitize($_POST['status'] ?? 'active');
        $title = "Grade $grade $subject — Chapter $chapter: $chapter_title";

        $stmt = $pdo->prepare("INSERT INTO lms_exams (grade,subject,chapter,chapter_title,title,description,duration_minutes,pass_percentage,total_questions,difficulty,status) VALUES (?,?,?,?,?,?,?,?,0,?,?)");
        $stmt->execute([$grade, $subject, $chapter, $chapter_title, $title, $description, $duration, $pass_pct, $difficulty, $status]);
        redirectWithMessage('manage_lms.php?view=exam&id=' . $pdo->lastInsertId(), 'success', 'Exam created! Now add questions.');
    }

    // UPDATE EXAM
    if ($action === 'update_exam') {
        $id = (int) $_POST['exam_id'];
        $grade = (int) $_POST['grade'];
        $subject = sanitize($_POST['subject']);
        $chapter = (int) $_POST['chapter'];
        $chapter_title = sanitize($_POST['chapter_title']);
        $description = sanitize($_POST['description'] ?? '');
        $duration = (int) $_POST['duration_minutes'];
        $pass_pct = (int) $_POST['pass_percentage'];
        $difficulty = sanitize($_POST['difficulty']);
        $status = sanitize($_POST['status'] ?? 'active');
        $title = "Grade $grade $subject — Chapter $chapter: $chapter_title";

        $stmt = $pdo->prepare("UPDATE lms_exams SET grade=?,subject=?,chapter=?,chapter_title=?,title=?,description=?,duration_minutes=?,pass_percentage=?,difficulty=?,status=? WHERE id=?");
        $stmt->execute([$grade, $subject, $chapter, $chapter_title, $title, $description, $duration, $pass_pct, $difficulty, $status, $id]);
        redirectWithMessage('manage_lms.php?view=exam&id=' . $id, 'success', 'Exam updated successfully.');
    }

    // DELETE EXAM
    if ($action === 'delete_exam') {
        $id = (int) $_POST['exam_id'];
        $pdo->prepare("DELETE FROM lms_exams WHERE id=?")->execute([$id]);
        redirectWithMessage('manage_lms.php', 'success', 'Exam deleted.');
    }

    // ADD QUESTION
    if ($action === 'add_question') {
        $exam_id = (int) $_POST['exam_id'];
        $q = sanitize($_POST['question_text']);
        $a = sanitize($_POST['option_a']);
        $b = sanitize($_POST['option_b']);
        $c = sanitize($_POST['option_c']);
        $d = sanitize($_POST['option_d']);
        $correct = strtoupper(sanitize($_POST['correct_answer']));
        $explanation = sanitize($_POST['explanation'] ?? '');
        $sort = (int) ($_POST['sort_order'] ?? 0);

        $stmt = $pdo->prepare("INSERT INTO lms_questions (exam_id,question_text,option_a,option_b,option_c,option_d,correct_answer,explanation,sort_order) VALUES (?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$exam_id, $q, $a, $b, $c, $d, $correct, $explanation, $sort]);

        // Update total_questions
        $count = $pdo->prepare("SELECT COUNT(*) FROM lms_questions WHERE exam_id=?");
        $count->execute([$exam_id]);
        $pdo->prepare("UPDATE lms_exams SET total_questions=? WHERE id=?")->execute([$count->fetchColumn(), $exam_id]);

        redirectWithMessage('manage_lms.php?view=exam&id=' . $exam_id, 'success', 'Question added!');
    }

    // UPDATE QUESTION
    if ($action === 'update_question') {
        $qid = (int) $_POST['question_id'];
        $exam_id = (int) $_POST['exam_id'];
        $q = sanitize($_POST['question_text']);
        $a = sanitize($_POST['option_a']);
        $b = sanitize($_POST['option_b']);
        $c = sanitize($_POST['option_c']);
        $d = sanitize($_POST['option_d']);
        $correct = strtoupper(sanitize($_POST['correct_answer']));
        $explanation = sanitize($_POST['explanation'] ?? '');

        $stmt = $pdo->prepare("UPDATE lms_questions SET question_text=?,option_a=?,option_b=?,option_c=?,option_d=?,correct_answer=?,explanation=? WHERE id=?");
        $stmt->execute([$q, $a, $b, $c, $d, $correct, $explanation, $qid]);
        redirectWithMessage('manage_lms.php?view=exam&id=' . $exam_id, 'success', 'Question updated!');
    }

    // DELETE QUESTION
    if ($action === 'delete_question') {
        $qid = (int) $_POST['question_id'];
        $exam_id = (int) $_POST['exam_id'];
        $pdo->prepare("DELETE FROM lms_questions WHERE id=?")->execute([$qid]);
        $count = $pdo->prepare("SELECT COUNT(*) FROM lms_questions WHERE exam_id=?");
        $count->execute([$exam_id]);
        $pdo->prepare("UPDATE lms_exams SET total_questions=? WHERE id=?")->execute([$count->fetchColumn(), $exam_id]);
        redirectWithMessage('manage_lms.php?view=exam&id=' . $exam_id, 'success', 'Question deleted.');
    }
}

// ============================
// FETCH DATA
// ============================
$view = $_GET['view'] ?? 'list';
$filter_grade = (int) ($_GET['grade'] ?? 0);
$filter_subject = sanitize($_GET['subject'] ?? '');

// Stats
$total_exams = $pdo->query("SELECT COUNT(*) FROM lms_exams")->fetchColumn();
$total_questions = $pdo->query("SELECT COUNT(*) FROM lms_questions")->fetchColumn();
$total_attempts = 0;
try {
    $total_attempts = $pdo->query("SELECT COUNT(*) FROM lms_attempts WHERE status='completed'")->fetchColumn();
} catch (Exception $e) {
}

// All subjects
$all_subjects = [
    'Amharic',
    'English',
    'Mathematics',
    'Environmental Science',
    'Afan Oromo',
    'Social Studies',
    'Civics',
    'General Science',
    'Biology',
    'Physics',
    'Chemistry',
    'Geography',
    'History',
    'Economics',
    'ICT'
];

// Fetch exam for detail view
$exam = null;
$exam_questions = [];
if ($view === 'exam' && isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM lms_exams WHERE id=?");
    $stmt->execute([(int) $_GET['id']]);
    $exam = $stmt->fetch();
    if ($exam) {
        $stmt = $pdo->prepare("SELECT * FROM lms_questions WHERE exam_id=? ORDER BY sort_order, id");
        $stmt->execute([$exam['id']]);
        $exam_questions = $stmt->fetchAll();
    }
}

// Fetch exams list
$exams = [];
if ($view === 'list') {
    $sql = "SELECT e.*, (SELECT COUNT(*) FROM lms_questions q WHERE q.exam_id=e.id) as q_count FROM lms_exams e WHERE 1=1";
    $params = [];
    if ($filter_grade > 0) {
        $sql .= " AND e.grade=?";
        $params[] = $filter_grade;
    }
    if (!empty($filter_subject)) {
        $sql .= " AND e.subject=?";
        $params[] = $filter_subject;
    }
    $sql .= " ORDER BY e.grade, e.subject, e.chapter";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $exams = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage LMS - EthioServe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background: #f4f6f9;
            min-height: 100vh;
        }

        .stat-card {
            border-radius: 15px;
            transition: transform .3s;
        }

        .stat-card:hover {
            transform: translateY(-4px);
        }

        .question-row {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            transition: .2s;
        }

        .question-row:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .correct-badge {
            background: #d1fae5;
            color: #059669;
            padding: 2px 10px;
            border-radius: 50px;
            font-size: .75rem;
            font-weight: 700;
        }

        .option-preview {
            font-size: .82rem;
            color: #6b7280;
            padding: 2px 0;
        }

        .option-preview .letter {
            font-weight: 700;
            color: #374151;
            margin-right: 4px;
        }

        @media(max-width:768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-brain text-primary me-2"></i>LMS Management</h2>
                    <p class="text-muted mb-0">Create and manage exams, questions, and quiz content</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="seed_exams.php" class="btn btn-outline-primary rounded-pill px-3">
                        <i class="fas fa-database me-1"></i> Seed Exams
                    </a>
                    <?php if ($view !== 'list'): ?>
                        <a href="manage_lms.php" class="btn btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-arrow-left me-1"></i> All Exams
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-primary rounded-pill px-3" data-bs-toggle="modal"
                        data-bs-target="#createExamModal">
                        <i class="fas fa-plus me-1"></i> New Exam
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-4"
                        style="background:linear-gradient(135deg,#6366f1,#818cf8);color:#fff;">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Exams</p>
                        <h2 class="fw-bold">
                            <?php echo $total_exams; ?>
                        </h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-4"
                        style="background:linear-gradient(135deg,#10b981,#34d399);color:#fff;">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Questions</p>
                        <h2 class="fw-bold">
                            <?php echo $total_questions; ?>
                        </h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-4"
                        style="background:linear-gradient(135deg,#f59e0b,#fbbf24);color:#fff;">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Student Attempts</p>
                        <h2 class="fw-bold">
                            <?php echo $total_attempts; ?>
                        </h2>
                    </div>
                </div>
            </div>

            <?php if ($view === 'list'): ?>
                <!-- EXAM LIST VIEW -->
                <!-- Filters -->
                <div class="card border-0 shadow-sm p-3 mb-4">
                    <form method="GET" class="d-flex gap-2 flex-wrap align-items-center">
                        <select name="grade" class="form-select form-select-sm rounded-pill" style="width:auto;">
                            <option value="0">All Grades</option>
                            <?php for ($g = 1; $g <= 12; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo $filter_grade == $g ? 'selected' : ''; ?>>Grade
                                    <?php echo $g; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                        <select name="subject" class="form-select form-select-sm rounded-pill" style="width:auto;">
                            <option value="">All Subjects</option>
                            <?php foreach ($all_subjects as $s): ?>
                                <option value="<?php echo $s; ?>" <?php echo $filter_subject === $s ? 'selected' : ''; ?>>
                                    <?php echo $s; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <button class="btn btn-sm btn-primary rounded-pill px-3"><i class="fas fa-filter me-1"></i>
                            Filter</button>
                        <a href="manage_lms.php" class="btn btn-sm btn-light rounded-pill px-3">Clear</a>
                    </form>
                </div>

                <!-- Exams Table -->
                <div class="card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4">Exam</th>
                                    <th class="border-0">Grade</th>
                                    <th class="border-0">Subject</th>
                                    <th class="border-0">Chapter</th>
                                    <th class="border-0">Questions</th>
                                    <th class="border-0">Difficulty</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($exams)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">
                                            <i class="fas fa-clipboard-list fs-1 d-block mb-3 opacity-25"></i>
                                            No exams found. Create one or run the seeder!
                                        </td>
                                    </tr>
                                <?php else:
                                    foreach ($exams as $e): ?>
                                        <tr>
                                            <td class="px-4">
                                                <a href="?view=exam&id=<?php echo $e['id']; ?>"
                                                    class="fw-bold text-decoration-none">
                                                    <?php echo htmlspecialchars($e['chapter_title']); ?>
                                                </a>
                                            </td>
                                            <td><span class="badge bg-primary rounded-pill">
                                                    <?php echo $e['grade']; ?>
                                                </span></td>
                                            <td>
                                                <?php echo htmlspecialchars($e['subject']); ?>
                                            </td>
                                            <td>Ch.
                                                <?php echo $e['chapter']; ?>
                                            </td>
                                            <td>
                                                <span
                                                    class="fw-bold <?php echo $e['q_count'] > 0 ? 'text-success' : 'text-danger'; ?>">
                                                    <?php echo $e['q_count']; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo $e['difficulty'] === 'easy' ? 'success' : ($e['difficulty'] === 'hard' ? 'danger' : 'warning text-dark'); ?> rounded-pill">
                                                    <?php echo ucfirst($e['difficulty']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span
                                                    class="badge bg-<?php echo $e['status'] === 'active' ? 'success' : ($e['status'] === 'draft' ? 'secondary' : 'danger'); ?> rounded-pill">
                                                    <?php echo ucfirst($e['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?view=exam&id=<?php echo $e['id']; ?>"
                                                    class="btn btn-sm btn-outline-primary rounded-pill px-2" title="Manage">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <form method="POST" style="display:inline;"
                                                    onsubmit="return confirm('Delete this exam and all its questions?');">
                                                    <input type="hidden" name="action" value="delete_exam">
                                                    <input type="hidden" name="exam_id" value="<?php echo $e['id']; ?>">
                                                    <button class="btn btn-sm btn-outline-danger rounded-pill px-2"
                                                        title="Delete"><i class="fas fa-trash"></i></button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($view === 'exam' && $exam): ?>
                <!-- EXAM DETAIL VIEW -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <span class="badge bg-primary rounded-pill mb-2">Grade
                                <?php echo $exam['grade']; ?>
                            </span>
                            <span class="badge bg-info rounded-pill mb-2">
                                <?php echo htmlspecialchars($exam['subject']); ?>
                            </span>
                            <span
                                class="badge bg-<?php echo $exam['difficulty'] === 'easy' ? 'success' : ($exam['difficulty'] === 'hard' ? 'danger' : 'warning text-dark'); ?> rounded-pill mb-2">
                                <?php echo ucfirst($exam['difficulty']); ?>
                            </span>
                            <span
                                class="badge bg-<?php echo $exam['status'] === 'active' ? 'success' : 'secondary'; ?> rounded-pill mb-2">
                                <?php echo ucfirst($exam['status']); ?>
                            </span>
                            <h4 class="fw-bold mt-1">
                                <?php echo htmlspecialchars($exam['chapter_title']); ?>
                            </h4>
                            <p class="text-muted mb-0">
                                <?php echo htmlspecialchars($exam['description'] ?? ''); ?>
                            </p>
                            <div class="mt-2 d-flex gap-3 text-muted small">
                                <span><i class="fas fa-clock me-1"></i>
                                    <?php echo $exam['duration_minutes']; ?> min
                                </span>
                                <span><i class="fas fa-trophy me-1"></i>Pass:
                                    <?php echo $exam['pass_percentage']; ?>%
                                </span>
                                <span><i class="fas fa-question-circle me-1"></i>
                                    <?php echo count($exam_questions); ?> questions
                                </span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <button class="btn btn-outline-primary rounded-pill px-3" data-bs-toggle="modal"
                                data-bs-target="#editExamModal">
                                <i class="fas fa-edit me-1"></i> Edit Exam
                            </button>
                            <a href="../customer/lms.php?grade=<?php echo $exam['grade']; ?>&subject=<?php echo urlencode($exam['subject']); ?>"
                                class="btn btn-outline-info rounded-pill px-3" target="_blank">
                                <i class="fas fa-eye me-1"></i> Preview
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Add Question Form -->
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-plus-circle text-success me-2"></i>Add New Question</h5>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_question">
                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-bold small">Question Text *</label>
                            <textarea name="question_text" class="form-control" rows="2" required
                                placeholder="Enter the question..."></textarea>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">A)</label>
                                <input type="text" name="option_a" class="form-control" required placeholder="Option A">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">B)</label>
                                <input type="text" name="option_b" class="form-control" required placeholder="Option B">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">C)</label>
                                <input type="text" name="option_c" class="form-control" required placeholder="Option C">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">D)</label>
                                <input type="text" name="option_d" class="form-control" required placeholder="Option D">
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Correct Answer *</label>
                                <select name="correct_answer" class="form-select" required>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Sort Order</label>
                                <input type="number" name="sort_order" class="form-control"
                                    value="<?php echo count($exam_questions) + 1; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Explanation (Optional)</label>
                                <input type="text" name="explanation" class="form-control"
                                    placeholder="Why this is correct...">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success rounded-pill px-4"><i class="fas fa-plus me-1"></i> Add
                            Question</button>
                    </form>
                </div>

                <!-- Questions List -->
                <h5 class="fw-bold mb-3"><i class="fas fa-list-ol text-primary me-2"></i>Questions (
                    <?php echo count($exam_questions); ?>)
                </h5>
                <?php if (empty($exam_questions)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-inbox fs-1 d-block mb-3 opacity-25"></i>No questions yet. Add one above!
                    </div>
                <?php else:
                    foreach ($exam_questions as $idx => $q): ?>
                        <div class="question-row">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-2">
                                        <span class="badge bg-primary rounded-pill">#
                                            <?php echo $idx + 1; ?>
                                        </span>
                                        <span class="correct-badge"><i class="fas fa-check me-1"></i>Answer:
                                            <?php echo $q['correct_answer']; ?>
                                        </span>
                                    </div>
                                    <p class="fw-bold mb-2">
                                        <?php echo htmlspecialchars($q['question_text']); ?>
                                    </p>
                                    <div class="row">
                                        <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $l => $t): ?>
                                            <div
                                                class="col-md-6 option-preview <?php echo $l === $q['correct_answer'] ? 'fw-bold text-success' : ''; ?>">
                                                <span class="letter">
                                                    <?php echo $l; ?>)
                                                </span>
                                                <?php if ($l === $q['correct_answer']): ?><i class="fas fa-check-circle me-1"></i>
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($t); ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php if (!empty($q['explanation'])): ?>
                                        <p class="mt-2 mb-0 small text-info"><i class="fas fa-lightbulb me-1"></i>
                                            <?php echo htmlspecialchars($q['explanation']); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                                <div class="d-flex gap-1 ms-3">
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-2" data-bs-toggle="modal"
                                        data-bs-target="#editQ<?php echo $q['id']; ?>"><i class="fas fa-edit"></i></button>
                                    <form method="POST" onsubmit="return confirm('Delete this question?');" style="display:inline;">
                                        <input type="hidden" name="action" value="delete_question">
                                        <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                        <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                        <button class="btn btn-sm btn-outline-danger rounded-pill px-2"><i
                                                class="fas fa-trash"></i></button>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Edit Question Modal -->
                        <div class="modal fade" id="editQ<?php echo $q['id']; ?>" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <form method="POST">
                                        <div class="modal-header">
                                            <h5 class="modal-title fw-bold">Edit Question #
                                                <?php echo $idx + 1; ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="action" value="update_question">
                                            <input type="hidden" name="question_id" value="<?php echo $q['id']; ?>">
                                            <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small">Question Text</label>
                                                <textarea name="question_text" class="form-control" rows="2"
                                                    required><?php echo htmlspecialchars($q['question_text']); ?></textarea>
                                            </div>
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-6"><label class="form-label small fw-bold">A)</label>
                                                    <input type="text" name="option_a" class="form-control" required
                                                        value="<?php echo htmlspecialchars($q['option_a']); ?>">
                                                </div>
                                                <div class="col-md-6"><label class="form-label small fw-bold">B)</label>
                                                    <input type="text" name="option_b" class="form-control" required
                                                        value="<?php echo htmlspecialchars($q['option_b']); ?>">
                                                </div>
                                                <div class="col-md-6"><label class="form-label small fw-bold">C)</label>
                                                    <input type="text" name="option_c" class="form-control" required
                                                        value="<?php echo htmlspecialchars($q['option_c']); ?>">
                                                </div>
                                                <div class="col-md-6"><label class="form-label small fw-bold">D)</label>
                                                    <input type="text" name="option_d" class="form-control" required
                                                        value="<?php echo htmlspecialchars($q['option_d']); ?>">
                                                </div>
                                            </div>
                                            <div class="row g-3">
                                                <div class="col-md-4"><label class="form-label small fw-bold">Correct Answer</label>
                                                    <select name="correct_answer" class="form-select">
                                                        <?php foreach (['A', 'B', 'C', 'D'] as $l): ?>
                                                            <option value="<?php echo $l; ?>" <?php echo $q['correct_answer'] === $l ? 'selected' : ''; ?>>
                                                                <?php echo $l; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                                <div class="col-md-8"><label class="form-label small fw-bold">Explanation</label>
                                                    <input type="text" name="explanation" class="form-control"
                                                        value="<?php echo htmlspecialchars($q['explanation'] ?? ''); ?>">
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary rounded-pill"
                                                data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" class="btn btn-primary rounded-pill"><i
                                                    class="fas fa-save me-1"></i> Save Changes</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>

                <!-- Edit Exam Modal -->
                <div class="modal fade" id="editExamModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <form method="POST">
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Edit Exam</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <input type="hidden" name="action" value="update_exam">
                                    <input type="hidden" name="exam_id" value="<?php echo $exam['id']; ?>">
                                    <div class="row g-3">
                                        <div class="col-md-4"><label class="form-label small fw-bold">Grade</label>
                                            <select name="grade" class="form-select">
                                                <?php for ($g = 1; $g <= 12; $g++): ?>
                                                    <option value="<?php echo $g; ?>" <?php echo $exam['grade'] == $g ? 'selected' : ''; ?>>Grade
                                                        <?php echo $g; ?>
                                                    </option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Subject</label>
                                            <select name="subject" class="form-select">
                                                <?php foreach ($all_subjects as $s): ?>
                                                    <option value="<?php echo $s; ?>" <?php echo $exam['subject'] === $s ? 'selected' : ''; ?>>
                                                        <?php echo $s; ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Chapter #</label>
                                            <input type="number" name="chapter" class="form-control"
                                                value="<?php echo $exam['chapter']; ?>" min="1">
                                        </div>
                                        <div class="col-12"><label class="form-label small fw-bold">Chapter Title</label>
                                            <input type="text" name="chapter_title" class="form-control"
                                                value="<?php echo htmlspecialchars($exam['chapter_title']); ?>" required>
                                        </div>
                                        <div class="col-12"><label class="form-label small fw-bold">Description</label>
                                            <textarea name="description" class="form-control"
                                                rows="2"><?php echo htmlspecialchars($exam['description'] ?? ''); ?></textarea>
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Duration (min)</label>
                                            <input type="number" name="duration_minutes" class="form-control"
                                                value="<?php echo $exam['duration_minutes']; ?>">
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Pass %</label>
                                            <input type="number" name="pass_percentage" class="form-control"
                                                value="<?php echo $exam['pass_percentage']; ?>">
                                        </div>
                                        <div class="col-md-4"><label class="form-label small fw-bold">Difficulty</label>
                                            <select name="difficulty" class="form-select">
                                                <?php foreach (['easy', 'medium', 'hard'] as $d): ?>
                                                    <option value="<?php echo $d; ?>" <?php echo $exam['difficulty'] === $d ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($d); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                        <div class="col-12"><label class="form-label small fw-bold">Status</label>
                                            <select name="status" class="form-select">
                                                <?php foreach (['active', 'draft', 'archived'] as $st): ?>
                                                    <option value="<?php echo $st; ?>" <?php echo $exam['status'] === $st ? 'selected' : ''; ?>>
                                                        <?php echo ucfirst($st); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary rounded-pill"
                                        data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary rounded-pill"><i
                                            class="fas fa-save me-1"></i> Update Exam</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'create'): ?>
                <!-- Show create form inline if preferred -->
            <?php endif; ?>

        </div><!-- main-content -->
    </div>

    <!-- Create Exam Modal -->
    <div class="modal fade" id="createExamModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle text-primary me-2"></i>Create New
                            Exam</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="action" value="create_exam">
                        <div class="row g-3">
                            <div class="col-md-4"><label class="form-label small fw-bold">Grade *</label>
                                <select name="grade" class="form-select" required>
                                    <?php for ($g = 1; $g <= 12; $g++): ?>
                                        <option value="<?php echo $g; ?>">Grade
                                            <?php echo $g; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Subject *</label>
                                <select name="subject" class="form-select" required>
                                    <?php foreach ($all_subjects as $s): ?>
                                        <option value="<?php echo $s; ?>">
                                            <?php echo $s; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Chapter #</label>
                                <input type="number" name="chapter" class="form-control" value="1" min="1" required>
                            </div>
                            <div class="col-12"><label class="form-label small fw-bold">Chapter Title *</label>
                                <input type="text" name="chapter_title" class="form-control"
                                    placeholder="e.g. Numbers 1-20" required>
                            </div>
                            <div class="col-12"><label class="form-label small fw-bold">Description</label>
                                <textarea name="description" class="form-control" rows="2"
                                    placeholder="What this exam covers..."></textarea>
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Duration (min)</label>
                                <input type="number" name="duration_minutes" class="form-control" value="30">
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Pass %</label>
                                <input type="number" name="pass_percentage" class="form-control" value="50">
                            </div>
                            <div class="col-md-4"><label class="form-label small fw-bold">Difficulty</label>
                                <select name="difficulty" class="form-select">
                                    <option value="easy">Easy</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="hard">Hard</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label small fw-bold">Status</label>
                                <select name="status" class="form-select">
                                    <option value="active" selected>Active</option>
                                    <option value="draft">Draft</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary rounded-pill"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill"><i class="fas fa-plus me-1"></i>
                            Create Exam</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>