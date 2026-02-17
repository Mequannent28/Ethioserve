<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireLogin();
requireRole('admin');

$user_id = getCurrentUserId();
$success_msg = '';
$error_msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);
            $_SESSION['user_name'] = $full_name;
            $success_msg = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error_msg = "Error: " . $e->getMessage();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $current = $_POST['current_password'];
        $new = $_POST['new_password'];
        $confirm = $_POST['confirm_password'];

        if ($new !== $confirm) {
            $error_msg = "Passwords do not match.";
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            if (password_verify($current, $user['password'])) {
                $hash = password_hash($new, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hash, $user_id]);
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Incorrect current password.";
            }
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f4f6f9;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <h2 class="fw-bold mb-4">Edit Profile</h2>
            <?php if ($success_msg): ?>
                <div class="alert alert-success">
                    <?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary-green me-2"></i>Admin Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="update_profile" value="1">
                                <div class="mb-3"><label class="form-label">Full Name</label><input type="text"
                                        name="full_name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Email</label><input type="email"
                                        name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required></div>
                                <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone"
                                        class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary-green w-100 rounded-pill">Update
                                    Profile</button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-lock text-warning me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="change_password" value="1">
                                <div class="mb-3"><label class="form-label">Current Password</label><input
                                        type="password" name="current_password" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">New Password</label><input type="password"
                                        name="new_password" class="form-control" required></div>
                                <div class="mb-3"><label class="form-label">Confirm</label><input type="password"
                                        name="confirm_password" class="form-control" required></div>
                                <button type="submit" class="btn btn-warning text-white w-100 rounded-pill">Change
                                    Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>