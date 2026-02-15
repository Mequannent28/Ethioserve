<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a hotel owner
requireRole('hotel');

$user_id = getCurrentUserId();

// Get hotel details
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    // Create a pending hotel profile if doesn't exist
    $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, status) VALUES (?, ?, 'pending')");
    $stmt->execute([$user_id, getCurrentUserName() . "'s Hotel"]);

    header("Location: profile.php");
    exit();
}

$hotel_id = $hotel['id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $order_id = (int) $_POST['order_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['pending', 'preparing', 'on_delivery', 'delivered', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$new_status, $order_id, $hotel_id]);
            redirectWithMessage('dashboard.php', 'success', 'Order status updated');
        }
    }
}

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_id = (int) $_POST['booking_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['pending', 'approved', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$new_status, $booking_id, $hotel_id]);
            redirectWithMessage('dashboard.php', 'success', 'Booking status updated');
        }
    }
}

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE hotel_id = ?");
$stmt->execute([$hotel_id]);
$total_orders_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(total_amount) FROM orders WHERE hotel_id = ? AND payment_status = 'paid'");
$stmt->execute([$hotel_id]);
$total_revenue = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND status = 'approved'");
$stmt->execute([$hotel_id]);
$active_bookings_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE hotel_id = ? AND status = 'pending'");
$stmt->execute([$hotel_id]);
$pending_orders_count = $stmt->fetchColumn();

// Get pending orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.hotel_id = ? AND o.status IN ('pending', 'preparing')
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$hotel_id]);
$pending_orders = $stmt->fetchAll();

// Get pending bookings
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name as customer_name, u.phone as customer_phone
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    WHERE b.hotel_id = ? AND b.status = 'pending'
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute([$hotel_id]);
$pending_bookings = $stmt->fetchAll();

// Get recent orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    WHERE o.hotel_id = ?
    ORDER BY o.created_at DESC
    LIMIT 5
");
$stmt->execute([$hotel_id]);
$recent_orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Dashboard - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
<<<<<<< HEAD
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        .main-content {
            padding: 40px;
            min-height: 100vh;
        }

        .admin-stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 20px;
            color: #fff;
            position: relative;
            overflow: hidden;
            border: none;
        }

        .admin-stat-card::after {
            content: '';
            position: absolute;
            right: -20px;
            bottom: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .admin-stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
=======
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .stat-card {
            border-left: 5px solid var(--primary-green);
        }

        .stat-card.gold {
            border-left-color: var(--secondary-gold);
        }

        .stat-card.red {
            border-left-color: var(--accent-red);
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_hotel.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
<<<<<<< HEAD
                    <h2 class="fw-bold mb-0">Hotel Administration</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($hotel['name']); ?>!</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <?php if ($hotel['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Status: Pending Approval</span>
                    <?php elseif ($hotel['status'] === 'approved'): ?>
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
                    <h2 class="fw-bold mb-0">Dashboard Overview</h2>
                    <p class="text-muted">Welcome back, <?php echo htmlspecialchars($hotel['name']); ?>!</p>
                    <?php if ($hotel['status'] === 'pending'): ?>
                        <span class="badge bg-warning text-dark">Your hotel is pending approval</span>
                    <?php elseif ($hotel['status'] === 'rejected'): ?>
                        <span class="badge bg-danger">Your hotel was rejected. Contact support.</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="position-relative">
                        <i class="fas fa-bell fs-4 text-muted"></i>
                        <?php if ($pending_orders_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $pending_orders_count; ?>
                            </span>
                        <?php endif; ?>
                    </div>
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
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Orders</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_orders_count); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-shopping-cart me-1"></i> Lifetime volume</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-warning text-dark">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Revenue</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_revenue / 1000, 1); ?>k <small
                                class="fs-6">ETB</small></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-money-bill-wave me-1"></i> Paid earnings</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-info text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Active Bookings</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($active_bookings_count); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-calendar-check me-1"></i> Confirmed slots</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-danger text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Pending Orders</p>
                        <h2 class="fw-bold mb-0"><?php echo number_format($pending_orders_count); ?></h2>
                        <p class="small mb-0 mt-2"><i class="fas fa-clock me-1"></i> Needs attention</p>
=======
                    <div class="card stat-card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Total Orders</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_orders_count); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-shopping-cart text-primary-green fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card gold p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Revenue (ETB)</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($total_revenue / 1000, 1); ?>k</h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-money-bill-wave text-warning fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Active Bookings</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($active_bookings_count); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-calendar-check text-primary-green fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card red p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1 fw-bold text-uppercase">Pending Orders</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($pending_orders_count); ?></h3>
                            </div>
                            <div class="bg-light p-3 rounded-circle">
                                <i class="fas fa-clock text-danger fs-4"></i>
                            </div>
                        </div>
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Pending Orders -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-clock text-warning me-2"></i>Pending Orders</h5>
                            <span class="badge bg-warning text-dark"><?php echo count($pending_orders); ?>
                                pending</span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending_orders)): ?>
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                    No pending orders. Great job!
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 px-4">Order</th>
                                                <th class="border-0">Customer</th>
                                                <th class="border-0">Amount</th>
                                                <th class="border-0">Status</th>
                                                <th class="border-0">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($pending_orders as $order): ?>
                                                <tr>
                                                    <td class="px-4">
                                                        <strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                                        <br><small
                                                            class="text-muted"><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></small>
                                                    </td>
                                                    <td>
                                                        <?php echo htmlspecialchars($order['customer_name']); ?>
                                                        <?php if ($order['customer_phone']): ?>
                                                            <br><small
                                                                class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><strong><?php echo number_format($order['total_amount']); ?>
                                                            ETB</strong></td>
                                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                                    <td>
                                                        <form method="POST" class="d-flex gap-1">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="order_id"
                                                                value="<?php echo $order['id']; ?>">
                                                            <input type="hidden" name="update_order_status" value="1">

                                                            <?php if ($order['status'] === 'pending'): ?>
                                                                <button type="submit" name="status" value="preparing"
                                                                    class="btn btn-sm btn-primary-green rounded-pill">
                                                                    <i class="fas fa-fire"></i> Start
                                                                </button>
                                                            <?php elseif ($order['status'] === 'preparing'): ?>
                                                                <button type="submit" name="status" value="on_delivery"
                                                                    class="btn btn-sm btn-primary rounded-pill">
                                                                    <i class="fas fa-motorcycle"></i> Send
                                                                </button>
                                                            <?php endif; ?>
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

                <!-- Pending Bookings -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-calendar text-primary-green me-2"></i>Pending
                                Bookings</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($pending_bookings)): ?>
                                <p class="text-muted text-center mb-0">No pending bookings</p>
                            <?php else: ?>
                                <div class="d-flex flex-column gap-3">
                                    <?php foreach ($pending_bookings as $booking): ?>
                                        <div class="border rounded-3 p-3">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h6 class="fw-bold mb-0">
