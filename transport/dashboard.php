<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a transport owner
requireRole('transport');

$user_id = getCurrentUserId();

// Get transport company details
$stmt = $pdo->prepare("SELECT * FROM transport_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    // Auto-create company record if it doesn't exist
    $stmt = $pdo->prepare("INSERT INTO transport_companies (user_id, company_name, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, getCurrentUserName() . "'s Transport"]);
    header("Location: dashboard.php");
    exit();
}

$company_id = $company['id'];

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_id = (int) $_POST['booking_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['pending', 'confirmed', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE bus_bookings SET status = ? WHERE id = ? AND schedule_id IN (SELECT id FROM schedules WHERE bus_id IN (SELECT id FROM buses WHERE company_id = ?))");
            $stmt->execute([$new_status, $booking_id, $company_id]);
            redirectWithMessage('dashboard.php', 'success', 'Booking status updated');
        }
    }
}

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM buses WHERE company_id = ?");
$stmt->execute([$company_id]);
$total_buses = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM routes WHERE company_id = ?");
$stmt->execute([$company_id]);
$total_routes = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT COUNT(*) FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    WHERE b.company_id = ?
");
$stmt->execute([$company_id]);
$total_bookings = $stmt->fetchColumn();

$stmt = $pdo->prepare("
    SELECT SUM(bb.total_amount) FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    WHERE b.company_id = ? AND bb.payment_status = 'paid'
");
$stmt->execute([$company_id]);
$total_revenue = $stmt->fetchColumn() ?: 0;

// Get pending bookings
$stmt = $pdo->prepare("
    SELECT bb.*, u.full_name as customer_name, u.phone as customer_phone,
           r.origin, r.destination, s.departure_time, b.bus_number
    FROM bus_bookings bb
    JOIN users u ON bb.customer_id = u.id
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.company_id = ? AND bb.status = 'pending'
    ORDER BY bb.travel_date ASC, s.departure_time ASC
    LIMIT 10
");
$stmt->execute([$company_id]);
$pending_bookings = $stmt->fetchAll();

// Get recent bookings
$stmt = $pdo->prepare("
    SELECT bb.*, u.full_name as customer_name,
           r.origin, r.destination, s.departure_time, b.bus_number
    FROM bus_bookings bb
    JOIN users u ON bb.customer_id = u.id
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    JOIN routes r ON s.route_id = r.id
    WHERE b.company_id = ?
    ORDER BY bb.created_at DESC
    LIMIT 5
");
$stmt->execute([$company_id]);
$recent_bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transport Dashboard - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f4f6f9;
=======
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
<<<<<<< HEAD
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
=======
            align-items: stretch;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f0f2f5;
            min-height: 100vh;
            margin-left: 250px;
        }

        .stat-card {
            border-left: 5px solid var(--primary-green);
        }

        .stat-card.gold {
            border-left-color: var(--secondary-gold);
        }

        .stat-card.blue {
            border-left-color: #0d6efd;
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_transport.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
<<<<<<< HEAD
                    <h2 class="fw-bold mb-0">Transport Management</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>!</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <?php if ($company['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Status: Pending Approval</span>
                    <?php elseif ($company['status'] === 'approved'): ?>
                        <span class="badge bg-success px-3 py-2 rounded-pill">Status: Active</span>
                    <?php endif; ?>

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
=======
                    <h2 class="fw-bold mb-0">Transport Dashboard</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($company['company_name']); ?>!</p>
                    <?php if ($company['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Your company is pending approval</span>
                    <?php elseif ($company['status'] === 'rejected'): ?>
                        <span class="badge bg-danger">Your company was rejected. Contact support.</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="../logout.php" class="btn btn-white shadow-sm rounded-pill px-4 text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
<<<<<<< HEAD
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-primary-green">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Buses</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_buses); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-bus me-1"></i> Fleet size</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-warning text-dark">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Active Routes</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_routes); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-route me-1"></i> Operational paths</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-info text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Bookings</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_bookings); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-ticket-alt me-1"></i> Tickets sold</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-success text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Revenue</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_revenue / 1000, 1); ?>k <small
                                class="fs-6">ETB</small></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-money-bill-wave me-1"></i> Paid earnings</p>
=======
                    <div class="card stat-card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Total Buses</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_buses); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-bus text-primary-green fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card gold p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Active Routes</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_routes); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-route text-warning fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card blue p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Total Bookings</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_bookings); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-ticket-alt text-primary fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Revenue (ETB)</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_revenue / 1000, 1); ?>k</h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-money-bill-wave text-success fs-4"></i>
                            </div>
                        </div>
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Pending Bookings -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-clock text-warning me-2"></i>Pending Bookings</h5>
                            <span class="badge bg-warning text-dark"><?php echo count($pending_bookings); ?>
                                pending</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending_bookings)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                    No pending bookings. Great job!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 px-4">Booking</th>
                                                <th class="border-0">Route</th>
                                                <th class="border-0">Customer</th>
                                                <th class="border-0">Amount</th>
                                                <th class="border-0">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_bookings as $booking): ?>
                                                <tr>
                                                    <td class="px-4">
                                                        <strong><?php echo htmlspecialchars($booking['booking_reference']); ?></strong>
                                                        <br><small
                                                            class="text-muted"><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?>
                                                            -
                                                            <?php echo date('h:i A', strtotime($booking['departure_time'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['origin']); ?> →
                                                        <?php echo htmlspecialchars($booking['destination']); ?>
                                                        <br><small class="text-muted">Bus:
                                                            <?php echo htmlspecialchars($booking['bus_number']); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                                        <?php if ($booking['customer_phone']): ?>
                                                            <br><small
                                                                class="text-muted"><?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo number_format($booking['total_amount']); ?>
                                                            ETB</strong></td>
                                                    <td>
                                                        <form method="POST" class="d-flex gap-1">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="booking_id"
                                                                value="<?php echo $booking['id']; ?>">
                                                            <input type="hidden" name="update_booking_status" value="1">
                                                            <button type="submit" name="status" value="confirmed"
                                                                class="btn btn-sm btn-success rounded-pill">
                                                                <i class="fas fa-check"></i> Confirm
                                                            </button>
                                                            <button type="submit" name="status" value="cancelled"
                                                                class="btn btn-sm btn-outline-danger rounded-pill">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="buses.php" class="btn btn-outline-primary-green rounded-pill">
                                    <i class="fas fa-bus me-2"></i> Manage Buses
                                </a>
                                <a href="routes.php" class="btn btn-outline-primary-green rounded-pill">
                                    <i class="fas fa-route me-2"></i> Manage Routes
                                </a>
                                <a href="schedules.php" class="btn btn-outline-primary-green rounded-pill">
                                    <i class="fas fa-calendar-alt me-2"></i> Manage Schedules
                                </a>
                                <a href="bookings.php" class="btn btn-outline-primary-green rounded-pill">
                                    <i class="fas fa-ticket-alt me-2"></i> All Bookings
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Company Info -->
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-building text-primary-green me-2"></i>Company Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2"><strong>Company:</strong>
                                <?php echo htmlspecialchars($company['company_name']); ?></p>
                            <p class="mb-2"><strong>Phone:</strong>
                                <?php echo htmlspecialchars($company['phone'] ?? 'Not set'); ?></p>
                            <p class="mb-2"><strong>Email:</strong>
                                <?php echo htmlspecialchars($company['email'] ?? 'Not set'); ?></p>
                            <p class="mb-0"><strong>Rating:</strong>
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i
                                        class="fas fa-star <?php echo $i <= $company['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                <?php endfor; ?>
                            </p>
                            <a href="profile.php" class="btn btn-sm btn-light rounded-pill mt-3 w-100">
                                <i class="fas fa-edit me-1"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Bookings</h5>
                    <a href="bookings.php" class="btn btn-sm btn-light rounded-pill">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 px-4">Reference</th>
                                <th class="border-0">Route</th>
                                <th class="border-0">Customer</th>
                                <th class="border-0">Travel Date</th>
                                <th class="border-0">Amount</th>
                                <th class="border-0">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_bookings as $booking): ?>
                                <tr>
                                    <td class="px-4 fw-bold text-primary-green">
<<<<<<< HEAD
                                        <?php echo htmlspecialchars($booking['booking_reference']); ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($booking['origin']); ?> →
                                        <?php echo htmlspecialchars($booking['destination']); ?>
                                    </td>
=======
                                        <?php echo htmlspecialchars($booking['booking_reference']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['origin']); ?> →
                                        <?php echo htmlspecialchars($booking['destination']); ?></td>
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                                    <td><?php echo htmlspecialchars($booking['customer_name']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></td>
                                    <td><?php echo number_format($booking['total_amount']); ?> ETB</td>
                                    <td><?php echo getStatusBadge($booking['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>