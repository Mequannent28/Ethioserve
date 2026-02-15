<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Ensure education_resources table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS education_resources (
        id INT AUTO_INCREMENT PRIMARY KEY,
        grade INT NOT NULL,
        subject VARCHAR(100) NOT NULL,
        type ENUM('textbook','teacher_guide','video') DEFAULT 'textbook',
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_url VARCHAR(500),
        video_url VARCHAR(500),
        video_id VARCHAR(50),
        pages INT DEFAULT 0,
        units INT DEFAULT 0,
        edition VARCHAR(50) DEFAULT '2023',
        status ENUM('active','draft','archived') DEFAULT 'active',
        downloads INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
}

// Handle Add Resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_resource'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $grade = (int) $_POST['grade'];
        $subject = sanitize($_POST['subject']);
        $type = sanitize($_POST['type']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $file_url = sanitize($_POST['file_url'] ?? '');
        $video_url = sanitize($_POST['video_url'] ?? '');
        $video_id = sanitize($_POST['video_id'] ?? '');
        $pages = (int) ($_POST['pages'] ?? 0);
        $units = (int) ($_POST['units'] ?? 0);
        $edition = sanitize($_POST['edition'] ?? '2023');

        try {
            $stmt = $pdo->prepare("INSERT INTO education_resources (grade, subject, type, title, description, file_url, video_url, video_id, pages, units, edition) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$grade, $subject, $type, $title, $description, $file_url, $video_url, $video_id, $pages, $units, $edition]);
            redirectWithMessage('manage_education.php', 'success', 'Education resource added successfully!');
        } catch (Exception $e) {
            redirectWithMessage('manage_education.php', 'error', 'Failed to add resource: ' . $e->getMessage());
        }
    }
}

// Handle Edit Resource
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_resource'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int) $_POST['resource_id'];
        $grade = (int) $_POST['grade'];
        $subject = sanitize($_POST['subject']);
        $type = sanitize($_POST['type']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $file_url = sanitize($_POST['file_url'] ?? '');
        $video_url = sanitize($_POST['video_url'] ?? '');
        $video_id = sanitize($_POST['video_id'] ?? '');
        $pages = (int) ($_POST['pages'] ?? 0);
        $units = (int) ($_POST['units'] ?? 0);
        $edition = sanitize($_POST['edition'] ?? '2023');
        $status = sanitize($_POST['status'] ?? 'active');

        try {
            $stmt = $pdo->prepare("UPDATE education_resources SET grade=?, subject=?, type=?, title=?, description=?, file_url=?, video_url=?, video_id=?, pages=?, units=?, edition=?, status=? WHERE id=?");
            $stmt->execute([$grade, $subject, $type, $title, $description, $file_url, $video_url, $video_id, $pages, $units, $edition, $status, $id]);
            redirectWithMessage('manage_education.php', 'success', 'Resource updated successfully!');
        } catch (Exception $e) {
            redirectWithMessage('manage_education.php', 'error', 'Failed to update: ' . $e->getMessage());
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM education_resources WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_education.php', 'success', 'Resource deleted successfully');
    } catch (Exception $e) {
        redirectWithMessage('manage_education.php', 'error', 'Failed to delete');
    }
}

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = (int) $_GET['toggle'];
    try {
        $stmt = $pdo->prepare("UPDATE education_resources SET status = IF(status='active','draft','active') WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_education.php', 'success', 'Status toggled');
    } catch (Exception $e) {
    }
}

// Filters
$filter_grade = isset($_GET['grade']) ? (int) $_GET['grade'] : 0;
$filter_type = sanitize($_GET['type'] ?? '');
$filter_subject = sanitize($_GET['subject_filter'] ?? '');

// Build query
$where = [];
$params = [];
if ($filter_grade > 0) {
    $where[] = "grade = ?";
    $params[] = $filter_grade;
}
if ($filter_type) {
    $where[] = "type = ?";
    $params[] = $filter_type;
}
if ($filter_subject) {
    $where[] = "subject = ?";
    $params[] = $filter_subject;
}
$whereSQL = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Fetch resources
$resources = [];
try {
    $stmt = $pdo->prepare("SELECT * FROM education_resources $whereSQL ORDER BY grade ASC, subject ASC, type ASC");
    $stmt->execute($params);
    $resources = $stmt->fetchAll();
} catch (Exception $e) {
}

