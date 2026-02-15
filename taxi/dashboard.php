<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('taxi');

$user_id = getCurrentUserId();

// Get taxi company details
$stmt = $pdo->prepare("SELECT * FROM taxi_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    echo "<div class='container py-5 text-center'><h3>Your taxi company is being set up. Please contact admin.</h3></div>";
    exit();
}

$company_id = $company['id'];

// Handle ride status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ride_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $ride_id = (int) $_POST['ride_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['requested', 'accepted', 'in_progress', 'completed', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE taxi_rides SET status = ? WHERE id = ? AND taxi_company_id = ?");
            $stmt->execute([$new_status, $ride_id, $company_id]);
            redirectWithMessage('dashboard.php', 'success', 'Ride status updated!');
        }
    }
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_rides WHERE taxi_company_id = ?");
$stmt->execute([$company_id]);
$total_rides = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(fare), 0) FROM taxi_rides WHERE taxi_company_id = ? AND payment_status = 'paid'");
$stmt->execute([$company_id]);
$total_revenue = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM taxi_rides WHERE taxi_company_id = ?");
$stmt->execute([$company_id]);
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_rides WHERE taxi_company_id = ? AND status = 'requested'");
$stmt->execute([$company_id]);
$pending_rides = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_vehicles WHERE company_id = ?");
$stmt->execute([$company_id]);
$total_vehicles = $stmt->fetchColumn();

