<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$users = $pdo->query("SELECT id, full_name, username FROM users ORDER BY full_name")->fetchAll();
$categories = $pdo->query("SELECT id, name FROM home_service_categories ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_provider'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = (int) $_POST['user_id'];
        $bio = sanitize($_POST['bio']);
        $experience_years = (int) $_POST['experience_years'];
        $degree_type = sanitize($_POST['degree_type']);
        $certification = sanitize($_POST['certification']);
        $location = sanitize($_POST['location']);
        $availability_status = sanitize($_POST['availability_status']);
        $selected_categories = $_POST['categories'] ?? [];

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("INSERT INTO home_service_providers 
                (user_id, bio, experience_years, degree_type, certification, location, availability_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $bio, $experience_years, $degree_type, $certification, $location, $availability_status]);
            $provider_id = $pdo->lastInsertId();

            // Insert into provider_services
            if (!empty($selected_categories)) {
                $stmt = $pdo->prepare("INSERT INTO provider_services (provider_id, category_id) VALUES (?, ?)");
                foreach ($selected_categories as $cat_id) {
                    $stmt->execute([$provider_id, (int) $cat_id]);
                }
            }

            $pdo->commit();
            redirectWithMessage('manage_home.php', 'success', 'Home Service Provider added successfully!');
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Failed to add provider: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Home Service Provider - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #444;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 20px;
            border: 1px solid #e0e0e0;
        }

        .btn-primary {
            background-color: #1B5E20;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
            font-weight: 600;
        }

        .btn-primary:hover {
            background-color: #154d1a;
        }

        .section-title {
            font-size: 1.1rem;
            font-weight: 700;
            color: #1B5E20;
            margin-bottom: 20px;
            border-left: 4px solid #1B5E20;
            padding-left: 15px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="manage_home.php" class="btn btn-white rounded-circle shadow-sm"
                        style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="fw-bold mb-0">Add Home Service Provider</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger rounded-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="card p-4 p-md-5">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="add_provider" value="1">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Link to User Account *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">-- Select User --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?> (@
                                        <?php echo htmlspecialchars($user['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Experience (Years) *</label>
                            <input type="number" name="experience_years" class="form-control" required min="0"
                                value="1">
                        </div>

                        <div class="col-12">
                            <h5 class="section-title mt-3">Professional Details</h5>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Degree / Qualification</label>
                            <input type="text" name="degree_type" class="form-control"
                                placeholder="e.g. B.Sc. in Electrical Engineering">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Certification</label>
                            <input type="text" name="certification" class="form-control"
                                placeholder="e.g. Certified Plumber">
                        </div>

                        <div class="col-12">
                            <label class="form-label">Bio / Description *</label>
                            <textarea name="bio" class="form-control" rows="4" required
                                placeholder="Describe skills and services..."></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Main Location *</label>
                            <input type="text" name="location" class="form-control" required
                                placeholder="e.g. Bole, Addis Ababa">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Availability Status</label>
                            <select name="availability_status" class="form-select">
                                <option value="available">Available</option>
                                <option value="busy">Busy</option>
                                <option value="offline">Offline</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <h5 class="section-title mt-3">Services Provided</h5>
                            <div class="row g-3">
                                <?php foreach ($categories as $cat): ?>
                                    <div class="col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="categories[]"
                                                value="<?php echo $cat['id']; ?>" id="cat_<?php echo $cat['id']; ?>">
                                            <label class="form-check-label" for="cat_<?php echo $cat['id']; ?>">
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-12 mt-5">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-save me-2"></i>Save Provider
                            </button>
                            <a href="manage_home.php" class="btn btn-light rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>