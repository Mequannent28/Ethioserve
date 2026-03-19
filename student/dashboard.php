<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();

$user_id   = getCurrentUserId();
$user_role = getCurrentUserRole();
$user_name = getCurrentUserName();

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_assignment' && $user_role === 'student') {
    $assignment_id = (int)$_POST['assignment_id'];
    $submission_text = sanitize($_POST['submission_text'] ?? '');
    
    $file_path = null;
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/assignments/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
        $ext = pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION);
        $file_name = time() . '_' . uniqid() . '.' . $ext;
        $dest = $upload_dir . $file_name;
        if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $dest)) {
            $file_path = $dest;
        }
    }

    try {
        // Check if student already submitted
        $check = $pdo->prepare("SELECT id, file_path FROM sms_assignment_submissions WHERE assignment_id = ? AND student_id = ?");
        $check->execute([$assignment_id, $user_id]);
        $existing = $check->fetch();
        
        if ($existing) {
            // Unlink old file if new one is uploaded
            if ($file_path && $existing['file_path'] && file_exists($existing['file_path'])) {
                unlink($existing['file_path']);
            }
            
            $stmt = $pdo->prepare("UPDATE sms_assignment_submissions SET submission_text = ?, file_path = COALESCE(?, file_path), submission_date = NOW() WHERE id = ?");
            $stmt->execute([$submission_text, $file_path, $existing['id']]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO sms_assignment_submissions (assignment_id, student_id, submission_text, file_path, submission_date) VALUES (?, ?, ?, ?, NOW())");
            $stmt->execute([$assignment_id, $user_id, $submission_text, $file_path]);
        }
        setFlashMessage("Assignment uploaded successfully!", "success");
    } catch (Exception $e) {
        setFlashMessage("Error: " . $e->getMessage(), "danger");
    }
    header("Location: dashboard.php");
    exit;
}

