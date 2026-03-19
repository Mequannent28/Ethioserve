<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('teacher');

$teacher_user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT id FROM sms_teachers WHERE user_id = ?");
$stmt->execute([$teacher_user_id]);
$teacher_id = $stmt->fetchColumn();

$class_id = (int)($_GET['class_id'] ?? 0);
$selected_date = $_GET['date'] ?? date('Y-m-d');

// Action: Save Attendance
$marked_count = ['Present' => 0, 'Absent' => 0, 'Late' => 0];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_attendance') {
    $class_id = (int)$_POST['class_id'];
    $date = $_POST['attendance_date'];
    $status_array = $_POST['status'] ?? []; // [student_user_id => status]

    try {
        $pdo->beginTransaction();
        foreach ($status_array as $student_id => $status) {
            $marked_count[$status]++;
            // Check if exists for this date, if so update, else insert
            $stmt = $pdo->prepare("INSERT INTO sms_attendance (student_id, class_id, status, attendance_date, marked_by) 
                                   VALUES (?, ?, ?, ?, ?) 
                                   ON DUPLICATE KEY UPDATE status = VALUES(status), marked_by = VALUES(marked_by)");
            $stmt->execute([$student_id, $class_id, $status, $date, $teacher_user_id]);
        }
        $pdo->commit();
        $msg = "Attendance marked: {$marked_count['Present']} Present, {$marked_count['Absent']} Absent, {$marked_count['Late']} Late.";
        setFlashMessage($msg, 'success');
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage($e->getMessage(), 'danger');
    }
}

