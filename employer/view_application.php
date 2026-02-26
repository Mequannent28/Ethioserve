<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('employer');

$user_id = getCurrentUserId();
$application_id = intval($_GET['id'] ?? 0);

if (!$application_id) {
    redirectWithMessage('dashboard.php', 'danger', 'Invalid application ID.');
}

// Fetch application details
$stmt = $pdo->prepare("
    SELECT ja.*, jl.title as job_title, jl.location as job_location, jl.job_type,
           u.full_name as applicant_name, u.email as applicant_email, u.phone as applicant_phone,
           jp.profile_pic, jp.headline, jp.bio, jp.skills, jp.experience_years, jp.education, jp.availability, jp.cv_url as profile_cv,
           jp.location
    FROM job_applications ja
    JOIN job_listings jl ON ja.job_id = jl.id
    JOIN job_companies jc ON jl.company_id = jc.id
    JOIN users u ON ja.applicant_id = u.id
    LEFT JOIN job_profiles jp ON u.id = jp.user_id
    WHERE ja.id = ? AND jc.user_id = ?
");
$stmt->execute([$application_id, $user_id]);
$app = $stmt->fetch();

if (!$app) {
    redirectWithMessage('dashboard.php', 'danger', 'Application not found or access denied.');
}

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $status = sanitize($_POST['status']);
        $notes = sanitize($_POST['notes']);
        $interview_date = !empty($_POST['interview_date']) ? $_POST['interview_date'] : null;

        $stmt = $pdo->prepare("UPDATE job_applications SET status = ?, notes = ?, interview_date = ? WHERE id = ?");
        $stmt->execute([$status, $notes, $interview_date, $application_id]);

        // Send Email
        sendJobApplicationEmail($pdo, $application_id);

        redirectWithMessage('view_application.php?id=' . $application_id, 'success', 'Application updated successfully.');
    }
}

