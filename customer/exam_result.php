<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: ../login.php');
    exit;
}

$attempt_id = (int) ($_GET['attempt_id'] ?? 0);
if ($attempt_id <= 0) {
    header('Location: lms.php');
    exit;
}

// Fetch attempt with exam info
$stmt = $pdo->prepare("
    SELECT a.*, e.grade, e.subject, e.chapter, e.chapter_title, e.title as exam_title,
           e.duration_minutes, e.pass_percentage, e.total_questions, e.difficulty
    FROM lms_attempts a
    JOIN lms_exams e ON a.exam_id = e.id
    WHERE a.id = ? AND a.user_id = ?
");
$stmt->execute([$attempt_id, $user_id]);
$attempt = $stmt->fetch();

if (!$attempt) {
    setFlashMessage('Result not found', 'danger');
    header('Location: lms.php');
    exit;
}

// Fetch all answers with question details
$stmt = $pdo->prepare("
    SELECT ans.*, q.question_text, q.option_a, q.option_b, q.option_c, q.option_d,
           q.correct_answer, q.explanation, q.points, q.sort_order
    FROM lms_answers ans
    JOIN lms_questions q ON ans.question_id = q.id
    WHERE ans.attempt_id = ?
    ORDER BY q.sort_order, q.id
");
$stmt->execute([$attempt_id]);
$answers = $stmt->fetchAll();

$passed = $attempt['score'] >= $attempt['pass_percentage'];
$correct_count = 0;
$wrong_count = 0;
$unanswered = 0;
foreach ($answers as $a) {
    if ($a['is_correct'])
        $correct_count++;
    elseif (empty($a['selected_answer']))
        $unanswered++;
    else
        $wrong_count++;
}

$time_spent = $attempt['time_spent_seconds'];
$minutes = floor($time_spent / 60);
$seconds = $time_spent % 60;

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
$color = $subject_colors[$attempt['subject']] ?? '#6366f1';

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<style>
    :root {
        --exam-color: <?php echo $color; ?>;
        --bg: #0a0f1a;
        --surface: #111827;
        --card: #1a2332;
        --border: rgba(255, 255, 255, 0.08);
        --text: #e5e7eb;
        --muted: #6b7280;
        --accent: #6366f1;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background: var(--bg);
        margin: 0;
    }

    .result-hero {
        background: linear-gradient(135deg, <?php echo $passed ? '#064e3b, #065f46, #047857' : '#450a0a, #7f1d1d, #991b1b'; ?>);
        padding: 48px 0 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .result-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: radial-gradient(circle at 50% 120%, rgba(255, 255, 255, 0.1), transparent 60%);
    }

    .result-hero .container {
        position: relative;
        z-index: 1;
    }

    .result-icon {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        border: 3px solid rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        color: #fff;
        margin: 0 auto 20px;
        animation: resultPop 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes resultPop {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    .result-score {
        font-size: 4rem;
        font-weight: 900;
        color: #fff;
        line-height: 1;
        animation: scoreCount 1s ease-out;
    }

    @keyframes scoreCount {
        0% {
            opacity: 0;
            transform: translateY(20px);
        }

        100% {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .result-score span {
        font-size: 1.8rem;
        font-weight: 400;
        opacity: .7;
    }

    .result-label {
        color: rgba(255, 255, 255, 0.8);
        font-size: 1.1rem;
        font-weight: 600;
        margin-top: 8px;
    }

    .result-subject {
        color: rgba(255, 255, 255, 0.5);
        font-size: .85rem;
        margin-top: 4px;
    }

    .result-stats {
        display: flex;
        justify-content: center;
        gap: 20px;
        margin-top: 28px;
        flex-wrap: wrap;
    }

    .result-stat-card {
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 14px;
        padding: 14px 24px;
        text-align: center;
        min-width: 110px;
    }

    .result-stat-val {
        color: #fff;
        font-weight: 800;
        font-size: 1.5rem;
        line-height: 1;
    }

    .result-stat-label {
        color: rgba(255, 255, 255, 0.5);
        font-size: .7rem;
        margin-top: 4px;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .result-actions {
        display: flex;
        gap: 12px;
        justify-content: center;
        margin-top: 28px;
        flex-wrap: wrap;
    }

    .result-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 28px;
        border-radius: 50px;
        font-weight: 700;
        font-size: .85rem;
        text-decoration: none;
        transition: all .3s;
    }

    .result-btn-primary {
        background: rgba(255, 255, 255, 0.2);
        color: #fff;
        border: 1px solid rgba(255, 255, 255, 0.3);
    }

    .result-btn-primary:hover {
        background: rgba(255, 255, 255, 0.3);
        color: #fff;
    }

    .result-btn-accent {
        background: var(--accent);
        color: #fff;
        border: none;
    }

    .result-btn-accent:hover {
        background: #4f46e5;
        color: #fff;
        transform: translateY(-2px);
    }

    /* Answers review */
    .review-container {
        max-width: 780px;
        margin: 0 auto;
        padding: 32px 20px 60px;
    }

    .review-header {
        color: #fff;
        font-weight: 800;
        font-size: 1.3rem;
        margin-bottom: 24px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .review-header i {
        color: var(--accent);
    }

    .review-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 18px;
        padding: 24px;
        margin-bottom: 16px;
        position: relative;
        overflow: hidden;
    }

    .review-card.correct {
        border-left: 4px solid var(--success);
    }

    .review-card.wrong {
        border-left: 4px solid var(--danger);
    }

    .review-card.skipped {
        border-left: 4px solid var(--muted);
    }

    .review-status {
        position: absolute;
        top: 16px;
        right: 16px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 14px;
        border-radius: 50px;
        font-size: .72rem;
        font-weight: 700;
    }

    .status-correct {
        background: rgba(16, 185, 129, 0.15);
        color: var(--success);
    }

    .status-wrong {
        background: rgba(239, 68, 68, 0.15);
        color: var(--danger);
    }

    .status-skipped {
        background: rgba(107, 114, 128, 0.15);
        color: var(--muted);
    }

    .review-q-num {
        color: var(--accent);
        font-size: .72rem;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .review-q-text {
        color: #fff;
        font-weight: 600;
        font-size: .95rem;
        line-height: 1.5;
        margin-bottom: 16px;
    }

    .review-option {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 10px 14px;
        border-radius: 12px;
        margin-bottom: 6px;
        border: 2px solid transparent;
    }

    .review-option.is-correct {
        background: rgba(16, 185, 129, 0.08);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .review-option.is-selected-wrong {
        background: rgba(239, 68, 68, 0.08);
        border-color: rgba(239, 68, 68, 0.3);
    }

    .review-opt-letter {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: .78rem;
    }

    .review-opt-letter.correct-letter {
        background: var(--success);
        color: #fff;
    }

    .review-opt-letter.wrong-letter {
        background: var(--danger);
        color: #fff;
    }

    .review-opt-letter.neutral {
        background: rgba(255, 255, 255, 0.06);
        color: var(--muted);
    }

    .review-opt-text {
        color: var(--text);
        font-size: .85rem;
        line-height: 1.5;
        padding-top: 3px;
    }

    .explanation-box {
        background: rgba(99, 102, 241, 0.08);
        border: 1px solid rgba(99, 102, 241, 0.2);
        border-radius: 12px;
        padding: 14px 18px;
        margin-top: 14px;
    }

    .explanation-box .label {
        color: var(--accent);
        font-weight: 700;
        font-size: .72rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 4px;
    }

    .explanation-box p {
        color: var(--text);
        font-size: .82rem;
        line-height: 1.6;
        margin: 0;
    }

    @media(max-width:576px) {
        .result-score {
            font-size: 3rem;
        }

        .result-stats {
            gap: 10px;
        }

        .result-stat-card {
            padding: 10px 16px;
            min-width: 80px;
        }

        .review-card {
            padding: 18px 14px;
        }
    }
</style>

<!-- Result Hero -->
<div class="result-hero">
    <div class="container">
        <div class="result-icon">
            <i class="fas fa-<?php echo $passed ? 'trophy' : 'times'; ?>"></i>
        </div>
        <div class="result-score">
            <?php echo number_format($attempt['score'], 0); ?><span>%</span>
        </div>
        <div class="result-label">
            <?php echo $passed ? '🎉 Congratulations! You Passed!' : '📚 Keep Studying, You Can Do Better!'; ?>
        </div>
        <div class="result-subject">
            Grade
            <?php echo $attempt['grade']; ?> ·
            <?php echo htmlspecialchars($attempt['subject']); ?> ·
            Chapter
            <?php echo $attempt['chapter']; ?>:
            <?php echo htmlspecialchars($attempt['chapter_title']); ?>
        </div>

        <div class="result-stats">
            <div class="result-stat-card">
                <div class="result-stat-val" style="color:var(--success);">
                    <?php echo $correct_count; ?>
                </div>
                <div class="result-stat-label">Correct</div>
            </div>
            <div class="result-stat-card">
                <div class="result-stat-val" style="color:var(--danger);">
                    <?php echo $wrong_count; ?>
                </div>
                <div class="result-stat-label">Wrong</div>
            </div>
            <div class="result-stat-card">
                <div class="result-stat-val" style="color:var(--muted);">
                    <?php echo $unanswered; ?>
                </div>
                <div class="result-stat-label">Skipped</div>
            </div>
            <div class="result-stat-card">
                <div class="result-stat-val">
                    <?php echo $minutes; ?>:
                    <?php echo str_pad($seconds, 2, '0', STR_PAD_LEFT); ?>
                </div>
                <div class="result-stat-label">Time</div>
            </div>
        </div>

        <div class="result-actions">
            <a href="take_exam.php?exam_id=<?php echo $attempt['exam_id']; ?>" class="result-btn result-btn-primary">
                <i class="fas fa-redo"></i> Retake Exam
            </a>
            <a href="lms.php?grade=<?php echo $attempt['grade']; ?>&subject=<?php echo urlencode($attempt['subject']); ?>"
                class="result-btn result-btn-accent">
                <i class="fas fa-list"></i> All Chapter Exams
            </a>
            <a href="education.php?grade=<?php echo $attempt['grade']; ?>" class="result-btn result-btn-primary">
                <i class="fas fa-book-reader"></i> Study More
            </a>
        </div>
    </div>
</div>

<!-- Answer Review -->
<div class="review-container">
    <div class="review-header">
        <i class="fas fa-clipboard-check"></i> Answer Review — See the correct answers
    </div>

    <?php foreach ($answers as $idx => $a):
        $is_correct = $a['is_correct'];
        $is_skipped = empty($a['selected_answer']);
        $status_class = $is_correct ? 'correct' : ($is_skipped ? 'skipped' : 'wrong');
        $letters = ['A' => $a['option_a'], 'B' => $a['option_b'], 'C' => $a['option_c'], 'D' => $a['option_d']];
        ?>
        <div class="review-card <?php echo $status_class; ?>">
            <span class="review-status status-<?php echo $status_class; ?>">
                <i
                    class="fas fa-<?php echo $is_correct ? 'check-circle' : ($is_skipped ? 'minus-circle' : 'times-circle'); ?>"></i>
                <?php echo $is_correct ? 'Correct' : ($is_skipped ? 'Skipped' : 'Wrong'); ?>
            </span>
            <div class="review-q-num">Question
                <?php echo $idx + 1; ?>
            </div>
            <div class="review-q-text">
                <?php echo htmlspecialchars($a['question_text']); ?>
            </div>

            <?php foreach ($letters as $letter => $text):
                $is_this_correct = (strtoupper($a['correct_answer']) === $letter);
                $is_this_selected = (strtoupper($a['selected_answer'] ?? '') === $letter);
                $option_class = '';
                $letter_class = 'neutral';

                if ($is_this_correct) {
                    $option_class = 'is-correct';
                    $letter_class = 'correct-letter';
                } elseif ($is_this_selected && !$is_correct) {
                    $option_class = 'is-selected-wrong';
                    $letter_class = 'wrong-letter';
                }
                ?>
                <div class="review-option <?php echo $option_class; ?>">
                    <span class="review-opt-letter <?php echo $letter_class; ?>">
                        <?php if ($is_this_correct): ?>
                            <i class="fas fa-check"></i>
                        <?php elseif ($is_this_selected && !$is_correct): ?>
                            <i class="fas fa-times"></i>
                        <?php else: ?>
                            <?php echo $letter; ?>
                        <?php endif; ?>
                    </span>
                    <span class="review-opt-text">
                        <?php echo htmlspecialchars($text); ?>
                    </span>
                </div>
            <?php endforeach; ?>

            <?php if (!empty($a['explanation'])): ?>
                <div class="explanation-box">
                    <div class="label"><i class="fas fa-lightbulb me-1"></i> Explanation</div>
                    <p>
                        <?php echo htmlspecialchars($a['explanation']); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>

<?php include('../includes/footer.php'); ?>