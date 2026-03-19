<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('teacher');

$teacher_user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT id FROM sms_teachers WHERE user_id = ?");
$stmt->execute([$teacher_user_id]);
$teacher_id = $stmt->fetchColumn();

// Fetch all students under this teacher who have 2+ weekly or 5+ monthly absences
$warning_students_query = "
    SELECT u.id as student_user_id, u.full_name, u.email, sp.student_id_number, c.class_name, c.section, c.id as class_id,
    (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)) as weekly_absent,
    (SELECT COUNT(*) FROM sms_attendance WHERE student_id = u.id AND status = 'Absent' AND attendance_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)) as monthly_absent
    FROM sms_student_profiles sp
    JOIN users u ON sp.user_id = u.id
    JOIN sms_classes c ON sp.class_id = c.id
    WHERE sp.class_id IN (SELECT class_id FROM sms_class_subjects WHERE teacher_id = ?)
    HAVING weekly_absent >= 2 OR monthly_absent >= 5
    ORDER BY monthly_absent DESC, weekly_absent DESC
";

$stmt = $pdo->prepare($warning_students_query);
$stmt->execute([$teacher_id]);
$students = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Warning List - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-dark: #0a2d0f; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; color: #333; }
        .main-content { margin-left: 260px; padding: 2.5rem; min-height: 100vh; }
        
        .warning-card {
            background: #fff;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            border: none;
        }
        
        .warning-table th {
            background: #f8f9fa;
            border-bottom: 2px solid #eee;
            color: #555;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 15px 20px;
        }

        .alert-row { border-left: 5px solid #d32f2f; }
        .warning-row { border-left: 5px solid #f9a825; }

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
                <h2 class="fw-bold mb-1"><i class="fas fa-exclamation-triangle text-danger me-2"></i>Attendance Warning List</h2>
                <p class="text-muted mb-0">Students who have exceeded the absenteeism thresholds.</p>
            </div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-success text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Warning List</li>
                </ol>
            </nav>
        </div>

        <div class="alert alert-danger rounded-4 border-0 shadow-sm p-4 mb-5">
            <h5 class="fw-bold"><i class="fas fa-info-circle me-2"></i>Absence Thresholds</h5>
            <p class="mb-0">A student is added to this list if they miss <strong>2+ days</strong> in a week or <strong>5+ days</strong> in a month.</p>
        </div>

        <div class="warning-card">
            <div class="table-responsive">
                <table class="table warning-table mb-0 align-middle">
                    <thead>
                        <tr>
                            <th>Student</th>
                            <th>Class / Section</th>
                            <th class="text-center">Weekly Absences</th>
                            <th class="text-center">Monthly Absences</th>
                            <th class="text-center">Official Paper</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($students)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <div class="text-success opacity-50 mb-3"><i class="fas fa-check-circle fa-3x"></i></div>
                                    <h6 class="fw-bold">Great Job!</h6>
                                    <p class="small text-muted mb-0">No students currently have excessive absenteeism.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($students as $s): 
                                $is_critical = $s['weekly_absent'] >= 2 || $s['monthly_absent'] >= 5;
                            ?>
                                <tr class="<?php echo $s['weekly_absent'] >= 2 ? 'alert-row' : 'warning-row'; ?>">
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($s['full_name']); ?>&background=random" class="rounded-circle" width="35">
                                            <div>
                                                <div class="fw-bold"><?php echo htmlspecialchars($s['full_name']); ?></div>
                                                <div class="text-muted small"><?php echo htmlspecialchars($s['student_id_number']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="badge bg-light text-dark border px-3 rounded-pill">
                                            <?php echo htmlspecialchars($s['class_name'] . ' - ' . $s['section']); ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <div class="h5 mb-0 fw-bold <?php echo $s['weekly_absent'] >= 2 ? 'text-danger' : 'text-dark'; ?>">
                                            <?php echo $s['weekly_absent']; ?>
                                        </div>
                                        <small class="text-muted">Last 7 Days</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="h5 mb-0 fw-bold <?php echo $s['monthly_absent'] >= 5 ? 'text-danger' : 'text-dark'; ?>">
                                            <?php echo $s['monthly_absent']; ?>
                                        </div>
                                        <small class="text-muted">Last 30 Days</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary px-3 rounded-pill view-notice-btn" 
                                                    data-url="print_warning.php?student_id=<?php echo $s['student_user_id']; ?>&class_id=<?php echo $s['class_id']; ?>"
                                                    data-bs-toggle="modal" data-bs-target="#viewNoticeModal">
                                                <i class="fas fa-eye me-1"></i> View
                                            </button>
                                            <a href="print_warning.php?student_id=<?php echo $s['student_user_id']; ?>&class_id=<?php echo $s['class_id']; ?>&print=1" target="_blank" class="btn btn-sm btn-outline-danger px-3 rounded-pill" title="Print Paper">
                                                <i class="fas fa-print me-1"></i> Print
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
    </div>

    <!-- Notice View Modal -->
    <div class="modal fade" id="viewNoticeModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content border-0 shadow-lg rounded-4">
                <div class="modal-header border-0 bg-light">
                    <h5 class="modal-title fw-bold">Official Notice Preview</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: 80vh;">
                    <iframe id="noticeFrame" src="" frameborder="0" style="width: 100%; height: 100%;"></iframe>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Close Preview</button>
                    <button type="button" class="btn btn-primary rounded-pill px-4" onclick="document.getElementById('noticeFrame').contentWindow.print()">
                        <i class="fas fa-print me-2"></i>Print This Paper
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const viewBtns = document.querySelectorAll('.view-notice-btn');
            const noticeFrame = document.getElementById('noticeFrame');
            
            viewBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const url = this.getAttribute('data-url');
                    noticeFrame.src = url;
                });
            });

            // Clear src when modal closed to stop any loading
            document.getElementById('viewNoticeModal').addEventListener('hidden.bs.modal', function () {
                noticeFrame.src = "";
            });
        });
    </script>
</body>
</html>