// ── Auto-enroll if missing profile ──────────────────────────────
$profile = null;
if ($user_role === 'student' || in_array($user_role, ['admin', 'school_admin'])) {
    $target_id = ($user_role === 'student') ? $user_id : ($_GET['student_id'] ?? $pdo->query("SELECT user_id FROM sms_student_profiles LIMIT 1")->fetchColumn());
    
    $stmt = $pdo->prepare("SELECT p.*, c.class_name, c.section, u.full_name, u.profile_photo FROM sms_student_profiles p LEFT JOIN sms_classes c ON p.class_id = c.id JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
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
        $stmt2 = $pdo->prepare("SELECT p.*, c.class_name, c.section, u.full_name, u.profile_photo FROM sms_student_profiles p LEFT JOIN sms_classes c ON p.class_id = c.id JOIN users u ON p.user_id = u.id WHERE p.user_id = ?");
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
    $stmt = $pdo->prepare("
        SELECT a.*, s.subject_name,
               (SELECT id FROM sms_assignment_submissions WHERE assignment_id = a.id AND student_id = ?) as sub_id,
               (SELECT submission_date FROM sms_assignment_submissions WHERE assignment_id = a.id AND student_id = ?) as sub_date
        FROM sms_assignments a 
        JOIN sms_subjects s ON a.subject_id = s.id 
        WHERE a.class_id = ? AND a.due_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY a.due_date DESC 
        LIMIT 10
    ");
    $stmt->execute([$target_id ?? $user_id, $target_id ?? $user_id, $class_id]);
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
                <?php if (!empty($profile['profile_photo'])): ?>
                    <img src="<?= htmlspecialchars($profile['profile_photo']) ?>" class="profile-img shadow" style="object-fit: cover; border: 4px solid rgba(255,255,255,0.2); width: 120px; height: 120px; border-radius: 20px;">
                <?php else: ?>
                    <img src="https://ui-avatars.com/api/?name=<?= urlencode($profile['full_name']) ?>&background=F9A825&color=fff&size=200" class="profile-img" style="border-radius: 20px;">
                <?php endif; ?>
                <h5 class="fw-bold mt-3 mb-0 text-truncate text-capitalize text-white"><?= htmlspecialchars($profile['full_name']) ?></h5>
                <p class="text-white-50 small mb-2"><?= htmlspecialchars(($profile['class_name'] ?? 'No Class') . ' - ' . ($profile['section'] ?? 'No Section')) ?></p>
                <span class="badge bg-black bg-opacity-20 text-white rounded-pill border-0 py-1 px-3">ID: <?= $profile['student_id_number'] ?? 'N/A' ?></span>
            </div>
            
            <nav class="d-grid gap-1">
                <a href="#" class="nav-link-custom active" onclick="switchSection('dashboard', this)">
                    <i class="fas fa-th-large"></i> Dashboard
                </a>
                <a href="#" class="nav-link-custom" onclick="switchSection('timetable', this)">
                    <i class="fas fa-calendar-alt"></i> Weekly Schedule
                </a>
                <a href="#" class="nav-link-custom" onclick="switchSection('assignments', this)">
                    <i class="fas fa-tasks"></i> Assignments
                </a>
                <a href="#" class="nav-link-custom" onclick="switchSection('grades', this)">
                    <i class="fas fa-chart-line"></i> Academic Grades
                </a>
                <a href="chat.php" class="nav-link-custom">
                    <i class="fas fa-comments"></i> Chat with Teachers
                </a>
                <div class="px-4 py-3"><small class="text-white-50 text-uppercase fw-bold letter-spacing-1" style="font-size: 0.7rem;">Resources</small></div>
                <a href="#" class="nav-link-custom" onclick="switchSection('elearning', this)">
                    <i class="fas fa-book-open"></i> E-Learning (LMS)
                </a>
                <a href="#" class="nav-link-custom" onclick="switchSection('exams', this)">
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
        <main class="main-content">
            <div class="welcome-card">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <?php if ($user_role === 'parent'): ?>
                            <div class="d-flex align-items-center gap-3">
                                <h2 class="fw-bold mb-0">Child Portfolio</h2>
                                <div class="dropdown">
                                    <button class="btn btn-white bg-white rounded-pill border-0 px-4 dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        Select Student
                                    </button>
                                    <ul class="dropdown-menu shadow-lg border-0 p-2">
                                        <?php foreach($children as $ch): ?>
                                        <li><a class="dropdown-item rounded-3 <?= $active_child == $ch['user_id'] ? 'active bg-success' : '' ?>" href="?student_id=<?= $ch['user_id'] ?>"><?= htmlspecialchars($ch['full_name']) ?></a></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php else: ?>
                            <h2 class="fw-bold mb-1">Welcome Back, <?= htmlspecialchars(explode(' ', $profile['full_name'])[0]) ?>! 👋</h2>
                        <?php endif; ?>
                        <p class="opacity-75 mb-0">Academic Year 2024/25 • Semester 2 • Global Status: <span class="badge bg-success bg-opacity-25 text-white">Active</span></p>
                    </div>
                </div>
            </div>

            <!-- Dashboard Notifications/Alerts -->
            <?php if (!empty($notifications)): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-5 overflow-hidden">
                <div class="card-header bg-white border-0 py-3 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0 text-success"><i class="fas fa-bell me-2"></i>System Notifications</h5>
                    <span class="badge bg-success bg-opacity-10 text-success rounded-pill"><?= count($notifications) ?> New</span>
                </div>
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead class="bg-light">
                            <tr class="small text-muted text-uppercase">
                                <th class="px-4">Notice Title</th>
                                <th>Summary</th>
                                <th>Status</th>
                                <th class="text-end px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach($notifications as $notif): 
                                $is_warning = ($notif['type'] === 'warning');
                                // Extract the URL if present in markdown [Label](url)
                                preg_match('/\[(.*?)\]\((.*?)\)/', $notif['message'], $matches);
                                $view_url = $matches[2] ?? '#';
                                $clean_message = preg_replace('/\[(.*?)\]\((.*?)\)/', '$1', $notif['message']);
                            ?>
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-<?= $is_warning ? 'danger' : 'primary' ?> bg-opacity-10 p-2 rounded-3 text-<?= $is_warning ? 'danger' : 'primary' ?>">
                                            <i class="fas fa-<?= $is_warning ? 'exclamation-triangle' : 'info-circle' ?>"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($notif['title']) ?></div>
                                            <small class="text-muted"><?= time_ago($notif['created_at']) ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <small class="text-muted d-block text-truncate" style="max-width: 300px;">
                                        <?= htmlspecialchars(substr($clean_message, 0, 80)) ?>...
                                    </small>
                                </td>
                                <td>
                                    <?php if($is_warning): ?>
                                        <span class="badge bg-danger rounded-pill px-3">CRITICAL</span>
                                    <?php else: ?>
                                        <span class="badge bg-primary rounded-pill px-3">INFO</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end px-4">
                                    <button type="button" class="btn btn-sm btn-edu-green text-white rounded-pill px-4 shadow-sm view-notif-btn" 
                                            data-url="<?= $view_url ?>">
                                        <i class="fas fa-eye me-1"></i> View
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>

        <div id="section-dashboard" class="content-section">
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="stat-widget d-flex align-items-center justify-content-between">
                        <div>
                            <p class="text-muted small fw-bold mb-1">ATTENDANCE</p>
                            <h2 class="fw-bold mb-0"><?= $attendance_pct ?>%</h2>
                            <small class="<?= $attendance_pct > 75 ? 'text-success' : 'text-danger' ?>">
                                <i class="fas fa-<?= $attendance_pct > 75 ? 'check' : 'warning' ?> me-1"></i>
                                <?= $attendance_pct > 75 ? 'On Track' : 'Below Target' ?>
                            </small>
                        </div>
                        <div class="progress-ring" style="background: conic-gradient(var(--edu-green) <?= $attendance_pct * 3.6 ?>deg, #f1f5f9 0);">
                            <div style="background:#fff; inset:8px; position:absolute; border-radius:50%; display:flex; align-items:center; justify-content:center;"><?= $attendance_pct ?>%</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget">
                        <p class="text-muted small fw-bold mb-1">PENDING TASKS</p>
                        <h2 class="fw-bold mb-0"><?= count($assignments) ?></h2>
                        <div class="progress mt-3" style="height: 8px; border-radius:10px;">
                            <div class="progress-bar bg-warning" style="width: 60%; border-radius:10px;"></div>
                        </div>
                        <small class="text-muted mt-2 d-block">Next due: <?= !empty($assignments) ? date('M d', strtotime($assignments[0]['due_date'])) : 'N/A' ?></small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-widget bg-dark text-white border-0 shadow-lg">
                        <p class="text-white-50 small fw-bold mb-1">GRADE POINT AVG</p>
                        <h2 class="fw-bold mb-0">3.8 / 4.0</h2>
                        <div class="d-flex align-items-center gap-2 mt-3">
                            <span class="badge bg-success">Rank #4</span>
                            <span class="text-white-50 small">Top 10% of class</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm p-4 rounded-4 h-100">
                        <div class="d-flex justify-content-between mb-4">
                            <h5 class="fw-bold">Next Lessons Today</h5>
                            <a href="#" class="small text-edu-green text-decoration-none fw-bold">Full Schedule</a>
                        </div>
                        
                        <?php 
                        $today = date('l');
                        $today_lessons = $timetable[$today] ?? [];
                        if(empty($today_lessons)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-mug-hot fa-3x text-muted opacity-30 mb-3"></i>
                                <p class="text-muted">No scheduled classes for today!</p>
                            </div>
                        <?php else: foreach($today_lessons as $l): ?>
                            <div class="d-flex align-items-center gap-3 p-3 border rounded-4 mb-3">
                                <div class="bg-light p-3 rounded-4 fw-bold text-dark" style="min-width:70px; text-align:center;">
                                    <?= date('H:i', strtotime($l['start_time'])) ?>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?= htmlspecialchars($l['subject_name']) ?></h6>
                                    <small class="text-muted">Teacher: Ms. Abeba • Room 302</small>
                                </div>
                                <div class="ms-auto">
                                    <span class="badge bg-soft-success text-success">Live Soon</span>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm p-4 rounded-4 h-100 bg-white">
                        <h5 class="fw-bold mb-4">Quick Links</h5>
                        <div class="d-grid gap-3">
                            <a href="../customer/lms.php" class="btn btn-light py-3 rounded-4 border text-start d-flex justify-content-between">
                                <span><i class="fas fa-play-circle text-danger me-2"></i> Watch Video Lessons</span>
                                <i class="fas fa-arrow-right small opacity-50"></i>
                            </a>
                            <a href="../customer/lms.php?tab=exams" class="btn btn-light py-3 rounded-4 border text-start d-flex justify-content-between">
                                <span><i class="fas fa-laptop-code text-primary me-2"></i> Take Active Quiz</span>
                                <i class="fas fa-arrow-right small opacity-50"></i>
                            </a>
                            <a href="chat.php" class="btn btn-light py-3 rounded-4 border text-start d-flex justify-content-between">
                                <span><i class="fas fa-paper-plane text-warning me-2"></i> Message Teacher</span>
                                <i class="fas fa-arrow-right small opacity-50"></i>
                            </a>
                        </div>
                        
                        <div class="mt-4 p-4 rounded-4" style="background: linear-gradient(135deg, #1B5E20, #2E7D32); color:#fff;">
                            <h6 class="fw-bold mb-2">School Announcement</h6>
                            <p class="small opacity-80 mb-0">Mid-term results will be published on March 25th. Good luck to all students!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timetable Section -->
        <div id="section-timetable" class="content-section" style="display:none;">
            <h4 class="fw-bold mb-4">Current Weekly Schedule</h4>
            <div class="card border-0 shadow-sm p-4 rounded-4">
                <?php if (empty($timetable)): ?>
                    <p class="text-center text-muted py-5">No schedule published yet.</p>
                <?php else: ?>
                    <?php foreach ($timetable as $day => $lessons): ?>
                        <div class="fw-bold text-success mt-4 mb-2 d-flex align-items-center gap-2">
                             <div class="bg-success rounded-circle" style="width:8px; height:8px;"></div> <?= $day ?>
                        </div>
                        <div class="row g-3">
                            <?php foreach ($lessons as $l): ?>
                            <div class="col-md-6">
                                <div class="bg-light p-3 rounded-4 border d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="fw-bold"><?= htmlspecialchars($l['subject_name']) ?></div>
                                        <div class="text-muted small"><?= date('h:i A', strtotime($l['start_time'])) ?> - <?= date('h:i A', strtotime($l['end_time'] ?? $l['start_time'])) ?></div>
                                    </div>
                                    <i class="fas fa-clock text-muted opacity-50"></i>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments Section -->
        <div id="section-assignments" class="content-section" style="display:none;">
            <h4 class="fw-bold mb-4">Assignments & Homework</h4>
            <?php if (empty($assignments)): ?>
                <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                    <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                    <h5 class="fw-bold">You are all caught up!</h5>
                    <p class="text-muted">No pending assignments due at the moment.</p>
                </div>
            <?php else: foreach($assignments as $a): ?>
                <div class="assignment-premium">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <span class="badge bg-soft-warning text-warning mb-2"><?= htmlspecialchars($a['subject_name']) ?></span>
                            <h5 class="fw-bold mb-0"><?= htmlspecialchars($a['title']) ?></h5>
                        </div>
                        <div class="text-end">
                            <div class="text-danger fw-bold small">Due: <?= date('M d, Y, h:i A', strtotime($a['due_date'])) ?></div>
                            <div class="text-muted small">Max Score: <?= $a['max_points'] ?> pts</div>
                        </div>
                    </div>
                    <p class="text-muted small"><?= nl2br(htmlspecialchars($a['description'])) ?></p>
                    <hr>
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <?php if ($user_role === 'student'): ?>
                                <button class="btn btn-edu-green text-white rounded-pill px-4 btn-sm fw-bold" onclick="openSubmitModal(<?= $a['id'] ?>, '<?= htmlspecialchars(addslashes($a['title'])) ?>')">
                                    <?= $a['sub_id'] ? '<i class="fas fa-check-circle me-1"></i> Resubmit Work' : 'Submit Work' ?>
                                </button>
                            <?php endif; ?>
                            <?php if (!empty($a['file_path'])): ?>
                                <a href="<?= htmlspecialchars($a['file_path']) ?>" download class="btn btn-light rounded-pill px-4 btn-sm fw-bold border ms-2"><i class="fas fa-download me-1"></i> Download Material</a>
                            <?php endif; ?>
                        </div>
                        <?php if ($a['sub_date']): ?>
                            <small class="text-success fw-bold"><i class="fas fa-check"></i> Submitted on <?= date('M d, h:i A', strtotime($a['sub_date'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; endif; ?>
        </div>

        <!-- Submit Assignment Modal -->
        <div class="modal fade" id="submitModal" tabindex="-1">
            <div class="modal-dialog">
                <form method="POST" enctype="multipart/form-data" class="modal-content border-0">
                    <input type="hidden" name="action" value="submit_assignment">
                    <input type="hidden" name="assignment_id" id="submit_assignment_id">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Submit Assignment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3" id="submit_assignment_title"></p>
                        
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Upload Your File (PDF, DOCX, ZIP, etc.)</label>
                            <input type="file" name="assignment_file" class="form-control rounded-3">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Additional Notes / Text Answer</label>
                            <textarea name="submission_text" class="form-control rounded-3" rows="4" placeholder="Any comments or direct text submission..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold">Turn In <i class="fas fa-paper-plane ms-1"></i></button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Grades Section -->
        <div id="section-grades" class="content-section" style="display:none;">
            <h4 class="fw-bold mb-4">Academic Grade Report</h4>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <table class="table table-hover mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th class="px-4 py-3">Assignment/Exam</th>
                            <th class="py-3">Subject</th>
                            <th class="py-3">Grade</th>
                            <th class="py-3">Status</th>
                            <th class="py-3 text-end px-4">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($recent_grades)): ?>
                            <tr><td colspan="5" class="text-center py-5 text-muted">No graded records found.</td></tr>
                        <?php else: foreach($recent_grades as $g): ?>
                            <tr>
                                <td class="px-4 py-3 fw-bold"><?= htmlspecialchars($g['title']) ?></td>
                                <td class="py-3"><?= htmlspecialchars($g['subject_name']) ?></td>
                                <td class="py-3"><span class="badge bg-success rounded-pill px-3"><?= $g['grade'] ?></span></td>
                                <td class="py-3 text-muted small">Graded on <?= date('M d', strtotime($g['submission_date'])) ?></td>
                                <td class="py-3 text-end px-4"><button class="btn btn-sm btn-light border rounded-pill">View Feedback</button></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Embedded Resources -->
        <div id="section-elearning" class="content-section shadow-sm rounded-4" style="display:none; padding:0; height:calc(100vh - 80px); overflow:hidden; background:var(--edu-green);">
            <iframe src="../customer/education.php?iframe=1" style="width:100%; height:100%; border:none;"></iframe>
        </div>

        <div id="section-exams" class="content-section shadow-sm rounded-4" style="display:none; padding:0; height:calc(100vh - 80px); overflow:hidden; background:#0a0f1a;">
            <iframe src="../customer/lms.php?iframe=1" style="width:100%; height:100%; border:none;"></iframe>
        </div>

    <?php endif; ?>
    </main>


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

    function openSubmitModal(id, title) {
        document.getElementById('submit_assignment_id').value = id;
        document.getElementById('submit_assignment_title').innerText = "Turning in for: " + title;
        new bootstrap.Modal(document.getElementById('submitModal')).show();
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
