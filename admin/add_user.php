<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        $username = sanitize($_POST['username']);
        $role = sanitize($_POST['role']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        try {
            // Check if email or username already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR username = ?");
            $stmt->execute([$email, $username]);
            if ($stmt->fetch()) {
                throw new Exception("Email or Username already exists");
            }

            $stmt = $pdo->prepare("INSERT INTO users (full_name, email, phone, username, role, password) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$full_name, $email, $phone, $username, $role, $password]);

            redirectWithMessage('manage_users.php', 'success', 'User added successfully');
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add User - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
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
            max-width: 800px;
            margin: 0 auto;
        }

        .section-title {
            border-left: 5px solid var(--primary-green);
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
                        <h2 class="fw-bold mb-1">Add New User</h2>
                        <p class="text-muted mb-0">Create a new account for the platform.</p>
                    </div>
                    <a href="manage_users.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to Users
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
                        <input type="hidden" name="add_user" value="1">

                        <h5 class="section-title">User Information</h5>
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" name="full_name"
                                    class="form-control rounded-pill px-4 py-2 border-0 bg-light" required
                                    placeholder="John Doe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" name="username"
                                    class="form-control rounded-pill px-4 py-2 border-0 bg-light" required
                                    placeholder="johndoe">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" name="email"
                                    class="form-control rounded-pill px-4 py-2 border-0 bg-light" required
                                    placeholder="john@example.com">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Phone Number</label>
                                <input type="text" name="phone"
                                    class="form-control rounded-pill px-4 py-2 border-0 bg-light" required
                                    placeholder="+251...">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Role</label>
                                <select name="role" class="form-select rounded-pill px-4 py-2 border-0 bg-light"
                                    required>
                                    <?php $selected_role = $_GET['role'] ?? 'customer'; ?>
                                    <option value="customer" <?php echo $selected_role === 'customer' ? 'selected' : ''; ?>>Customer
                                    </option>
                                    <option value="broker" <?php echo $selected_role === 'broker' ? 'selected' : ''; ?>>
                                        Broker
                                    </option>
                                    <option value="hotel" <?php echo $selected_role === 'hotel' ? 'selected' : ''; ?>>
                                        Hotel Owner
                                    </option>
                                    <option value="transport" <?php echo $selected_role === 'transport' ? 'selected' : ''; ?>>
                                        Transport Company</option>
                                    <option value="agent" <?php echo $selected_role === 'agent' ? 'selected' : ''; ?>>Real
                                        Estate
                                        Agent</option>
                                    <option value="employer" <?php echo $selected_role === 'employer' ? 'selected' : ''; ?>>Employer
                                    </option>
                                    <option value="admin" <?php echo $selected_role === 'admin' ? 'selected' : ''; ?>>
                                        System Admin
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Password</label>
                                <input type="password" name="password"
                                    class="form-control rounded-pill px-4 py-2 border-0 bg-light" required
                                    placeholder="********">
                            </div>
                        </div>

                        <div class="mt-5">
                            <button type="submit"
                                class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-user-plus me-2"></i>Create User Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>