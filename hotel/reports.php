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

// Report 1: Bookings by Type (Room, Table, Hall)
$stmt = $pdo->prepare("SELECT booking_type, COUNT(*) as count FROM bookings WHERE hotel_id = ? GROUP BY booking_type");
$stmt->execute([$hotel_id]);
$booking_types = $stmt->fetchAll();

// Report 2: Bookings by Status
$stmt = $pdo->prepare("SELECT status, COUNT(*) as count FROM bookings WHERE hotel_id = ? GROUP BY status");
$stmt->execute([$hotel_id]);
$booking_statuses = $stmt->fetchAll();

// Report 3: Monthly Booking Revenue (Estimated from approved bookings)
// Note: bookings table doesn't have price, but orders do. If user meant bookings specifically, we show counts.
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
    FROM bookings 
    WHERE hotel_id = ? 
    GROUP BY month 
    ORDER BY month DESC 
    LIMIT 6
");
$stmt->execute([$hotel_id]);
$monthly_bookings = array_reverse($stmt->fetchAll());

// Report 4: Most ordered menu items
$stmt = $pdo->prepare("
    SELECT mi.name, SUM(oi.quantity) as total_sold
    FROM order_items oi
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.hotel_id = ?
    GROUP BY mi.id
    ORDER BY total_sold DESC
    LIMIT 5
");
$stmt->execute([$hotel_id]);
$top_selling_items = $stmt->fetchAll();

// Report 5: Detailed Booking Log (The Record List)
$stmt = $pdo->prepare("
    SELECT b.id, b.booking_type, b.status, b.created_at, u.full_name as customer_name, u.phone, u.email
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    WHERE b.hotel_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$hotel_id]);
$detailed_bookings = $stmt->fetchAll();

// Page Title
$page_title = "Analytics Reports - " . htmlspecialchars($hotel['name']);
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
            font-size: 12pt;
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
            margin-bottom: 15px !important;
        }

        .row {
            display: flex !important;
            flex-wrap: wrap !important;
        }

        .col-md-3 {
            width: 25% !important;
            flex: 0 0 25% !important;
        }

        .col-lg-8 {
            width: 65% !important;
            flex: 0 0 65% !important;
        }

        .col-lg-4 {
            width: 35% !important;
            flex: 0 0 35% !important;
        }

        .col-lg-6 {
            width: 50% !important;
            flex: 0 0 50% !important;
        }

        canvas {
            max-width: 100% !important;
            height: auto !important;
        }

        h2 {
            font-size: 22pt !important;
        }

        h5 {
            font-size: 16pt !important;
        }

        .bg-primary,
        .bg-success,
        .bg-info,
        .bg-warning,
        .badge {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
    }
</style>

