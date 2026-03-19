<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin or school_admin
requireRole(['admin', 'school_admin']);

$current_page = basename($_SERVER['PHP_SELF']);
$view = $_GET['view'] ?? 'dashboard';
$action = $_POST['action'] ?? '';

// --- ACTIONS HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Manage Classes
    if ($action === 'add_class') {
        $name = sanitize($_POST['class_name']);
        $section = sanitize($_POST['section']);
        $capacity = (int)$_POST['capacity'];
        $room = sanitize($_POST['room_number']);

        $stmt = $pdo->prepare("INSERT INTO sms_classes (class_name, section, capacity, room_number) VALUES (?, ?, ?, ?)");
        $stmt->execute([$name, $section, $capacity, $room]);
        setFlashMessage('Class added successfully.', 'success');
        header('Location: manage_school.php?view=classes');
        exit;
    }

    // 2. Manage Subjects
    if ($action === 'add_subject') {
        $name = sanitize($_POST['subject_name']);
        $code = sanitize($_POST['subject_code']);

        $stmt = $pdo->prepare("INSERT INTO sms_subjects (subject_name, subject_code) VALUES (?, ?)");
        $stmt->execute([$name, $code]);
        setFlashMessage('Subject added successfully.', 'success');
        header('Location: manage_school.php?view=classes');
        exit;
    }

    // 3. Assign Subject to Class
    if ($action === 'assign_subject') {
        $class_id = (int)$_POST['class_id'];
        $subject_id = (int)$_POST['subject_id'];
        $teacher_id = (int)$_POST['teacher_id'];

        $stmt = $pdo->prepare("INSERT INTO sms_class_subjects (class_id, subject_id, teacher_id) VALUES (?, ?, ?)");
        $stmt->execute([$class_id, $subject_id, $teacher_id]);
        setFlashMessage('Subject assigned to class.', 'success');
        header('Location: manage_school.php?view=classes');
        exit;
    }

    // 4. Enroll Student
    if ($action === 'enroll_student') {
        $existing_user_id = isset($_POST['existing_user_id']) ? (int)$_POST['existing_user_id'] : 0;
        
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : null;
        $class_id = (int)$_POST['class_id'];
        $student_id = sanitize($_POST['student_id_number']);
        $phone = sanitize($_POST['phone']);
        $gender = sanitize($_POST['gender']);
        $dob = sanitize($_POST['date_of_birth']);
        
        $p_name = sanitize($_POST['parent_name'] ?? '');
        $p_phone = sanitize($_POST['parent_phone'] ?? '');
        $em_contact = sanitize($_POST['emergency_contact'] ?? '');
        $p_school = sanitize($_POST['previous_school'] ?? '');
        $h_cond = sanitize($_POST['health_conditions'] ?? '');
        $b_group = sanitize($_POST['blood_group'] ?? '');
        $address = sanitize($_POST['home_address'] ?? '');
        
        $profile_photo = null;
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            if (!is_dir('../uploads/profiles')) {
                mkdir('../uploads/profiles', 0755, true);
            }
            $ext = pathinfo($_FILES['profile_photo']['name'], PATHINFO_EXTENSION);
            $filename = time() . '_' . uniqid() . '.' . $ext;
            $dest = '../uploads/profiles/' . $filename;
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $dest)) {
                $profile_photo = $dest;
            }
        }

        try {
            $pdo->beginTransaction();
            
            // Fetch grade number from class name for users table
            $grade_num = 0;
            $stmt = $pdo->prepare("SELECT class_name FROM sms_classes WHERE id = ?");
            $stmt->execute([$class_id]);
            $c_name = $stmt->fetchColumn();
            if ($c_name && preg_match('/\d+/', $c_name, $matches)) {
                $grade_num = (int)$matches[0];
            }

            if ($existing_user_id > 0) {
                // Update existing user details
                $sql = "UPDATE users SET full_name = ?, email = ?, phone = ?, grade = ?";
                $params = [$full_name, $email, $phone, $grade_num];
                
                if ($password) {
                    $sql .= ", password = ?";
                    $params[] = $password;
                }
                
                if ($profile_photo) {
                    $sql .= ", profile_photo = ?";
                    $params[] = $profile_photo;
                }
                $sql .= " WHERE id = ?";
                $params[] = $user_id;
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                
                // Check if profile exists
                $stmt = $pdo->prepare("SELECT id FROM sms_student_profiles WHERE user_id = ?");
                $stmt->execute([$user_id]);
                if ($stmt->fetch()) {
                    // Update existing profile
                    $stmt = $pdo->prepare("UPDATE sms_student_profiles SET class_id=?, student_id_number=?, date_of_birth=?, gender=?, parent_name=?, parent_phone=?, emergency_contact=?, previous_school=?, health_conditions=?, blood_group=?, home_address=? WHERE user_id=?");
                    $stmt->execute([$class_id, $student_id, $dob, $gender, $p_name, $p_phone, $em_contact, $p_school, $h_cond, $b_group, $address, $user_id]);
                } else {
                    // Create profile for existing user
                    $stmt = $pdo->prepare("INSERT INTO sms_student_profiles (user_id, class_id, student_id_number, date_of_birth, gender, parent_name, parent_phone, emergency_contact, previous_school, health_conditions, blood_group, home_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$user_id, $class_id, $student_id, $dob, $gender, $p_name, $p_phone, $em_contact, $p_school, $h_cond, $b_group, $address]);
                }
            } else {
                // Create new user completely
                $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, password, role, profile_photo, grade) VALUES (?, ?, ?, ?, 'student', ?, ?)");
                $stmt->execute([$full_name, $email, $phone, $password, $profile_photo, $grade_num]);
                $user_id = $pdo->lastInsertId();

                // Create profile
                $stmt = $pdo->prepare("INSERT INTO sms_student_profiles (user_id, class_id, student_id_number, date_of_birth, gender, parent_name, parent_phone, emergency_contact, previous_school, health_conditions, blood_group, home_address) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $class_id, $student_id, $dob, $gender, $p_name, $p_phone, $em_contact, $p_school, $h_cond, $b_group, $address]);
            }
            
            $pdo->commit();
            setFlashMessage('Student enrolled successfully.', 'success');
        } catch (Exception $e) { $pdo->rollBack(); setFlashMessage($e->getMessage(), 'danger'); }
        header('Location: manage_school.php?view=students');
        exit;
    }

    // 5. Add Teacher
    if ($action === 'add_teacher') {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        $emp_id = sanitize($_POST['employee_id']);
        $spec = sanitize($_POST['specialization']);

        try {
            $pdo->beginTransaction();
            // Create user
            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, role) VALUES (?, ?, ?, 'teacher')");
            $stmt->execute([$full_name, $email, $password]);
            $user_id = $pdo->lastInsertId();

            // Create teacher profile
            $stmt = $pdo->prepare("INSERT INTO sms_teachers (user_id, employee_id, specialization) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $emp_id, $spec]);
            
            $pdo->commit();
            setFlashMessage('Teacher added successfully.', 'success');
        } catch (Exception $e) { $pdo->rollBack(); setFlashMessage($e->getMessage(), 'danger'); }
        header('Location: manage_school.php?view=teachers');
        exit;
    }

    // 6. Record Fee Payment
    if ($action === 'record_payment') {
        $student_id   = (int)$_POST['student_id'];
        $fee_type     = sanitize($_POST['fee_type']);
        $amount       = (float)$_POST['amount'];
        $payment_date = $_POST['payment_date'];
        $method       = sanitize($_POST['payment_method']);
        $notes        = sanitize($_POST['notes'] ?? '');
        $receipt      = 'RCP-' . strtoupper(substr(uniqid(), -6));

        $stmt = $pdo->prepare("INSERT INTO sms_fee_payments (student_id, fee_type, amount_paid, payment_date, payment_method, receipt_number, notes) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$student_id, $fee_type, $amount, $payment_date, $method, $receipt, $notes]);
        setFlashMessage("Payment of ETB $amount recorded. Receipt: $receipt", 'success');
        header('Location: manage_school.php?view=finance');
        exit;
    }
    // 7. Delete Student
    if ($action === 'delete_student') {
        $sid = (int)$_POST['student_user_id'];
        
        try {
            $pdo->beginTransaction();
            // Delete profile and user (sms_student_profiles likely has ON DELETE CASCADE or we do it manually)
            $pdo->prepare("DELETE FROM sms_student_profiles WHERE user_id = ?")->execute([$sid]);
            $pdo->prepare("DELETE FROM users WHERE id = ?")->execute([$sid]);
            
            $pdo->commit();
            setFlashMessage('Student and all linked data deleted.', 'success');
        } catch (Exception $e) { $pdo->rollBack(); setFlashMessage($e->getMessage(), 'danger'); }
        header('Location: manage_school.php?view=students');
        exit;
    }
}

