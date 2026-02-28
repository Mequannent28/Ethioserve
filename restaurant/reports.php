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

// Report 1: Order volume over time
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m-%d') as date, COUNT(*) as count 
    FROM restaurant_orders 
    WHERE restaurant_id = ? 
    GROUP BY date 
    ORDER BY date DESC 
    LIMIT 14
");
$stmt->execute([$restaurant_id]);
$daily_orders = array_reverse($stmt->fetchAll());

// Report 2: Most selling menu items
$stmt = $pdo->prepare("
    SELECT item_name, SUM(quantity) as total_sold
    FROM restaurant_order_items oi
    JOIN restaurant_orders o ON oi.order_id = o.id
    WHERE o.restaurant_id = ?
    GROUP BY item_name
    ORDER BY total_sold DESC
    LIMIT 10
");
$stmt->execute([$restaurant_id]);
$top_items = $stmt->fetchAll();

// Report 3: Sales by Status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM restaurant_orders WHERE restaurant_id = ? GROUP BY status");
$stmt->execute([$restaurant_id]);
$order_statuses = $stmt->fetchAll();

$stmt->execute([$restaurant_id]);
$total_revenue = $stmt->fetchColumn() ?: 0;

// Report 5: Detailed Order Log (The Record List)
$stmt = $pdo->prepare("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.full_name as customer_name, u.phone,
           (SELECT GROUP_CONCAT(CONCAT(item_name, ' x', quantity) SEPARATOR ', ') 
            FROM restaurant_order_items WHERE order_id = o.id) as items_summary
    FROM restaurant_orders o
    JOIN users u ON o.user_id = u.id
    WHERE o.restaurant_id = ?
    ORDER BY o.created_at DESC
");
$stmt->execute([$restaurant_id]);
$detailed_orders = $stmt->fetchAll();

// Page Title
$page_title = "Business Reports - " . htmlspecialchars($restaurant['name']);
include '../includes/header.php';
?>

<style>
    @media print {
        @page {
            size: A4;
            margin: 10mm;
        }

        body {
            background: #fff !important;
            font-size: 11pt;
        }

        .sidebar,
        nav,
        footer,
        .btn,
        .no-print {
            display: none !important;
        }

        .main-content,
        main {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
            background: #fff !important;
        }

        .container-fluid {
            width: 100% !important;
            max-width: 100% !important;
        }

        .card {
            border: 1px solid #eee !important;
            box-shadow: none !important;
            break-inside: avoid;
            margin-bottom: 20px !important;
        }

        .row {
            display: flex !important;
            flex-wrap: wrap !important;
            margin-right: -15px;
            margin-left: -15px;
        }

        .col-md-4 {
            width: 33.33% !important;
            flex: 0 0 33.33% !important;
            padding: 0 15px;
        }

        .col-lg-7 {
            width: 60% !important;
            flex: 0 0 60% !important;
            padding: 0 15px;
        }

        .col-lg-5 {
            width: 40% !important;
            flex: 0 0 40% !important;
            padding: 0 15px;
        }

        .col-md-12 {
            width: 100% !important;
            flex: 0 0 100% !important;
            padding: 0 15px;
        }

        canvas {
            max-width: 100% !important;
            height: auto !important;
        }

        .bg-success,
        .bg-warning,
        .bg-primary,
        .progress-bar {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<div class="d-flex">
    <?php include '../includes/sidebar_restaurant.php'; ?>

    <main class="flex-grow-1 p-4" style="background: #fdfdfd; min-height: 100vh;">
        <div class="container-fluid">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1">Performance Intelligence</h2>
                    <p class="text-muted mb-0 text-uppercase small letter-spacing-1">Restaurant Analytics Dashboard</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-success rounded-3 px-4 shadow-sm" onclick="window.print()">
                        <i class="fas fa-file-pdf me-2"></i> Export PDF
                    </button>
                </div>
            </div>

            <!-- Stats Overview -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 text-white"
                        style="background: linear-gradient(45deg, #1B5E20, #2E7D32);">
                        <h6 class="text-white-50 small fw-bold mb-3">TOTAL REVENUE (PAID)</h6>
                        <h2 class="fw-bold mb-0"><?php echo number_format($total_revenue, 2); ?> <small
                                class="fs-6">ETB</small></h2>
                        <div class="mt-3 small">
                            <i class="fas fa-arrow-up me-1"></i> Lifetime Earnings
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-warning">
                        <h6 class="text-muted small fw-bold mb-3">TOTAL ORDERS</h6>
                        <h2 class="fw-bold mb-0">
                            <?php echo number_format(array_sum(array_column($order_statuses, 'count'))); ?>
                        </h2>
                        <div class="mt-3 small text-warning">
                            <i class="fas fa-shopping-basket me-1"></i> Customer Purchases
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white border-start border-4 border-primary">
                        <h6 class="text-muted small fw-bold mb-3">COMPLETED DELIVERIES</h6>
                        <h2 class="fw-bold mb-0">
                            <?php
                            $delivered = 0;
                            foreach ($order_statuses as $os)
                                if ($os['status'] == 'delivered')
                                    $delivered = $os['count'];
                            echo number_format($delivered);
                            ?>
                        </h2>
                        <div class="mt-3 small text-primary">
                            <i class="fas fa-check-circle me-1"></i> Fulfilled Success
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Sales Trend -->
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h5 class="fw-bold mb-0">Order Volume Trend</h5>
                            <span class="badge bg-light text-dark">Last 14 Days</span>
                        </div>
                        <canvas id="ordersTrendChart" height="280"></canvas>
                    </div>
                </div>

                <!-- Status Breakdown -->
                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-white h-100">
                        <h5 class="fw-bold mb-4">Order Status Breakdown</h5>
                        <div style="height: 250px;">
                            <canvas id="statusDonutChart"></canvas>
                        </div>
                        <div class="mt-4">
                            <?php foreach ($order_statuses as $os): ?>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="small text-muted"><?php echo ucfirst($os['status']); ?></span>
                                    <span class="fw-bold"><?php echo $os['count']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Detailed Order Log -->
                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-receipt text-success me-2"></i>Detailed Order Transaction Log
                            </h5>
                            <span class="badge bg-success rounded-pill px-3"><?php echo count($detailed_orders); ?>
                                Orders</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr class="small text-uppercase tracking-wider">
                                        <th class="ps-4 py-3">Order ID</th>
                                        <th>Customer</th>
                                        <th>Items / Products</th>
                                        <th>Total (ETB)</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Order Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($detailed_orders)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No order history found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($detailed_orders as $ord): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold text-primary">
                                                    #ORD-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($ord['customer_name']); ?>
                                                    </div>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($ord['phone']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="small text-truncate" style="max-width: 300px;"
                                                        title="<?php echo htmlspecialchars($ord['items_summary']); ?>">
                                                        <?php echo htmlspecialchars($ord['items_summary']); ?>
                                                    </div>
                                                </td>
                                                <td><span
                                                        class="fw-bold"><?php echo number_format($ord['total_amount'], 2); ?></span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill <?php
                                                    echo match ($ord['status']) {
                                                        'delivered' => 'bg-success',
                                                        'preparing' => 'bg-info',
                                                        'pending' => 'bg-warning text-dark',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?> w-100">
                                                        <?php echo ucfirst($ord['status']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="small fw-bold">
                                                        <?php echo date('d M, Y', strtotime($ord['created_at'])); ?></div>
                                                    <div class="extra-small text-muted">
                                                        <?php echo date('h:i A', strtotime($ord['created_at'])); ?></div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Trend Chart
    new Chart(document.getElementById('ordersTrendChart'), {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($daily_orders, 'date')); ?>,
            datasets: [{
                label: 'Orders',
                data: <?php echo json_encode(array_column($daily_orders, 'count')); ?>,
                backgroundColor: '#FFB300',
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true } }
        }
    });

    // Doughnut Chart
    new Chart(document.getElementById('statusDonutChart'), {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($order_statuses, 'status')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($order_statuses, 'count')); ?>,
                backgroundColor: ['#43A047', '#FFB300', '#1E88E5', '#E53935', '#757575'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            cutout: '75%'
        }
    });
</script>

<?php include '../includes/footer.php'; ?>