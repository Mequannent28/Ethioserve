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
    echo "<div class='container py-5 text-center'><h3>Your restaurant is being set up. Please contact admin.</h3></div>";
    exit();
}

$restaurant_id = $restaurant['id'];

// Handle order status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_order_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $order_id = (int) $_POST['order_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE restaurant_orders SET status = ? WHERE id = ? AND restaurant_id = ?");
            $stmt->execute([$new_status, $order_id, $restaurant_id]);
            redirectWithMessage('dashboard.php', 'success', 'Order status updated!');
        }
    }
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurant_orders WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$total_orders = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM restaurant_orders WHERE restaurant_id = ? AND payment_status = 'paid'");
$stmt->execute([$restaurant_id]);
$total_revenue = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT customer_id) FROM restaurant_orders WHERE restaurant_id = ?");
$stmt->execute([$restaurant_id]);
$total_customers = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurant_orders WHERE restaurant_id = ? AND status = 'pending'");
$stmt->execute([$restaurant_id]);
$pending_orders = $stmt->fetchColumn();

// Get recent orders with customer info
$stmt = $pdo->prepare("
    SELECT ro.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
    FROM restaurant_orders ro
    JOIN users u ON ro.customer_id = u.id
    WHERE ro.restaurant_id = ?
    ORDER BY ro.created_at DESC
    LIMIT 10
");
$stmt->execute([$restaurant_id]);
$recent_orders = $stmt->fetchAll();

// Get order items for each order
$order_items = [];
foreach ($recent_orders as $order) {
    $stmt = $pdo->prepare("SELECT * FROM restaurant_order_items WHERE order_id = ?");
    $stmt->execute([$order['id']]);
    $order_items[$order['id']] = $stmt->fetchAll();
}

// Top customers
$stmt = $pdo->prepare("
    SELECT u.full_name, u.phone, u.email, COUNT(ro.id) as order_count, SUM(ro.total_amount) as total_spent
    FROM restaurant_orders ro
    JOIN users u ON ro.customer_id = u.id
    WHERE ro.restaurant_id = ?
    GROUP BY u.id
    ORDER BY total_spent DESC
    LIMIT 5
");
$stmt->execute([$restaurant_id]);
$top_customers = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Dashboard - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
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
            border-left: 4px solid var(--primary-green);
            border-radius: 12px;
        }

        .stat-card.gold {
            border-left-color: var(--secondary-gold);
        }

        .stat-card.red {
            border-left-color: var(--accent-red);
        }

        .stat-card.blue {
            border-left-color: #1976D2;
        }

        .customer-avatar {
            width: 45px;
            height: 45px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1B5E20, #4CAF50);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_restaurant.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-0">🍽️ Dashboard Overview</h2>
                    <p class="text-muted mb-0">Welcome back,
                        <?php echo htmlspecialchars($restaurant['name']); ?>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <span
                        class="badge bg-<?php echo $restaurant['status'] === 'approved' ? 'success' : 'warning'; ?> fs-6 px-3 py-2 rounded-pill">
                        <i class="fas fa-circle me-1" style="font-size: 0.5rem;"></i>
                        <?php echo ucfirst($restaurant['status']); ?>
                    </span>
                    <a href="../logout.php" class="btn btn-white shadow-sm rounded-pill px-4 text-danger">
                        <i class="fas fa-sign-out-alt me-2"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card stat-card p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Orders</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $total_orders; ?>
                                </h3>
                            </div>
                            <div class="bg-light rounded-3 p-3">
                                <i class="fas fa-shopping-cart text-primary-green fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card gold p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Revenue</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo number_format($total_revenue); ?> <small class="text-muted">ETB</small>
                                </h3>
                            </div>
                            <div class="bg-light rounded-3 p-3">
                                <i class="fas fa-money-bill-wave text-warning fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card blue p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Total Customers</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $total_customers; ?>
                                </h3>
                            </div>
                            <div class="bg-light rounded-3 p-3">
                                <i class="fas fa-users text-primary fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card red p-4 border-0 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small">Pending Orders</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $pending_orders; ?>
                                </h3>
                            </div>
                            <div class="bg-light rounded-3 p-3">
                                <i class="fas fa-clock text-danger fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm">
                        <div
                            class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0"><i class="fas fa-receipt me-2 text-primary-green"></i>Recent Orders
                            </h5>
                            <a href="orders.php" class="btn btn-sm btn-outline-primary-green rounded-pill px-3">View
                                All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox text-muted mb-3" style="font-size: 3rem;"></i>
                                    <p class="text-muted">No orders yet. Orders will appear here!</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="border-0 px-4 py-3">Order #</th>
                                                <th class="border-0">Customer</th>
                                                <th class="border-0">Items</th>
                                                <th class="border-0">Total</th>
                                                <th class="border-0">Status</th>
                                                <th class="border-0">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                                <tr>
                                                    <td class="px-4 py-3">
                                                        <span class="fw-bold">
                                                            <?php echo htmlspecialchars($order['order_reference'] ?? '#' . str_pad($order['id'], 5, '0', STR_PAD_LEFT)); ?>
                                                        </span>
                                                        <br><small class="text-muted">
                                                            <?php echo date('M d, h:i A', strtotime($order['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <div class="customer-avatar">
                                                                <?php echo strtoupper(substr($order['customer_name'], 0, 1)); ?>
                                                            </div>
                                                            <div>
                                                                <span class="fw-bold">
                                                                    <?php echo htmlspecialchars($order['customer_name']); ?>
                                                                </span>
                                                                <br><small class="text-muted">
                                                                    <?php echo htmlspecialchars($order['customer_phone'] ?? ''); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $items = $order_items[$order['id']] ?? [];
                                                        foreach ($items as $item) {
                                                            echo '<small>' . htmlspecialchars($item['item_name']) . ' x' . $item['quantity'] . '</small><br>';
                                                        }
                                                        ?>
                                                    </td>
                                                    <td><span class="fw-bold text-primary-green">
                                                            <?php echo number_format($order['total_amount']); ?> ETB
                                                        </span></td>
                                                    <td>
                                                        <?php echo getStatusBadge($order['status']); ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($order['status'] === 'pending'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="order_id"
                                                                    value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="update_order_status" value="1">
                                                                <button type="submit" name="status" value="preparing"
                                                                    class="btn btn-sm btn-primary-green rounded-pill">
                                                                    <i class="fas fa-fire"></i> Prepare
                                                                </button>
                                                            </form>
                                                        <?php elseif ($order['status'] === 'preparing'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="order_id"
                                                                    value="<?php echo $order['id']; ?>">
                                                                <input type="hidden" name="update_order_status" value="1">
                                                                <button type="submit" name="status" value="ready"
                                                                    class="btn btn-sm btn-success rounded-pill">
                                                                    <i class="fas fa-check"></i> Ready
                                                                </button>
                                                            </form>
                                                        <?php else: ?>
                                                            <span class="text-muted small">
                                                                <?php echo ucfirst($order['status']); ?>
                                                            </span>
                                                        <?php endif; ?>
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

                <!-- Top Customers -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-crown me-2 text-warning"></i>Top Customers</h5>
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
                                                <?php echo $customer['order_count']; ?> orders •
                                                <?php echo number_format($customer['total_spent']); ?> ETB
                                            </small>
                                        </div>
                                        <?php if ($idx === 0): ?>
                                            <span class="badge bg-warning text-dark">👑 VIP</span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <a href="customers.php" class="btn btn-outline-primary-green w-100 rounded-pill mt-2">
                                <i class="fas fa-users me-2"></i>View All Customers
                            </a>
                        </div>
                    </div>

                    <!-- Restaurant Info Card -->
                    <div class="card border-0 shadow-sm mt-4">
                        <div class="card-header bg-white border-0 py-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-store me-2 text-primary-green"></i>Restaurant Info
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-2">
                                <small class="text-muted">Name</small>
                                <p class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($restaurant['name']); ?>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Cuisine</small>
                                <p class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($restaurant['cuisine_type'] ?? 'Not set'); ?>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Address</small>
                                <p class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($restaurant['address'] ?? 'Not set'); ?>
                                </p>
                            </div>
                            <div class="mb-2">
                                <small class="text-muted">Rating</small>
                                <p class="fw-bold mb-0">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i
                                            class="fas fa-star <?php echo $i <= round($restaurant['rating']) ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                    <span class="ms-1">(
                                        <?php echo $restaurant['rating']; ?>)
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>