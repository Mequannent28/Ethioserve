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

$categories = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['post_job'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO job_listings 
                (company_id, posted_by, title, description, requirements, job_type, category_id, 
                 location, salary_min, salary_max, salary_period, skills_required, experience_level, deadline, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $company_id,
                $user_id,
                sanitize($_POST['title']),
                $_POST['description'], // Allow rich text if any
                sanitize($_POST['requirements']),
                $_POST['job_type'],
                (int) $_POST['category_id'],
                sanitize($_POST['location']),
                (float) $_POST['salary_min'],
                (float) $_POST['salary_max'],
                $_POST['salary_period'],
                sanitize($_POST['skills_required']),
                $_POST['experience_level'],
                !empty($_POST['deadline']) ? $_POST['deadline'] : null
            ]);

            redirectWithMessage('jobs_management.php', 'success', 'Job posted successfully!');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>

<body class="bg-light">
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1 p-4">
            <div class="container-fluid" style="max-width: 900px;">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="jobs_management.php" class="btn btn-white shadow-sm rounded-circle p-2"
                        style="width:40px; height:40px;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="fw-bold mb-0">Post a New Job</h2>
                </div>

                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="post_job" value="1">

                            <div class="mb-4">
                                <label class="form-label fw-bold">Job Title *</label>
                                <input type="text" name="title" class="form-control rounded-3 py-3" required
                                    placeholder="e.g. Senior PHP Developer">
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Category *</label>
                                    <select name="category_id" class="form-select rounded-3 py-3" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Job Type *</label>
                                    <select name="job_type" class="form-select rounded-3 py-3" required>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                        <option value="freelance">Freelance</option>
                                        <option value="daily_labor">Daily Labor</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Location *</label>
                                    <input type="text" name="location" class="form-control rounded-3 py-3" required
                                        placeholder="e.g. Addis Ababa, Remote">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Experience Level</label>
                                    <select name="experience_level" class="form-select rounded-3 py-3">
                                        <option value="any">Any Experience</option>
                                        <option value="entry">Entry Level</option>
                                        <option value="mid">Mid Level</option>
                                        <option value="senior">Senior Level</option>
                                        <option value="lead">Lead</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Job Description *</label>
                                <textarea name="description" class="form-control rounded-3" rows="8" required
                                    placeholder="Describe the role and responsibilities..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Requirements / Qualifications</label>
                                <textarea name="requirements" class="form-control rounded-3" rows="5"
                                    placeholder="List skills, education, and experience needed..."></textarea>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Skills Required (Comma separated)</label>
                                <input type="text" name="skills_required" class="form-control rounded-3 py-3"
                                    placeholder="PHP, MySQL, Laravel, Git">
                            </div>

                            <div class="row g-4 mb-4">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Min Salary</label>
                                    <input type="number" name="salary_min" class="form-control rounded-3 py-3"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Max Salary</label>
                                    <input type="number" name="salary_max" class="form-control rounded-3 py-3"
                                        placeholder="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Salary Period</label>
                                    <select name="salary_period" class="form-select rounded-3 py-3">
                                        <option value="month">Per Month</option>
                                        <option value="year">Per Year</option>
                                        <option value="hour">Per Hour</option>
                                        <option value="day">Per Day</option>
                                        <option value="project">Per Project</option>
                                    </select>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label fw-bold">Application Deadline</label>
                                <input type="date" name="deadline" class="form-control rounded-3 py-3">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary-green rounded-pill py-3 fw-bold shadow-sm">
                                    <i class="fas fa-paper-plane me-2"></i>Publish Job Listing
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <p class="text-center text-muted small mt-4">By publishing, you agree to EthioServe's Recruitment Terms.
                </p>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>