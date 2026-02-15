<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$booking_id = (int) ($_GET['id'] ?? 0);

if (!$booking_id) {
    header("Location: index.php");
    exit();
}

// Get booking details
$stmt = $pdo->prepare("
    SELECT bb.*, s.departure_time, s.arrival_time,
           r.origin, r.destination, r.estimated_hours,
           b.bus_number, bt.name as bus_type,
           tc.company_name, tc.phone as company_phone
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_types bt ON b.bus_type_id = bt.id
    JOIN transport_companies tc ON b.company_id = tc.id
    WHERE bb.id = ?
");
$stmt->execute([$booking_id]);
$booking = $stmt->fetch();

if (!$booking) {
    header("Location: index.php");
    exit();
}

// Check if user owns this booking or is admin
if (isLoggedIn() && getCurrentUserId() != $booking['customer_id'] && !hasRole('admin')) {
    header("Location: index.php");
    exit();
}

include('../includes/header.php');
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Success Header -->
            <div class="text-center mb-5">
                <div class="bg-success text-white rounded-circle mx-auto mb-4 d-flex align-items-center justify-content-center"
                    style="width: 80px; height: 80px;">
                    <i class="fas fa-check fs-1"></i>
                </div>
                <h2 class="fw-bold text-success">Booking Confirmed!</h2>
                <p class="text-muted">Your bus ticket has been booked successfully</p>
            </div>

            <!-- Ticket Card -->
            <div class="card border-0 shadow-lg mb-4 overflow-hidden">
                <div class="card-header bg-primary-green text-white py-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0 fw-bold">
                            <i class="fas fa-bus me-2"></i><?php echo htmlspecialchars($booking['company_name']); ?>
                        </h5>
                        <span class="badge bg-white text-primary-green fs-6">
                            <?php echo htmlspecialchars($booking['booking_reference']); ?>
                        </span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <!-- Journey Details -->
                    <div class="row g-0 border-bottom">
                        <div class="col-4 text-center p-4 border-end">
                            <small class="text-muted d-block">FROM</small>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['origin']); ?></h4>
                        </div>
                        <div class="col-4 text-center p-4 border-end d-flex flex-column justify-content-center">
                            <i class="fas fa-bus text-primary-green fs-4 mb-2"></i>
                            <small class="text-muted"><?php echo $booking['estimated_hours']; ?> hours</small>
                        </div>
                        <div class="col-4 text-center p-4">
                            <small class="text-muted d-block">TO</small>
                            <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($booking['destination']); ?></h4>
                        </div>
                    </div>

                    <!-- Date and Time -->
                    <div class="row g-0 border-bottom bg-light">
                        <div class="col-6 p-3 text-center border-end">
                            <small class="text-muted d-block">Travel Date</small>
                            <strong><?php echo date('M d, Y', strtotime($booking['travel_date'])); ?></strong>
                        </div>
                        <div class="col-6 p-3 text-center">
                            <small class="text-muted d-block">Departure Time</small>
                            <strong><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></strong>
                        </div>
                    </div>

                    <!-- Booking Details -->
                    <div class="p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <small class="text-muted d-block">Bus Number</small>
                                <strong><?php echo htmlspecialchars($booking['bus_number']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Bus Type</small>
                                <strong><?php echo htmlspecialchars($booking['bus_type']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Seat Numbers</small>
                                <strong
                                    class="text-primary-green fs-5"><?php echo htmlspecialchars($booking['seat_numbers']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Number of Passengers</small>
                                <strong><?php echo $booking['num_passengers']; ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Pickup Point</small>
                                <strong><?php echo htmlspecialchars($booking['pickup_point']); ?></strong>
                            </div>
                            <div class="col-md-6">
                                <small class="text-muted d-block">Drop-off Point</small>
                                <strong><?php echo htmlspecialchars($booking['dropoff_point']); ?></strong>
                            </div>
                        </div>

                        <!-- Passengers -->
                        <div class="mt-4">
                            <h6 class="fw-bold mb-3">Passenger Details</h6>
                            <?php
                            $names = explode('|', $booking['passenger_names']);
                            $phones = explode('|', $booking['passenger_phones']);
                            $seats = explode(',', $booking['seat_numbers']);
                            ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Seat</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php for ($i = 0; $i < count($seats); $i++): ?>
                                            <tr>
                                                <td><?php echo $seats[$i]; ?></td>
                                                <td><?php echo htmlspecialchars($names[$i] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($phones[$i] ?? ''); ?></td>
                                            </tr>
                                        <?php endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="bg-light p-4 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Price per Seat:</span>
                            <span><?php echo number_format($booking['total_amount'] / $booking['num_passengers']); ?>
                                ETB</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Number of Seats:</span>
                            <span><?php echo $booking['num_passengers']; ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <h5 class="fw-bold">Total Amount:</h5>
                            <h5 class="fw-bold text-primary-green">
                                <?php echo number_format($booking['total_amount']); ?> ETB</h5>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Payment Method:</span>
                            <span
                                class="text-capitalize"><?php echo htmlspecialchars($booking['payment_method']); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Status:</span>
                            <?php echo getStatusBadge($booking['status']); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="d-flex gap-3 justify-content-center mb-4">
                <button onclick="window.print()" class="btn btn-outline-primary-green rounded-pill px-4">
                    <i class="fas fa-print me-2"></i>Print Ticket
                </button>
                <a href="buses.php" class="btn btn-primary-green rounded-pill px-4">
                    <i class="fas fa-bus me-2"></i>Book Another Trip
                </a>
            </div>

            <!-- Important Info -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark py-3">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-exclamation-triangle me-2"></i>Important Information</h6>
                </div>
                <div class="card-body">
                    <ul class="mb-0">
                        <li>Please arrive at the boarding point at least 30 minutes before departure.</li>
                        <li>Keep this ticket (or booking reference) with you during the journey.</li>
                        <li>Contact the transport company for any changes or cancellations.</li>
                        <li>Company Phone: <strong><?php echo htmlspecialchars($booking['company_phone']); ?></strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</main>

<style>
    @media print {

        .btn,
        nav,
        footer,
        .no-print {
            display: none !important;
        }

        .card {
            box-shadow: none !important;
            border: 1px solid #ddd !important;
        }
    }
</style>

<?php include('../includes/footer.php'); ?>