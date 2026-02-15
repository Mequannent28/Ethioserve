<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php?redirect=book_bus.php&schedule=" . ($_GET['schedule'] ?? ''));
    exit();
}

$schedule_id = (int) ($_GET['schedule'] ?? 0);
$travel_date = sanitize($_GET['date'] ?? date('Y-m-d'));

if (!$schedule_id) {
    redirectWithMessage('buses.php', 'error', 'Invalid schedule selected');
}

// Fetch schedule details
$stmt = $pdo->prepare("
    SELECT s.*, r.origin, r.destination, b.bus_number, tc.company_name, tc.logo_url
    FROM schedules s
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN transport_companies tc ON b.company_id = tc.id
    WHERE s.id = ?
");
$stmt->execute([$schedule_id]);
$schedule = $stmt->fetch();

if (!$schedule) {
    redirectWithMessage('buses.php', 'error', 'Schedule not found');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage("book_bus.php?schedule=$schedule_id&date=$travel_date", 'error', 'Invalid security token');
    }

    $num_passengers = (int) $_POST['num_passengers'];
    $total_amount = $schedule['price'] * $num_passengers;
    $passenger_details = $_POST['passengers']; // Array of passenger info

    // Generate unique reference
    $reference = 'BUS-' . strtoupper(substr(uniqid(), -8));

    try {
        $pdo->beginTransaction();

        // Insert booking
        $stmt = $pdo->prepare("
            INSERT INTO bus_bookings (booking_reference, customer_id, schedule_id, travel_date, num_passengers, total_amount, status, payment_status)
            VALUES (?, ?, ?, ?, ?, ?, 'pending', 'paid')
        ");
        $stmt->execute([
            $reference,
            getCurrentUserId(),
            $schedule_id,
            $travel_date,
            $num_passengers,
            $total_amount
        ]);
        $booking_id = $pdo->lastInsertId();

        // We could save passenger details in a separate table if we had one, 
        // but for now let's just use the booking reference.

        $pdo->commit();

        // Redirect to track order
        header("Location: track_order.php?bus_booking=$booking_id&new_ticket=$reference");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        redirectWithMessage("book_bus.php?schedule=$schedule_id&date=$travel_date", 'error', 'Booking failed: ' . $e->getMessage());
    }
}

include('../includes/header.php');
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <a href="buses.php"
                        class="text-decoration-none text-primary-green small fw-bold mb-3 d-inline-block">
                        <i class="fas fa-arrow-left me-1"></i> Back to Search
                    </a>
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div class="bg-primary-green text-white rounded-circle p-3"
                            style="width: 60px; height: 60px; display: flex; align-items: center; justify-content: center;">
                            <i class="fas fa-bus fs-4"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0">Review & Register</h4>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($schedule['company_name']); ?> •
                                Ticket Registration</p>
                        </div>
                    </div>

                    <div class="bg-light rounded-4 p-4 mb-4">
                        <div class="row g-4 align-items-center text-center">
                            <div class="col-md-5">
                                <p class="text-muted small text-uppercase mb-1">Departure</p>
                                <h4 class="fw-bold mb-0"><?php echo htmlspecialchars($schedule['origin']); ?></h4>
                                <p class="mb-0"><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></p>
                            </div>
                            <div class="col-md-2">
                                <i class="fas fa-long-arrow-alt-right text-primary-green fs-3"></i>
                            </div>
                            <div class="col-md-5">
                                <p class="text-muted small text-uppercase mb-1">Destination</p>
                                <h4 class="fw-bold mb-0 text-primary-green">
                                    <?php echo htmlspecialchars($schedule['destination']); ?>
                                </h4>
                                <p class="mb-0"><?php echo date('M d, Y', strtotime($travel_date)); ?></p>
                            </div>
                        </div>
                    </div>

                    <form method="POST" id="bookingForm">
                        <?php echo csrfField(); ?>

                        <div class="mb-4">
                            <label class="form-label fw-bold">Number of Passengers</label>
                            <select name="num_passengers" id="num_passengers"
                                class="form-select rounded-pill px-4 py-3 bg-light border-0"
                                onchange="updatePassengerForms()">
                                <option value="1">1 Passenger</option>
                                <option value="2">2 Passengers</option>
                                <option value="3">3 Passengers</option>
                                <option value="4">4 Passengers</option>
                            </select>
                        </div>

                        <div id="passengerContainer">
                            <div class="passenger-form mb-4 p-4 border rounded-4">
                                <h6 class="fw-bold mb-3"><i class="fas fa-user-circle me-2"></i>Passenger 1</h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Full Name</label>
                                        <input type="text" name="passengers[0][name]"
                                            class="form-control rounded-pill bg-light border-0" required
                                            placeholder="As shown on ID">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Phone Number</label>
                                        <input type="tel" name="passengers[0][phone]"
                                            class="form-control rounded-pill bg-light border-0" required
                                            placeholder="09xxxxxxxx">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card border-0 bg-dark text-white rounded-4 p-4 mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span>Ticket Price (x<span id="seatCount">1</span>)</span>
                                <span><?php echo number_format($schedule['price']); ?> ETB</span>
                            </div>
                            <div
                                class="d-flex justify-content-between align-items-center pt-3 border-top border-secondary">
                                <h5 class="fw-bold mb-0">Total Amount</h5>
                                <h4 class="fw-bold text-warning mb-0"><span
                                        id="totalAmount"><?php echo number_format($schedule['price']); ?></span> ETB
                                </h4>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold mb-3">Select Payment Method</label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="telebirr"
                                        value="telebirr" checked>
                                    <label class="btn btn-outline-primary-green w-100 p-3 rounded-4" for="telebirr">
                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_x5vK7k_9_9Z7X_8X_8X_8X_8X_8X_8X_8X&s"
                                            height="25" class="me-2 rounded">
                                        Telebirr
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <input type="radio" class="btn-check" name="payment_method" id="cbebirr"
                                        value="cbebirr">
                                    <label class="btn btn-outline-primary-green w-100 p-3 rounded-4" for="cbebirr">
                                        <img src="https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcR_x5vK7k_9_9Z7X_8X_8X_8X_8X_8X_8X_8X&s"
                                            height="25" class="me-2 rounded">
                                        CBE Birr
                                    </label>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="submit_booking"
                            class="btn btn-primary-green btn-lg w-100 rounded-pill py-3 fw-bold shadow">
                            <i class="fas fa-lock me-2"></i> Pay & Request Ticket
                        </button>
                    </form>
                </div>
            </div>
            <p class="text-center text-muted small">
                <i class="fas fa-info-circle me-1"></i> After payment, the bus owner will review and approve your
                ticket. You will receive a notification once confirmed.
            </p>
        </div>
    </div>
</main>

<script>
    const basePrice = <?php echo $schedule['price']; ?>;

    function updatePassengerForms() {
        const count = parseInt(document.getElementById('num_passengers').value);
        const container = document.getElementById('passengerContainer');
        container.innerHTML = '';

        for (let i = 0; i < count; i++) {
            container.innerHTML += `
                <div class="passenger-form mb-4 p-4 border rounded-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-user-circle me-2"></i>Passenger ${i + 1}</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Full Name</label>
                            <input type="text" name="passengers[${i}][name]" class="form-control rounded-pill bg-light border-0" required placeholder="As shown on ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number</label>
                            <input type="tel" name="passengers[${i}][phone]" class="form-control rounded-pill bg-light border-0" required placeholder="09xxxxxxxx">
                        </div>
                    </div>
                </div>
            `;
        }

        document.getElementById('seatCount').innerText = count;
        document.getElementById('totalAmount').innerText = (count * basePrice).toLocaleString();
    }
</script>

<?php include('../includes/footer.php'); ?>