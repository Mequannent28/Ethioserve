<?php
require_once 'includes/functions.php';
require_once 'includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role == 'admin') {
        header("Location: admin/dashboard.php");
    } elseif ($role == 'hotel') {
        header("Location: hotel/dashboard.php");
    } elseif ($role == 'broker') {
        header("Location: broker/dashboard.php");
    } elseif ($role == 'transport') {
        header("Location: transport/dashboard.php");
    } elseif ($role == 'restaurant') {
        header("Location: restaurant/dashboard.php");
    } elseif ($role == 'taxi') {
        header("Location: taxi/dashboard.php");
    } elseif ($role == 'doctor') {
        header("Location: doctor/dashboard.php");
    } else {
        header("Location: customer/index.php");
    }
    exit();
}

$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please try again.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password']; // Don't sanitize password before verification

        if (empty($username) || empty($password)) {
            $error = "Please fill in all fields";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];

                // Determine redirect URL
                $redirect_url = $_SESSION['redirect_after_login'] ?? '';
                unset($_SESSION['redirect_after_login']);

                if (!empty($redirect_url)) {
                    header("Location: $redirect_url");
                } elseif ($redirect === 'cart') {
                    header("Location: customer/cart.php");
                } elseif ($redirect === 'booking') {
                    header("Location: customer/booking.php");
                } elseif ($redirect === 'track_order') {
                    header("Location: customer/track_order.php");
                } else {
                    // Redirect based on role
                    switch ($user['role']) {
                        case 'admin':
                            header("Location: admin/dashboard.php");
                            break;
                        case 'hotel':
                            header("Location: hotel/dashboard.php");
                            break;
                        case 'broker':
                            header("Location: broker/dashboard.php");
                            break;
                        case 'transport':
                            header("Location: transport/dashboard.php");
                            break;
                        case 'restaurant':
                            header("Location: restaurant/dashboard.php");
                            break;
                        case 'taxi':
                            header("Location: taxi/dashboard.php");
                            break;
                        case 'student':
                            header("Location: customer/education.php");
                            break;
                        case 'employer':
                            header("Location: employer/dashboard.php");
                            break;
                        case 'doctor':
                            header("Location: doctor/dashboard.php");
                            break;
                        case 'dating':
                            header("Location: customer/dating.php");
                            break;
                        default:
                            header("Location: customer/index.php");
                    }
                }
                exit();
            } else {
                $error = "Invalid username/email or password";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - EthioServe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>

<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center align-items-center vh-100">
            <div class="col-md-5">
                <div class="card shadow-lg border-0 rounded-4">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <a href="index.php">
                                <h2 class="fw-bold text-primary-green">EthioServe</h2>
                            </a>
                            <p class="text-muted">Welcome back! Please login to your account.</p>
                        </div>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo $error; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" autocomplete="off">
                            <?php echo csrfField(); ?>

                            <div class="mb-3">
                                <label class="form-label">Username or Email</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-user text-muted"></i>
                                    </span>
                                    <input type="text" name="username" class="form-control bg-light border-0" required
                                        placeholder="Enter username or email"
                                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0">
                                        <i class="fas fa-lock text-muted"></i>
                                    </span>
                                    <input type="password" name="password" class="form-control bg-light border-0"
                                        required placeholder="Enter password" id="password">
                                    <button type="button" class="btn btn-light border-0" onclick="togglePassword()">
                                        <i class="fas fa-eye text-muted" id="toggleIcon"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit"
                                class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>

                        <div class="text-center mt-4">
                            <p class="mb-0 text-muted">
                                Don't have an account?
                                <a href="register.php" class="text-primary-green fw-bold text-decoration-none">Sign
                                    Up</a>
                            </p>
                        </div>

                        <!-- Demo Credentials -->
                        <div class="mt-4 p-3 bg-light rounded-3">
                            <small class="text-muted d-block text-center mb-2 fw-bold">Quick Login (Tap to
                                fill):</small>
                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    onclick="fillLogin('customer1','password')">
                                    <i class="fas fa-user me-1"></i>Customer
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                    onclick="fillLogin('hilton_owner','password')">
                                    <i class="fas fa-hotel me-1"></i>Hotel
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('lucy_restaurant','password')">
                                    <i class="fas fa-utensils me-1"></i>Restaurant
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                    onclick="fillLogin('ride_addis','password')">
                                    <i class="fas fa-taxi me-1"></i>Taxi
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-warning rounded-pill px-3"
                                    onclick="fillLogin('broker1','password')">
                                    <i class="fas fa-user-tie me-1"></i>Broker
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3"
                                    onclick="fillLogin('broker1','password')">
                                    <i class="fas fa-home me-1"></i>Rent
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3"
                                    onclick="fillLogin('golden_bus','password')">
                                    <i class="fas fa-bus me-1"></i>Transport
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3"
                                    onclick="fillLogin('student1','password')">
                                    <i class="fas fa-graduation-cap me-1"></i>Student
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('selam_dating','password')">
                                    <i class="fas fa-heart me-1"></i>Dating (Selam)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('dawit_dating','password')">
                                    <i class="fas fa-heart me-1"></i>Dating (Dawit)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('beaza_dating','password')">
                                    <i class="fas fa-heart me-1"></i>Dating (Beaza)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('aman_dating','password')">
                                    <i class="fas fa-heart me-1"></i>Dating (Aman)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('eden_dating','password')">
                                    <i class="fas fa-heart me-1"></i>Dating (Eden)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-dark rounded-pill px-3"
                                    onclick="fillLogin('cloud_company','password')">
                                    <i class="fas fa-building me-1"></i>Employer (Jobs)
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill px-3"
                                    onclick="fillLogin('dr_dawit','password')">
                                    <i class="fas fa-stethoscope me-1"></i>Doctor
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3"
                                    onclick="fillLogin('admin','password')">
                                    <i class="fas fa-shield-alt me-1"></i>Admin
                                </button>
                            </div>
                            <small class="text-muted d-block text-center mt-2">All passwords:
                                <code>password</code></small>
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
        function togglePassword() {
            const password = document.getElementById('password');
            const icon = document.getElementById('toggleIcon');

            if (password.type === 'password') {
                password.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                password.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function fillLogin(username, pass) {
            document.querySelector('input[name="username"]').value = username;
            document.getElementById('password').value = pass;
        }
    </script>
</body>

</html>