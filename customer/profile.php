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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <h2 class="fw-bold mb-4">My Profile</h2>

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
                <!-- Personal Info -->
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-user-edit text-primary-green me-2"></i>Personal
                                Information</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="update_profile" value="1">

                                <div class="mb-3">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" class="form-control"
                                        value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Address</label>
                                    <input type="email" name="email" class="form-control"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Phone Number</label>
                                    <input type="text" name="phone" class="form-control"
                                        value="<?php echo htmlspecialchars($user['phone']); ?>">
                                </div>
                                <button type="submit" class="btn btn-primary-green rounded-pill">Update Profile</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Change Password -->
                <div class="col-md-12">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-lock text-warning me-2"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="change_password" value="1">

                                <div class="mb-3">
                                    <label class="form-label">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required>
                                </div>
                                <button type="submit" class="btn btn-warning text-white rounded-pill">Change
                                    Password</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>