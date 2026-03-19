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

    // Create Assignment
    if ($action === 'create_assignment') {
        $title      = sanitize($_POST['title']);
        $desc       = sanitize($_POST['description']);
        $class_id   = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $due_date   = $_POST['due_date'];
        $max_marks  = (int)$_POST['max_marks'];

        $file_path = null;
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir('../uploads/assignments')) {
                mkdir('../uploads/assignments', 0755, true);
            }
            $ext = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $dest = '../uploads/assignments/' . $filename;
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $dest)) {
                $file_path = $dest;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO sms_assignments (title, description, class_id, subject_id, teacher_id, due_date, max_points, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $desc, $class_id, $subject_id, $teacher_id, $due_date, $max_marks, $file_path]);
        setFlashMessage('Assignment created successfully!', 'success');
        header('Location: assignments.php');
        exit;
    }

    // Edit Assignment
    if ($action === 'edit_assignment') {
        $id         = (int)$_POST['assignment_id'];
        $title      = sanitize($_POST['title']);
        $desc       = sanitize($_POST['description']);
        $due_date   = $_POST['due_date'];
        $max_marks  = (int)$_POST['max_marks'];

        $file_path = $_POST['existing_file_path'] ?? null;
        if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir('../uploads/assignments')) {
                mkdir('../uploads/assignments', 0755, true);
            }
            $ext = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $dest = '../uploads/assignments/' . $filename;
            if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $dest)) {
                $file_path = $dest;
            }
        }

        $stmt = $pdo->prepare("UPDATE sms_assignments SET title=?, description=?, due_date=?, max_points=?, file_path=? WHERE id=? AND teacher_id=?");
        $stmt->execute([$title, $desc, $due_date, $max_marks, $file_path, $id, $teacher_id]);
        setFlashMessage('Assignment updated successfully!', 'success');
        header('Location: assignments.php');
        exit;
    }

    // Grade a submission
    if ($action === 'grade_submission') {
        $sub_id = (int)$_POST['submission_id'];
        $grade  = sanitize($_POST['grade']);
        $remark = sanitize($_POST['remark']);
        $pdo->prepare("UPDATE sms_assignment_submissions SET grade = ?, teacher_remark = ? WHERE id = ?")
            ->execute([$grade, $remark, $sub_id]);
        setFlashMessage('Submission graded!', 'success');
        header('Location: assignments.php?view=submissions&aid=' . (int)$_POST['assignment_id']);
        exit;
    }

    // Delete assignment
    if ($action === 'delete_assignment') {
        $pdo->prepare("DELETE FROM sms_assignments WHERE id = ? AND teacher_id = ?")
            ->execute([(int)$_POST['assignment_id'], $teacher_id]);
        setFlashMessage('Assignment deleted.', 'info');
        header('Location: assignments.php');
        exit;
    }
}

$view = $_GET['view'] ?? 'list';
$aid  = (int)($_GET['aid'] ?? 0);

