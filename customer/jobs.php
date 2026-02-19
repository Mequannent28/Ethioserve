<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;

// ── Handle Quick Apply ────────────────────────────────────────────
$apply_success = false;
$apply_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply_job'])) {
    if (!$user_id) {
        redirectWithMessage('../login.php', 'warning', 'Please login to apply for jobs.');
    }
    $job_id = intval($_POST['job_id']);
    $cover = sanitize($_POST['cover_letter'] ?? '');
    try {
        $chk = $pdo->prepare("SELECT id FROM job_applications WHERE job_id=? AND applicant_id=?");
        $chk->execute([$job_id, $user_id]);
        if ($chk->fetch()) {
            $apply_error = 'You have already applied for this job.';
        } else {
            // Get CV from profile if available
            $prof = $pdo->prepare("SELECT cv_url FROM job_profiles WHERE user_id=?");
            $prof->execute([$user_id]);
            $cv_url = $prof->fetchColumn() ?: null;

            $pdo->prepare("INSERT INTO job_applications (job_id, applicant_id, cover_letter, cv_url) VALUES (?,?,?,?)")
                ->execute([$job_id, $user_id, $cover, $cv_url]);

            $pdo->prepare("UPDATE job_listings SET views=views+1 WHERE id=?")->execute([$job_id]);
            $apply_success = true;
        }
    } catch (Exception $e) {
        $apply_error = 'Application failed: ' . $e->getMessage();
    }
}

// ── Handle Post Job ───────────────────────────────────────────────
$post_success = false;
$post_error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    if (!$user_id) {
        $post_error = 'Please login to post a job.';
        // Optional: you can still redirect, but let's allow the UI to show the error if we want
        header("Location: ../login.php?msg=Please login to post a job&type=warning");
        exit;
    }
    try {
        // ensure company record
        $co = $pdo->prepare("SELECT id FROM job_companies WHERE user_id=?");
        $co->execute([$user_id]);
        $company = $co->fetch();
        if (!$company) {
            $pdo->prepare("INSERT INTO job_companies (user_id, company_name, location, industry) VALUES (?,?,?,?)")
                ->execute([
                    $user_id,
                    sanitize($_POST['company_name'] ?? 'My Company'),
                    sanitize($_POST['location'] ?? ''),
                    sanitize($_POST['industry'] ?? '')
                ]);
            $company_id = $pdo->lastInsertId();
        } else {
            $company_id = $company['id'];
        }

        $pdo->prepare("INSERT INTO job_listings
            (company_id, posted_by, title, description, requirements, job_type, category_id,
             location, salary_min, salary_max, salary_period, skills_required, experience_level, deadline)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)")
            ->execute([
                $company_id,
                $user_id,
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
                $_POST['deadline'] ?: null
            ]);
        $post_success = true;
    } catch (Exception $e) {
        $apply_error = 'Post failed: ' . $e->getMessage();
    }
}

// ── Filters ───────────────────────────────────────────────────────
$filter_type = $_GET['type'] ?? '';
$filter_cat = intval($_GET['cat'] ?? 0);
$filter_location = sanitize($_GET['location'] ?? '');
$filter_search = sanitize($_GET['q'] ?? '');
$active_tab = $_GET['tab'] ?? 'jobs';

// ── Fetch Categories ──────────────────────────────────────────────
$categories = [];
try {
    $categories = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();
} catch (Exception $e) {
}

// ── Fetch Job Listings ────────────────────────────────────────────
$jobs = [];
try {
    $where = ["jl.status = 'active'"];
    $params = [];
    if ($filter_type) {
        $where[] = "jl.job_type = ?";
        $params[] = $filter_type;
    }
    if ($filter_cat) {
        $where[] = "jl.category_id = ?";
        $params[] = $filter_cat;
    }
    if ($filter_location) {
        $where[] = "jl.location LIKE ?";
        $params[] = "%$filter_location%";
    }
    if ($filter_search) {
        $where[] = "(jl.title LIKE ? OR jl.description LIKE ? OR jl.skills_required LIKE ?)";
        $params = array_merge($params, ["%$filter_search%", "%$filter_search%", "%$filter_search%"]);
    }
    if ($active_tab === 'freelance') {
        $where[] = "jl.job_type = 'freelance'";
    } elseif ($active_tab === 'internship') {
        $where[] = "jl.job_type = 'internship'";
    } elseif ($active_tab === 'daily') {
        $where[] = "jl.job_type = 'daily_labor'";
    }

    $sql = "SELECT jl.*, jc.company_name, jc.logo_url, jc.verified,
                   jcat.name as cat_name, jcat.icon as cat_icon, jcat.color as cat_color,
                   u.full_name as poster_name
            FROM job_listings jl
            LEFT JOIN job_companies jc ON jl.company_id = jc.id
            LEFT JOIN job_categories jcat ON jl.category_id = jcat.id
            LEFT JOIN users u ON jl.posted_by = u.id
            WHERE " . implode(' AND ', $where) . "
            ORDER BY jl.created_at DESC LIMIT 50";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $jobs = $stmt->fetchAll();
} catch (Exception $e) {
}

