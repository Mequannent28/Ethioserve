<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
// Version: 1.1 - SMS Migration Reinforced
 
// Check if user is logged in and is a teacher
requireRole('teacher');

$teacher_user_id = getCurrentUserId();
$teacher = $pdo->prepare("SELECT * FROM sms_teachers WHERE user_id = ?");
$teacher->execute([$teacher_user_id]);
$teacher_data = $teacher->fetch();

if (!$teacher_data) {
    die("Teacher profile not found. Please contact admin.");
}

$teacher_id = $teacher_data['id'];

// Get Classes assigned to this teacher
$my_classes = $pdo->prepare("
    SELECT cs.*, c.class_name, c.section, s.subject_name 
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    JOIN sms_subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ?
");
$my_classes->execute([$teacher_id]);
$classes = $my_classes->fetchAll();

// Stats
$total_students = 0;
foreach($classes as $c) {
    $count = $pdo->query("SELECT COUNT(*) FROM sms_student_profiles WHERE class_id = ".$c['class_id'])->fetchColumn();
    $total_students += $count;
}

$pending_assignments = $pdo->prepare("SELECT COUNT(*) FROM sms_assignment_submissions WHERE assignment_id IN (SELECT id FROM sms_assignments WHERE teacher_id = ?) AND grade IS NULL");
$pending_assignments->execute([$teacher_id]);
$pending_count = $pending_assignments->fetchColumn();

// Get Recent Activity
$recent_submissions = $pdo->prepare("
    SELECT ss.*, u.full_name, a.title as assignment_name
    FROM sms_assignment_submissions ss
    JOIN users u ON ss.student_id = u.id
    JOIN sms_assignments a ON ss.assignment_id = a.id
    WHERE a.teacher_id = ?
    ORDER BY ss.submission_date DESC LIMIT 5
");
$recent_submissions->execute([$teacher_id]);
$submissions = $recent_submissions->fetchAll();

// Get Attendance Alerts (Students with 2+ weekly or 5+ monthly absences)
$attendance_alerts = $pdo->prepare("
    SELECT u.id as student_user_id, u.full_name, c.class_name, c.section, c.id as class_id,
    (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_absent,
    (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_absent
    FROM sms_student_profiles sp
    JOIN users u ON sp.user_id = u.id
    JOIN sms_classes c ON sp.class_id = c.id
    WHERE sp.class_id IN (SELECT class_id FROM sms_class_subjects WHERE teacher_id = ?)
    HAVING weekly_absent >= 2 OR monthly_absent >= 5
    ORDER BY monthly_absent DESC
");
$attendance_alerts->execute([$teacher_id]);
$alerts = $attendance_alerts->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Portal - EthioServe Education</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-gold: #F9A825; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; overflow-x: hidden; }
        .main-content { margin-left: 260px; padding: 2.5rem; min-height: 100vh; transition: all 0.3s; }
        
        .welcome-card {
            background: linear-gradient(135deg, var(--edu-green), #2E7D32);
            color: #fff;
            border-radius: 24px;
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(27, 94, 32, 0.2);
            margin-bottom: 2rem;
        }
        .welcome-card::after {
            content: '\f51c';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            position: absolute;
            right: -20px;
            bottom: -40px;
            font-size: 15rem;
            opacity: 0.1;
            transform: rotate(-15deg);
        }

        .stat-card-premium {
            background: #fff;
            border: none;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .stat-card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.08);
        }
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }

        .class-card {
            border-radius: 20px;
            border: 1px solid #f0f0f0;
            background: #fff;
            transition: all 0.3s ease;
        }
        .class-card:hover {
            border-color: var(--edu-green);
            background: #fcfdfc;
        }

        .submission-item {
            padding: 12px;
            border-radius: 12px;
            border: 1px solid #f8f9fa;
            margin-bottom: 10px;
            transition: background 0.2s;
        }
        .submission-item:hover {
            background: #f8f9fa;
        }

        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include('../includes/sidebar_teacher.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h3 class="fw-bold mb-0">Teacher Dashboard</h3>
                <p class="text-muted small">Managing academic progress and classroom education.</p>
            </div>
            <div class="d-none d-md-block text-end">
                <div class="fw-bold text-dark"><?php echo date('l, M d, Y'); ?></div>
                <div class="text-muted small">School Academic Session 2024/25</div>
            </div>
        </div>

        <div class="welcome-card">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="fw-bold mb-2">Hello, Teacher <?php echo htmlspecialchars(explode(' ', getCurrentUserName())[0]); ?>! 👋</h1>
                    <p class="lead opacity-90 mb-4">You have <strong><?php echo count($classes); ?></strong> classes assigned for this semester. Your students are making great progress!</p>
                    <div class="d-flex gap-3">
                        <a href="attendance.php" class="btn btn-warning rounded-pill px-4 fw-bold">
                            <i class="fas fa-user-check me-2"></i>Mark Today's Attendance
                        </a>
                        <a href="assignments.php" class="btn btn-outline-light rounded-pill px-4">
                            <i class="fas fa-plus me-2"></i>New Assignment
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-3">
                <a href="my_students.php" class="text-decoration-none">
                    <div class="stat-card-premium">
                        <div class="d-flex align-items-center gap-3">
                            <div class="stat-icon bg-success bg-opacity-10 text-success">
                                <i class="fas fa-users"></i>
                            </div>
                            <div>
                                <h3 class="fw-bold mb-0 text-dark"><?php echo $total_students; ?></h3>
                                <p class="text-muted small mb-0 fw-bold">TOTAL STUDENTS</p>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-md-3">
                <div class="stat-card-premium shadow-sm border-start border-warning border-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo $pending_count; ?></h3>
                            <p class="text-muted small mb-0 fw-bold">PENDING GRADING</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-premium">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-success bg-opacity-10 text-success">
                            <i class="fas fa-chalkboard"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0"><?php echo count($classes); ?></h3>
                            <p class="text-muted small mb-0 fw-bold">ACTIVE CLASSES</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card-premium">
                    <div class="d-flex align-items-center gap-3">
                        <div class="stat-icon bg-teal bg-opacity-10 text-teal" style="background-color: rgba(0, 121, 107, 0.1); color: #00796B;">
                            <i class="fas fa-book"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold mb-0">8</h3>
                            <p class="text-muted small mb-0 fw-bold">COURSES</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0"><i class="fas fa-school me-2 text-success"></i>Assigned Classes & Subjects</h5>
                        <a href="my_classes.php" class="small text-decoration-none">View All</a>
                    </div>
                    
                    <?php if(empty($classes)): ?>
                        <div class="text-center py-5">
                            <img src="https://illustrations.popsy.co/gray/work-from-home.svg" style="height:150px;" class="mb-4">
                            <h6 class="text-muted">No classes assigned yet.</h6>
                        </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach($classes as $c): ?>
                        <div class="col-md-6">
                            <div class="class-card p-3 shadow-sm">
                                <span class="badge bg-light text-primary mb-2 rounded-pill px-3"><?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?></span>
                                <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($c['subject_name']); ?></h6>
                                <p class="text-muted small mb-3"><i class="fas fa-users me-1"></i> <?php echo $pdo->query("SELECT COUNT(*) FROM sms_student_profiles WHERE class_id = ".$c['class_id'])->fetchColumn(); ?> Students</p>
                                <div class="d-flex gap-2">
                                    <a href="attendance.php?class_id=<?php echo $c['class_id']; ?>" class="btn btn-sm btn-edu-soft flex-fill rounded-pill">Attendance</a>
                                    <a href="manage_courses.php?class_id=<?php echo $c['class_id']; ?>&subject_id=<?php echo $c['subject_id']; ?>" class="btn btn-sm btn-dark flex-fill rounded-pill">Materials</a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Attendance Alerts Card -->
                <?php if(!empty($alerts)): ?>
                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4 border-start border-danger border-5">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Attendance Alerts</h5>
                        <span class="badge bg-danger rounded-pill"><?php echo count($alerts); ?></span>
                    </div>
                    <div class="alert-list">
                        <?php foreach($alerts as $al): ?>
                        <div class="alert-item d-flex align-items-center gap-3 p-3 rounded-4 mb-2 border border-danger border-opacity-10 bg-danger bg-opacity-10">
                            <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-bold small text-truncate text-danger"><?php echo htmlspecialchars($al['full_name']); ?></div>
                                <div class="text-muted" style="font-size:0.7rem;"><?php echo htmlspecialchars($al['class_name'] . ' (' . $al['section'] . ')'); ?></div>
                                <div class="d-flex gap-2 mt-1">
                                    <span class="badge rounded-pill bg-danger border-0 small" style="font-size: 0.6rem;"><?php echo $al['monthly_absent']; ?> Monthly Ads</span>
                                    <a href="print_warning.php?student_id=<?php echo $al['student_user_id']; ?>&class_id=<?php echo $al['class_id']; ?>" target="_blank" class="text-danger small fw-bold text-decoration-none" title="Print Official Warning Paper">
                                        <i class="fas fa-print me-1"></i> PRINT
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <a href="attendance.php" class="btn btn-danger w-100 rounded-pill mt-3 small fw-bold shadow-sm">Review All Attendance</a>
                </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
                    <h5 class="fw-bold mb-4">Recent Submissions</h5>
                    <?php if(empty($submissions)): ?>
                        <p class="text-muted small text-center py-4">No recent assignment submissions.</p>
                    <?php else: ?>
                        <div class="submissions-list">
                            <?php foreach($submissions as $s): ?>
                            <div class="submission-item d-flex align-items-center gap-3">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($s['full_name']); ?>&background=random" class="rounded-circle" width="35">
                                <div class="overflow-hidden">
                                    <div class="fw-bold small text-truncate"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;"><?php echo htmlspecialchars($s['assignment_name']); ?></div>
                                </div>
                                <div class="ms-auto text-end">
                                    <div class="badge bg-soft-warning text-warning small"><?php echo $s['grade'] ? 'Graded' : 'Pending'; ?></div>
                                    <div class="text-muted" style="font-size:0.6rem;"><?php echo time_ago($s['submission_date']); ?></div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <a href="assignments.php" class="btn btn-light w-100 rounded-pill mt-3 small fw-bold">Manage All Assignments</a>
                    <?php endif; ?>
                </div>

                <div class="card border-0 shadow-sm p-4 rounded-4 bg-dark text-white">
                    <h6 class="fw-bold mb-3">Quick Actions</h6>
                    <div class="list-group list-group-flush bg-transparent">
                        <a href="exams.php" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0 d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-file-contract me-2"></i> Create Quiz</span>
                            <i class="fas fa-chevron-right small opacity-50"></i>
                        </a>
                        <a href="chat.php" class="list-group-item list-group-item-action bg-transparent text-white border-secondary px-0 d-flex justify-content-between align-items-center border-0">
                            <span><i class="fas fa-comments text-warning me-2"></i> Messages</span>
                            <i class="fas fa-chevron-right small opacity-50"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .btn-edu-soft { background: #E8F5E9; color: #1B5E20; border: none; font-weight: 600; }
        .btn-edu-soft:hover { background: #C8E6C9; color: #1B5E20; }
        .hover-shadow:hover { box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    </style>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

