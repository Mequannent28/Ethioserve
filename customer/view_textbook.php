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
if (isset($_GET['download']) && $_GET['download'] === 'true' && (isset($res_data['file_path']) || isset($res_data['file_url']))) {
    $raw_path = $res_data['file_url'] ?? $res_data['file_path'];

    if (strpos($raw_path, 'http') === 0) {
        // Redir to external URL for download if it's a full URL
        header("Location: " . $raw_path);
        exit;
    }

    $file_path = '../uploads/education/' . $raw_path;

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

// Fetch related resources for sidebar and mobile view
$related = [];
try {
    $rel_stmt = $pdo->prepare("SELECT * FROM education_resources WHERE grade = ? AND id != ? AND status='active' LIMIT 5");
    $rel_stmt->execute([$grade, $res_data['id']]);
    $related = $rel_stmt->fetchAll();
} catch (Exception $e) {
    // Silent fail
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
if (isset($subject_colors[$res_data['subject']])) {
    $color = $subject_colors[$res_data['subject']];
}

$raw_url = $res_data['file_url'] ?? $res_data['file_path'] ?? '';
$pdf_url = (strpos($raw_url, 'http') === 0) ? $raw_url : '../uploads/education/' . $raw_url;

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

    .transition-all {
        transition: all 0.3s ease;
    }

    .hover-bg-light:hover {
        background-color: #f8fafc;
        transform: translateX(5px);
    }

    .fw-600 {
        font-weight: 600;
    }

    .viewer-page-header {
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(15px);
        -webkit-backdrop-filter: blur(15px);
        padding: 15px 0;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        position: sticky;
        top: 0;
        z-index: 1000;
        border-bottom: 1px solid rgba(255, 255, 255, 0.3);
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
        transform: translateX(-3px);
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
        font-size: 1.5rem;
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

    .btn-action-view {
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

    .btn-action-view:hover {
        background: var(--book-color);
        color: #fff;
        transform: translateY(-2px);
    }

    .btn-icon-circle {
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

    .btn-icon-circle:hover {
        border-color: var(--book-color);
        color: var(--book-color);
        background: rgba(var(--book-color-rgb), 0.05);
    }

    .video-container,
    .pdf-container {
        height: calc(100vh - 180px);
        margin-top: 20px;
        border-radius: 24px;
        overflow: hidden;
        background: #000;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        position: relative;
    }

    .pdf-viewer,
    .video-player {
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
        border-radius: 24px;
        padding: 25px;
        color: #fff;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        align-items: center;
        position: relative;
        overflow: hidden;
    }

    .lms-promo::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        pointer-events: none;
    }

    .lms-btn {
        background: #fff;
        color: #4338ca;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 700;
        text-decoration: none;
        margin-top: 15px;
        transition: 0.3s;
        display: inline-block;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
    }

    .lms-btn:hover {
        background: #e0e7ff;
        transform: translateY(-2px);
        color: #3730a3;
    }

    @media (max-width: 768px) {
        .resource-title {
            font-size: 1.1rem;
            max-width: 200px;
            white-space: nowrap;
            overflow: hidden;
            text-truncate: ellipsis;
        }

        .video-container,
        .pdf-container {
            height: 60vh;
        }

        .resource-actions {
            gap: 5px;
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
                        <?php if (!empty($res_data['edition']) && $res_data['edition'] != '0'): ?>
                            <span class="text-muted small">• <?php echo $res_data['edition']; ?> Edition</span>
                        <?php endif; ?>
                        <?php if (!empty($res_data['pages']) && $res_data['pages'] > 0): ?>
                            <span class="text-muted small">• <?php echo $res_data['pages']; ?> Pages</span>
                        <?php else: ?>
                            <span class="text-muted small">• Full Digital Edition</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="resource-actions">
                <a href="<?php echo $lms_url; ?>"
                    class="btn btn-outline-dark rounded-pill px-3 fw-bold d-none d-lg-flex align-items-center"
                    style="font-size:0.85rem;">
                    <i class="fas fa-brain me-2 text-warning"></i>Practice Quiz
                </a>

                <?php if ($res_data['type'] !== 'video'): ?>
                    <a href="?<?php echo http_build_query($_GET); ?>&download=true"
                        class="btn-action-view d-none d-md-flex">
                        <i class="fas fa-download"></i> Download PDF
                    </a>
                <?php endif; ?>

                <button class="btn-icon-circle d-none d-md-flex" onclick="shareResource()" title="Share">
                    <i class="fas fa-share-alt"></i>
                </button>

                <button class="btn-icon-circle" onclick="toggleFullscreen()" title="Fullscreen">
                    <i class="fas fa-expand"></i>
                </button>

                <!-- Mobile Action -->
                <div class="d-md-none d-flex gap-2">
                    <?php if ($res_data['type'] !== 'video'): ?>
                        <a href="?<?php echo http_build_query($_GET); ?>&download=true"
                            class="btn btn-primary rounded-circle shadow-sm"
                            style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;background:var(--book-color);border:none;">
                            <i class="fas fa-download"></i>
                        </a>
                    <?php endif; ?>
                    <button class="btn btn-light rounded-circle shadow-sm border" style="width:40px;height:40px;"
                        onclick="shareResource()">
                        <i class="fas fa-share-alt"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid px-2 px-md-4 mb-5">
    <div class="row g-4">
        <div class="col-lg-9">
            <div class="pdf-container" id="pdfContainer">
                <?php
                if ($res_data['type'] === 'video'):
                    $video_id = $res_data['video_id'];
                    if (empty($video_id) && !empty($res_data['video_url'])) {
                        // Extract ID from URL if needed
                        parse_str(parse_url($res_data['video_url'], PHP_URL_QUERY), $vars);
                        $video_id = $vars['v'] ?? '';
                    }
                    ?>
                    <?php if (!empty($video_id)): ?>
                        <iframe src="https://www.youtube.com/embed/<?php echo $video_id; ?>?autoplay=1" class="video-player"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                            allowfullscreen></iframe>
                    <?php else: ?>
                        <div class="fallback-message">
                            <i class="fas fa-play-circle mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                            <h4>Invalid Video Source</h4>
                            <p>We couldn't load the video for this resource.</p>
                        </div>
                    <?php endif; ?>
                <?php else:
                    $is_external = strpos($pdf_url, 'http') === 0;
                    if (!empty($pdf_url)):
                        if ($is_external):
                            // Use Google Docs Viewer for external PDFs to bypass CORS/Iframe blocks
                            $viewer_url = "https://docs.google.com/viewer?url=" . urlencode($pdf_url) . "&embedded=true";
                            ?>
                            <iframe src="<?php echo $viewer_url; ?>" class="pdf-viewer" style="background: #fff;"></iframe>
                        <?php else: ?>
                            <object data="<?php echo $pdf_url; ?>" type="application/pdf" class="pdf-viewer">
                                <div class="fallback-message">
                                    <i class="fas fa-file-pdf mb-3" style="font-size: 3rem; opacity: 0.5;"></i>
                                    <h4>PDF Preview Not Available</h4>
                                    <p class="mb-4">Your browser doesn't support embedded PDFs or the file is too large.</p>
                                    <a href="?<?php echo http_build_query($_GET); ?>&download=true"
                                        class="btn btn-light rounded-pill px-4 fw-bold shadow-sm">
                                        <i class="fas fa-download me-2"></i>Download File
                                    </a>
                                </div>
                            </object>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="fallback-message">
                            <i class="fas fa-exclamation-triangle mb-3" style="font-size: 3rem; color: #ff9800;"></i>
                            <h4>File Not Found</h4>
                            <p>The requested resource file is missing from the server.</p>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- Mobile Related Content (Extra Discovery) -->
            <div class="mt-5 d-lg-none">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-0 pt-4 pb-0 px-4">
                        <h5 class="fw-bold mb-0">Discover More Grade <?php echo $grade; ?> Resources</h5>
                    </div>
                    <div class="card-body px-4 pb-4">
                        <div class="row g-3 mt-1">
                            <?php
                            // Re-use related logic or fetch again if context lost, but showing different UI
                            if (isset($related) && count($related) > 0):
                                foreach (array_slice($related, 0, 3) as $rel):
                                    $rel_color = $subject_colors[$rel['subject']] ?? '#1565C0';
                                    $rel_url = "view_textbook.php?grade=$grade&subject=" . urlencode($rel['subject']) . "&type=" . $rel['type'] . "&id=" . $rel['id'];
                                    ?>
                                    <div class="col-12">
                                        <a href="<?php echo $rel_url; ?>"
                                            class="d-flex align-items-center gap-3 p-3 rounded-4 text-decoration-none border hover-bg-light transition-all">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                                                style="width: 50px; height: 50px; background: <?php echo $rel_color; ?>15; color: <?php echo $rel_color; ?>;">
                                                <i
                                                    class="fas fa-<?php echo ($rel['type'] == 'teacher_guide' ? 'chalkboard-teacher' : ($rel['type'] == 'video' ? 'play-circle' : 'book')); ?> fs-4"></i>
                                            </div>
                                            <div class="overflow-hidden">
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <?php echo htmlspecialchars($rel['subject']); ?></h6>
                                                <span
                                                    class="text-muted small"><?php echo ucfirst(str_replace('_', ' ', $rel['type'])); ?></span>
                                            </div>
                                            <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                                        </a>
                                    </div>
                                    <?php
                                endforeach;
                            endif;
                            ?>
                        </div>
                        <a href="education.php?grade=<?php echo $grade; ?>"
                            class="btn btn-primary w-100 rounded-pill mt-4 fw-bold py-2">
                            Explore Education Dashboard
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-3 d-none d-lg-block">
            <!-- Sidebar content -->
            <div class="sticky-top" style="top: 20px; z-index: 5;">
                <div class="lms-promo mb-4 shadow-sm">
                    <i class="fas fa-brain mb-3 text-warning" style="font-size: 2.5rem;"></i>
                    <h5 class="fw-bold">Test Your Knowledge</h5>
                    <p class="small text-white-50 mb-0">Take an interactive quiz based on this textbook's chapters.</p>
                    <a href="<?php echo $lms_url; ?>" class="lms-btn">Start Quiz</a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="card-header bg-white border-0 pt-3 pb-0">
                        <h6 class="fw-bold mb-0">Related Grade <?php echo $grade; ?> Resources</h6>
                    </div>
                    <div class="card-body">
                        <?php
                        if ($related) {
                            foreach ($related as $rel) {
                                $rel_color = $subject_colors[$rel['subject']] ?? '#1565C0';
                                $rel_icon = $subject_icons[$rel['subject']] ?? 'book';
                                $rel_url = "view_textbook.php?grade=$grade&subject=" . urlencode($rel['subject']) . "&type=" . $rel['type'] . "&id=" . $rel['id'];
                                ?>
                                <a href="<?php echo $rel_url; ?>"
                                    class="d-flex align-items-center gap-3 p-2 rounded-3 text-decoration-none hover-bg-light mb-2 transition-all">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px; background: <?php echo $rel_color; ?>15; color: <?php echo $rel_color; ?>;">
                                        <i
                                            class="fas fa-<?php echo ($rel['type'] == 'teacher_guide' ? 'chalkboard-teacher' : ($rel['type'] == 'video' ? 'play-circle' : 'book')); ?> fa-sm"></i>
                                    </div>
                                    <div class="overflow-hidden">
                                        <h6 class="small fw-bold mb-0 text-dark text-truncate">
                                            <?php echo htmlspecialchars($rel['subject']); ?></h6>
                                        <span class="text-muted"
                                            style="font-size: 0.7rem;"><?php echo ucfirst(str_replace('_', ' ', $rel['type'])); ?></span>
                                    </div>
                                </a>
                                <?php
                            }
                        } else {
                            echo '<p class="text-muted small">No other resources found for this grade.</p>';
                        }
                        ?>

                        <hr class="my-3 opacity-10">

                        <a href="education.php?grade=<?php echo $grade; ?>"
                            class="btn btn-outline-primary btn-sm rounded-pill w-100 fw-600">
                            View All Grade <?php echo $grade; ?> Materials
                        </a>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-4 p-3 bg-light">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <i class="fas fa-info-circle text-primary"></i>
                        <h6 class="fw-bold mb-0 small">Need Help?</h6>
                    </div>
                    <p class="text-muted mb-0" style="font-size: 0.75rem;">
                        If the PDF is not loading, try downloading it or using a different browser.
                    </p>
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

    function shareResource() {
        if (navigator.share) {
            navigator.share({
                title: '<?php echo addslashes($res_data['title']); ?>',
                text: 'Check out this Grade <?php echo $grade; ?> <?php echo $subject; ?> resource on EthioServe Education.',
                url: window.location.href
            }).then(() => {
                console.log('Thanks for sharing!');
            }).catch(console.error);
        } else {
            // Fallback: Copy to clipboard
            const el = document.createElement('textarea');
            el.value = window.location.href;
            document.body.appendChild(el);
            el.select();
            document.execCommand('copy');
            document.body.removeChild(el);
            alert('Link copied to clipboard!');
        }
    }
</script>

<?php include('../includes/footer.php'); ?>