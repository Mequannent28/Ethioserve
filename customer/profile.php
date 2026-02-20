<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireLogin();

$user_id = getCurrentUserId();
$success_msg = '';
$error_msg = '';

// Handle Profile Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $full_name = sanitize($_POST['full_name']);
        $email = sanitize($_POST['email']);
        $phone = sanitize($_POST['phone']);

        // Update users table
        try {
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$full_name, $email, $phone, $user_id]);

            // Update session name if changed
            $_SESSION['user_name'] = $full_name;
            $success_msg = "Profile updated successfully!";
        } catch (PDOException $e) {
            $error_msg = "Error updating profile: " . $e->getMessage();
        }
    }
}

// Handle Password Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        if ($new_password !== $confirm_password) {
            $error_msg = "New passwords do not match.";
        } else {
            // Verify current password
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (password_verify($current_password, $user['password'])) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
                $stmt->execute([$hashed_password, $user_id]);
                $success_msg = "Password changed successfully!";
            } else {
                $error_msg = "Incorrect current password.";
            }
        }
    }
}

// Fetch current user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

include('../includes/header.php');
?>

<style>
    body {
        background-color: #0d121f;
        color: #fff;
    }

    .profile-header-premium {
        background: linear-gradient(135deg, #151b2b, #0d121f);
        padding: 40px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        margin-bottom: 30px;
    }

    .profile-avatar-big {
        width: 100px;
        height: 100px;
        background: #FFD600;
        color: #1B5E20;
        font-size: 2.5rem;
        font-weight: 800;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        margin: 0 auto 15px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        border: 4px solid rgba(255, 255, 255, 0.1);
    }

    .account-card {
        background: #151b2b;
        border: 1px solid rgba(255, 255, 255, 0.05);
        border-radius: 20px;
        padding: 25px;
        margin-bottom: 20px;
        transition: 0.3s;
    }

    .form-control {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        color: #fff;
        border-radius: 12px;
        padding: 12px 15px;
    }

    .form-control:focus {
        background: rgba(255, 255, 255, 0.08);
        border-color: #5091f2;
        color: #fff;
        box-shadow: none;
    }

    .form-label {
        font-size: 0.9rem;
        font-weight: 600;
        color: rgba(255, 255, 255, 0.6);
        margin-bottom: 8px;
    }

    .btn-premium {
        background: #5091f2;
        color: #fff;
        border: none;
        border-radius: 50px;
        padding: 12px 30px;
        font-weight: 700;
        transition: 0.3s;
        width: 100%;
    }

    .btn-premium:hover {
        background: #4081e2;
        transform: translateY(-2px);
    }

    .account-section-title {
        font-size: 1.1rem;
        font-weight: 800;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .account-section-title i {
        color: #5091f2;
    }
</style>

<div class="profile-header-premium">
    <div class="profile-avatar-big">
        <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
    </div>
    <h3 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h3>
    <p class="text-white-50 small mb-0"><?php echo htmlspecialchars($user['email']); ?></p>
</div>

<div class="container pb-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <?php if ($success_msg): ?>
                <div class="alert alert-success bg-success bg-opacity-10 border-success text-success rounded-4 mb-4">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success_msg; ?>
                </div>
            <?php endif; ?>
            <?php if ($error_msg): ?>
                <div class="alert alert-danger bg-danger bg-opacity-10 border-danger text-danger rounded-4 mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_msg; ?>
                </div>
            <?php endif; ?>

            <div class="account-card">
                <h5 class="account-section-title"><i class="fas fa-user-circle"></i>Personal Details</h5>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="update_profile" value="1">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Full Name</label>
                            <input type="text" name="full_name" class="form-control"
                                value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Phone Number</label>
                            <input type="text" name="phone" class="form-control"
                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control"
                                value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-premium shadow">Save Changes</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="account-card">
                <h5 class="account-section-title"><i class="fas fa-shield-alt"></i>Security</h5>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="change_password" value="1">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label">Current Password</label>
                            <input type="password" name="current_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">New Password</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm New Password</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-premium shadow"
                                style="background: #151b2b; border: 1px solid rgba(255,255,255,0.1);">Update
                                Password</button>
                        </div>
                    </div>
                </form>
            </div>

            <div class="text-center mt-4">
                <a href="../logout.php" class="text-danger text-decoration-none fw-bold small">
                    <i class="fas fa-sign-out-alt me-1"></i>Sign Out of Account
                </a>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>