<div class="d-flex">
    <?php include '../includes/sidebar_hotel.php'; ?>

    <main class="flex-grow-1 p-4" style="background: #f8f9fa; min-height: 100vh;">
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold text-dark mb-1">Business Analytics</h2>
                    <p class="text-muted mb-0">Deep insights into your hotel's performance.</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-dark">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                    <a href="dashboard.php" class="btn btn-warning shadow-sm">
                        <i class="fas fa-arrow-left me-2"></i> Back to Dashboard
                    </a>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <!-- Summary Cards -->
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                                <i class="fas fa-calendar-check text-primary fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small uppercase fw-bold">TOTAL BOOKINGS</h6>
                                <h3 class="fw-bold mb-0">
                                    <?php
                                    $total_b = array_sum(array_column($booking_types, 'count'));
                                    echo number_format($total_b);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white text-dark">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-warning bg-opacity-20 p-3 rounded-4 border border-warning">
                                <i class="fas fa-bed text-warning fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small uppercase fw-bold">ROOM BOOKINGS</h6>
                                <h3 class="fw-bold mb-0">
                                    <?php
                                    $room_count = 0;
                                    foreach ($booking_types as $bt)
                                        if ($bt['booking_type'] == 'room')
                                            $room_count = $bt['count'];
                                    echo number_format($room_count);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-success bg-opacity-10 p-3 rounded-4">
                                <i class="fas fa-utensils text-success fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small uppercase fw-bold">TABLE BOOKINGS</h6>
                                <h3 class="fw-bold mb-0">
                                    <?php
                                    $table_count = 0;
                                    foreach ($booking_types as $bt)
                                        if ($bt['booking_type'] == 'table')
                                            $table_count = $bt['count'];
                                    echo number_format($table_count);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-3">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-3 bg-white">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-info bg-opacity-10 p-3 rounded-4">
                                <i class="fas fa-landmark text-info fs-4"></i>
                            </div>
                            <div>
                                <h6 class="text-muted mb-1 small uppercase fw-bold">HALL BOOKINGS</h6>
                                <h3 class="fw-bold mb-0">
                                    <?php
                                    $hall_count = 0;
                                    foreach ($booking_types as $bt)
                                        if ($bt['booking_type'] == 'hall')
                                            $hall_count = $bt['count'];
                                    echo number_format($hall_count);
                                    ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <!-- Chart 1: Booking Growth -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white">
                        <h5 class="fw-bold mb-4">Booking Activity (Last 6 Months)</h5>
                        <canvas id="monthlyChart" height="250"></canvas>
                    </div>
                </div>

                <!-- Chart 2: Booking Distribution -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100 p-4 bg-white">
                        <h5 class="fw-bold mb-4">Booking Distribution</h5>
                        <canvas id="typeChart"></canvas>
                    </div>
                </div>

                <!-- Report: Top Selling Food Items -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100 bg-white overflow-hidden">
                        <div class="p-4 border-bottom bg-light">
                            <h5 class="fw-bold mb-0"><i class="fas fa-trophy text-warning me-2"></i>Top Selling Menu
                                Items</h5>
                        </div>
                        <div class="table-responsive p-0">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-4">Item Name</th>
                                        <th class="text-center">Total Sold</th>
                                        <th class="text-end pe-4">Trend</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($top_selling_items)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center py-4 text-muted">No sales data available yet.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($top_selling_items as $item): ?>
                                            <tr>
                                                <td class="ps-4">
                                                    <span class="fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-success bg-opacity-10 text-success p-2 rounded-3">
                                                        <?php echo $item['total_sold']; ?> units
                                                    </span>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <i class="fas fa-chart-line text-success"></i>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Detailed Booking Log -->
                <div class="col-12 mt-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-white overflow-hidden">
                        <div class="p-4 border-bottom bg-light d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0 text-dark">
                                <i class="fas fa-list-ul text-warning me-2"></i>Detailed Booking Transaction Log
                            </h5>
                            <span
                                class="badge bg-warning text-dark rounded-pill px-3"><?php echo count($detailed_bookings); ?>
                                Records</span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr class="small text-uppercase tracking-wider">
                                        <th class="ps-4 py-3">Booking ID</th>
                                        <th>Customer Name</th>
                                        <th>Booking Type</th>
                                        <th>Status</th>
                                        <th>Contact Info</th>
                                        <th class="text-end pe-4">Date & Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($detailed_bookings)): ?>
                                        <tr>
                                            <td colspan="6" class="text-center py-5 text-muted">No booking records found.
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($detailed_bookings as $bk): ?>
                                            <tr>
                                                <td class="ps-4 fw-bold">
                                                    #BK-<?php echo str_pad($bk['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark">
                                                        <?php echo htmlspecialchars($bk['customer_name']); ?></div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark border px-3">
                                                        <i
                                                            class="fas <?php echo $bk['booking_type'] == 'room' ? 'fa-bed' : ($bk['booking_type'] == 'table' ? 'fa-utensils' : 'fa-landmark'); ?> me-1"></i>
                                                        <?php echo ucfirst($bk['booking_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge rounded-pill <?php
                                                    echo match ($bk['status']) {
                                                        'approved' => 'bg-success',
                                                        'pending' => 'bg-warning text-dark',
                                                        'cancelled' => 'bg-danger',
                                                        default => 'bg-secondary'
                                                    };
                                                    ?> w-100">
                                                        <?php echo ucfirst($bk['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="small text-muted">
                                                        <div><i class="fas fa-phone me-1 small"></i>
                                                            <?php echo htmlspecialchars($bk['phone']); ?></div>
                                                        <div><i class="fas fa-envelope me-1 small"></i>
                                                            <?php echo htmlspecialchars($bk['email']); ?></div>
                                                    </div>
                                                </td>
                                                <td class="text-end pe-4">
                                                    <div class="fw-bold">
                                                        <?php echo date('d M, Y', strtotime($bk['created_at'])); ?></div>
                                                    <div class="small text-muted">
                                                        <?php echo date('h:i A', strtotime($bk['created_at'])); ?></div>
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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Monthly Activity Chart
    const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
    new Chart(monthlyCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($monthly_bookings, 'month')); ?>,
            datasets: [{
                label: 'Bookings Volume',
                data: <?php echo json_encode(array_column($monthly_bookings, 'count')); ?>,
                borderColor: '#FFB300',
                backgroundColor: 'rgba(255, 179, 0, 0.1)',
                fill: true,
                tension: 0.4,
                pointRadius: 6,
                pointBackgroundColor: '#FFB300'
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    // Distribution Chart
    const typeCtx = document.getElementById('typeChart').getContext('2d');
    new Chart(typeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_column($booking_types, 'booking_type')); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($booking_types, 'count')); ?>,
                backgroundColor: ['#1e88e5', '#ffb300', '#43a047']
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
</script>

<?php include '../includes/footer.php'; ?>