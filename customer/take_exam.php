<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Must be logged in
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    setFlashMessage('Please log in to take exams', 'warning');
    header('Location: ../login.php');
    exit;
}

$exam_id = (int) ($_GET['exam_id'] ?? 0);
if ($exam_id <= 0) {
    header('Location: lms.php');
    exit;
}

// Fetch exam
$stmt = $pdo->prepare("SELECT * FROM lms_exams WHERE id = ? AND status = 'active'");
$stmt->execute([$exam_id]);
$exam = $stmt->fetch();
if (!$exam) {
    setFlashMessage('Exam not found', 'danger');
    header('Location: lms.php');
    exit;
}

// Fetch questions
$stmt = $pdo->prepare("SELECT * FROM lms_questions WHERE exam_id = ? ORDER BY sort_order, id");
$stmt->execute([$exam_id]);
$questions = $stmt->fetchAll();

if (empty($questions)) {
    setFlashMessage('No questions found for this exam', 'warning');
    header('Location: lms.php?grade=' . $exam['grade'] . '&subject=' . urlencode($exam['subject']));
    exit;
}

// Handle exam submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_exam'])) {
    $attempt_id = (int) ($_POST['attempt_id'] ?? 0);
    $time_spent = (int) ($_POST['time_spent'] ?? 0);

    // Verify attempt belongs to user
    $stmt = $pdo->prepare("SELECT * FROM lms_attempts WHERE id = ? AND user_id = ? AND status = 'in_progress'");
    $stmt->execute([$attempt_id, $user_id]);
    $attempt = $stmt->fetch();

    if (!$attempt) {
        // Create new attempt if not found
        $stmt = $pdo->prepare("INSERT INTO lms_attempts (user_id, exam_id, status) VALUES (?, ?, 'in_progress')");
        $stmt->execute([$user_id, $exam_id]);
        $attempt_id = $pdo->lastInsertId();
    }

    // Grade the exam
    $total_points = 0;
    $earned_points = 0;

    // Delete any old answers for this attempt
    $pdo->prepare("DELETE FROM lms_answers WHERE attempt_id = ?")->execute([$attempt_id]);

    foreach ($questions as $q) {
        $answer = $_POST['q_' . $q['id']] ?? '';
        $is_correct = (strtoupper($answer) === strtoupper($q['correct_answer'])) ? 1 : 0;
        $total_points += $q['points'];
        if ($is_correct)
            $earned_points += $q['points'];

        $stmt = $pdo->prepare("INSERT INTO lms_answers (attempt_id, question_id, selected_answer, is_correct) VALUES (?, ?, ?, ?)");
        $stmt->execute([$attempt_id, $q['id'], $answer ?: null, $is_correct]);
    }

    $score = ($total_points > 0) ? ($earned_points / $total_points * 100) : 0;

    // Update attempt
    $stmt = $pdo->prepare("UPDATE lms_attempts SET score = ?, total_points = ?, earned_points = ?, status = 'completed', completed_at = NOW(), time_spent_seconds = ? WHERE id = ?");
    $stmt->execute([$score, $total_points, $earned_points, $time_spent, $attempt_id]);

    header("Location: exam_result.php?attempt_id=" . $attempt_id);
    exit;
}

// Create a new attempt
$stmt = $pdo->prepare("INSERT INTO lms_attempts (user_id, exam_id, status) VALUES (?, ?, 'in_progress')");
$stmt->execute([$user_id, $exam_id]);
$attempt_id = $pdo->lastInsertId();

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
$color = $subject_colors[$exam['subject']] ?? '#6366f1';

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800;900&display=swap"
    rel="stylesheet">