// Stats
$totalResources = 0;
$totalTextbooks = 0;
$totalVideos = 0;
$totalGuides = 0;
$gradesWithContent = 0;
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM education_resources");
    $totalResources = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM education_resources WHERE type='textbook'");
    $totalTextbooks = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM education_resources WHERE type='video'");
    $totalVideos = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(*) FROM education_resources WHERE type='teacher_guide'");
    $totalGuides = $stmt->fetchColumn();
    $stmt = $pdo->query("SELECT COUNT(DISTINCT grade) FROM education_resources");
    $gradesWithContent = $stmt->fetchColumn();
} catch (Exception $e) {
}

// Subject list for dropdowns
$all_subjects = ['Amharic', 'English', 'Mathematics', 'Environmental Science', 'Afan Oromo', 'Social Studies', 'Civics', 'General Science', 'Biology', 'Physics', 'Chemistry', 'Geography', 'History', 'Economics', 'ICT'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Education - Admin | EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .stat-card {
            background: #fff;
            border-radius: 16px;
            padding: 24px;
            border: none;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            transition: transform .3s, box-shadow .3s;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: #fff;
        }

        .grade-badge {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: .85rem;
            color: #fff;
        }

        .type-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: .72rem;
            font-weight: 600;
        }

        .type-textbook {
            background: #E3F2FD;
            color: #1565C0;
        }

        .type-teacher_guide {
            background: #FFF3E0;
            color: #E65100;
        }

        .type-video {
            background: #FCE4EC;
            color: #C62828;
        }

        .filter-bar {
            background: #fff;
            border-radius: 16px;
            padding: 16px 20px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            margin-bottom: 20px;
        }

        .resource-table th {
            font-size: .75rem;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: #999;
            font-weight: 600;
            border-bottom: 2px solid #f0f0f0;
        }

        .resource-table td {
            vertical-align: middle;
            font-size: .88rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
            margin-right: 6px;
        }

        .status-active {
            background: #4CAF50;
        }

        .status-draft {
            background: #FF9800;
        }

        .status-archived {
            background: #9E9E9E;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-graduation-cap me-2" style="color:#1565C0;"></i>Manage
                        Education</h2>
                    <p class="text-muted mb-0">Add and manage educational resources for Grade 1-12</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="../customer/education.php" target="_blank"
                        class="btn btn-outline-primary rounded-pill px-4">
                        <i class="fas fa-external-link-alt me-2"></i>View Portal
                    </a>
                    <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#addResourceModal" style="background:#1565C0;border-color:#1565C0;">
                        <i class="fas fa-plus me-2"></i>Add Resource
                    </button>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#1565C0,#42A5F5);">
                                <i class="fas fa-book-open"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $totalResources; ?>
                                </h3>
                                <span class="text-muted small">Total Resources</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#2E7D32,#66BB6A);">
                                <i class="fas fa-book"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $totalTextbooks; ?>
                                </h3>
                                <span class="text-muted small">Textbooks</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#E65100,#FF9800);">
                                <i class="fas fa-chalkboard-teacher"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $totalGuides; ?>
                                </h3>
                                <span class="text-muted small">Teacher Guides</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-card">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon" style="background:linear-gradient(135deg,#C62828,#EF5350);">
                                <i class="fas fa-play-circle"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $totalVideos; ?>
                                </h3>
                                <span class="text-muted small">Video Lessons</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <form method="GET" class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Grade</label>
                        <select name="grade" class="form-select form-select-sm rounded-pill border-0 bg-light">
                            <option value="0">All Grades</option>
                            <?php for ($g = 1; $g <= 12; $g++): ?>
                                <option value="<?php echo $g; ?>" <?php echo $filter_grade === $g ? 'selected' : ''; ?>>
                                    Grade
                                    <?php echo $g; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Type</label>
                        <select name="type" class="form-select form-select-sm rounded-pill border-0 bg-light">
                            <option value="">All Types</option>
                            <option value="textbook" <?php echo $filter_type === 'textbook' ? 'selected' : ''; ?>>
                                Textbook</option>
                            <option value="teacher_guide" <?php echo $filter_type === 'teacher_guide' ? 'selected' : ''; ?>>Teacher Guide</option>
                            <option value="video" <?php echo $filter_type === 'video' ? 'selected' : ''; ?>>Video
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Subject</label>
                        <select name="subject_filter" class="form-select form-select-sm rounded-pill border-0 bg-light">
                            <option value="">All Subjects</option>
                            <?php foreach ($all_subjects as $subj): ?>
                                <option value="<?php echo $subj; ?>" <?php echo $filter_subject === $subj ? 'selected' : ''; ?>>
                                    <?php echo $subj; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4"
                            style="background:#1565C0;border-color:#1565C0;">
                            <i class="fas fa-filter me-1"></i>Filter
                        </button>
                        <a href="manage_education.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Resources Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 resource-table">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Resource</th>
                                <th>Grade</th>
                                <th>Subject</th>
                                <th>Type</th>
                                <th>Details</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($resources)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-graduation-cap fs-1 mb-3 d-block" style="color:#ccc;"></i>
                                        <h6 class="fw-bold">No education resources found</h6>
                                        <p class="small">Click "Add Resource" to start building the education
                                            library</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php
                                $grade_colors = ['#1565C0', '#2E7D32', '#E65100', '#6A1B9A', '#00897B', '#AD1457', '#0D47A1', '#1B5E20', '#BF360C', '#4527A0', '#00695C', '#C62828'];
                                foreach ($resources as $res):
                                    $gc = $grade_colors[($res['grade'] - 1) % 12];
                                    ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div>
                                                    <?php if ($res['type'] === 'video'): ?>
                                                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                                                            style="width:45px;height:45px;background:linear-gradient(135deg,#C62828,#EF5350);">
                                                            <i class="fas fa-play text-white"></i>
                                                        </div>
                                                    <?php elseif ($res['type'] === 'teacher_guide'): ?>
                                                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                                                            style="width:45px;height:45px;background:linear-gradient(135deg,#E65100,#FF9800);">
                                                            <i class="fas fa-chalkboard-teacher text-white"></i>
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="rounded-3 d-flex align-items-center justify-content-center"
                                                            style="width:45px;height:45px;background:linear-gradient(135deg,#1565C0,#42A5F5);">
                                                            <i class="fas fa-book text-white"></i>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold" style="font-size:.88rem;">
                                                        <?php echo htmlspecialchars($res['title']); ?>
                                                    </h6>
                                                    <span class="text-muted" style="font-size:.75rem;">
                                                        <?php echo mb_strimwidth(htmlspecialchars($res['description'] ?? ''), 0, 50, '...'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="grade-badge" style="background:<?php echo $gc; ?>;">
                                                <?php echo $res['grade']; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-semibold" style="font-size:.85rem;">
                                                <?php echo htmlspecialchars($res['subject']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="type-badge type-<?php echo $res['type']; ?>">
                                                <?php echo ucwords(str_replace('_', ' ', $res['type'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size:.78rem;">
                                                <?php if ($res['type'] !== 'video'): ?>
                                                    <?php if ($res['units']): ?>
                                                        <span class="text-muted"><i class="fas fa-list me-1"></i>
                                                            <?php echo $res['units']; ?>
                                                            Units
                                                        </span><br>
                                                    <?php endif; ?>
                                                    <?php if ($res['pages']): ?>
                                                        <span class="text-muted"><i class="fas fa-file me-1"></i>
                                                            <?php echo $res['pages']; ?>
                                                            Pages
                                                        </span>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <?php if ($res['video_id']): ?>
                                                        <span class="text-muted"><i class="fab fa-youtube me-1"
                                                                style="color:#C62828;"></i>YouTube</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                <br><span class="badge bg-light text-muted" style="font-size:.65rem;">
                                                    <?php echo $res['edition']; ?>
                                                    Edition
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-dot status-<?php echo $res['status']; ?>"></span>
                                            <span style="font-size:.8rem;">
                                                <?php echo ucfirst($res['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="d-flex gap-1 justify-content-end">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill"
                                                    onclick="editResource(<?php echo htmlspecialchars(json_encode($res)); ?>)"
                                                    title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?toggle=<?php echo $res['id']; ?>"
                                                    class="btn btn-sm btn-outline-warning rounded-pill" title="Toggle Status">
                                                    <i
                                                        class="fas fa-toggle-<?php echo $res['status'] === 'active' ? 'on' : 'off'; ?>"></i>
                                                </a>
                                                <a href="?delete=<?php echo $res['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Are you sure you want to delete this resource?')"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Quick Add Info -->
            <div class="card border-0 shadow-sm rounded-4 mt-4 p-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary me-2"></i>Quick Guide</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:30px;height:30px;background:#E3F2FD;color:#1565C0;font-size:.8rem;font-weight:700;">
                                1</div>
                            <div>
                                <h6 class="fw-bold mb-0" style="font-size:.85rem;">Add Textbooks</h6>
                                <p class="text-muted mb-0" style="font-size:.75rem;">Upload student textbook PDFs for
                                    each grade and subject</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:30px;height:30px;background:#FFF3E0;color:#E65100;font-size:.8rem;font-weight:700;">
                                2</div>
                            <div>
                                <h6 class="fw-bold mb-0" style="font-size:.85rem;">Teacher Guides</h6>
                                <p class="text-muted mb-0" style="font-size:.75rem;">Add instructor manuals with lesson
                                    plans and rubrics</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-start gap-2">
                            <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                                style="width:30px;height:30px;background:#FCE4EC;color:#C62828;font-size:.8rem;font-weight:700;">
                                3</div>
                            <div>
                                <h6 class="fw-bold mb-0" style="font-size:.85rem;">Video Lessons</h6>
                                <p class="text-muted mb-0" style="font-size:.75rem;">Link YouTube video IDs for
                                    interactive learning</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Resource Modal -->
    <div class="modal fade" id="addResourceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4 pb-2">
                    <h5 class="modal-title fw-bold"><i class="fas fa-plus-circle me-2" style="color:#1565C0;"></i>Add
                        Education Resource</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-2">
                    <?php echo csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Grade <span class="text-danger">*</span></label>
                            <select name="grade" class="form-select rounded-pill bg-light border-0 px-4" required>
                                <?php for ($g = 1; $g <= 12; $g++): ?>
                                    <option value="<?php echo $g; ?>">Grade
                                        <?php echo $g; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Subject <span class="text-danger">*</span></label>
                            <select name="subject" class="form-select rounded-pill bg-light border-0 px-4" required>
                                <?php foreach ($all_subjects as $subj): ?>
                                    <option value="<?php echo $subj; ?>">
                                        <?php echo $subj; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Type <span class="text-danger">*</span></label>
                            <select name="type" id="addType" class="form-select rounded-pill bg-light border-0 px-4"
                                required onchange="toggleAddFields()">
                                <option value="textbook">📘 Textbook</option>
                                <option value="teacher_guide">📙 Teacher Guide</option>
                                <option value="video">🎬 Video Lesson</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="e.g. Grade 5 Mathematics Student Textbook">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 px-4" rows="2"
                                style="border-radius:15px;"
                                placeholder="Brief description of the resource..."></textarea>
                        </div>
                        <div class="col-12 add-file-fields">
                            <label class="form-label small fw-bold">File/Download URL</label>
                            <input type="url" name="file_url" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="https://example.com/textbook.pdf">
                        </div>
                        <div class="col-md-6 add-video-fields" style="display:none;">
                            <label class="form-label small fw-bold">YouTube Video ID</label>
                            <input type="text" name="video_id" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="e.g. pTnEG_WGd2Q">
                            <div class="form-text small">The ID from the YouTube URL (after v=)</div>
                        </div>
                        <div class="col-md-6 add-video-fields" style="display:none;">
                            <label class="form-label small fw-bold">Full Video URL</label>
                            <input type="url" name="video_url" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="https://youtube.com/watch?v=...">
                        </div>
                        <div class="col-md-4 add-book-fields">
                            <label class="form-label small fw-bold">Units</label>
                            <input type="number" name="units" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="12" min="0">
                        </div>
                        <div class="col-md-4 add-book-fields">
                            <label class="form-label small fw-bold">Pages</label>
                            <input type="number" name="pages" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="200" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Edition</label>
                            <input type="text" name="edition" class="form-control rounded-pill bg-light border-0 px-4"
                                value="2023" placeholder="2023">
                        </div>
                    </div>
                    <button type="submit" name="add_resource"
                        class="btn w-100 rounded-pill py-3 fw-bold mt-4 shadow text-white"
                        style="background:linear-gradient(135deg,#1565C0,#0D47A1);">
                        <i class="fas fa-check-circle me-2"></i>Add Resource
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Resource Modal -->
    <div class="modal fade" id="editResourceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4 pb-2">
                    <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2" style="color:#E65100;"></i>Edit Resource
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-2">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="resource_id" id="edit_id">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Grade</label>
                            <select name="grade" id="edit_grade" class="form-select rounded-pill bg-light border-0 px-4"
                                required>
                                <?php for ($g = 1; $g <= 12; $g++): ?>
                                    <option value="<?php echo $g; ?>">Grade
                                        <?php echo $g; ?>
                                    </option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Subject</label>
                            <select name="subject" id="edit_subject"
                                class="form-select rounded-pill bg-light border-0 px-4" required>
                                <?php foreach ($all_subjects as $subj): ?>
                                    <option value="<?php echo $subj; ?>">
                                        <?php echo $subj; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" id="edit_type" class="form-select rounded-pill bg-light border-0 px-4"
                                required onchange="toggleEditFields()">
                                <option value="textbook">📘 Textbook</option>
                                <option value="teacher_guide">📙 Teacher Guide</option>
                                <option value="video">🎬 Video</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_status"
                                class="form-select rounded-pill bg-light border-0 px-4">
                                <option value="active">Active</option>
                                <option value="draft">Draft</option>
                                <option value="archived">Archived</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Title</label>
                            <input type="text" name="title" id="edit_title"
                                class="form-control rounded-pill bg-light border-0 px-4" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" id="edit_description"
                                class="form-control bg-light border-0 px-4" rows="2"
                                style="border-radius:15px;"></textarea>
                        </div>
                        <div class="col-12 edit-file-fields">
                            <label class="form-label small fw-bold">File/Download URL</label>
                            <input type="url" name="file_url" id="edit_file_url"
                                class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                        <div class="col-md-6 edit-video-fields" style="display:none;">
                            <label class="form-label small fw-bold">YouTube Video ID</label>
                            <input type="text" name="video_id" id="edit_video_id"
                                class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                        <div class="col-md-6 edit-video-fields" style="display:none;">
                            <label class="form-label small fw-bold">Full Video URL</label>
                            <input type="url" name="video_url" id="edit_video_url"
                                class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                        <div class="col-md-4 edit-book-fields">
                            <label class="form-label small fw-bold">Units</label>
                            <input type="number" name="units" id="edit_units"
                                class="form-control rounded-pill bg-light border-0 px-4" min="0">
                        </div>
                        <div class="col-md-4 edit-book-fields">
                            <label class="form-label small fw-bold">Pages</label>
                            <input type="number" name="pages" id="edit_pages"
                                class="form-control rounded-pill bg-light border-0 px-4" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Edition</label>
                            <input type="text" name="edition" id="edit_edition"
                                class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                    </div>
                    <button type="submit" name="edit_resource"
                        class="btn w-100 rounded-pill py-3 fw-bold mt-4 shadow text-white"
                        style="background:linear-gradient(135deg,#E65100,#FF9800);">
                        <i class="fas fa-save me-2"></i>Update Resource
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleAddFields() {
            const type = document.getElementById('addType').value;
            const fileFields = document.querySelectorAll('.add-file-fields');
            const videoFields = document.querySelectorAll('.add-video-fields');
            const bookFields = document.querySelectorAll('.add-book-fields');

            if (type === 'video') {
                fileFields.forEach(el => el.style.display = 'none');
                videoFields.forEach(el => el.style.display = '');
                bookFields.forEach(el => el.style.display = 'none');
            } else {
                fileFields.forEach(el => el.style.display = '');
                videoFields.forEach(el => el.style.display = 'none');
                bookFields.forEach(el => el.style.display = '');
            }
        }

        function toggleEditFields() {
            const type = document.getElementById('edit_type').value;
            const fileFields = document.querySelectorAll('.edit-file-fields');
            const videoFields = document.querySelectorAll('.edit-video-fields');
            const bookFields = document.querySelectorAll('.edit-book-fields');

            if (type === 'video') {
                fileFields.forEach(el => el.style.display = 'none');
                videoFields.forEach(el => el.style.display = '');
                bookFields.forEach(el => el.style.display = 'none');
            } else {
                fileFields.forEach(el => el.style.display = '');
                videoFields.forEach(el => el.style.display = 'none');
                bookFields.forEach(el => el.style.display = '');
            }
        }

        function editResource(res) {
            document.getElementById('edit_id').value = res.id;
            document.getElementById('edit_grade').value = res.grade;
            document.getElementById('edit_subject').value = res.subject;
            document.getElementById('edit_type').value = res.type;
            document.getElementById('edit_title').value = res.title;
            document.getElementById('edit_description').value = res.description || '';
            document.getElementById('edit_file_url').value = res.file_url || '';
            document.getElementById('edit_video_id').value = res.video_id || '';
            document.getElementById('edit_video_url').value = res.video_url || '';
            document.getElementById('edit_units').value = res.units || 0;
            document.getElementById('edit_pages').value = res.pages || 0;
            document.getElementById('edit_edition').value = res.edition || '2023';
            document.getElementById('edit_status').value = res.status || 'active';

            toggleEditFields();

            new bootstrap.Modal(document.getElementById('editResourceModal')).show();
        }
    </script>
</body>

</html>