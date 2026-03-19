<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

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
$stmt = $pdo->prepare("
    SELECT cs.*, c.class_name, c.section, s.subject_name,
    (SELECT COUNT(*) FROM sms_student_profiles WHERE class_id = c.id) as student_count
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    JOIN sms_subjects s ON cs.subject_id = s.id
    WHERE cs.teacher_id = ?
    ORDER BY c.class_name ASC, c.section ASC
");
$stmt->execute([$teacher_id]);
$my_classes = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Classes - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-gold: #F9A825; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; }
        .main-content { margin-left: 260px; padding: 2.5rem; min-height: 100vh; }
        
        .class-card-premium {
            background: #fff;
            border: none;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .class-card-premium:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        .class-header {
            background: linear-gradient(135deg, var(--edu-green), #2E7D32);
            color: #fff;
            padding: 1.5rem;
            position: relative;
        }
        .class-header .class-badge {
            background: rgba(255,255,255,0.2);
            backdrop-filter: blur(4px);
            color: #fff;
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .class-body {
            padding: 1.5rem;
            flex-grow: 1;
        }
        .class-footer {
            padding: 1rem 1.5rem;
            background: #f8f9fa;
            border-top: 1px solid #eee;
            display: flex;
            gap: 10px;
        }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; padding: 1.5rem; }
        }
    </style>
</head>
<body>
    <?php include('../includes/sidebar_teacher.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-5">
            <div>
                <h2 class="fw-bold mb-1">My Assigned Classes</h2>
                <p class="text-muted mb-0">List of groups and subjects you are currently instructing.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-success text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Classes</li>
                </ol>
            </nav>
        </div>

        <?php if(empty($my_classes)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                <div class="mb-4">
                    <i class="fas fa-chalkboard-teacher fa-4x text-muted opacity-25"></i>
                </div>
                <h4 class="fw-bold">No Classes Assigned</h4>
                <p class="text-muted mb-0">You don't have any classes assigned to you for this academic term yet.</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach($my_classes as $class): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="class-card-premium">
                        <div class="class-header">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <span class="class-badge"><?php echo htmlspecialchars($class['section']); ?> Section</span>
                                <i class="fas fa-graduation-cap opacity-50 fa-lg"></i>
                            </div>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($class['class_name']); ?></h4>
                        </div>
                        <div class="class-body">
                            <div class="d-flex align-items-center gap-3 mb-3">
                                <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                                    <i class="fas fa-book-open"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Current Subject</small>
                                    <span class="fw-bold"><?php echo htmlspecialchars($class['subject_name']); ?></span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-info bg-opacity-10 text-info p-2 rounded-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div>
                                    <small class="text-muted d-block">Students Enrolled</small>
                                    <span class="fw-bold"><?php echo $class['student_count']; ?> Students</span>
                                </div>
                            </div>
                        </div>
                        <div class="class-footer px-2">
                            <a href="my_students.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-outline-info rounded-pill px-2 flex-fill" title="Students">
                                <i class="fas fa-users"></i>
                            </a>
                            <a href="attendance.php?class_id=<?php echo $class['class_id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-2 flex-fill" title="Attendance">
                                <i class="fas fa-user-check"></i>
                            </a>
                            <a href="manage_courses.php?class_id=<?php echo $class['class_id']; ?>&subject_id=<?php echo $class['subject_id']; ?>" class="btn btn-sm btn-success rounded-pill px-2 flex-fill" title="Materials">
                                <i class="fas fa-folder-open"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