<style>
    :root {
        --exam-color:
            <?php echo $color; ?>
        ;
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

    /* Top exam bar */
    .exam-topbar {
        background: linear-gradient(90deg, #111827, #1a2332);
        border-bottom: 1px solid var(--border);
        padding: 12px 24px;
        position: sticky;
        top: 0;
        z-index: 100;
        backdrop-filter: blur(16px);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .exam-topbar-left {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .exam-topbar-back {
        color: rgba(255, 255, 255, 0.6);
        text-decoration: none;
        font-size: .85rem;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.06);
        transition: .3s;
    }

    .exam-topbar-back:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }

    .exam-title-info h6 {
        color: #fff;
        margin: 0;
        font-weight: 700;
        font-size: .9rem;
    }

    .exam-title-info span {
        color: var(--muted);
        font-size: .72rem;
    }

    /* Timer */
    .exam-timer {
        display: flex;
        align-items: center;
        gap: 8px;
        background: rgba(239, 68, 68, 0.1);
        border: 1px solid rgba(239, 68, 68, 0.3);
        padding: 8px 18px;
        border-radius: 50px;
        color: var(--danger);
        font-weight: 700;
        font-size: .9rem;
    }

    .exam-timer.warning {
        background: rgba(245, 158, 11, 0.1);
        border-color: rgba(245, 158, 11, 0.3);
        color: var(--warning);
    }

    .exam-timer.safe {
        background: rgba(16, 185, 129, 0.1);
        border-color: rgba(16, 185, 129, 0.3);
        color: var(--success);
    }

    .exam-timer i {
        font-size: .8rem;
    }

    /* Progress bar */
    .exam-progress {
        background: rgba(255, 255, 255, 0.06);
        height: 4px;
        position: sticky;
        top: 56px;
        z-index: 99;
    }

    .exam-progress-fill {
        height: 100%;
        background: linear-gradient(90deg, var(--accent), #818cf8);
        transition: width .5s ease;
        border-radius: 0 4px 4px 0;
    }

    /* Question navigator */
    .q-navigator {
        position: fixed;
        right: 24px;
        top: 100px;
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 16px;
        padding: 16px;
        width: 70px;
        z-index: 50;
    }

    .q-navigator h6 {
        color: var(--muted);
        font-size: .6rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        text-align: center;
        margin-bottom: 10px;
    }

    .q-nav-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 6px;
    }

    .q-nav-btn {
        width: 28px;
        height: 28px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: transparent;
        color: var(--muted);
        font-size: .7rem;
        font-weight: 700;
        cursor: pointer;
        transition: all .2s;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .q-nav-btn:hover {
        border-color: var(--accent);
        color: var(--accent);
    }

    .q-nav-btn.answered {
        background: rgba(16, 185, 129, 0.15);
        color: var(--success);
        border-color: rgba(16, 185, 129, 0.3);
    }

    .q-nav-btn.current {
        background: var(--accent);
        color: #fff;
        border-color: var(--accent);
    }

    /* Questions container */
    .questions-container {
        max-width: 780px;
        margin: 0 auto;
        padding: 32px 20px 60px;
    }

    /* Question card */
    .question-card {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 28px;
        margin-bottom: 20px;
        transition: all .3s ease;
        scroll-margin-top: 80px;
    }

    .question-card:hover {
        border-color: rgba(255, 255, 255, 0.12);
    }

    .q-number {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        background: rgba(99, 102, 241, 0.15);
        color: var(--accent);
        font-size: .72rem;
        font-weight: 700;
        padding: 4px 14px;
        border-radius: 50px;
        margin-bottom: 14px;
    }

    .q-text {
        color: #fff;
        font-weight: 600;
        font-size: 1rem;
        line-height: 1.6;
        margin-bottom: 18px;
    }

    /* Options */
    .option-group {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .option-label {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        padding: 14px 18px;
        background: rgba(255, 255, 255, 0.03);
        border: 2px solid var(--border);
        border-radius: 14px;
        cursor: pointer;
        transition: all .25s;
        position: relative;
    }

    .option-label:hover {
        background: rgba(99, 102, 241, 0.05);
        border-color: rgba(99, 102, 241, 0.3);
    }

    .option-label input {
        display: none;
    }

    .option-letter {
        width: 32px;
        height: 32px;
        border-radius: 10px;
        flex-shrink: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.06);
        color: var(--muted);
        font-weight: 700;
        font-size: .82rem;
        transition: all .25s;
    }

    .option-text {
        color: var(--text);
        font-size: .9rem;
        line-height: 1.5;
        padding-top: 4px;
    }

    .option-label input:checked~.option-letter {
        background: var(--accent);
        color: #fff;
    }

    .option-label:has(input:checked) {
        border-color: var(--accent);
        background: rgba(99, 102, 241, 0.08);
    }

    /* Submit section */
    .submit-section {
        background: var(--card);
        border: 1px solid var(--border);
        border-radius: 20px;
        padding: 28px;
        text-align: center;
        margin-top: 10px;
    }

    .submit-section h5 {
        color: #fff;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .submit-section p {
        color: var(--muted);
        font-size: .85rem;
        margin-bottom: 18px;
    }

    .btn-submit-exam {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: linear-gradient(135deg, var(--accent), #818cf8);
        color: #fff;
        border: none;
        padding: 14px 40px;
        border-radius: 50px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all .3s;
        box-shadow: 0 4px 20px rgba(99, 102, 241, 0.4);
    }

    .btn-submit-exam:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 30px rgba(99, 102, 241, 0.5);
    }

    .btn-submit-exam:disabled {
        opacity: 0.5;
        cursor: not-allowed;
        transform: none;
    }

    .answered-count {
        color: var(--success);
        font-weight: 700;
        font-size: 1.4rem;
    }

    @media(max-width:900px) {
        .q-navigator {
            display: none;
        }
    }

    @media(max-width:576px) {
        .question-card {
            padding: 20px 16px;
        }

        .exam-topbar {
            padding: 10px 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .exam-title-info h6 {
            font-size: .78rem;
        }
    }
</style>

<!-- Exam Top Bar -->
<div class="exam-topbar">
    <div class="exam-topbar-left">
        <a href="lms.php?grade=<?php echo $exam['grade']; ?>&subject=<?php echo urlencode($exam['subject']); ?>"
            class="exam-topbar-back" onclick="return confirm('Leave exam? Your progress will be lost.');">
            <i class="fas fa-arrow-left"></i> Exit
        </a>
        <div class="exam-title-info">
            <h6>
                <?php echo htmlspecialchars($exam['subject']); ?> — Chapter
                <?php echo $exam['chapter']; ?>
            </h6>
            <span>Grade
                <?php echo $exam['grade']; ?> ·
                <?php echo htmlspecialchars($exam['chapter_title']); ?>
            </span>
        </div>
    </div>
    <div class="exam-timer safe" id="examTimer">
        <i class="fas fa-clock"></i>
        <span id="timerDisplay">
            <?php echo $exam['duration_minutes']; ?>:00
        </span>
    </div>
</div>

<!-- Progress bar -->
<div class="exam-progress">
    <div class="exam-progress-fill" id="progressFill" style="width:0%;"></div>
</div>

<!-- Question Navigator (sidebar) -->
<div class="q-navigator" id="qNavigator">
    <h6>Questions</h6>
    <div class="q-nav-grid">
        <?php foreach ($questions as $idx => $q): ?>
            <button class="q-nav-btn <?php echo $idx === 0 ? 'current' : ''; ?>" id="navBtn<?php echo $idx; ?>"
                onclick="scrollToQuestion(<?php echo $idx; ?>)">
                <?php echo $idx + 1; ?>
            </button>
        <?php endforeach; ?>
    </div>
</div>

<!-- Questions -->
<form method="POST" id="examForm" onsubmit="return confirmSubmit()">
    <input type="hidden" name="submit_exam" value="1">
    <input type="hidden" name="attempt_id" value="<?php echo $attempt_id; ?>">
    <input type="hidden" name="time_spent" id="timeSpent" value="0">

    <div class="questions-container">
        <?php foreach ($questions as $idx => $q): ?>
            <div class="question-card" id="question<?php echo $idx; ?>">
                <div class="q-number"><i class="fas fa-question-circle"></i> Question
                    <?php echo $idx + 1; ?> of
                    <?php echo count($questions); ?>
                </div>
                <div class="q-text">
                    <?php echo htmlspecialchars($q['question_text']); ?>
                </div>
                <div class="option-group">
                    <?php foreach (['A' => $q['option_a'], 'B' => $q['option_b'], 'C' => $q['option_c'], 'D' => $q['option_d']] as $letter => $text): ?>
                        <label class="option-label">
                            <input type="radio" name="q_<?php echo $q['id']; ?>" value="<?php echo $letter; ?>"
                                onchange="markAnswered(<?php echo $idx; ?>)">
                            <span class="option-letter">
                                <?php echo $letter; ?>
                            </span>
                            <span class="option-text">
                                <?php echo htmlspecialchars($text); ?>
                            </span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <!-- Submit Section -->
        <div class="submit-section">
            <h5><i class="fas fa-flag-checkered me-2"></i>Ready to Submit?</h5>
            <p>
                <span class="answered-count" id="answeredCount">0</span> /
                <?php echo count($questions); ?>
                questions answered
            </p>
            <button type="submit" class="btn-submit-exam" id="submitBtn">
                <i class="fas fa-paper-plane"></i> Submit Exam
            </button>
        </div>
    </div>
</form>

<!-- Anti-cheat warning overlay -->
<div id="cheatWarning"
    style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.92);
    backdrop-filter:blur(16px);display:none;align-items:center;justify-content:center;flex-direction:column;text-align:center;padding:24px;">
    <div style="max-width:420px;">
        <div style="width:90px;height:90px;border-radius:50%;background:rgba(239,68,68,0.15);display:flex;align-items:center;
            justify-content:center;margin:0 auto 20px;border:3px solid #ef4444;animation:pulse 1s infinite;">
            <i class="fas fa-exclamation-triangle" style="font-size:2.5rem;color:#ef4444;"></i>
        </div>
        <h2 style="color:#ef4444;font-weight:800;margin-bottom:10px;" id="warningTitle">⚠️ Warning!</h2>
        <p style="color:#e5e7eb;font-size:1rem;margin-bottom:8px;" id="warningMsg">
            You left the exam page. This is your <strong>first warning</strong>.
        </p>
        <p style="color:#f87171;font-weight:700;font-size:1.1rem;margin-bottom:24px;">
            If you leave again, your exam will be <u>automatically submitted</u>!
        </p>
        <button onclick="dismissWarning()" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff;border:none;
            padding:14px 40px;border-radius:50px;font-weight:700;font-size:1rem;cursor:pointer;box-shadow:0 4px 20px rgba(239,68,68,0.4);
            transition:.3s;" onmouseover="this.style.transform='translateY(-2px)'"
            onmouseout="this.style.transform='none'">
            <i class="fas fa-arrow-left me-2"></i> Return to Exam
        </button>
        <p style="color:#6b7280;font-size:.75rem;margin-top:16px;">
            <i class="fas fa-shield-alt me-1"></i> Anti-cheating system is monitoring this exam
        </p>
    </div>
</div>

<!-- Violation badge in topbar -->
<style>
    .violation-badge {
        position: fixed;
        top: 12px;
        right: 200px;
        z-index: 101;
        background: rgba(239, 68, 68, 0.15);
        border: 1px solid rgba(239, 68, 68, 0.3);
        padding: 6px 14px;
        border-radius: 50px;
        color: #f87171;
        font-size: .75rem;
        font-weight: 700;
        display: none;
        align-items: center;
        gap: 6px;
    }

    .violation-badge.show {
        display: flex;
    }

    @keyframes pulse {

        0%,
        100% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }
    }

    .terminating-overlay {
        position: fixed;
        inset: 0;
        z-index: 99999;
        background: rgba(0, 0, 0, 0.95);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-direction: column;
        text-align: center;
    }
</style>
<div class="violation-badge" id="violationBadge">
    <i class="fas fa-shield-alt"></i> Violations: <span id="violationCount">0</span>
</div>

<script>
    const totalQuestions = <?php echo count($questions); ?>;
    const durationMinutes = <?php echo $exam['duration_minutes']; ?>;
    let timeLeft = durationMinutes * 60;
    let elapsed = 0;
    let answeredSet = new Set();

    // ===== ANTI-CHEATING SYSTEM =====
    let violationCount = 0;
    const maxWarnings = 1; // 1 warning, then auto-submit on 2nd violation

    function handleViolation(reason) {
        violationCount++;
        document.getElementById('violationCount').textContent = violationCount;
        document.getElementById('violationBadge').classList.add('show');

        if (violationCount <= maxWarnings) {
            // First violation: show warning
            document.getElementById('warningTitle').textContent = '⚠️ Warning #' + violationCount + '!';
            document.getElementById('warningMsg').innerHTML =
                'You attempted to <strong>' + reason + '</strong>. This is your <strong>first and final warning</strong>.';
            document.getElementById('cheatWarning').style.display = 'flex';
        } else {
            // Second violation: AUTO-TERMINATE
            terminateExam(reason);
        }
    }

    function dismissWarning() {
        document.getElementById('cheatWarning').style.display = 'none';
    }

    function terminateExam(reason) {
        // Show terminating overlay
        const overlay = document.createElement('div');
        overlay.className = 'terminating-overlay';
        overlay.innerHTML = `
            <div style="max-width:400px;">
                <i class="fas fa-ban" style="font-size:4rem;color:#ef4444;margin-bottom:20px;"></i>
                <h2 style="color:#ef4444;font-weight:800;">Exam Terminated</h2>
                <p style="color:#e5e7eb;">You violated the exam rules by trying to <strong>${reason}</strong>.</p>
                <p style="color:#f87171;">Your exam is being submitted with current answers...</p>
                <div style="margin-top:20px;">
                    <div class="spinner-border text-danger" role="status"></div>
                </div>
            </div>`;
        document.body.appendChild(overlay);

        // Auto-submit after brief delay
        setTimeout(() => {
            document.getElementById('examForm').submit();
        }, 2000);
    }

    // Detect tab switch / page hidden
    document.addEventListener('visibilitychange', () => {
        if (document.hidden) {
            handleViolation('leave the exam page');
        }
    });

    // Detect window blur (alt-tab, clicking outside)
    window.addEventListener('blur', () => {
        handleViolation('switch to another window');
    });

    // Block right-click
    document.addEventListener('contextmenu', (e) => {
        e.preventDefault();
        handleViolation('right-click (possible cheating)');
    });

    // Block copy/cut/paste
    document.addEventListener('copy', (e) => { e.preventDefault(); handleViolation('copy text'); });
    document.addEventListener('cut', (e) => { e.preventDefault(); handleViolation('cut text'); });

    // Block keyboard shortcuts (Ctrl+C, Ctrl+V, Ctrl+U, F12, etc.)
    document.addEventListener('keydown', (e) => {
        if (e.ctrlKey && ['c', 'u', 's', 'p', 'a'].includes(e.key.toLowerCase())) {
            e.preventDefault();
            handleViolation('use keyboard shortcut (Ctrl+' + e.key.toUpperCase() + ')');
        }
        if (e.key === 'F12' || (e.ctrlKey && e.shiftKey && ['I', 'J', 'C'].includes(e.key.toUpperCase()))) {
            e.preventDefault();
            handleViolation('open developer tools');
        }
    });

    // ===== TIMER =====
    const timerDisplay = document.getElementById('timerDisplay');
    const timerEl = document.getElementById('examTimer');

    const timerInterval = setInterval(() => {
        timeLeft--;
        elapsed++;
        document.getElementById('timeSpent').value = elapsed;

        const minutes = Math.floor(timeLeft / 60);
        const seconds = timeLeft % 60;
        timerDisplay.textContent = `${minutes}:${seconds.toString().padStart(2, '0')}`;

        // Color coding
        if (timeLeft <= 60) {
            timerEl.className = 'exam-timer';
            timerEl.style.animation = 'pulse 1s ease-in-out infinite';
        } else if (timeLeft <= 300) {
            timerEl.className = 'exam-timer warning';
        } else {
            timerEl.className = 'exam-timer safe';
        }

        // Auto-submit when time's up
        if (timeLeft <= 0) {
            clearInterval(timerInterval);
            alert('⏰ Time\'s up! Your exam will be submitted now.');
            document.getElementById('examForm').submit();
        }
    }, 1000);

    // ===== QUESTION TRACKING =====
    function markAnswered(idx) {
        answeredSet.add(idx);
        const navBtn = document.getElementById('navBtn' + idx);
        if (navBtn) navBtn.classList.add('answered');

        document.getElementById('answeredCount').textContent = answeredSet.size;

        // Update progress bar
        const progress = (answeredSet.size / totalQuestions) * 100;
        document.getElementById('progressFill').style.width = progress + '%';
    }

    function scrollToQuestion(idx) {
        const el = document.getElementById('question' + idx);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });

        document.querySelectorAll('.q-nav-btn').forEach(b => b.classList.remove('current'));
        const navBtn = document.getElementById('navBtn' + idx);
        if (navBtn) navBtn.classList.add('current');
    }

    function confirmSubmit() {
        const unanswered = totalQuestions - answeredSet.size;
        if (unanswered > 0) {
            return confirm(`You have ${unanswered} unanswered question${unanswered > 1 ? 's' : ''}. Submit anyway?`);
        }
        return confirm('Are you sure you want to submit your exam?');
    }

    // Intersection Observer for navigator
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const idx = parseInt(entry.target.id.replace('question', ''));
                document.querySelectorAll('.q-nav-btn').forEach(b => b.classList.remove('current'));
                const navBtn = document.getElementById('navBtn' + idx);
                if (navBtn && !navBtn.classList.contains('answered')) navBtn.classList.add('current');
            }
        });
    }, { threshold: 0.5 });

    document.querySelectorAll('.question-card').forEach(card => observer.observe(card));

    // Standard beforeunload warning
    window.addEventListener('beforeunload', (e) => {
        e.preventDefault();
        e.returnValue = '';
    });
</script>

<?php include('../includes/footer.php'); ?>