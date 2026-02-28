<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('hotel');
$user_id = getCurrentUserId();

// Get hotel details
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
if (!$hotel)
    die("Hotel record not found.");
$hotel_id = $hotel['id'];

// Filter Logic
$date_filter = sanitize($_GET['filter'] ?? 'all');
$where_date = "";
$params = [$hotel_id, $hotel_id];

if ($date_filter === 'today') {
    $where_date = " AND DATE(o.created_at) = CURDATE()";
    $where_date_b = " AND DATE(b.created_at) = CURDATE()";
} elseif ($date_filter === 'yesterday') {
    $where_date = " AND DATE(o.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
    $where_date_b = " AND DATE(b.created_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
} else {
    $where_date = "";
    $where_date_b = "";
}

$sql = "
    (SELECT 
        o.id as trans_id, 
        u.full_name as customer, 
        mi.name as item, 
        oi.quantity as qty, 
        oi.price as unit_price, 
        (oi.quantity * oi.price) as total, 
        o.created_at, 
        'Food Order' as type,
        o.status,
        mi.tax_rate
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    JOIN menu_items mi ON oi.menu_item_id = mi.id
    JOIN users u ON o.customer_id = u.id
    WHERE o.hotel_id = ? $where_date)
    UNION ALL
    (SELECT 
        b.id as trans_id, 
        u.full_name as customer, 
        COALESCE(hr.room_number, CONCAT('Room (', b.booking_type, ')')) as item, 
        1 as qty, 
        COALESCE(hr.price_per_night, 0) as unit_price, 
        COALESCE(hr.price_per_night, 0) as total, 
        b.created_at, 
        'Booking' as type,
        b.status,
        0 as tax_rate
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN hotel_rooms hr ON b.room_id = hr.id
    WHERE b.hotel_id = ? $where_date_b)
    ORDER BY created_at DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Calculate total including tax
$grand_total = 0;
foreach ($transactions as $tx) {
    $tax_amount = ($tx['total'] * $tx['tax_rate']) / 100;
    $grand_total += ($tx['total'] + $tax_amount);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions History - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .main-content {
            padding: 30px;
            min-height: 100vh;
        }

        .table-card {
            border-radius: 15px;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .type-badge-food {
            background-color: #e3f2fd;
            color: #1976d2;
        }

        .type-badge-booking {
            background-color: #f3e5f5;
            color: #7b1fa2;
        }

        @media print {

            .no-print,
            .sidebar-hotel,
            .btn {
                display: none !important;
            }

            .main-content {
                padding: 0 !important;
                width: 100% !important;
            }

            .table-card {
                box-shadow: none;
                border: 1px solid #eee;
            }

            body {
                background: white;
            }
        }

        .filter-btn {
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            font-size: 0.85rem;
            transition: all 0.3s;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include('../includes/sidebar_hotel.php'); ?>

        <main class="main-content flex-grow-1">
            <!-- Header for both screen and print -->
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <div>
                        <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($hotel['name']); ?></h2>
                        <p class="text-muted mb-0">Financial Transaction Report</p>
                    </div>
                    <div class="no-print">
                        <button onclick="window.print()" class="btn btn-outline-dark rounded-pill px-4">
                            <i class="fas fa-print me-2"></i> Print Report
                        </button>
                    </div>
                </div>
                <hr>
            </div>

            <!-- Filters and Totals -->
            <div class="row g-4 mb-4 align-items-center no-print">
                <div class="col-md-6">
                    <div class="d-flex gap-2">
                        <a href="transactions.php?filter=all"
                            class="btn filter-btn <?php echo $date_filter === 'all' ? 'btn-dark' : 'btn-outline-dark'; ?>">All
                            History</a>
                        <a href="transactions.php?filter=today"
                            class="btn filter-btn <?php echo $date_filter === 'today' ? 'btn-success' : 'btn-outline-success'; ?>">Today</a>
                        <a href="transactions.php?filter=yesterday"
                            class="btn filter-btn <?php echo $date_filter === 'yesterday' ? 'btn-primary' : 'btn-outline-primary'; ?>">Yesterday</a>
                    </div>
                </div>
                <div class="col-md-6 text-md-end">
                    <div class="bg-white p-3 rounded-4 shadow-sm border-start border-4 border-warning d-inline-block">
                        <h6 class="text-muted small fw-bold mb-1 uppercase">TOTAL REVENUE
                            (<?php echo strtoupper($date_filter); ?>)</h6>
                        <h3 class="fw-bold mb-0 text-dark"><?php echo number_format($grand_total); ?> <small>ETB</small>
                        </h3>
                    </div>
                </div>
            </div>

            <!-- Total display for Print ONLY -->
            <div class="d-none d-print-block mb-4">
                <div class="alert alert-light border p-4 text-center rounded-4">
                    <h5 class="text-muted mb-1">Grand Total Summary (<?php echo ucfirst($date_filter); ?>)</h5>
                    <h2 class="fw-bold mb-0"><?php echo number_format($grand_total); ?> ETB</h2>
                </div>
            </div>

            <div class="table-card p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Date & Time</th>
                                <th>Type</th>
                                <th>Item / Room</th>
                                <th>Customer</th>
                                <th>Qty</th>
                                <th>Unit Price</th>
                                <th>Tax</th>
                                <th>Total</th>
                                <th class="text-end pe-4">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($transactions)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5">
                                        <i class="fas fa-file-invoice-dollar text-muted mb-3 d-block"
                                            style="font-size: 3rem;"></i>
                                        No transactions found yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $tx): ?>
                                    <tr>
                                        <td class="ps-4 small">
                                            <div class="fw-bold">
                                                <?php echo date('M d, Y', strtotime($tx['created_at'])); ?>
                                            </div>
                                            <div class="text-muted">
                                                <?php echo date('h:i A', strtotime($tx['created_at'])); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span
                                                class="badge rounded-pill <?php echo $tx['type'] == 'Food Order' ? 'type-badge-food' : 'type-badge-booking'; ?>">
                                                <i
                                                    class="fas <?php echo $tx['type'] == 'Food Order' ? 'fa-utensils' : 'fa-bed'; ?> me-1"></i>
                                                <?php echo $tx['type']; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($tx['item']); ?>
                                            </div>
                                            <small class="text-muted">ID: #
                                                <?php echo str_pad($tx['trans_id'], 5, '0', STR_PAD_LEFT); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($tx['customer']); ?>
                                        </td>
                                        <td>x
                                            <?php echo $tx['qty']; ?>
                                        </td>
                                        <td>
                                            <?php echo number_format($tx['unit_price'], 2); ?> ETB
                                        </td>
                                        <td class="small text-muted">
                                            <?php
                                            $tax_amount = ($tx['total'] * $tx['tax_rate']) / 100;
                                            echo number_format($tax_amount, 2);
                                            ?>
                                            <br><small>(<?php echo $tx['tax_rate']; ?>%)</small>
                                        </td>
                                        <td><strong>
                                                <?php echo number_format($tx['total'] + $tax_amount, 2); ?> ETB
                                            </strong></td>
                                        <td class="text-end pe-4">
                                            <?php echo getStatusBadge($tx['status']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>