$page_title = "Application Review - " . $app['applicant_name'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo $page_title; ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 240px;
            padding: 40px;
            min-height: 100vh;
        }

        .card-profile {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .profile-img {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 30px;
            border: 5px solid #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .badge-status {
            padding: 8px 16px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.8rem;
        }

        .section-title {
            border-left: 4px solid #1B5E20;
            padding-left: 15px;
            margin-bottom: 20px;
            color: #1B5E20;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9rem;
            letter-spacing: 1px;
        }

        .skill-tag {
            background: #e8f5e9;
            color: #2e7d32;
            padding: 6px 14px;
            border-radius: 50px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 8px;
            margin-bottom: 8px;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content">
            <div class="container-fluid">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-1">
                                <li class="breadcrumb-item"><a href="dashboard.php"
                                        class="text-decoration-none text-muted">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="applications.php"
                                        class="text-decoration-none text-muted">Applications</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Review</li>
                            </ol>
                        </nav>
                        <h2 class="fw-bold mb-0">Detailed Application View</h2>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="applications.php" class="btn btn-outline-secondary rounded-pill px-4">
                            <i class="fas fa-arrow-left me-2"></i>Back to List
                        </a>
                        <?php
                        $unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM job_messages WHERE application_id = ? AND receiver_id = ? AND is_read = 0");
                        $unread_stmt->execute([$application_id, $user_id]);
                        $unread_count = $unread_stmt->fetchColumn();
                        ?>
                        <a href="../customer/job_chat.php?application_id=<?php echo $app['id']; ?>"
                            class="btn <?php echo $unread_count > 0 ? 'btn-danger' : 'btn-primary'; ?> rounded-pill px-4 shadow-sm position-relative">
                            <i
                                class="fas fa-comments me-2"></i><?php echo $unread_count > 0 ? 'Reply to Candidate' : 'Start Chat'; ?>
                            <?php if ($unread_count > 0): ?>
                                <span
                                    class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger border border-light">
                                    <?php echo $unread_count; ?>
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>

                <div class="row g-4">
                    <!-- Left Column: Profile -->
                    <div class="col-lg-4">
                        <div class="card card-profile h-100 p-4 text-center bg-white">
                            <div class="mb-4">
                                <img src="<?php echo $app['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($app['applicant_name']) . '&size=200&background=1B5E20&color=fff'; ?>"
                                    class="profile-img">
                            </div>
                            <h3 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($app['applicant_name']); ?>
                            </h3>
                            <p class="text-primary fw-medium mb-3">
                                <?php echo htmlspecialchars($app['headline'] ?: 'Candidate'); ?>
                            </p>

                            <div class="d-flex justify-content-center gap-2 mb-4">
                                <?php echo getStatusBadge($app['status']); ?>
                                <span class="badge bg-light text-dark border rounded-pill py-2 px-3">
                                    <?php echo $app['experience_years']; ?>+ Yrs Exp
                                </span>
                            </div>

                            <hr class="my-4 opacity-25">

                            <div class="text-start mb-4">
                                <h6 class="section-title">Contact Information</h6>
                                <p class="mb-2"><i class="fas fa-envelope text-muted me-3"></i>
                                    <?php echo htmlspecialchars($app['applicant_email']); ?>
                                </p>
                                <p class="mb-2"><i class="fas fa-phone text-muted me-3"></i>
                                    <?php echo htmlspecialchars($app['applicant_phone'] ?: 'Not Provided'); ?>
                                </p>
                                <p class="mb-0"><i class="fas fa-map-marker-alt text-muted me-3"></i>
                                    <?php echo htmlspecialchars(($app['location'] ?? '') ?: 'Not Disclosed'); ?>
                                </p>
                            </div>

                            <div class="d-grid gap-2">
                                <?php if ($app['cv_url'] || $app['profile_cv']): ?>
                                    <a href="<?php echo BASE_URL . ($app['cv_url'] ?: $app['profile_cv']); ?>"
                                        target="_blank" class="btn btn-outline-primary rounded-pill py-2 fw-bold">
                                        <i class="fas fa-file-download me-2"></i>Download Portfolio/CV
                                    </a>
                                <?php endif; ?>
                                <a href="mailto:<?php echo $app['applicant_email']; ?>"
                                    class="btn btn-light rounded-pill py-2">
                                    <i class="fas fa-paper-plane me-2"></i>Send Direct Email
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Details & Manage -->
                    <div class="col-lg-8">
                        <div class="card card-profile p-4 bg-white mb-4">
                            <h6 class="section-title">Background & Bio</h6>
                            <p class="text-muted small leading-relaxed mb-4">
                                <?php echo nl2br(htmlspecialchars($app['bio'] ?: 'The candidate hasn\'t provided a professional biography yet.')); ?>
                            </p>

                            <h6 class="section-title">Professional Assets</h6>
                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <p class="small text-muted mb-1">Highest Education</p>
                                    <p class="fw-bold">
                                        <?php echo htmlspecialchars($app['education'] ?: 'Self-taught / Professional Exp'); ?>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p class="small text-muted mb-1">Availability</p>
                                    <p class="fw-bold">
                                        <?php echo ucfirst(str_replace('_', ' ', $app['availability'] ?: 'immediately')); ?>
                                    </p>
                                </div>
                                <div class="col-12">
                                    <p class="small text-muted mb-2">Technical Proficiencies</p>
                                    <div class="d-flex flex-wrap">
                                        <?php
                                        $skills = explode(',', $app['skills'] ?? '');
                                        foreach ($skills as $skill):
                                            if (trim($skill)): ?>
                                                <span class="skill-tag">
                                                    <?php echo htmlspecialchars(trim($skill)); ?>
                                                </span>
                                            <?php endif; endforeach; ?>
                                        <?php if (empty(array_filter($skills))): ?>
                                            <span class="text-muted small italic">No specific skills listed.</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <p class="small text-muted mb-2">Cover Letter / Message</p>
                                    <div class="bg-light p-3 rounded-3 small">
                                        <?php echo nl2br(htmlspecialchars($app['cover_letter'] ?: 'No message attached to this application.')); ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Management Card -->
                        <div class="card card-profile p-4 bg-white">
                            <h6 class="section-title">Manage Application Status</h6>
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="update_status" value="1">

                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold text-muted">Current Application
                                            Phase</label>
                                        <select name="status" class="form-select rounded-pill"
                                            onchange="toggleInterviewField(this.value)">
                                            <option value="pending" <?php echo $app['status'] == 'pending' ? 'selected' : ''; ?>>New Application</option>
                                            <option value="shortlisted" <?php echo $app['status'] == 'shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                            <option value="interviewed" <?php echo $app['status'] == 'interviewed' ? 'selected' : ''; ?>>Invite to Interview</option>
                                            <option value="hired" <?php echo $app['status'] == 'hired' ? 'selected' : ''; ?>>Hire Recommendation</option>
                                            <option value="rejected" <?php echo $app['status'] == 'rejected' ? 'selected' : ''; ?>>Mark as Rejected</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6" id="interviewField"
                                        style="<?php echo $app['status'] == 'interviewed' ? '' : 'display:none;'; ?>">
                                        <label class="form-label small fw-bold text-muted">Interview Date & Time</label>
                                        <input type="datetime-local" name="interview_date"
                                            class="form-control rounded-pill"
                                            value="<?php echo $app['interview_date'] ? date('Y-m-d\TH:i', strtotime($app['interview_date'])) : ''; ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label small fw-bold text-muted">Internal Feedback (Visible to
                                            Team)</label>
                                        <textarea name="notes" class="form-control rounded-4" rows="3"
                                            placeholder="Add your evaluation or next steps here..."><?php echo htmlspecialchars($app['notes'] ?? ''); ?></textarea>
                                    </div>
                                    <div class="col-12 text-end">
                                        <button type="submit" class="btn btn-primary rounded-pill px-5 shadow-sm">Save
                                            Evolution</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleInterviewField(status) {
            const field = document.getElementById('interviewField');
            field.style.display = (status === 'interviewed') ? 'block' : 'none';
        }
    </script>
</body>

</html>