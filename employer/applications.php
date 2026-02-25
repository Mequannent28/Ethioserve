<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('employer');

$user_id = getCurrentUserId();

// Get company details
$stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
$company_id = $company['id'];

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $app_id = (int) $_POST['application_id'];
        $new_status = sanitize($_POST['status']);
        $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
        $notes = sanitize($_POST['notes'] ?? '');

        $valid_statuses = ['pending', 'shortlisted', 'interviewed', 'hired', 'rejected'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("
                UPDATE job_applications ja
                JOIN job_listings jl ON ja.job_id = jl.id
                SET ja.status = ?, ja.interview_date = ?, ja.notes = ?
                WHERE ja.id = ? AND jl.company_id = ?
            ");
            $stmt->execute([$new_status, $interview_date, $notes, $app_id, $company_id]);

            // Trigger email notification
            sendJobApplicationEmail($pdo, $app_id);

            redirectWithMessage('applications.php', 'success', 'Application updated successfully');
        }
    }
}

// Filter by job if requested
$filter_job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$filter_status = isset($_GET['status']) ? sanitize($_GET['status']) : '';

// Fetch potential filter jobs
$stmt = $pdo->prepare("SELECT id, title FROM job_listings WHERE company_id = ? ORDER BY title ASC");
$stmt->execute([$company_id]);
$filter_jobs = $stmt->fetchAll();

// Build query
$query = "
    SELECT ja.*, jl.title as job_title, u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone, jp.cv_url, jp.profile_pic, jp.headline
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    JOIN users u ON ja.applicant_id = u.id
    LEFT JOIN job_profiles jp ON u.id = jp.user_id
    WHERE jl.company_id = ?
";
$params = [$company_id];

if ($filter_job_id) {
    $query .= " AND ja.job_id = ?";
    $params[] = $filter_job_id;
}
if ($filter_status) {
    $query .= " AND ja.status = ?";
    $params[] = $filter_status;
}

