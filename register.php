<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'] ?? '';
        $full_name = sanitize($_POST['full_name']);
        $phone = sanitize($_POST['phone'] ?? '');
        $role = sanitize($_POST['role']);

        // Validation
        $errors = [];

        // Username validation
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = "Username must be between 3 and 50 characters";
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = "Username can only contain letters, numbers, and underscores";
        }

        // Email validation
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address";
        }

        // Password validation
        if (strlen($password) < 6) {
            $errors[] = "Password must be at least 6 characters";
        }
        if ($password !== $confirm_password) {
            $errors[] = "Passwords do not match";
        }

        // Full name validation
        if (strlen($full_name) < 2) {
            $errors[] = "Please enter your full name";
        }

        // Role validation
        $valid_roles = ['customer', 'hotel', 'broker', 'transport', 'restaurant', 'taxi', 'student'];
        if (!in_array($role, $valid_roles)) {
            $errors[] = "Please select a valid role";
        }

        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = "Username or Email already exists";
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Hash password
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Insert user
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([$username, $email, $hashed_password, $full_name, $phone, $role]);
                $user_id = $pdo->lastInsertId();

                // If hotel role, create pending hotel entry
                if ($role === 'hotel') {
                    $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, status, created_at) VALUES (?, ?, 'pending', NOW())");
                    $stmt->execute([$user_id, $full_name . "'s Hotel"]);
                }

                // If broker role, create broker entry with referral code
                if ($role === 'broker') {
                    $referral_code = generateReferralCode();
                    $stmt = $pdo->prepare("INSERT INTO brokers (user_id, referral_code, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$user_id, $referral_code]);
                }

                // If transport role, create pending transport company entry
                if ($role === 'transport') {
                    $stmt = $pdo->prepare("INSERT INTO transport_companies (user_id, company_name, status, created_at) VALUES (?, ?, 'pending', NOW())");
                    $stmt->execute([$user_id, $full_name . "'s Transport"]);
                }

                // If restaurant role, create pending restaurant entry
                if ($role === 'restaurant') {
                    $stmt = $pdo->prepare("INSERT INTO restaurants (user_id, name, status, created_at) VALUES (?, ?, 'pending', NOW())");
                    $stmt->execute([$user_id, $full_name . "'s Restaurant"]);
                }

                // If taxi role, create pending taxi company entry
                if ($role === 'taxi') {
                    $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, status, created_at) VALUES (?, ?, 'pending', NOW())");
                    $stmt->execute([$user_id, $full_name . "'s Taxi"]);
                }

                $pdo->commit();

                $success = "Registration successful! You can now login with your credentials.";

                // Clear form data
                $_POST = [];

            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Registration failed. Please try again.";
            }
        } else {
            $error = implode("<br>", $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - EthioServe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <a href="index.php">
                                <h2 class="fw-bold text-primary-green">Join EthioServe</h2>
                            </a>
                            <p class="text-muted">Create your account and start exploring.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <?php echo csrfField(); ?>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-user text-muted"></i>
                                        </span>
                                        <input type="text" name="full_name" class="form-control bg-light border-0"
                                            required placeholder="John Doe"
                                            value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Username</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-at text-muted"></i>
                                        </span>
                                        <input type="text" name="username" class="form-control bg-light border-0"
                                            required placeholder="johndoe"
                                            value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                                            pattern="[a-zA-Z0-9_]+" title="Letters, numbers, and underscores only">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-envelope text-muted"></i>
                                    </span>
                                    <input type="email" name="email" class="form-control bg-light border-0" required
                                        placeholder="john@example.com"
                                        value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number (Optional)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-phone text-muted"></i>
                                    </span>
                                    <input type="tel" name="phone" class="form-control bg-light border-0"
                                        placeholder="+251 9XX XXX XXX"
                                        value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" name="password" class="form-control bg-light border-0"
                                            required placeholder="Min 6 characters" id="password" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-0">
                                            <i class="fas fa-lock text-muted"></i>
                                        </span>
                                        <input type="password" name="confirm_password"
                                            class="form-control bg-light border-0" required
                                            placeholder="Confirm password" id="confirm_password" minlength="6">
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold">Register As</label>
                                <div class="row g-2">
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleCustomer"
                                            value="customer" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'customer') ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleCustomer">
                                            <i class="fas fa-user d-block mb-1 fs-4"></i>
                                            <small>Customer</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleHotel" value="hotel"
                                            <?php echo ($_POST['role'] ?? '') === 'hotel' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleHotel">
                                            <i class="fas fa-hotel d-block mb-1 fs-4"></i>
                                            <small>Hotel Owner</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleRestaurant"
                                            value="restaurant" <?php echo ($_POST['role'] ?? '') === 'restaurant' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleRestaurant">
                                            <i class="fas fa-utensils d-block mb-1 fs-4"></i>
                                            <small>Restaurant</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleTaxi" value="taxi"
                                            <?php echo ($_POST['role'] ?? '') === 'taxi' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleTaxi">
                                            <i class="fas fa-taxi d-block mb-1 fs-4"></i>
                                            <small>Taxi</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleBroker" value="broker"
                                            <?php echo ($_POST['role'] ?? '') === 'broker' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleBroker">
                                            <i class="fas fa-user-tie d-block mb-1 fs-4"></i>
                                            <small>Broker</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleTransport"
                                            value="transport" <?php echo ($_POST['role'] ?? '') === 'transport' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleTransport">
                                            <i class="fas fa-bus d-block mb-1 fs-4"></i>
                                            <small>Transport</small>
                                        </label>
                                    </div>
                                    <div class="col-6 col-md-4 col-lg">
                                        <input type="radio" class="btn-check" name="role" id="roleStudent"
                                            value="student" <?php echo ($_POST['role'] ?? '') === 'student' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green w-100 py-3 rounded-3"
                                            for="roleStudent">
                                            <i class="fas fa-graduation-cap d-block mb-1 fs-4"></i>
                                            <small>Student</small>
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-user-plus me-2"></i>Sign Up
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0 text-muted">
                                Already have an account?
                                <a href="login.php" class="text-primary-green fw-bold text-decoration-none">Login</a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="text-center py-4 mt-auto">
        <p class="mb-1 text-muted small">&copy; 2026 EthioServe Platform. All rights reserved.</p>
        <p class="text-muted small">Developed by <a
                href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
                class="fw-bold text-primary-green text-decoration-none">Mequannent Gashaw Asinake</a> with ❤️ in
            Ethiopia</p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Password confirmation validation
        document.querySelector('form').addEventListener('submit', function (e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>

</html>