// ── Fetch Freelance Services ──────────────────────────────────────
$services = [];
try {
    $services = $pdo->query("SELECT fs.*, u.full_name, jp.headline, jp.profile_pic, jp.rating as prof_rating
                              FROM freelance_services fs
                              JOIN users u ON fs.provider_id = u.id
                              LEFT JOIN job_profiles jp ON jp.user_id = fs.provider_id
                              WHERE fs.status = 'active'
                              ORDER BY fs.rating DESC, fs.total_orders DESC LIMIT 24")->fetchAll();
} catch (Exception $e) {
}

// ── Stats ─────────────────────────────────────────────────────────
$stats = ['jobs' => 0, 'companies' => 0, 'freelancers' => 0, 'applications' => 0, 'total_apps' => 0];
try {
    $stats['jobs'] = (int) $pdo->query("SELECT COUNT(*) FROM job_listings WHERE status='active'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $stats['companies'] = (int) $pdo->query("SELECT COUNT(*) FROM job_companies")->fetchColumn();
} catch (Exception $e) {
}
try {
    $stats['freelancers'] = (int) $pdo->query("SELECT COUNT(*) FROM freelance_services WHERE status='active'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $stats['total_apps'] = (int) $pdo->query("SELECT COUNT(*) FROM job_applications")->fetchColumn();
} catch (Exception $e) {
}
if ($user_id) {
    try {
        $st = $pdo->prepare("SELECT COUNT(*) FROM job_applications WHERE applicant_id=?");
        $st->execute([$user_id]);
        $stats['applications'] = (int) $st->fetchColumn();
    } catch (Exception $e) {
    }
}

// User's applications
$my_applications = [];
if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT ja.*, jl.title, jl.job_type, jc.company_name, jp.cv_url as profile_cv
                               FROM job_applications ja
                               JOIN job_listings jl ON ja.job_id = jl.id
                               LEFT JOIN job_companies jc ON jl.company_id = jc.id
                               LEFT JOIN job_profiles jp ON jp.user_id = ja.applicant_id
                               WHERE ja.applicant_id = ? ORDER BY ja.applied_at DESC LIMIT 10");
        $stmt->execute([$user_id]);
        $my_applications = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}

include '../includes/header.php';
?>

<!-- ═══════════════════════ HERO SECTION ═══════════════════════════ -->
<div class="jobs-hero"
    style="background:linear-gradient(135deg,#0d47a1 0%,#1565c0 40%,#1976d2 70%,#0288d1 100%); min-height:360px; position:relative; overflow:hidden;">
    <!-- Decorative blobs -->
    <div
        style="position:absolute;top:-80px;right:-80px;width:400px;height:400px;border-radius:50%;background:rgba(255,255,255,0.05);">
    </div>
    <div
        style="position:absolute;bottom:-120px;left:-60px;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,0.05);">
    </div>
    <div class="container py-5 position-relative">
        <!-- Messages -->
        <?php if ($apply_success): ?>
            <div class="alert alert-success rounded-4 shadow-sm border-0 mb-4 p-4 animate__animated animate__fadeIn">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-success bg-opacity-10 text-success rounded-circle p-3"><i
                            class="fas fa-check-circle fs-3"></i></div>
                    <div>
                        <h5 class="fw-bold mb-1">Application Submitted!</h5>
                        <p class="mb-0">Your application has been received. You can track its status in <strong>My
                                Applications</strong>.</p>
                    </div>
                    <a href="?tab=my_apps" class="btn btn-success rounded-pill ms-auto px-4">Track Status</a>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($apply_error): ?>
            <div class="alert alert-danger rounded-4 shadow-sm border-0 mb-4 p-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $apply_error; ?>
            </div>
        <?php endif; ?>
        <?php if ($post_error): ?>
            <div class="alert alert-danger rounded-4 shadow-sm border-0 mb-4 p-4">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo $post_error; ?>
            </div>
        <?php endif; ?>
        <?php if ($post_success): ?>
            <div class="alert alert-primary rounded-4 shadow-sm border-0 mb-4 p-4">
                <div class="d-flex align-items-center gap-3">
                    <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-3"><i class="fas fa-magic fs-3"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-1">Job Posted Successfully!</h5>
                        <p class="mb-0">Your job listing is now live. Check the <strong>Employer Dashboard</strong> to
                            manage applicants.</p>
                    </div>
                    <a href="employer_dashboard.php" class="btn btn-primary rounded-pill ms-auto px-4">Go to Dashboard</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row align-items-center">
            <div class="col-lg-7">
                <span class="badge rounded-pill px-3 py-2 mb-3"
                    style="background:rgba(255,255,255,0.15);color:#fff;font-size:0.8rem;"><i
                        class="fas fa-briefcase me-2"></i>Ethiopia's #1 Job & Freelance Platform</span>
                <h1 class="display-5 fw-bold text-white mb-3">Find Your Dream Job <br>or <span
                        style="color:#FFD600;">Hire Top Talent</span></h1>
                <p class="text-white-75 mb-4" style="font-size:1.05rem;opacity:0.85;">Jobs, internships, daily labor,
                    and freelance services — all in one super app.</p>
                <!-- Search Bar -->
                <form method="GET" class="d-flex gap-2 flex-wrap">
                    <input type="hidden" name="tab" value="<?php echo htmlspecialchars($active_tab); ?>">
                    <div class="flex-grow-1 position-relative" style="min-width:220px;">
                        <i class="fas fa-search position-absolute text-muted"
                            style="left:18px;top:50%;transform:translateY(-50%);"></i>
                        <input type="text" name="q" value="<?php echo htmlspecialchars($filter_search); ?>"
                            class="form-control rounded-pill border-0 shadow-sm ps-5 py-3"
                            placeholder="Job title, skill, or keyword..." style="font-size:0.95rem;">
                    </div>
                    <div style="min-width:160px;">
                        <div class="position-relative">
                            <i class="fas fa-map-marker-alt position-absolute text-muted"
                                style="left:18px;top:50%;transform:translateY(-50%);"></i>
                            <input type="text" name="location" value="<?php echo htmlspecialchars($filter_location); ?>"
                                class="form-control rounded-pill border-0 shadow-sm ps-5 py-3" placeholder="Location..."
                                style="font-size:0.95rem;">
                        </div>
                    </div>
                    <button type="submit" class="btn rounded-pill px-4 py-2 fw-bold shadow"
                        style="background:#FFD600;color:#0d47a1;white-space:nowrap;">
                        <i class="fas fa-search me-1"></i> Search
                    </button>
                </form>
                <!-- Quick filters -->
                <div class="d-flex flex-wrap gap-2 mt-3">
                    <?php $types = ['full_time' => 'Full Time', 'part_time' => 'Part Time', 'contract' => 'Contract', 'internship' => 'Internship', 'daily_labor' => 'Daily Labor', 'freelance' => 'Freelance']; ?>
                    <?php foreach ($types as $val => $lbl): ?>
                        <a href="?tab=<?php echo $active_tab; ?>&type=<?php echo $val; ?>&q=<?php echo urlencode($filter_search); ?>"
                            class="badge rounded-pill px-3 py-2 text-decoration-none"
                            style="background:<?php echo $filter_type === $val ? '#FFD600' : 'rgba(255,255,255,0.15)'; ?>;color:<?php echo $filter_type === $val ? '#0d47a1' : '#fff'; ?>;font-size:0.78rem;">
                            <?php echo $lbl; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-5 text-center d-none d-lg-block">
                <!-- Stats Cards -->
                <div class="row g-3 mt-2">
                    <?php $stat_items = [
                        ['fas fa-briefcase', '#FFD600', '#0d47a1', $stats['jobs'], 'Active Jobs'],
                        ['fas fa-building', 'rgba(255,255,255,0.2)', '#fff', $stats['companies'], 'Companies'],
                        ['fas fa-laptop-code', 'rgba(255,255,255,0.2)', '#fff', $stats['freelancers'], 'Freelancers'],
                        ['fas fa-paper-plane', 'rgba(255,255,255,0.2)', '#fff', $stats['total_apps'], 'Applications'],
                    ]; ?>
                    <?php foreach ($stat_items as $si): ?>
                        <div class="col-6">
                            <div class="rounded-4 p-3 text-center" style="background:<?php echo $si[1]; ?>;">
                                <i class="<?php echo $si[0]; ?> mb-1"
                                    style="font-size:1.6rem;color:<?php echo $si[2]; ?>;"></i>
                                <div class="fw-bold" style="font-size:1.4rem;color:<?php echo $si[2]; ?>;">
                                    <?php echo number_format($si[3]); ?>
                                </div>
                                <div style="font-size:0.72rem;color:<?php echo $si[2]; ?>;opacity:0.8;">
                                    <?php echo $si[4]; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ FEATURED CATEGORIES ══════════════════════ -->
<div class="bg-light py-5 border-bottom">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div class="text-start">
                <h3 class="fw-bold mb-1">Explore by Category</h3>
                <p class="text-muted small mb-0">Discover opportunities across all professional sectors</p>
            </div>
            <a href="#Categories" class="btn btn-sm btn-outline-primary rounded-pill px-3 d-none d-md-block">View
                All</a>
        </div>

        <div class="row g-3">
            <?php
            // Show top 8 categories with most jobs
            $top_cats = array_slice($categories ?? [], 0, 8);
            foreach ($top_cats as $cat): ?>
                <div class="col-6 col-md-4 col-lg-3">
                    <a href="?tab=<?php echo $active_tab ?? 'jobs'; ?>&cat=<?php echo $cat['id']; ?>"
                        class="category-card-premium shadow-sm border p-4 rounded-4 hover-lift d-block text-decoration-none bg-white">
                        <div class="icon-circle mb-3 mx-auto"
                            style="background:<?php echo $cat['color']; ?>15; color:<?php echo $cat['color']; ?>;">
                            <i class="<?php echo $cat['icon']; ?> fs-3"></i>
                        </div>
                        <h6 class="fw-bold text-dark mb-1 text-truncate"><?php echo htmlspecialchars($cat['name']); ?></h6>
                        <?php
                        try {
                            $stmtCount = $pdo->prepare("SELECT COUNT(*) FROM job_listings WHERE category_id = ? AND status = 'active'");
                            $stmtCount->execute([$cat['id']]);
                            $count = $stmtCount->fetchColumn();
                        } catch (Exception $e) {
                            $count = 0;
                        }
                        ?>
                        <span class="text-muted small"><?php echo $count; ?> Active Jobs</span>
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- ═══════════════════════ MAIN CONTENT ═══════════════════════════ -->
<div class="container py-4">

    <?php if ($apply_success): ?>
        <div class="alert alert-success alert-dismissible rounded-4 shadow-sm border-0 d-flex align-items-center gap-3"
            role="alert">
            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:44px;height:44px;">
                <i class="fas fa-check text-white"></i>
            </div>
            <div>
                <strong>Application Submitted! 🎉</strong><br>
                <small>Your application has been sent. The employer will contact you soon. Good luck!</small>
            </div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($post_success): ?>
        <div class="alert alert-success alert-dismissible rounded-4 shadow-sm border-0 d-flex align-items-center gap-3"
            role="alert">
            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                style="width:44px;height:44px;">
                <i class="fas fa-check text-white"></i>
            </div>
            <div><strong>Job Posted Successfully! ✅</strong><br><small>Your job listing is now live and visible to job
                    seekers.</small></div>
            <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if ($apply_error): ?>
        <div class="alert alert-warning rounded-4">
            <?php echo htmlspecialchars($apply_error); ?>
        </div>
    <?php endif; ?>

    <!-- ── NAV TABS ──────────────────────────────────────────────── -->
    <ul class="nav nav-pills gap-2 mb-4 flex-wrap" id="jobTabs">
        <?php $tabs = [
            'jobs' => ['fas fa-briefcase', 'All Jobs'],
            'freelance' => ['fas fa-laptop-code', 'Freelance'],
            'internship' => ['fas fa-graduation-cap', 'Internships'],
            'daily' => ['fas fa-hard-hat', 'Daily Labor'],
            'my_apps' => ['fas fa-paper-plane', 'My Applications'],
            'post' => ['fas fa-plus-circle', 'Post a Job'],
        ]; ?>
        <?php foreach ($tabs as $t => [$icon, $label]): ?>
            <li class="nav-item">
                <a href="?tab=<?php echo $t; ?>&q=<?php echo urlencode($filter_search); ?>&location=<?php echo urlencode($filter_location); ?>"
                    class="nav-link rounded-pill px-4 py-2 fw-semibold <?php echo $active_tab === $t ? 'active' : ''; ?>"
                    style="<?php echo $active_tab === $t ? 'background:#1565C0;color:#fff;' : 'background:#f0f4ff;color:#1565C0;'; ?>">
                    <i class="<?php echo $icon; ?> me-1"></i>
                    <?php echo $label; ?>
                </a>
            </li>
        <?php endforeach; ?>
    </ul>

    <!-- ─────────────────── JOBS / INTERNSHIPS / DAILY TAB ──────── -->
    <?php if (in_array($active_tab, ['jobs', 'internship', 'daily'])): ?>
        <div class="row g-4">
            <!-- Sidebar: Categories -->
            <div class="col-lg-3 d-none d-lg-block">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between">
                        <h6 class="fw-bold mb-0 text-dark">
                            <i class="fas fa-th-large me-2 text-primary"></i>Categories
                        </h6>
                        <?php if ($filter_cat): ?>
                            <a href="?tab=<?php echo $active_tab; ?>" class="text-decoration-none small text-danger fw-bold">
                                <i class="fas fa-times-circle"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-2" style="max-height: 500px; overflow-y: auto;">
                        <div class="category-list">
                            <?php foreach ($categories as $cat): ?>
                                <a href="?tab=<?php echo $active_tab; ?>&cat=<?php echo $cat['id']; ?>&q=<?php echo urlencode($filter_search); ?>"
                                    class="category-item <?php echo $filter_cat == $cat['id'] ? 'active' : ''; ?>">
                                    <div class="category-icon"
                                        style="background:<?php echo $cat['color']; ?>15; color:<?php echo $cat['color']; ?>;">
                                        <i class="<?php echo $cat['icon']; ?>"></i>
                                    </div>
                                    <div class="category-info">
                                        <div class="category-name"><?php echo htmlspecialchars($cat['name']); ?></div>
                                        <div class="category-indicator"></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Quick post CTA -->
                <div class="card border-0 rounded-4 p-4 text-center text-white"
                    style="background:linear-gradient(135deg,#1565c0,#0d47a1);">
                    <i class="fas fa-plus-circle mb-2" style="font-size:2rem;"></i>
                    <h6 class="fw-bold">Hiring?</h6>
                    <p class="small mb-3" style="opacity:0.85;">Post a job and find the perfect candidate.</p>
                    <a href="?tab=post" class="btn rounded-pill fw-bold" style="background:#FFD600;color:#0d47a1;">Post a
                        Job</a>
                </div>
            </div>

            <!-- Job Cards -->
            <div class="col-lg-9">
                <?php if (empty($jobs)): ?>
                    <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                        <div class="mb-3">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                                style="width:80px;height:80px;"><i class="fas fa-briefcase text-primary fs-2"></i></div>
                        </div>
                        <h5 class="fw-bold">No jobs found</h5>
                        <p class="text-muted">Try adjusting your filters or be the first to post a job!</p>
                        <a href="?tab=post" class="btn btn-primary rounded-pill px-4">Post a Job</a>
                    </div>
                <?php else: ?>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">
                            <?php echo count($jobs); ?> job
                            <?php echo count($jobs) !== 1 ? 's' : ''; ?> found
                        </span>
                        <div class="d-flex gap-2 align-items-center">
                            <span class="text-muted small">Sort:</span>
                            <select class="form-select form-select-sm rounded-pill border-0 bg-light" style="width:auto;"
                                onchange="window.location=this.value">
                                <option value="?tab=<?php echo $active_tab; ?>&q=<?php echo urlencode($filter_search); ?>">
                                    Newest</option>
                            </select>
                        </div>
                    </div>
                    <?php foreach ($jobs as $job): ?>
                        <div class="card border-0 shadow-sm rounded-4 mb-3 job-card hover-lift">
                            <div class="card-body p-4">
                                <div class="d-flex gap-3">
                                    <!-- Company Logo -->
                                    <div class="flex-shrink-0">
                                        <?php if (!empty($job['logo_url'])): ?>
                                            <img src="<?php echo htmlspecialchars($job['logo_url']); ?>" class="rounded-3" width="56"
                                                height="56" style="object-fit:cover;border:1px solid #e0e0e0;">
                                        <?php else: ?>
                                            <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold text-white"
                                                style="width:56px;height:56px;background:<?php echo $job['cat_color'] ?: '#1565C0'; ?>;font-size:1.4rem;">
                                                <?php echo strtoupper(substr($job['company_name'] ?: $job['poster_name'] ?: 'J', 0, 1)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <!-- Job Info -->
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="d-flex align-items-start justify-content-between gap-2 flex-wrap">
                                            <div>
                                                <h6 class="fw-bold mb-1 text-dark" style="font-size:1rem;">
                                                    <?php echo htmlspecialchars($job['title']); ?>
                                                </h6>
                                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                                    <span class="text-muted small">
                                                        <i class="fas fa-building me-1"></i>
                                                        <?php echo htmlspecialchars($job['company_name'] ?: $job['poster_name'] ?: 'Individual'); ?>
                                                    </span>
                                                    <?php if ($job['verified']): ?>
                                                        <span class="badge rounded-pill"
                                                            style="background:#e3f2fd;color:#1565c0;font-size:0.65rem;"><i
                                                                class="fas fa-check-circle me-1"></i>Verified</span>
                                                    <?php endif; ?>
                                                    <?php if ($job['location']): ?>
                                                        <span class="text-muted small"><i
                                                                class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                            <?php echo htmlspecialchars($job['location']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="text-end flex-shrink-0">
                                                <?php if ($job['salary_min'] || $job['salary_max']): ?>
                                                    <div class="fw-bold text-success" style="font-size:0.9rem;">
                                                        <?php echo $job['currency'] ?? 'ETB'; ?>
                                                        <?php if ($job['salary_min'])
                                                            echo number_format($job['salary_min']); ?>
                                                        <?php if ($job['salary_min'] && $job['salary_max'])
                                                            echo ' – '; ?>
                                                        <?php if ($job['salary_max'])
                                                            echo number_format($job['salary_max']); ?>
                                                        <small class="text-muted fw-normal">/
                                                            <?php echo $job['salary_period']; ?>
                                                        </small>
                                                    </div>
                                                <?php endif; ?>
                                                <small class="text-muted">
                                                    <?php echo date('M d', strtotime($job['created_at'])); ?>
                                                </small>
                                            </div>
                                        </div>

                                        <!-- Tags -->
                                        <div class="d-flex flex-wrap gap-1 mt-2">
                                            <?php $type_colors = ['full_time' => ['#e8f5e9', '#2e7d32'], 'part_time' => ['#fff8e1', '#f57f17'], 'contract' => ['#e3f2fd', '#1565c0'], 'internship' => ['#f3e5f5', '#6a1b9a'], 'daily_labor' => ['#fce4ec', '#b71c1c'], 'freelance' => ['#e0f2f1', '#004d40']];
                                            $tc = $type_colors[$job['job_type']] ?? ['#f5f5f5', '#333']; ?>
                                            <span class="badge rounded-pill px-2 py-1"
                                                style="background:<?php echo $tc[0]; ?>;color:<?php echo $tc[1]; ?>;font-size:0.7rem;">
                                                <?php echo ucwords(str_replace('_', ' ', $job['job_type'])); ?>
                                            </span>
                                            <?php if ($job['cat_name']): ?>
                                                <span class="badge rounded-pill px-2 py-1"
                                                    style="background:<?php echo ($job['cat_color'] ?? '#1565C0') . '20'; ?>;color:<?php echo $job['cat_color'] ?? '#1565C0'; ?>;font-size:0.7rem;">
                                                    <i class="<?php echo $job['cat_icon'] ?? 'fas fa-briefcase'; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($job['cat_name']); ?>
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($job['experience_level'] && $job['experience_level'] !== 'any'): ?>
                                                <span class="badge rounded-pill px-2 py-1"
                                                    style="background:#f5f5f5;color:#555;font-size:0.7rem;">
                                                    <?php echo ucfirst($job['experience_level']); ?> Level
                                                </span>
                                            <?php endif; ?>
                                            <?php if ($job['is_remote']): ?>
                                                <span class="badge rounded-pill px-2 py-1"
                                                    style="background:#e0f7fa;color:#006064;font-size:0.7rem;"><i
                                                        class="fas fa-globe me-1"></i>Remote</span>
                                            <?php endif; ?>
                                            <?php if ($job['deadline'] && strtotime($job['deadline']) > time() && strtotime($job['deadline']) < time() + 604800): ?>
                                                <span class="badge rounded-pill px-2 py-1"
                                                    style="background:#fff3e0;color:#e65100;font-size:0.7rem;"><i
                                                        class="fas fa-clock me-1"></i>Closing Soon</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Skills -->
                                        <?php if ($job['skills_required']): ?>
                                            <div class="mt-2 d-flex flex-wrap gap-1">
                                                <?php foreach (array_slice(explode(',', $job['skills_required']), 0, 4) as $sk): ?>
                                                    <span class="badge rounded-pill border px-2 py-1"
                                                        style="background:#f9f9f9;color:#555;border-color:#ddd!important;font-size:0.68rem;">
                                                        <?php echo htmlspecialchars(trim($sk)); ?>
                                                    </span>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Description preview -->
                                        <p class="text-muted small mt-2 mb-3"
                                            style="line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">
                                            <?php echo htmlspecialchars(substr(strip_tags($job['description']), 0, 180)); ?>...
                                        </p>

                                        <!-- Actions -->
                                        <div class="d-flex gap-2 flex-wrap">
                                            <button class="btn btn-primary rounded-pill px-4 py-2 fw-semibold apply-btn"
                                                data-job-id="<?php echo $job['id']; ?>"
                                                data-job-title="<?php echo htmlspecialchars($job['title']); ?>"
                                                data-company="<?php echo htmlspecialchars($job['company_name'] ?: $job['poster_name']); ?>"
                                                style="font-size:0.88rem;">
                                                <i class="fas fa-paper-plane me-1"></i>Apply Now
                                            </button>
                                            <button class="btn btn-outline-secondary rounded-pill px-3 py-2"
                                                style="font-size:0.88rem;" data-bs-toggle="modal" data-bs-target="#jobDetailModal"
                                                data-job-id="<?php echo $job['id']; ?>"
                                                data-title="<?php echo htmlspecialchars($job['title']); ?>"
                                                data-company="<?php echo htmlspecialchars($job['company_name'] ?: $job['poster_name']); ?>"
                                                data-desc="<?php echo htmlspecialchars($job['description']); ?>"
                                                data-req="<?php echo htmlspecialchars($job['requirements']); ?>"
                                                data-skills="<?php echo htmlspecialchars($job['skills_required']); ?>"
                                                data-type="<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $job['job_type']))); ?>"
                                                data-location="<?php echo htmlspecialchars($job['location']); ?>">
                                                <i class="fas fa-eye me-1"></i>Details
                                            </button>
                                            <button class="btn btn-outline-warning rounded-pill px-3 py-2" title="Save Job"
                                                style="font-size:0.88rem;">
                                                <i class="fas fa-bookmark"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- ─────────────────── FREELANCE TAB ───────────────────────── -->
    <?php elseif ($active_tab === 'freelance'): ?>
        <!-- Freelance Categories -->
        <div class="d-flex gap-2 flex-wrap mb-4">
            <?php $fl_cats = ['All Services', 'Graphic Design', 'Web Development', 'Video Editing', 'IT Support', 'Writing', 'Marketing', 'Translation', 'Photography', 'Other']; ?>
            <?php foreach ($fl_cats as $fc): ?>
                <button
                    class="btn btn-sm rounded-pill <?php echo $fc === 'All Services' ? 'btn-primary' : 'btn-outline-primary'; ?>"
                    style="font-size:0.8rem;">
                    <?php echo $fc; ?>
                </button>
            <?php endforeach; ?>
        </div>
        <?php if (empty($services)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <div class="mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:80px;height:80px;"><i class="fas fa-laptop-code text-primary fs-2"></i></div>
                </div>
                <h5 class="fw-bold">No Freelance Services Yet</h5>
                <p class="text-muted">Be the first to offer your skills!</p>
                <button class="btn btn-primary rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#postServiceModal">Offer a Service</button>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($services as $svc): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 h-100 hover-lift overflow-hidden">
                            <?php if (!empty($svc['image_url'])): ?>
                                <img src="<?php echo htmlspecialchars($svc['image_url']); ?>" style="height:180px;object-fit:cover;"
                                    class="card-img-top">
                            <?php else: ?>
                                <div style="height:140px;background:linear-gradient(135deg,#1565C0,#0d47a1);"
                                    class="d-flex align-items-center justify-content-center">
                                    <i class="fas fa-laptop-code text-white" style="font-size:3rem;opacity:0.4;"></i>
                                </div>
                            <?php endif; ?>
                            <div class="card-body p-3">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center fw-bold"
                                        style="width:32px;height:32px;font-size:0.8rem;">
                                        <?php echo strtoupper(substr($svc['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold" style="font-size:0.85rem;">
                                            <?php echo htmlspecialchars($svc['full_name']); ?>
                                        </div>
                                        <?php if (!empty($svc['headline'])): ?>
                                            <div class="text-muted" style="font-size:0.72rem;">
                                                <?php echo htmlspecialchars(substr($svc['headline'], 0, 35)); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($svc['prof_rating'] > 0): ?>
                                        <div class="ms-auto text-warning small" style="font-size:0.8rem;"><i class="fas fa-star"></i>
                                            <?php echo number_format($svc['prof_rating'], 1); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <h6 class="fw-bold mb-1" style="font-size:0.9rem;line-height:1.4;">
                                    <?php echo htmlspecialchars($svc['title']); ?>
                                </h6>
                                <?php if ($svc['category']): ?>
                                    <span class="badge rounded-pill mb-2" style="background:#e3f2fd;color:#1565c0;font-size:0.68rem;">
                                        <?php echo htmlspecialchars($svc['category']); ?>
                                    </span>
                                <?php endif; ?>
                                <p class="text-muted small mb-2"
                                    style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;line-height:1.4;">
                                    <?php echo htmlspecialchars($svc['description']); ?>
                                </p>
                                <div class="d-flex align-items-center justify-content-between mt-auto">
                                    <div>
                                        <?php if ($svc['price']): ?>
                                            <span class="fw-bold text-success">
                                                <?php if ($svc['price_type'] === 'hourly'): ?>
                                                    ETB
                                                    <?php echo number_format($svc['price']); ?>/hr
                                                <?php elseif ($svc['price_type'] === 'fixed'): ?>
                                                    Starting ETB
                                                    <?php echo number_format($svc['price']); ?>
                                                <?php else: ?>
                                                    Negotiable
                                                <?php endif; ?>
                                            </span>
                                        <?php endif; ?>
                                        <div class="text-muted" style="font-size:0.7rem;"><i class="fas fa-clock me-1"></i>
                                            <?php echo $svc['delivery_days']; ?> day delivery ·
                                            <?php echo $svc['total_orders']; ?> orders
                                        </div>
                                    </div>
                                    <button class="btn btn-primary rounded-pill px-3 py-1 fw-semibold" style="font-size:0.8rem;">
                                        <i class="fas fa-envelope me-1"></i>Contact
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <!-- Offer service CTA -->
        <div class="card border-0 rounded-4 mt-4 p-4 d-flex flex-column flex-md-row align-items-center gap-4"
            style="background:linear-gradient(135deg,#e3f2fd,#bbdefb);">
            <div class="flex-grow-1">
                <h5 class="fw-bold text-primary mb-1">Offer Your Skills as a Freelancer</h5>
                <p class="text-muted mb-0">Graphic design, web dev, video editing, IT support, writing — earn from your
                    expertise.</p>
            </div>
            <button class="btn btn-primary rounded-pill px-4 fw-bold flex-shrink-0" data-bs-toggle="modal"
                data-bs-target="#postServiceModal">
                <i class="fas fa-plus me-2"></i>List My Service
            </button>
        </div>

        <!-- ─────────────────── MY APPLICATIONS TAB ─────────────────── -->
    <?php elseif ($active_tab === 'my_apps'): ?>
        <?php if (!$user_id): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <i class="fas fa-lock text-muted mb-3 fs-1"></i>
                <h5 class="fw-bold">Please Log In</h5>
                <p class="text-muted">You need to be logged in to see your applications.</p>
                <a href="../login.php" class="btn btn-primary rounded-pill px-4">Login</a>
            </div>
        <?php elseif (empty($my_applications)): ?>
            <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                <div class="mb-3">
                    <div class="bg-primary bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center"
                        style="width:80px;height:80px;"><i class="fas fa-paper-plane text-primary fs-2"></i></div>
                </div>
                <h5 class="fw-bold">No Applications Yet</h5>
                <p class="text-muted">Start applying for jobs that match your skills!</p>
                <a href="?tab=jobs" class="btn btn-primary rounded-pill px-4">Browse Jobs</a>
            </div>
        <?php else: ?>
            <h5 class="fw-bold mb-3">My Applications</h5>
            <?php foreach ($my_applications as $app): ?>
                <?php $sc = ['pending' => ['#fff8e1', '#f57f17', 'fas fa-clock'], 'shortlisted' => ['#e3f2fd', '#1565c0', 'fas fa-thumbs-up'], 'interviewed' => ['#e8f5e9', '#2e7d32', 'fas fa-user-check'], 'hired' => ['#e8f5e9', '#1b5e20', 'fas fa-check-double'], 'rejected' => ['#fce4ec', '#b71c1c', 'fas fa-times-circle']];
                $s = $sc[$app['status']] ?? $sc['pending']; ?>
                <div class="card border-0 shadow-sm rounded-4 mb-3">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="flex-grow-1">
                                <h6 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($app['title']); ?>
                                </h6>
                                <div class="text-muted small"><i class="fas fa-building me-1"></i>
                                    <?php echo htmlspecialchars($app['company_name'] ?: 'Individual'); ?>
                                </div>
                                <div class="text-muted small mt-1"><i class="fas fa-calendar me-1"></i>Applied
                                    <?php echo date('M d, Y', strtotime($app['applied_at'])); ?>
                                </div>
                            </div>
                            <span class="badge rounded-pill px-3 py-2 fw-semibold"
                                style="background:<?php echo $s[0]; ?>;color:<?php echo $s[1]; ?>;font-size:0.8rem;">
                                <i class="<?php echo $s[2]; ?> me-1"></i>
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                            <?php if ($app['interview_date']): ?>
                                <div class="card border-0 rounded-3 p-2 text-center" style="background:#e8f5e9;min-width:100px;">
                                    <div class="small fw-bold text-success"><i class="fas fa-calendar-check me-1"></i>Interview</div>
                                    <div class="small text-muted">
                                        <?php echo date('M d, H:i', strtotime($app['interview_date'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <div class="ms-auto d-flex gap-2">
                                <?php if ($app['cv_url'] || $app['profile_cv']): ?>
                                    <a href="<?php echo htmlspecialchars($app['cv_url'] ?: $app['profile_cv']); ?>" target="_blank"
                                        class="btn btn-sm btn-outline-primary rounded-pill">
                                        <i class="fas fa-file-pdf me-1"></i>View CV
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- ─────────────────── POST JOB TAB ────────────────────────── -->
    <?php elseif ($active_tab === 'post'): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="p-4 text-white" style="background:linear-gradient(135deg,#1565C0,#0d47a1);">
                        <h4 class="fw-bold mb-1"><i class="fas fa-plus-circle me-2"></i>Post a Job / Opportunity</h4>
                        <p class="mb-0 text-white-75" style="opacity:0.85;">Reach thousands of qualified candidates in
                            Ethiopia.</p>
                    </div>
                    <form method="POST" class="p-4 p-md-5">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="post_job" value="1">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label class="form-label small fw-bold">Job Title *</label>
                                <input type="text" name="title"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="e.g. Software Developer" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Job Type *</label>
                                <select name="job_type" class="form-select rounded-pill border-0 bg-light px-4 py-3"
                                    required>
                                    <option value="full_time">Full Time</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="contract">Contract</option>
                                    <option value="internship">Internship</option>
                                    <option value="daily_labor">Daily Labor</option>
                                    <option value="freelance">Freelance</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Company Name</label>
                                <input type="text" name="company_name"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="Your company or your name">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Location</label>
                                <input type="text" name="location"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="e.g. Addis Ababa">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <option value="">Select...</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Min Salary (ETB)</label>
                                <input type="number" name="salary_min"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Max Salary (ETB)</label>
                                <input type="number" name="salary_max"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3" placeholder="0">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Experience Level</label>
                                <select name="experience_level"
                                    class="form-select rounded-pill border-0 bg-light px-4 py-3">
                                    <option value="any">Any</option>
                                    <option value="entry">Entry Level</option>
                                    <option value="mid">Mid Level</option>
                                    <option value="senior">Senior</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Application Deadline</label>
                                <input type="date" name="deadline"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    min="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Required Skills (comma-separated)</label>
                                <input type="text" name="skills_required"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="e.g. PHP, MySQL, JavaScript">
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Job Description *</label>
                                <textarea name="description" class="form-control border-0 bg-light px-4 py-3" rows="5"
                                    style="border-radius:15px;"
                                    placeholder="Describe the role, responsibilities, and what you're looking for..."
                                    required></textarea>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Requirements</label>
                                <textarea name="requirements" class="form-control border-0 bg-light px-4 py-3" rows="3"
                                    style="border-radius:15px;"
                                    placeholder="Education, certifications, experience required..."></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                                    <i class="fas fa-paper-plane me-2"></i>Post Job Now
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- ══════════════════════ APPLY MODAL ════════════════════════════ -->
<div class="modal fade" id="applyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 p-4 pb-0">
                <div>
                    <h5 class="fw-bold mb-0" id="applyModalTitle">Apply for Job</h5>
                    <p class="text-muted small mb-0" id="applyModalCompany"></p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" class="modal-body p-4">
                <?php echo csrfField(); ?>
                <input type="hidden" name="apply_job" value="1">
                <input type="hidden" name="job_id" id="applyJobId">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Cover Letter <span
                            class="text-muted fw-normal">(optional)</span></label>
                    <textarea name="cover_letter" class="form-control border-0 bg-light" rows="5"
                        style="border-radius:15px;"
                        placeholder="Introduce yourself and explain why you're the perfect fit..."></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary rounded-pill flex-grow-1 py-2 fw-bold">
                        <i class="fas fa-paper-plane me-2"></i>Submit Application
                    </button>
                    <button type="button" class="btn btn-outline-secondary rounded-pill px-3"
                        data-bs-dismiss="modal">Cancel</button>
                </div>
                <p class="text-center text-muted small mt-3"><i class="fas fa-shield-alt me-1"></i>Your info is private
                    and secure.</p>
            </form>
        </div>
    </div>
</div>

<!-- ══════════════════════ JOB DETAIL MODAL ══════════════════════ -->
<div class="modal fade" id="jobDetailModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 p-4 pb-2">
                <div>
                    <h5 class="fw-bold mb-0" id="detailTitle"></h5>
                    <div class="text-muted small mt-1">
                        <span id="detailCompany"></span> &bull; <span id="detailLocation"></span> &bull; <span
                            id="detailType"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-4">
                    <div class="col-md-8">
                        <h6 class="fw-bold">Job Description</h6>
                        <p id="detailDesc" class="text-muted" style="line-height:1.7;white-space:pre-line;"></p>
                        <h6 class="fw-bold mt-3">Requirements</h6>
                        <p id="detailReq" class="text-muted" style="line-height:1.7;white-space:pre-line;"></p>
                    </div>
                    <div class="col-md-4">
                        <div class="bg-light rounded-4 p-3">
                            <h6 class="fw-bold mb-3">Required Skills</h6>
                            <div id="detailSkills" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer border-0 p-4 pt-0">
                <button type="button" class="btn btn-primary rounded-pill px-4 fw-bold apply-from-detail">
                    <i class="fas fa-paper-plane me-2"></i>Apply for This Job
                </button>
                <button type="button" class="btn btn-outline-secondary rounded-pill"
                    data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ POST SERVICE MODAL ════════════════════ -->
<div class="modal fade" id="postServiceModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 p-4 pb-0">
                <h5 class="fw-bold">Offer a Freelance Service</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info rounded-3 small">
                    <i class="fas fa-info-circle me-1"></i>
                    To offer services, please <a href="../login.php" class="fw-bold">login</a> and complete your
                    profile.
                    Full freelance service management is available in your profile dashboard.
                </div>
                <div class="text-center">
                    <a href="../login.php" class="btn btn-primary rounded-pill px-4">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ══════════════════════ STYLES ═════════════════════════════════ -->
<style>
    .text-white-75 {
        opacity: 0.85;
    }

    .hover-lift {
        transition: all 0.25s ease;
    }

    .hover-lift:hover {
        transform: translateY(-4px);
        box-shadow: 0 1rem 2rem rgba(0, 0, 0, 0.12) !important;
    }

    .job-card {
        transition: all 0.25s ease;
    }

    /* Modern Category Styling */
    .category-list::-webkit-scrollbar {
        width: 4px;
    }

    .category-list::-webkit-scrollbar-thumb {
        background: #e0e0e0;
        border-radius: 10px;
    }

    .category-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 12px;
        text-decoration: none;
        border-radius: 12px;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        margin-bottom: 4px;
        position: relative;
    }

    .category-item:hover {
        background: #f8f9fa;
        transform: translateX(4px);
    }

    .category-item.active {
        background: #e3f2fd;
    }

    .category-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        flex-shrink: 0;
        transition: all 0.3s ease;
    }

    .category-item:hover .category-icon {
        transform: scale(1.1) rotate(5deg);
    }

    .category-name {
        font-size: 0.88rem;
        font-weight: 500;
        color: #444;
        transition: all 0.2s ease;
    }

    .category-item.active .category-name {
        color: #1565C0;
        font-weight: 700;
    }

    .category-indicator {
        position: absolute;
        right: 12px;
        width: 6px;
        height: 6px;
        border-radius: 50%;
        background: #1565C0;
        opacity: 0;
        transform: scale(0);
        transition: all 0.3s ease;
    }

    .category-item.active .category-indicator {
        opacity: 1;
        transform: scale(1);
    }

    .category-item:hover .category-name {
        color: #000;
    }

    /* Premium Category Card Styling */
    .category-card-premium {
        background: white;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        border: 1px solid rgba(0, 0, 0, 0.06) !important;
        text-align: center;
    }

    .category-card-premium:hover {
        background: #fff;
        border-color: #1565C0 !important;
        transform: translateY(-8px);
        box-shadow: 0 15px 30px rgba(21, 101, 192, 0.12) !important;
    }

    .category-card-premium .icon-circle {
        width: 64px;
        height: 64px;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .category-card-premium:hover .icon-circle {
        transform: rotate(-10deg) scale(1.15);
    }

    .nav-link {
        transition: all 0.2s ease;
    }
</style>

<!-- ══════════════════════ SCRIPTS ════════════════════════════════ -->
<script>
    document.addEventListener('DOMContentLoaded', function () {

        // Apply button → open modal
        document.querySelectorAll('.apply-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('applyJobId').value = this.dataset.jobId;
                document.getElementById('applyModalTitle').textContent = this.dataset.jobTitle;
                document.getElementById('applyModalCompany').textContent = this.dataset.company;
                <?php if (!$user_id): ?>
                    window.location.href = '../login.php';
                    return;
                <?php endif; ?>
                new bootstrap.Modal(document.getElementById('applyModal')).show();
            });
        });

        // Detail modal
        document.querySelectorAll('[data-bs-target="#jobDetailModal"]').forEach(btn => {
            btn.addEventListener('click', function () {
                document.getElementById('detailTitle').textContent = this.dataset.title;
                document.getElementById('detailCompany').textContent = this.dataset.company;
                document.getElementById('detailLocation').textContent = this.dataset.location;
                document.getElementById('detailType').textContent = this.dataset.type;
                document.getElementById('detailDesc').textContent = this.dataset.desc;
                document.getElementById('detailReq').textContent = this.dataset.req || 'Not specified';

                const skillsEl = document.getElementById('detailSkills');
                skillsEl.innerHTML = '';
                if (this.dataset.skills) {
                    this.dataset.skills.split(',').forEach(sk => {
                        if (sk.trim()) {
                            const b = document.createElement('span');
                            b.className = 'badge rounded-pill border px-2 py-1';
                            b.style.cssText = 'background:#f5f5f5;color:#555;border-color:#ddd!important;font-size:0.75rem;';
                            b.textContent = sk.trim();
                            skillsEl.appendChild(b);
                        }
                    });
                }

                document.querySelector('.apply-from-detail').dataset.jobId = this.dataset.jobId;
                document.querySelector('.apply-from-detail').dataset.jobTitle = this.dataset.title;
                document.querySelector('.apply-from-detail').dataset.company = this.dataset.company;
            });
        });

        // Apply from detail modal
        document.querySelector('.apply-from-detail')?.addEventListener('click', function () {
            bootstrap.Modal.getInstance(document.getElementById('jobDetailModal'))?.hide();
            setTimeout(() => {
                document.getElementById('applyJobId').value = this.dataset.jobId;
                document.getElementById('applyModalTitle').textContent = this.dataset.jobTitle;
                document.getElementById('applyModalCompany').textContent = this.dataset.company;
                <?php if (!$user_id): ?>
                    window.location.href = '../login.php';
                    return;
                <?php endif; ?>
                new bootstrap.Modal(document.getElementById('applyModal')).show();
            }, 350);
        });
    });
</script>

<?php include '../includes/footer.php'; ?>