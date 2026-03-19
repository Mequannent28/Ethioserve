<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('teacher');

$teacher_user_id = getCurrentUserId();
$teacher_id = $pdo->prepare("SELECT id FROM sms_teachers WHERE user_id = ?");
$teacher_id->execute([$teacher_user_id]);
$teacher_id = $teacher_id->fetchColumn();

$class_id    = (int)($_GET['class_id'] ?? 0);
$subject_id  = (int)($_GET['subject_id'] ?? 0);

// ACTION: Add Chapter/Material
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_chapter') {
        $title          = sanitize($_POST['title']);
        $chapter_number = (int)$_POST['chapter_number'];
        $content        = $_POST['content'];   // rich text, no strip
        $video_url      = sanitize($_POST['video_url'] ?? '');
        $c_id           = (int)$_POST['class_id'];
        $s_id           = (int)$_POST['subject_id'];

        $document_path = null;
        $local_video_path = null;

        // Handle File Upload
        if (isset($_FILES['local_file']) && $_FILES['local_file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/lms/docs/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = pathinfo($_FILES['local_file']['name'], PATHINFO_EXTENSION);
            $file_name = 'lms_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['local_file']['tmp_name'], $upload_dir . $file_name)) {
                $document_path = $file_name;
            }
        }

        // Handle Video Upload
        if (isset($_FILES['local_video']) && $_FILES['local_video']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/lms/videos/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $file_ext = pathinfo($_FILES['local_video']['name'], PATHINFO_EXTENSION);
            $file_name = 'lms_vid_' . time() . '_' . rand(1000, 9999) . '.' . $file_ext;
            if (move_uploaded_file($_FILES['local_video']['tmp_name'], $upload_dir . $file_name)) {
                $local_video_path = $file_name;
            }
        }

        // Get grade & subject name from DB
        $cls = $pdo->prepare("SELECT class_name FROM sms_classes WHERE id = ?");
        $cls->execute([$c_id]);
        $class_name = $cls->fetchColumn();

        $sub = $pdo->prepare("SELECT subject_name FROM sms_subjects WHERE id = ?");
        $sub->execute([$s_id]);
        $subject_name = $sub->fetchColumn();

        // Extract grade from class_name
        preg_match('/\d+/', $class_name, $matches);
        $grade_num = isset($matches[0]) ? (int)$matches[0] : 0;

        $stmt = $pdo->prepare("INSERT INTO lms_chapters (grade, subject, chapter_number, title, content, video_url, class_id, teacher_id, document_path, local_video_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$grade_num, $subject_name, $chapter_number, $title, $content, $video_url, $c_id, $teacher_id, $document_path, $local_video_path]);

        setFlashMessage('Chapter uploaded successfully!', 'success');
        header("Location: manage_courses.php?class_id=$c_id&subject_id=$s_id");
        exit;
    }

    if ($action === 'delete_chapter') {
        $ch_id = (int)$_POST['chapter_id'];
        $pdo->prepare("DELETE FROM lms_chapters WHERE id = ?")->execute([$ch_id]);
        setFlashMessage('Chapter deleted.', 'info');
        header("Location: manage_courses.php?class_id=$class_id&subject_id=$subject_id");
        exit;
    }
}

