<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$subject = sanitize($_GET['subject'] ?? '');
$type = sanitize($_GET['type'] ?? 'textbook'); // textbook or teacher_guide

if ($grade < 1 || $grade > 12 || empty($subject)) {
    header('Location: education.php');
    exit;
}

// Fetch from database
$db_type = ($type === 'teacher_guide') ? 'teacher_guide' : 'textbook';
$page_title = ($type === 'teacher_guide')
    ? "{$subject} — Grade {$grade} Teacher Guide"
    : "{$subject} — Grade {$grade} Student Textbook";

$pdf_url = '';
$resource = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM education_resources WHERE grade=? AND subject=? AND type=? AND status='active' LIMIT 1");
    $stmt->execute([$grade, $subject, $db_type]);
    $resource = $stmt->fetch();
    if ($resource && !empty($resource['file_url'])) {
        $pdf_url = $resource['file_url'];
    }
} catch (Exception $e) {
}

$pages_count = $resource['pages'] ?? 0;
$units_count = $resource['units'] ?? 0;
$edition = $resource['edition'] ?? '2023';
$description = $resource['description'] ?? '';

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
$color = $subject_colors[$subject] ?? '#1565C0';

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
$icon = $subject_icons[$subject] ?? 'book';

// Track downloads
if ($resource) {
    try {
        $pdo->prepare("UPDATE education_resources SET downloads = downloads + 1 WHERE id = ?")->execute([$resource['id']]);
    } catch (Exception $e) {
    }
}

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --book-color:
            <?php echo $color; ?>
        ;
    }

    body {
        font-family: 'Outfit', sans-serif;
        margin: 0;
        background: #0f1724;
    }

    .viewer-topbar {
        background: linear-gradient(90deg, #0f1724, #1a2332);
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        padding: 10px 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
        backdrop-filter: blur(12px);
    }

    .viewer-topbar-left {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .viewer-back {
        color: rgba(255, 255, 255, 0.7);
        text-decoration: none;
        font-size: .88rem;
        display: flex;
        align-items: center;
        gap: 6px;
        padding: 8px 16px;
        border-radius: 10px;
        transition: .3s;
        background: rgba(255, 255, 255, 0.06);
    }

    .viewer-back:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
    }

    .viewer-title-icon {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: .95rem;
        background: var(--book-color);
    }

    .viewer-title h6 {
        color: #fff;
        margin: 0;
        font-weight: 700;
        font-size: .92rem;
    }

    .viewer-title span {
        color: rgba(255, 255, 255, 0.5);
        font-size: .72rem;
    }

    .viewer-meta {
        display: flex;
        gap: 12px;
        align-items: center;
        margin-top: 3px;
    }

    .viewer-meta .badge-info {
        background: rgba(255, 255, 255, 0.1);
        color: rgba(255, 255, 255, 0.7);
        padding: 2px 10px;
        border-radius: 20px;
        font-size: .65rem;
        font-weight: 600;
    }

    .viewer-actions {
        display: flex;
        gap: 8px;
    }

    .viewer-btn {
        padding: 8px 18px;
        border-radius: 50px;
        border: 1px solid rgba(255, 255, 255, 0.15);
        background: transparent;
        color: rgba(255, 255, 255, 0.8);
        font-size: .78rem;
        font-weight: 600;
        cursor: pointer;
        transition: .3s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .viewer-btn:hover {
        background: rgba(255, 255, 255, 0.1);
        color: #fff;
    }

    .viewer-btn-primary {
        background: var(--book-color);
        border-color: var(--book-color);
        color: #fff;
    }

    .viewer-btn-primary:hover {
        opacity: .9;
        color: #fff;
    }

    .viewer-container {
        position: fixed;
        top: 62px;
        left: 0;
        right: 0;
        bottom: 0;
        background: #1a2332;
    }

    .viewer-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .viewer-fallback {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #fff;
        text-align: center;
        padding: 40px;
    }

    .viewer-fallback-icon {
        width: 100px;
        height: 100px;
        border-radius: 28px;
        background: rgba(255, 255, 255, 0.06);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2.8rem;
        color: var(--book-color);
        margin-bottom: 24px;
        animation: pulse 2s ease-in-out infinite;
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

    .viewer-fallback h3 {
        font-weight: 800;
        margin-bottom: 8px;
    }

    .viewer-fallback p {
        color: rgba(255, 255, 255, 0.5);
        max-width: 450px;
        font-size: .9rem;
        line-height: 1.6;
    }

    .loading-spinner {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
        flex-direction: column;
        gap: 16px;
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 5;
        background: #1a2332;
    }

    .loading-spinner .spinner {
        width: 50px;
        height: 50px;
        border: 4px solid rgba(255, 255, 255, 0.1);
        border-top-color: var(--book-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    .loading-spinner span {
        color: rgba(255, 255, 255, 0.5);
        font-size: .85rem;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    @media(max-width:768px) {
        .viewer-topbar {
            padding: 8px 12px;
            flex-wrap: wrap;
            gap: 8px;
        }

        .viewer-title h6 {
            font-size: .8rem;
        }

        .viewer-meta {
            display: none;
        }

        .viewer-actions {
            gap: 4px;
        }

        .viewer-btn {
            padding: 6px 12px;
            font-size: .72rem;
        }

        .viewer-container {
            top: 56px;
        }
    }
</style>

<div class="viewer-topbar">
    <div class="viewer-topbar-left">
        <a href="education.php?grade=<?php echo $grade; ?>&tab=<?php echo $type === 'teacher_guide' ? 'teachers' : 'textbooks'; ?>"
            class="viewer-back">
            <i class="fas fa-arrow-left"></i> Back
        </a>
        <div class="viewer-title-icon">
            <i class="fas fa-<?php echo $icon; ?>"></i>
        </div>
        <div class="viewer-title">
            <h6><?php echo htmlspecialchars($page_title); ?></h6>
            <div class="viewer-meta">
                <span><i class="fas fa-graduation-cap me-1"></i>Ethiopian New Curriculum · MoE</span>
                <?php if ($pages_count > 0): ?>
                    <span class="badge-info"><i class="fas fa-file me-1"></i><?php echo $pages_count; ?> Pages</span>
                <?php endif; ?>
                <?php if ($units_count > 0): ?>
                    <span class="badge-info"><i class="fas fa-list me-1"></i><?php echo $units_count; ?> Units</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="viewer-actions">
        <?php if (!empty($pdf_url)): ?>
            <a href="<?php echo htmlspecialchars($pdf_url); ?>" target="_blank" class="viewer-btn" title="Download PDF">
                <i class="fas fa-download"></i> <span class="d-none d-md-inline">Download</span>
            </a>
            <button onclick="toggleFullscreen()" class="viewer-btn" title="Fullscreen">
                <i class="fas fa-expand"></i>
            </button>
        <?php endif; ?>
        <a href="lms.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subject); ?>" class="viewer-btn"
            style="background:linear-gradient(135deg,#6366f1,#818cf8);border-color:#6366f1;color:#fff;"
            title="Take Exam">
            <i class="fas fa-brain"></i> <span class="d-none d-md-inline">Take Exam</span>
        </a>
        <a href="education.php?grade=<?php echo $grade; ?>" class="viewer-btn viewer-btn-primary">
            <i class="fas fa-th-large"></i> <span class="d-none d-md-inline">All Subjects</span>
        </a>
    </div>
</div>

<div class="viewer-container" id="viewerContainer">
    <?php if (!empty($pdf_url)): ?>
        <div class="loading-spinner" id="loadingSpinner">
            <div class="spinner"></div>
            <span>Loading <?php echo htmlspecialchars($subject); ?> textbook...</span>
        </div>
        <iframe id="pdfFrame" src="https://docs.google.com/viewer?url=<?php echo urlencode($pdf_url); ?>&embedded=true"
            onload="document.getElementById('loadingSpinner').style.display='none';" allowfullscreen>
        </iframe>
    <?php else: ?>
        <div class="viewer-fallback">
            <div class="viewer-fallback-icon">
                <i class="fas fa-<?php echo $icon; ?>"></i>
            </div>
            <h3><?php echo htmlspecialchars($subject); ?></h3>
            <p>This <?php echo $type === 'teacher_guide' ? 'teacher guide' : 'textbook'; ?> for Grade <?php echo $grade; ?>
                is not yet available in the database. Please ask the admin to run the education seeder or add this resource
                manually.</p>
            <div class="d-flex gap-2 mt-3 flex-wrap justify-content-center">
                <a href="education.php?grade=<?php echo $grade; ?>" class="viewer-btn viewer-btn-primary">
                    <i class="fas fa-arrow-left"></i> Browse Other Subjects
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
    function toggleFullscreen() {
        const container = document.getElementById('viewerContainer');
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            container.requestFullscreen();
        }
    }

    // Keyboard shortcut: Escape to go back
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            window.location.href = 'education.php?grade=<?php echo $grade; ?>&tab=<?php echo $type === 'teacher_guide' ? 'teachers' : 'textbooks'; ?>';
        }
    });
</script>

<?php include('../includes/footer.php'); ?>