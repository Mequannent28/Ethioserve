<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Must be logged in
if (!isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    redirectWithMessage('../login.php', 'warning', 'Please login to access the Employer Dashboard.');
}
$user_id = $_SESSION['id'] ?? $_SESSION['user_id'];

// ── Ensure company record exists ──────────────────────────────────
function ensureCompany($pdo, $user_id)
{
    $co = $pdo->prepare("SELECT id FROM job_companies WHERE user_id=?");
    $co->execute([$user_id]);
    $c = $co->fetch();
    if ($c)
        return $c['id'];
    $pdo->prepare("INSERT INTO job_companies (user_id, company_name) VALUES (?,?)")
        ->execute([$user_id, $_SESSION['full_name'] ?? 'My Company']);
    return $pdo->lastInsertId();
}

$company_id = null;
try {
    $company_id = ensureCompany($pdo, $user_id);
} catch (Exception $e) {
}

// ── Handle POST actions ───────────────────────────────────────────
$action_msg = '';
$action_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* 1. Update application status (accept / reject / shortlist / interview) */
    if (isset($_POST['update_status'])) {
        $app_id = intval($_POST['app_id']);
        $new_status = in_array(
            $_POST['new_status'],
            ['pending', 'shortlisted', 'interviewed', 'hired', 'rejected']
        )
            ? $_POST['new_status'] : 'pending';
        $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;
        $notes = sanitize($_POST['notes'] ?? '');

        // Verify application belongs to this employer's job
        $chk = $pdo->prepare("SELECT ja.id, ja.applicant_id, jl.title FROM job_applications ja
                               JOIN job_listings jl ON ja.job_id = jl.id
                               WHERE ja.id=? AND jl.posted_by=?");
        $chk->execute([$app_id, $user_id]);
        $app_row = $chk->fetch();

        if ($app_row) {
            $pdo->prepare("UPDATE job_applications SET status=?, interview_date=?, notes=?, updated_at=NOW() WHERE id=?")
                ->execute([$new_status, $interview_date, $notes, $app_id]);

            // Push notification to applicant
            $notif_msgs = [
                'shortlisted' => "🎉 You've been shortlisted for '{$app_row['title']}'!",
                'interviewed' => "📅 Interview scheduled for '{$app_row['title']}'" . ($interview_date ? " on " . date('M d, Y H:i', strtotime($interview_date)) : ''),
                'hired' => "🥳 Congratulations! You've been hired for '{$app_row['title']}'!",
                'rejected' => "Your application for '{$app_row['title']}' was not selected this time.",
                'pending' => "Your application for '{$app_row['title']}' status was updated.",
            ];
            try {
                $pdo->prepare("INSERT INTO job_notifications (user_id, title, message, link) VALUES (?,?,?,?)")
                    ->execute([
                        $app_row['applicant_id'],
                        ucfirst($new_status) . ' — ' . $app_row['title'],
                        $notif_msgs[$new_status],
                        'jobs.php?tab=my_apps'
                    ]);
            } catch (Exception $e) {
            }

            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => true, 'message' => "Applicant status updated to " . ucfirst($new_status)]);
                exit;
            }

            $action_msg = "Applicant status updated to <strong>" . ucfirst($new_status) . "</strong>.";
            $action_type = 'success';
        } else {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                echo json_encode(['success' => false, 'message' => "Unauthorized action."]);
                exit;
            }
            $action_msg = "Unauthorized action.";
            $action_type = 'danger';
        }
    }

    /* 2. Toggle job status (active / closed) */
    if (isset($_POST['toggle_job'])) {
        $job_id = intval($_POST['job_id']);
        $pdo->prepare("UPDATE job_listings SET status = IF(status='active','closed','active') WHERE id=? AND posted_by=?")
            ->execute([$job_id, $user_id]);

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            echo json_encode(['success' => true, 'message' => "Job status toggled."]);
            exit;
        }
        $action_msg = "Job status toggled.";
        $action_type = 'success';
    }

    /* 3. Delete job */
    if (isset($_POST['delete_job'])) {
        $job_id = intval($_POST['job_id']);
        $pdo->prepare("DELETE FROM job_listings WHERE id=? AND posted_by=?")->execute([$job_id, $user_id]);
        $action_msg = "Job deleted.";
        $action_type = 'warning';
    }

    /* 4. Update company profile */
    if (isset($_POST['update_company'])) {
        $pdo->prepare("UPDATE job_companies SET company_name=?, description=?, website=?, location=?, industry=?, size=? WHERE user_id=?")
            ->execute([
                sanitize($_POST['company_name'] ?? ''),
                sanitize($_POST['description'] ?? ''),
                sanitize($_POST['website'] ?? ''),
                sanitize($_POST['location'] ?? ''),
                sanitize($_POST['industry'] ?? ''),
                sanitize($_POST['size'] ?? ''),
                $user_id
            ]);
        $action_msg = "Company profile updated.";
        $action_type = 'success';
    }

    /* 5. Update Job Listing */
    if (isset($_POST['update_job'])) {
        $job_id = intval($_POST['job_id']);
        try {
            $pdo->prepare("UPDATE job_listings SET title=?, description=?, requirements=?, job_type=?, category_id=?, location=?, salary_min=?, salary_max=?, salary_period=?, skills_required=?, experience_level=?, education_level=?, deadline=?, status=? WHERE id=? AND posted_by=?")
                ->execute([
                    sanitize($_POST['title']),
                    sanitize($_POST['description']),
                    sanitize($_POST['requirements'] ?? ''),
                    $_POST['job_type'],
                    intval($_POST['category_id'] ?? 0) ?: null,
                    sanitize($_POST['location'] ?? ''),
                    floatval($_POST['salary_min'] ?? 0),
                    floatval($_POST['salary_max'] ?? 0),
                    $_POST['salary_period'] ?? 'month',
                    sanitize($_POST['skills_required'] ?? ''),
                    $_POST['experience_level'] ?? 'any',
                    sanitize($_POST['education_level'] ?? ''),
                    $_POST['deadline'] ?: null,
                    $_POST['job_status'] ?? 'active',
                    $job_id,
                    $user_id
                ]);
            $action_msg = "Job '{$_POST['title']}' updated successfully.";
            $action_type = 'success';
        } catch (Exception $e) {
            $action_msg = "Failed to update job: " . $e->getMessage();
            $action_type = 'danger';
        }
    }
}

// ── Active view tab ────────────────────────────────────────────────
$view = $_GET['view'] ?? 'overview';
$job_focus = intval($_GET['job_id'] ?? 0);  // show applications for a specific job

