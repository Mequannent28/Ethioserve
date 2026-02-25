<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_hotel'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $cuisine = sanitize($_POST['cuisine_type']);
        $owner_name = sanitize($_POST['owner_name']);
        $owner_email = sanitize($_POST['owner_email']);
        $owner_phone = sanitize($_POST['owner_phone']);
        $password = password_hash($_POST['temp_password'] ?: 'welcome123', PASSWORD_DEFAULT);

        try {
            $pdo->beginTransaction();

            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$owner_email]);
            $user = $stmt->fetch();

            if (!$user) {
                $username = strtolower(str_replace(' ', '', $name)) . rand(100, 999);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'hotel')");
                $stmt->execute([$username, $owner_email, $password, $owner_name, $owner_phone]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
                // Update role just in case
                $stmt = $pdo->prepare("UPDATE users SET role = 'hotel' WHERE id = ?");
                $stmt->execute([$user_id]);
            }

            // Create Hotel
            $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, location, cuisine_type, status, rating) VALUES (?, ?, ?, ?, 'approved', 4.5)");
            $stmt->execute([$user_id, $name, $location, $cuisine]);
            $hotel_id = $pdo->lastInsertId();

            $pdo->commit();
            redirectWithMessage('view_hotel.php?id=' . $hotel_id, 'success', 'Hotel registered successfully');
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
    <title>Add New Hotel - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .form-container {
            max-width: 900px;
            margin: 40px auto;
        }

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 35px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
            border: 1px solid rgba(0, 0, 0, 0.02);
        }

        .section-title {
            border-left: 5px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 700;
            color: #2c3e50;
        }

        .form-label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            color: #6c757d;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 20px;
            background-color: #f8f9fa;
            border: 2px solid transparent;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 0.25rem rgba(27, 94, 32, 0.1);
        }

        .btn-submit {
            padding: 15px 40px;
            font-weight: 600;
            letter-spacing: 0.5px;
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
                        <h2 class="fw-bold mb-1">Add New Hotel</h2>
                        <p class="text-muted mb-0">Manually register a new hotel and its owner account.</p>
                    </div>
                    <a href="manage_hotels.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="form-container">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="add_hotel" value="1">

                    <!-- Hotel Details -->
                    <div class="form-section">
                        <h5 class="section-title">Hotel Information</h5>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Hotel Name</label>
                                <input type="text" name="name" class="form-control"
                                    placeholder="e.g. Luxury Plaza Hotel" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location / Address</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="e.g. Bole Road, Addis Ababa" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Cuisine / Service Type</label>
                                <input type="text" name="cuisine_type" class="form-control"
                                    placeholder="e.g. International, Buffet, Local">
                            </div>
                        </div>
                    </div>

                    <!-- Owner Details -->
                    <div class="form-section">
                        <h5 class="section-title">Owner & Account Details</h5>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label fw-bold">Owner Full Name</label>
                                <input type="text" name="owner_name" class="form-control"
                                    placeholder="The legal owner's name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="owner_email" class="form-control"
                                    placeholder="owner@example.com" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="owner_phone" class="form-control" placeholder="+251 ..."
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Temporary Password</label>
                                <input type="text" name="temp_password" class="form-control" value="welcome123">
                                <small class="text-muted">The owner will use this to login initially.</small>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end mt-4 px-3">
                        <button type="reset" class="btn btn-light rounded-pill px-5 fw-bold">Reset Form</button>
                        <button type="submit" class="btn btn-primary-green rounded-pill btn-submit shadow-sm">
                            <i class="fas fa-check-circle me-2"></i>Register Hotel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>