// Recent rides
$stmt = $pdo->prepare("
    SELECT tr.*, u.full_name as customer_name, u.phone as cust_phone
    FROM taxi_rides tr
    LEFT JOIN users u ON tr.customer_id = u.id
    WHERE tr.taxi_company_id = ?
    ORDER BY tr.created_at DESC
    LIMIT 10
");
$stmt->execute([$company_id]);
$recent_rides = $stmt->fetchAll();

// Top customers
$stmt = $pdo->prepare("
    SELECT u.full_name, u.phone, u.email, COUNT(tr.id) as ride_count, SUM(tr.fare) as total_spent
    FROM taxi_rides tr
    JOIN users u ON tr.customer_id = u.id
    WHERE tr.taxi_company_id = ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmt->execute([$company_id]);
$top_customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Taxi Dashboard - EthioServe</title>
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

        .admin-stat-card {
            transition: transform 0.3s;
            border-radius: 15px;
            color: #fff;
        }

        .admin-stat-card:hover {
            transform: translateY(-5px);
        }

        .customer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #F9A825, #FF8F00);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }

        .ride-card {
            border-left: 4px solid #1B5E20;
            border-radius: 12px;
            transition: all 0.3s;
        }

        .ride-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .ride-status-requested {
            border-left-color: #F9A825;
        }

        .ride-status-in_progress {
            border-left-color: #1976D2;
        }

        .ride-status-completed {
            border-left-color: #2E7D32;
        }

        .ride-status-cancelled {
            border-left-color: #C62828;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_taxi.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-0">Taxi Fleet Management</h2>
                    <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>
                    </p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <span
                        class="badge bg-<?php echo $company['status'] === 'approved' ? 'success' : 'warning'; ?> px-3 py-2 rounded-pill">
                        Status: <?php echo ucfirst($company['status']); ?>
                    </span>

                    <div class="dropdown">
                        <button class="btn btn-white shadow-sm dropdown-toggle rounded-pill px-4"
                            data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-2"></i>
                            <?php echo htmlspecialchars(getCurrentUserName()); ?>
                        </button>
                        <ul class="dropdown-menu border-0 shadow mt-2">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-cog me-2"></i>Profile</a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                        class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-primary-green">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Rides</p>
                        <h2 class="fw-bold mb-0"><?php echo $total_rides; ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-route me-1"></i> Lifetime activity</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-warning text-dark">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Revenue</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_revenue); ?> <small
                                class="fs-6">ETB</small></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-money-bill-wave me-1"></i> Paid earnings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-info text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Fleet Size</p>
                        <h2 class="fw-bold mb-0"><?php echo $total_vehicles; ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-car me-1"></i> Registered vehicles</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-danger text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Active Requests</p>
                        <h2 class="fw-bold mb-0"><?php echo $pending_rides; ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-clock me-1"></i> Waiting for acceptance</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Rides -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-car-side me-2 text-primary-green"></i>Recent Rides
                            </h5>
                            <a href="rides.php" class="btn btn-sm btn-outline-primary-green rounded-pill px-3">View
                                All</a>
                        </div>
                        <div class="card-body">
                            <?php if (empty($recent_rides)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-taxi text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="text-muted">No rides yet. Rides will appear here!</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($recent_rides as $ride): ?>
                                    <div
                                        class="card ride-card ride-status-<?php echo $ride['status']; ?> border-0 shadow-sm mb-3">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <span class="fw-bold">
                                                        <?php echo htmlspecialchars($ride['ride_reference'] ?? '#' . $ride['id']); ?>
                                                    </span>
                                                    <span class="ms-2">
                                                        <?php echo getStatusBadge($ride['status']); ?>
                                                    </span>
                                                </div>
                                                <span class="fw-bold text-primary-green">
                                                    <?php echo number_format($ride['fare']); ?> ETB
                                                </span>
                                            </div>
                                            <div class="d-flex align-items-center gap-2 mb-2">
                                                <div class="customer-avatar" style="width:35px;height:35px;font-size:0.85rem;">
                                                    <?php echo strtoupper(substr($ride['passenger_name'] ?? $ride['customer_name'] ?? '?', 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <span class="fw-bold small">
                                                        <?php echo htmlspecialchars($ride['passenger_name'] ?? $ride['customer_name'] ?? 'N/A'); ?>
                                                    </span>
                                                    <br><small class="text-muted">
                                                        <?php echo htmlspecialchars($ride['passenger_phone'] ?? $ride['cust_phone'] ?? ''); ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="bg-light rounded-3 p-2 mb-2">
                                                <small>
                                                    <i class="fas fa-map-marker-alt text-success me-1"></i>
                                                    <?php echo htmlspecialchars($ride['pickup_location']); ?>
                                                    <br>
                                                    <i class="fas fa-flag-checkered text-danger me-1"></i>
                                                    <?php echo htmlspecialchars($ride['dropoff_location']); ?>
                                                </small>
                                            </div>
                                            <?php if ($ride['status'] === 'requested'): ?>
                                                <form method="POST" class="d-flex gap-2">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                                    <input type="hidden" name="update_ride_status" value="1">
                                                    <button type="submit" name="status" value="accepted"
                                                        class="btn btn-sm btn-primary-green rounded-pill flex-grow-1">
                                                        <i class="fas fa-check me-1"></i>Accept
                                                    </button>
                                                    <button type="submit" name="status" value="cancelled"
                                                        class="btn btn-sm btn-outline-danger rounded-pill">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($ride['status'] === 'accepted'): ?>
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                                    <input type="hidden" name="update_ride_status" value="1">
                                                    <button type="submit" name="status" value="in_progress"
                                                        class="btn btn-sm btn-primary rounded-pill w-100">
                                                        <i class="fas fa-play me-1"></i>Start Ride
                                                    </button>
                                                </form>
                                            <?php elseif ($ride['status'] === 'in_progress'): ?>
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                                    <input type="hidden" name="update_ride_status" value="1">
                                                    <button type="submit" name="status" value="completed"
                                                        class="btn btn-sm btn-success rounded-pill w-100">
                                                        <i class="fas fa-flag-checkered me-1"></i>Complete Ride
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Right Panel -->
                <div class="col-lg-4">
                    <!-- Top Customers -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-crown me-2 text-warning"></i>Top Riders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($top_customers)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-users text-muted mb-2" style="font-size: 2rem;"></i>
                                    <p class="text-muted small">No customers yet</p>
                                </div>
                            <?php else: ?>
                                <?php foreach ($top_customers as $idx => $customer): ?>
                                    <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded-3">
                                        <div class="customer-avatar">
                                            <?php echo ($idx + 1); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 fw-bold">
                                                <?php echo htmlspecialchars($customer['full_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo $customer['ride_count']; ?> rides •
                                                <?php echo number_format($customer['total_spent']); ?> ETB
                                            </small>
                                        </div>
                                        <?php if ($idx === 0): ?>
                                            <span class="badge bg-warning text-dark">👑</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <a href="customers.php" class="btn btn-outline-primary-green w-100 rounded-pill mt-2">
                                <i class="fas fa-users me-2"></i>All Customers
                            </a>
                        </div>
                    </div>

                    <!-- Company Info -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-building me-2 text-primary-green"></i>Company Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">Company</small>
                                <p class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($company['company_name']); ?>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Address</small>
                                <p class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($company['address'] ?? 'Not set'); ?>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Vehicles</small>
                                <p class="fw-bold mb-1">
                                    <?php echo $total_vehicles; ?> vehicles
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Rating</small>
                                <p class="fw-bold mb-0">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i
                                            class="fas fa-star <?php echo $i <= round($company['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1">(
                                        <?php echo $company['rating']; ?>)
                                    </span>
                                </p>
                            </div>
                            <a href="vehicles.php" class="btn btn-outline-primary-green w-100 rounded-pill mt-2">
                                <i class="fas fa-car me-2"></i>Manage Vehicles
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>