// Fetch Classes for dropdown
$classes_stmt = $pdo->prepare("
    SELECT c.id, c.class_name, c.section 
    FROM sms_class_subjects cs
    JOIN sms_classes c ON cs.class_id = c.id
    WHERE cs.teacher_id = ?
    GROUP BY c.id
");
$classes_stmt->execute([$teacher_id]);
$my_classes = $classes_stmt->fetchAll();

// Fetch Students for selected class
$students = [];
if ($class_id > 0) {
    // We add absentee subqueries for alerts (2 days in last 7 days, 5 days in last 30 days)
    $stmt = $pdo->prepare("
        SELECT u.id, u.full_name, p.student_id_number,
        (SELECT status FROM sms_attendance WHERE student_id = u.id AND attendance_date = ? AND class_id = ? LIMIT 1) as current_status,
        (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(?, INTERVAL 7 DAY)) as weekly_absent,
        (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(?, INTERVAL 30 DAY)) as monthly_absent
        FROM sms_student_profiles p
        JOIN users u ON p.user_id = u.id
        WHERE p.class_id = ?
        ORDER BY u.full_name
    ");
    $stmt->execute([$selected_date, $class_id, $selected_date, $selected_date, $class_id]);
    $students = $stmt->fetchAll();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daily Attendance - Teacher Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f8f9fc; }
        .main-content { margin-left: 260px; padding: 2rem; }
        .attendance-card { border: none; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.05); }
        .btn-check:checked + .btn-p { background: #1B5E20 !important; color: white !important; }
        .btn-check:checked + .btn-a { background: #d32f2f !important; color: white !important; }
        .btn-check:checked + .btn-l { background: #fbc02d !important; color: white !important; }
        .student-row:hover { background: rgba(27, 94, 32, 0.02); }
    </style>
</head>
<body>
    <?php include('../includes/sidebar_teacher.php'); ?>

    <div class="main-content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold text-dark">Daily Attendance</h2>
                <p class="text-muted">Select a class and date to mark the roll call.</p>
            </div>
            <div class="bg-white p-2 rounded-4 shadow-sm">
                <form class="row g-2 align-items-center">
                    <div class="col-auto">
                        <select name="class_id" class="form-select border-0 bg-light" required onchange="this.form.submit()">
                            <option value="">-- Select Class --</option>
                            <?php foreach($my_classes as $c): ?>
                                <option value="<?php echo $c['id']; ?>" <?php echo $class_id == $c['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($c['class_name'] . ' - ' . $c['section']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <input type="date" name="date" class="form-control border-0 bg-light" value="<?php echo $selected_date; ?>" onchange="this.form.submit()">
                    </div>
                </form>
            </div>
        </div>

        <?php echo displayFlashMessage(); ?>

        <?php if ($class_id > 0 && !empty($students)): 
            $present_count = count(array_filter($students, fn($s) => $s['current_status'] === 'Present'));
            $absent_count = count(array_filter($students, fn($s) => $s['current_status'] === 'Absent'));
            $late_count = count(array_filter($students, fn($s) => $s['current_status'] === 'Late'));
            $total_count = count($students);

            // Identify students with warnings
            $warning_students = array_filter($students, fn($s) => ($s['weekly_absent'] >= 2 || $s['monthly_absent'] >= 5));
        ?>
            <!-- Attendance Summary Bar -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border-start border-success border-4">
                        <div class="small text-muted fw-bold">PRESENT</div>
                        <div class="h4 fw-bold mb-0 text-success"><?php echo $present_count; ?> <small class="text-muted" style="font-size: .6rem;">/ <?php echo $total_count; ?></small></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border-start border-danger border-4">
                        <div class="small text-muted fw-bold">ABSENT</div>
                        <div class="h4 fw-bold mb-0 text-danger"><?php echo $absent_count; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border-start border-warning border-4">
                        <div class="small text-muted fw-bold">LATE</div>
                        <div class="h4 fw-bold mb-0 text-warning"><?php echo $late_count; ?></div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="bg-white p-3 rounded-4 shadow-sm border-start border-primary border-4">
                        <div class="small text-muted fw-bold">ALERTS</div>
                        <div class="h4 fw-bold mb-0 text-primary"><?php echo count($warning_students); ?></div>
                    </div>
                </div>
            </div>

            <!-- Warning Paper (Alert Panel) -->
            <?php if (!empty($warning_students)): ?>
                <div class="alert alert-danger border-0 shadow-sm rounded-4 p-4 mb-4">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-danger text-white p-2 rounded-circle">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5 class="alert-heading mb-0 fw-bold">Attendance Warning Notice</h5>
                    </div>
                    <p class="mb-3">The following students have exceeded the absence threshold (2 days/week or 5 days/month):</p>
                    <div class="row g-2">
                        <?php foreach($warning_students as $ws): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="bg-white bg-opacity-50 p-2 rounded-3 border border-danger border-opacity-25 d-flex align-items-center justify-content-between">
                                    <span class="small fw-bold text-dark"><i class="fas fa-user-circle me-2"></i><?php echo htmlspecialchars($ws['full_name']); ?></span>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-danger rounded-pill"><?php echo $ws['monthly_absent']; ?> Monthly</span>
                                        <a href="print_warning.php?student_id=<?php echo $ws['id']; ?>&class_id=<?php echo $class_id; ?>" target="_blank" class="btn btn-sm btn-light border p-1 rounded-circle shadow-sm" title="Print Warning Paper">
                                            <i class="fas fa-print text-danger"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <hr>
                    <p class="mb-0 small"><i class="fas fa-info-circle me-1"></i> Please consider contacting parents or school counselor for these students.</p>
                </div>
            <?php endif; ?>

            <div class="card attendance-card p-4">
                <form method="POST">
                    <input type="hidden" name="action" value="save_attendance">
                    <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                    <input type="hidden" name="attendance_date" value="<?php echo $selected_date; ?>">

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="text-muted small text-uppercase">
                                <tr>
                                    <th>Student</th>
                                    <th>ID Number</th>
                                    <th class="text-center">Attendance Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($students)): ?>
                                    <tr><td colspan="3" class="text-center py-5">No students found in this class.</td></tr>
                                <?php else: foreach($students as $s): ?>
                                    <tr class="student-row">
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($s['full_name']); ?>&background=random" class="rounded-circle" width="35">
                                                <div>
                                                    <span class="fw-bold d-block"><?php echo htmlspecialchars($s['full_name']); ?></span>
                                                    <div class="d-flex align-items-center gap-2 mt-1">
                                                        <?php if($s['weekly_absent'] >= 2): ?>
                                                            <span class="badge bg-danger bg-opacity-10 text-danger border-0 py-1 px-2" style="font-size: .65rem;"><i class="fas fa-warning me-1"></i>2+ Weekly Absences</span>
                                                        <?php elseif($s['monthly_absent'] >= 5): ?>
                                                            <span class="badge bg-warning bg-opacity-10 text-warning border-0 py-1 px-2" style="font-size: .65rem;"><i class="fas fa-info-circle me-1"></i>5+ Monthly Absences</span>
                                                        <?php endif; ?>
                                                        
                                                        <?php if($s['weekly_absent'] >= 2 || $s['monthly_absent'] >= 5): ?>
                                                            <a href="print_warning.php?student_id=<?php echo $s['id']; ?>&class_id=<?php echo $class_id; ?>" target="_blank" class="text-danger small fw-bold text-decoration-none" title="Print Official Warning Paper">
                                                                <i class="fas fa-print me-1"></i> PRINT
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><code><?php echo htmlspecialchars($s['student_id_number']); ?></code></td>
                                        <td class="text-center">
                                            <div class="btn-group w-100" style="max-width: 300px;">
                                                <input type="radio" class="btn-check" name="status[<?php echo $s['id']; ?>]" id="p_<?php echo $s['id']; ?>" value="Present" <?php echo ($s['current_status'] === 'Present' || !$s['current_status']) ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-success border-0 btn-p btn-sm" for="p_<?php echo $s['id']; ?>">Present</label>

                                                <input type="radio" class="btn-check" name="status[<?php echo $s['id']; ?>]" id="a_<?php echo $s['id']; ?>" value="Absent" <?php echo $s['current_status'] === 'Absent' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-danger border-0 btn-a btn-sm" for="a_<?php echo $s['id']; ?>">Absent</label>

                                                <input type="radio" class="btn-check" name="status[<?php echo $s['id']; ?>]" id="l_<?php echo $s['id']; ?>" value="Late" <?php echo $s['current_status'] === 'Late' ? 'checked' : ''; ?>>
                                                <label class="btn btn-outline-warning border-0 btn-l btn-sm" for="l_<?php echo $s['id']; ?>">Late</label>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if(!empty($students)): ?>
                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success rounded-pill px-5 fw-bold shadow-sm">
                            <i class="fas fa-check-circle me-2"></i>Submit Attendance
                        </button>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <img src="https://illustrations.popsy.co/amber/selection.svg" style="max-width: 300px;" class="mb-4">
                <h4 class="text-muted">Please select a class to start marking attendance</h4>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
