<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an employer
requireRole('employer');

$user_id = getCurrentUserId();

// Get company details
$stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: company_profile.php");
    exit();
}

$company_id = $company['id'];

// Fetch Exams
$stmt = $pdo->prepare("SELECT e.*, (SELECT COUNT(*) FROM job_questions WHERE exam_id = e.id) as q_count FROM job_exams e WHERE company_id = ? ORDER BY created_at DESC");
$stmt->execute([$company_id]);
$exams = $stmt->fetchAll();

// Fetch Recent Attempts
$stmt = $pdo->prepare("
    SELECT a.*, e.title as exam_title, u.full_name as candidate_name, u.email as candidate_email
    FROM job_exam_attempts a
    JOIN job_exams e ON a.exam_id = e.id
    JOIN users u ON a.candidate_id = u.id
    WHERE a.company_id = ?
    ORDER BY a.created_at DESC LIMIT 10
");
$stmt->execute([$company_id]);
$attempts = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LMS & Exams - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #f4f7f6; }
        .main-content { padding: 40px; min-height: 100vh; }
        .card-custom { border: none; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
    </style>
</head>
<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="fw-bold"><i class="fas fa-graduation-cap me-2 text-primary"></i>LMS & Exams</h2>
                <a href="create_exam.php" class="btn btn-primary rounded-pill px-4"><i class="fas fa-plus me-2"></i>Create New Exam</a>
            </div>

            <div class="row mb-5">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="fw-bold mb-0">My Exams</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Title</th>
                                            <th>Description</th>
                                            <th>Duration</th>
                                            <th>Questions</th>
                                            <th>Created At</th>
                                            <th class="text-end">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($exams)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted">No exams created yet.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($exams as $exam): ?>
                                            <tr>
                                                <td class="fw-bold text-dark"><?php echo htmlspecialchars($exam['title']); ?></td>
                                                <td><?php echo htmlspecialchars(substr($exam['description'], 0, 50)) . '...'; ?></td>
                                                <td><?php echo $exam['duration_minutes']; ?> mins</td>
                                                <td><span class="badge bg-secondary"><?php echo $exam['q_count']; ?></span></td>
                                                <td><?php echo date('M d, Y', strtotime($exam['created_at'])); ?></td>
                                                <td class="text-end">
                                                    <a href="edit_exam.php?id=<?php echo $exam['id']; ?>" class="btn btn-sm btn-light border"><i class="fas fa-edit text-primary"></i></a>
                                                    <a href="javascript:void(0)" class="btn btn-sm btn-light border text-danger" onclick="confirmExamDelete('<?php echo $exam['id']; ?>', '<?php echo htmlspecialchars($exam['title'], ENT_QUOTES); ?>')"><i class="fas fa-trash"></i></a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12">
                    <div class="card card-custom">
                        <div class="card-header bg-white py-3 border-0">
                            <h5 class="fw-bold mb-0">Recent Candidate Attempts</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Candidate</th>
                                            <th>Exam Title</th>
                                            <th>Status</th>
                                            <th>Score</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($attempts)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">No candidate attempts found.</td>
                                        </tr>
                                        <?php else: ?>
                                            <?php foreach ($attempts as $attempt): ?>
                                            <tr>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($attempt['candidate_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($attempt['candidate_email']); ?></div>
                                                </td>
                                                <td><?php echo htmlspecialchars($attempt['exam_title']); ?></td>
                                                <td>
                                                    <?php 
                                                        $badge = 'bg-warning';
                                                        if($attempt['status'] == 'completed') $badge = 'bg-success';
                                                        if($attempt['status'] == 'ongoing') $badge = 'bg-primary';
                                                    ?>
                                                    <span class="badge <?php echo $badge; ?>"><?php echo ucfirst($attempt['status']); ?></span>
                                                </td>
                                                <td class="fw-bold">
                                                    <?php echo $attempt['status'] == 'completed' ? ($attempt['score'] . ' / ' . $attempt['total_score']) : '-'; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($attempt['created_at'])); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmExamDelete(examId, examTitle) {
            Swal.fire({
                title: '<span style="color:#d32f2f;"><i class="fas fa-trash-alt me-2"></i>Move to Recycle Bin?</span>',
                html: `
                    <p class="text-muted mb-3">You are about to delete <strong>${examTitle}</strong>. Please select a reason:</p>
                    <select id="deleteReason" class="form-select rounded-3">
                        <option value="No longer relevant">No longer relevant</option>
                        <option value="Duplicate exam">Duplicate exam</option>
                        <option value="Content needs update">Content needs update</option>
                        <option value="Job listing closed">Job listing closed</option>
                        <option value="Other">Other</option>
                    </select>
                `,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash-restore me-1"></i> Move to Bin',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#6c757d',
                focusConfirm: false,
                preConfirm: () => {
                    return document.getElementById('deleteReason').value;
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `delete_exam.php?id=${examId}&reason=${encodeURIComponent(result.value)}`;
                }
            });
        }
    </script>
</body>
</html>