// --- DATA FETCHING ---
// Stats
$total_students = $pdo->query("SELECT COUNT(*) FROM sms_student_profiles")->fetchColumn();
$total_teachers = $pdo->query("SELECT COUNT(*) FROM sms_teachers")->fetchColumn();
$total_classes = $pdo->query("SELECT COUNT(*) FROM sms_classes")->fetchColumn();
$total_subjects = $pdo->query("SELECT COUNT(*) FROM sms_subjects")->fetchColumn();

// Lists
$classes = $pdo->query("SELECT * FROM sms_classes ORDER BY class_name")->fetchAll();
$subjects = $pdo->query("SELECT * FROM sms_subjects ORDER BY subject_name")->fetchAll();
$teachers = $pdo->query("SELECT t.*, u.full_name, u.email FROM sms_teachers t JOIN users u ON t.user_id = u.id")->fetchAll();
$students = $pdo->query("SELECT p.*, u.full_name, u.email, u.profile_photo, c.class_name, c.section FROM sms_student_profiles p JOIN users u ON p.user_id = u.id LEFT JOIN sms_classes c ON p.class_id = c.id ORDER BY c.class_name, u.full_name")->fetchAll();

// Finance
$total_collected = $pdo->query("SELECT COALESCE(SUM(amount_paid),0) FROM sms_fee_payments")->fetchColumn();
$recent_payments = $pdo->query("
    SELECT fp.*, u.full_name, c.class_name
    FROM sms_fee_payments fp
    JOIN users u ON fp.student_id = u.id
    LEFT JOIN sms_student_profiles sp ON sp.user_id = fp.student_id
    LEFT JOIN sms_classes c ON sp.class_id = c.id
    ORDER BY fp.payment_date DESC LIMIT 20
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Management - EthioServe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --school-primary: #1B5E20;
            --school-accent: #F9A825;
            --bg-light: #f4f6f9;
        }
        body { font-family: 'Inter', sans-serif; background-color: var(--bg-light); overflow-x: hidden; }
        
        .card-stat { border: none; border-radius: 15px; box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075); transition: transform 0.3s; }
        .card-stat:hover { transform: translateY(-5px); }
        .nav-tabs-premium { border-bottom: 2px solid #e3e6f0; gap: 10px; }
        .nav-tabs-premium .nav-link { 
            border: none; color: #b7b9cc; font-weight: 600; padding: 12px 20px; border-radius: 10px 10px 0 0;
            transition: all 0.3s;
        }
        .nav-tabs-premium .nav-link:hover { background: rgba(27, 94, 32, 0.05); color: var(--school-primary); }
        .nav-tabs-premium .nav-link.active { 
            color: var(--school-primary); background: transparent; border-bottom: 3px solid var(--school-primary);
        }
        .btn-premium { background: var(--school-primary); color: #fff; border-radius: 50px; padding: 8px 24px; font-weight: 600; border: none; }
        .btn-premium:hover { background: #144618; color: #fff; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold text-dark mb-0">
                        <i class="fas fa-school me-2" style="color: var(--school-primary);"></i>School Management
                    </h2>
                    <p class="text-muted">Manage academic structure, users, and educational flow.</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-premium" data-bs-toggle="modal" data-bs-target="#addClassModal">
                        <i class="fas fa-plus me-2"></i>New Class
                    </button>
                    <button class="btn btn-outline-success rounded-pill px-4 border-2" data-bs-toggle="modal" data-bs-target="#addSubjectModal">
                        <i class="fas fa-book me-2"></i>New Subject
                    </button>
                </div>
            </div>

            <?php echo displayFlashMessage(); ?>

            <!-- NAVIGATION TABS -->
            <ul class="nav nav-tabs nav-tabs-premium mb-4">
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'dashboard' ? 'active' : ''; ?>" href="?view=dashboard">
                        <i class="fas fa-th-large me-2"></i>Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'classes' ? 'active' : ''; ?>" href="?view=classes">
                        <i class="fas fa-school me-2"></i>Classes & Subjects
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'teachers' ? 'active' : ''; ?>" href="?view=teachers">
                        <i class="fas fa-chalkboard-teacher me-2"></i>Teachers
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'students' ? 'active' : ''; ?>" href="?view=students">
                        <i class="fas fa-user-graduate me-2"></i>Students
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo $view === 'finance' ? 'active' : ''; ?>" href="?view=finance">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Finance
                    </a>
                </li>
            </ul>

            <?php if ($view === 'dashboard'): ?>
                <!-- PREMIUM STATS GRID -->
                <div class="row g-4 mb-5">
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #1B5E20, #2E7D32); color: #fff;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="bg-white bg-opacity-20 p-2 rounded-3">
                                        <i class="fas fa-user-graduate fa-lg"></i>
                                    </div>
                                    <span class="badge bg-white bg-opacity-20 rounded-pill small">+5% new</span>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($total_students); ?></h2>
                                <p class="mb-0 opacity-75 small fw-bold text-uppercase">Total Students</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #F9A825, #fbc02d); color: #fff;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="bg-white bg-opacity-20 p-2 rounded-3">
                                        <i class="fas fa-chalkboard-teacher fa-lg"></i>
                                    </div>
                                    <span class="badge bg-white bg-opacity-20 rounded-pill small">Active</span>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($total_teachers); ?></h2>
                                <p class="mb-0 opacity-75 small fw-bold text-uppercase">Academic Staff</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #0288d1, #03a9f4); color: #fff;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="bg-white bg-opacity-20 p-2 rounded-3">
                                        <i class="fas fa-school fa-lg"></i>
                                    </div>
                                    <span class="badge bg-white bg-opacity-20 rounded-pill small">Classrooms</span>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($total_classes); ?></h2>
                                <p class="mb-0 opacity-75 small fw-bold text-uppercase">Active Classes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card border-0 shadow-sm rounded-4 overflow-hidden" style="background: linear-gradient(135deg, #c2185b, #e91e63); color: #fff;">
                            <div class="card-body p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div class="bg-white bg-opacity-20 p-2 rounded-3">
                                        <i class="fas fa-file-invoice-dollar fa-lg"></i>
                                    </div>
                                    <span class="badge bg-white bg-opacity-20 rounded-pill small">ETB</span>
                                </div>
                                <h2 class="fw-bold mb-1"><?php echo number_format($total_collected / 1000, 1); ?>K</h2>
                                <p class="mb-0 opacity-75 small fw-bold text-uppercase">Revenue (YTD)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold mb-0">Enrollment & Revenue Trends</h5>
                                <div class="dropdown">
                                    <button class="btn btn-light btn-sm rounded-pill border" data-bs-toggle="dropdown">Last 6 Months</button>
                                </div>
                            </div>
                            <div style="height: 300px; background: #f8fafc; border-radius: 12px; display: flex; align-items: center; justify-content: center; position: relative; overflow: hidden;">
                                <div style="position: absolute; inset: 0; background: linear-gradient(transparent 90%, #e2e8f0 100%); background-size: 100% 40px;"></div>
                                <canvas id="academicChart" style="z-index: 1;"></canvas>
                                <div id="chartLoading" class="text-muted small">
                                    <i class="fas fa-chart-line me-2"></i>Analytics Visualization Active
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white">
                            <h5 class="fw-bold mb-4">Recent Financial Transactions</h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="text-muted small text-uppercase">
                                        <tr>
                                            <th>Student</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($recent_payments)): ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">No recent transactions.</td></tr>
                                        <?php else: foreach(array_slice($recent_payments, 0, 5) as $rp): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?= htmlspecialchars($rp['full_name']) ?></div>
                                                    <div class="small text-muted"><?= htmlspecialchars($rp['class_name']) ?></div>
                                                </td>
                                                <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($rp['fee_type']) ?></span></td>
                                                <td class="fw-bold text-success">ETB <?= number_format($rp['amount_paid'], 2) ?></td>
                                                <td><span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3">Paid</span></td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm p-4 rounded-4 bg-white h-100">
                            <h5 class="fw-bold mb-4">System Shortcuts</h5>
                            <div class="d-grid gap-3 mb-5">
                                <a href="?view=students" class="btn btn-light text-start py-3 px-4 rounded-4 border d-flex align-items-center justify-content-between group">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-primary bg-opacity-10 p-2 rounded-3 text-primary"><i class="fas fa-user-plus"></i></div>
                                        <span class="fw-bold">Enroll Student</span>
                                    </div>
                                    <i class="fas fa-chevron-right small opacity-50"></i>
                                </a>
                                <a href="?view=teachers" class="btn btn-light text-start py-3 px-4 rounded-4 border d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning"><i class="fas fa-user-tie"></i></div>
                                        <span class="fw-bold">Add Teacher</span>
                                    </div>
                                    <i class="fas fa-chevron-right small opacity-50"></i>
                                </a>
                                <a href="manage_lms.php" class="btn btn-light text-start py-3 px-4 rounded-4 border d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success"><i class="fas fa-file-alt"></i></div>
                                        <span class="fw-bold">Manage Exams</span>
                                    </div>
                                    <i class="fas fa-chevron-right small opacity-50"></i>
                                </a>
                            </div>

                            <div class="p-4 rounded-4 bg-dark text-white position-relative overflow-hidden">
                                <div style="position: absolute; top: -10px; right: -10px; opacity: 0.1;">
                                    <i class="fas fa-shield-alt fa-6x"></i>
                                </div>
                                <h6 class="fw-bold mb-2">Security Audit</h6>
                                <p class="small opacity-75 mb-3">Your last backup was completed 2 hours ago.</p>
                                <button class="btn btn-outline-light btn-sm rounded-pill px-3">Run Full Scan</button>
                            </div>
                            
                            <div class="mt-4">
                                <h6 class="fw-bold mb-3">Notice Board</h6>
                                <div class="d-flex gap-3 mb-3">
                                    <div class="bg-danger p-1 rounded-circle mt-1" style="width: 8px; height: 8px;"></div>
                                    <div class="small"><span class="fw-bold">Meeting:</span> PTA Council on March 20.</div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div class="bg-primary p-1 rounded-circle mt-1" style="width: 8px; height: 8px;"></div>
                                    <div class="small"><span class="fw-bold">Update:</span> System maintenance scheduled for Sunday.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chart Script -->
                <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const ctx = document.getElementById('academicChart');
                    if (ctx) {
                        new Chart(ctx, {
                            type: 'line',
                            data: {
                                labels: ['Oct', 'Nov', 'Dec', 'Jan', 'Feb', 'Mar'],
                                datasets: [{
                                    label: 'Enrollments',
                                    data: [12, 19, 3, 5, 12, 8],
                                    borderColor: '#1B5E20',
                                    backgroundColor: 'rgba(27, 94, 32, 0.1)',
                                    fill: true,
                                    tension: 0.4
                                }]
                            },
                            options: {
                                responsive: true,
                                maintainAspectRatio: false,
                                plugins: { legend: { display: false } },
                                scales: { 
                                    y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                                    x: { grid: { display: false } }
                                }
                            }
                        });
                    }
                });
                </script>
            <?php elseif ($view === 'classes'): ?>
                <!-- CLASSES & SUBJECTS VIEW -->
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold mb-0">Active Classes</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Class Name</th>
                                            <th>Section</th>
                                            <th>Capacity</th>
                                            <th>Room</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($classes)): ?>
                                            <tr><td colspan="5" class="text-center py-4 text-muted">No classes active yet.</td></tr>
                                        <?php else: foreach($classes as $c): ?>
                                            <tr>
                                                <td class="fw-bold"><?php echo htmlspecialchars($c['class_name']); ?></td>
                                                <td><span class="badge bg-primary px-3 rounded-pill"><?php echo htmlspecialchars($c['section']); ?></span></td>
                                                <td><?php echo htmlspecialchars($c['capacity']); ?></td>
                                                <td><?php echo htmlspecialchars($c['room_number']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary shadow-sm rounded-pill" onclick="assignSubject(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars(addslashes($c['class_name'] . ' - ' . $c['section'])); ?>')">
                                                        <i class="fas fa-plus"></i> Assign Subject
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="card border-0 shadow-sm p-4 h-100">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h5 class="fw-bold mb-0">Subject Library</h5>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Subject Name</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if(empty($subjects)): ?>
                                            <tr><td colspan="3" class="text-center py-4 text-muted">No subjects registered yet.</td></tr>
                                        <?php else: foreach($subjects as $s): ?>
                                            <tr>
                                                <td><code><?php echo htmlspecialchars($s['subject_code']); ?></code></td>
                                                <td class="fw-bold"><?php echo htmlspecialchars($s['subject_name']); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($view === 'teachers'): ?>
                <!-- TEACHERS VIEW -->
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Teaching Faculty</h5>
                        <button class="btn btn-premium btn-sm" data-bs-toggle="modal" data-bs-target="#addTeacherModal">
                            <i class="fas fa-user-plus me-2"></i>Add Teacher
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Teacher Name</th>
                                    <th>Employee ID</th>
                                    <th>Department/Spec</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($teachers)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No teachers registered yet.</td></tr>
                                <?php else: foreach($teachers as $t): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($t['full_name']); ?>&background=random" class="rounded-circle" width="32">
                                                <span class="fw-bold"><?php echo htmlspecialchars($t['full_name']); ?></span>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($t['employee_id']); ?></code></td>
                                        <td><?php echo htmlspecialchars($t['specialization']); ?></td>
                                        <td><?php echo htmlspecialchars($t['email']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-light border"><i class="fas fa-edit"></i></button>
                                            <button class="btn btn-sm btn-light border text-danger"><i class="fas fa-trash"></i></button>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($view === 'students'): ?>
                <!-- STUDENTS VIEW -->
                <div class="card border-0 shadow-sm p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Student Enrollment</h5>
                        <button class="btn btn-premium btn-sm" data-bs-toggle="modal" data-bs-target="#enrollStudentModal">
                            <i class="fas fa-user-plus me-2"></i>Enroll New Student
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Student Name</th>
                                    <th>ID Number</th>
                                    <th>Class & Section</th>
                                    <th>Email</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($students)): ?>
                                    <tr><td colspan="5" class="text-center py-4 text-muted">No students enrolled yet.</td></tr>
                                <?php else: foreach($students as $s): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if (!empty($s['profile_photo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($s['profile_photo']); ?>" class="rounded-circle shadow-sm" width="40" height="40" style="object-fit: cover; border: 2px solid white;">
                                                <?php else: ?>
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($s['full_name']); ?>&background=random" class="rounded-circle shadow-sm" width="40" height="40">
                                                <?php endif; ?>
                                                <div>
                                                    <span class="fw-bold d-block"><?php echo htmlspecialchars($s['full_name']); ?></span>
                                                    <?php $initials = implode('', array_map(fn($n) => strtoupper($n[0]), explode(' ', $s['full_name']))); ?>
                                                    <small class="text-muted" style="font-size: 0.7rem;"><?php echo $initials; ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($s['student_id_number']); ?></code></td>
                                        <td>
                                            <span class="badge bg-primary rounded-pill px-3">
                                                <?php echo htmlspecialchars($s['class_name'] . ' - ' . $s['section']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($s['email']); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-light border" title="View Profile" onclick="viewStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                                    <i class="fas fa-eye text-primary"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light border" title="Edit Student" onclick="editStudent(<?php echo htmlspecialchars(json_encode($s)); ?>)">
                                                    <i class="fas fa-edit text-success"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-light border" title="ID Card" onclick="viewIDCard(<?= $s['user_id'] ?>)">
                                                    <i class="fas fa-id-card text-info"></i>
                                                </button>
                                                <button class="btn btn-sm btn-light border text-danger" title="Delete Student" onclick="deleteStudent(<?= $s['user_id'] ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                </div>
            <?php elseif ($view === 'finance'): ?>
                <!-- FINANCE VIEW -->
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <div class="card border-0 p-4 text-white rounded-4" style="background: linear-gradient(135deg,#1B5E20,#2e7d32);">
                            <p class="mb-1 opacity-75 small fw-bold text-uppercase">Total Collected</p>
                            <h2 class="fw-bold mb-0">ETB <?= number_format($total_collected, 2) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 p-4 rounded-4 shadow-sm">
                            <p class="mb-1 text-muted small fw-bold text-uppercase">Total Payments</p>
                            <h2 class="fw-bold mb-0 text-primary"><?= count($recent_payments) ?></h2>
                        </div>
                    </div>
                    <div class="col-md-4 d-flex align-items-center">
                        <button class="btn btn-premium w-100 py-3" data-bs-toggle="modal" data-bs-target="#recordPaymentModal">
                            <i class="fas fa-plus-circle me-2"></i>Record New Payment
                        </button>
                    </div>
                </div>
                <div class="card border-0 shadow-sm p-4 rounded-4">
                    <h5 class="fw-bold mb-4">Payment History</h5>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th>Student</th>
                                    <th>Class</th>
                                    <th>Fee Type</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Date</th>
                                    <th>Receipt</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recent_payments)): ?>
                                    <tr><td colspan="7" class="text-center py-5 text-muted">No payments recorded yet.</td></tr>
                                <?php else: foreach ($recent_payments as $p): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($p['full_name']) ?></td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($p['class_name'] ?? 'N/A') ?></span></td>
                                        <td><?= htmlspecialchars($p['fee_type']) ?></td>
                                        <td class="fw-bold text-success">ETB <?= number_format($p['amount_paid'], 2) ?></td>
                                        <td><?= htmlspecialchars($p['payment_method']) ?></td>
                                        <td><?= date('M d, Y', strtotime($p['payment_date'])) ?></td>
                                        <td><code class="small"><?= htmlspecialchars($p['receipt_number']) ?></code></td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal" id="recordPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0">
                <input type="hidden" name="action" value="record_payment">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Record Fee Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Student</label>
                        <select name="student_id" class="form-select rounded-3" required>
                            <option value="">-- Select Student --</option>
                            <?php foreach ($students as $s): ?>
                                <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['full_name']) ?> (<?= htmlspecialchars($s['class_name'] ?? '') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Fee Type</label>
                            <select name="fee_type" class="form-select rounded-3" required>
                                <option value="Tuition Fee">Tuition Fee</option>
                                <option value="Registration">Registration</option>
                                <option value="Examination Fee">Examination Fee</option>
                                <option value="Transport Fee">Transport Fee</option>
                                <option value="Library Fee">Library Fee</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Amount (ETB)</label>
                            <input type="number" name="amount" class="form-control rounded-3" step="0.01" min="1" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Payment Date</label>
                            <input type="date" name="payment_date" class="form-control rounded-3" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Payment Method</label>
                            <select name="payment_method" class="form-select rounded-3" required>
                                <option value="Cash">Cash</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Chapa">Chapa</option>
                                <option value="Telebirr">Telebirr</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Notes (optional)</label>
                        <input type="text" name="notes" class="form-control rounded-3" placeholder="e.g. First semester payment">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium"><i class="fas fa-receipt me-2"></i>Record & Generate Receipt</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Class Modal -->
    <div class="modal" id="addClassModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0">
                <input type="hidden" name="action" value="add_class">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Create New Class</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Class Name</label>
                        <input type="text" name="class_name" class="form-control rounded-3" placeholder="e.g. Grade 10" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Section</label>
                            <input type="text" name="section" class="form-control rounded-3" placeholder="e.g. A" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Capacity</label>
                            <input type="number" name="capacity" class="form-control rounded-3" value="40" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium">Save Class</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Subject Modal -->
    <div class="modal" id="addSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0">
                <input type="hidden" name="action" value="add_subject">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Add Subject to Library</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Name</label>
                        <input type="text" name="subject_name" class="form-control rounded-3" placeholder="e.g. Mathematics" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Subject Code</label>
                        <input type="text" name="subject_code" class="form-control rounded-3" placeholder="e.g. MATH-101" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium">Add Subject</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Teacher Modal -->
    <div class="modal" id="addTeacherModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0">
                <input type="hidden" name="action" value="add_teacher">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Register New Teacher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Full Name</label>
                        <input type="text" name="full_name" class="form-control rounded-3" placeholder="e.g. Dr. Abebe" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Email Address</label>
                        <input type="email" name="email" class="form-control rounded-3" placeholder="abebe@ethioserve.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Password</label>
                        <input type="password" name="password" class="form-control rounded-3" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Employee ID</label>
                            <input type="text" name="employee_id" class="form-control rounded-3" placeholder="T-123" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Specialization</label>
                            <input type="text" name="specialization" class="form-control rounded-3" placeholder="Biology" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium">Register Teacher</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Enroll Student Modal -->
    <div class="modal" id="enrollStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <form method="POST" enctype="multipart/form-data" class="modal-content border-0">
                <input type="hidden" name="action" value="enroll_student">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Student Admission Form</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Advanced Search to prefill existing user -->
                    <div class="mb-4 bg-light p-3 rounded-3 border">
                        <label class="form-label small fw-bold text-success"><i class="fas fa-search me-1"></i> Enroll Existing Student</label>
                        <p class="small text-muted mb-2">If this student was already enrolled before (e.g., advancing from Grade 10 to Grade 11), select them here to automatically pull their details.</p>
                        <div class="input-group">
                            <input type="text" id="existingStudentSearch" class="form-control" placeholder="Type name or email to load existing profile...">
                            <button type="button" class="btn btn-outline-secondary" onclick="clearStudentSearch()"><i class="fas fa-times"></i></button>
                        </div>
                        <ul id="studentSearchResults" class="list-group position-absolute w-100 shadow-sm" style="display:none; z-index:1000; max-height:200px; overflow-y:auto;"></ul>
                    </div>

                    <h6 class="fw-bold text-primary mb-3 border-bottom pb-2">Primary Details</h6>
                    <input type="hidden" name="existing_user_id" id="existing_user_id" value="0">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="full_name" id="stu_full_name" class="form-control rounded-3" placeholder="Samuel Yohannes" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="email" id="stu_email" class="form-control rounded-3" placeholder="samuel@school.com" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="text" name="phone" id="stu_phone" class="form-control rounded-3" placeholder="+251 911..." required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label small fw-bold">Password</label>
                            <input type="password" name="password" id="stu_password" class="form-control rounded-3" placeholder="Leave blank to keep existing password" required>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Date of Birth</label>
                            <input type="date" name="date_of_birth" id="stu_dob" class="form-control rounded-3" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Gender</label>
                            <select name="gender" id="stu_gender" class="form-select rounded-3" required>
                                <option value="">Select</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold"><i class="fas fa-camera text-primary me-1"></i>Profile Picture</label>
                            <div class="d-flex align-items-center gap-2">
                                <img id="stu_photo_preview" src="" class="rounded border" style="width: 40px; height: 40px; display: none; object-fit: cover;">
                                <input type="file" name="profile_photo" class="form-control rounded-3" accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <h6 class="fw-bold text-primary mb-3 mt-3 border-bottom pb-2">Academic Information</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Student ID #</label>
                            <input type="text" name="student_id_number" id="stu_student_id" class="form-control rounded-3" placeholder="S-2024-001" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Assign to Class</label>
                            <select name="class_id" id="stu_class_id" class="form-select rounded-3" required>
                                <option value="">-- Choose Class --</option>
                                <?php foreach($classes as $c): ?>
                                    <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Previous School</label>
                            <input type="text" name="previous_school" id="stu_previous_school" class="form-control rounded-3" placeholder="Optional">
                        </div>
                    </div>

                    <h6 class="fw-bold text-primary mb-3 mt-3 border-bottom pb-2">Parent/Guardian & Health Details</h6>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Parent Name</label>
                            <input type="text" name="parent_name" id="stu_parent_name" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Parent Phone</label>
                            <input type="text" name="parent_phone" id="stu_parent_phone" class="form-control rounded-3">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Emergency Contact</label>
                            <input type="text" name="emergency_contact" id="stu_emergency_contact" class="form-control rounded-3" placeholder="Relative phone">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label small fw-bold">Blood Group</label>
                            <select name="blood_group" id="stu_blood_group" class="form-select rounded-3">
                                <option value="">Select</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                            </select>
                        </div>
                        <div class="col-md-8 mb-3">
                            <label class="form-label small fw-bold">Health Conditions / Allergies</label>
                            <input type="text" name="health_conditions" id="stu_health_conditions" class="form-control rounded-3" placeholder="None">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12 mb-3">
                            <label class="form-label small fw-bold">Home Address</label>
                            <textarea name="home_address" id="stu_home_address" class="form-control rounded-3" rows="2" placeholder="Full address"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium px-5 py-2 shadow">
                        <i class="fas fa-user-plus me-2"></i>Enroll Student Profile
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Student Modal -->
    <div class="modal fade" id="viewStudentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold"><i class="fas fa-user-graduate me-2"></i>Student Profile Details</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="viewStudentContent">
                    <!-- Loaded via JS -->
                </div>
            </div>
        </div>
    </div>

    <!-- ID Card Modal -->
    <div class="modal fade" id="idCardModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 bg-transparent">
                <div class="modal-body p-0 d-flex flex-column align-items-center">
                    <iframe id="idCardFrame" src="" style="width: 350px; height: 620px; border-radius: 20px; border: none; overflow: hidden; box-shadow: 0 25px 50px rgba(0,0,0,0.3);"></iframe>
                    <div class="mt-3 no-print">
                        <button class="btn btn-premium rounded-pill px-4 shadow" onclick="document.getElementById('idCardFrame').contentWindow.print()">
                            <i class="fas fa-print me-2"></i>Print Card Now
                        </button>
                        <button class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assign Subject Modal -->
    <div class="modal" id="assignSubjectModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content border-0">
                <input type="hidden" name="action" value="assign_subject">
                <input type="hidden" name="class_id" id="assign_class_id">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Assign Subject to <span id="assign_class_name" class="text-primary"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Subject</label>
                        <select name="subject_id" class="form-select rounded-3" required>
                            <option value="">-- Choose Subject --</option>
                            <?php foreach($subjects as $s): ?>
                                <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['subject_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Assign Teacher</label>
                        <select name="teacher_id" class="form-select rounded-3" required>
                            <option value="">-- Choose Teacher --</option>
                            <?php foreach($teachers as $t): ?>
                                <option value="<?php echo $t['id']; ?>"><?php echo htmlspecialchars($t['full_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium">Assign Now</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-complete logic for student enrollment
            const searchInput = document.getElementById('existingStudentSearch');
            const resultsBox = document.getElementById('studentSearchResults');
            let timeout = null;

            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    clearTimeout(timeout);
                    const query = this.value.trim();
                    if (query.length < 2) {
                        resultsBox.style.display = 'none';
                        return;
                    }

                    timeout = setTimeout(() => {
                        fetch('search_student.php?q=' + encodeURIComponent(query))
                        .then(r => r.json())
                        .then(data => {
                            resultsBox.innerHTML = '';
                            if (data.length > 0) {
                                data.forEach(student => {
                                    const li = document.createElement('a');
                                    li.href = '#';
                                    li.className = 'list-group-item list-group-item-action border-0 border-bottom';
                                    li.innerHTML = `<strong>${student.full_name}</strong> - ${student.email} <small class="text-muted">(${student.student_id_number || 'No ID'})</small>`;
                                    li.onclick = function(e) {
                                        e.preventDefault();
                                        selectStudent(student);
                                    };
                                    resultsBox.appendChild(li);
                                });
                                resultsBox.style.display = 'block';
                            } else {
                                resultsBox.innerHTML = '<li class="list-group-item text-muted">No students found.</li>';
                                resultsBox.style.display = 'block';
                            }
                        });
                    }, 300);
                });
                
                // Hide autocomplete on outside click
                document.addEventListener('click', function(e) {
                    if (e.target !== searchInput && e.target !== resultsBox && !resultsBox.contains(e.target)) {
                        resultsBox.style.display = 'none';
                    }
                });
            }
        });

        function clearStudentSearch() {
            document.getElementById('existingStudentSearch').value = '';
            document.getElementById('studentSearchResults').style.display = 'none';
            document.getElementById('existing_user_id').value = '0';
            document.getElementById('stu_full_name').value = '';
            document.getElementById('stu_email').value = '';
            document.getElementById('stu_phone').value = '';
            document.getElementById('stu_dob').value = '';
            document.getElementById('stu_gender').value = '';
            document.getElementById('stu_student_id').value = '';
            document.getElementById('stu_class_id').value = '';
            document.getElementById('stu_previous_school').value = '';
            document.getElementById('stu_parent_name').value = '';
            document.getElementById('stu_parent_phone').value = '';
            document.getElementById('stu_emergency_contact').value = '';
            document.getElementById('stu_health_conditions').value = '';
            document.getElementById('stu_blood_group').value = '';
            document.getElementById('stu_home_address').value = '';
            document.getElementById('stu_photo_preview').style.display = 'none';
            document.getElementById('stu_photo_preview').src = '';
            
            // Re-require password for new students
            const passInput = document.getElementById('stu_password');
            passInput.placeholder = 'Secure password';
            passInput.required = true;
        }

        function selectStudent(student) {
            document.getElementById('existingStudentSearch').value = student.full_name;
            document.getElementById('studentSearchResults').style.display = 'none';
            
            document.getElementById('existing_user_id').value = student.user_id;
            document.getElementById('stu_full_name').value = student.full_name || '';
            document.getElementById('stu_email').value = student.email || '';
            document.getElementById('stu_phone').value = student.phone || '';
            
            // Password is not required if we are editing
            const passInput = document.getElementById('stu_password');
            passInput.placeholder = 'Leave blank to keep existing password';
            passInput.required = false;

            document.getElementById('stu_dob').value = student.date_of_birth || '';
            document.getElementById('stu_gender').value = student.gender || '';
            document.getElementById('stu_student_id').value = student.student_id_number || '';
            document.getElementById('stu_class_id').value = student.class_id || '';
            document.getElementById('stu_previous_school').value = student.previous_school || '';
            document.getElementById('stu_parent_name').value = student.parent_name || '';
            document.getElementById('stu_parent_phone').value = student.parent_phone || '';
            document.getElementById('stu_emergency_contact').value = student.emergency_contact || '';
            document.getElementById('stu_health_conditions').value = student.health_conditions || '';
            document.getElementById('stu_blood_group').value = student.blood_group || '';
            document.getElementById('stu_home_address').value = student.home_address || '';
            
            const photoPreview = document.getElementById('stu_photo_preview');
            if (student.profile_photo) {
                photoPreview.src = student.profile_photo;
                photoPreview.style.display = 'block';
            } else {
                photoPreview.style.display = 'none';
            }
        }

        function editStudent(student) {
            selectStudent(student);
            new bootstrap.Modal(document.getElementById('enrollStudentModal')).show();
        }

        function viewStudent(student) {
            const content = document.getElementById('viewStudentContent');
            const photoUrl = student.profile_photo || `https://ui-avatars.com/api/?name=${encodeURIComponent(student.full_name)}&background=random&size=128`;
            
            content.innerHTML = `
                <div class="row g-4 text-center text-md-start">
                    <div class="col-md-4 text-center">
                        <img src="${photoUrl}" class="rounded-4 shadow mb-3" style="width: 180px; height: 210px; object-fit: cover; border: 4px solid #f8f9fa;">
                        <h4 class="fw-bold mb-0 text-success">${student.full_name}</h4>
                        <p class="text-muted small">ID: ${student.student_id_number}</p>
                    </div>
                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-6"><p class="small text-muted mb-0">Class</p><p class="fw-bold">${student.class_name} - ${student.section}</p></div>
                            <div class="col-6"><p class="small text-muted mb-0">Email</p><p class="fw-bold">${student.email}</p></div>
                            <div class="col-6"><p class="small text-muted mb-0">Parent Name</p><p class="fw-bold">${student.parent_name || 'N/A'}</p></div>
                            <div class="col-6"><p class="small text-muted mb-0">Parent Phone</p><p class="fw-bold">${student.parent_phone || 'N/A'}</p></div>
                            <div class="col-6"><p class="small text-muted mb-0">Gender</p><p class="fw-bold">${student.gender}</p></div>
                            <div class="col-6"><p class="small text-muted mb-0">Blood Group</p><p class="fw-bold">${student.blood_group || 'N/A'}</p></div>
                            <div class="col-12"><p class="small text-muted mb-0">Health Conditions</p><p class="fw-bold text-danger">${student.health_conditions || 'None Reported'}</p></div>
                            <div class="col-12"><p class="small text-muted mb-0">Home Address</p><p class="fw-bold">${student.home_address || 'N/A'}</p></div>
                        </div>
                    </div>
                </div>
            `;
            new bootstrap.Modal(document.getElementById('viewStudentModal')).show();
        }

        function viewIDCard(userId) {
            const frame = document.getElementById('idCardFrame');
            frame.src = 'print_id_card.php?user_id=' + userId + '&iframe=1';
            new bootstrap.Modal(document.getElementById('idCardModal')).show();
        }

        function deleteStudent(userId) {
            if (confirm('Are you sure you want to permanently delete this student? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                const actionInput = document.createElement('input');
                actionInput.type = 'hidden';
                actionInput.name = 'action';
                actionInput.value = 'delete_student';
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'student_user_id';
                idInput.value = userId;
                form.appendChild(actionInput);
                form.appendChild(idInput);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Toggle Password visibility logic and other vanilla JS...
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        function assignSubject(classId, className) {
            document.getElementById('assign_class_id').value = classId;
            document.getElementById('assign_class_name').innerText = className;
            new bootstrap.Modal(document.getElementById('assignSubjectModal')).show();
        }

        // Mock Chart for Dashboard
        const ctx = document.getElementById('academicChart');
        if(ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'],
                    datasets: [{
                        label: 'Students Enrolled',
                        data: [65, 59, 80, 81],
                        backgroundColor: '#1B5E20',
                        borderRadius: 8
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { display: false } }, x: { grid: { display: false } } }
                }
            });
        }
    </script>
</body>
</html>
