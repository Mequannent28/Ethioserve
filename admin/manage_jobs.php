<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('admin');

// Handle company status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $company_id = (int) $_POST['company_id'];
        $status = (int) $_POST['status']; // 1 for verified, 0 for not

        $stmt = $pdo->prepare("UPDATE job_companies SET verified = ? WHERE id = ?");
        $stmt->execute([$status, $company_id]);
        redirectWithMessage('manage_jobs.php', 'success', 'Company verification status updated');
    }
}

// Handle company deletion
if (isset($_GET['delete'])) {
    $company_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM job_companies WHERE id = ?");
    $stmt->execute([$company_id]);
    redirectWithMessage('manage_jobs.php', 'success', 'Company removed successfully');
}

// Fetch all companies
$stmt = $pdo->query("
    SELECT c.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone,
           (SELECT COUNT(*) FROM job_listings WHERE company_id = c.id) as job_count
    FROM job_companies c
    JOIN users u ON c.user_id = u.id
    ORDER BY c.verified ASC, c.company_name ASC
");
$companies = $stmt->fetchAll();

// Stats
$total_companies = count($companies);
$verified_companies = 0;
foreach ($companies as $c)
    if ($c['verified'])
        $verified_companies++;
$pending_verification = $total_companies - $verified_companies;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recruitment - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            width: 100%;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .company-logo {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Manage Recruitment</h2>
                    <p class="text-muted">Verify companies and manage employer accounts</p>
                </div>
                <a href="add_company.php" class="btn btn-primary-green rounded-pill px-4">
                    <i class="fas fa-plus me-2"></i>Register Company
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 bg-primary-green text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Companies</p>
                        <h2 class="fw-bold mb-0">
                            <?php echo $total_companies; ?>
                        </h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 bg-warning text-dark">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Pending Verification</p>
                        <h2 class="fw-bold mb-0">
                            <?php echo $pending_verification; ?>
                        </h2>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-4 bg-info text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Active Job Postings</p>
                        <h2 class="fw-bold mb-0">
                            <?php
                            $total_jobs = array_sum(array_column($companies, 'job_count'));
                            echo $total_jobs;
                            ?>
                        </h2>
                    </div>
                </div>
            </div>

            <!-- Companies Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Company</th>
                                <th>Owner / Recruiter</th>
                                <th>Industry & Location</th>
                                <th>Jobs</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($companies)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-building fs-1 mb-3 d-block"></i>
                                        No companies registered yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($companies as $company): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo $company['logo_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($company['company_name']) . '&background=1B5E20&color=fff&bold=true'; ?>"
                                                    class="company-logo border">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($company['company_name']); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($company['website'] ?: 'No website'); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($company['owner_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($company['owner_email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small fw-bold">
                                                <?php echo htmlspecialchars($company['industry'] ?: 'General'); ?>
                                            </div>
                                            <div class="text-muted small"><i class="fas fa-map-marker-alt me-1"></i>
                                                <?php echo htmlspecialchars($company['location'] ?: 'Not set'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo $company['job_count']; ?> jobs
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($company['verified']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check-circle me-1"></i>
                                                    Verified</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock me-1"></i>
                                                    Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="d-flex justify-content-end gap-2">
                                                <?php if (!$company['verified']): ?>
                                                    <form method="POST" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="company_id"
                                                            value="<?php echo $company['id']; ?>">
                                                        <input type="hidden" name="status" value="1">
                                                        <button type="submit" name="update_status"
                                                            class="btn btn-sm btn-success rounded-pill px-3">Verify</button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" class="d-inline">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="company_id"
                                                            value="<?php echo $company['id']; ?>">
                                                        <input type="hidden" name="status" value="0">
                                                        <button type="submit" name="update_status"
                                                            class="btn btn-sm btn-outline-warning rounded-pill px-3">Revoke</button>
                                                    </form>
                                                <?php endif; ?>
                                                <a href="?delete=<?php echo $company['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-circle"
                                                    onclick="return confirm('Remove this company and all its jobs?')"
                                                    style="width:32px; height:32px; display:inline-flex; align-items:center; justify-content:center;">
                                                    <i class="fas fa-trash-alt"></i>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>