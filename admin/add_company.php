<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_company'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $company_name = sanitize($_POST['company_name']);
        $industry = sanitize($_POST['industry']);
        $location = sanitize($_POST['location']);
        $website = sanitize($_POST['website']);
        $owner_name = sanitize($_POST['owner_name']);
        $owner_email = sanitize($_POST['owner_email']);
        $owner_phone = sanitize($_POST['owner_phone']);
        $password = password_hash($_POST['temp_password'] ?: 'welcome123', PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // 1. Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$owner_email]);
            $user = $stmt->fetch();

            if (!$user) {
                $username = strtolower(str_replace(' ', '', $company_name)) . rand(100, 999);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'employer')");
                $stmt->execute([$username, $owner_email, $password, $owner_name, $owner_phone]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
                // Update role to employer
                $stmt = $pdo->prepare("UPDATE users SET role = 'employer' WHERE id = ?");
                $stmt->execute([$user_id]);
            }

            // 2. Create Company Record
            $stmt = $pdo->prepare("INSERT INTO job_companies (user_id, company_name, industry, location, website, verified) VALUES (?, ?, ?, ?, ?, 1)");
            $stmt->execute([$user_id, $company_name, $industry, $location, $website]);

            $pdo->commit();
            redirectWithMessage('manage_jobs.php', 'success', 'Company and employer account registered successfully');

        } catch (Exception $e) {
            $pdo->rollBack();
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
    <title>Register Company - Admin Panel</title>
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

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-title {
            border-left: 5px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            color: #2c3e50;
        }
    </style>
</head>

<body class="bg-light">
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold mb-1">Add New Company</h2>
                        <p class="text-muted mb-0">Register a new employer and create their company profile.</p>
                    </div>
                    <a href="manage_jobs.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="add_company" value="1">

                    <div class="row">
                        <div class="col-lg-7">
                            <div class="form-section">
                                <h5 class="section-title">Company Profile</h5>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Company Name</label>
                                        <input type="text" name="company_name" class="form-control rounded-3"
                                            placeholder="e.g. Google Ethiopia" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Industry</label>
                                        <select name="industry" class="form-select rounded-3">
                                            <option value="Information Technology">Information Technology</option>
                                            <option value="Healthcare">Healthcare</option>
                                            <option value="Construction">Construction</option>
                                            <option value="Finance & Banking">Finance & Banking</option>
                                            <option value="Education">Education</option>
                                            <option value="Hospitality">Hospitality</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Website</label>
                                        <input type="url" name="website" class="form-control rounded-3"
                                            placeholder="https://...">
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Headquarters / Location</label>
                                        <input type="text" name="location" class="form-control rounded-3"
                                            placeholder="e.g. Addis Ababa, Bole 1st Floor" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="form-section">
                                <h5 class="section-title">Employer Account</h5>
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Recruiter Name</label>
                                        <input type="text" name="owner_name" class="form-control rounded-3"
                                            placeholder="Full Name" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Work Email</label>
                                        <input type="email" name="owner_email" class="form-control rounded-3"
                                            placeholder="hr@company.com" required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Contact Phone</label>
                                        <input type="text" name="owner_phone" class="form-control rounded-3"
                                            placeholder="+251..." required>
                                    </div>
                                    <div class="col-md-12">
                                        <label class="form-label fw-bold">Temporary Password</label>
                                        <input type="text" name="temp_password" class="form-control rounded-3"
                                            value="welcome123">
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-check-circle me-2"></i>Register Company & Employer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>