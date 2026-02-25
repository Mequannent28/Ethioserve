<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

// Fetch users who can be providers
$stmt = $pdo->query("SELECT id, full_name, username, role FROM users ORDER BY full_name");
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_provider'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = !empty($_POST['user_id']) ? (int) $_POST['user_id'] : null;
        $name = sanitize($_POST['name']);
        $type = sanitize($_POST['type']);
        $bio = sanitize($_POST['bio']);
        $location = sanitize($_POST['location']);
        $phone = sanitize($_POST['phone']);
        $rating = (float) $_POST['rating'] ?: 5.0;
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        try {
            $sql = "INSERT INTO health_providers (user_id, name, type, bio, location, phone, rating, is_available) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $name, $type, $bio, $location, $phone, $rating, $is_available]);

            redirectWithMessage('manage_health.php', 'success', 'Health provider added successfully');
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
    <title>Add Health Provider - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            border-left: 5px solid #2196F3;
            padding-left: 15px;
            margin-bottom: 30px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold mb-1">Add Health Provider</h2>
                        <p class="text-muted mb-0">Register a new doctor, pharmacy, or laboratory.</p>
                    </div>
                    <a href="manage_health.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="add_provider" value="1">

                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="section-title">Provider Information</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Provider / Business Name</label>
                                        <input type="text" name="name" class="form-control rounded-3" required
                                            placeholder="e.g. Dr. Dawit Telemed or Central Pharmacy">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Bio / Description</label>
                                        <textarea name="bio" class="form-control rounded-3" rows="5" required
                                            placeholder="Details about services provided..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Provider Type</label>
                                        <select name="type" class="form-select rounded-3">
                                            <option value="doctor">Doctor / Specialist</option>
                                            <option value="pharmacy">Pharmacy</option>
                                            <option value="lab">Laboratory</option>
                                            <option value="clinic">Clinic / Hospital</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Contact Phone</label>
                                        <input type="text" name="phone" class="form-control rounded-3" required
                                            placeholder="09...">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Location</label>
                                        <input type="text" name="location" class="form-control rounded-3" required
                                            placeholder="e.g. Bole, Addis Ababa">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="bg-light p-4 rounded-4 mb-4">
                                    <h5 class="fw-bold mb-3 small text-uppercase text-muted">Account Link</h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Link to User Account</label>
                                        <select name="user_id" class="form-select rounded-3">
                                            <option value="">-- No Account (Business Listing Only) --</option>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?> (@
                                                    <?php echo htmlspecialchars($user['username']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted d-block mt-2">Linking to an account allows the provider
                                            to log in and manage their own dashboard.</small>
                                    </div>
                                </div>

                                <div class="bg-light p-4 rounded-4">
                                    <h5 class="fw-bold mb-3 small text-uppercase text-muted">Status & Rating</h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Initial Rating</label>
                                        <input type="number" step="0.1" name="rating" class="form-control rounded-3"
                                            value="5.0" min="1" max="5">
                                    </div>
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="is_available"
                                            id="isAvailable" checked>
                                        <label class="form-check-label fw-bold" for="isAvailable">Available for
                                            Service</label>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit"
                                        class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-sm"
                                        style="background-color: #2196F3; border-color: #2196F3;">
                                        <i class="fas fa-check-circle me-2"></i>Register Provider
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>