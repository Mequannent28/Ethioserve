<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Handle hotel approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hotel_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $hotel_id = (int) $_POST['hotel_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE hotels SET status = ? WHERE id = ?");
            $stmt->execute([$status, $hotel_id]);
            redirectWithMessage('dashboard.php', 'success', 'Hotel status updated');
        }
    }
}

// Fetch stats from database
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_hotels = $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
<<<<<<< HEAD
$total_restaurants = $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn();
$total_taxis = $pdo->query("SELECT COUNT(*) FROM taxi_companies")->fetchColumn();
$total_buses = $pdo->query("SELECT COUNT(*) FROM buses")->fetchColumn();
=======
$approved_hotels = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status = 'approved'")->fetchColumn();
$pending_hotels = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status = 'pending'")->fetchColumn();
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE payment_status = 'paid'")->fetchColumn() ?: 0;
$total_commissions = $pdo->query("SELECT SUM(commission_amount) FROM referrals WHERE status = 'paid'")->fetchColumn() ?: 0;

// Get pending hotel approvals
$stmt = $pdo->query("
    SELECT h.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
    FROM hotels h
    JOIN users u ON h.user_id = u.id
    WHERE h.status = 'pending'
    ORDER BY h.id DESC
    LIMIT 10
");
$pending_hotels_list = $stmt->fetchAll();

// Get recent users
$stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();

// Get recent orders
$stmt = $pdo->query("
    SELECT o.*, u.full_name as customer_name, h.name as hotel_name
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    JOIN hotels h ON o.hotel_id = h.id
    ORDER BY o.created_at DESC
    LIMIT 5
");
$recent_orders = $stmt->fetchAll();

// Get monthly stats for chart
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            width: 100%;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .admin-stat-card {
            transition: transform 0.3s;
            border-radius: 15px;
        }

        .admin-stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-1">System Master Dashboard</h2>
                    <div class="badge bg-success px-3 py-2 rounded-pill">
                        <i class="fas fa-circle me-1" style="font-size: 8px;"></i> System Status: Optimal
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-white shadow-sm dropdown-toggle rounded-pill px-4" data-bs-toggle="dropdown">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getCurrentUserName()); ?>&background=000&color=fff"
                            class="rounded-circle me-2" width="25">
                        <?php echo htmlspecialchars(getCurrentUserName()); ?>
                    </button>
                    <ul class="dropdown-menu border-0 shadow mt-2">
                        <li><a class="dropdown-item" href="#"><i class="fas fa-cog me-2"></i>Settings</a></li>
                        <li><a class="dropdown-item" href="#"><i class="fas fa-database me-2"></i>Database</a></li>
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                    class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </div>
            </div>

            <!-- Global Analytics -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-primary-green text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Users</p>
                        <h2 class="fw-bold"><?php echo number_format($total_users); ?></h2>
                        <p class="small mb-0">Across all roles</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-warning text-dark">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Total Hotels</p>
                        <h2 class="fw-bold"><?php echo number_format($total_hotels); ?></h2>
                        <p class="small mb-0"><?php echo $approved_hotels; ?> approved, <?php echo $pending_hotels; ?>
                            pending</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-white">
                        <p class="text-muted small fw-bold text-uppercase mb-1">Total Revenue</p>
                        <h2 class="fw-bold"><?php echo number_format($total_revenue / 1000, 1); ?>k <small
                                class="fs-6">ETB</small></h2>
                        <p class="text-success small mb-0 fw-bold"><i class="fas fa-caret-up"></i>
                            <?php echo number_format($total_orders); ?> orders</p>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card admin-stat-card border-0 shadow-sm p-4 bg-danger text-white">
                        <p class="small fw-bold text-uppercase opacity-75 mb-1">Broker Commissions</p>
                        <h2 class="fw-bold"><?php echo number_format($total_commissions / 1000, 1); ?>k <small
                                class="fs-6">ETB</small></h2>
                        <p class="small mb-0">Total distributed</p>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Revenue Chart -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Platform Growth (Monthly)</h5>
                            <select class="form-select form-select-sm w-auto rounded-pill">
                                <option>Last 6 Months</option>
                            </select>
                        </div>
                        <canvas id="growthChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Awaiting Approval</h5>
                            <span class="badge bg-warning text-dark"><?php echo count($pending_hotels_list); ?></span>
                        </div>

                        <?php if (empty($pending_hotels_list)): ?>
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-check-circle text-success fs-1 mb-3 d-block"></i>
                                All caught up! No pending approvals.
                            </div>
                        <?php else: ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($pending_hotels_list as $hotel): ?>
                                    <div class="list-group-item px-0 py-3 border-0 border-bottom">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light p-2 rounded">
                                                <i class="fas fa-hotel text-primary-green"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($hotel['name']); ?></h6>
                                                <p class="small text-muted mb-0">
                                                    <?php echo htmlspecialchars($hotel['owner_name']); ?>
                                                </p>
                                            </div>
                                            <form method="POST" class="d-flex gap-1">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                                <input type="hidden" name="update_hotel_status" value="1">
                                                <button type="submit" name="status" value="approved"
                                                    class="btn btn-sm btn-outline-success rounded-pill px-2">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button type="submit" name="status" value="rejected"
                                                    class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <a href="manage_hotels.php" class="btn btn-light rounded-pill mt-4 w-100">
                            <i class="fas fa-building me-2"></i>Manage All Hotels
                        </a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="row g-4 mt-2">
                <!-- Recent Users -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-users text-primary-green me-2"></i>Recent Users
                            </h5>
                            <a href="manage_users.php" class="btn btn-sm btn-light rounded-pill">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td class="px-4">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=1B5E20&color=fff"
                                                    class="rounded-circle me-3" width="35">
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($user['full_name']); ?></strong>
                                                <br><small
                                                    class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                echo $user['role'] === 'admin' ? 'danger' :
                                                    ($user['role'] === 'hotel' ? 'warning' :
                                                        ($user['role'] === 'broker' ? 'info' : 'success'));
                                                ?>"><?php echo ucfirst($user['role']); ?></span>
                                            </td>
                                            <td class="text-muted small">
                                                <?php echo date('M d', strtotime($user['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-shopping-bag text-primary-green me-2"></i>Recent
                                Orders</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 px-4">Order</th>
                                        <th class="border-0">Customer</th>
                                        <th class="border-0">Amount</th>
                                        <th class="border-0">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                        <tr>
                                            <td class="px-4">
                                                <strong>#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                                <br><small
                                                    class="text-muted"><?php echo htmlspecialchars($order['hotel_name']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                            <td><strong><?php echo number_format($order['total_amount']); ?> ETB</strong>
                                            </td>
                                            <td><?php echo getStatusBadge($order['status']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('growthChart').getContext('2d');

        // Prepare chart data
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
        const orderData = [450, 600, 800, 1200, 1500, 2100];
        const revenueData = [45000, 62000, 85000, 120000, 155000, 210000];

        <?php if (!empty($monthly_stats)): ?>
            const realMonths = <?php echo json_encode(array_column($monthly_stats, 'month')); ?>;
            const realOrders = <?php echo json_encode(array_column($monthly_stats, 'order_count')); ?>;
            const realRevenue = <?php echo json_encode(array_column($monthly_stats, 'revenue')); ?>;
        <?php endif; ?>

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Orders',
                    data: orderData,
                    backgroundColor: '#1B5E20',
                    borderRadius: 5
                }, {
                    label: 'Revenue (k ETB)',
                    data: revenueData.map(r => r / 1000),
                    backgroundColor: '#F9A825',
                    borderRadius: 5
                }]
            },
            options: {
                responsive: true,
                scales: { y: { beginAtZero: true } },
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
</body>

</html>