// ── Fetch specific job for editing ─────────────────────────────────
$edit_job = null;
if ($view === 'edit_job' && $job_focus) {
    try {
        $s = $pdo->prepare("SELECT * FROM job_listings WHERE id=? AND posted_by=?");
        $s->execute([$job_focus, $user_id]);
        $edit_job = $s->fetch();
        if (!$edit_job)
            $view = 'jobs';
    } catch (Exception $e) {
        $view = 'jobs';
    }
}

// ── Fetch company ──────────────────────────────────────────────────
$company = [];
try {
    $s = $pdo->prepare("SELECT * FROM job_companies WHERE user_id=?");
    $s->execute([$user_id]);
    $company = $s->fetch() ?: [];
    if ($company)
        $company_id = $company['id'];
} catch (Exception $e) {
}

// ── Fetch my jobs ──────────────────────────────────────────────────
$my_jobs = [];
try {
    $s = $pdo->prepare("SELECT jl.*, jcat.name as cat_name, jcat.icon as cat_icon, jcat.color as cat_color,
                                (SELECT COUNT(*) FROM job_applications WHERE job_id=jl.id) as total_apps,
                                (SELECT COUNT(*) FROM job_applications WHERE job_id=jl.id AND status='pending') as new_apps,
                                (SELECT COUNT(*) FROM job_applications WHERE job_id=jl.id AND status='hired') as hired_count
                         FROM job_listings jl
                         LEFT JOIN job_categories jcat ON jl.category_id = jcat.id
                         WHERE jl.posted_by=?
                         ORDER BY jl.created_at DESC");
    $s->execute([$user_id]);
    $my_jobs = $s->fetchAll();
} catch (Exception $e) {
}

// ── Fetch applications (for focus job or all) ──────────────────────
$applications = [];
try {
    $where_job = $job_focus ? "AND ja.job_id = $job_focus" : '';
    $s = $pdo->prepare("SELECT ja.*, jl.title as job_title, jl.job_type, jl.salary_min, jl.salary_max,
                                u.full_name, u.email, u.phone,
                                jp.headline, jp.skills, jp.experience_years, jp.portfolio_url, jp.cv_url, jp.profile_pic, jp.location as app_location
                         FROM job_applications ja
                         JOIN job_listings jl ON ja.job_id = jl.id
                         JOIN users u ON ja.applicant_id = u.id
                         LEFT JOIN job_profiles jp ON jp.user_id = ja.applicant_id
                         WHERE jl.posted_by = ? $where_job
                         ORDER BY ja.applied_at DESC");
    $s->execute([$user_id]);
    $applications = $s->fetchAll();
} catch (Exception $e) {
}

// ── Fetch Recent Job Messages ─────────────────────────────────────
$recent_job_messages = [];
try {
    $stmt = $pdo->prepare("SELECT m.*, u.full_name as other_name, jl.title as job_title, ja.id as application_id
                           FROM (
                               SELECT MAX(id) as id, application_id 
                               FROM job_messages 
                               WHERE receiver_id = ? 
                               GROUP BY application_id
                           ) latest_msgs 
                           JOIN job_messages m ON latest_msgs.id = m.id 
                           JOIN job_applications ja ON m.application_id = ja.id
                           JOIN job_listings jl ON ja.job_id = jl.id
                           JOIN users u ON m.sender_id = u.id 
                           ORDER BY m.created_at DESC 
                           LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_job_messages = $stmt->fetchAll();
} catch (Exception $e) {
}

// ── Stats ─────────────────────────────────────────────────────────
$stats = [
    'total_jobs' => count($my_jobs),
    'active_jobs' => count(array_filter($my_jobs, fn($j) => $j['status'] === 'active')),
    'total_apps' => array_sum(array_column($my_jobs, 'total_apps')),
    'new_apps' => array_sum(array_column($my_jobs, 'new_apps')),
    'hired' => array_sum(array_column($my_jobs, 'hired_count')),
    'total_views' => array_sum(array_column($my_jobs, 'views')),
];

// Group applications by status for the pipeline view
$pipeline = ['pending' => [], 'shortlisted' => [], 'interviewed' => [], 'hired' => [], 'rejected' => []];
foreach ($applications as $a) {
    $pipeline[$a['status'] ?? 'pending'][] = $a;
}

include '../includes/header.php';
?>

<!-- ═══════════════════ TOP HEADER BAR ════════════════════════════ -->
<div
    style="background:linear-gradient(135deg,#0d47a1,#1565C0,#1976d2);min-height:140px;position:relative;overflow:hidden;">
    <div
        style="position:absolute;top:-40px;right:-40px;width:260px;height:260px;border-radius:50%;background:rgba(255,255,255,0.05);">
    </div>
    <div
        style="position:absolute;bottom:-60px;left:-30px;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,0.04);">
    </div>
    <div class="container py-4 position-relative">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="rounded-4 d-flex align-items-center justify-content-center fw-bold text-white shadow"
                    style="width:56px;height:56px;background:rgba(255,255,255,0.15);font-size:1.4rem;letter-spacing:-1px;">
                    <?php echo strtoupper(substr($company['company_name'] ?? ($_SESSION['full_name'] ?? 'E'), 0, 1)); ?>
                </div>
                <div>
                    <div class="text-white fw-bold" style="font-size:1.2rem;">
                        <?php echo htmlspecialchars($company['company_name'] ?? ($_SESSION['full_name'] ?? 'My Company')); ?>
                    </div>
                    <?php if (!empty($company['industry'])): ?>
                        <div class="text-white-50 small">
                            <?php echo htmlspecialchars($company['industry']); ?>
                            <?php echo !empty($company['location']) ? '· ' . $company['location'] : ''; ?>
                        </div>
                    <?php endif; ?>
                    <span class="badge rounded-pill mt-1"
                        style="background:rgba(255,255,255,0.15);color:#fff;font-size:0.7rem;"><i
                            class="fas fa-building me-1"></i>Employer Dashboard</span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <a href="jobs.php?tab=post" class="btn btn-sm rounded-pill fw-bold px-3"
                    style="background:#FFD600;color:#0d47a1;"><i class="fas fa-plus me-1"></i>Post New Job</a>
                <a href="jobs.php" class="btn btn-sm rounded-pill border-0 px-3"
                    style="background:rgba(255,255,255,0.15);color:#fff;"><i class="fas fa-briefcase me-1"></i>View
                    Jobs</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">

    <?php if ($action_msg): ?>
        <div class="alert alert-<?php echo $action_type; ?> alert-dismissible rounded-4 border-0 shadow-sm mb-4"
            role="alert">
            <i class="fas fa-<?php echo $action_type === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
            <?php echo $action_msg; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── STAT CHIPS ──────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <?php $srows = [
            ['fas fa-briefcase', '#1565C0', '#e3f2fd', $stats['total_jobs'], 'Total Jobs'],
            ['fas fa-play-circle', '#2e7d32', '#e8f5e9', $stats['active_jobs'], 'Active'],
            ['fas fa-users', '#AD1457', '#fce4ec', $stats['total_apps'], 'Total Applicants'],
            ['fas fa-eye', '#6A1B9A', '#f3e5f5', $stats['total_views'], 'Total Views'],
            ['fas fa-handshake', '#00695C', '#e0f2f1', $stats['hired'], 'Hired'],
        ]; ?>
        <?php foreach ($srows as $sr): ?>
            <div class="col-6 col-md-4 col-lg">
                <div class="card border-0 shadow-sm rounded-4 p-3 h-100">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center flex-shrink-0"
                            style="width:44px;height:44px;background:<?php echo $sr[2]; ?>;">
                            <i class="<?php echo $sr[0]; ?>" style="color:<?php echo $sr[1]; ?>;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:1.4rem;color:<?php echo $sr[1]; ?>;">
                                <?php echo $sr[3]; ?>
                            </div>
                            <div class="text-muted" style="font-size:0.78rem;">
                                <?php echo $sr[4]; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- ── SIDE NAV + CONTENT ─────────────────────────────────── -->
    <div class="row g-4">
        <!-- Sidebar -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                <?php $navItems = [
                    ['overview', 'fas fa-chart-pie', 'Overview'],
                    ['jobs', 'fas fa-briefcase', 'My Job Posts'],
                    ['applications', 'fas fa-users', 'All Applications'],
                    ['pipeline', 'fas fa-columns', 'Hiring Pipeline'],
                    ['company', 'fas fa-building', 'Company Profile'],
                ]; ?>
                <?php foreach ($navItems as [$vk, $icon, $label]): ?>
                    <a href="?view=<?php echo $vk; ?>"
                        class="d-flex align-items-center gap-3 px-4 py-3 text-decoration-none border-bottom"
                        style="background:<?php echo $view === $vk ? '#e3f2fd' : '#fff'; ?>;color:<?php echo $view === $vk ? '#1565C0' : '#555'; ?>;font-weight:<?php echo $view === $vk ? '700' : '400'; ?>;">
                        <i class="<?php echo $icon; ?> fa-fw"></i>
                        <span>
                            <?php echo $label; ?>
                        </span>
                        <?php if ($vk === 'applications' && $stats['new_apps'] > 0): ?>
                            <span class="badge rounded-pill ms-auto" style="background:#E65100;color:#fff;">
                                <?php echo $stats['new_apps']; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Quick Links -->
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <h6 class="fw-bold small text-muted mb-2 text-uppercase">Quick Actions</h6>
                <a href="jobs.php?tab=post" class="btn btn-primary w-100 rounded-pill py-2 mb-2 fw-semibold"
                    style="font-size:0.85rem;"><i class="fas fa-plus me-2"></i>Post New Job</a>
                <a href="jobs.php" class="btn btn-outline-primary w-100 rounded-pill py-2 fw-semibold"
                    style="font-size:0.85rem;"><i class="fas fa-eye me-2"></i>View Job Board</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-lg-9">

            <!-- ══════════ OVERVIEW ══════════ -->
            <?php if ($view === 'overview'): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h5 class="fw-bold mb-4"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard Overview</h5>
                    <?php if (empty($my_jobs)): ?>
                        <div class="text-center py-5">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                                style="width:80px;height:80px;">
                                <i class="fas fa-briefcase text-primary fs-2"></i>
                            </div>
                            <h5 class="fw-bold">No Jobs Posted Yet</h5>
                            <p class="text-muted">Start posting jobs and find the right candidates.</p>
                            <a href="jobs.php?tab=post" class="btn btn-primary rounded-pill px-4">Post Your First Job</a>
                        </div>
                    <?php else: ?>
                        <div class="row g-3">
                            <?php foreach (array_slice($my_jobs, 0, 4) as $j): ?>
                                <div class="col-md-6">
                                    <div class="card border-0 rounded-4 p-3" style="background:#f8fafc;">
                                        <div class="d-flex align-items-start gap-3">
                                            <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold text-white flex-shrink-0"
                                                style="width:44px;height:44px;background:<?php echo $j['cat_color'] ?: '#1565C0'; ?>;">
                                                <i class="<?php echo $j['cat_icon'] ?: 'fas fa-briefcase'; ?>"
                                                    style="font-size:1rem;"></i>
                                            </div>
                                            <div class="flex-grow-1 min-w-0">
                                                <div class="fw-bold text-truncate" style="font-size:0.9rem;">
                                                    <?php echo htmlspecialchars($j['title']); ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.75rem;">
                                                    <?php echo ucwords(str_replace('_', ' ', $j['job_type'])); ?>
                                                </div>
                                                <div class="d-flex gap-2 mt-1">
                                                    <span class="badge rounded-pill"
                                                        style="background:#e3f2fd;color:#1565c0;font-size:0.65rem;">
                                                        <?php echo $j['total_apps']; ?> applicants
                                                    </span>
                                                    <?php if ($j['new_apps'] > 0): ?>
                                                        <span class="badge rounded-pill"
                                                            style="background:#fff3e0;color:#e65100;font-size:0.65rem;">
                                                            <?php echo $j['new_apps']; ?> new
                                                        </span>
                                                    <?php endif; ?>
                                                    <span
                                                        class="badge rounded-pill <?php echo $j['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"
                                                        style="font-size:0.65rem;">
                                                        <?php echo ucfirst($j['status']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <a href="?view=applications&job_id=<?php echo $j['id']; ?>"
                                                class="btn btn-sm btn-outline-primary rounded-pill px-3 flex-shrink-0"
                                                style="font-size:0.75rem;">Review</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3 text-center">
                            <a href="?view=jobs" class="btn btn-outline-primary rounded-pill px-4 btn-sm">View All Jobs →</a>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Recent Conversations Sidebar -->
                <div class="card border-0 shadow-sm rounded-4 mb-4">
                    <div class="card-header bg-white border-0 p-4 border-bottom">
                        <h6 class="fw-bold mb-0 text-primary"><i class="fas fa-comments me-2"></i>Recent Messages</h6>
                    </div>
                    <div class="card-body p-0">
                        <?php if (empty($recent_job_messages)): ?>
                            <div class="text-center py-4">
                                <p class="text-muted small mb-0">No recent messages.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($recent_job_messages as $msg): ?>
                                    <a href="job_chat.php?application_id=<?php echo $msg['application_id']; ?>"
                                        class="list-group-item list-group-item-action p-3 border-0 border-bottom">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <span
                                                class="fw-bold small text-dark"><?php echo htmlspecialchars($msg['other_name']); ?></span>
                                            <div class="d-flex align-items-center gap-2">
                                                <?php if ($msg['is_read'] == 0): ?>
                                                    <span class="badge rounded-circle p-1 bg-danger"></span>
                                                <?php endif; ?>
                                                <small class="text-muted"
                                                    style="font-size: 0.65rem;"><?php echo date('H:i', strtotime($msg['created_at'])); ?></small>
                                            </div>
                                        </div>
                                        <p
                                            class="text-muted small mb-1 text-truncate <?php echo $msg['is_read'] == 0 ? 'fw-bold text-dark' : ''; ?>">
                                            <?php echo htmlspecialchars($msg['message']); ?>
                                        </p>
                                        <div class="small text-primary-emphasis fw-medium" style="font-size: 0.65rem;">
                                            #<?php echo htmlspecialchars($msg['job_title']); ?>
                                        </div>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent applications -->
                <?php if (!empty($applications)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold mb-3"><i class="fas fa-users me-2 text-primary"></i>Recent Applications</h6>
                        <?php foreach (array_slice($applications, 0, 5) as $app): ?>
                            <?php
                            $sc = [
                                'pending' => ['#fff8e1', '#f57f17', 'fas fa-clock'],
                                'shortlisted' => ['#e3f2fd', '#1565c0', 'fas fa-thumbs-up'],
                                'interviewed' => ['#e8f5e9', '#2e7d32', 'fas fa-user-check'],
                                'hired' => ['#e8f5e9', '#1b5e20', 'fas fa-handshake'],
                                'rejected' => ['#fce4ec', '#b71c1c', 'fas fa-times-circle']
                            ];
                            $s = $sc[$app['status']] ?? $sc['pending'];
                            ?>
                            <div class="d-flex align-items-center gap-3 p-3 rounded-3 mb-2" style="background:#f8fafc;">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                    style="width:38px;height:38px;font-size:0.9rem;">
                                    <?php echo strtoupper(substr($app['full_name'], 0, 1)); ?>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="fw-semibold text-truncate" style="font-size:0.9rem;">
                                        <?php echo htmlspecialchars($app['full_name']); ?>
                                    </div>
                                    <div class="text-muted" style="font-size:0.75rem;">
                                        <?php echo htmlspecialchars($app['job_title']); ?> ·
                                        <?php echo date('M d', strtotime($app['applied_at'])); ?>
                                    </div>
                                </div>
                                <span class="badge rounded-pill px-2 py-1 flex-shrink-0"
                                    style="background:<?php echo $s[0]; ?>;color:<?php echo $s[1]; ?>;font-size:0.7rem;">
                                    <i class="<?php echo $s[2]; ?> me-1"></i>
                                    <?php echo ucfirst($app['status']); ?>
                                </span>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <a href="?view=applications&job_id=<?php echo $app['job_id']; ?>#app-<?php echo $app['id']; ?>"
                                        class="btn btn-sm btn-outline-secondary rounded-pill px-2"
                                        style="font-size:0.72rem;">Review</a>
                                    <a href="job_chat.php?application_id=<?php echo $app['id']; ?>"
                                        class="btn btn-sm btn-outline-primary rounded-pill px-2" style="font-size:0.72rem;"><i
                                            class="fas fa-comments"></i></a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div class="text-center mt-2"><a href="?view=applications" class="small text-primary">View All →</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- ══════════ MY JOBS ══════════ -->
            <?php elseif ($view === 'jobs'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>My Job Posts</h5>
                    <a href="jobs.php?tab=post" class="btn btn-primary rounded-pill px-4 btn-sm fw-bold"><i
                            class="fas fa-plus me-1"></i>Post New Job</a>
                </div>
                <?php if (empty($my_jobs)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto"
                            style="width:80px;height:80px;"><i class="fas fa-briefcase text-primary fs-2"></i></div>
                        <h5 class="fw-bold">No Jobs Posted</h5>
                        <a href="jobs.php?tab=post" class="btn btn-primary rounded-pill px-4 mt-2">Post a Job</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($my_jobs as $j): ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3 overflow-hidden">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3 align-items-start">
                                    <div class="rounded-3 d-flex align-items-center justify-content-center text-white fw-bold flex-shrink-0"
                                        style="width:50px;height:50px;background:<?php echo $j['cat_color'] ?? '#1565C0'; ?>;">
                                        <i class="<?php echo $j['cat_icon'] ?? 'fas fa-briefcase'; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                                            <div>
                                                <h6 class="fw-bold mb-1">
                                                    <?php echo htmlspecialchars($j['title']); ?>
                                                </h6>
                                                <div class="d-flex flex-wrap gap-2">
                                                    <?php $tc = ['full_time' => ['#e8f5e9', '#2e7d32'], 'part_time' => ['#fff8e1', '#f57f17'], 'contract' => ['#e3f2fd', '#1565c0'], 'internship' => ['#f3e5f5', '#6a1b9a'], 'daily_labor' => ['#fce4ec', '#b71c1c'], 'freelance' => ['#e0f2f1', '#004d40']];
                                                    $t = $tc[$j['job_type']] ?? ['#f5f5f5', '#333']; ?>
                                                    <span class="badge rounded-pill"
                                                        style="background:<?php echo $t[0]; ?>;color:<?php echo $t[1]; ?>;font-size:0.7rem;">
                                                        <?php echo ucwords(str_replace('_', ' ', $j['job_type'])); ?>
                                                    </span>
                                                    <span
                                                        class="badge rounded-pill <?php echo $j['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>"
                                                        style="font-size:0.7rem;">
                                                        <?php echo ucfirst($j['status']); ?>
                                                    </span>
                                                    <?php if ($j['location']): ?><span class="text-muted small"><i
                                                                class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                            <?php echo htmlspecialchars($j['location']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="text-muted small"><i class="fas fa-calendar me-1"></i>
                                                        <?php echo date('M d, Y', strtotime($j['created_at'])); ?>
                                                    </span>
                                                    <?php if ($j['deadline']): ?><span class="text-muted small"><i
                                                                class="fas fa-clock me-1"></i>Deadline:
                                                            <?php echo date('M d', strtotime($j['deadline'])); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end">
                                                <div class="fw-bold text-primary" style="font-size:1.1rem;">
                                                    <?php echo $j['total_apps']; ?>
                                                </div>
                                                <div class="text-muted" style="font-size:0.72rem;">Applicants</div>
                                                <?php if ($j['new_apps'] > 0): ?>
                                                    <span class="badge rounded-pill"
                                                        style="background:#E65100;color:#fff;font-size:0.65rem;">
                                                        <?php echo $j['new_apps']; ?> new
                                                    </span>
                                                <?php endif; ?>
                                                <div class="text-muted mt-1" style="font-size:0.65rem;"><i
                                                        class="fas fa-eye me-1"></i><?php echo $j['views']; ?> views</div>
                                            </div>
                                        </div>
                                        <!-- Action Buttons -->
                                        <div class="d-flex gap-2 mt-3 flex-wrap">
                                            <a href="?view=applications&job_id=<?php echo $j['id']; ?>"
                                                class="btn btn-primary rounded-pill px-3 py-1 fw-semibold"
                                                style="font-size:0.8rem;">
                                                <i class="fas fa-users me-1"></i>Applicants (
                                                <?php echo $j['total_apps']; ?>)
                                            </a>
                                            <a href="?view=edit_job&job_id=<?php echo $j['id']; ?>"
                                                class="btn btn-outline-primary rounded-pill px-3 py-1 fw-semibold"
                                                style="font-size:0.8rem;">
                                                <i class="fas fa-edit me-1"></i>Edit
                                            </a>
                                            <!-- Toggle Active/Closed -->
                                            <form method="POST" class="d-inline ">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="job_id" value="<?php echo $j['id']; ?>">
                                                <button type="submit" name="toggle_job" value="1"
                                                    class="btn btn-sm rounded-pill px-3 py-1"
                                                    style="background:<?php echo $j['status'] === 'active' ? '#fff3e0' : '#e8f5e9'; ?>;color:<?php echo $j['status'] === 'active' ? '#e65100' : '#2e7d32'; ?>;font-size:0.8rem;">
                                                    <i
                                                        class="fas fa-<?php echo $j['status'] === 'active' ? 'pause' : 'play'; ?> me-1"></i>
                                                    <?php echo $j['status'] === 'active' ? 'Close Job' : 'Reopen Job'; ?>
                                                </button>
                                            </form>
                                            <!-- Delete -->
                                            <form method="POST" class="d-inline"
                                                onsubmit="return confirm('Delete this job? All applications will also be removed.');">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="job_id" value="<?php echo $j['id']; ?>">
                                                <button type="submit" name="delete_job" value="1"
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1"
                                                    style="font-size:0.8rem;">
                                                    <i class="fas fa-trash me-1"></i>Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- ══════════ ALL APPLICATIONS ══════════ -->
            <?php elseif ($view === 'applications'): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h5 class="fw-bold mb-0">
                        <i class="fas fa-users me-2 text-primary"></i>
                        <?php echo $job_focus ? 'Applicants for: ' . htmlspecialchars($applications[0]['job_title'] ?? 'Job') : 'All Applications'; ?>
                    </h5>
                    <div class="d-flex gap-2">
                        <?php if ($job_focus): ?>
                            <a href="?view=applications" class="btn btn-sm btn-outline-secondary rounded-pill px-3"><i
                                    class="fas fa-arrow-left me-1"></i>All Jobs</a>
                        <?php endif; ?>
                        <!-- Filter by status -->
                        <?php foreach (['pending', 'shortlisted', 'interviewed', 'hired', 'rejected'] as $fs): ?>
                            <a href="?view=applications<?php echo $job_focus ? "&job_id=$job_focus" : ""; ?>&filter_status=
                        <?php echo $fs; ?>" class="badge rounded-pill px-3 py-2 text-decoration-none" style="font-size:0.75rem;background:
                        <?php echo ($_GET['filter_status'] ?? '') === $fs ? '#1565C0' : '#e3f2fd'; ?>;color:
                        <?php echo ($_GET['filter_status'] ?? '') === $fs ? '#fff' : '#1565C0'; ?>">
                                <?php echo ucfirst($fs); ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php
                // Apply status filter if set
                $disp_apps = $applications;
                if (!empty($_GET['filter_status'])) {
                    $disp_apps = array_filter($applications, fn($a) => $a['status'] === $_GET['filter_status']);
                }
                ?>
                <?php if (empty($disp_apps)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto"
                            style="width:80px;height:80px;"><i class="fas fa-inbox text-primary fs-2"></i></div>
                        <h5 class="fw-bold">No Applications Yet</h5>
                        <p class="text-muted">Applications will appear here once candidates apply to your jobs.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($disp_apps as $app):
                        $sc = [
                            'pending' => ['#fff8e1', '#f57f17', 'fas fa-clock'],
                            'shortlisted' => ['#e3f2fd', '#1565c0', 'fas fa-thumbs-up'],
                            'interviewed' => ['#e8f5e9', '#2e7d32', 'fas fa-user-check'],
                            'hired' => ['#e8f5e9', '#1b5e20', 'fas fa-handshake'],
                            'rejected' => ['#fce4ec', '#b71c1c', 'fas fa-times-circle']
                        ];
                        $s = $sc[$app['status']] ?? $sc['pending'];
                        ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3" id="app-<?php echo $app['id']; ?>">
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <!-- Applicant Info -->
                                    <div class="col-md-5">
                                        <div class="d-flex gap-3 align-items-start">
                                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                                style="width:48px;height:48px;font-size:1.1rem;">
                                                <?php echo strtoupper(substr($app['full_name'], 0, 1)); ?>
                                            </div>
                                            <div>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($app['full_name']); ?>
                                                </div>
                                                <?php if ($app['headline']): ?>
                                                    <div class="text-muted small">
                                                        <?php echo htmlspecialchars(substr($app['headline'], 0, 50)); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex flex-wrap gap-1 mt-1">
                                                    <span class="text-muted small"><i class="fas fa-envelope me-1"></i>
                                                        <?php echo htmlspecialchars($app['email']); ?>
                                                    </span>
                                                </div>
                                                <?php if ($app['phone']): ?>
                                                    <div class="text-muted small"><i class="fas fa-phone me-1"></i>
                                                        <?php echo htmlspecialchars($app['phone']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($app['app_location']): ?>
                                                    <div class="text-muted small"><i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                        <?php echo htmlspecialchars($app['app_location']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (!empty($app['university'])): ?>
                                                    <div class="text-primary small fw-bold mt-1"><i
                                                            class="fas fa-graduation-cap me-1"></i>
                                                        <?php echo htmlspecialchars($app['university']); ?>
                                                        <?php if ($app['gpa'] > 0): ?> (GPA:
                                                            <?php echo number_format($app['gpa'], 2); ?>)<?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <!-- Skills -->
                                                <?php if ($app['skills']): ?>
                                                    <div class="d-flex flex-wrap gap-1 mt-1">
                                                        <?php foreach (array_slice(explode(',', $app['skills']), 0, 4) as $sk): ?>
                                                            <span class="badge rounded-pill border"
                                                                style="background:#f5f5f5;color:#555;border-color:#ddd!important;font-size:0.65rem;">
                                                                <?php echo htmlspecialchars(trim($sk)); ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="d-flex gap-2 mt-2 flex-wrap">
                                                    <?php if ($app['cv_url']): ?>
                                                        <a href="../<?php echo htmlspecialchars(ltrim($app['cv_url'], './')); ?>"
                                                            target="_blank"
                                                            class="btn btn-sm btn-outline-primary rounded-pill px-2 py-1"
                                                            style="font-size:0.72rem;"><i class="fas fa-file-pdf me-1"></i>CV</a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['recommendation_url'])): ?>
                                                        <a href="../<?php echo htmlspecialchars(ltrim($app['recommendation_url'], './')); ?>"
                                                            target="_blank" class="btn btn-sm btn-outline-info rounded-pill px-2 py-1"
                                                            style="font-size:0.72rem;"><i
                                                                class="fas fa-award me-1"></i>Recommendation</a>
                                                    <?php endif; ?>
                                                    <?php if (!empty($app['certificates_url'])): ?>
                                                        <a href="../<?php echo htmlspecialchars(ltrim($app['certificates_url'], './')); ?>"
                                                            target="_blank"
                                                            class="btn btn-sm btn-outline-success rounded-pill px-2 py-1"
                                                            style="font-size:0.72rem;"><i
                                                                class="fas fa-certificate me-1"></i>Certificates</a>
                                                    <?php endif; ?>
                                                    <?php if ($app['portfolio_url']): ?>
                                                        <a href="<?php echo htmlspecialchars($app['portfolio_url']); ?>" target="_blank"
                                                            class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-1"
                                                            style="font-size:0.72rem;"><i class="fas fa-globe me-1"></i>Portfolio</a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Job & Cover Letter -->
                                    <div class="col-md-4">
                                        <div class="text-muted small mb-1"><i
                                                class="fas fa-briefcase me-1 text-primary"></i><strong>
                                                <?php echo htmlspecialchars($app['job_title']); ?>
                                            </strong></div>
                                        <div class="text-muted small mb-2"><i class="fas fa-calendar me-1"></i>Applied
                                            <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                        </div>
                                        <?php if ($app['experience_years']): ?>
                                            <div class="text-muted small mb-1"><i class="fas fa-star me-1 text-warning"></i>
                                                <?php echo $app['experience_years']; ?> yrs experience
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($app['cover_letter']): ?>
                                            <div class="mt-2 p-2 rounded-3"
                                                style="background:#f8fafc;font-size:0.78rem;color:#555;line-height:1.5;max-height:80px;overflow-y:auto;">
                                                <em>"
                                                    <?php echo htmlspecialchars(substr($app['cover_letter'], 0, 200)); ?>
                                                    <?php echo strlen($app['cover_letter']) > 200 ? '…' : ''; ?>"
                                                </em>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-muted small fst-italic">No cover letter provided.</div>
                                        <?php endif; ?>
                                        <?php if ($app['interview_date']): ?>
                                            <div class="mt-2 rounded-3 p-2" style="background:#e8f5e9;">
                                                <div class="fw-bold text-success small"><i
                                                        class="fas fa-calendar-check me-1"></i>Interview Scheduled</div>
                                                <div class="small text-muted">
                                                    <?php echo date('M d, Y H:i', strtotime($app['interview_date'])); ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                        <?php if ($app['notes']): ?>
                                            <div class="mt-2 small text-muted fst-italic"><i class="fas fa-sticky-note me-1"></i>
                                                <?php echo htmlspecialchars(substr($app['notes'], 0, 100)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Status & Actions -->
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="text-muted small mb-1">Current Status</div>
                                            <span class="badge rounded-pill px-3 py-2 fw-semibold"
                                                style="background:<?php echo $s[0]; ?>;color:<?php echo $s[1]; ?>;font-size:0.82rem;">
                                                <i class="<?php echo $s[2]; ?> me-1"></i>
                                                <?php echo ucfirst($app['status']); ?>
                                            </span>
                                        </div>

                                        <!-- Update Status Form -->
                                        <form method="POST" class="update-status-form">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                            <input type="hidden" name="update_status" value="1">

                                            <div class="mb-2">
                                                <label class="form-label text-muted" style="font-size:0.72rem;">Change
                                                    Status</label>
                                                <select name="new_status" class="form-select rounded-3 border-0 bg-light"
                                                    style="font-size:0.82rem;">
                                                    <?php foreach (['pending', 'shortlisted', 'interviewed', 'hired', 'rejected'] as $st): ?>
                                                        <option value="<?php echo $st; ?>" <?php echo $app['status'] === $st ? 'selected' : ''; ?>>
                                                            <?php echo ucfirst($st); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>

                                            <div class="mb-2 interview-date-row"
                                                style="display:<?php echo $app['status'] === 'interviewed' ? 'block' : 'none'; ?>;">
                                                <label class="form-label text-muted" style="font-size:0.72rem;">Interview
                                                    Date/Time</label>
                                                <input type="datetime-local" name="interview_date"
                                                    class="form-control rounded-3 border-0 bg-light" style="font-size:0.82rem;"
                                                    value="<?php echo $app['interview_date'] ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : ''; ?>">
                                            </div>

                                            <div class="mb-2">
                                                <textarea name="notes" class="form-control border-0 bg-light rounded-3" rows="2"
                                                    placeholder="Internal notes..."
                                                    style="font-size:0.78rem;"><?php echo htmlspecialchars($app['notes'] ?? ''); ?></textarea>
                                            </div>

                                            <!-- Quick Action Buttons -->
                                            <div class="d-flex gap-1 flex-wrap mb-2">
                                                <button type="submit" name="new_status" value="shortlisted"
                                                    class="btn btn-sm rounded-pill px-2 py-1 fw-semibold"
                                                    style="background:#e3f2fd;color:#1565c0;font-size:0.72rem;border:0;">
                                                    <i class="fas fa-thumbs-up me-1"></i>Shortlist
                                                </button>
                                                <button type="submit" name="new_status" value="hired"
                                                    class="btn btn-sm rounded-pill px-2 py-1 fw-semibold"
                                                    style="background:#e8f5e9;color:#1b5e20;font-size:0.72rem;border:0;"
                                                    onclick="this.form.querySelector('[name=new_status]').value='hired'">
                                                    <i class="fas fa-handshake me-1"></i>Hire
                                                </button>
                                                <button type="submit" name="new_status" value="rejected"
                                                    class="btn btn-sm rounded-pill px-2 py-1 fw-semibold"
                                                    style="background:#fce4ec;color:#b71c1c;font-size:0.72rem;border:0;"
                                                    onclick="return confirm('Reject this applicant?')">
                                                    <i class="fas fa-times me-1"></i>Reject
                                                </button>
                                            </div>
                                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-1 fw-semibold"
                                                style="font-size:0.78rem;">
                                                <i class="fas fa-save me-1"></i>Save Changes
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>

                <!-- ══════════ HIRING PIPELINE ══════════ -->
            <?php elseif ($view === 'pipeline'): ?>
                <h5 class="fw-bold mb-4"><i class="fas fa-columns me-2 text-primary"></i>Hiring Pipeline</h5>
                <div class="row g-3">
                    <?php
                    $stages = [
                        'pending' => ['#fff8e1', '#f57f17', 'fas fa-inbox', 'Pending'],
                        'shortlisted' => ['#e3f2fd', '#1565c0', 'fas fa-thumbs-up', 'Shortlisted'],
                        'interviewed' => ['#f3e5f5', '#6a1b9a', 'fas fa-comments', 'Interviewed'],
                        'hired' => ['#e8f5e9', '#1b5e20', 'fas fa-handshake', 'Hired'],
                        'rejected' => ['#fce4ec', '#b71c1c', 'fas fa-times', 'Rejected'],
                    ];
                    foreach ($stages as $stage => [$bg, $col, $icon, $label]):
                        ?>
                        <div class="col-md-6 col-lg">
                            <div class="card border-0 shadow-sm rounded-4 h-100">
                                <div class="card-header border-0 rounded-top-4 p-3" style="background:<?php echo $bg; ?>;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="<?php echo $icon; ?>" style="color:<?php echo $col; ?>;"></i>
                                        <span class="fw-bold" style="color:<?php echo $col; ?>;font-size:0.88rem;">
                                            <?php echo $label; ?>
                                        </span>
                                        <span class="badge rounded-pill ms-auto"
                                            style="background:<?php echo $col; ?>;color:#fff;font-size:0.7rem;">
                                            <?php echo count($pipeline[$stage]); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-2" style="max-height:400px;overflow-y:auto;">
                                    <?php if (empty($pipeline[$stage])): ?>
                                        <div class="text-center text-muted small py-4">No applicants</div>
                                    <?php else: ?>
                                        <?php foreach ($pipeline[$stage] as $app): ?>
                                            <div class="card border-0 rounded-3 mb-2 p-2" style="background:#fafafa;">
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                                        style="width:32px;height:32px;font-size:0.8rem;">
                                                        <?php echo strtoupper(substr($app['full_name'], 0, 1)); ?>
                                                    </div>
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="fw-semibold text-truncate" style="font-size:0.82rem;">
                                                            <?php echo htmlspecialchars($app['full_name']); ?>
                                                        </div>
                                                        <div class="text-muted text-truncate" style="font-size:0.7rem;">
                                                            <?php echo htmlspecialchars($app['job_title']); ?>
                                                        </div>
                                                        <?php if ($app['interview_date'] && $stage === 'interviewed'): ?>
                                                            <div class="text-success" style="font-size:0.68rem;"><i
                                                                    class="fas fa-calendar me-1"></i>
                                                                <?php echo date('M d, H:i', strtotime($app['interview_date'])); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="d-flex gap-1 mt-2">
                                                    <?php if ($stage !== 'hired' && $stage !== 'rejected'): ?>
                                                        <form method="POST" class="d-inline update-status-form">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="app_id" value="<?php echo $app['id']; ?>">
                                                            <input type="hidden" name="update_status" value="1">
                                                            <input type="hidden" name="interview_date" value="">
                                                            <input type="hidden" name="notes"
                                                                value="<?php echo htmlspecialchars($app['notes'] ?? ''); ?>">
                                                            <?php if ($stage === 'pending'): ?>
                                                                <button name="new_status" value="shortlisted"
                                                                    class="btn btn-sm rounded-pill px-2 py-0"
                                                                    style="background:#e3f2fd;color:#1565c0;font-size:0.65rem;border:0;"><i
                                                                        class="fas fa-thumbs-up"></i></button>
                                                            <?php endif; ?>
                                                            <?php if (in_array($stage, ['pending', 'shortlisted'])): ?>
                                                                <button name="new_status" value="hired"
                                                                    class="btn btn-sm rounded-pill px-2 py-0"
                                                                    style="background:#e8f5e9;color:#1b5e20;font-size:0.65rem;border:0;"><i
                                                                        class="fas fa-handshake"></i></button>
                                                            <?php endif; ?>
                                                            <button name="new_status" value="rejected"
                                                                class="btn btn-sm rounded-pill px-2 py-0"
                                                                style="background:#fce4ec;color:#b71c1c;font-size:0.65rem;border:0;"
                                                                onclick="return confirm('Reject?')"><i class="fas fa-times"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                    <a href="?view=applications&job_id=<?php echo $app['job_id']; ?>#app-<?php echo $app['id']; ?>"
                                                        class="btn btn-sm btn-outline-secondary rounded-pill px-2 py-0"
                                                        style="font-size:0.65rem;"><i class="fas fa-eye"></i></a>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- ══════════ EDIT JOB ══════════ -->
            <?php elseif ($view === 'edit_job' && $edit_job): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 text-white d-flex align-items-center justify-content-between"
                        style="background:linear-gradient(135deg,#1565C0,#0d47a1);">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-edit me-2"></i>Edit Job Listing</h5>
                            <p class="mb-0 small" style="opacity:0.85;">Update the details for
                                "<?php echo htmlspecialchars($edit_job['title']); ?>"</p>
                        </div>
                        <a href="?view=jobs" class="btn btn-sm btn-outline-light rounded-pill border-0"><i
                                class="fas fa-times me-1"></i>Cancel</a>
                    </div>
                    <form method="POST" class="p-4 p-md-5">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="update_job" value="1">
                        <input type="hidden" name="job_id" value="<?php echo $edit_job['id']; ?>">

                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Job Title *</label>
                                <input type="text" name="title"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo htmlspecialchars($edit_job['title']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Job Type *</label>
                                <select name="job_type" class="form-select rounded-pill border-0 bg-light px-4 py-3"
                                    required>
                                    <?php $jt = ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'internship' => 'Internship', 'freelance' => 'Freelance', 'daily_labor' => 'Daily Labor']; ?>
                                    <?php foreach ($jt as $v => $l): ?>
                                        <option value="<?php echo $v; ?>" <?php echo $edit_job['job_type'] === $v ? 'selected' : ''; ?>><?php echo $l; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <option value="">Select...</option>
                                    <?php
                                    $cats = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
                                    foreach ($cats as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $edit_job['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Location</label>
                                <input type="text" name="location"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo htmlspecialchars($edit_job['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Min Salary (ETB)</label>
                                <input type="number" name="salary_min"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo $edit_job['salary_min']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Max Salary (ETB)</label>
                                <input type="number" name="salary_max"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo $edit_job['salary_max']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Salary Period</label>
                                <select name="salary_period" class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <?php foreach (['month', 'day', 'week', 'hour', 'project'] as $p): ?>
                                        <option value="<?php echo $p; ?>" <?php echo $edit_job['salary_period'] === $p ? 'selected' : ''; ?>><?php echo ucfirst($p); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Experience Level</label>
                                <select name="experience_level"
                                    class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <?php foreach (['any', 'entry', 'mid', 'senior'] as $ex): ?>
                                        <option value="<?php echo $ex; ?>" <?php echo $edit_job['experience_level'] === $ex ? 'selected' : ''; ?>><?php echo ucfirst($ex); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Education Level</label>
                                <input type="text" name="education_level"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo htmlspecialchars($edit_job['education_level'] ?? ''); ?>"
                                    placeholder="e.g. Bachelor's Degree">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Deadline</label>
                                <input type="date" name="deadline"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo $edit_job['deadline']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status</label>
                                <select name="job_status" class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <option value="active" <?php echo $edit_job['status'] === 'active' ? 'selected' : ''; ?>>
                                        Active</option>
                                    <option value="closed" <?php echo $edit_job['status'] === 'closed' ? 'selected' : ''; ?>>
                                        Closed</option>
                                    <option value="draft" <?php echo $edit_job['status'] === 'draft' ? 'selected' : ''; ?>>
                                        Draft</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Skills Required (comma-separated)</label>
                                <input type="text" name="skills_required"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo htmlspecialchars($edit_job['skills_required'] ?? ''); ?>"
                                    placeholder="e.g. PHP, MySQL, JavaScript">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Job Description *</label>
                                <textarea name="description" class="form-control border-0 bg-light px-4 py-3" rows="4"
                                    style="border-radius:15px;"
                                    required><?php echo htmlspecialchars($edit_job['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Requirements / Qualifications</label>
                                <textarea name="requirements" class="form-control border-0 bg-light px-4 py-3" rows="4"
                                    style="border-radius:15px;"><?php echo htmlspecialchars($edit_job['requirements'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12 text-end">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>Update Job Listing
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- ══════════ COMPANY PROFILE ══════════ -->
            <?php elseif ($view === 'company'): ?>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="p-4 text-white" style="background:linear-gradient(135deg,#1565C0,#0d47a1);">
                        <h5 class="fw-bold mb-1"><i class="fas fa-building me-2"></i>Company Profile</h5>
                        <p class="mb-0 small" style="opacity:0.85;">Update your company info. This appears on all your job
                            listings.</p>
                    </div>
                    <form method="POST" class="p-4 p-md-5">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="update_company" value="1">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Company Name *</label>
                                <input type="text" name="company_name"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    value="<?php echo htmlspecialchars($company['company_name'] ?? ''); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Industry</label>
                                <input type="text" name="industry"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="e.g. Technology, Healthcare, Finance"
                                    value="<?php echo htmlspecialchars($company['industry'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Location</label>
                                <input type="text" name="location"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="e.g. Addis Ababa, Ethiopia"
                                    value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Company Size</label>
                                <select name="size" class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <?php foreach (['1-10', '11-50', '51-200', '201-500', '500+'] as $sz): ?>
                                        <option value="<?php echo $sz; ?>" <?php echo ($company['size'] ?? '') === $sz ? 'selected' : ''; ?>>
                                            <?php echo $sz; ?> employees
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label small fw-bold">Website</label>
                                <input type="url" name="website"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="https://yourcompany.com"
                                    value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Company Description</label>
                                <textarea name="description" class="form-control border-0 bg-light px-4 py-3" rows="5"
                                    style="border-radius:15px;"
                                    placeholder="Tell job seekers about your company, culture, mission..."><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-sm">
                                    <i class="fas fa-save me-2"></i>Save Company Profile
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

        </div><!-- /col-lg-9 -->
    </div><!-- /row -->
</div><!-- /container -->

<style>
    .toast-container {
        z-index: 1060;
    }
</style>

<!-- Toast for AJAX notifications -->
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div id="liveToast" class="toast align-items-center border-0 rounded-4 shadow" role="alert" aria-live="assertive"
        aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center gap-2">
                <i id="toastIcon"></i>
                <span id="toastMessage"></span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    document.addEventLis        tener('DOMContentLoaded', function () {
        const toastEl = document.getElementById('liveToast');
        const toast = new bootstrap.Toast(toastEl, { delay: 3000 });
        const toastIcon = document.getElementById('toastIcon');
        const toastMessage = document.getElementById('toastMessage');

        function showToast(msg, type = 'success') {
            toastEl.className = `toast align-items-center border-0 rounded-4 shadow text-white bg-${type}`;
            toastIcon.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
            toastMessage.textContent = msg;
            toast.show();
        }

        // Show/hide interview date field based on status select
        function updateInterviewVisibility(form) {
            const sel = form.querySelector('[name="new_status"]');
            const row = form.querySelector('.interview-date-row');
            if (sel && row) {
                row.style.display = sel.value === 'interviewed' ? 'block' : 'none';
            }
        }

        document.querySelectorAll('.update-status-form').forEach(form => {
            const sel = form.querySelector('[name="new_status"]');
            if (sel) {
                sel.addEventListener('change', () => updateInterviewVisibility(form));
            }

            // AJAX Submission
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                const btn = form.querySelector('button[type="submit"]');
                const originalHtml = btn.innerHTML;

                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                const formData = new FormData(this);
                formData.append('action', 'update_app_status');
                // Note: The specific value from quick action buttons is picked up because we set the select value or use the input hidden.

                fetch('ajax_job_action.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(data.message, 'success');
                            setTimeout(() => location.reload(), 1500);
                        } else {
                            showToast(data.message, 'danger');
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        }
                    })
                    .catch(err => {
                        showToast('Server error. Please try again.', 'danger');
                        btn.disabled = false;
                        btn.innerHTML = originalHtml;
                    });
            });
        });

        // Quick action buttons override the select value
        document.querySelectorAll('.update-status-form button[name="new_status"]').forEach(btn => {
            btn.addEventListener('click', function () {
                const form = this.closest('form');
                const sel = form.querySelector('select[name="new_status"]');
                if (sel) {
                    sel.value = this.value;
                    updateInterviewVisibility(form);
                }
            });
        });

        // AJAX for Job Toggle
        document.querySelectorAll('form[method="POST"]').forEach(form => {
            if (form.querySelector('button[name="toggle_job"]')) {
                form.addEventListener('submit', function (e) {
                    const btn = form.querySelector('button[name="toggle_job"]');
                    if (!btn) return;

                    e.preventDefault();
                    btn.disabled = true;
                    const originalHtml = btn.innerHTML;
                    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';

                    const formData = new FormData(this);
                    formData.append('action', 'toggle_job_status');

                    fetch('ajax_job_action.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                showToast(data.message, 'success');
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                showToast(data.message, 'danger');
                                btn.disabled = false;
                                btn.innerHTML = originalHtml;
                            }
                        })
                        .catch(() => {
                            btn.disabled = false;
                            btn.innerHTML = originalHtml;
                        });
                });
            }
        });
    });
</script>

<?php include '../includes/footer.php'; ?>