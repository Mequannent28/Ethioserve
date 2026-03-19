<?php
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/db.php';
requireLogin();
requireRole('transport');

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT * FROM transport_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    // Auto-create company if missing (safety check)
    $stmt = $pdo->prepare("INSERT INTO transport_companies (user_id, company_name, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, getCurrentUserName() . "'s Transport"]);
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Transport Dashboard'; ?> - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            margin-left: 250px;
            width: calc(100% - 250px);
            padding: 30px;
            min-height: 100vh;
            transition: all 0.3s;
        }
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include(__DIR__ . '/sidebar_transport.php'); ?>
        <div class="main-content">
            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-0"><?php echo $top_title ?? 'Transport Management'; ?></h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>!</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <?php if ($company['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Status: Pending Approval</span>
                    <?php elseif ($company['status'] === 'approved'): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">Status: Active</span>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn btn-white shadow-sm dropdown-toggle rounded-pill px-4" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars(getCurrentUserName()); ?>
                        </button>
                        <ul class="dropdown-menu border-0 shadow mt-2 dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-cog me-2"></i>Profile Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>
