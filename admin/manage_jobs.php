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

// Handle application status update (e.g. Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_application_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $application_id = (int) $_POST['application_id'];
        $status = sanitize($_POST['status']);

        $stmt = $pdo->prepare("UPDATE job_applications SET status = ? WHERE id = ?");
        $stmt->execute([$status, $application_id]);

        // Send Email
        sendJobApplicationEmail($pdo, $application_id);

        redirectWithMessage('manage_jobs.php', 'success', 'Application status updated and notification email sent');
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

// Fetch all applications
$stmt = $pdo->query("
    SELECT ja.*, jl.title as job_title, jc.company_name, 
           u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone,
           jp.profile_pic, jp.headline, jp.bio, jp.skills, jp.experience_years, jp.education
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    JOIN job_companies jc ON jl.company_id = jc.id
    JOIN users u ON ja.applicant_id = u.id
    LEFT JOIN job_profiles jp ON u.id = jp.user_id
    ORDER BY ja.applied_at DESC
    LIMIT 50
");
$applications = $stmt->fetchAll();

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
    <title>Recruitment Hub - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f4f6f9;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            min-height: 100vh;
        }

        .company-logo,
        .applicant-avatar {
            width: 45px;
            height: 45px;
            object-fit: cover;
            border-radius: 10px;
        }

        .card-header {
            border-bottom: 0;
            background-color: #fff;
            padding: 1.25rem 1.5rem;
        }

        .nav-tabs .nav-link {
            border: none;
            color: #6c757d;
            font-weight: 600;
            padding: 1rem 1.5rem;
        }

        .nav-tabs .nav-link.active {
            color: #1B5E20;
            border-bottom: 3px solid #1B5E20;
            background: transparent;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-0">Recruitment Hub</h2>
                    <p class="text-muted">Global oversight of employers and candidates</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="add_company.php" class="btn btn-outline-success rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>New Employer
                    </a>
                </div>
            </div>

            <!-- Global Recruitment Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-4 bg-white">
                        <p class="small fw-bold text-uppercase text-muted mb-1">Total Companies</p>
                        <h2 class="fw-bold mb-0 text-dark"><?php echo $total_companies; ?></h2>
                        <small class="text-success fw-bold"><i
                                class="fas fa-check-circle me-1"></i><?php echo $verified_companies; ?> Verified</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-4 bg-white border-start border-warning border-4">
                        <p class="small fw-bold text-uppercase text-muted mb-1">Company Requests</p>
                        <h2 class="fw-bold mb-0 text-warning"><?php echo $pending_verification; ?></h2>
                        <small class="text-muted">Awaiting access</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-4 bg-white">
                        <p class="small fw-bold text-uppercase text-muted mb-1">Applications</p>
                        <h2 class="fw-bold mb-0 text-primary"><?php echo count($applications); ?></h2>
                        <small class="text-muted">Last 30 days</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm p-4 bg-primary-green text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Active Vacancies</p>
                        <h2 class="fw-bold mb-0">
                            <?php echo array_sum(array_column($companies, 'job_count')); ?>
                        </h2>
                        <small class="opacity-75">Live on platform</small>
                    </div>
                </div>
            </div>

            <!-- Main Management Tabs -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header p-0">
                    <ul class="nav nav-tabs" id="recruitmentTabs" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active" id="companies-tab" data-bs-toggle="tab"
                                data-bs-target="#companies-content" type="button">
                                <i class="fas fa-building me-2"></i>Employers & Companies
                            </button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link" id="applications-tab" data-bs-toggle="tab"
                                data-bs-target="#applications-content" type="button">
                                <i class="fas fa-file-invoice me-2"></i>Global Applications
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="tab-content" id="recruitmentTabsContent">
                    <!-- Companies Tab -->
                    <div class="tab-pane fade show active" id="companies-content" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4">Company</th>
                                        <th>Owner / Recruiter</th>
                                        <th>Performance</th>
                                        <th>Status</th>
                                        <th class="text-end px-4">Verification</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($companies)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No companies found</td>
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
                                                                <?php echo htmlspecialchars($company['company_name']); ?></h6>
                                                            <small
                                                                class="text-muted"><?php echo htmlspecialchars($company['location'] ?: 'Not set'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($company['owner_name']); ?>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars($company['owner_email']); ?></div>
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-light text-dark border"><?php echo $company['job_count']; ?>
                                                        Jobs</span>
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
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="company_id"
                                                                value="<?php echo $company['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="status"
                                                                value="<?php echo $company['verified'] ? '0' : '1'; ?>">
                                                            <button type="submit"
                                                                class="btn btn-sm <?php echo $company['verified'] ? 'btn-outline-warning' : 'btn-success'; ?> rounded-pill px-3">
                                                                <?php echo $company['verified'] ? 'Revoke' : 'Verify'; ?>
                                                            </button>
                                                        </form>
                                                        <a href="?delete=<?php echo $company['id']; ?>"
                                                            class="btn btn-sm btn-outline-danger rounded-circle p-0 d-flex align-items-center justify-content-center"
                                                            style="width:32px; height:32px;"
                                                            onclick="return confirm('Remove this company?')">
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

                    <!-- Applications Tab -->
                    <div class="tab-pane fade" id="applications-content" role="tabpanel">
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4">Candidate</th>
                                        <th>Positon & Employer</th>
                                        <th>Submission</th>
                                        <th>Current Status</th>
                                        <th class="text-end px-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($applications)): ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-5 text-muted">No applications found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($applications as $app): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?php echo $app['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($app['applicant_name']) . '&size=100'; ?>"
                                                            class="applicant-avatar border">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold">
                                                                <?php echo htmlspecialchars($app['applicant_name']); ?></h6>
                                                            <small
                                                                class="text-muted"><?php echo htmlspecialchars($app['headline'] ?: 'Job Seeker'); ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($app['job_title']); ?>
                                                    </div>
                                                    <div class="text-muted small">@
                                                        <?php echo htmlspecialchars($app['company_name']); ?></div>
                                                </td>
                                                <td class="small"><?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                                </td>
                                                <td><?php echo getStatusBadge($app['status']); ?></td>
                                                <td class="text-end px-4">
                                                    <div class="d-flex justify-content-end gap-2">
                                                        <button class="btn btn-sm btn-info text-white rounded-pill px-3"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#detailedModal<?php echo $app['id']; ?>">
                                                            <i class="fas fa-eye me-1"></i>Details
                                                        </button>
                                                        <form method="POST" class="d-inline">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="application_id"
                                                                value="<?php echo $app['id']; ?>">
                                                            <input type="hidden" name="update_application_status" value="1">
                                                            <input type="hidden" name="status" value="rejected">
                                                            <button type="submit"
                                                                class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                                                onclick="return confirm('Reject this application and send email?')">
                                                                <i class="fas fa-times me-1"></i>Reject
                                                            </button>
                                                        </form>
                                                    </div>
                                                </td>
                                            </tr>

                                            <!-- Detailed Applicant Modal -->
                                            <div class="modal fade" id="detailedModal<?php echo $app['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-lg border-0 shadow-lg">
                                                    <div class="modal-content rounded-4 border-0">
                                                        <div class="modal-header border-0 bg-light rounded-top-4">
                                                            <h5 class="modal-title fw-bold">Candidate Deep Dive</h5>
                                                            <button type="button" class="btn-close"
                                                                data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <div class="row items-center mb-4">
                                                                <div class="col-md-4 text-center border-end">
                                                                    <img src="<?php echo $app['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($app['applicant_name']) . '&size=200'; ?>"
                                                                        class="img-fluid rounded-4 shadow-sm mb-3"
                                                                        style="width: 140px; height: 140px; object-fit: cover;">
                                                                    <h5 class="fw-bold mb-0">
                                                                        <?php echo htmlspecialchars($app['applicant_name']); ?>
                                                                    </h5>
                                                                    <p class="text-primary small mb-2">
                                                                        <?php echo htmlspecialchars($app['headline'] ?: 'Candidate'); ?>
                                                                    </p>
                                                                    <div class="d-grid gap-2 mt-3">
                                                                        <?php if ($app['cv_url']): ?>
                                                                            <a href="<?php echo BASE_URL . $app['cv_url']; ?>"
                                                                                target="_blank"
                                                                                class="btn btn-outline-primary btn-sm rounded-pill">View
                                                                                Documented CV</a>
                                                                        <?php endif; ?>
                                                                        <a href="mailto:<?php echo $app['applicant_email']; ?>"
                                                                            class="btn btn-outline-secondary btn-sm rounded-pill">Direct
                                                                            Message</a>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-8 px-4">
                                                                    <div class="mb-4">
                                                                        <h6
                                                                            class="fw-bold text-uppercase small text-muted mb-3">
                                                                            <i class="fas fa-address-card me-2"></i>Summary &
                                                                            Biography</h6>
                                                                        <p class="small text-muted mb-0">
                                                                            <?php echo nl2br(htmlspecialchars($app['bio'] ?: 'No professional summary provided.')); ?>
                                                                        </p>
                                                                    </div>
                                                                    <div class="row g-3">
                                                                        <div class="col-6">
                                                                            <h6
                                                                                class="fw-bold text-uppercase small text-muted mb-2">
                                                                                Qualifications</h6>
                                                                            <p class="small mb-0"><strong>Edu:</strong>
                                                                                <?php echo htmlspecialchars($app['education'] ?: 'Self-taught / N/A'); ?>
                                                                            </p>
                                                                            <p class="small mb-0"><strong>Exp:</strong>
                                                                                <?php echo $app['experience_years']; ?> Years
                                                                            </p>
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <h6
                                                                                class="fw-bold text-uppercase small text-muted mb-2">
                                                                                Technical Skills</h6>
                                                                            <div class="d-flex flex-wrap gap-1">
                                                                                <?php $sk = explode(',', $app['skills'] ?? '');
                                                                                foreach ($sk as $s):
                                                                                    if (trim($s)): ?>
                                                                                        <span
                                                                                            class="badge bg-light text-dark border-0 small shadow-sm"><?php echo htmlspecialchars(trim($s)); ?></span>
                                                                                    <?php endif; endforeach; ?>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                    <hr class="my-4 opacity-25">
                                                                    <div class="mb-0">
                                                                        <h6
                                                                            class="fw-bold text-uppercase small text-muted mb-2">
                                                                            Application Context</h6>
                                                                        <p class="small mb-1 text-dark"><strong>Target
                                                                                Position:</strong>
                                                                            <?php echo htmlspecialchars($app['job_title']); ?>
                                                                        </p>
                                                                        <p class="small mb-0 text-dark">
                                                                            <strong>Employer:</strong>
                                                                            <?php echo htmlspecialchars($app['company_name']); ?>
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0 p-4 pt-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-4"
                                                                data-bs-dismiss="modal">Close Review</button>
                                                        </div>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>