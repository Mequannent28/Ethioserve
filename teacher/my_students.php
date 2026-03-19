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
    die("Teacher profile not found.");
}

$teacher_id = $teacher_data['id'];

// Get unique classes assigned to this teacher to populate a filter dropdown
$classes_stmt = $pdo->prepare("
    SELECT DISTINCT c.id, c.class_name, c.section
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    WHERE cs.teacher_id = ?
    ORDER BY c.class_name ASC, c.section ASC
");
$classes_stmt->execute([$teacher_id]);
$my_classes = $classes_stmt->fetchAll();

// Handle Class Filter
$filter_class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Fetch Students
$students_query = "
    SELECT u.id as user_id, u.full_name, u.email, u.phone, u.profile_photo, sp.student_id_number, sp.gender, c.class_name, c.section
    FROM sms_student_profiles sp
    JOIN users u ON sp.user_id = u.id
    JOIN sms_classes c ON sp.class_id = c.id
    WHERE sp.class_id IN (SELECT class_id FROM sms_class_subjects WHERE teacher_id = ?)
";

$params = [$teacher_id];
if ($filter_class_id > 0) {
    $students_query .= " AND sp.class_id = ?";
    $params[] = $filter_class_id;
}

$students_query .= " ORDER BY c.class_name ASC, u.full_name ASC";

$stmt = $pdo->prepare($students_query);
$stmt->execute($params);
$students = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Students - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-dark: #0a2d0f; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; color: #333; }
        .main-content { margin-left: 260px; padding: 2.5rem; min-height: 100vh; }
        
        .student-list-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
        }
        
        .student-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
            color: #555;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 15px 20px;
        }
        
        .student-table td {
            padding: 15px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f1f1f1;
        }

        .student-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
            background: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: var(--edu-green);
        }

        .class-badge {
            background: rgba(27, 94, 32, 0.1);
            color: var(--edu-green);
            padding: 4px 12px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .filter-section {
            background: #fff;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.02);
            border: 1px solid #eee;
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
                <h2 class="fw-bold mb-1">My Students</h2>
                <p class="text-muted mb-0">List of students currently enrolled in your classes.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-success text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">My Students</li>
                </ol>
            </nav>
        </div>

        <div class="filter-section d-flex flex-wrap align-items-center gap-3">
            <div class="d-flex align-items-center gap-2">
                <i class="fas fa-filter text-muted"></i>
                <span class="fw-600">Filter by Class:</span>
            </div>
            <div class="flex-grow-1" style="max-width: 300px;">
                <select class="form-select rounded-pill border-2" onchange="location.href='?class_id='+this.value">
                    <option value="0">All My Classes</option>
                    <?php foreach($my_classes as $cls): ?>
                        <option value="<?php echo $cls['id']; ?>" <?php echo $filter_class_id == $cls['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cls['class_name'] . ' (' . $cls['section'] . ')'); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="ms-auto">
                <span class="text-muted small fw-bold">TOTAL: <?php echo count($students); ?> STUDENTS</span>
            </div>
        </div>

        <div class="student-list-card">
            <div class="table-responsive">
                <table class="table student-table mb-0">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>ID Number</th>
                            <th>Class / Section</th>
                            <th>Contact</th>
                            <th>Gender</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($students)): ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div class="text-muted opacity-50 mb-3"><i class="fas fa-users-slash fa-3x"></i></div>
                                    <h6 class="fw-bold">No students found</h6>
                                    <p class="small text-muted mb-0">Check back later or ensure you are assigned to a class.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($students as $s): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if($s['profile_photo']): ?>
                                                <img src="../uploads/profiles/<?php echo $s['profile_photo']; ?>" class="student-avatar" alt="">
                                            <?php else: ?>
                                                <div class="student-avatar"><?php echo strtoupper(substr($s['full_name'], 0, 1)); ?></div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                                <div class="text-muted small" style="font-size: 0.75rem;"><?php echo htmlspecialchars($s['email']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><code class="text-dark fw-bold"><?php echo htmlspecialchars($s['student_id_number'] ?: 'N/A'); ?></code></td>
                                    <td>
                                        <div class="class-badge">
                                            <?php echo htmlspecialchars($s['class_name'] . ' – ' . $s['section']); ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small fw-500"><i class="fas fa-phone-alt me-1 text-muted"></i> <?php echo htmlspecialchars($s['phone'] ?: 'No Phone'); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge rounded-pill <?php echo $s['gender'] == 'Male' ? 'bg-primary' : ($s['gender'] == 'Female' ? 'bg-danger' : 'bg-secondary'); ?> bg-opacity-10 <?php echo $s['gender'] == 'Male' ? 'text-primary' : ($s['gender'] == 'Female' ? 'text-danger' : 'text-secondary'); ?> border-0 small">
                                            <?php echo $s['gender']; ?>
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="chat.php?user_id=<?php echo $s['user_id']; ?>" class="btn btn-sm btn-outline-success rounded-pill px-3" title="Send Message">
                                            <i class="fas fa-comment-dots"></i> Message
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
