<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('teacher');

$teacher_user_id = getCurrentUserId();
$teacher_stmt = $pdo->prepare("SELECT id FROM sms_teachers WHERE user_id = ?");
$teacher_stmt->execute([$teacher_user_id]);
$teacher_id = $teacher_stmt->fetchColumn();

// ── ACTION HANDLERS ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Create Exam
    if ($action === 'create_exam') {
        $title    = sanitize($_POST['title']);
        $desc     = sanitize($_POST['description']);
        $duration = (int)$_POST['duration_minutes'];
        $pass_pct = (int)$_POST['pass_percentage'];
        $diff     = sanitize($_POST['difficulty']);
        $class_id = (int)$_POST['class_id'];
        
        // Get subject name and grade from class assignment
        $stmt = $pdo->prepare("SELECT s.subject_name, c.class_name FROM sms_class_subjects cs JOIN sms_subjects s ON cs.subject_id = s.id JOIN sms_classes c ON cs.class_id = c.id WHERE cs.class_id = ? AND cs.teacher_id = ?");
        $stmt->execute([$class_id, $teacher_id]);
        $cls_data = $stmt->fetch();
        $subject = $cls_data['subject_name'] ?? 'General';
        
        preg_match('/\d+/', ($cls_data['class_name'] ?? ''), $matches);
        $grade = isset($matches[0]) ? (int)$matches[0] : (int)$_POST['grade'];

        $stmt = $pdo->prepare("INSERT INTO lms_exams (grade, subject, chapter, chapter_title, title, description, duration_minutes, pass_percentage, difficulty, class_id, teacher_id) VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$grade, $subject, $title, $title, $desc, $duration, $pass_pct, $diff, $class_id, $teacher_id]);
        $exam_id = $pdo->lastInsertId();

        setFlashMessage('Exam created! Now add questions.', 'success');
        header("Location: exams.php?view=questions&eid=$exam_id");
        exit;
    }

    // Add Question
    if ($action === 'add_question') {
        $eid    = (int)$_POST['exam_id'];
        $qtext  = sanitize($_POST['question_text']);
        $opt_a  = sanitize($_POST['option_a']);
        $opt_b  = sanitize($_POST['option_b']);
        $opt_c  = sanitize($_POST['option_c']);
        $opt_d  = sanitize($_POST['option_d']);
        $correct= sanitize($_POST['correct_answer']);
        $explan = sanitize($_POST['explanation'] ?? '');
        $points = (int)$_POST['points'];

        $stmt = $pdo->prepare("INSERT INTO lms_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$eid, $qtext, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $explan, $points]);

        // Update total questions count
        $count = $pdo->query("SELECT COUNT(*) FROM lms_questions WHERE exam_id = $eid")->fetchColumn();
        $pdo->prepare("UPDATE lms_exams SET total_questions = ? WHERE id = ?")->execute([$count, $eid]);

        setFlashMessage('Question added!', 'success');
        header("Location: exams.php?view=questions&eid=$eid");
        exit;
    }

    // Delete Question
    if ($action === 'delete_question') {
        $qid = (int)$_POST['question_id'];
        $eid = (int)$_POST['exam_id'];
        $pdo->prepare("DELETE FROM lms_questions WHERE id = ?")->execute([$qid]);
        $count = $pdo->query("SELECT COUNT(*) FROM lms_questions WHERE exam_id = $eid")->fetchColumn();
        $pdo->prepare("UPDATE lms_exams SET total_questions = ? WHERE id = ?")->execute([$count, $eid]);
        header("Location: exams.php?view=questions&eid=$eid");
        exit;
    }

    // Toggle Exam Status
    if ($action === 'toggle_status') {
        $eid = (int)$_POST['exam_id'];
        $pdo->prepare("UPDATE lms_exams SET status = IF(status='active','draft','active') WHERE id = ?")->execute([$eid]);
        header("Location: exams.php");
        exit;
    }

    // Delete exam
    if ($action === 'delete_exam') {
        $pdo->prepare("DELETE FROM lms_exams WHERE id = ?")->execute([(int)$_POST['exam_id']]);
        header("Location: exams.php");
        exit;
    }

    // Bulk Add Questions
    if ($action === 'bulk_add_questions') {
        $eid = (int)$_POST['exam_id'];
        $batch_text = $_POST['bulk_text'];
        
        // Split by double newline or similar to get individual question blocks
        $blocks = preg_split('/\r\n\r\n|\n\n/', $batch_text);
        $added_count = 0;

        foreach ($blocks as $block) {
            $lines = explode("\n", trim($block));
            if (count($lines) < 3) continue; // Skip too small blocks

            $qtext = ''; $opt_a = ''; $opt_b = ''; $opt_c = ''; $opt_d = ''; 
            $correct = ''; $explanation = ''; $points = 1;

            foreach ($lines as $line) {
                $line = trim($line);
                if (empty($line)) continue;

                if (preg_match('/^Q[:\.\d\s]+(.*)/i', $line, $matches)) {
                    $qtext = trim($matches[1]);
                } elseif (preg_match('/^[A][\)\.\:\s]+(.*)/i', $line, $matches)) {
                    $opt_a = trim($matches[1]);
                } elseif (preg_match('/^[B][\)\.\:\s]+(.*)/i', $line, $matches)) {
                    $opt_b = trim($matches[1]);
                } elseif (preg_match('/^[C][\)\.\:\s]+(.*)/i', $line, $matches)) {
                    $opt_c = trim($matches[1]);
                } elseif (preg_match('/^[D][\)\.\:\s]+(.*)/i', $line, $matches)) {
                    $opt_d = trim($matches[1]);
                } elseif (preg_match('/^(Ans|Correct|Answer)[\s:\.]+(.*)/i', $line, $matches)) {
                    $correct = strtoupper(substr(trim($matches[2]), 0, 1));
                } elseif (preg_match('/^(Exp|Explanation)[\s:\.]+(.*)/i', $line, $matches)) {
                    $explanation = trim($matches[2]);
                } elseif (preg_match('/^(Points|Pts)[\s:\.]+(\d+)/i', $line, $matches)) {
                    $points = (int)$matches[2];
                } elseif (empty($qtext)) {
                    // If no explicit Q: prefix, assume first line is the question
                    $qtext = $line;
                }
            }

            if ($qtext && $opt_a && $opt_b && $correct) {
                $stmt = $pdo->prepare("INSERT INTO lms_questions (exam_id, question_text, option_a, option_b, option_c, option_d, correct_answer, explanation, points) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$eid, $qtext, $opt_a, $opt_b, $opt_c, $opt_d, $correct, $explanation, $points]);
                $added_count++;
            }
        }

        if ($added_count > 0) {
            $count = $pdo->query("SELECT COUNT(*) FROM lms_questions WHERE exam_id = $eid")->fetchColumn();
            $pdo->prepare("UPDATE lms_exams SET total_questions = ? WHERE id = ?")->execute([$count, $eid]);
            setFlashMessage("Successfully imported $added_count questions!", 'success');
        } else {
            setFlashMessage("No valid questions found in the text. Check the format.", 'warning');
        }
        
        header("Location: exams.php?view=questions&eid=$eid");
        exit;
    }
}

$view = $_GET['view'] ?? 'list';
$eid  = (int)($_GET['eid'] ?? 0);

// All exams
$exams = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM lms_attempts WHERE exam_id = e.id AND status='completed') as attempt_count FROM lms_exams e WHERE e.teacher_id = ? ORDER BY e.created_at DESC");
$exams->execute([$teacher_id]);
$exams = $exams->fetchAll();

// Questions for selected exam
$questions = [];
$current_exam = null;
if ($view === 'questions' && $eid) {
    $current_exam = $pdo->query("SELECT * FROM lms_exams WHERE id = $eid")->fetch();
    $questions = $pdo->query("SELECT * FROM lms_questions WHERE exam_id = $eid ORDER BY sort_order, id")->fetchAll();
}

// Get my classes for the select box
$classes_stmt = $pdo->prepare("
    SELECT cs.class_id, c.class_name, c.section, s.subject_name, s.id as subject_id
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    JOIN sms_subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ?
");
$classes_stmt->execute([$teacher_id]);
$my_classes = $classes_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exams & Quizzes - Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .exam-card { border-radius: 16px; border: 1px solid #e0e0e0; transition: all .3s; }
        .exam-card:hover { border-color: #a5d6a7; box-shadow: 0 8px 24px rgba(27,94,32,.1); }
        .q-card { border-radius: 14px; border-left: 4px solid #1B5E20; background: #fff; }
        .option-label { padding: 10px 16px; border-radius: 10px; border: 1.5px solid #e0e0e0; cursor: pointer; }
        .correct-option { border-color: #2e7d32; background: #e8f5e9; color: #1B5E20; font-weight: 600; }
        .badge-easy { background: #e8f5e9; color: #2e7d32; }
        .badge-medium { background: #fff3e0; color: #e65100; }
        .badge-hard { background: #fce4ec; color: #c62828; }
    </style>
</head>
<body>
<?php include('../includes/sidebar_teacher.php'); ?>

<div class="main-content">
    <?php echo displayFlashMessage(); ?>

    <?php if ($view === 'questions' && $current_exam): ?>
        <!-- QUESTION MANAGER -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="exams.php" class="btn btn-light border rounded-circle"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h2 class="fw-bold mb-0"><?= htmlspecialchars($current_exam['title']) ?></h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($current_exam['subject']) ?> &bull; <?= $current_exam['total_questions'] ?> Questions &bull;
                    <span class="badge badge-<?= $current_exam['difficulty'] ?>"><?= ucfirst($current_exam['difficulty']) ?></span>
                </p>
            </div>
            <div class="ms-auto d-flex gap-2">
                <button class="btn btn-outline-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#bulkQuestionModal">
                    <i class="fas fa-paste me-2"></i>Bulk Paste
                </button>
                <button class="btn btn-success rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addQuestionModal">
                    <i class="fas fa-plus me-2"></i>Add Question
                </button>
            </div>
        </div>

        <?php if (empty($questions)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="fas fa-question-circle fa-3x text-muted opacity-50 mb-3"></i>
                <h5 class="text-muted">No questions yet.</h5>
                <p class="text-muted">Click <strong>"Add Question"</strong> to start building the exam.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
            <?php foreach ($questions as $i => $q): ?>
                <div class="col-12">
                    <div class="card q-card p-4 shadow-sm">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h6 class="fw-bold mb-0">Q<?= $i+1 ?>. <?= htmlspecialchars($q['question_text']) ?></h6>
                            <div class="d-flex gap-2 align-items-center">
                                <span class="badge bg-light text-dark border"><?= $q['points'] ?> pt<?= $q['points'] > 1 ? 's' : '' ?></span>
                                <form method="POST" onsubmit="return confirm('Delete?')" class="d-inline">
                                    <input type="hidden" name="action" value="delete_question">
                                    <input type="hidden" name="question_id" value="<?= $q['id'] ?>">
                                    <input type="hidden" name="exam_id" value="<?= $eid ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-circle border-0"><i class="fas fa-times"></i></button>
                                </form>
                            </div>
                        </div>
                        <div class="row g-2">
                            <?php foreach (['a'=>'A','b'=>'B','c'=>'C','d'=>'D'] as $key => $label): ?>
                            <div class="col-md-6">
                                <div class="option-label <?= (strtolower($q['correct_answer']) === $key) ? 'correct-option' : '' ?>">
                                    <span class="fw-bold me-2"><?= $label ?>.</span><?= htmlspecialchars($q['option_' . $key]) ?>
                                    <?php if (strtolower($q['correct_answer']) === $key): ?>
                                        <i class="fas fa-check-circle text-success ms-2"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if ($q['explanation']): ?>
                        <div class="mt-3 p-2 bg-light rounded-3 small text-muted">
                            <i class="fas fa-lightbulb text-warning me-1"></i><?= htmlspecialchars($q['explanation']) ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- EXAM LIST -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Exams & Quizzes</h2>
                <p class="text-muted mb-0">Create and manage assessments for your students.</p>
            </div>
            <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createExamModal">
                <i class="fas fa-plus me-2"></i>Create Exam
            </button>
        </div>

        <?php if (empty($exams)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                <i class="fas fa-file-signature fa-3x text-muted opacity-50 mb-3"></i>
                <h5 class="text-muted">No exams or quizzes yet.</h5>
                <p class="text-muted">Create your first exam to assess student progress.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
            <?php foreach ($exams as $e): ?>
                <div class="col-md-6 col-xl-4">
                    <div class="card exam-card p-4 h-100">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge badge-<?= $e['difficulty'] ?> rounded-pill px-3 py-2"><?= ucfirst($e['difficulty']) ?></span>
                            <span class="badge <?= $e['status'] === 'active' ? 'bg-success' : 'bg-secondary' ?> rounded-pill px-3"><?= ucfirst($e['status']) ?></span>
                        </div>
                        <h5 class="fw-bold mb-1"><?= htmlspecialchars($e['title']) ?></h5>
                        <p class="text-muted small mb-3"><?= htmlspecialchars($e['subject']) ?></p>
                        <div class="d-flex gap-3 mb-4 text-muted small">
                            <div><i class="fas fa-question-circle text-primary me-1"></i><?= $e['total_questions'] ?> Qs</div>
                            <div><i class="fas fa-clock text-warning me-1"></i><?= $e['duration_minutes'] ?> min</div>
                            <div><i class="fas fa-users text-info me-1"></i><?= $e['attempt_count'] ?> attempts</div>
                        </div>
                        <div class="d-flex gap-2 mt-auto">
                            <a href="?view=questions&eid=<?= $e['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1">
                                <i class="fas fa-list me-1"></i>Questions
                            </a>
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                <button class="btn btn-sm <?= $e['status'] === 'active' ? 'btn-warning' : 'btn-success' ?> rounded-pill px-3" title="<?= $e['status'] === 'active' ? 'Unpublish' : 'Publish' ?>">
                                    <i class="fas fa-<?= $e['status'] === 'active' ? 'eye-slash' : 'globe' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" onsubmit="return confirm('Delete this exam and all its questions?')">
                                <input type="hidden" name="action" value="delete_exam">
                                <input type="hidden" name="exam_id" value="<?= $e['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Create Exam Modal -->
<div class="modal" id="createExamModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="create_exam">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Create New Exam / Quiz</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Exam Title</label>
                    <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Chapter 5 Mid-Term Quiz" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Description (optional)</label>
                    <textarea name="description" class="form-control rounded-3" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Subject</label>
                        <select name="class_id" class="form-select rounded-3" required>
                            <option value="">-- Choose Class/Subject --</option>
                            <?php foreach ($my_classes as $c): ?>
                                <option value="<?= $c['class_id'] ?>"><?= htmlspecialchars($c['subject_name'] . ' – ' . $c['class_name'] . ' ' . $c['section']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Grade Level</label>
                        <select name="grade" class="form-select rounded-3" required>
                            <?php for ($g = 1; $g <= 12; $g++): ?>
                                <option value="<?= $g ?>">Grade <?= $g ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label small fw-bold">Difficulty</label>
                        <select name="difficulty" class="form-select rounded-3">
                            <option value="easy">Easy</option>
                            <option value="medium" selected>Medium</option>
                            <option value="hard">Hard</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Duration (minutes)</label>
                        <input type="number" name="duration_minutes" class="form-control rounded-3" value="30" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Pass Percentage (%)</label>
                        <input type="number" name="pass_percentage" class="form-control rounded-3" value="50" min="1" max="100" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5"><i class="fas fa-arrow-right me-2"></i>Create & Add Questions</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Question Modal -->
<div class="modal" id="addQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="add_question">
            <input type="hidden" name="exam_id" value="<?= $eid ?>">
            <div class="modal-header border-0"><h5 class="modal-title fw-bold">Add Question</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Question</label>
                    <textarea name="question_text" class="form-control rounded-3" rows="3" placeholder="Type your question here..." required></textarea>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Option A</label>
                        <input type="text" name="option_a" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Option B</label>
                        <input type="text" name="option_b" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Option C</label>
                        <input type="text" name="option_c" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-bold">Option D</label>
                        <input type="text" name="option_d" class="form-control rounded-3" required>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label small fw-bold">Correct Answer</label>
                        <select name="correct_answer" class="form-select rounded-3" required>
                            <option value="A">A</option>
                            <option value="B">B</option>
                            <option value="C">C</option>
                            <option value="D">D</option>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label class="form-label small fw-bold">Points</label>
                        <input type="number" name="points" class="form-control rounded-3" value="1" min="1">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Explanation (optional)</label>
                        <input type="text" name="explanation" class="form-control rounded-3" placeholder="Why is this correct?">
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5"><i class="fas fa-plus me-2"></i>Add Question</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Question Modal -->
<div class="modal" id="bulkQuestionModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content border-0 shadow-lg">
            <input type="hidden" name="action" value="bulk_add_questions">
            <input type="hidden" name="exam_id" value="<?= $eid ?>">
            <div class="modal-header border-0 bg-success text-white">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-import me-2"></i>Bulk Question Import</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info rounded-4 border-0 mb-4">
                    <h6 class="fw-bold mb-2"><i class="fas fa-magic me-2"></i>Parsing Format</h6>
                    <p class="small mb-2">Paste your questions below. Seperate each question with a <strong>blank line</strong>.</p>
                    <code class="small d-block bg-white p-2 rounded shadow-sm opacity-75">
                        Q: What is the capital of Ethiopia?<br>
                        A) Addis Ababa<br>
                        B) Dire Dawa<br>
                        C) Hawassa<br>
                        D) Bahir Dar<br>
                        Ans: A<br>
                        Points: 1
                    </code>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold">Paste Questions from Text/PDF</label>
                    <textarea name="bulk_text" class="form-control rounded-4 p-3" rows="12" placeholder="Paste your mass questions here..." required></textarea>
                </div>
                <div class="text-muted small">
                    <i class="fas fa-info-circle me-1"></i> You can copy text directly from a PDF and paste it here. Make sure each question block has options A, B, C, D and an answer starting with 'Ans:' or 'Correct:'.
                </div>
            </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5 shadow-sm">
                    <i class="fas fa-bolt me-2"></i>Import Questions Now
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
