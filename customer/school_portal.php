<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();

$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Determine which student we are looking at
$target_student_id = $user_id;

if ($user_role === 'parent') {
    // If parent, find their linked children
    $stmt = $pdo->prepare("SELECT user_id FROM sms_student_profiles WHERE parent_id = (SELECT id FROM sms_parents WHERE user_id = ?)");
    $stmt->execute([$user_id]);
    $children = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $target_student_id = $_GET['student_id'] ?? ($children[0] ?? null);
} elseif (in_array($user_role, ['admin', 'school_admin'])) {
    // If admin, allowing preview of any student (default to first one if not specified)
    $target_student_id = $_GET['student_id'] ?? $pdo->query("SELECT user_id FROM sms_student_profiles LIMIT 1")->fetchColumn();
    
    if (!$target_student_id) {
        die("No students found in the system to preview. Please enroll a student first via Admin Panel.");
    }
    
    $is_preview = true;
}

if (!$target_student_id) {
    die("Student identifier missing. Please log in as a student or parent.");
}

// Fetch Student Profile & Class
$stmt = $pdo->prepare("
    SELECT p.*, c.class_name, c.section, u.full_name 
    FROM sms_student_profiles p
    JOIN users u ON p.user_id = u.id
    LEFT JOIN sms_classes c ON p.class_id = c.id
    WHERE p.user_id = ?
");
$stmt->execute([$target_student_id]);
$profile = $stmt->fetch();

if (!$profile) {
    die("Student profile not found. Please complete enrollment.");
}

$class_id = $profile['class_id'];

// Get Attendance Percentage
$attendance_stats = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'Present' THEN 1 ELSE 0 END) as present
    FROM sms_attendance 
    WHERE student_id = ?
");
$attendance_stats->execute([$target_student_id]);
$attendance = $attendance_stats->fetch();
$attendance_pct = ($attendance['total'] > 0) ? round(($attendance['present'] / $attendance['total']) * 100) : 0;

