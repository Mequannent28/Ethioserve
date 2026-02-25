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

// Handle toggle status
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $job_id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT status FROM job_listings WHERE id = ? AND company_id = ?");
    $stmt->execute([$job_id, $company_id]);
    $job = $stmt->fetch();

    if ($job) {
        $new_status = $job['status'] === 'active' ? 'closed' : 'active';
        $pdo->prepare("UPDATE job_listings SET status = ? WHERE id = ?")->execute([$new_status, $job_id]);
        redirectWithMessage('jobs_management.php', 'success', 'Job status updated');
    }
}

// Handle delete
if (isset($_GET['delete']) && isset($_GET['id'])) {
    $job_id = (int) $_GET['id'];
    $pdo->prepare("DELETE FROM job_listings WHERE id = ? AND company_id = ?")->execute([$job_id, $company_id]);
    redirectWithMessage('jobs_management.php', 'success', 'Job listing deleted');
}

// Fetch all jobs for this company
$stmt = $pdo->prepare("
    SELECT jl.*, 
           (SELECT COUNT(*) FROM job_applications ja WHERE ja.job_id = jl.id) as app_count,
           jcat.name as cat_name
    FROM job_listings jl
    LEFT JOIN job_categories jcat ON jl.category_id = jcat.id
    WHERE jl.company_id = ?
    ORDER BY jl.created_at DESC
");
$stmt->execute([$company_id]);
$jobs = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Management - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .main-content {
            padding: 30px;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">My Job Listings</h2>
                    <p class="text-muted">Manage your active and closed job postings</p>
                </div>
                <a href="post_job.php" class="btn btn-primary-green rounded-pill px-4 shadow-sm">
                    <i class="fas fa-plus me-2"></i>Post New Job
                </a>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-4">Job Title</th>
                                    <th class="border-0">Category</th>
                                    <th class="border-0">Type</th>
                                    <th class="border-0">Applications</th>
                                    <th class="border-0">Views</th>
                                    <th class="border-0">Status</th>
                                    <th class="border-0">Posted Date</th>
                                    <th class="border-0 text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($jobs)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center py-5 text-muted">No jobs posted yet</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td class="px-4">
                                                <h6 class="mb-0 fw-bold">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </h6>
                                                <small class="text-muted"><i class="fas fa-map-marker-alt me-1"></i>
                                                    <?php echo htmlspecialchars($job['location']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($job['cat_name'] ?: 'Other'); ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border rounded-pill">
                                                    <?php echo ucwords(str_replace('_', ' ', $job['job_type'])); ?>
                                                </span></td>
                                            <td>
                                                <a href="applications.php?job_id=<?php echo $job['id']; ?>"
                                                    class="text-decoration-none">
                                                    <span class="badge bg-info rounded-pill px-3">
                                                        <?php echo $job['app_count']; ?> Apps
                                                    </span>
                                                </a>
                                            </td>
                                            <td><small class="fw-bold">
                                                    <?php echo $job['views']; ?>
                                                </small></td>
                                            <td>
                                                <a href="?toggle_status=1&id=<?php echo $job['id']; ?>"
                                                    class="text-decoration-none">
                                                    <?php echo getStatusBadge($job['status']); ?>
                                                </a>
                                            </td>
                                            <td><small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                                                </small></td>
                                            <td class="text-end px-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm rounded-pill" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu dropdown-menu-end border-0 shadow">
                                                        <li><a class="dropdown-item"
                                                                href="edit_job.php?id=<?php echo $job['id']; ?>"><i
                                                                    class="fas fa-edit me-2"></i>Edit</a></li>
                                                        <li><a class="dropdown-item"
                                                                href="applications.php?job_id=<?php echo $job['id']; ?>"><i
                                                                    class="fas fa-users me-2"></i>View Applicants</a></li>
                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li><a class="dropdown-item text-danger"
                                                                href="?delete=1&id=<?php echo $job['id']; ?>"
                                                                onclick="return confirm('Are you sure you want to delete this job listing?')"><i
                                                                    class="fas fa-trash me-2"></i>Delete</a></li>
                                                    </ul>
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
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>