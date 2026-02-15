<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a restaurant owner
requireRole('restaurant');

$user_id = getCurrentUserId();

// Get restaurant details
$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE user_id = ?");
$stmt->execute([$user_id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header("Location: dashboard.php");
    exit();
}

$restaurant_id = $restaurant['id'];

// Search filter
$search = sanitize($_GET['search'] ?? '');
$status_filter = sanitize($_GET['status'] ?? '');

// Get all customers who have ordered from this restaurant
$where = "WHERE ro.restaurant_id = ?";
$params = [$restaurant_id];

if (!empty($search)) {
    $where .= " AND (u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
    $search_param = "%$search%";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.full_name, u.phone, u.email, u.created_at as joined_date,
           COUNT(ro.id) as total_orders,
           COALESCE(SUM(ro.total_amount), 0) as total_spent,
           MAX(ro.created_at) as last_order_date,
           SUM(CASE WHEN ro.status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
           SUM(CASE WHEN ro.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
           SUM(CASE WHEN ro.status = 'pending' THEN 1 ELSE 0 END) as pending_orders
    FROM restaurant_orders ro
    JOIN users u ON ro.customer_id = u.id
    $where
    GROUP BY u.id
    ORDER BY total_spent DESC
");
$stmt->execute($params);
$customers = $stmt->fetchAll();

// Totals
$total_unique = count($customers);
$total_revenue_all = array_sum(array_column($customers, 'total_spent'));
$total_orders_all = array_sum(array_column($customers, 'total_orders'));
$avg_order = $total_orders_all > 0 ? $total_revenue_all / $total_orders_all : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customers - Restaurant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body { overflow-x: hidden; }
        .dashboard-wrapper { display: flex; width: 100%; align-items: stretch; }
        .main-content { flex: 1; padding: 30px; background-color: #f0f2f5; min-height: 100vh; }
        .customer-avatar {
            width: 50px; height: 50px; border-radius: 50%;
            background: linear-gradient(135deg, #1B5E20, #4CAF50);
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700; font-size: 1.2rem;
            flex-shrink: 0;
        }
        .stat-mini { border-radius: 12px; padding: 20px; text-align: center; }
        .customer-card { transition: all 0.3s; border-left: 4px solid transparent; }
        .customer-card:hover { border-left-color: var(--primary-green); transform: translateX(5px); }
        .order-history-badge { font-size: 0.75rem; padding: 4px 10px; }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_restaurant.php'); ?>

        <div class="main-content">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary-green"></i>Customer Management</h2>
                    <p class="text-muted">View and manage customers who ordered from <?php echo htmlspecialchars($restaurant['name']); ?></p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-mini">
                        <i class="fas fa-users text-primary-green fs-3 mb-2"></i>
                        <h3 class="fw-bold mb-0"><?php echo $total_unique; ?></h3>
                        <small class="text-muted">Total Customers</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-mini">
                        <i class="fas fa-shopping-bag text-info fs-3 mb-2"></i>
                        <h3 class="fw-bold mb-0"><?php echo $total_orders_all; ?></h3>
                        <small class="text-muted">Total Orders</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-mini">
                        <i class="fas fa-money-bill-wave text-warning fs-3 mb-2"></i>
                        <h3 class="fw-bold mb-0"><?php echo number_format($total_revenue_all); ?> <small>ETB</small></h3>
                        <small class="text-muted">Total Revenue</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-mini">
                        <i class="fas fa-chart-line text-success fs-3 mb-2"></i>
                        <h3 class="fw-bold mb-0"><?php echo number_format($avg_order); ?> <small>ETB</small></h3>
                        <small class="text-muted">Avg Order Value</small>
                    </div>
                </div>
            </div>

            <!-- Search Bar -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body py-3">
                    <form method="GET" class="d-flex gap-3 align-items-center">
                        <div class="input-group flex-grow-1">
                            <span class="input-group-text bg-light border-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" name="search" class="form-control bg-light border-0" 
                                   placeholder="Search by name, email, or phone..."
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <button type="submit" class="btn btn-primary-green rounded-pill px-4">
                            <i class="fas fa-search me-1"></i> Search
                        </button>
                        <?php if (!empty($search)): ?>
                            <a href="customers.php" class="btn btn-outline-secondary rounded-pill px-4">Clear</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <!-- Customers List -->
            <?php if (empty($customers)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-users text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No customers found</h4>
                    <p class="text-muted">
                        <?php echo !empty($search) ? 'Try a different search term.' : 'Customers will appear here when they place orders.'; ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($customers as $idx => $customer): ?>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm customer-card h-100">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start gap-3">
                                        <div class="customer-avatar">
                                            <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($customer['full_name']); ?></h5>
                                                    <small class="text-muted">
                                                        <?php if ($customer['phone']): ?>
                                                            <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($customer['phone']); ?>
                                                        <?php endif; ?>
                                                        <?php if ($customer['email']): ?>
                                                            <span class="ms-2"><i class="fas fa-envelope me-1"></i><?php echo htmlspecialchars($customer['email']); ?></span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <?php if ($idx === 0): ?>
                                                    <span class="badge bg-warning text-dark rounded-pill px-3">👑 Top Customer</span>
                                                <?php elseif ($customer['total_orders'] >= 5): ?>
                                                    <span class="badge bg-success rounded-pill px-3">⭐ Regular</span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Order Stats -->
                                            <div class="row g-2 mt-2">
                                                <div class="col-4">
                                                    <div class="bg-light rounded-3 p-2 text-center">
                                                        <div class="fw-bold text-primary-green"><?php echo $customer['total_orders']; ?></div>
                                                        <small class="text-muted" style="font-size:0.7rem;">Orders</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-light rounded-3 p-2 text-center">
                                                        <div class="fw-bold text-warning"><?php echo number_format($customer['total_spent']); ?></div>
                                                        <small class="text-muted" style="font-size:0.7rem;">ETB Spent</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="bg-light rounded-3 p-2 text-center">
                                                        <div class="fw-bold text-success"><?php echo $customer['completed_orders']; ?></div>
                                                        <small class="text-muted" style="font-size:0.7rem;">Completed</small>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Status Badges -->
                                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                                <?php if ($customer['pending_orders'] > 0): ?>
                                                    <span class="badge bg-warning text-dark order-history-badge">
                                                        <i class="fas fa-clock me-1"></i><?php echo $customer['pending_orders']; ?> Pending
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($customer['cancelled_orders'] > 0): ?>
                                                    <span class="badge bg-danger order-history-badge">
                                                        <i class="fas fa-times me-1"></i><?php echo $customer['cancelled_orders']; ?> Cancelled
                                                    </span>
                                                <?php endif; ?>
                                                <span class="badge bg-light text-muted order-history-badge">
                                                    <i class="fas fa-calendar me-1"></i>Last: <?php echo date('M d, Y', strtotime($customer['last_order_date'])); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
