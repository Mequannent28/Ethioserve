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
    header("Location: dashboard.php");
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
            redirectWithMessage('orders.php', 'success', 'Order status updated');
        }
    }
}

// Filter by status
$status_filter = sanitize($_GET['status'] ?? '');
$where_clause = "WHERE o.hotel_id = ?";
$params = [$hotel_id];

if (!empty($status_filter) && in_array($status_filter, ['pending', 'preparing', 'on_delivery', 'delivered', 'cancelled'])) {
    $where_clause .= " AND o.status = ?";
    $params[] = $status_filter;
}

// Get all orders
$stmt = $pdo->prepare("
    SELECT o.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
    FROM orders o
    JOIN users u ON o.customer_id = u.id
    $where_clause
    ORDER BY o.created_at DESC
");
$stmt->execute($params);
$orders = $stmt->fetchAll();

// Get order items for each order
$order_items = [];
foreach ($orders as $order) {
    $stmt = $pdo->prepare("
        SELECT oi.*, mi.name as item_name
        FROM order_items oi
        JOIN menu_items mi ON oi.menu_item_id = mi.id
        WHERE oi.order_id = ?
    ");
    $stmt->execute([$order['id']]);
    $order_items[$order['id']] = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders - Hotel Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
<<<<<<< HEAD
            background-color: #f8fafc;
        }

        .main-content {
            padding: 40px;
=======
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
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_hotel.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">All Orders</h2>
                    <p class="text-muted">Manage orders for <?php echo htmlspecialchars($hotel['name']); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?status=pending"
                        class="btn btn-outline-warning rounded-pill <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=preparing"
                        class="btn btn-outline-info rounded-pill <?php echo $status_filter === 'preparing' ? 'active' : ''; ?>">Preparing</a>
                    <a href="?status=on_delivery"
                        class="btn btn-outline-primary rounded-pill <?php echo $status_filter === 'on_delivery' ? 'active' : ''; ?>">On
                        Delivery</a>
                    <a href="?status=delivered"
                        class="btn btn-outline-success rounded-pill <?php echo $status_filter === 'delivered' ? 'active' : ''; ?>">Delivered</a>
                    <a href="orders.php" class="btn btn-outline-secondary rounded-pill">All</a>
                </div>
            </div>

            <?php if (empty($orders)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-inbox text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No orders found</h4>
                    <p class="text-muted">Orders will appear here when customers place them.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($orders as $order): ?>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div
                                    class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-0">#<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?>
                                        </h6>
                                        <small
                                            class="text-muted"><?php echo date('M d, Y - h:i A', strtotime($order['created_at'])); ?></small>
                                    </div>
                                    <?php echo getStatusBadge($order['status']); ?>
                                </div>
                                <div class="card-body">
                                    <!-- Customer Info -->
                                    <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-3">
                                        <div class="bg-white rounded-circle p-2">
                                            <i class="fas fa-user text-primary-green"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($order['customer_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($order['customer_phone'] ?? 'No phone'); ?>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Order Items -->
                                    <div class="mb-3">
                                        <h6 class="small fw-bold text-muted mb-2">ORDER ITEMS</h6>
                                        <?php foreach ($order_items[$order['id']] as $item): ?>
                                            <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                                                <span><?php echo htmlspecialchars($item['item_name']); ?>
                                                    x<?php echo $item['quantity']; ?></span>
                                                <span
                                                    class="fw-bold"><?php echo number_format($item['price'] * $item['quantity']); ?>
                                                    ETB</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Total -->
                                    <div class="d-flex justify-content-between align-items-center pt-2">
                                        <span class="fw-bold">Total</span>
                                        <span
                                            class="fw-bold text-primary-green fs-5"><?php echo number_format($order['total_amount']); ?>
                                            ETB</span>
                                    </div>

                                    <!-- Payment Info -->
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <span class="text-muted small">Payment:
                                            <?php echo ucfirst($order['payment_method']); ?></span>
                                        <?php echo getStatusBadge($order['payment_status']); ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="mt-3 pt-3 border-top">
                                        <form method="POST" class="d-flex gap-2 flex-wrap">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                            <input type="hidden" name="update_order_status" value="1">

                                            <?php if ($order['status'] === 'pending'): ?>
                                                <button type="submit" name="status" value="preparing"
                                                    class="btn btn-primary-green rounded-pill flex-grow-1">
                                                    <i class="fas fa-fire me-1"></i> Start Preparing
                                                </button>
                                                <button type="submit" name="status" value="cancelled"
                                                    class="btn btn-outline-danger rounded-pill">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($order['status'] === 'preparing'): ?>
                                                <button type="submit" name="status" value="on_delivery"
                                                    class="btn btn-primary rounded-pill flex-grow-1">
                                                    <i class="fas fa-motorcycle me-1"></i> Send for Delivery
                                                </button>
                                            <?php elseif ($order['status'] === 'on_delivery'): ?>
                                                <button type="submit" name="status" value="delivered"
                                                    class="btn btn-success rounded-pill flex-grow-1">
                                                    <i class="fas fa-check me-1"></i> Mark Delivered
                                                </button>
                                            <?php elseif ($order['status'] === 'cancelled'): ?>
                                                <span class="text-muted">Order was cancelled</span>
                                            <?php else: ?>
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i> Order
                                                    completed</span>
                                            <?php endif; ?>
                                        </form>
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