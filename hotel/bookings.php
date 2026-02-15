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

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_id = (int) $_POST['booking_id'];
        $new_status = sanitize($_POST['status']);

        $valid_statuses = ['pending', 'approved', 'cancelled'];
        if (in_array($new_status, $valid_statuses)) {
            $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$new_status, $booking_id, $hotel_id]);
            redirectWithMessage('bookings.php', 'success', 'Booking status updated');
        }
    }
}

// Filter by status
$status_filter = sanitize($_GET['status'] ?? '');
$where_clause = "WHERE b.hotel_id = ?";
$params = [$hotel_id];

if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'cancelled'])) {
    $where_clause .= " AND b.status = ?";
    $params[] = $status_filter;
}

// Get all bookings
$stmt = $pdo->prepare("
    SELECT b.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    $where_clause
    ORDER BY b.booking_date ASC, b.booking_time ASC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Hotel Dashboard</title>
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
                    <h2 class="fw-bold mb-0">Booking Requests</h2>
                    <p class="text-muted">Manage bookings for <?php echo htmlspecialchars($hotel['name']); ?></p>
                </div>
                <div class="d-flex gap-2">
                    <a href="?status=pending"
                        class="btn btn-outline-warning rounded-pill <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">Pending</a>
                    <a href="?status=approved"
                        class="btn btn-outline-success rounded-pill <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">Approved</a>
                    <a href="?status=cancelled"
                        class="btn btn-outline-danger rounded-pill <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">Cancelled</a>
                    <a href="bookings.php" class="btn btn-outline-secondary rounded-pill">All</a>
                </div>
            </div>

            <?php if (empty($bookings)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-calendar-times text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No bookings found</h4>
                    <p class="text-muted">Booking requests will appear here when customers make them.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($bookings as $booking): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div
                                    class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-0">#<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
                                        </h6>
                                    </div>
                                    <?php echo getStatusBadge($booking['status']); ?>
                                </div>
                                <div class="card-body">
                                    <!-- Booking Type -->
                                    <div class="text-center mb-3">
                                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                            style="width: 60px; height: 60px;">
                                            <i class="fas fa-<?php
                                            echo $booking['booking_type'] === 'room' ? 'bed' :
                                                ($booking['booking_type'] === 'table' ? 'utensils' : 'building');
                                            ?> text-primary-green fs-4"></i>
                                        </div>
                                        <h5 class="fw-bold mb-0"><?php echo ucfirst($booking['booking_type']); ?></h5>
                                    </div>

                                    <!-- Customer Info -->
                                    <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-3">
                                        <div class="bg-white rounded-circle p-2">
                                            <i class="fas fa-user text-primary-green"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($booking['customer_name']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($booking['customer_phone'] ?? $booking['customer_email']); ?>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Date & Time -->
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <div class="p-2 bg-light rounded-3 text-center">
                                                <i class="fas fa-calendar text-primary-green d-block mb-1"></i>
                                                <small class="text-muted d-block">Date</small>
                                                <strong><?php echo date('M d, Y', strtotime($booking['booking_date'])); ?></strong>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="p-2 bg-light rounded-3 text-center">
                                                <i class="fas fa-clock text-primary-green d-block mb-1"></i>
                                                <small class="text-muted d-block">Time</small>
                                                <strong><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></strong>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Requested On -->
                                    <p class="text-muted small text-center mb-3">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Requested on <?php echo date('M d, Y', strtotime($booking['created_at'])); ?>
                                    </p>

                                    <!-- Action Buttons -->
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <form method="POST" class="d-flex gap-2">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="update_booking_status" value="1">
                                            <button type="submit" name="status" value="approved"
                                                class="btn btn-success rounded-pill flex-grow-1">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                            <button type="submit" name="status" value="cancelled"
                                                class="btn btn-outline-danger rounded-pill">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    <?php elseif ($booking['status'] === 'approved'): ?>
                                        <form method="POST">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="update_booking_status" value="1">
                                            <button type="submit" name="status" value="cancelled"
                                                class="btn btn-outline-danger rounded-pill w-100">
                                                <i class="fas fa-times me-1"></i> Cancel Booking
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-ban me-1"></i> Booking was cancelled
                                        </div>
                                    <?php endif; ?>
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