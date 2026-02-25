<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('employer');

$user_id = getCurrentUserId();

// Get company details
$stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    // Should not happen if registered correctly, but handle gracefully
    $pdo->prepare("INSERT INTO job_companies (user_id, company_name) VALUES (?, ?)")
        ->execute([$user_id, getCurrentUserName() . "'s Company"]);

    $stmt = $pdo->prepare("SELECT * FROM job_companies WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $company = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $company_name = sanitize($_POST['company_name']);
        $industry = sanitize($_POST['industry']);
        $location = sanitize($_POST['location']);
        $website = sanitize($_POST['website']);
        $size = sanitize($_POST['size']);
        $description = $_POST['description']; // Keep HTML if needed, but sanitize later if displaying

        // Handle Logo Upload
        $logo_url = $company['logo_url'];
        if (isset($_FILES['logo']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../assets/uploads/logos/';
            if (!is_dir($upload_dir))
                mkdir($upload_dir, 0777, true);

            $file_ext = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $file_name = 'logo_' . $company['id'] . '_' . time() . '.' . $file_ext;
            $target_file = $upload_dir . $file_name;

            if (move_uploaded_file($_FILES['logo']['tmp_name'], $target_file)) {
                $logo_url = 'assets/uploads/logos/' . $file_name;
            }
        }

        $stmt = $pdo->prepare("
            UPDATE job_companies 
            SET company_name = ?, industry = ?, location = ?, website = ?, size = ?, description = ?, logo_url = ?
            WHERE id = ?
        ");

        if ($stmt->execute([$company_name, $industry, $location, $website, $size, $description, $logo_url, $company['id']])) {
            redirectWithMessage('company_profile.php', 'success', 'Company profile updated successfully');
        } else {
            $error = "Failed to update company profile.";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Company Profile - EthioServe</title>
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

        .profile-card {
            border-radius: 20px;
            border: none;
            overflow: hidden;
        }

        .logo-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 20px;
            border: 4px solid #fff;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper d-flex">
        <?php include('../includes/sidebar_employer.php'); ?>

        <div class="main-content flex-grow-1">
            <?php echo displayFlashMessage(); ?>

            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-lg-10">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="fw-bold mb-0">Company Profile</h2>
                                <p class="text-muted">Manage your business brand and public info</p>
                            </div>
                            <a href="dashboard.php" class="btn btn-light rounded-pill px-4 shadow-sm border">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>

                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <div class="card profile-card shadow-sm mb-4">
                                <div class="card-body p-4 p-lg-5">
                                    <div class="row g-4 align-items-center mb-5">
                                        <div class="col-auto">
                                            <img src="<?php echo $company['logo_url'] ? BASE_URL . $company['logo_url'] : 'https://ui-avatars.com/api/?name=' . urlencode($company['company_name']) . '&size=200'; ?>"
                                                class="logo-preview" id="logoPreview">
                                        </div>
                                        <div class="col">
                                            <h4 class="fw-bold mb-2">Company Logo</h4>
                                            <p class="text-muted small mb-3">Upload a high-quality logo for your company
                                                profile.</p>
                                            <input type="file" name="logo" class="form-control rounded-pill"
                                                accept="image/*" onchange="previewImage(this)">
                                        </div>
                                    </div>

                                    <div class="row g-4">
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Company Name</label>
                                            <input type="text" name="company_name" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($company['company_name']); ?>"
                                                required>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Industry</label>
                                            <select name="industry" class="form-select rounded-pill">
                                                <?php
                                                $industries = ['Technology', 'Healthcare', 'Education', 'Construction', 'Finance', 'Manufacturing', 'Retail', 'Hospitality', 'Other'];
                                                foreach ($industries as $ind) {
                                                    $selected = ($company['industry'] === $ind) ? 'selected' : '';
                                                    echo "<option value=\"$ind\" $selected>$ind</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Headquarters Location</label>
                                            <input type="text" name="location" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($company['location'] ?? ''); ?>"
                                                placeholder="e.g. Addis Ababa, Ethiopia">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Website URL</label>
                                            <input type="url" name="website" class="form-control rounded-pill"
                                                value="<?php echo htmlspecialchars($company['website'] ?? ''); ?>"
                                                placeholder="https://www.example.com">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label fw-bold">Company Size</label>
                                            <select name="size" class="form-select rounded-pill">
                                                <?php
                                                $sizes = ['1-10', '11-50', '51-200', '201-500', '500+'];
                                                foreach ($sizes as $s) {
                                                    $selected = ($company['size'] === $s) ? 'selected' : '';
                                                    echo "<option value=\"$s\" $selected>$s employees</option>";
                                                }
                                                ?>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label fw-bold">Company Bio / Description</label>
                                            <textarea name="description" class="form-control rounded-4" rows="6"
                                                placeholder="Tell candidates about your company culture, mission, and what it's like to work with you..."><?php echo htmlspecialchars($company['description'] ?? ''); ?></textarea>
                                        </div>
                                    </div>

                                    <div class="mt-5">
                                        <button type="submit"
                                            class="btn btn-primary-green rounded-pill px-5 py-3 fw-bold">
                                            <i class="fas fa-save me-2"></i>Save Changes
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
    <script>
        function previewImage(input) {
            if (input.files && input.files[0]) {
                var reader = new FileReader();
                reader.onload = function (e) {
                    document.getElementById('logoPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
    </script>
</body>

</html>