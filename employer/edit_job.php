<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('employer');

$user_id = getCurrentUserId();
$job_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if (!$job_id) {
    redirectWithMessage('jobs_management.php', 'error', 'Invalid Job ID');
}

// Get company details
$stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
$company_id = $company['id'];

// Get existing job details
$stmt = $pdo->prepare("SELECT * FROM job_listings WHERE id = ? AND company_id = ?");
$stmt->execute([$job_id, $company_id]);
$job = $stmt->fetch();

if (!$job) {
    redirectWithMessage('jobs_management.php', 'error', 'Job not found or unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $title = sanitize($_POST['title']);
        $description = $_POST['description']; // Keep formatting
        $requirements = $_POST['requirements'];
        $type = sanitize($_POST['type']);
        $category_id = (int) $_POST['category_id'];
        $location = sanitize($_POST['location']);
        $salary_min = $_POST['salary_min'] ? (float) $_POST['salary_min'] : null;
        $salary_max = $_POST['salary_max'] ? (float) $_POST['salary_max'] : null;
        $skills = sanitize($_POST['skills']);
        $experience = sanitize($_POST['experience_level']);
        $deadline = $_POST['deadline'];

        $stmt = $pdo->prepare("
            UPDATE job_listings SET 
                title = ?, description = ?, requirements = ?, job_type = ?, 
                category_id = ?, location = ?, salary_min = ?, salary_max = ?, 
                skills_required = ?, experience_level = ?, deadline = ?
            WHERE id = ? AND company_id = ?
        ");

        if (
            $stmt->execute([
                $title,
                $description,
                $requirements,
                $type,
                $category_id,
                $location,
                $salary_min,
                $salary_max,
                $skills,
                $experience,
                $deadline,
                $job_id,
                $company_id
            ])
        ) {
            redirectWithMessage('jobs_management.php', 'success', 'Job updated successfully');
        } else {
            $error = "Failed to update job.";
        }
    }
}

// Fetch categories for the select input
$categories = $pdo->query("SELECT * FROM job_categories ORDER BY name ASC")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - EthioServe</title>
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

        .form-card {
            border-radius: 20px;
            border: none;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="fw-bold mb-0">Edit Job Posting</h2>
                                <p class="text-muted">Update your listing details</p>
                            </div>
                            <a href="jobs_management.php" class="btn btn-light rounded-pill px-4 shadow-sm border">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                        </div>

                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger rounded-4 mb-4">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <div class="card form-card shadow-sm mb-4">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label class="form-label fw-bold">Job Title</label>
                                            <input type="text" name="title" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($job['title']); ?>" required>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Department / Category</label>
                                            <select name="category_id" class="form-select rounded-pill" required>
                                                <?php foreach ($categories as $cat): ?>
                                                    <option value="<?php echo $cat['id']; ?>" <?php echo $job['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($cat['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Job Type</label>
                                            <select name="type" class="form-select rounded-pill" required>
                                                <option value="full_time" <?php echo $job['job_type'] == 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                                <option value="part_time" <?php echo $job['job_type'] == 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                                <option value="contract" <?php echo $job['job_type'] == 'contract' ? 'selected' : ''; ?>>Contract</option>
                                                <option value="freelance" <?php echo $job['job_type'] == 'freelance' ? 'selected' : ''; ?>>Freelance</option>
                                                <option value="internship" <?php echo $job['job_type'] == 'internship' ? 'selected' : ''; ?>>Internship</option>
                                            </select>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Location</label>
                                            <input type="text" name="location" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($job['location']); ?>"
                                                placeholder="e.g. Addis Ababa or Remote">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Experience level</label>
                                            <select name="experience_level" class="form-select rounded-pill">
                                                <option value="any" <?php echo $job['experience_level'] == 'any' ? 'selected' : ''; ?>>Any Experience</option>
                                                <option value="entry" <?php echo $job['experience_level'] == 'entry' ? 'selected' : ''; ?>>Entry Level</option>
                                                <option value="mid" <?php echo $job['experience_level'] == 'mid' ? 'selected' : ''; ?>>Mid Level</option>
                                                <option value="senior" <?php echo $job['experience_level'] == 'senior' ? 'selected' : ''; ?>>Senior Level</option>
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Salary Min (ETB)</label>
                                            <input type="number" name="salary_min" class="form-control rounded-pill"
                                                value="<?php echo $job['salary_min']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Salary Max (ETB)</label>
                                            <input type="number" name="salary_max" class="form-control rounded-pill"
                                                value="<?php echo $job['salary_max']; ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold">Application Deadline</label>
                                            <input type="date" name="deadline" class="form-control rounded-pill"
                                                value="<?php echo $job['deadline']; ?>">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">Skills Required (Comma separated)</label>
                                            <input type="text" name="skills" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($job['skills_required']); ?>"
                                                placeholder="e.g. PHP, Laravel, MySQL">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">Detailed Description</label>
                                            <textarea name="description" class="form-control rounded-4" rows="8"
                                                required><?php echo htmlspecialchars($job['description']); ?></textarea>
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label fw-bold">Requirements / Qualifications</label>
                                            <textarea name="requirements" class="form-control rounded-4"
                                                rows="5"><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <button type="submit"
                                            class="btn btn-primary-green rounded-pill px-5 py-3 fw-bold">
                                            <i class="fas fa-save me-2"></i>Update Job Listing
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>