// Get teacher's assigned classes
$classes_stmt = $pdo->prepare("
    SELECT cs.class_id, cs.subject_id, c.class_name, c.section, s.subject_name
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    JOIN sms_subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ?
");
$classes_stmt->execute([$teacher_id]);
$my_assignments = $classes_stmt->fetchAll();

// Get chapters for selected class/subject
$chapters = [];
$current_subject_name = '';
if ($class_id && $subject_id) {
    $sub = $pdo->prepare("SELECT subject_name FROM sms_subjects WHERE id = ?");
    $sub->execute([$subject_id]);
    $current_subject_name = $sub->fetchColumn();

    $stmt = $pdo->prepare("SELECT * FROM lms_chapters WHERE subject = ? AND class_id = ? ORDER BY chapter_number");
    $stmt->execute([$current_subject_name, $class_id]);
    $chapters = $stmt->fetchAll();

    // Extract grade from class_name to fetch system resources
    $cls = $pdo->prepare("SELECT class_name FROM sms_classes WHERE id = ?");
    $cls->execute([$class_id]);
    $cname = $cls->fetchColumn();
    preg_match('/\d+/', $cname, $matches);
    $grade_num = isset($matches[0]) ? (int)$matches[0] : 0;

    $sys_stmt = $pdo->prepare("SELECT * FROM education_resources WHERE grade = ? AND subject = ? AND status = 'active'");
    $sys_stmt->execute([$grade_num, $current_subject_name]);
    $system_resources = $sys_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Materials - Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .chapter-card { border-radius: 16px; border: 1px solid #e8f5e9; transition: all .3s; }
        .chapter-card:hover { box-shadow: 0 8px 25px rgba(27,94,32,.1); border-color: #a5d6a7; }
        .chapter-num { width: 48px; height: 48px; border-radius: 12px; background: #1B5E20; color: #fff; display: flex; align-items: center; justify-content: center; font-size: 1.1rem; font-weight: bold; flex-shrink: 0; }
        .sidebar-select { background: #fff; border: 2px solid #e8f5e9; border-radius: 14px; transition: all .3s; cursor: pointer; }
        .sidebar-select.active, .sidebar-select:hover { border-color: #1B5E20; background: #e8f5e9; }
    </style>
</head>
<body>
<?php include('../includes/sidebar_teacher.php'); ?>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">Course Materials</h2>
            <p class="text-muted mb-0">Upload chapters and content for your classes.</p>
        </div>
        <?php if ($class_id && $subject_id): ?>
        <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#addChapterModal">
            <i class="fas fa-plus-circle me-2"></i>Add Chapter
        </button>
        <?php endif; ?>
    </div>

    <?php echo displayFlashMessage(); ?>

    <div class="row g-4">
        <!-- Left: Class selector -->
        <div class="col-lg-3">
            <h6 class="text-muted fw-bold text-uppercase small mb-3">My Subjects</h6>
            <?php foreach ($my_assignments as $a): ?>
            <a href="?class_id=<?= $a['class_id'] ?>&subject_id=<?= $a['subject_id'] ?>" class="text-decoration-none">
                <div class="sidebar-select p-3 mb-2 <?= ($class_id == $a['class_id'] && $subject_id == $a['subject_id']) ? 'active' : '' ?>">
                    <div class="fw-bold text-dark"><?= htmlspecialchars($a['subject_name']) ?></div>
                    <small class="text-muted"><?= htmlspecialchars($a['class_name'] . ' – ' . $a['section']) ?></small>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($my_assignments)): ?>
                <p class="text-muted small">No classes assigned yet.</p>
            <?php endif; ?>
        </div>

        <!-- Right: Chapter list -->
        <div class="col-lg-9">
            <?php if (!$class_id): ?>
                <div class="text-center py-5 mt-4">
                    <i class="fas fa-hand-point-left fa-3x text-muted opacity-50 mb-3"></i>
                    <p class="text-muted lead">Select a subject from the left to manage chapters.</p>
                </div>
            <?php else: ?>
                <h5 class="fw-bold mb-4">
                    <i class="fas fa-book-open text-success me-2"></i>
                    <?= htmlspecialchars($current_subject_name) ?> — Course Materials
                    <span class="badge bg-light text-dark border ms-2"><?= count($chapters) + count($system_resources) ?></span>
                </h5>

                <?php if (!empty($system_resources)): ?>
                    <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fas fa-building me-2"></i>School Admin Materials</h6>
                    <div class="row g-3 mb-4">
                    <?php foreach ($system_resources as $sys): ?>
                        <div class="col-12">
                            <div class="card chapter-card p-4" style="border-left: 4px solid #1565C0;">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="chapter-num" style="background:#1565C0;"><i class="fas fa-book"></i></div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div>
                                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($sys['title']) ?> <span class="badge bg-primary bg-opacity-10 text-primary ms-2 rounded-pill"><?= ucwords(str_replace('_',' ',$sys['type'])) ?></span></h6>
                                                <p class="text-muted small mb-2"><?= htmlspecialchars($sys['description'] ?? '') ?></p>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-2 flex-wrap mt-2">
                                            <?php if ($sys['video_url']): ?>
                                                <a href="<?= htmlspecialchars($sys['video_url']) ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill">
                                                    <i class="fab fa-youtube me-1"></i>Watch Video
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($sys['file_url']): ?>
                                                <a href="<?= htmlspecialchars($sys['file_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                    <i class="fas fa-download me-1"></i>Download File
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <h6 class="fw-bold text-uppercase small text-muted mb-3"><i class="fas fa-chalkboard-teacher me-2"></i>My Uploaded Chapters</h6>

                <?php if (empty($chapters)): ?>
                    <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                        <i class="fas fa-folder-open fa-3x text-muted opacity-50 mb-3"></i>
                        <h5 class="text-muted">No chapters yet</h5>
                        <p class="text-muted">Click <strong>"Add Chapter"</strong> to publish the first lesson.</p>
                    </div>
                <?php else: ?>
                    <div class="row g-3">
                    <?php foreach ($chapters as $ch): ?>
                        <div class="col-12">
                            <div class="card chapter-card p-4">
                                <div class="d-flex align-items-start gap-3">
                                    <div class="chapter-num">Ch.<?= $ch['chapter_number'] ?></div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold mb-1"><?= htmlspecialchars($ch['title']) ?></h6>
                                        <p class="text-muted small mb-2"><?= mb_substr(strip_tags($ch['content']), 0, 150) ?>...</p>
                                        <div class="d-flex gap-2 flex-wrap">
                                            <?php if ($ch['video_url']): ?>
                                                <a href="<?= htmlspecialchars($ch['video_url']) ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill">
                                                    <i class="fab fa-youtube me-1"></i>Video
                                                </a>
                                            <?php elseif ($ch['local_video_path']): ?>
                                                <a href="../uploads/lms/videos/<?= $ch['local_video_path'] ?>" target="_blank" class="btn btn-sm btn-outline-danger rounded-pill">
                                                    <i class="fas fa-video me-1"></i>Local Vid
                                                </a>
                                            <?php endif; ?>
                                            <?php if ($ch['document_path']): ?>
                                                <a href="../uploads/lms/docs/<?= $ch['document_path'] ?>" target="_blank" class="btn btn-sm btn-outline-info rounded-pill">
                                                    <i class="fas fa-file-alt me-1"></i>Document
                                                </a>
                                            <?php endif; ?>
                                            <a href="../customer/lms.php?chapter_id=<?= $ch['id'] ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                                <i class="fas fa-eye me-1"></i>Preview
                                            </a>
                                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this chapter?')">
                                                <input type="hidden" name="action" value="delete_chapter">
                                                <input type="hidden" name="chapter_id" value="<?= $ch['id'] ?>">
                                                <input type="hidden" name="class_id" value="<?= $class_id ?>">
                                                <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
                                                <button class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-trash me-1"></i>Delete</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add Chapter Modal -->
<div class="modal" id="addChapterModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" class="modal-content border-0 shadow" enctype="multipart/form-data">
            <input type="hidden" name="action" value="add_chapter">
            <input type="hidden" name="class_id" value="<?= $class_id ?>">
            <input type="hidden" name="subject_id" value="<?= $subject_id ?>">
            <div class="modal-header border-0">
                <h5 class="modal-title fw-bold">Add New Chapter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label fw-bold small">Chapter #</label>
                        <input type="number" name="chapter_number" class="form-control" value="<?= count($chapters) + 1 ?>" required>
                    </div>
                    <div class="col-md-9">
                        <label class="form-label fw-bold small">Chapter Title</label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. Introduction to Cells" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Content / Lesson Notes</label>
                    <textarea name="content" class="form-control" rows="8" placeholder="Write the lesson content here..." required></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-bold small">Video URL (YouTube embed, optional)</label>
                    <input type="url" name="video_url" class="form-control" placeholder="https://youtube.com/watch?v=...">
                    <div class="mt-2 text-muted small">OR Upload local video:</div>
                    <input type="file" name="local_video" class="form-control" accept="video/*">
                </div>
                <div class="mb-2">
                    <label class="form-label fw-bold small">Upload Material (PDF/Word/PPT)</label>
                    <input type="file" name="local_file" class="form-control" accept=".pdf,.doc,.docx,.ppt,.pptx">
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5">
                    <i class="fas fa-upload me-2"></i>Publish Chapter
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
