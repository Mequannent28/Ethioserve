<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_taxi'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $company_name = sanitize($_POST['company_name']);
        $location = sanitize($_POST['location']);
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
                $username = 'taxi_' . strtolower(str_replace(' ', '', $company_name)) . rand(10, 99);
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'transport')");
                $stmt->execute([$username, $owner_email, $password, $owner_name, $owner_phone]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
                $stmt = $pdo->prepare("UPDATE users SET role = 'transport' WHERE id = ?");
                $stmt->execute([$user_id]);
            }

            // Create Taxi Company
            $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, address, status, rating) VALUES (?, ?, ?, 'approved', 4.5)");
            $stmt->execute([$user_id, $company_name, $location]);
            $company_id = $pdo->lastInsertId();

            $pdo->commit();
            redirectWithMessage('view_taxi.php?id=' . $company_id, 'success', 'Taxi company registered successfully');
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
    <title>Add Taxi Company - Admin Panel</title>
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
        }

        .section-title {
            border-left: 5px solid #FFC107;
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 700;
        }

        .form-control,
        .form-select {
            border-radius: 12px;
            padding: 12px 20px;
            background-color: #f8f9fa;
            border: 2px solid transparent;
        }

        .form-control:focus {
            background-color: #fff;
            border-color: #FFC107;
        }

        .btn-taxi {
            background: #FFC107;
            color: black;
            font-weight: 700;
            padding: 15px 40px;
        }

        .btn-taxi:hover {
            background: #FFA000;
            color: black;
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
                        <h2 class="fw-bold mb-1">Add Taxi Company</h2>
                        <p class="text-muted mb-0">Manually register a new taxi service provider.</p>
                    </div>
                    <a href="manage_taxi.php" class="btn btn-outline-secondary rounded-pill px-4">
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
                    <input type="hidden" name="add_taxi" value="1">

                    <div class="form-section">
                        <h5 class="section-title">Company Details</h5>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted uppercase">Company Name</label>
                                <input type="text" name="company_name" class="form-control"
                                    placeholder="e.g. City Runner Taxi" required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted uppercase">Headquarters /
                                    Location</label>
                                <input type="text" name="location" class="form-control"
                                    placeholder="e.g. Bole Atlas, Addis Ababa" required>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5 class="section-title">Proprietor Details</h5>
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted uppercase">Owner Full Name</label>
                                <input type="text" name="owner_name" class="form-control" placeholder="Full name"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted uppercase">Owner Email</label>
                                <input type="email" name="owner_email" class="form-control" placeholder="owner@taxi.com"
                                    required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted uppercase">Owner Phone</label>
                                <input type="text" name="owner_phone" class="form-control" placeholder="+251 ..."
                                    required>
                            </div>
                            <div class="col-md-12">
                                <label class="form-label small fw-bold text-muted uppercase">Initial Password</label>
                                <input type="text" name="temp_password" class="form-control" value="taxi123">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-end mt-4">
                        <button type="submit" class="btn btn-taxi rounded-pill shadow-sm">
                            <i class="fas fa-car me-2"></i>Register Taxi Company
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>