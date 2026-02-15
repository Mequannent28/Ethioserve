<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php?redirect=track_order");
    exit();
}

$user_id = getCurrentUserId();
$order = null;
$booking = null;
$flight_booking = null;
$bus_booking = null;
$order_items = [];

// Check if viewing specific order
if (isset($_GET['id'])) {
    $order_id = (int) $_GET['id'];
    $stmt = $pdo->prepare("
        SELECT o.*, h.name as hotel_name, h.location as hotel_location, h.image_url as hotel_image
        FROM orders o
        JOIN hotels h ON o.hotel_id = h.id
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$order_id, $user_id]);
    $order = $stmt->fetch();

    if ($order) {
        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, mi.name as item_name, mi.image_url
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $order_items = $stmt->fetchAll();
    }
}

// Check if viewing specific booking
if (isset($_GET['booking'])) {
    $booking_id = (int) $_GET['booking'];
    $stmt = $pdo->prepare("
        SELECT b.*, h.name as hotel_name, h.location as hotel_location, h.image_url as hotel_image
        FROM bookings b
        JOIN hotels h ON b.hotel_id = h.id
        WHERE b.id = ? AND b.customer_id = ?
    ");
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch();
}

// Check if viewing specific flight booking
if (isset($_GET['flight_booking'])) {
    $fb_id = (int) $_GET['flight_booking'];
    $stmt = $pdo->prepare("
        SELECT fb.*, f.airline, f.flight_number, f.destination, f.departure_time, f.price
        FROM flight_bookings fb
        JOIN flights f ON fb.flight_id = f.id
        WHERE fb.id = ? AND fb.customer_id = ?
    ");
    $stmt->execute([$fb_id, $user_id]);
    $flight_booking = $stmt->fetch();
}
// Check if viewing specific bus booking
if (isset($_GET['bus_booking'])) {
    $bb_id = (int) $_GET['bus_booking'];
    $stmt = $pdo->prepare("
        SELECT bb.*, s.departure_time, r.origin, r.destination, tc.company_name, b.bus_number, bt.name as bus_type
        FROM bus_bookings bb
        JOIN schedules s ON bb.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN buses b ON s.bus_id = b.id
        JOIN bus_types bt ON b.bus_type_id = bt.id
        JOIN transport_companies tc ON b.company_id = tc.id
        WHERE bb.id = ? AND bb.customer_id = ?
    ");
    $stmt->execute([$bb_id, $user_id]);
    $bus_booking = $stmt->fetch();
}

// Get user's recent orders
$stmt = $pdo->prepare("
    SELECT o.*, h.name as hotel_name
    FROM orders o
    JOIN hotels h ON o.hotel_id = h.id
    WHERE o.customer_id = ?
    ORDER BY o.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_orders = $stmt->fetchAll();

// Get user's recent bookings
$stmt = $pdo->prepare("
    SELECT b.*, h.name as hotel_name
    FROM bookings b
    JOIN hotels h ON b.hotel_id = h.id
    WHERE b.customer_id = ?
    ORDER BY b.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_bookings = $stmt->fetchAll();

// Get user's recent flight bookings
$stmt = $pdo->prepare("
    SELECT fb.*, f.destination, f.airline 
    FROM flight_bookings fb
    JOIN flights f ON fb.flight_id = f.id
    WHERE fb.customer_id = ?
    ORDER BY fb.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_flights = $stmt->fetchAll();

// Get user's recent bus bookings
$stmt = $pdo->prepare("
    SELECT bb.*, r.origin, r.destination, tc.company_name
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN transport_companies tc ON b.company_id = tc.id
    WHERE bb.customer_id = ?
    ORDER BY bb.created_at DESC
    LIMIT 10
");
$stmt->execute([$user_id]);
$recent_bus_bookings = $stmt->fetchAll();

include('../includes/header.php');
?>

<main class="container py-5">
    <?php echo displayFlashMessage(); ?>

    <div class="row g-5">
        <!-- Main Content -->
        <div class="col-lg-8">
            <h3 class="fw-bold mb-4">
                <i class="fas fa-truck text-primary-green me-2"></i>Track Your Orders & Bookings
            </h3>

            <?php if ($order): ?>
                <!-- Order Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Order #<?php echo str_pad($order['id'], 5, '0', STR_PAD_LEFT); ?></h5>
                            <?php echo getStatusBadge($order['status']); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Order Progress -->
                        <div class="order-progress mb-4">
                            <?php
                            $statuses = ['pending', 'preparing', 'on_delivery', 'delivered'];
                            $current_index = array_search($order['status'], $statuses);
                            if ($order['status'] === 'cancelled')
                                $current_index = -1;
                            ?>
                            <div class="d-flex justify-content-between position-relative">
                                <div class="progress position-absolute w-100" style="top: 15px; height: 3px; z-index: 0;">
                                    <div class="progress-bar bg-primary-green"
                                        style="width: <?php echo max(0, ($current_index / 3) * 100); ?>%"></div>
                                </div>
                                <?php foreach ($statuses as $index => $status): ?>
                                    <div class="text-center position-relative" style="z-index: 1;">
                                        <div class="rounded-circle d-flex align-items-center justify-content-center mx-auto mb-2 
                                            <?php echo $index <= $current_index ? 'bg-primary-green text-white' : 'bg-light text-muted'; ?>"
                                            style="width: 30px; height: 30px;">
                                            <?php if ($index <= $current_index): ?>
                                                <i class="fas fa-check small"></i>
                                            <?php else: ?>
                                                <span class="small"><?php echo $index + 1; ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <small
                                            class="<?php echo $index <= $current_index ? 'text-primary-green fw-bold' : 'text-muted'; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $status)); ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Restaurant Info -->
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                            <img src="<?php echo htmlspecialchars($order['hotel_image'] ?? 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=100'); ?>"
                                class="rounded-3" width="60" height="60" style="object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($order['hotel_name']); ?></h6>
                                <small class="text-muted">
                                    <i
                                        class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($order['hotel_location']); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Order Items -->
                        <h6 class="fw-bold mb-3">Order Items</h6>
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td style="width: 50px;">
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?w=50'); ?>"
                                                class="rounded" width="40" height="40" style="object-fit: cover;">
                                        </td>
                                        <td><?php echo htmlspecialchars($item['item_name']); ?></td>
                                        <td class="text-center">x<?php echo $item['quantity']; ?></td>
                                        <td class="text-end"><?php echo number_format($item['price'] * $item['quantity']); ?>
                                            ETB</td>
                                    </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>

                        <!-- Order Summary -->
                        <div class="border-top pt-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Subtotal</span>
                                <span><?php echo number_format($order['total_amount'] - 150); ?> ETB</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted">Delivery Fee</span>
                                <span>150 ETB</span>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total</span>
                                <span class="text-primary-green"><?php echo number_format($order['total_amount']); ?>
                                    ETB</span>
                            </div>
                        </div>

                        <!-- Payment Info -->
                        <div class="mt-3 p-3 bg-light rounded-3">
                            <div class="d-flex justify-content-between">
                                <span class="text-muted">Payment Method</span>
                                <span
                                    class="fw-bold"><?php echo ucfirst(str_replace('_', ' ', $order['payment_method'])); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mt-2">
                                <span class="text-muted">Payment Status</span>
                                <?php echo getStatusBadge($order['payment_status']); ?>
                            </div>
                        </div>

                        <!-- Cancel Button -->
                        <?php if (in_array($order['status'], ['pending', 'preparing'])): ?>
                            <button class="btn btn-outline-danger rounded-pill mt-4"
                                onclick="cancelOrder(<?php echo $order['id']; ?>)">
                                <i class="fas fa-times me-2"></i>Cancel Order
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

            <?php elseif ($booking): ?>
                <!-- Booking Details -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white border-0 py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="fw-bold mb-0">Booking #<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?>
                            </h5>
                            <?php echo getStatusBadge($booking['status']); ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Venue Info -->
                        <div class="d-flex align-items-center gap-3 mb-4 p-3 bg-light rounded-3">
                            <img src="<?php echo htmlspecialchars($booking['hotel_image'] ?? 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=100'); ?>"
                                class="rounded-3" width="60" height="60" style="object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['hotel_name']); ?></h6>
                                <small class="text-muted">
                                    <i
                                        class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($booking['hotel_location']); ?>
                                </small>
                            </div>
                        </div>

                        <!-- Booking Details -->
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase">Booking Type</small>
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fas fa-<?php
                                        echo $booking['booking_type'] === 'room' ? 'bed' :
                                            ($booking['booking_type'] === 'table' ? 'utensils' : 'building');
                                        ?> me-2 text-primary-green"></i>
                                        <?php echo ucfirst($booking['booking_type']); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase">Date</small>
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fas fa-calendar me-2 text-primary-green"></i>
                                        <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase">Time</small>
                                    <h6 class="mb-0 fw-bold">
                                        <i class="fas fa-clock me-2 text-primary-green"></i>
                                        <?php echo date('h:i A', strtotime($booking['booking_time'])); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase">Status</small>
                                    <h6 class="mb-0"><?php echo getStatusBadge($booking['status']); ?></h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($flight_booking): ?>
                <!-- New Booking Success Message -->
                <?php if (isset($_GET['new_pnr'])): ?>
                    <div class="alert bg-success text-white border-0 p-4 rounded-4 mb-4 shadow d-flex align-items-center gap-4">
                        <div class="bg-white text-success rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 60px; height: 60px; flex-shrink: 0;">
                            <i class="fas fa-check fs-2"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-1">Booking Confirmed!</h4>
                            <p class="mb-0 opacity-75">Your PNR Code is
                                <strong><?php echo htmlspecialchars($_GET['new_pnr']); ?></strong>. Please keep this for your
                                records.
                            </p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Flight Booking Details -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden rounded-4">
                    <div class="card-header bg-primary-green text-white border-0 py-4 px-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-bold mb-0">Flight Ticket (PNR:
                                    <?php echo htmlspecialchars($flight_booking['pnr_code']); ?>)
                                </h4>
                                <p class="small mb-0 opacity-75">EthioServe Travel Reservation</p>
                            </div>
                            <?php echo getStatusBadge($flight_booking['status']); ?>
                        </div>
                    </div>
                    <div class="card-body p-5">
                        <div class="row align-items-center mb-5">
                            <div class="col-md-5">
                                <p class="text-muted small text-uppercase mb-1">Origin</p>
                                <h2 class="fw-bold mb-0">ADD</h2>
                                <p class="mb-0">Addis Ababa</p>
                            </div>
                            <div class="col-md-2 text-center">
                                <i class="fas fa-plane text-primary-green fs-1"></i>
                            </div>
                            <div class="col-md-5 text-end">
                                <p class="text-muted small text-uppercase mb-1">Destination</p>
                                <h2 class="fw-bold mb-0 text-primary-green">
                                    <?php
                                    $dest = explode(' ', $flight_booking['destination']);
                                    echo end($dest) ? trim(end($dest), '()') : 'DEST';
                                    ?>
                                </h2>
                                <p class="mb-0 text-truncate">
                                    <?php echo htmlspecialchars($flight_booking['destination']); ?>
                                </p>
                            </div>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Airline</small>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($flight_booking['airline']); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Flight No.</small>
                                    <h6 class="mb-0 fw-bold">
                                        <?php echo htmlspecialchars($flight_booking['flight_number']); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Departure</small>
                                    <h6 class="mb-0 fw-bold">
                                        <?php echo date('M d, H:i', strtotime($flight_booking['departure_time'])); ?>
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-4 rounded-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div class="d-flex gap-3 align-items-center">
                                    <div class="bg-white p-2 rounded-circle">
                                        <i class="fas fa-user text-primary-green"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted text-uppercase">Passenger</small>
                                        <h6 class="mb-0 fw-bold">
                                            <?php echo htmlspecialchars($flight_booking['passenger_name']); ?>
                                        </h6>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted text-uppercase">Passport</small>
                                    <h6 class="mb-0 fw-bold">
                                        <?php echo htmlspecialchars($flight_booking['passport_number']); ?>
                                    </h6>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Payment Status</span>
                                <div class="d-flex align-items-center gap-3">
                                    <?php echo getStatusBadge($flight_booking['payment_status']); ?>
                                    <?php if ($flight_booking['payment_status'] === 'unpaid'): ?>
                                        <a href="payment.php?type=flight&booking_id=<?php echo $flight_booking['id']; ?>&method=telebirr"
                                            class="btn btn-warning btn-sm rounded-pill px-3 fw-bold">
                                            <i class="fas fa-wallet me-1"></i> Pay Now
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            <?php elseif ($bus_booking): ?>
                <!-- Bus Booking Details -->
                <div class="card border-0 shadow-sm mb-4 overflow-hidden rounded-4">
                    <div class="card-header bg-dark text-warning border-0 py-4 px-5">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="fw-bold mb-0">Bus Ticket (Ref:
                                    <?php echo htmlspecialchars($bus_booking['booking_reference']); ?>)
                                </h4>
                                <p class="small mb-0 opacity-75"><?php echo htmlspecialchars($bus_booking['company_name']); ?></p>
                            </div>
                            <?php echo getStatusBadge($bus_booking['status']); ?>
                        </div>
                    </div>
                    <div class="card-body p-5">
                        <div class="row align-items-center mb-5">
                            <div class="col-md-5">
                                <p class="text-muted small text-uppercase mb-1">From</p>
                                <h2 class="fw-bold mb-0"><?php echo htmlspecialchars($bus_booking['origin']); ?></h2>
                            </div>
                            <div class="col-md-2 text-center">
                                <i class="fas fa-bus text-warning fs-1"></i>
                            </div>
                            <div class="col-md-5 text-end">
                                <p class="text-muted small text-uppercase mb-1">To</p>
                                <h2 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($bus_booking['destination']); ?>
                                </h2>
                            </div>
                        </div>

                        <div class="row g-4 mb-5">
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Date</small>
                                    <h6 class="mb-0 fw-bold"><?php echo date('M d, Y', strtotime($bus_booking['travel_date'])); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Departure</small>
                                    <h6 class="mb-0 fw-bold"><?php echo date('h:i A', strtotime($bus_booking['departure_time'])); ?>
                                    </h6>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="p-3 bg-light rounded-3">
                                    <small class="text-muted text-uppercase d-block mb-1">Bus Number</small>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($bus_booking['bus_number']); ?>
                                        (<?php echo htmlspecialchars($bus_booking['bus_type']); ?>)
                                    </h6>
                                </div>
                            </div>
                        </div>

                        <div class="bg-light p-4 rounded-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <div>
                                    <small class="text-muted text-uppercase">Passengers</small>
                                    <h6 class="mb-0 fw-bold"><?php echo $bus_booking['num_passengers']; ?> Adult(s)</h6>
                                </div>
                                <div class="text-end">
                                    <small class="text-muted text-uppercase">Total Amount</small>
                                    <h6 class="mb-0 fw-bold"><?php echo number_format($bus_booking['total_amount']); ?> ETB</h6>
                                </div>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="fw-bold">Payment Status</span>
                                <?php echo getStatusBadge($bus_booking['payment_status']); ?>
                            </div>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- No Selection -->
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-search text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">Select an order or booking to track</h4>
                    <p class="text-muted">Click on an item from the sidebar to view details</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Recent Orders -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-shopping-bag me-2 text-primary-green"></i>Recent Orders
                    </h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_orders)): ?>
                        <div class="p-4 text-center text-muted">
                            <small>No orders yet</small>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_orders as $o): ?>
                                <a href="?id=<?php echo $o['id']; ?>"
                                    class="list-group-item list-group-item-action py-3 <?php echo ($order && $order['id'] == $o['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold">#<?php echo str_pad($o['id'], 5, '0', STR_PAD_LEFT); ?>
                                            </h6>
                                            <small
                                                class="<?php echo ($order && $order['id'] == $o['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo htmlspecialchars($o['hotel_name']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo getStatusBadge($o['status']); ?>
                                            <br><small
                                                class="<?php echo ($order && $order['id'] == $o['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo date('M d', strtotime($o['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2 text-primary-green"></i>Recent
                        Bookings</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_bookings)): ?>
                        <div class="p-4 text-center text-muted">
                            <small>No bookings yet</small>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_bookings as $b): ?>
                                <a href="?booking=<?php echo $b['id']; ?>"
                                    class="list-group-item list-group-item-action py-3 <?php echo ($booking && $booking['id'] == $b['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo ucfirst($b['booking_type']); ?></h6>
                                            <small
                                                class="<?php echo ($booking && $booking['id'] == $b['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo htmlspecialchars($b['hotel_name']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo getStatusBadge($b['status']); ?>
                                            <br><small
                                                class="<?php echo ($booking && $booking['id'] == $b['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo date('M d', strtotime($b['booking_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Flight Bookings -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-plane-departure me-2 text-primary-green"></i>Recent
                        Flights</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_flights)): ?>
                        <div class="p-4 text-center text-muted">
                            <small>No flights booked yet</small>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_flights as $rf): ?>
                                <a href="?flight_booking=<?php echo $rf['id']; ?>"
                                    class="list-group-item list-group-item-action py-3 <?php echo ($flight_booking && $flight_booking['id'] == $rf['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($rf['destination']); ?></h6>
                                            <small
                                                class="<?php echo ($flight_booking && $flight_booking['id'] == $rf['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo htmlspecialchars($rf['airline']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo getStatusBadge($rf['status']); ?>
                                            <br><small
                                                class="<?php echo ($flight_booking && $flight_booking['id'] == $rf['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo date('M d', strtotime($rf['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Bus Bookings -->
            <div class="card border-0 shadow-sm mt-4">
                <div class="card-header bg-white border-0 py-3">
                    <h6 class="fw-bold mb-0"><i class="fas fa-bus me-2 text-primary-green"></i>Recent Bus Trips</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_bus_bookings)): ?>
                        <div class="p-4 text-center text-muted">
                            <small>No bus tickets yet</small>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_bus_bookings as $rb): ?>
                                <a href="?bus_booking=<?php echo $rb['id']; ?>"
                                    class="list-group-item list-group-item-action py-3 <?php echo ($bus_booking && $bus_booking['id'] == $rb['id']) ? 'active' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($rb['origin']); ?> ➔
                                                <?php echo htmlspecialchars($rb['destination']); ?></h6>
                                            <small
                                                class="<?php echo ($bus_booking && $bus_booking['id'] == $rb['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo htmlspecialchars($rb['company_name']); ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <?php echo getStatusBadge($rb['status']); ?>
                                            <br><small
                                                class="<?php echo ($bus_booking && $bus_booking['id'] == $rb['id']) ? 'text-white-50' : 'text-muted'; ?>">
                                                <?php echo date('M d', strtotime($rb['travel_date'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const csrfToken = '<?php echo generateCSRFToken(); ?>';

    function cancelOrder(orderId) {
        if (!confirm('Are you sure you want to cancel this order?')) return;

        fetch('../api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=cancel_order&order_id=${orderId}&csrf_token=${csrfToken}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Order cancelled successfully');
                    location.reload();
                } else {
                    alert(data.message);
                }
            });
    }

    // Auto-refresh for pending orders
    <?php if ($order && in_array($order['status'], ['pending', 'preparing', 'on_delivery'])): ?>
        setTimeout(() => location.reload(), 30000); // Refresh every 30 seconds
    <?php endif; ?>
</script>

<?php include('../includes/footer.php'); ?>