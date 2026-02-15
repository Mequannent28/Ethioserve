<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Helper to convert hex to RGB for background-opacity
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
        padding: 3rem 0;
        border-bottom: 1px solid #e2e8f0;
        margin-bottom: 3rem;
    }

    .subject-card-branded {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .subject-card-branded:hover {
        transform: scale(1.05);
        box-shadow: 0 10px 25px rgba(var(--book-color-rgb), 0.3);
    }

    .book-meta-badge {
        background: #f1f5f9;
        color: #475569;
        padding: 8px 18px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.85rem;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        border: 1px solid #e2e8f0;
    }


    .viewer-wrapper {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e9ecef;
        position: relative;
        min-height: 600px;
        height: calc(100vh - 120px);
        margin-bottom: 3rem;
    }

    @media (max-width: 768px) {
        .viewer-wrapper {
            height: 70vh;
            border-radius: 12px;
        }

        .viewer-page-header {
            padding: 1.5rem 0;
        }

        .book-actions-mobile {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
    }

    .pdf-object-container {
        width: 100%;
        height: 100%;
    }

    .pdf-object-container object,
    .pdf-object-container embed,
    .pdf-object-container iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        z-index: 100;
        transition: opacity 0.5s ease-out;
    }

    .custom-loader {
        width: 48px;
        height: 48px;
        border: 5px solid #f3f3f3;
        border-top: 5px solid var(--book-color);
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    .btn-exam {
        background: linear-gradient(135deg, #FFD600 0%, #FFB300 100%);
        color: #000;
        border: none;
        font-weight: 700;
        border-radius: 50px;
        padding: 10px 25px;
        box-shadow: 0 4px 15px rgba(255, 214, 0, 0.3);
        transition: all 0.3s ease;
    }

    .btn-exam:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(255, 214, 0, 0.4);
        color: #000;
    }
</style>

<div class="viewer-page-header shadow-sm">
    <div class="container">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb mb-0 px-3 py-2 rounded-pill bg-light d-inline-flex border">
                <li class="breadcrumb-item small"><a href="education.php">Education Hub</a></li>
                <li class="breadcrumb-item small"><a href="education.php?grade=<?php echo $grade; ?>">Grade
                        <?php echo $grade; ?></a></li>
                <li class="breadcrumb-item small active"><?php echo htmlspecialchars($subject); ?></li>
            </ol>
        </nav>

        <div class="row align-items-center g-4">
            <div class="col-md-7">
                <div class="d-flex align-items-center gap-4">
                    <div class="subject-card-branded shadow-sm"
                        style="background:var(--book-color); min-width: 80px; height: 80px; border-radius: 22px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 2.2rem;">
                        <i class="fas fa-<?php echo $icon; ?>"></i>
                    </div>
                    <div>
                        <h1 class="h2 fw-bold mb-1 text-dark"><?php echo htmlspecialchars($page_title); ?></h1>
                        <div class="d-flex gap-3 mt-1 flex-wrap">
                            <span class="text-muted small"><i class="fas fa-shield-alt text-success me-1"></i> Official
                                Curriculum</span>
                            <span class="text-muted small"><i class="fas fa-check-circle text-primary me-1"></i>
                                Verified PDF Resource</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <div class="d-flex gap-2 justify-content-md-end flex-wrap">
                    <button onclick="toggleFullscreen()" class="btn btn-light border rounded-pill px-4 py-2 shadow-sm"
                        title="Expand View">
                        <i class="fas fa-expand me-2"></i> Fullscreen
                    </button>
                    <a href="lms.php?grade=<?php echo $grade; ?>&subject=<?php echo urlencode($subject); ?>"
                        class="btn btn-exam px-4 py-2">
                        <i class="fas fa-brain me-2"></i> Take Practice Exam
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container pb-5">
    <div class="row">
        <div class="col-lg-12">
            <div class="viewer-wrapper" id="fullscreenTarget">
                <!-- Viewer Toolbar -->
                <div
                    class="bg-dark text-white-50 px-4 py-3 d-flex align-items-center justify-content-between border-bottom border-secondary">
                    <div class="d-flex align-items-center gap-3">
                        <span class="small fw-bold text-white"><i class="fas fa-book-reader me-2 text-warning"></i>
                            Reading Mode</span>
                        <div class="vr bg-secondary d-none d-sm-block"></div>
                        <span class="small d-none d-sm-inline">Grade <?php echo $grade; ?> —
                            <?php echo $subject; ?></span>
                    </div>
                    <?php if (!empty($pdf_url)): ?>
                        <a href="<?php echo $pdf_url; ?>" download
                            class="btn btn-sm btn-outline-light rounded-pill px-3 border-secondary">
                            <i class="fas fa-file-download me-1"></i> Download for Offline
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($pdf_url)): ?>
                    <div class="loading-overlay" id="loadingOverlay">
                        <div class="custom-loader"></div>
                        <h6 class="mt-4 fw-bold text-dark">Preparing High-Definition Reader</h6>
                        <p class="text-muted small px-4 text-center">Optimizing the layout for your device...</p>
                    </div>

                    <div class="pdf-object-container">
                        <object data="<?php echo $pdf_url; ?>" type="application/pdf" id="pdfViewerObj" class="w-100 h-100">
                            <embed src="<?php echo $pdf_url; ?>" type="application/pdf" class="w-100 h-100">
                            <div
                                class="p-5 text-center bg-white h-100 d-flex flex-column align-items-center justify-content-center">
                                <i class="fas fa-file-pdf fa-4x text-danger mb-4"></i>
                                <h3 class="fw-bold">Ready to Read?</h3>
                                <p class="text-muted mb-4 px-5">Your browser doesn't have a built-in PDF viewer. Click below
                                    to open and read the Grade <?php echo $grade; ?>     <?php echo $subject; ?> textbook.</p>
                                <a href="<?php echo $pdf_url; ?>" target="_blank"
                                    class="btn btn-success btn-lg rounded-pill px-5">
                                    <i class="fas fa-external-link-alt me-2"></i> Open Textbook Now
                                </a>
                            </div>
                        </object>
                    </div>
                <?php else: ?>
                    <div
                        class="d-flex flex-column align-items-center justify-content-center h-100 p-5 text-center bg-white">
                        <div class="bg-light rounded-circle p-4 mb-4"
                            style="width: 120px; height: 120px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-<?php echo $icon; ?> fa-4x" style="color:var(--book-color); opacity: 0.3;"></i>
                        </div>
                        <h2 class="fw-bold">Book Not Found</h2>
                        <p class="text-muted mx-auto" style="max-width: 400px;">The digital version for this subject is
                            currently unavailable in our database. Please check back later or browse other grades.</p>
                        <a href="education.php?grade=<?php echo $grade; ?>"
                            class="btn btn-book-primary rounded-pill px-5 mt-4 text-white"
                            style="background:var(--book-color)">Back to Grade <?php echo $grade; ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Help / Footer Info -->
    <div class="text-center mt-4">
        <p class="text-muted small">Can't see the book? <a href="#"
                class="text-success fw-bold text-decoration-none">Contact Support</a> or <a
                href="<?php echo $pdf_url; ?>" target="_blank" class="text-success fw-bold text-decoration-none">Open
                Direct Link</a></p>
    </div>
</div>

<script>
    function toggleFullscreen() {
        const elem = document.getElementById('fullscreenTarget');
        if (!document.fullscreenElement) {
            elem.requestFullscreen().catch(err => {
                alert(`Error: ${err.message}`);
            });
        } else {
            document.exitFullscreen();
        }
    }

    // Hide loading overlay when PDF loads
    const viewer = document.getElementById('pdfViewerObj');
    if (viewer) {
        viewer.addEventListener('load', function () {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => { overlay.style.display = 'none'; }, 500);
            }
        });

        // Safety fallback timer
        setTimeout(() => {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay && overlay.style.display !== 'none') {
                overlay.style.opacity = '0';
                setTimeout(() => { overlay.style.display = 'none'; }, 500);
            }
        }, 8000);
    }
</script>

<?php include('../includes/footer.php'); ?>