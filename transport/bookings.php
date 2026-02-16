<?php
require_once '../includes/functions.php';
requireLogin();
requireRole('transport');
require_once '../includes/db.php';

$user_id = getCurrentUserId();

// Get transport company
$stmt = $pdo->prepare("SELECT * FROM transport_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    redirectWithMessage('dashboard.php', 'error', 'Company profile not found');
}

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $action = $_POST['action'] ?? '';
    $booking_id = (int) ($_POST['booking_id'] ?? 0);

    if ($booking_id > 0) {
        // Verify booking belongs to this company
        $stmt = $pdo->prepare("
            SELECT bb.* FROM bus_bookings bb
            JOIN schedules s ON bb.schedule_id = s.id
            JOIN buses b ON s.bus_id = b.id
            WHERE bb.id = ? AND b.company_id = ?
        ");
        $stmt->execute([$booking_id, $company['id']]);
        $booking = $stmt->fetch();

        if ($booking) {
            switch ($action) {
                case 'confirm':
                    $seat_numbers = sanitize($_POST['seat_numbers'] ?? '');
                    $owner_response = sanitize($_POST['owner_response'] ?? '');

                    if (empty($seat_numbers)) {
                        redirectWithMessage('bookings.php', 'error', 'Please assign seat numbers before confirming');
                    }

                    $stmt = $pdo->prepare("
                        UPDATE bus_bookings 
                        SET status = 'confirmed', 
                            seat_numbers = ?, 
                            owner_response = ?,
                            confirmed_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$seat_numbers, $owner_response, $booking_id]);

                    // Create notification for customer
                    $stmt = $pdo->prepare("
                        INSERT INTO booking_notifications (booking_id, user_id, type, title, message)
                        VALUES (?, ?, 'seats_assigned', ?, ?)
                    ");
                    $message = "Your booking #{$booking['booking_reference']} has been confirmed! Seat number(s): $seat_numbers.";
                    if (!empty($owner_response)) {
                        $message .= " Message from {$company['company_name']}: $owner_response";
                    }
                    $stmt->execute([
                        $booking_id,
                        $booking['customer_id'],
                        'Seats Assigned - Booking Confirmed',
                        $message
                    ]);

                    redirectWithMessage('bookings.php', 'success', 'Booking #' . $booking['booking_reference'] . ' confirmed with seat(s): ' . $seat_numbers);
                    break;

                case 'cancel':
                    $cancellation_reason = sanitize($_POST['cancellation_reason'] ?? 'Cancelled by transport company');

                    $stmt = $pdo->prepare("
                        UPDATE bus_bookings 
                        SET status = 'cancelled',
                            cancellation_reason = ?,
                            cancelled_at = NOW() 
                        WHERE id = ?
                    ");
                    $stmt->execute([$cancellation_reason, $booking_id]);

                    // Create notification for customer
                    $stmt = $pdo->prepare("
                        INSERT INTO booking_notifications (booking_id, user_id, type, title, message)
                        VALUES (?, ?, 'booking_cancelled', ?, ?)
                    ");
                    $stmt->execute([
                        $booking_id,
                        $booking['customer_id'],
                        'Booking Cancelled',
                        "Your booking #{$booking['booking_reference']} has been cancelled. Reason: $cancellation_reason"
                    ]);

                    redirectWithMessage('bookings.php', 'success', 'Booking #' . $booking['booking_reference'] . ' cancelled');
                    break;

                case 'update_seats':
                    $seat_numbers = sanitize($_POST['seat_numbers'] ?? '');
                    if (!empty($seat_numbers)) {
                        $stmt = $pdo->prepare("UPDATE bus_bookings SET seat_numbers = ? WHERE id = ?");
                        $stmt->execute([$seat_numbers, $booking_id]);

                        // Notify customer of seat change
                        $stmt = $pdo->prepare("
                            INSERT INTO booking_notifications (booking_id, user_id, type, title, message)
                            VALUES (?, ?, 'seats_assigned', ?, ?)
                        ");
                        $stmt->execute([
                            $booking_id,
                            $booking['customer_id'],
                            'Seat Numbers Updated',
                            "Your seat numbers have been updated to: $seat_numbers for booking #{$booking['booking_reference']}"
                        ]);

                        redirectWithMessage('bookings.php', 'success', 'Seat numbers updated for booking #' . $booking['booking_reference']);
                    }
                    break;
            }
        }
    }
}

// Filter parameters
$status_filter = sanitize($_GET['status'] ?? 'all');
$date_filter = sanitize($_GET['date'] ?? '');
$search = sanitize($_GET['search'] ?? '');

// Build query
$where = "b.company_id = ?";
$params = [$company['id']];

if ($status_filter !== 'all') {
    $where .= " AND bb.status = ?";
    $params[] = $status_filter;
}
if (!empty($date_filter)) {
    $where .= " AND bb.travel_date = ?";
    $params[] = $date_filter;
}
if (!empty($search)) {
    $where .= " AND (bb.booking_reference LIKE ? OR bb.passenger_names LIKE ? OR u.full_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// Fetch bookings
$stmt = $pdo->prepare("
    SELECT bb.*, s.departure_time, s.arrival_time,
           r.origin, r.destination,
           b2.bus_number, bt.name as bus_type, b2.total_seats,
           u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN buses b2 ON s.bus_id = b2.id
    JOIN bus_types bt ON b2.bus_type_id = bt.id
    JOIN buses b ON s.bus_id = b.id
    JOIN users u ON bb.customer_id = u.id
    WHERE $where
    ORDER BY bb.created_at DESC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Stats
$stats_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN bb.status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN bb.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN bb.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN bb.status != 'cancelled' THEN bb.total_amount ELSE 0 END) as total_revenue
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN buses b ON s.bus_id = b.id
    WHERE b.company_id = ?
");
$stats_stmt->execute([$company['id']]);
$stats = $stats_stmt->fetch();

$flash = getFlashMessage();
include('../includes/header.php');
?>

<style>
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
        border-left: 4px solid;
        transition: transform 0.2s;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    .stat-card.pending {
        border-color: #FFC107;
    }

    .stat-card.confirmed {
        border-color: #4CAF50;
    }

    .stat-card.cancelled {
        border-color: #f44336;
    }

    .stat-card.revenue {
        border-color: #2196F3;
    }

    .booking-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s;
        margin-bottom: 16px;
    }

    .booking-card:hover {
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
    }

    .booking-card .header {
        padding: 16px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .booking-card .body {
        padding: 0 20px 20px;
    }

    .booking-card.booking-pending {
        border-left: 5px solid #FFC107;
    }

    .booking-card.booking-confirmed {
        border-left: 5px solid #4CAF50;
    }

    .booking-card.booking-cancelled {
        border-left: 5px solid #f44336;
    }

    .seat-input {
        border: 2px dashed #1B5E20;
        border-radius: 12px;
        padding: 15px;
        background: rgba(27, 94, 32, 0.03);
    }

    .filter-pill {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 14px;
        font-weight: 500;
        text-decoration: none;
        transition: all 0.3s;
        border: 2px solid transparent;
    }

    .filter-pill.active {
        background: #1B5E20;
        color: white;
    }

    .filter-pill:not(.active) {
        background: #f8f9fa;
        color: #333;
    }

    .filter-pill:hover:not(.active) {
        border-color: #1B5E20;
        color: #1B5E20;
    }

    .route-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        background: rgba(27, 94, 32, 0.1);
        color: #1B5E20;
        font-weight: 600;
    }

    .passenger-list {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 12px 16px;
    }

    .passenger-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 6px 0;
    }

    .passenger-item:not(:last-child) {
        border-bottom: 1px solid #e9ecef;
    }
</style>

<div class="container-fluid py-4 px-lg-5">
    <?php include('../includes/sidebar_transport.php'); ?>

    <div class="ms-lg-auto" style="margin-left: 260px !important;">
        <!-- Page Header -->
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <div>
                <h3 class="fw-bold mb-1"><i class="fas fa-ticket-alt text-primary-green me-2"></i>Booking Management
                </h3>
                <p class="text-muted mb-0">Review, approve and assign seat numbers to passenger bookings</p>
            </div>
            <a href="dashboard.php" class="btn btn-outline-secondary rounded-pill">
                <i class="fas fa-arrow-left me-1"></i> Dashboard
            </a>
        </div>

        <?php if ($flash): ?>
            <div
                class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4">
                <i
                    class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> me-2"></i>
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="row g-3 mb-4">
            <div class="col-md-3 col-6">
                <div class="stat-card pending">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold">Pending</small>
                            <h3 class="fw-bold mb-0 mt-1">
                                <?php echo $stats['pending'] ?? 0; ?>
                            </h3>
                        </div>
                        <div class="text-warning opacity-50 fs-1"><i class="fas fa-clock"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card confirmed">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold">Confirmed</small>
                            <h3 class="fw-bold mb-0 mt-1">
                                <?php echo $stats['confirmed'] ?? 0; ?>
                            </h3>
                        </div>
                        <div class="text-success opacity-50 fs-1"><i class="fas fa-check-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card cancelled">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold">Cancelled</small>
                            <h3 class="fw-bold mb-0 mt-1">
                                <?php echo $stats['cancelled'] ?? 0; ?>
                            </h3>
                        </div>
                        <div class="text-danger opacity-50 fs-1"><i class="fas fa-times-circle"></i></div>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="stat-card revenue">
                    <div class="d-flex justify-content-between">
                        <div>
                            <small class="text-muted text-uppercase fw-bold">Revenue</small>
                            <h3 class="fw-bold mb-0 mt-1">
                                <?php echo number_format($stats['total_revenue'] ?? 0); ?>
                            </h3>
                        </div>
                        <div class="text-primary opacity-50 fs-1"><i class="fas fa-money-bill-wave"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3">
                <div class="d-flex flex-wrap gap-2 align-items-center">
                    <div class="d-flex flex-wrap gap-2 flex-grow-1">
                        <a href="bookings.php"
                            class="filter-pill <?php echo $status_filter === 'all' ? 'active' : ''; ?>">
                            <i class="fas fa-list"></i> All (
                            <?php echo $stats['total'] ?? 0; ?>)
                        </a>
                        <a href="bookings.php?status=pending"
                            class="filter-pill <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> Pending (
                            <?php echo $stats['pending'] ?? 0; ?>)
                        </a>
                        <a href="bookings.php?status=confirmed"
                            class="filter-pill <?php echo $status_filter === 'confirmed' ? 'active' : ''; ?>">
                            <i class="fas fa-check"></i> Confirmed (
                            <?php echo $stats['confirmed'] ?? 0; ?>)
                        </a>
                        <a href="bookings.php?status=cancelled"
                            class="filter-pill <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                            <i class="fas fa-times"></i> Cancelled (
                            <?php echo $stats['cancelled'] ?? 0; ?>)
                        </a>
                    </div>
                    <form class="d-flex gap-2" method="GET">
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                        <input type="date" name="date" class="form-control rounded-pill border-0 bg-light"
                            value="<?php echo htmlspecialchars($date_filter); ?>" onchange="this.form.submit()">
                        <input type="text" name="search" class="form-control rounded-pill border-0 bg-light"
                            placeholder="Search..." value="<?php echo htmlspecialchars($search); ?>">
                        <button class="btn btn-primary-green rounded-pill px-3"><i class="fas fa-search"></i></button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Bookings List -->
        <?php if (empty($bookings)): ?>
            <div class="text-center py-5">
                <i class="fas fa-inbox text-muted fs-1 mb-3 d-block"></i>
                <h5 class="text-muted">No bookings found</h5>
                <p class="text-muted">Bookings will appear here when customers book your buses</p>
            </div>
        <?php else: ?>
            <?php foreach ($bookings as $bk): ?>
                <?php
                $status_class = 'booking-' . $bk['status'];
                $status_badge = match ($bk['status']) {
                    'pending' => '<span class="badge bg-warning text-dark px-3 py-2 rounded-pill"><i class="fas fa-clock me-1"></i>Pending</span>',
                    'confirmed' => '<span class="badge bg-success px-3 py-2 rounded-pill"><i class="fas fa-check me-1"></i>Confirmed</span>',
                    'cancelled' => '<span class="badge bg-danger px-3 py-2 rounded-pill"><i class="fas fa-times me-1"></i>Cancelled</span>',
                    default => '<span class="badge bg-secondary px-3 py-2 rounded-pill">' . $bk['status'] . '</span>'
                };
                $passenger_names = array_filter(explode('|', $bk['passenger_names'] ?? ''));
                $passenger_phones = array_filter(explode('|', $bk['passenger_phones'] ?? ''));
                ?>
                <div class="booking-card <?php echo $status_class; ?>">
                    <div class="header">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <h6 class="fw-bold mb-0 font-monospace">
                                <?php echo htmlspecialchars($bk['booking_reference']); ?>
                            </h6>
                            <?php echo $status_badge; ?>
                            <span class="route-badge">
                                <?php echo htmlspecialchars($bk['origin']); ?> →
                                <?php echo htmlspecialchars($bk['destination']); ?>
                            </span>
                        </div>
                        <div class="text-end">
                            <small class="text-muted d-block">
                                <?php echo date('M d, Y', strtotime($bk['created_at'])); ?>
                            </small>
                            <strong class="text-primary-green">
                                <?php echo number_format($bk['total_amount']); ?> ETB
                            </strong>
                        </div>
                    </div>
                    <div class="body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <small class="text-muted d-block mb-1"><i class="fas fa-user me-1"></i>Booked By</small>
                                <strong>
                                    <?php echo htmlspecialchars($bk['customer_name']); ?>
                                </strong>
                                <br><small class="text-muted">
                                    <?php echo htmlspecialchars($bk['customer_phone']); ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block mb-1"><i class="fas fa-calendar me-1"></i>Travel Date</small>
                                <strong>
                                    <?php echo date('M d, Y', strtotime($bk['travel_date'])); ?>
                                </strong>
                                <br><small class="text-muted">
                                    <?php echo date('h:i A', strtotime($bk['departure_time'])); ?>
                                </small>
                            </div>
                            <div class="col-md-4">
                                <small class="text-muted d-block mb-1"><i class="fas fa-bus me-1"></i>Bus</small>
                                <strong>
                                    <?php echo htmlspecialchars($bk['bus_number']); ?>
                                </strong> (
                                <?php echo htmlspecialchars($bk['bus_type']); ?>)
                                <br><small class="text-muted">
                                    <?php echo $bk['num_passengers']; ?> passenger(s)
                                </small>
                            </div>
                        </div>

                        <!-- Passenger Details -->
                        <?php if (!empty($passenger_names)): ?>
                            <div class="passenger-list mt-3">
                                <small class="fw-bold text-muted text-uppercase d-block mb-2"><i
                                        class="fas fa-users me-1"></i>Passengers</small>
                                <?php foreach ($passenger_names as $idx => $name): ?>
                                    <div class="passenger-item">
                                        <span><strong>
                                                <?php echo ($idx + 1); ?>.
                                            </strong>
                                            <?php echo htmlspecialchars($name); ?>
                                        </span>
                                        <span class="text-muted small">
                                            <?php echo htmlspecialchars($passenger_phones[$idx] ?? ''); ?>
                                        </span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Pickup / Dropoff -->
                        <?php if (!empty($bk['pickup_point']) || !empty($bk['dropoff_point'])): ?>
                            <div class="d-flex gap-4 mt-3 flex-wrap">
                                <?php if (!empty($bk['pickup_point'])): ?>
                                    <small><i class="fas fa-map-pin text-primary-green me-1"></i><strong>Pickup:</strong>
                                        <?php echo htmlspecialchars($bk['pickup_point']); ?>
                                    </small>
                                <?php endif; ?>
                                <?php if (!empty($bk['dropoff_point'])): ?>
                                    <small><i class="fas fa-flag-checkered text-danger me-1"></i><strong>Drop-off:</strong>
                                        <?php echo htmlspecialchars($bk['dropoff_point']); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Current Seat Numbers -->
                        <?php if (!empty($bk['seat_numbers'])): ?>
                            <div class="mt-3">
                                <span class="badge bg-success px-3 py-2 fs-6 rounded-pill">
                                    <i class="fas fa-chair me-1"></i> Seat(s):
                                    <?php echo htmlspecialchars($bk['seat_numbers']); ?>
                                </span>
                            </div>
                        <?php endif; ?>

                        <!-- Actions for Pending Bookings -->
                        <?php if ($bk['status'] === 'pending'): ?>
                            <div class="seat-input mt-3">
                                <form method="POST" class="d-flex flex-column gap-3">
                                    <?php echo csrfField(); ?>
                                    <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                    <div>
                                        <label class="form-label fw-bold mb-1">
                                            <i class="fas fa-chair text-primary-green me-1"></i>Assign Seat/Cher Number(s)
                                        </label>
                                        <input type="text" name="seat_numbers" class="form-control rounded-pill bg-white px-4 py-2"
                                            placeholder="e.g., 12 (or 12,13 for multiple passengers)" required
                                            value="<?php echo htmlspecialchars($bk['seat_numbers'] ?? ''); ?>">
                                        <small class="text-muted mt-1 d-block">
                                            Bus has
                                            <?php echo $bk['total_seats']; ?> seats.
                                            Assign
                                            <?php echo $bk['num_passengers']; ?> seat number(s), separated by commas.
                                        </small>
                                    </div>
                                    <div class="d-flex gap-2">
                                        <button type="submit" name="action" value="confirm"
                                            class="btn btn-success rounded-pill px-4 flex-grow-1">
                                            <i class="fas fa-check me-1"></i> Approve & Assign Seats
                                        </button>
                                        <button type="submit" name="action" value="cancel"
                                            class="btn btn-outline-danger rounded-pill px-4"
                                            onclick="return confirm('Are you sure you want to cancel this booking?')">
                                            <i class="fas fa-times me-1"></i> Reject
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php elseif ($bk['status'] === 'confirmed'): ?>
                            <!-- Allow updating seat numbers for confirmed bookings -->
                            <div class="mt-3">
                                <button class="btn btn-sm btn-outline-primary-green rounded-pill"
                                    onclick="toggleEditSeats(<?php echo $bk['id']; ?>)">
                                    <i class="fas fa-edit me-1"></i> Edit Seat Numbers
                                </button>
                                <div id="editSeats<?php echo $bk['id']; ?>" style="display:none;" class="seat-input mt-2">
                                    <form method="POST" class="d-flex gap-2 align-items-end">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $bk['id']; ?>">
                                        <input type="hidden" name="action" value="update_seats">
                                        <div class="flex-grow-1">
                                            <input type="text" name="seat_numbers"
                                                class="form-control rounded-pill bg-white px-4 py-2"
                                                value="<?php echo htmlspecialchars($bk['seat_numbers'] ?? ''); ?>"
                                                placeholder="Seat numbers">
                                        </div>
                                        <button type="submit" class="btn btn-primary-green rounded-pill px-4">
                                            <i class="fas fa-save me-1"></i> Save
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Payment Info -->
                        <div class="d-flex gap-3 mt-3 small flex-wrap">
                            <span class="text-muted">
                                <i class="fas fa-credit-card me-1"></i>
                                Payment: <strong class="text-capitalize">
                                    <?php echo htmlspecialchars($bk['payment_method'] ?? 'N/A'); ?>
                                </strong>
                            </span>
                            <span class="text-muted">
                                <i class="fas fa-receipt me-1"></i>
                                Status: <strong class="text-capitalize">
                                    <?php echo $bk['payment_status']; ?>
                                </strong>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleEditSeats(id) {
        const el = document.getElementById('editSeats' + id);
        el.style.display = el.style.display === 'none' ? 'block' : 'none';
    }
</script>

<?php include('../includes/footer.php'); ?>