<<<<<<< HEAD
                                                        <?php echo htmlspecialchars($booking['customer_name']); ?>
                                                    </h6>
=======
                                                        <?php echo htmlspecialchars($booking['customer_name']); ?></h6>
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                                                    <small class="text-muted">
                                                        <i class="fas fa-<?php
                                                        echo $booking['booking_type'] === 'room' ? 'bed' :
                                                            ($booking['booking_type'] === 'table' ? 'utensils' : 'building');
                                                        ?> me-1"></i>
                                                        <?php echo ucfirst($booking['booking_type']); ?>
                                                    </small>
                                                </div>
                                                <?php echo getStatusBadge($booking['status']); ?>
                                            </div>
                                            <p class="small mb-2">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                at <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                            </p>
                                            <form method="POST" class="d-flex gap-2">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                <input type="hidden" name="update_booking_status" value="1">
                                                <button type="submit" name="status" value="approved"
                                                    class="btn btn-sm btn-success rounded-pill flex-grow-1">
                                                    <i class="fas fa-check"></i> Approve
                                                </button>
                                                <button type="submit" name="status" value="cancelled"
                                                    class="btn btn-sm btn-outline-danger rounded-pill">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Orders Table -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Orders</h5>
                    <a href="orders.php" class="btn btn-sm btn-light rounded-pill">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="border-0 px-4">Order ID</th>
                                <th class="border-0">Customer</th>
                                <th class="border-0">Amount</th>
                                <th class="border-0">Payment</th>
                                <th class="border-0">Status</th>
                                <th class="border-0">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td class="px-4 fw-bold text-primary-green">
                                        #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                    <td><?php echo number_format($order['total_amount']); ?> ETB</td>
                                    <td><?php echo getStatusBadge($order['payment_status']); ?></td>
                                    <td><?php echo getStatusBadge($order['status']); ?></td>
                                    <td><?php echo date('M d, H:i', strtotime($order['created_at'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="text-center text-muted small mt-5">&copy; 2026 EthioServe Platform. Hotel Administration Portal.
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</body>

</html>