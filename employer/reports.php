<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an employer
requireRole('employer');

$user_id = getCurrentUserId();

// Get employer/company details
$stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: dashboard.php");
    exit();
}

$company_id = $company['id'];

// Report 1: Applications per Job Listing (Engagement)
$stmt = $pdo->prepare("
    SELECT jl.title, COUNT(ja.id) as app_count 
    FROM job_listings jl
    LEFT JOIN job_applications ja ON jl.id = ja.job_id
    WHERE jl.company_id = ?
    GROUP BY jl.id
    ORDER BY app_count DESC
");
$stmt->execute([$company_id]);
$job_stats = $stmt->fetchAll();

// Report 2: Application status breakdown
$stmt = $pdo->prepare("
    SELECT ja.status, COUNT(*) as count 
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ?
    GROUP BY ja.status
");
$stmt->execute([$company_id]);
$status_stats = $stmt->fetchAll();

// Report 3: Application Trends (Daily for last 30 days)
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(applied_at, '%Y-%m-%d') as date, COUNT(*) as count 
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ?
    GROUP BY date
    ORDER BY date DESC
    LIMIT 30
");
$stmt->execute([$company_id]);
$application_trends = array_reverse($stmt->fetchAll());

// Report 4: Views per job listing
$stmt = $pdo->prepare("
    SELECT title, views 
    FROM job_listings 
    WHERE company_id = ? 
    ORDER BY views DESC
    LIMIT 10
");
$stmt->execute([$company_id]);
$view_stats = $stmt->fetchAll();

// Report 5: FULL Detailed Candidate List (The "Employee" List)
$stmt = $pdo->prepare("
    SELECT u.full_name as name, u.email, u.phone, jl.title as job_title, ja.status, ja.applied_at
    FROM job_applications ja
    JOIN users u ON ja.applicant_id = u.id
    JOIN job_listings jl ON ja.job_id = jl.id
    WHERE jl.company_id = ?
    ORDER BY ja.applied_at DESC
");
$stmt->execute([$company_id]);
$detailed_applicants = $stmt->fetchAll();

// Page Title
$page_title = "Hiring Intelligence - " . htmlspecialchars($company['company_name']);
include '../includes/header.php';
?>

<style>
    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }
        body {
            background: #fff !important;
            font-size: 11pt;
        }
        .sidebar, nav, footer, .btn, .no-print {
            display: none !important;
        }
        .main-content, main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #fff !important;
        }
        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
        }
        .card {
            border: 1px solid #ddd !important;
            box-shadow: none !important;
            break-inside: avoid;
            margin-bottom: 20px !important;
        }
        .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }
        .col-md-3 {
            width: 25% !important;
            flex: 0 0 25% !important;
        }
        .col-lg-7 {
            width: 60% !important;
            flex: 0 0 60% !important;
        }
        .col-lg-5 {
            width: 40% !important;
            flex: 0 0 40% !important;
        }
        .col-12 {
            width: 100% !important;
            flex: 0 0 100% !important;
        }
        .badge, .bg-primary, .progress-bar {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        .avatar-circle {
            border: 1px solid #ccc !important;
        }
    }
    .letter-spacing-1 { letter-spacing: 1px; }
    .tracking-wider { letter-spacing: 0.05em; }
    .avatar-circle {
        width: 45px;
        height: 45px;
        background: #f0f4f8;
        color: #1a73e8;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: 700;
        font-size: 1.1rem;
        border: 2px solid #fff;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .table-hover-custom tbody tr:hover {
        background-color: #f8fbff;
        cursor: default;
    }
    .card-reports {
        border: none;
        border-radius: 1.25rem;
        transition: transform 0.2s;
    }
    .bg-soft-primary { background-color: rgba(26, 115, 232, 0.08); }
    .text-primary-dark { color: #174ea6; }
</style>

<div class="d-flex">
    <?php include '../includes/sidebar_employer.php'; ?>
    
    <main class="flex-grow-1 p-4" style="background: #f4f7f6; min-height: 100vh;">
        <div class="container-fluid">
            <!-- Top Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 pb-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Recruitment Intelligence</h2>
                    <p class="text-muted mb-0 small text-uppercase fw-bold letter-spacing-1">Analytics Dashboard for <?php echo htmlspecialchars($company['company_name']); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white border shadow-sm rounded-3 px-3" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                    <a href="post_job.php" class="btn btn-primary shadow-sm rounded-3 px-4">
                        <i class="fas fa-plus me-2"></i> Post New Job
                    </a>
                </div>
            </div>

            <!-- Key Summary Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card card-reports shadow-sm p-3 bg-white border-start border-5 border-primary">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted small fw-bold mb-1">ACTIVE JOBS</h6>
                                <h3 class="fw-bold mb-0"><?php echo count($job_stats); ?></h3>
                            </div>
                            <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                                <i class="fas fa-briefcase fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-reports shadow-sm p-3 bg-white border-start border-5 border-success">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted small fw-bold mb-1">TOTAL APPLICANTS</h6>
                                <h3 class="fw-bold mb-0"><?php echo array_sum(array_column($status_stats, 'count')); ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-3 rounded-circle text-success">
                                <i class="fas fa-users fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-reports shadow-sm p-3 bg-white border-start border-5 border-warning">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted small fw-bold mb-1">HIRED HIRES</h6>
                                <h3 class="fw-bold mb-0">
                                    <?php
                                    $hired = 0;
                                    foreach ($status_stats as $ss)
                                        if ($ss['status'] == 'hired')
                                            $hired = $ss['count'];
                                    echo $hired;
                                    ?>
                                </h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-3 rounded-circle text-warning">
                                <i class="fas fa-user-check fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card card-reports shadow-sm p-3 bg-white border-start border-5 border-info">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <h6 class="text-muted small fw-bold mb-1">LISTING VIEWS</h6>
                                <h3 class="fw-bold mb-0"><?php echo number_format(array_sum(array_column($view_stats, 'views'))); ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-3 rounded-circle text-info">
                                <i class="fas fa-eye fa-lg"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- PRIMARY TABLE: DETAILED APPLICANT LIST (The "Employee" List requested) -->
                <div class="col-12">
                    <div class="card card-reports shadow-sm bg-white overflow-hidden">
                        <div class="card-header bg-white border-bottom py-3 px-4 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-list-ul text-primary me-2"></i>Detailed Candidate Tracking (Employee List)
                            </h5>
                            <span class="badge bg-soft-primary text-primary-dark px-3 py-2 rounded-pill fw-bold">
                                <?php echo count($detailed_applicants); ?> Candidates
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover table-hover-custom align-middle mb-0">
                                <thead class="bg-light">
                                    <tr class="text-uppercase small fw-bold text-muted">
                                        <th class="ps-4 py-3">Applicant Profile</th>
                                        <th>Target Position</th>
                                        <th>Contact Details</th>
                                        <th>Application Status</th>
                                        <th class="text-end pe-4">Applied Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($detailed_applicants)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-5">
                                                    <i class="fas fa-user-slash fs-1 text-muted opacity-25 mb-3"></i>
                                                    <p class="text-muted fw-bold">No application data available for display.</p>
                                                </td>
                                            </tr>
                                    <?php else: ?>
                                            <?php foreach ($detailed_applicants as $app): ?>
                                                    <tr>
                                                        <td class="ps-4">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <div class="avatar-circle">
                                                                    <?php echo strtoupper(substr($app['name'], 0, 1)); ?>
                                                                </div>
                                                                <div>
                                                                    <div class="fw-bold text-dark fs-6"><?php echo htmlspecialchars($app['name']); ?></div>
                                                                    <div class="text-primary small fw-semibold">ID: #<?php echo rand(1000, 9999); ?></div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-white text-dark border px-3 py-2 rounded-3 fw-medium">
                                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div class="small text-muted">
                                                                <div class="mb-1"><i class="fas fa-envelope-open me-2"></i><?php echo htmlspecialchars($app['email']); ?></div>
                                                                <div><i class="fas fa-mobile-alt me-2"></i><?php echo htmlspecialchars($app['phone']); ?></div>
                                                            </div>
                                                        </td>
                                                        <td>
                                                            <span class="badge w-100 rounded-pill py-2 <?php
                                                            echo match ($app['status']) {
                                                                'pending' => 'bg-warning text-dark',
                                                                'shortlisted' => 'bg-info',
                                                                'interviewed' => 'bg-primary',
                                                                'hired' => 'bg-success',
                                                                'rejected' => 'bg-danger',
                                                                default => 'bg-secondary'
                                                            };
                                                            ?>">
                                                                <i class="fas <?php
                                                                echo match ($app['status']) {
                                                                    'pending' => 'fa-clock',
                                                                    'shortlisted' => 'fa-star',
                                                                    'interviewed' => 'fa-comments',
                                                                    'hired' => 'fa-check-double',
                                                                    'rejected' => 'fa-times-circle',
                                                                    default => 'fa-info-circle'
                                                                };
                                                                ?> me-1"></i>
                                                                <?php echo strtoupper($app['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td class="text-end pe-4">
                                                            <div class="fw-bold text-dark small"><?php echo date('d M, Y', strtotime($app['applied_at'])); ?></div>
                                                            <div class="text-muted extra-small" style="font-size: 0.75rem;"><?php echo date('h:i A', strtotime($app['applied_at'])); ?></div>
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

            <div class="row g-4">
                <!-- Charts Row -->
                <div class="col-lg-7">
                    <div class="card card-reports shadow-sm bg-white p-4 h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Application Inflow Trend</h5>
                            <span class="text-muted small">Daily activity (Last 30 Days)</span>
                        </div>
                        <canvas id="appTrendChart" height="300"></canvas>
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="card card-reports shadow-sm bg-white p-4 h-100">
                        <h5 class="fw-bold mb-4">Hiring Success Funnel</h5>
                        <div class="position-relative" style="height: 250px;">
                            <canvas id="funnelChart"></canvas>
                        </div>
                        <div class="mt-4 pt-2">
                            <?php foreach ($status_stats as $ss): ?>
                                    <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                                        <span class="text-muted small fw-bold text-uppercase"><?php echo $ss['status']; ?></span>
                                        <span class="fw-bold text-primary"><?php echo $ss['count']; ?></span>
                                    </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Engagement Table -->
                <div class="col-12">
                    <div class="card card-reports shadow-sm bg-white overflow-hidden">
                        <div class="p-4 border-bottom bg-light">
                            <h5 class="fw-bold mb-0">Engagement & Performance by Position</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr class="small text-uppercase">
                                        <th class="ps-4 py-3">Target Role</th>
                                        <th class="text-center">Total Applicants</th>
                                        <th class="text-center">Listing Reach (Views)</th>
                                        <th class="text-end pe-4">Conversion Rate</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($job_stats)): ?>
                                            <tr><td colspan="4" class="text-center py-4">No job data found.</td></tr>
                                    <?php else: ?>
                                            <?php foreach ($job_stats as $job): ?>
                                                    <?php
                                                    $views = 0;
                                                    foreach ($view_stats as $vs)
                                                        if ($vs['title'] == $job['title'])
                                                            $views = $vs['views'];
                                                    $rate = $views > 0 ? round(($job['app_count'] / $views) * 100, 1) : 0;
                                                    ?>
                                                    <tr>
                                                        <td class="ps-4 fw-bold"><?php echo htmlspecialchars($job['title']); ?></td>
                                                        <td class="text-center fw-bold fs-5 text-primary"><?php echo $job['app_count']; ?></td>
                                                        <td class="text-center text-muted"><?php echo number_format($views); ?></td>
                                                        <td class="pe-4 text-end">
                                                            <div class="d-flex align-items-center justify-content-end gap-3">
                                                                <span class="fw-bold text-dark"><?php echo $rate; ?>%</span>
                                                                <div class="progress rounded-pill shadow-sm" style="width: 100px; height: 8px;">
                                                                    <div class="progress-bar bg-primary" style="width: <?php echo min($rate * 4, 100); ?>%"></div>
                                                                </div>
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
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Inflow Trend
    new Chart(document.getElementById('appTrendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($application_trends, 'date')); ?>,
            datasets: [{
                label: 'Inflow',
                data: <?php echo json_encode(array_column($application_trends, 'count')); ?>,
                borderColor: '#1a73e8',
                backgroundColor: 'rgba(26, 115, 232, 0.1)',
                fill: true,
                tension: 0.4,
                borderWidth: 3,
                pointRadius: 5,
                pointBackgroundColor: '#1a73e8'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Success Funnel
    new Chart(document.getElementById('funnelChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($status_stats, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($status_stats, 'count')); ?>,
                backgroundColor: ['#1a73e8', '#34a853', '#fbbc04', '#ea4335', '#70757a'],
                borderWidth: 0,
                spacing: 5
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '80%'
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
