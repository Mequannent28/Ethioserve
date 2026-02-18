<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

// Get resource details
$grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$subject = sanitize($_GET['subject'] ?? '');
$type = sanitize($_GET['type'] ?? 'textbook');
$resource_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

$res_data = null;

try {
    if ($resource_id > 0) {
        $stmt = $pdo->prepare("SELECT * FROM education_resources WHERE id = ?");
        $stmt->execute([$resource_id]);
        $res_data = $stmt->fetch();
    } elseif ($grade > 0 && !empty($subject)) {
        // Find best match
        $stmt = $pdo->prepare("SELECT * FROM education_resources WHERE grade = ? AND subject = ? AND type = ? AND status='active' LIMIT 1");
        $stmt->execute([$grade, $subject, $type]);
        $res_data = $stmt->fetch();
    }
} catch (Exception $e) {
    // Database error
}

if (!$res_data) {
    redirectWithMessage('education.php', 'error', 'Resource not found');
}

// Log view/download if needed (simple tracking)
try {
    // Check if viewed recently to avoid spamming stats
    if (!isset($_COOKIE['viewed_res_' . $res_data['id']])) {
        $stmt = $pdo->prepare("UPDATE education_resources SET views = views + 1 WHERE id = ?");
        $stmt->execute([$res_data['id']]);
        setcookie('viewed_res_' . $res_data['id'], '1', time() + 3600, '/');
    }
} catch (Exception $e) {
}

$page_title = htmlspecialchars($res_data['title']);

// Define hexToRgb function if not exists
if (!function_exists('hexToRgb')) {
    function hexToRgb($hex)
    {
        $hex = str_replace("#", "", $hex);
        if (strlen($hex) == 3) {
            $r = hexdec(substr($hex, 0, 1) . substr($hex, 0, 1));
            $g = hexdec(substr($hex, 1, 1) . substr($hex, 1, 1));
            $b = hexdec(substr($hex, 2, 1) . substr($hex, 2, 1));
        } else {
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 2, 2));
            $b = hexdec(substr($hex, 4, 2));
        }
        return "$r, $g, $b";
    }
}

// Handle Download Request
if (isset($_GET['download']) && $_GET['download'] === 'true' && isset($res_data['file_path'])) {
    $file_path = '../uploads/education/' . $res_data['file_path'];
    if (file_exists($file_path)) {
        // Log download
        try {
            $stmt = $pdo->prepare("INSERT INTO resource_downloads (user_id, resource_id, download_date) VALUES (?, ?, NOW())");
            $stmt->execute([$_SESSION['user_id'], $res_data['id']]);
            $pdo->prepare("UPDATE education_resources SET downloads = downloads + 1 WHERE id = ?")->execute([$res_data['id']]);
        } catch (Exception $e) {
        }

        // Force download
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_path));
        ob_clean();
        flush();
        readfile($file_path);
        exit;
    } else {
        $flash = ['type' => 'error', 'message' => 'File not found on server'];
    }
}

include('../includes/header.php');

$color = "#1565C0"; // Default blue
// Map subject to color
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
if (isset($subject_colors[$res_data['subject']])) {
    $color = $subject_colors[$res_data['subject']];
}

