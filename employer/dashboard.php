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
    // Create a default company entry if somehow missing
    $stmt = $pdo->prepare("INSERT INTO job_companies (user_id, company_name) VALUES (?, ?)");
    $stmt->execute([$user_id, getCurrentUserName() . "'s Company"]);

    $stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();
}

$company_id = $company['id'];

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM job_listings WHERE company_id = ?");
$stmt->execute([$company_id]);
$total_jobs = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ?
");
$stmt->execute([$company_id]);
$total_applications = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ? AND ja.status = 'pending'
");
$stmt->execute([$company_id]);
$pending_applications = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ? AND ja.status = 'hired'
");
$stmt->execute([$company_id]);
$total_hired = $stmt->fetchColumn();

// Get recent applications
$stmt = $pdo->prepare("
    SELECT ja.*, jl.title as job_title, u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone, jp.cv_url, jp.profile_pic
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    JOIN users u ON ja.applicant_id = u.id
    LEFT JOIN job_profiles jp ON u.id = jp.user_id
    WHERE jl.company_id = ?
    ORDER BY ja.applied_at DESC
    LIMIT 5
");
$stmt->execute([$company_id]);
$recent_applications = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Administration - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        :root {
            --premium-green: #1B5E20;
            --premium-yellow: #FFB300;
            --premium-blue: #0288D1;
            --premium-red: #D32F2F;
        }

        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            padding: 40px;
            min-height: 100vh;
        }

        .premium-stat-card {
            border: none;
            border-radius: 15px;
            color: #fff;
            padding: 25px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .premium-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            position: absolute;
            right: 20px;
            bottom: 20px;
            font-size: 2.5rem;
            opacity: 0.2;
        }

        .stat-label {
            font-size: 0.8rem;
            font-weight: 600;
            text-uppercase: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.9;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 0;
        }

        .stat-desc {
            font-size: 0.85rem;
            opacity: 0.8;
            margin-top: 5px;
        }

        .card-custom {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
        }

        .table thead th {
            background: #f8f9fa;
            border: none;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #6c757d;
            padding: 15px;
        }

        .table tbody td {
            padding: 15px;
            border-bottom: 1px solid #f1f1f1;
        }

        .btn-premium-toggle {
            background: #1B5E20;
            color: #fff;
            border-radius: 50px;
            padding: 5px 20px;
            font-size: 0.85rem;
            border: none;
        }

        .profile-avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
        }

        .status-badge-active {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <div class="d-flex justify-content-between align-items-start mb-5">
                <div>
                    <h2 class="fw-bold mb-1">Employer Administration</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>!</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <span class="status-badge-active">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i> Status: Active
                    </span>
                    <div class="dropdown">
                        <button class="btn btn-white shadow-sm dropdown-toggle rounded-pill px-3 py-2 border"
                            data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($company['company_name']); ?>&background=1B5E20&color=fff"
                                class="rounded-circle me-2" width="25">
                            <span class="small fw-bold"><?php echo htmlspecialchars(getCurrentUserName()); ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2">
                            <li><a class="dropdown-item" href="company_profile.php"><i
                                        class="fas fa-building me-2"></i>Company Profile</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Logout Portal</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Enhanced Stat Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="premium-stat-card" style="background: var(--premium-green);">
                        <div class="stat-label">Total Postings</div>
                        <div class="stat-value"><?php echo $total_jobs; ?></div>
                        <div class="stat-desc"><i class="fas fa-briefcase me-1"></i> Active listings</div>
                        <i class="fas fa-briefcase stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-stat-card" style="background: var(--premium-yellow);">
                        <div class="stat-label">Total Applications</div>
                        <div class="stat-value"><?php echo $total_applications; ?></div>
                        <div class="stat-desc"><i class="fas fa-users me-1"></i> Lifetime volume</div>
                        <i class="fas fa-users stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-stat-card" style="background: var(--premium-blue);">
                        <div class="stat-label">Hired Candidates</div>
                        <div class="stat-value"><?php echo $total_hired; ?></div>
                        <div class="stat-desc"><i class="fas fa-check-circle me-1"></i> Successful hires</div>
                        <i class="fas fa-user-check stat-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="premium-stat-card" style="background: var(--premium-red);">
                        <div class="stat-label">Pending Reviews</div>
                        <div class="stat-value"><?php echo $pending_applications; ?></div>
                        <div class="stat-desc"><i class="fas fa-clock me-1"></i> Needs attention</div>
                        <i class="fas fa-file-invoice stat-icon"></i>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-8">
                    <!-- Pending Section Style Like Image -->
                    <div class="card card-custom mb-4">
                        <div
                            class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i class="far fa-clock text-warning me-2"></i>New
                                Applications</h6>
                            <span class="badge bg-warning rounded-pill px-3"><?php echo $pending_applications; ?>
                                pending</span>
                        </div>
                        <div class="card-body py-5 text-center">
                            <?php if ($pending_applications == 0): ?>
                                <div class="mb-3">
                                    <i class="fas fa-check-circle text-success fs-1"></i>
                                </div>
                                <h6 class="text-dark fw-bold">No pending applications. Great job!</h6>
                            <?php else: ?>
                                <p class="text-muted">You have <?php echo $pending_applications; ?> applications waiting for
                                    review.</p>
                                <a href="applications.php?status=pending" class="btn btn-premium-toggle px-4">Review Now</a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Table -->
                    <div class="card card-custom">
                        <div
                            class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark">Recent Applications</h6>
                            <a href="applications.php"
                                class="btn btn-sm btn-light rounded-pill px-3 border shadow-sm">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Applicant</th>
                                            <th>Position</th>
                                            <th>Status</th>
                                            <th class="text-end">Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($recent_applications)): ?>
                                            <tr>
                                                <td colspan="4" class="text-center py-4 text-muted">No recent applications
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($recent_applications as $app): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-3">
                                                            <img src="<?php echo $app['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($app['applicant_name']) . '&background=f0f2f5&color=1B5E20&bold=true'; ?>"
                                                                class="profile-avatar border shadow-sm">
                                                            <div>
                                                                <div class="fw-bold text-dark" style="font-size: 0.9rem;">
                                                                    <?php echo htmlspecialchars($app['applicant_name']); ?>
                                                                </div>
                                                                <div class="text-muted" style="font-size: 0.75rem;">
                                                                    <?php echo htmlspecialchars($app['applicant_email']); ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td class="small fw-semibold text-muted">
                                                        <?php echo htmlspecialchars($app['job_title']); ?></td>
                                                    <td><?php echo getStatusBadge($app['status']); ?></td>
                                                    <td class="text-end small text-muted">
                                                        <?php echo date('M d, H:i', strtotime($app['applied_at'])); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card card-custom h-100">
                        <div
                            class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0 text-dark"><i
                                    class="far fa-calendar-alt text-success me-2"></i>Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="post_job.php"
                                    class="list-group-item list-group-item-action border-0 px-0 py-3 d-flex align-items-center">
                                    <div class="icon-box me-3 rounded-circle bg-light d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="fas fa-plus text-success"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small">Post New Job</div>
                                        <div class="text-muted extra-small" style="font-size: 0.7rem;">Create a new
                                            vacancy listing</div>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                                </a>
                                <a href="jobs_management.php"
                                    class="list-group-item list-group-item-action border-0 px-0 py-3 d-flex align-items-center">
                                    <div class="icon-box me-3 rounded-circle bg-light d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="fas fa-list text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small">Manage Listings</div>
                                        <div class="text-muted extra-small" style="font-size: 0.7rem;">Edit or close
                                            active vacancies</div>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                                </a>
                                <a href="company_profile.php"
                                    class="list-group-item list-group-item-action border-0 px-0 py-3 d-flex align-items-center">
                                    <div class="icon-box me-3 rounded-circle bg-light d-flex align-items-center justify-content-center"
                                        style="width: 40px; height: 40px;">
                                        <i class="fas fa-building text-warning"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small">Company Profile</div>
                                        <div class="text-muted extra-small" style="font-size: 0.7rem;">Update brand
                                            information</div>
                                    </div>
                                    <i class="fas fa-chevron-right ms-auto text-muted small"></i>
                                </a>
                            </div>

                            <div class="mt-5 p-4 rounded-4 text-white text-center"
                                style="background: linear-gradient(135deg, #1B5E20 0%, #388E3C 100%);">
                                <h6 class="fw-bold mb-3">Premium Recruiter</h6>
                                <p class="extra-small opacity-75 mb-4" style="font-size: 0.75rem;">Your company profile
                                    is verified and active.</p>
                                <button class="btn btn-light btn-sm rounded-pill px-4 fw-bold text-success w-100">View
                                    Public Page</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>