<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Allow both teachers and students to view
if (!hasRole('teacher') && !hasRole('student')) {
    header("Location: ../login.php");
    exit;
}

$student_user_id = (int)($_GET['student_id'] ?? 0);
$class_id = (int)($_GET['class_id'] ?? 0);
$current_user_id = getCurrentUserId();

// Security: If student, they can only view their own paper
if (hasRole('student') && $student_user_id !== $current_user_id) {
    die("Access denied. You can only view your own warning notice.");
}

// Fetch teacher name (for the signature) - find the teacher assigned to this class
$stmt = $pdo->prepare("
    SELECT u.full_name 
    FROM sms_class_subjects cs 
    JOIN users u ON cs.teacher_id = (SELECT id FROM sms_teachers WHERE user_id = u.id)
    WHERE cs.class_id = ? LIMIT 1
");
$stmt->execute([$class_id]);
$teacher_name = $stmt->fetchColumn() ?: "Class Teacher";

// Fetch student details
$stmt = $pdo->prepare("
    SELECT u.full_name, u.profile_photo, sp.student_id_number, c.class_name, c.section, u.email
    FROM sms_student_profiles sp
    JOIN users u ON sp.user_id = u.id
    JOIN sms_classes c ON sp.class_id = c.id
    WHERE u.id = ? AND sp.class_id = ?
");
$stmt->execute([$student_user_id, $class_id]);
$student = $stmt->fetch();

if (!$student) {
    die("Student or class record not found.");
}

// Stats for last 7 and 30 days
$stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_attendance WHERE student_id = ? AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$stmt->execute([$student_user_id]);
$weekly_absent = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM sms_attendance WHERE student_id = ? AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)");
$stmt->execute([$student_user_id]);
$monthly_absent = $stmt->fetchColumn();
// Fetch last few absent dates for proof
$stmt = $pdo->prepare("SELECT attendance_date FROM sms_attendance WHERE student_id = ? AND status = 'Absent' ORDER BY attendance_date DESC LIMIT 5");
$stmt->execute([$student_user_id]);
$absent_dates = $stmt->fetchAll(PDO::FETCH_COLUMN);

// Action: Send Notification
if (isset($_POST['action']) && $_POST['action'] === 'send_notification') {
    $title = "Official Attendance Warning Notice";
    $paper_url = "../teacher/print_warning.php?student_id={$student_user_id}&class_id={$class_id}";
    $message = "You have received an official attendance warning. Absences: Last 7 days ({$weekly_absent}), Last 30 days ({$monthly_absent}). View and print your paper here: [Warning Paper]({$paper_url})";

    try {
        $stmt = $pdo->prepare("INSERT INTO sms_notifications (user_id, title, message, type, created_at) VALUES (?, ?, ?, 'warning', NOW())");
        $stmt->execute([$student_user_id, $title, $message]);
        setFlashMessage("Warning notice sent to student dashboard.", "success");
    }
    catch (Exception $e) {
        setFlashMessage("Failed to send notice: " . $e->getMessage(), "danger");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Warning - <?php echo htmlspecialchars($student['full_name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #fff; font-family: 'Times New Roman', serif; }
        .warning-container { padding: 50px; border: 2px solid #333; margin-top: 20px; position: relative; }
        .school-header { border-bottom: 3px double #333; padding-bottom: 20px; margin-bottom: 30px; }
        .warning-title { font-weight: 800; text-transform: uppercase; letter-spacing: 2px; text-decoration: underline; margin-bottom: 30px; }
        .stamp-container { 
            position: absolute; 
            bottom: 80px; 
            right: 60px; 
            transform: rotate(-10deg); 
            pointer-events: none; 
            mix-blend-mode: multiply; /* Gives authentic ink-over-paper look */
            z-index: 10;
        }
        .data-table th { width: 40%; background: #f8f9fa; }
        
        @media print {
            .no-print { display: none; }
            body { padding: 0; margin: 0; }
            .warning-container { border: none; padding: 0; margin: 0; }
            .btn { display: none; }
        }
    </style>
</head>
<body onload="if(window.location.search.includes('print=1')) window.print()">

    <div class="container py-4 no-print">
        <?php echo displayFlashMessage(); ?>
        <div class="d-flex justify-content-between align-items-center">
            <?php if (hasRole('teacher')): ?>
                <a href="attendance.php?class_id=<?php echo $class_id; ?>" class="btn btn-secondary rounded-pill px-4 shadow-sm"><i class="fas fa-arrow-left me-1"></i> Back to Attendance</a>
                <div class="d-flex gap-2">
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="send_notification">
                        <button type="submit" class="btn btn-warning rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-paper-plane me-1"></i> Send to Student Dashboard</button>
                    </form>
                    <button onclick="saveToPDF()" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-file-pdf me-1"></i> Save to Computer</button>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-print me-1"></i> Print</button>
                </div>
            <?php
else: ?>
                <div class="alert alert-info py-2 px-3 rounded-pill bg-light border-0 shadow-sm small">
                    <i class="fas fa-info-circle me-1"></i> Official Notice View Mode
                </div>
                <div class="d-flex gap-2">
                    <button onclick="saveToPDF()" class="btn btn-danger rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-file-pdf me-1"></i> Save to Computer</button>
                    <button onclick="window.print()" class="btn btn-primary rounded-pill px-4 shadow-sm fw-bold"><i class="fas fa-print me-1"></i> Print Your Copy</button>
                </div>
            <?php
endif; ?>
        </div>
    </div>
    <div class="container warning-container mb-5">
        <div class="stamp-container">
            <svg viewBox="0 0 200 200" width="170" height="170">
                <!-- Outer double rings (Navy Blue Ink) -->
                <circle cx="100" cy="100" r="95" fill="none" stroke="#1e3a8a" stroke-width="3" opacity="0.85" />
                <circle cx="100" cy="100" r="90" fill="none" stroke="#1e3a8a" stroke-width="1" opacity="0.85" />
                
                <!-- Inner ring -->
                <circle cx="100" cy="100" r="62" fill="none" stroke="#1e3a8a" stroke-width="1" stroke-dasharray="4, 3" opacity="0.85" />

                <!-- Circular Text Paths -->
                <path id="curve-top" d="M 24,100 A 76,76 0 0,1 176,100" fill="none" />
                <path id="curve-bottom" d="M 166,100 A 66,66 0 0,1 34,100" fill="none" />

                <!-- Curved Text (Institution Name) -->
                <text font-family="'Times New Roman', serif" font-weight="900" font-size="14.5" fill="#1e3a8a" opacity="0.9" text-anchor="middle" letter-spacing="1.5">
                    <textPath href="#curve-top" startOffset="50%">★ ETHIOSERVE SCHOOL ★</textPath>
                </text>
                <text font-family="'Times New Roman', serif" font-weight="bold" font-size="11" fill="#1e3a8a" opacity="0.85" text-anchor="middle" letter-spacing="2">
                    <textPath href="#curve-bottom" startOffset="50%">MANAGEMENT SYSTEM</textPath>
                </text>

                <!-- Center Text (Red Warning) -->
                <text x="100" y="88" font-family="'Arial', sans-serif" font-weight="900" font-size="16" fill="#dc2626" text-anchor="middle" opacity="0.85" style="letter-spacing: 1.5px;">OFFICIAL</text>
                <text x="100" y="108" font-family="'Arial', sans-serif" font-weight="900" font-size="16" fill="#dc2626" text-anchor="middle" opacity="0.85" style="letter-spacing: 1.5px;">WARNING</text>
                
                <!-- Divider and Date -->
                <line x1="68" y1="120" x2="132" y2="120" stroke="#1e3a8a" stroke-width="1" opacity="0.8" />
                <text x="100" y="138" font-family="'Courier New', monospace" font-size="12" font-weight="bold" fill="#1e3a8a" text-anchor="middle" opacity="0.9"><?php echo date('d/m/Y'); ?></text>
            </svg>
        </div>
        
        <div class="school-header text-center">
            <h2 class="fw-bold mb-1">EthioServe Education Management System</h2>
            <p class="mb-0">Academic Excellence & Discipline Division</p>
            <p class="small text-muted">Official Attendance Monitoring Report</p>
        </div>
        <div class="text-center">
            <h3 class="warning-title">NOTICE OF EXCESSIVE ABSENTEEISM</h3>
        </div>
        <div class="mb-4 d-flex justify-content-between align-items-start">
            <div>
                <p><strong>Date:</strong> <?php echo date('F d, Y'); ?></p>
                <p class="mb-1"><strong>To the Parent/Guardian of:</strong></p>
                <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                <p class="text-muted mb-0">ID: <?php echo htmlspecialchars($student['student_id_number']); ?> | Class: <?php echo htmlspecialchars($student['class_name'] . ' (' . $student['section'] . ')'); ?></p>
            </div>
            <?php if (!empty($student['profile_photo'])): ?>
                <div class="ms-3 text-center">
                    <img src="<?php echo htmlspecialchars($student['profile_photo']); ?>" 
                         style="width: 105px; height: 140px; border: 1px solid #333; object-fit: cover; background: #fff; padding: 2px;">
                    <div class="small text-muted mt-1" style="font-size: 10px; font-weight: bold;">OFFICIAL PHOTO</div>
                </div>
            <?php
endif; ?>
        </div>

        <div class="mb-4">
            <p>This is a formal notice to inform you that your child/ward has accumulated a significant number of absences from their scheduled classes. Excessive absenteeism seriously affects a student's academic performance and violates the school's attendance policy.</p>
        </div>
        <h5 class="fw-bold border-bottom pb-2 mb-3">Absence Statistics</h5>
        <table class="table table-bordered data-table mb-4">
            <tr>
                <th>Total Absences (Last 7 Days)</th>
                <td class="text-danger fw-bold h5 mb-0"><?php echo $weekly_absent; ?> Days</td>
            </tr>
            <tr>
                <th>Total Absences (Last 30 Days)</th>
                <td class="text-danger fw-bold h5 mb-0"><?php echo $monthly_absent; ?> Days</td>
            </tr>
            <tr>
                <th>Recorded Dates of Absence</th>
                <td><?php echo implode(', ', $absent_dates); ?></td>
            </tr>
        </table>
        <div class="mb-5">
            <p><strong>Required Action:</strong></p>
            <ul>
                <li>The student must provide a valid written justification for these absences.</li>
                <li>A meeting between the parent, student, and the undersigned teacher is strongly advised.</li>
                <li>Continued absenteeism may result in disciplinary action or grade reduction.</li>
            </ul>
        </div>
        <div class="row mt-5 pt-5">
            <div class="col-6">
                <div class="border-top pt-2 text-center" style="width: 200px;">
                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars($teacher_name); ?></p>
                    <p class="small text-muted">Assigned Teacher</p>
                </div>
            </div>
            <div class="col-6 text-end">
                <div class="border-top pt-2 text-center d-inline-block" style="width: 200px;">
                    <p class="mb-0 fw-bold">____________________</p>
                    <p class="small text-muted">Parent/Guardian Signature</p>
                </div>
            </div>
        </div>
        <div class="mt-5 text-center small text-muted font-italic">
            <p>This document is electronically generated and verified by the EthioServe School Management System.</p>
        </div>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/js/all.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script>
        function saveToPDF() {
            const element = document.querySelector('.warning-container');
            const studentName = "<?php echo addslashes(htmlspecialchars($student['full_name'])); ?>";
            const opt = {
                margin:       0.5,
                filename:     `Attendance_Warning_${studentName.replace(/\s+/g, '_')}.pdf`,
                image:        { type: 'jpeg', quality: 0.98 },
                html2canvas:  { scale: 2 },
                jsPDF:        { unit: 'in', format: 'letter', orientation: 'portrait' }
            };
            // New Promise-based usage:
            html2pdf().set(opt).from(element).save();
        }
    </script>
</body>
</html>