// Get my classes/subjects for dropdown
$classes_stmt = $pdo->prepare("
    SELECT cs.class_id, cs.subject_id, c.class_name, c.section, s.subject_name
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    JOIN sms_subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ?
");
$classes_stmt->execute([$teacher_id]);
$my_classes = $classes_stmt->fetchAll();

// My assignments
$assignments = $pdo->prepare("
    SELECT a.*, c.class_name, c.section, s.subject_name,
    (SELECT COUNT(*) FROM sms_assignment_submissions WHERE assignment_id = a.id) as submission_count,
    (SELECT COUNT(*) FROM sms_assignment_submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
    FROM sms_assignments a
    JOIN sms_classes c ON a.class_id = c.id
    JOIN sms_subjects s ON a.subject_id = s.id
    WHERE a.teacher_id = ?
    ORDER BY a.id DESC
");
$assignments->execute([$teacher_id]);
$assignments = $assignments->fetchAll();

// Submissions view
$submissions = [];
$current_assignment = null;
if ($view === 'submissions' && $aid) {
    $current_assignment = $pdo->prepare("SELECT a.*, c.class_name, s.subject_name FROM sms_assignments a JOIN sms_classes c ON a.class_id = c.id JOIN sms_subjects s ON a.subject_id = s.id WHERE a.id = ?");
    $current_assignment->execute([$aid]);
    $current_assignment = $current_assignment->fetch();

    $subs_stmt = $pdo->prepare("
        SELECT ss.*, u.full_name, u.email
        FROM sms_assignment_submissions ss
        JOIN users u ON ss.student_id = u.id
        WHERE ss.assignment_id = ?
        ORDER BY ss.submission_date ASC
    ");
    $subs_stmt->execute([$aid]);
    $submissions = $subs_stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignments - Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        body { background: #f4f7f6; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .assignment-row { border-radius: 14px; border: 1px solid #e0e0e0; transition: all .25s; }
        .assignment-row:hover { border-color: #a5d6a7; box-shadow: 0 6px 20px rgba(27,94,32,.08); }
        .badge-due { background: #fff3e0; color: #e65100; }
        .badge-over { background: #fce4ec; color: #c62828; }
    </style>
</head>
<body>
<?php include('../includes/sidebar_teacher.php'); ?>

<div class="main-content">
    <?php echo displayFlashMessage(); ?>

    <?php if ($view === 'submissions' && $current_assignment): ?>
        <!-- SUBMISSIONS VIEW -->
        <div class="d-flex align-items-center gap-3 mb-4">
            <a href="assignments.php" class="btn btn-light border rounded-circle"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h2 class="fw-bold mb-0"><?= htmlspecialchars($current_assignment['title']) ?></h2>
                <p class="text-muted mb-0"><?= htmlspecialchars($current_assignment['subject_name']) ?> &bull; <?= htmlspecialchars($current_assignment['class_name']) ?></p>
            </div>
        </div>

        <div class="card border-0 shadow-sm p-4 rounded-4">
            <h5 class="fw-bold mb-4">Student Submissions <span class="badge bg-light text-dark border"><?= count($submissions) ?></span></h5>
            <?php if (empty($submissions)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-inbox fa-3x text-muted opacity-50 mb-3"></i>
                    <p class="text-muted">No submissions received yet.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                <?php foreach ($submissions as $sub): ?>
                    <div class="col-12">
                        <div class="card border rounded-4 p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex gap-3 align-items-center">
                                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($sub['full_name']) ?>&background=random" class="rounded-circle" width="40">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($sub['full_name']) ?></div>
                                        <small class="text-muted">Submitted: <?= date('M d, Y H:i', strtotime($sub['submission_date'])) ?></small>
                                    </div>
                                </div>
                                <?php if ($sub['grade']): ?>
                                    <span class="badge bg-success fs-6 px-3 py-2"><?= htmlspecialchars($sub['grade']) ?></span>
                                <?php else: ?>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill" 
                                            onclick="showGradeForm(<?= $sub['id'] ?>, <?= $aid ?>)">
                                        <i class="fas fa-pen me-1"></i>Grade
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ($sub['submission_text']): ?>
                                <div class="bg-light rounded-3 p-3 mt-3 small">
                                    <?= nl2br(htmlspecialchars($sub['submission_text'])) ?>
                                </div>
                            <?php endif; ?>
                            <?php if ($sub['teacher_remark']): ?>
                                <div class="mt-2 small text-success"><i class="fas fa-comment-dots me-1"></i><?= htmlspecialchars($sub['teacher_remark']) ?></div>
                            <?php endif; ?>
                            <div id="grade_form_<?= $sub['id'] ?>" style="display:none;" class="mt-3">
                                <form method="POST" class="row g-2 align-items-end">
                                    <input type="hidden" name="action" value="grade_submission">
                                    <input type="hidden" name="submission_id" value="<?= $sub['id'] ?>">
                                    <input type="hidden" name="assignment_id" value="<?= $aid ?>">
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Grade / Score</label>
                                        <input type="text" name="grade" class="form-control" placeholder="e.g. A+ or 90">
                                    </div>
                                    <div class="col-md-7">
                                        <label class="form-label small fw-bold">Remark</label>
                                        <input type="text" name="remark" class="form-control" placeholder="Well done!">
                                    </div>
                                    <div class="col-md-2">
                                        <button class="btn btn-success w-100">Save</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    <?php else: ?>
        <!-- ASSIGNMENTS LIST VIEW -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-1">Assignments</h2>
                <p class="text-muted mb-0">Create and manage student assignments.</p>
            </div>
            <button class="btn btn-success rounded-pill px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#createAssignmentModal">
                <i class="fas fa-plus me-2"></i>New Assignment
            </button>
        </div>

        <?php if (empty($assignments)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                <i class="fas fa-tasks fa-3x text-muted opacity-50 mb-3"></i>
                <h5 class="text-muted">No assignments created yet.</h5>
                <p class="text-muted">Click <strong>"New Assignment"</strong> to get started.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
            <?php foreach ($assignments as $a): 
                $is_overdue = strtotime($a['due_date']) < time();
            ?>
                <div class="col-12">
                    <div class="card assignment-row p-4">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1"><?= htmlspecialchars($a['title']) ?></h5>
                                <p class="text-muted small mb-2"><?= htmlspecialchars($a['subject_name']) ?> &bull; <?= htmlspecialchars($a['class_name'] . ' – ' . $a['section']) ?></p>
                                <p class="text-muted small mb-2"><?= nl2br(htmlspecialchars($a['description'])) ?></p>
                                <?php if (!empty($a['file_path'])): ?>
                                    <a href="<?= htmlspecialchars($a['file_path']) ?>" target="_blank" class="badge bg-primary bg-opacity-10 text-primary border border-primary text-decoration-none rounded-pill px-3 py-1 mb-2">
                                        <i class="fas fa-paperclip me-1"></i> Attached Document
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="text-end">
                                <span class="badge <?= $is_overdue ? 'badge-over' : 'badge-due' ?> rounded-pill px-3 py-2 mb-2">
                                    <i class="fas fa-calendar-alt me-1"></i>
                                    Due: <?= date('M d, Y', strtotime($a['due_date'])) ?>
                                </span>
                            </div>
                        </div>
                        <hr class="my-3 opacity-25">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex gap-4">
                                <div class="text-center">
                                    <div class="fw-bold fs-5 text-success"><?= $a['submission_count'] ?></div>
                                    <small class="text-muted">Submitted</small>
                                </div>
                                <div class="text-center">
                                    <div class="fw-bold fs-5 text-warning"><?= $a['submission_count'] - $a['graded_count'] ?></div>
                                    <small class="text-muted">Ungraded</small>
                                </div>
                                <div class="text-center">
                                    <div class="fw-bold fs-5 text-primary"><?= $a['max_points'] ?></div>
                                    <small class="text-muted">Max Points</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="?view=submissions&aid=<?= $a['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                    <i class="fas fa-list-check me-1"></i>Review (<?= $a['submission_count'] ?>)
                                </a>
                                <button class="btn btn-sm btn-outline-warning rounded-pill px-3" onclick='openEditModal(<?= json_encode($a) ?>)'>
                                    <i class="fas fa-edit me-1"></i>Edit
                                </button>
                                <form method="POST" class="d-inline" onsubmit="return confirm('Delete this assignment?')">
                                    <input type="hidden" name="action" value="delete_assignment">
                                    <input type="hidden" name="assignment_id" value="<?= $a['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger rounded-pill"><i class="fas fa-trash"></i></button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Create Assignment Modal -->
<div class="modal" id="createAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="create_assignment">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Create New Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Assignment Title</label>
                    <input type="text" name="title" class="form-control rounded-3" placeholder="e.g. Chapter 3 Review Questions" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Questions / Instructions</label>
                    <textarea name="description" class="form-control rounded-3" rows="6" placeholder="Type or paste your questions and instructions here..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold"><i class="fas fa-file-upload me-1 text-primary"></i>Attach Document (Optional)</label>
                    <input type="file" name="assignment_file" class="form-control rounded-3">
                    <small class="text-muted pb-2">Upload PDFs, Word docs, etc.</small>
                </div>
                <!-- MASS OR CLASS ASSIGNMENT TARGETING -->
                <div class="mb-3 bg-light p-3 rounded-3 border">
                    <label class="form-label small fw-bold mb-2">Target Assignment</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="radio" name="target_type" id="t_class" value="class" checked>
                        <label class="form-check-label text-dark" for="t_class">By Class / Section</label>
                    </div>
                    <!-- (We can add individual selection here if student lists are provided) -->
                    <select name="class_id" id="classSelect" class="form-select rounded-3 mt-2" required onchange="updateSubject('classSelect', 'subjectHidden')">
                        <option value="">-- Choose Class --</option>
                        <?php foreach ($my_classes as $c): ?>
                            <option value="<?= $c['class_id'] ?>" data-subject="<?= $c['subject_id'] ?>">
                                <?= htmlspecialchars($c['class_name'] . ' – ' . $c['section'] . ' (' . $c['subject_name'] . ')') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <input type="hidden" name="subject_id" id="subjectHidden" value="">
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Due Date & Time</label>
                        <input type="datetime-local" name="due_date" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Max Points (Grade)</label>
                        <input type="number" name="max_marks" class="form-control rounded-3" value="100" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5">
                    <i class="fas fa-paper-plane me-2"></i>Create Assignment
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Assignment Modal -->
<div class="modal" id="editAssignmentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" enctype="multipart/form-data" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit_assignment">
            <input type="hidden" name="assignment_id" id="edit_id">
            <input type="hidden" name="existing_file_path" id="edit_file_path">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Edit Assignment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Assignment Title</label>
                    <input type="text" name="title" id="edit_title" class="form-control rounded-3" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Questions / Instructions</label>
                    <textarea name="description" id="edit_description" class="form-control rounded-3" rows="6"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold"><i class="fas fa-file-upload me-1 text-primary"></i>Replacement Document (Optional)</label>
                    <input type="file" name="assignment_file" class="form-control rounded-3">
                    <small class="text-muted d-block mt-1">Leave empty to keep current file.</small>
                    <div id="current_file_link" class="mt-2 text-primary small"></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Due Date & Time</label>
                        <input type="datetime-local" name="due_date" id="edit_due_date" class="form-control rounded-3" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label small fw-bold">Max Points</label>
                        <input type="number" name="max_marks" id="edit_max_marks" class="form-control rounded-3" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-success fw-bold rounded-pill px-5">
                    <i class="fas fa-save me-2"></i>Save Changes
                </button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
function showGradeForm(subId, aid) {
    const el = document.getElementById('grade_form_' + subId);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
function updateSubject(selectId, hiddenId) {
    const sel = document.getElementById(selectId);
    const option = sel.options[sel.selectedIndex];
    document.getElementById(hiddenId).value = option.dataset.subject || '';
}

function openEditModal(assignment) {
    document.getElementById('edit_id').value = assignment.id;
    document.getElementById('edit_title').value = assignment.title;
    document.getElementById('edit_description').value = assignment.description;
    
    // Format date for datetime-local (YYYY-MM-DDThh:mm)
    let d = new Date(assignment.due_date);
    d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
    document.getElementById('edit_due_date').value = d.toISOString().slice(0, 16);
    
    document.getElementById('edit_max_marks').value = assignment.max_points;
    document.getElementById('edit_file_path').value = assignment.file_path || '';
    
    let linkContainer = document.getElementById('current_file_link');
    if(assignment.file_path) {
        linkContainer.innerHTML = 'Current file: <a href="'+assignment.file_path+'" target="_blank">View File</a>';
    } else {
        linkContainer.innerHTML = '';
    }

    const modal = new bootstrap.Modal(document.getElementById('editAssignmentModal'));
    modal.show();
}
</script>
</body>
</html>