// Get Upcoming Assignments
$stmt = $pdo->prepare("
    SELECT a.*, s.subject_name 
    FROM sms_assignments a
    JOIN sms_subjects s ON a.subject_id = s.id
    WHERE a.class_id = ? AND a.due_date >= NOW()
    ORDER BY a.due_date ASC LIMIT 5
");
$stmt->execute([$class_id]);
$assignments = $stmt->fetchAll();

// Get Timetable
$stmt = $pdo->prepare("
    SELECT t.*, s.subject_name 
    FROM sms_timetables t
    JOIN sms_subjects s ON t.subject_id = s.id
    WHERE t.class_id = ?
    ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time
");
$stmt->execute([$class_id]);
$timetable = $stmt->fetchAll(PDO::FETCH_GROUP|PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Portal - EthioServe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --school-green: #1B5E20; --school-gold: #F9A825; }
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; }
        .navbar-school { background: var(--school-green); box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .portal-card { border-radius: 20px; border: none; box-shadow: 0 4px 20px rgba(0,0,0,0.05); }
        .stat-badge { width: 60px; height: 60px; display: flex; align-items: center; justify-content: center; border-radius: 15px; font-size: 1.5rem; }
        .timetable-day { font-weight: bold; color: var(--school-green); background: #e8f5e9; padding: 10px; border-radius: 10px; margin-top: 15px; }
        .assignment-row { border-left: 4px solid var(--school-gold); padding-left: 15px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php if (isset($is_preview) && $is_preview): ?>
    <div class="bg-warning text-dark text-center py-2 fw-bold small">
        <i class="fas fa-eye me-2"></i> PREVIEW MODE: You are viewing the portal as <strong><?php echo htmlspecialchars($profile['full_name']); ?></strong>. 
        <a href="../admin/manage_school.php" class="text-dark ms-3"><u>Back to Admin</u></a>
    </div>
    <?php endif; ?>

    <nav class="navbar navbar-dark navbar-school mb-4">
        <div class="container">
            <a class="navbar-brand fw-bold d-flex align-items-center gap-2" href="index.php">
                <i class="fas fa-graduation-cap text-warning"></i> EthioServe Education
            </a>
            <div class="text-white small">
                Logged in as: <strong><?php echo htmlspecialchars(getCurrentUserName()); ?></strong>
            </div>
        </div>
    </nav>

    <div class="container pb-5">
        <div class="row g-4">
            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <div class="card portal-card p-4 text-center mb-4">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($profile['full_name']); ?>&size=128&background=random" class="rounded-circle mx-auto mb-3 shadow-sm border border-4 border-white">
                    <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($profile['full_name']); ?></h4>
                    <p class="text-muted"><?php echo htmlspecialchars($profile['class_name'] . ' - ' . $profile['section']); ?></p>
                    <div class="badge bg-light text-dark py-2 px-3 rounded-pill border">ID: <?php echo htmlspecialchars($profile['student_id_number']); ?></div>
                    
                    <hr class="my-4">
                    
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h5 class="fw-bold mb-0 text-success"><?php echo $attendance_pct; ?>%</h5>
                                <small class="text-muted">Attendance</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded-3">
                                <h5 class="fw-bold mb-0 text-primary">A-</h5>
                                <small class="text-muted">Avg Grade</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card portal-card p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-wallet me-2 text-warning"></i>Fee Status</h5>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Current Dues:</span>
                        <span class="fw-bold text-danger">2,500 ETB</span>
                    </div>
                    <div class="progress mb-3" style="height: 10px;">
                        <div class="progress-bar bg-warning" style="width: 70%"></div>
                    </div>
                    <button class="btn btn-dark w-100 rounded-pill fw-bold py-2">Pay via Chapa</button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Tabs -->
                <ul class="nav nav-pills gap-2 mb-4">
                    <li class="nav-item"><a class="nav-link active rounded-pill px-4" href="#timetable" data-bs-toggle="pill">Timetable</a></li>
                    <li class="nav-item"><a class="nav-link bg-white rounded-pill px-4 shadow-sm" href="#assignments" data-bs-toggle="pill">Assignments</a></li>
                    <li class="nav-item"><a class="nav-link bg-white rounded-pill px-4 shadow-sm" href="lms.php">E-Learning (LMS)</a></li>
                </ul>

                <div class="tab-content">
                    <div class="tab-pane fade show active" id="timetable">
                        <div class="card portal-card p-4">
                            <h5 class="fw-bold mb-4">Weekly Schedule</h5>
                            <?php if(empty($timetable)): ?>
                                <p class="text-muted text-center py-5">Schedule not yet published.</p>
                            <?php else: foreach($timetable as $day => $lessons): ?>
                                <div class="timetable-day"><?php echo $day; ?></div>
                                <div class="list-group list-group-flush">
                                    <?php foreach($lessons as $l): ?>
                                        <div class="list-group-item d-flex justify-content-between align-items-center py-3 border-0">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="fw-bold text-dark" style="width: 100px;"><?php echo date('H:i', strtotime($l['start_time'])); ?></div>
                                                <div>
                                                    <div class="fw-bold text-primary"><?php echo htmlspecialchars($l['subject_name']); ?></div>
                                                    <small class="text-muted">Room: <?php echo htmlspecialchars($profile['room_number'] ?? 'TBD'); ?></small>
                                                </div>
                                            </div>
                                            <i class="fas fa-chevron-right text-light"></i>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="assignments">
                        <div class="card portal-card p-4">
                            <h5 class="fw-bold mb-4">Upcoming Assignments</h5>
                            <?php if(empty($assignments)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-circle fa-3x text-success opacity-50 mb-3"></i>
                                    <p class="text-muted">All caught up! No pending assignments.</p>
                                </div>
                            <?php else: foreach($assignments as $a): ?>
                                <div class="assignment-row">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="fw-bold text-dark mb-1"><?php echo htmlspecialchars($a['title']); ?></h6>
                                        <span class="badge bg-soft-danger text-danger">Due: <?php echo date('M d', strtotime($a['due_date'])); ?></span>
                                    </div>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($a['subject_name']); ?> • <?php echo htmlspecialchars($a['description']); ?></p>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill px-3">Upload Submission</button>
                                </div>
                            <?php endforeach; endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