$pdf_url = isset($res_data['file_path']) ? '../uploads/education/' . $res_data['file_path'] : '';
$lms_url = 'lms.php?grade=' . $grade . '&subject=' . urlencode($subject);
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --book-color:
            <?php echo $color; ?>
        ;
        --book-color-rgb:
            <?php echo hexToRgb($color); ?>
        ;
    }

    body {
        background-color: #f0f2f5;
        font-family: 'Outfit', sans-serif;
    }

    .viewer-page-header {
        background: #fff;
        padding: 20px 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        position: relative;
        z-index: 10;
    }

    .back-btn {
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: #f1f5f9;
        color: #64748b;
        transition: 0.3s;
        text-decoration: none;
    }

    .back-btn:hover {
        background: var(--book-color);
        color: #fff;
    }

    .resource-meta {
        font-size: 0.85rem;
        color: #64748b;
    }

    .resource-title {
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        line-height: 1.2;
    }

    .resource-subtitle {
        color: var(--book-color);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .resource-actions {
        display: flex;
        gap: 10px;
    }

    .btn-download {
        background: rgba(var(--book-color-rgb), 0.1);
        color: var(--book-color);
        border: none;
        padding: 10px 20px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: 0.3s;
        text-decoration: none;
    }

    .btn-download:hover {
        background: var(--book-color);
        color: #fff;
    }

    .btn-fullscreen {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 1px solid #e2e8f0;
        background: #fff;
        color: #64748b;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: 0.3s;
        cursor: pointer;
    }

    .btn-fullscreen:hover {
        border-color: var(--book-color);
        color: var(--book-color);
    }

    .pdf-container {
        height: calc(100vh - 160px);
        margin-top: 20px;
        border-radius: 20px;
        overflow: hidden;
        background: #333;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        position: relative;
    }

    .pdf-viewer {
        width: 100%;
        height: 100%;
        border: none;
    }

    .fallback-message {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
        color: #fff;
        width: 80%;
    }

    .lms-promo {
        background: linear-gradient(135deg, #1e1b4b, #4338ca);
        border-radius: 20px;
        padding: 25px;
        color: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        align-items: center;
    }

    .lms-btn {
        background: #fff;
        color: #4338ca;
        padding: 10px 25px;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none;
        margin-top: 15px;
        transition: 0.3s;
        display: inline-block;
    }

    .lms-btn:hover {
        background: #e0e7ff;
        transform: translateY(-2px);
    }

    @media (max-width: 768px) {
        .resource-title {
            font-size: 1.2rem;
        }

        .pdf-container {
            height: 70vh;
        }

        .resource-actions {
            display: none;
            /* Hide on mobile header to save space, show fab instead? */
        }
    }
</style>

<div class="viewer-page-header">
    <div class="container-fluid px-4">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-3">
                <a href="education.php?grade=<?php echo $grade; ?>&tab=<?php echo ($type === 'teacher_guide' ? 'teachers' : 'textbooks'); ?>"
                    class="back-btn">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <div>
                    <h1 class="resource-title"><?php echo htmlspecialchars($res_data['title']); ?></h1>
                    <div class="d-flex align-items-center gap-2 mt-1">
                        <span class="badge rounded-pill"
                            style="background:var(--book-color); color:#fff; font-weight:normal; font-size:0.7rem;">
                            Grade <?php echo $grade; ?>
                        </span>
                        <span class="resource-subtitle"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></span>
                        <span class="text-muted small">• <?php echo $res_data['edition']; ?> Edition</span>
                        <span class="text-muted small">• <?php echo $res_data['pages']; ?> Pages</span>
                    </div>
                </div>
            </div>

            <div class="resource-actions d-none d-md-flex">
                <a href="<?php echo $lms_url; ?>" class="btn btn-outline-dark rounded-pill px-3 fw-bold"
                    style="font-size:0.9rem;">
                    <i class="fas fa-brain me-2 text-warning"></i>Practice Quiz
                </a>
                <a href="?<?php echo http_build_query($_GET); ?>&download=true" class="btn-download">
                    <i class="fas fa-download"></i> Download PDF
                </a>
                <button class="btn-fullscreen" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                </button>
            </div>

            <!-- Mobile Action -->
            <div class="d-md-none">
                <a href="?<?php echo http_build_query($_GET); ?>&download=true" class="btn btn-primary rounded-circle"
                    style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--book-color);border:none;">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="pdf-container" id="pdfContainer">
                <?php if (file_exists($pdf_url)): ?>
                    <object data="<?php echo $pdf_url; ?>" type="application/pdf" class="pdf-viewer">
                        <div class="fallback-message">
                            <i class="fas fa-file-pdf mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                            <h4>PDF Preview Not Available</h4>
                            <p class="mb-4">Your browser doesn't support embedded PDFs.</p>
                            <a href="?<?php echo http_build_query($_GET); ?>&download=true"
                                class="btn btn-light rounded-pill px-4 fw-bold">
                                Download File
                            </a>
                        </div>
                    </object>
                <?php else: ?>
                    <div class="fallback-message">
                        <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem; color: #ff9800;"></i>
                        <h4>File Not Found</h4>
                        <p>The requested resource file is missing from the server.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <div class="col-lg-3 d-none d-lg-block">
            <!-- Sidebar content -->
            <div class="mt-4">
                <div class="lms-promo mb-4">
                    <i class="fas fa-brain mb-3 text-warning" style="font-size: 2.5rem;"></i>
                    <h5 class="fw-bold">Test Your Knowledge</h5>
                    <p class="small text-white-50 mb-0">Take an interactive quiz based on this textbook's chapters.</p>
                    <a href="<?php echo $lms_url; ?>" class="lms-btn">Start Quiz</a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3">
                    <h6 class="fw-bold mb-3">Related Resources</h6>
                    <!-- Could list other subjects here -->
                    <p class="text-muted small">More grade <?php echo $grade; ?> materials available in the dashboard.
                    </p>
                    <a href="education.php?grade=<?php echo $grade; ?>"
                        class="btn btn-outline-primary btn-sm rounded-pill w-100">View
                        All</a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function toggleFullscreen() {
        const elem = document.getElementById("pdfContainer");
        if (!document.fullscreenElement) {
            if (elem.requestFullscreen) {
                elem.requestFullscreen();
            } else if (elem.webkitRequestFullscreen) {
                /* Safari */
                elem.webkitRequestFullscreen();
            } else if (elem.msRequestFullscreen) {
                /* IE11 */
                elem.msRequestFullscreen();
            }
        } else {
            if (document.exitFullscreen) {
                document.exitFullscreen();
            }
        }
    }
</script>

<?php include('../includes/footer.php'); ?>