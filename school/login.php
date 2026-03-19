<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Redirect if already logged in
if (isLoggedIn()) {
    $role = getCurrentUserRole();
    if ($role === 'teacher') {
        header("Location: ../teacher/dashboard.php");
    } elseif ($role === 'student' || $role === 'parent') {
        header("Location: ../customer/school_portal.php");
    } elseif ($role === 'admin') {
        header("Location: ../admin/manage_school.php");
    } else {
        header("Location: ../customer/index.php");
    }
    exit();
}

$error   = '';
$success = '';
$allowed_roles = ['teacher', 'student', 'parent', 'admin', 'school_admin'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Invalid security token. Please refresh and try again.";
    } else {
        $username = sanitize($_POST['username']);
        $password = $_POST['password'];

        if (empty($username) || empty($password)) {
            $error = "Please enter your username/email and password.";
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?)");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                if (!in_array($user['role'], $allowed_roles)) {
                    $error = "This portal is for School users only. <a href='../login.php' class='text-warning'>Go to main login →</a>";
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']       = $user['id'];
                    $_SESSION['username']      = $user['username'];
                    $_SESSION['role']          = $user['role'];
                    $_SESSION['full_name']     = $user['full_name'];
                    $_SESSION['email']         = $user['email'];
                    $_SESSION['profile_photo'] = $user['profile_photo'] ?? null;

                    logActivity('Login', 'School portal login - ' . strtoupper($user['role']), $user['id']);

                    switch ($user['role']) {
                        case 'teacher':      header("Location: ../teacher/dashboard.php"); break;
                        case 'admin':        header("Location: ../admin/manage_school.php"); break;
                        case 'school_admin': header("Location: ../admin/manage_school.php"); break;
                        case 'student':      header("Location: ../student/dashboard.php"); break;
                        case 'parent':       header("Location: ../student/dashboard.php"); break;
                        default:             header("Location: ../customer/index.php"); break;
                    }
                    exit();
                }
            } else {
                $error = "Invalid credentials. Please try again.";
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
    <title>School Portal Login — EthioServe Education</title>
    <meta name="description" content="Login to EthioServe School Portal for Teachers, Students, and Parents.">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --green-dark:  #1B5E20;
            --green-mid:   #2E7D32;
            --gold:        #F9A825;
            --gold-light:  #FFF8E1;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            background: #f0f4f8;
        }

        /* ── LEFT PANEL ─────────────────────────────── */
        .left-panel {
            flex: 1;
            background: linear-gradient(145deg, var(--green-dark) 0%, #388E3C 60%, #1B5E20 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 3rem;
            position: relative;
            overflow: hidden;
        }
        .left-panel::before {
            content: '';
            position: absolute;
            width: 600px; height: 600px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
            top: -150px; left: -150px;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            background: rgba(249,168,37,0.08);
            border-radius: 50%;
            bottom: -100px; right: -100px;
        }
        .brand-logo {
            font-size: 3rem;
            font-weight: 800;
            color: #fff;
            letter-spacing: -1px;
            position: relative;
            z-index: 1;
        }
        .brand-logo span { color: var(--gold); }
        .brand-tagline {
            color: rgba(255,255,255,0.75);
            font-size: 1rem;
            margin-top: 0.5rem;
            text-align: center;
            position: relative;
            z-index: 1;
        }
        .role-pill {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            color: #fff;
            border-radius: 50px;
            padding: 8px 20px;
            font-size: 0.85rem;
            font-weight: 500;
            backdrop-filter: blur(4px);
            position: relative;
            z-index: 1;
        }
        .highlight-stats {
            display: flex;
            gap: 2rem;
            margin-top: 3rem;
            position: relative;
            z-index: 1;
        }
        .stat-item { text-align: center; color: #fff; }
        .stat-item .num { font-size: 2rem; font-weight: 800; color: var(--gold); line-height: 1; }
        .stat-item .lbl { font-size: 0.75rem; opacity: 0.7; margin-top: 4px; }

        /* ── RIGHT PANEL ─────────────────────────────── */
        .right-panel {
            width: 480px;
            background: #fff;
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 3rem;
            box-shadow: -10px 0 40px rgba(0,0,0,0.08);
        }
        .portal-title {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1a1a2e;
        }
        .portal-title span { color: var(--green-dark); }

        .form-floating label { color: #9e9e9e; font-size: 0.9rem; }
        .form-floating .form-control {
            border: 2px solid #f0f0f0;
            border-radius: 14px;
            font-size: 0.95rem;
            background: #fafafa;
            transition: all 0.3s;
        }
        .form-floating .form-control:focus {
            border-color: var(--green-dark);
            background: #fff;
            box-shadow: 0 0 0 4px rgba(27,94,32,0.08);
        }

        .btn-school-login {
            background: linear-gradient(135deg, var(--green-dark), #388E3C);
            color: #fff;
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-size: 1rem;
            font-weight: 700;
            letter-spacing: 0.5px;
            transition: all 0.3s;
            box-shadow: 0 6px 20px rgba(27,94,32,0.35);
        }
        .btn-school-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 28px rgba(27,94,32,0.45);
            color: #fff;
        }
        .btn-school-login:active { transform: translateY(0); }

        /* Quick Login Buttons */
        .quick-login-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .ql-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s;
            border: 2px solid;
        }
        .ql-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.12); }

        .ql-teacher  { border-color: #1B5E20; color: #1B5E20; background: #e8f5e9; }
        .ql-teacher:hover  { background: #1B5E20; color: #fff; }

        .ql-student  { border-color: #1565C0; color: #1565C0; background: #e3f2fd; }
        .ql-student:hover  { background: #1565C0; color: #fff; }

        .ql-parent   { border-color: #E65100; color: #E65100; background: #fff3e0; }
        .ql-parent:hover   { background: #E65100; color: #fff; }

        .ql-admin    { border-color: #6A1B9A; color: #6A1B9A; background: #f3e5f5; }
        .ql-admin:hover    { background: #6A1B9A; color: #fff; }

        .divider {
            display: flex; align-items: center; gap: 12px;
            color: #bdbdbd; font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: ''; flex: 1; height: 1px; background: #f0f0f0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            body { flex-direction: column; }
            .left-panel { padding: 2rem; min-height: 220px; }
            .right-panel { width: 100%; padding: 2rem; }
            .highlight-stats { gap: 1.5rem; }
        }

        /* Password toggle */
        .pw-wrapper { position: relative; }
        .pw-toggle {
            position: absolute; right: 14px; top: 50%;
            transform: translateY(-50%);
            cursor: pointer; color: #9e9e9e; z-index: 5;
            background: none; border: none; padding: 0;
        }
        .pw-toggle:hover { color: var(--green-dark); }
    </style>
</head>
<body>

    <!-- LEFT: Branding -->
    <div class="left-panel">
        <div class="brand-logo mb-2">Ethio<span>Edu</span></div>
        <p class="brand-tagline">School Management & Learning Platform</p>

        <div class="d-flex flex-wrap gap-2 justify-content-center mt-4" style="position:relative;z-index:1;">
            <div class="role-pill"><i class="fas fa-chalkboard-teacher"></i> Teachers</div>
            <div class="role-pill"><i class="fas fa-user-graduate"></i> Students</div>
            <div class="role-pill"><i class="fas fa-users"></i> Parents</div>
            <div class="role-pill"><i class="fas fa-shield-alt"></i> Admins</div>
        </div>

        <div class="highlight-stats">
            <div class="stat-item">
                <div class="num">12+</div>
                <div class="lbl">Modules</div>
            </div>
            <div class="stat-item">
                <div class="num">4</div>
                <div class="lbl">User Roles</div>
            </div>
            <div class="stat-item">
                <div class="num">100%</div>
                <div class="lbl">Web Based</div>
            </div>
        </div>

        <p class="mt-5 text-white-50 small" style="position:relative;z-index:1;">
            <a href="../index.php" class="text-white-50 text-decoration-none">
                <i class="fas fa-arrow-left me-1"></i> Back to EthioServe Main Site
            </a>
        </p>
    </div>

    <!-- RIGHT: Login Form -->
    <div class="right-panel">
        <div class="mb-4">
            <p class="text-muted mb-1 small fw-bold text-uppercase tracking-wider">School Portal</p>
            <h1 class="portal-title">Welcome <span>Back!</span></h1>
            <p class="text-muted" style="font-size:0.9rem;">Sign in to access your school dashboard.</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger border-0 rounded-3 d-flex align-items-center gap-2 mb-4" role="alert">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form method="POST" id="loginForm" autocomplete="off">
            <?php echo csrfField(); ?>

            <div class="mb-3">
                <label class="form-label small fw-bold text-muted">Username or Email</label>
                <div class="input-group" style="border:2px solid #f0f0f0; border-radius:14px; overflow:hidden; background:#fafafa;">
                    <span class="input-group-text border-0 bg-transparent pe-0" style="padding-left:16px;">
                        <i class="fas fa-user text-muted"></i>
                    </span>
                    <input type="text" name="username" class="form-control border-0 bg-transparent py-3"
                        style="font-size:0.95rem; box-shadow:none;"
                        placeholder="Enter your username or email"
                        value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>"
                        required autofocus>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label small fw-bold text-muted">Password</label>
                <div class="pw-wrapper" style="border:2px solid #f0f0f0; border-radius:14px; overflow:hidden; background:#fafafa; display:flex; align-items:center;">
                    <span class="input-group-text border-0 bg-transparent pe-0" style="padding-left:16px;">
                        <i class="fas fa-lock text-muted"></i>
                    </span>
                    <input type="password" name="password" id="pwInput" class="form-control border-0 bg-transparent py-3"
                        style="font-size:0.95rem; box-shadow:none;"
                        placeholder="Enter your password" required>
                    <button type="button" class="pw-toggle" onclick="togglePw()">
                        <i class="fas fa-eye" id="pwIcon"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-school-login w-100 mb-4">
                <i class="fas fa-sign-in-alt me-2"></i>Sign In to School Portal
            </button>
        </form>

        <div class="divider mb-3">Quick Login (tap to fill)</div>

        <div class="quick-login-grid mb-4">
            <button class="ql-btn ql-teacher" onclick="fillLogin('teacher1','password')">
                <i class="fas fa-chalkboard-teacher"></i> Teacher
            </button>
            <button class="ql-btn ql-student" onclick="fillLogin('student1','password')">
                <i class="fas fa-graduation-cap"></i> Student
            </button>
            <button class="ql-btn ql-parent" onclick="fillLogin('parent1','password')">
                <i class="fas fa-users"></i> Parent
            </button>
            <button class="ql-btn ql-admin" onclick="fillLogin('admin','password')">
                <i class="fas fa-shield-alt"></i> Super Admin
            </button>
            <button class="ql-btn ql-admin" style="border-color:#1B5E20; color:#1B5E20; background:#e8f5e9;" onclick="fillLogin('school_admin1','password')">
                <i class="fas fa-user-shield"></i> School Admin
            </button>
        </div>

        <p class="text-center text-muted small mb-0">
            All demo passwords: <code class="text-success">password</code>
        </p>

        <hr class="my-4">

        <p class="text-center text-muted small">
            Not a school user?
            <a href="../login.php" class="text-decoration-none fw-bold" style="color:var(--green-dark);">
                Go to Main Login
            </a>
        </p>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function fillLogin(user, pass) {
            document.querySelector('input[name="username"]').value = user;
            document.getElementById('pwInput').value = pass;
            // subtle pulse animation
            document.getElementById('loginForm').classList.add('was-validated');
        }

        function togglePw() {
            const inp  = document.getElementById('pwInput');
            const icon = document.getElementById('pwIcon');
            if (inp.type === 'password') {
                inp.type = 'text';
                icon.className = 'fas fa-eye-slash';
            } else {
                inp.type = 'password';
                icon.className = 'fas fa-eye';
            }
        }
    </script>
</body>
</html>
