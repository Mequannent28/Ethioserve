<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();

$user_id   = getCurrentUserId();
$user_role = getCurrentUserRole();
$user_name = getCurrentUserName();

// ── Auto-enroll if missing profile ──────────────────────────────
$profile = null;
if ($user_role === 'student' || in_array($user_role, ['admin', 'school_admin'])) {
    $target_id = ($user_role === 'student') ? $user_id : ($_GET['student_id'] ?? $pdo->query("SELECT user_id FROM sms_student_profiles LIMIT 1")->fetchColumn());
    
    $stmt = $pdo->prepare("SELECT p.*, c.class_name, c.section, u.full_name FROM sms_student_profiles p LEFT JOIN sms_classes c ON p.class_id = c.id JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
    $stmt->execute([$target_id]);
    $profile = $stmt->fetch();
    
    if (in_array($user_role, ['admin', 'school_admin'])) $is_preview = true;
}

$children = [];
$active_child = null;
if ($user_role === 'parent') {
    $stmt = $pdo->prepare("SELECT p.user_id, u.full_name FROM sms_student_profiles p JOIN users u ON p.user_id = u.id WHERE p.parent_id = (SELECT id FROM sms_parents WHERE user_id = ?)");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll();

    $target_id = (int)($_GET['student_id'] ?? ($children[0]['user_id'] ?? 0));
    if ($target_id) {
        $stmt2 = $pdo->prepare("SELECT p.*, c.class_name, c.section, u.full_name FROM sms_student_profiles p LEFT JOIN sms_classes c ON p.class_id = c.id JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
        $stmt2->execute([$target_id]);
        $profile = $stmt2->fetch();
        $active_child = $target_id;
    }
}

// Stats when profile exists
$attendance_pct = 0;
$assignments    = [];
$timetable      = [];
$recent_grades  = [];

if ($profile) {
    $class_id = $profile['class_id'];

    // Attendance
    $att = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='Present' THEN 1 ELSE 0 END) as present FROM sms_attendance WHERE student_id = ?");
    $att->execute([$profile['user_id']]);
    $att_row = $att->fetch();
    $attendance_pct = $att_row['total'] > 0 ? round(($att_row['present'] / $att_row['total']) * 100) : 0;

    // Upcoming Assignments
    $stmt = $pdo->prepare("SELECT a.*, s.subject_name FROM sms_assignments a JOIN sms_subjects s ON a.subject_id = s.id WHERE a.class_id = ? AND a.due_date >= NOW() ORDER BY a.due_date ASC LIMIT 5");
    $stmt->execute([$class_id]);
    $assignments = $stmt->fetchAll();

    // Timetable
    $stmt = $pdo->prepare("SELECT t.*, s.subject_name FROM sms_timetables t JOIN sms_subjects s ON t.subject_id = s.id WHERE t.class_id = ? ORDER BY FIELD(day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday'), start_time");
    $stmt->execute([$class_id]);
    $timetable = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

    // Graded submissions
    $stmt = $pdo->prepare("SELECT ss.*, a.title, s.subject_name FROM sms_assignment_submissions ss JOIN sms_assignments a ON ss.assignment_id = a.id JOIN sms_subjects s ON a.subject_id = s.id WHERE ss.student_id = ? AND ss.grade IS NOT NULL ORDER BY ss.submission_date DESC LIMIT 5");
    $stmt->execute([$profile['user_id']]);
    $recent_grades = $stmt->fetchAll();

    // Fetch Notifications (Attendance Warnings etc)
    $stmt = $pdo->prepare("SELECT * FROM sms_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$profile['user_id']]);
    $notifications = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Portal - EthioServe Education</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-gold: #F9A825; --edu-accent: #2E7D32; }
        body { background: #f0f4f8; font-family: 'Outfit', sans-serif; color: #1a1a2e; }
        
        /* Sidebar */
        .sidebar { 
            width: 280px; 
            background: var(--edu-green); 
            color: #fff;
            border-right: none; 
            height: 100vh; 
            position: fixed; 
            left: 0; 
            top: 0; 
            padding: 2rem 0; 
            z-index: 1000; 
            overflow-y: auto; 
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
        }
        .main-content { margin-left: 280px; padding: 2.5rem; min-height: 100vh; }
        
        .nav-link-custom { 
            padding: 12px 24px; 
            color: rgba(255,255,255,0.9); 
            font-weight: 500; 
            display: flex; 
            align-items: center; 
            gap: 14px; 
            text-decoration: none; 
            transition: all 0.3s; 
            border-left: 4px solid transparent; 
        }
        .nav-link-custom:hover { background: rgba(255,255,255,0.1); color: #fff; }
        .nav-link-custom.active { 
            background: rgba(0,0,0,0.2); 
            color: var(--edu-gold); 
            border-left-color: var(--edu-gold); 
            font-weight: 700; 
        }
        
        .profile-box { text-align: center; padding: 0 24px 2rem; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 1.5rem; }
        .profile-img { width: 90px; height: 90px; border-radius: 24px; border: 4px solid rgba(255,255,255,0.2); box-shadow: 0 4px 15px rgba(0,0,0,0.2); margin-bottom: 1rem; }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--edu-green), #2E7D32);
            color: #fff;
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(27, 94, 32, 0.15);
            margin-bottom: 2.5rem;
        }
        .welcome-card::after {
            content: '\f51c';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -40px;
            font-size: 12rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .stat-widget { background: #fff; border-radius: 24px; padding: 1.5rem; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border: 1px solid #f1f5f9; height: 100%; }
        .progress-ring { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; position: relative; }
        
        .day-scroller { display: flex; gap: 10px; overflow-x: auto; padding-bottom: 15px; scrollbar-width: none; }
        .day-scroller::-webkit-scrollbar { display: none; }
        .day-card { min-width: 120px; background: #fff; border-radius: 16px; padding: 12px; text-align: center; border: 1px solid transparent; cursor: pointer; transition: all 0.3s; }
        .day-card.active { background: var(--edu-green); color: #fff; box-shadow: 0 8px 16px rgba(27,94,32,0.2); }
        
        .assignment-premium { border-left: 6px solid var(--edu-gold); background: #fff; border-radius: 16px; padding: 1.5rem; margin-bottom: 1rem; box-shadow: 0 4px 12px rgba(0,0,0,0.02); }
        
        @media (max-width: 992px) {
            .sidebar { display: none; }
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>

    <?php if (isset($is_preview) && $profile): ?>
    <div class="bg-warning text-dark text-center py-2 fw-bold sticky-top">
        <i class="fas fa-eye me-2"></i> PREVIEW MODE: You are viewing as <strong><?= htmlspecialchars($profile['full_name']) ?></strong>
        <a href="../admin/manage_school.php" class="ms-3 text-dark text-decoration-underline small">Back to Admin</a>
    </div>
    <?php elseif(isset($is_preview)): ?>
    <div class="bg-danger text-white text-center py-2 fw-bold sticky-top">
        <i class="fas fa-exclamation-triangle me-2"></i> PREVIEW ERROR: No student profiles found in the system.
        <a href="../admin/manage_school.php" class="ms-3 text-white text-decoration-underline small">Back to Admin</a>
    </div>
    <?php endif; ?>

    <?php if (!$profile): ?>
        <div class="main-content" style="margin-left: 0; width: 100%;">
            <div class="card border-0 shadow-lg p-5 text-center rounded-4 max-width-600 mx-auto mt-5" style="max-width: 600px;">
                <div class="bg-light p-4 rounded-circle d-inline-block mb-4">
                    <i class="fas fa-user-clock fa-4x text-muted opacity-50"></i>
                </div>
                <h2 class="fw-bold mb-3">Enrollment Pending</h2>
                <p class="text-muted mb-5">Your profile hasn't been set up in the school management system yet. Please contact your school administrator to complete your enrollment.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="../logout.php" class="btn btn-outline-danger rounded-pill px-4 py-2 fw-bold">Logout</a>
                    <a href="chat.php" class="btn btn-edu-green text-white rounded-pill px-4 py-2 fw-bold">Contact Support</a>
                </div>
            </div>
        </div>
        <style>.sidebar { display:none !important; } .main-content { margin-left:0 !important; }</style>
    <?php else: ?>
        <aside class="sidebar">
            <div class="profile-box">
                <img src="https://ui-avatars.com/api/?name=<?= urlencode($profile['full_name']) ?>&background=F9A825&color=fff&size=200" class="profile-img">
                <h5 class="fw-bold mb-0 text-truncate text-capitalize text-white"><?= htmlspecialchars($profile['full_name']) ?></h5>
                <p class="text-white-50 small mb-2"><?= htmlspecialchars(($profile['class_name'] ?? 'No Class') . ' - ' . ($profile['section'] ?? 'No Section')) ?></p>
                <span class="badge bg-black bg-opacity-20 text-white rounded-pill border-0 py-1 px-3">ID: <?= $profile['student_id_number'] ?? 'N/A' ?></span>
            </div>
            
            <nav class="d-grid gap-1">
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-calendar-alt"></i> Weekly Schedule
                </a>
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-tasks"></i> Assignments
                </a>
                <a href="dashboard.php" class="nav-link-custom">
                    <i class="fas fa-chart-line"></i> Academic Grades
                </a>
                <a href="chat.php" class="nav-link-custom active">
                    <i class="fas fa-comments"></i> Chat with Teachers
                </a>
                <div class="px-4 py-3"><small class="text-white-50 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Resources</small></div>
                <a href="../customer/lms.php" class="nav-link-custom">
                    <i class="fas fa-book-open"></i> E-Learning (LMS)
                </a>
                <a href="../customer/lms.php?tab=exams" class="nav-link-custom">
                    <i class="fas fa-file-signature"></i> Online Exams
                </a>
                <div class="mt-5 px-3">
                    <a href="../logout.php" class="btn btn-warning w-100 rounded-pill py-2 fw-bold shadow-sm">
                        <i class="fas fa-sign-out-alt me-2"></i>Logout
                    </a>
                </div>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="main-content" style="padding: 0; overflow: hidden; height: 100vh;">
            <iframe src="../chat.php<?= isset($_GET['user_id']) ? '?user_id=' . urlencode($_GET['user_id']) : '' ?>" 
                    style="width: 100%; height: 100%; border: none;"></iframe>
        </main>
    <?php endif; ?>


    <style>
        .btn-edu-green { background: #1B5E20; color: white; }
        .btn-edu-green:hover { background: #2E7D32; color: white; }
        .bg-soft-success { background: #e8f5e9; color: #1B5E20; }
        .bg-soft-warning { background: #fff8e1; color: #f9a825; }
        .letter-spacing-1 { letter-spacing: 1px; }
    </style>

    <!-- Notice View Modal -->
    <div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold text-success"><i class="fas fa-file-invoice me-2"></i>Official Warning Notice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh;">
                    <iframe id="noticeFrame" src="" frameborder="0" style="width: 100%; height: 100%;"></iframe>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-edu-green text-white rounded-pill px-4" onclick="document.getElementById('noticeFrame').contentWindow.print()">
                        <i class="fas fa-print me-2"></i>Print Notice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function switchSection(id, el) {
        document.querySelectorAll('.content-section').forEach(s => s.style.display = 'none');
        document.getElementById('section-' + id).style.display = 'block';
        
        document.querySelectorAll('.nav-link-custom').forEach(n => n.classList.remove('active'));
        el.classList.add('active');
    }

    // Notification Modal Logic
    document.addEventListener('DOMContentLoaded', function() {
        const viewBtns = document.querySelectorAll('.view-notif-btn');
        const noticeFrame = document.getElementById('noticeFrame');
        const viewNoticeModal = document.getElementById('viewNoticeModal');
        if (viewNoticeModal) {
            const modal = new bootstrap.Modal(viewNoticeModal);
            
            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    noticeFrame.src = url;
                    modal.show();
                });
            });

            viewNoticeModal.addEventListener('hidden.bs.modal', function () {
                noticeFrame.src = "";
            });
        }
    });
    </script>
</body>
</html>