$query .= " ORDER BY ja.applied_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Applications - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { background-color: #f0f2f5; }
        .main-content { padding: 30px; min-height: 100vh; }
        .applicant-img { width: 50px; height: 50px; object-fit: cover; border-radius: 12px; }
        .filter-section { background: #fff; border-radius: 15px; padding: 20px; }
    </style>
</head>
<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Job Applications</h2>
                    <p class="text-muted">Review and manage candidates for your positions</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section shadow-sm mb-4">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Filter by Job Posting</label>
                        <select name="job_id" class="form-select rounded-pill">
                            <option value="">All Jobs</option>
                            <?php foreach ($filter_jobs as $fj): ?>
                                <option value="<?php echo $fj['id']; ?>" <?php echo $filter_job_id == $fj['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($fj['title']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Status</label>
                        <select name="status" class="form-select rounded-pill">
                            <option value="">All Statuses</option>
                            <option value="pending" <?php echo $filter_status == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="shortlisted" <?php echo $filter_status == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                            <option value="interviewed" <?php echo $filter_status == 'interviewed' ? 'selected' : ''; ?>>Interviewed</option>
                            <option value="hired" <?php echo $filter_status == 'hired' ? 'selected' : ''; ?>>Hired</option>
                            <option value="rejected" <?php echo $filter_status == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary-green rounded-pill w-100 px-4">
                            <i class="fas fa-filter me-2"></i>Apply
                        </button>
                    </div>
                    <div class="col-md-2">
                        <a href="applications.php" class="btn btn-light rounded-pill w-100 border">Clear</a>
                    </div>
                </form>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4">Applicant</th>
                                    <th class="border-0">Position</th>
                                    <th class="border-0">Applied On</th>
                                    <th class="border-0">Interview Date</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0 text-end px-4">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr><td colspan="6" class="text-center py-5 text-muted">No applications matching your filters</td></tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="<?php echo $app['profile_pic'] ?: 'https://ui-avatars.com/api/?name='.urlencode($app['applicant_name']); ?>" class="applicant-img border">
                                                    <div>
                                                        <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($app['applicant_name']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($app['headline'] ?: $app['applicant_email']); ?></small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                            <td><small><?php echo date('M d, Y', strtotime($app['applied_at'])); ?></small></td>
                                            <td>
                                                <?php if ($app['interview_date']): ?>
                                                    <span class="badge bg-light text-primary border rounded-pill">
                                                        <i class="fas fa-calendar-alt me-1"></i><?php echo date('M d, H:i', strtotime($app['interview_date'])); ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted small">Not set</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo getStatusBadge($app['status']); ?></td>
                                            <td class="text-end px-4">
                                                <button class="btn btn-sm btn-white border shadow-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#reviewModal<?php echo $app['id']; ?>">
                                                    Manage
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Review Modal -->
                                        <div class="modal fade" id="reviewModal<?php echo $app['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-lg">
                                                <div class="modal-content border-0 shadow rounded-4">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">Application Review</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body p-4">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                                            <input type="hidden" name="update_application_status" value="1">

                                                            <div class="row mb-4">
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small text-uppercase fw-bold mb-2">Applicant Details</label>
                                                                    <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($app['applicant_name']); ?></p>
                                                                    <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($app['applicant_phone'] ?: 'N/A'); ?></p>
                                                                    <p class="mb-3"><strong>Email:</strong> <?php echo htmlspecialchars($app['applicant_email']); ?></p>
                                                                    <?php if ($app['cv_url']): ?>
                                                                        <a href="<?php echo BASE_URL . $app['cv_url']; ?>" target="_blank" class="btn btn-sm btn-info text-white rounded-pill px-3">
                                                                            <i class="fas fa-file-pdf me-1"></i> View CV
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <label class="text-muted small text-uppercase fw-bold mb-2">Job Details</label>
                                                                    <p class="mb-1"><strong>Position:</strong> <?php echo htmlspecialchars($app['job_title']); ?></p>
                                                                    <p class="mb-1"><strong>Applied:</strong> <?php echo date('M d, Y', strtotime($app['applied_at'])); ?></p>
                                                                </div>
                                                            </div>

                                                            <div class="mb-3">
                                                                <label class="form-label fw-bold">Update Status</label>
                                                                <select name="status" class="form-select rounded-3">
                                                                    <option value="pending" <?php echo $app['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                    <option value="shortlisted" <?php echo $app['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                                                    <option value="interviewed" <?php echo $app['status'] == 'interviewed' ? 'selected' : ''; ?>>Interview Scheduled</option>
                                                                    <option value="hired" <?php echo $app['status'] == 'hired' ? 'selected' : ''; ?>>Hired</option>
                                                                    <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                </select>
                                                            </div>

                                                            <div class="mb-3 interview-date-div" style="<?php echo $app['status'] == 'interviewed' ? '' : 'display:none;'; ?>">
                                                                <label class="form-label fw-bold">Interview Date & Time</label>
                                                                <input type="datetime-local" name="interview_date" class="form-control rounded-3" value="<?php echo $app['interview_date'] ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : ''; ?>">
                                                            </div>

                                                            <div class="mb-0">
                                                                <label class="form-label fw-bold">Internal Notes</label>
                                                                <textarea name="notes" class="form-control rounded-3" rows="3" placeholder="Add notes about this applicant..."><?php echo htmlspecialchars($app['notes'] ?? ''); ?></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 p-4">
                                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-primary rounded-pill px-4">Save Changes</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('select[name="status"]').forEach(select => {
            select.addEventListener('change', function() {
                const modal = this.closest('.modal-content');
                const interviewDiv = modal.querySelector('.interview-date-div');
                if (interviewDiv) {
                    interviewDiv.style.display = this.value === 'interviewed' ? 'block' : 'none';
                }
            });
        });
    </script>
</body>
</html>
