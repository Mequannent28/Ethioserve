<?php
ob_start();
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/email_service.php';
require_once '../includes/pdf_generator.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php?redirect=customer/book_bus.php&schedule=" . ($_GET['schedule'] ?? ''));
    exit();
}

$schedule_id = (int) ($_GET['schedule'] ?? 0);
$travel_date = sanitize($_GET['date'] ?? date('Y-m-d'));

if (!$schedule_id) {
    redirectWithMessage('buses.php', 'error', 'Invalid schedule selected');
}

// Fetch schedule details with available seats
$stmt = $pdo->prepare("
    SELECT s.*, r.origin, r.destination, r.estimated_hours,
           b.bus_number, b.total_seats, b.amenities, bt.name as bus_type, bt.seat_layout,
           tc.company_name, tc.logo_url, tc.phone as company_phone,
           (b.total_seats - COALESCE((
               SELECT SUM(bb.num_passengers) 
               FROM bus_bookings bb 
               WHERE bb.schedule_id = s.id 
               AND bb.travel_date = ? 
               AND bb.status != 'cancelled'
           ), 0)) as available_seats
    FROM schedules s
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_types bt ON b.bus_type_id = bt.id
    JOIN transport_companies tc ON b.company_id = tc.id
    WHERE s.id = ?
");
$stmt->execute([$travel_date, $schedule_id]);
$schedule = $stmt->fetch();

if (!$schedule) {
    redirectWithMessage('buses.php', 'error', 'Schedule not found');
}

if ($schedule['available_seats'] <= 0) {
    redirectWithMessage('buses.php', 'error', 'Sorry, no seats available for this schedule');
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid security token']);
            exit;
        }
        redirectWithMessage("book_bus.php?schedule=$schedule_id&date=$travel_date", 'error', 'Invalid security token');
    }

    $num_passengers = max(1, min((int) $_POST['num_passengers'], $schedule['available_seats']));
    $total_amount = $schedule['price'] * $num_passengers;
    $payment_method = sanitize($_POST['payment_method'] ?? 'telebirr');
    $pickup_point = sanitize($_POST['pickup_point'] ?? '');
    $dropoff_point = sanitize($_POST['dropoff_point'] ?? '');

    // Emergency contact info (optional)
    $emergency_contact_name = sanitize($_POST['emergency_contact_name'] ?? '');
    $emergency_contact_phone = sanitize($_POST['emergency_contact_phone'] ?? '');
    $special_requirements = sanitize($_POST['special_requirements'] ?? '');

    // Collect enhanced passenger details
    $passenger_first_names = [];
    $passenger_middle_names = [];
    $passenger_last_names = [];
    $passenger_phones = [];
    $passenger_emails = [];
    $passenger_dobs = [];
    $passenger_genders = [];

    // Also keep full names for backward compatibility
    $passenger_names = [];

    for ($i = 0; $i < $num_passengers; $i++) {
        $first_name = sanitize($_POST['passengers'][$i]['first_name'] ?? '');
        $middle_name = sanitize($_POST['passengers'][$i]['middle_name'] ?? '');
        $last_name = sanitize($_POST['passengers'][$i]['last_name'] ?? '');
        $phone = sanitize($_POST['passengers'][$i]['phone'] ?? '');
        $email = sanitize($_POST['passengers'][$i]['email'] ?? '');
        $dob = sanitize($_POST['passengers'][$i]['dob'] ?? '');
        $gender = sanitize($_POST['passengers'][$i]['gender'] ?? '');

        // Validate required fields
        if (empty($first_name) || empty($last_name) || empty($phone) || empty($dob) || empty($gender)) {
            if ($is_ajax) {
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Please fill in all required passenger details']);
                exit;
            }
            redirectWithMessage("book_bus.php?schedule=$schedule_id&date=$travel_date", 'error', 'Please fill in all required passenger details');
        }

        $passenger_first_names[] = $first_name;
        $passenger_middle_names[] = $middle_name;
        $passenger_last_names[] = $last_name;
        $passenger_phones[] = $phone;
        $passenger_emails[] = $email;
        $passenger_dobs[] = $dob;
        $passenger_genders[] = $gender;

        // Full name for display
        $full_name = trim("$first_name $middle_name $last_name");
        $passenger_names[] = $full_name;
    }

    // Generate unique reference
    $reference = 'BUS-' . strtoupper(substr(uniqid(), -8));

    // Generate transaction reference for payment
    $transaction_ref = 'TXN-' . strtoupper(substr(uniqid(), -10));

    try {
        $pdo->beginTransaction();

        // Insert booking with enhanced fields
        $stmt = $pdo->prepare("
            INSERT INTO bus_bookings (
                booking_reference, customer_id, schedule_id, travel_date, num_passengers, 
                passenger_names, passenger_phones, 
                passenger_first_names, passenger_middle_names, passenger_last_names,
                passenger_emails, passenger_dobs, passenger_genders,
                pickup_point, dropoff_point, 
                emergency_contact_name, emergency_contact_phone, special_requirements,
                payment_method, total_amount, status, payment_status, created_at
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'paid', NOW())
        ");
        $stmt->execute([
            $reference,
            getCurrentUserId(),
            $schedule_id,
            $travel_date,
            $num_passengers,
            implode('|', $passenger_names),
            implode('|', $passenger_phones),
            json_encode($passenger_first_names),
            json_encode($passenger_middle_names),
            json_encode($passenger_last_names),
            json_encode($passenger_emails),
            json_encode($passenger_dobs),
            json_encode($passenger_genders),
            $pickup_point,
            $dropoff_point,
            $emergency_contact_name,
            $emergency_contact_phone,
            $special_requirements,
            $payment_method,
            $total_amount
        ]);
        $booking_id = $pdo->lastInsertId();

        // Create payment record
        $stmt = $pdo->prepare("
            INSERT INTO payment_history (booking_id, amount, payment_method, transaction_reference, payment_status, payment_date, notes)
            VALUES (?, ?, ?, ?, 'completed', NOW(), ?)
        ");
        $stmt->execute([
            $booking_id,
            $total_amount,
            $payment_method,
            $transaction_ref,
            "Payment for booking $reference via $payment_method"
        ]);

        // Create notification for customer
        $stmt = $pdo->prepare("
            INSERT INTO booking_notifications (booking_id, user_id, type, title, message)
            VALUES (?, ?, 'booking_created', ?, ?)
        ");
        $stmt->execute([
            $booking_id,
            getCurrentUserId(),
            'Booking Confirmed - Awaiting Approval',
            "Your booking $reference has been created successfully. Total amount: " . number_format($total_amount) . " ETB. The bus company will review and assign seats shortly."
        ]);

        // Get transport company owner for notification
        $stmt = $pdo->prepare("
            SELECT u.id as owner_id, tc.company_name
            FROM transport_companies tc
            JOIN buses b ON b.company_id = tc.id
            JOIN schedules s ON s.bus_id = b.id
            JOIN users u ON u.id = tc.user_id
            WHERE s.id = ?
        ");
        $stmt->execute([$schedule_id]);
        $company_info = $stmt->fetch();

        if ($company_info) {
            // Create notification for transport owner
            $stmt = $pdo->prepare("
                INSERT INTO booking_notifications (booking_id, user_id, type, title, message)
                VALUES (?, ?, 'booking_created', ?, ?)
            ");
            $stmt->execute([
                $booking_id,
                $company_info['owner_id'],
                'New Booking Received',
                "New booking $reference received for " . $passenger_names[0] . " + " . ($num_passengers - 1) . " other(s). Total: " . number_format($total_amount) . " ETB. Please review and assign seats."
            ]);
        }

        $pdo->commit();

        try {
            // Fetch complete booking details for ticket generation
            $stmt = $pdo->prepare("
                SELECT bb.*, 
                       s.departure_time, s.arrival_time,
                       r.origin, r.destination,
                       b.bus_number, bt.name as bus_type,
                       tc.company_name
                FROM bus_bookings bb
                JOIN schedules s ON bb.schedule_id = s.id
                JOIN routes r ON s.route_id = r.id
                JOIN buses b ON s.bus_id = b.id
                JOIN bus_types bt ON b.bus_type_id = bt.id
                JOIN transport_companies tc ON b.company_id = tc.id
                WHERE bb.id = ?
            ");
            $stmt->execute([$booking_id]);
            $booking = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($booking) {
                // Generate Ticket File (HTML)
                $ticketFile = generateHTMLTicketFile($booking);

                // Get user email
                $userStmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                $userStmt->execute([getCurrentUserId()]);
                $userData = $userStmt->fetch();

                if ($userData && !empty($userData['email'])) {
                    $subject = "Your Bus Ticket - Booking #" . $booking['booking_reference'];
                    $body = "
                    <h2>Booking Confirmed!</h2>
                    <p>Dear " . htmlspecialchars($userData['full_name']) . ",</p>
                    <p>Your booking (Ref: <strong>" . $booking['booking_reference'] . "</strong>) has been confirmed.</p>
                    <p>Your ticket is attached to this email. Please save it for your journey.</p>
                    <p>Thank you for choosing EthioServe!</p>
                    ";

                    sendEmailWithAttachment(
                        $userData['email'],
                        $userData['full_name'],
                        $subject,
                        $body,
                        $ticketFile['filepath'],
                        $ticketFile['filename'],
                        'text/html'
                    );
                }
            }
        } catch (Exception $e) {
            // Silently fail email sending so it doesn't break the booking flow
            error_log("Failed to send booking email: " . $e->getMessage());
        }

        if ($is_ajax) {
            // Clean any previous output (e.g. from includes or warnings)
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'booking_id' => $booking_id, 'message' => 'Successfully Registered!']);
            exit;
        }

        // Redirect to confirmation page
        header("Location: booking_confirmation.php?id=$booking_id");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        if ($is_ajax) {
            // Clean any previous output
            while (ob_get_level()) {
                ob_end_clean();
            }
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Booking failed: ' . $e->getMessage()]);
            exit;
        }
        redirectWithMessage("book_bus.php?schedule=$schedule_id&date=$travel_date", 'error', 'Booking failed: ' . $e->getMessage());
    }
}

// Get user info for auto-fill
$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT full_name, phone, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

$flash = getFlashMessage();
include('../includes/header.php');
?>

<style>
    .booking-hero {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 50%, #388E3C 100%);
        border-radius: 0 0 30px 30px;
        padding: 40px 0;
        color: white;
    }

    .step-indicator {
        display: flex;
        justify-content: center;
        gap: 10px;
        margin-bottom: 30px;
    }

    .step-dot {
        width: 35px;
        height: 35px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 14px;
        transition: all 0.3s;
    }

    .step-dot.active {
        background: #1B5E20;
        color: white;
        box-shadow: 0 4px 15px rgba(27, 94, 32, 0.4);
    }

    .step-dot.inactive {
        background: #e9ecef;
        color: #999;
    }

    .step-dot.completed {
        background: #4CAF50;
        color: white;
    }

    .step-line {
        width: 40px;
        height: 2px;
        background: #ddd;
        align-self: center;
    }

    .step-line.active {
        background: #4CAF50;
    }

    .booking-section {
        display: none;
    }

    .booking-section.active {
        display: block;
        animation: fadeIn 0.4s ease-in-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .payment-option {
        cursor: pointer;
        transition: all 0.3s;
        border: 2px solid #e9ecef;
    }

    .payment-option:hover,
    .payment-option.selected {
        border-color: #1B5E20;
        box-shadow: 0 4px 15px rgba(27, 94, 32, 0.15);
    }

    .payment-option.selected {
        background: rgba(27, 94, 32, 0.05);
    }

    .route-timeline {
        position: relative;
        padding: 20px 0;
    }

    .route-timeline::before {
        content: '';
        position: absolute;
        left: 50%;
        top: 30%;
        bottom: 30%;
        width: 3px;
        background: linear-gradient(to bottom, #1B5E20, #4CAF50);
        transform: translateX(-50%);
    }

    .passenger-card {
        border: 2px solid #e9ecef;
        border-radius: 15px;
        padding: 20px;
        margin-bottom: 15px;
        transition: border-color 0.3s;
    }

    .passenger-card:focus-within {
        border-color: #1B5E20;
    }

    .amenity-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 12px;
        background: rgba(27, 94, 32, 0.1);
        color: #1B5E20;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }
</style>

<!-- Hero Section -->
<div class="booking-hero">
    <div class="container text-center">
        <a href="buses.php" class="text-white text-decoration-none opacity-75 small d-inline-block mb-3">
            <i class="fas fa-arrow-left me-1"></i> Back to Search
        </a>
        <h2 class="fw-bold mb-1">Book Your Bus Ticket</h2>
        <p class="opacity-75 mb-0"><?php echo htmlspecialchars($schedule['company_name']); ?> •
            <?php echo htmlspecialchars($schedule['bus_type']); ?>
        </p>
    </div>
</div>

<main class="container py-4" style="margin-top: -30px;">
    <?php if ($flash): ?>
        <div
            class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Step Indicators -->
            <div class="step-indicator">
                <div class="step-dot active" id="stepDot1">1</div>
                <div class="step-line" id="stepLine1"></div>
                <div class="step-dot inactive" id="stepDot2">2</div>
                <div class="step-line" id="stepLine2"></div>
                <div class="step-dot inactive" id="stepDot3">3</div>
            </div>

            <!-- Trip Summary Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <div class="row align-items-center text-center">
                        <div class="col-5">
                            <p class="text-muted small text-uppercase mb-1">From</p>
                            <h4 class="fw-bold mb-1"><?php echo htmlspecialchars($schedule['origin']); ?></h4>
                            <p class="text-primary-green fw-bold mb-0">
                                <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?>
                            </p>
                        </div>
                        <div class="col-2">
                            <div class="route-timeline">
                                <i class="fas fa-bus text-primary-green"></i>
                            </div>
                            <small class="text-muted"><?php echo $schedule['estimated_hours'] ?? '~'; ?>h</small>
                        </div>
                        <div class="col-5">
                            <p class="text-muted small text-uppercase mb-1">To</p>
                            <h4 class="fw-bold mb-1 text-primary-green">
                                <?php echo htmlspecialchars($schedule['destination']); ?>
                            </h4>
                            <p class="mb-0"><?php echo date('M d, Y', strtotime($travel_date)); ?></p>
                        </div>
                    </div>
                    <hr class="my-3">
                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                        <span class="amenity-badge"><i class="fas fa-bus"></i>
                            <?php echo htmlspecialchars($schedule['bus_number']); ?></span>
                        <span class="amenity-badge"><i class="fas fa-chair"></i>
                            <?php echo $schedule['available_seats']; ?> seats left</span>
                        <?php
                        $amenities = array_filter(explode(',', $schedule['amenities'] ?? ''));
                        foreach (array_slice($amenities, 0, 4) as $amenity):
                            $icon = match (strtolower(trim($amenity))) {
                                'ac' => 'snowflake',
                                'wifi' => 'wifi',
                                'tv', 'entertainment' => 'tv',
                                'usb charging' => 'charging-station',
                                'reclining seats', 'luxury seats' => 'couch',
                                'beds' => 'bed',
                                default => 'check'
                            };
                            ?>
                            <span class="amenity-badge"><i class="fas fa-<?php echo $icon; ?>"></i>
                                <?php echo trim($amenity); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Booking Form -->
            <form method="POST" id="bookingForm">
                <?php echo csrfField(); ?>

                <!-- STEP 1: Passenger Details -->
                <div class="booking-section active" id="step1">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-users text-primary-green me-2"></i>Passenger
                                Information</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Number of Passengers</label>
                                <select name="num_passengers" id="num_passengers"
                                    class="form-select rounded-pill px-4 py-3 bg-light border-0"
                                    onchange="updatePassengerForms()">
                                    <?php for ($i = 1; $i <= min(10, $schedule['available_seats']); $i++): ?>
                                        <option value="<?php echo $i; ?>"><?php echo $i; ?>
                                            Passenger<?php echo $i > 1 ? 's' : ''; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div id="passengerContainer">
                                <div class="passenger-card">
                                    <h6 class="fw-bold mb-3"><i
                                            class="fas fa-user-circle text-primary-green me-2"></i>Passenger 1 <span
                                            class="badge bg-light text-primary-green">Primary</span></h6>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">First Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="passengers[0][first_name]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required
                                                placeholder="First Name">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Middle Name</label>
                                            <input type="text" name="passengers[0][middle_name]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3"
                                                placeholder="Middle Name (Optional)">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label small fw-bold">Last Name <span
                                                    class="text-danger">*</span></label>
                                            <input type="text" name="passengers[0][last_name]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required
                                                placeholder="Last Name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Phone Number <span
                                                    class="text-danger">*</span></label>
                                            <input type="tel" name="passengers[0][phone]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required
                                                placeholder="09xxxxxxxx"
                                                value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Email Address</label>
                                            <input type="email" name="passengers[0][email]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3"
                                                placeholder="email@example.com"
                                                value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Date of Birth <span
                                                    class="text-danger">*</span></label>
                                            <input type="date" name="passengers[0][dob]"
                                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required
                                                max="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-bold">Gender <span
                                                    class="text-danger">*</span></label>
                                            <select name="passengers[0][gender]"
                                                class="form-select rounded-3 bg-light border-0 px-4 py-3" required>
                                                <option value="">Select Gender</option>
                                                <option value="Male">Male</option>
                                                <option value="Female">Female</option>
                                                <option value="Other">Other</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Emergency Contact Information -->
                            <div class="passenger-card mt-4" style="border-color: #ff9800;">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-phone-alt text-warning me-2"></i>Emergency Contact
                                    <span class="badge bg-warning text-dark">Optional</span>
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Name</label>
                                        <input type="text" name="emergency_contact_name"
                                            class="form-control rounded-3 bg-light border-0 px-4 py-3"
                                            placeholder="Full Name">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Contact Phone</label>
                                        <input type="tel" name="emergency_contact_phone"
                                            class="form-control rounded-3 bg-light border-0 px-4 py-3"
                                            placeholder="09xxxxxxxx">
                                    </div>
                                </div>
                            </div>

                            <!-- Special Requirements -->
                            <div class="passenger-card mt-4" style="border-color: #2196f3;">
                                <h6 class="fw-bold mb-3">
                                    <i class="fas fa-wheelchair text-info me-2"></i>Special Requirements
                                    <span class="badge bg-info text-white">Optional</span>
                                </h6>
                                <textarea name="special_requirements" rows="3"
                                    class="form-control rounded-3 bg-light border-0 px-4 py-3"
                                    placeholder="Please specify any special requirements (wheelchair assistance, dietary needs, medical conditions, etc.)"></textarea>
                            </div>

                            <button type="button" class="btn btn-primary-green btn-lg w-100 rounded-pill py-3 mt-3"
                                onclick="goToStep(2)">
                                Continue <i class="fas fa-arrow-right ms-2"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STEP 2: Pickup & Dropoff -->
                <div class="booking-section" id="step2">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-map-marker-alt text-primary-green me-2"></i>Pickup
                                & Drop-off Details</h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold">Pickup Point <span
                                        class="text-danger">*</span></label>
                                <select name="pickup_point" class="form-select rounded-pill px-4 py-3 bg-light border-0"
                                    required>
                                    <option value="">Select Pickup Point</option>
                                    <option value="Megenagna">Megenagna</option>
                                    <option value="Kaliti">Kaliti</option>
                                    <option value="Mercato">Mercato</option>
                                    <option value="Piazza">Piazza</option>
                                    <option value="Bole">Bole</option>
                                    <option value="Mexico">Mexico</option>
                                    <option value="CMC">CMC</option>
                                    <option value="Summit">Summit</option>
                                    <option value="Lebu">Lebu</option>
                                    <option value="Ayer Tena">Ayer Tena</option>
                                    <option value="Autobus Tera">Autobus Tera</option>
                                    <option value="Lamberet">Lamberet</option>
                                    <option value="Main Bus Station">Main Bus Station</option>
                                </select>
                                <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i>Where
                                    you'll board the bus</small>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-bold">Drop-off Point <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="dropoff_point"
                                    class="form-control rounded-pill px-4 py-3 bg-light border-0" required
                                    placeholder="e.g., Hawassa Main Station, Gondar Bus Terminal..."
                                    value="<?php echo htmlspecialchars($schedule['destination']); ?> Bus Station">
                                <small class="text-muted mt-1 d-block"><i class="fas fa-info-circle me-1"></i>Where
                                    you'll get off the bus</small>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="button"
                                    class="btn btn-outline-secondary btn-lg rounded-pill py-3 flex-grow-1"
                                    onclick="goToStep(1)">
                                    <i class="fas fa-arrow-left me-2"></i> Back
                                </button>
                                <button type="button" class="btn btn-primary-green btn-lg rounded-pill py-3 flex-grow-1"
                                    onclick="goToStep(3)">
                                    Continue <i class="fas fa-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- STEP 3: Payment -->
                <div class="booking-section" id="step3">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-header bg-white border-0 py-3 px-4">
                            <h5 class="fw-bold mb-0"><i class="fas fa-credit-card text-primary-green me-2"></i>Payment
                            </h5>
                        </div>
                        <div class="card-body px-4 pb-4">
                            <!-- Price Summary -->
                            <div class="card border-0 bg-dark text-white rounded-4 p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Ticket Price</span>
                                    <span><?php echo number_format($schedule['price']); ?> ETB</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span>Passengers</span>
                                    <span>x<span id="seatCount">1</span></span>
                                </div>
                                <hr class="border-secondary">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="fw-bold mb-0">Total Amount</h5>
                                    <h4 class="fw-bold text-warning mb-0"><span
                                            id="totalAmount"><?php echo number_format($schedule['price']); ?></span> ETB
                                    </h4>
                                </div>
                            </div>

                            <!-- Payment Methods -->
                            <label class="form-label fw-bold mb-3">Select Payment Method</label>
                            <div class="row g-3 mb-4">
                                <div class="col-md-4">
                                    <div class="payment-option rounded-4 p-3 text-center selected"
                                        onclick="selectPayment(this, 'telebirr')">
                                        <input type="radio" class="d-none" name="payment_method" value="telebirr"
                                            checked>
                                        <div class="bg-light rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center"
                                            style="width:50px;height:50px;">
                                            <i class="fas fa-mobile-alt text-primary-green fs-4"></i>
                                        </div>
                                        <strong class="d-block">Telebirr</strong>
                                        <small class="text-muted">Mobile Money</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="payment-option rounded-4 p-3 text-center"
                                        onclick="selectPayment(this, 'cbebirr')">
                                        <input type="radio" class="d-none" name="payment_method" value="cbebirr">
                                        <div class="bg-light rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center"
                                            style="width:50px;height:50px;">
                                            <i class="fas fa-university text-info fs-4"></i>
                                        </div>
                                        <strong class="d-block">CBE Birr</strong>
                                        <small class="text-muted">Bank Transfer</small>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="payment-option rounded-4 p-3 text-center"
                                        onclick="selectPayment(this, 'cash')">
                                        <input type="radio" class="d-none" name="payment_method" value="cash">
                                        <div class="bg-light rounded-circle mx-auto mb-2 d-flex align-items-center justify-content-center"
                                            style="width:50px;height:50px;">
                                            <i class="fas fa-money-bill-wave text-success fs-4"></i>
                                        </div>
                                        <strong class="d-block">Cash</strong>
                                        <small class="text-muted">Pay at Station</small>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-3">
                                <button type="button"
                                    class="btn btn-outline-secondary btn-lg rounded-pill py-3 flex-grow-1"
                                    onclick="goToStep(2)">
                                    <i class="fas fa-arrow-left me-2"></i> Back
                                </button>
                                <button type="submit" name="submit_booking" id="submitBtn"
                                    class="btn btn-primary-green btn-lg rounded-pill py-3 flex-grow-1 shadow fw-bold">
                                    <i class="fas fa-lock me-2"></i> Pay & Book Ticket
                                </button>
                            </div>
                        </div>
                    </div>

                    <p class="text-center text-muted small">
                        <i class="fas fa-info-circle me-1"></i> After booking, the bus company will review your ticket
                        and assign your seat number (cher number).
                        You will see the update on the confirmation page.
                    </p>
                </div>
            </form>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4 d-none d-lg-block">
            <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
                <div class="card-header bg-primary-green text-white py-3 rounded-top-4">
                    <h6 class="mb-0 fw-bold"><i class="fas fa-ticket-alt me-2"></i>Booking Summary</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted">Company</small>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($schedule['company_name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Route</small>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($schedule['origin']); ?> →
                            <?php echo htmlspecialchars($schedule['destination']); ?>
                        </p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Travel Date</small>
                        <p class="fw-bold mb-0"><?php echo date('M d, Y', strtotime($travel_date)); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Departure</small>
                        <p class="fw-bold mb-0"><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></p>
                    </div>
                    <div class="mb-3">
                        <small class="text-muted">Bus</small>
                        <p class="fw-bold mb-0"><?php echo htmlspecialchars($schedule['bus_number']); ?>
                            (<?php echo htmlspecialchars($schedule['bus_type']); ?>)</p>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span>Per Seat</span>
                        <strong><?php echo number_format($schedule['price']); ?> ETB</strong>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <span>Passengers</span>
                        <strong id="sidebarPassengers">1</strong>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <h5 class="fw-bold">Total</h5>
                        <h5 class="fw-bold text-primary-green" id="sidebarTotal">
                            <?php echo number_format($schedule['price']); ?> ETB
                        </h5>
                    </div>
                </div>
                <div class="card-footer bg-light text-center py-3 rounded-bottom-4">
                    <small class="text-muted"><i class="fas fa-shield-alt me-1"></i> Secure booking guaranteed</small>
                </div>
            </div>
        </div>
    </div>
</main>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const basePrice = <?php echo $schedule['price']; ?>;
    let currentStep = 1;

    function updatePassengerForms() {
        const count = parseInt(document.getElementById('num_passengers').value);
        const container = document.getElementById('passengerContainer');
        container.innerHTML = '';

        for (let i = 0; i < count; i++) {
            const isPrimary = i === 0;
            container.innerHTML += `
                <div class="passenger-card">
                    <h6 class="fw-bold mb-3">
                        <i class="fas fa-user-circle text-primary-green me-2"></i>Passenger ${i + 1} 
                        ${isPrimary ? '<span class="badge bg-light text-primary-green">Primary</span>' : ''}
                    </h6>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="passengers[${i}][first_name]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required 
                                placeholder="First Name">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Middle Name</label>
                            <input type="text" name="passengers[${i}][middle_name]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" 
                                placeholder="Middle Name (Optional)">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="passengers[${i}][last_name]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required 
                                placeholder="Last Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Phone Number <span class="text-danger">*</span></label>
                            <input type="tel" name="passengers[${i}][phone]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required 
                                placeholder="09xxxxxxxx"
                                ${isPrimary ? `value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"` : ''}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Email Address</label>
                            <input type="email" name="passengers[${i}][email]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" 
                                placeholder="email@example.com"
                                ${isPrimary ? `value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"` : ''}>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Date of Birth <span class="text-danger">*</span></label>
                            <input type="date" name="passengers[${i}][dob]" 
                                class="form-control rounded-3 bg-light border-0 px-4 py-3" required 
                                max="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Gender <span class="text-danger">*</span></label>
                            <select name="passengers[${i}][gender]" 
                                class="form-select rounded-3 bg-light border-0 px-4 py-3" required>
                                <option value="">Select Gender</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
        }

        // Update counts and totals
        document.getElementById('seatCount').innerText = count;
        document.getElementById('totalAmount').innerText = (count * basePrice).toLocaleString();
        document.getElementById('sidebarPassengers').innerText = count;
        document.getElementById('sidebarTotal').innerText = (count * basePrice).toLocaleString() + ' ETB';
    }

    function goToStep(step) {
        // Validate current step before proceeding
        if (step > currentStep) {
            if (currentStep === 1) {
                const passengers = document.querySelectorAll('#passengerContainer input[required]');
                for (let p of passengers) {
                    if (!p.value.trim()) {
                        p.focus();
                        p.classList.add('is-invalid');
                        return;
                    }
                    p.classList.remove('is-invalid');
                }
            }
            if (currentStep === 2) {
                const pickup = document.querySelector('[name="pickup_point"]');
                const dropoff = document.querySelector('[name="dropoff_point"]');
                if (!pickup.value) { pickup.focus(); pickup.classList.add('is-invalid'); return; }
                if (!dropoff.value.trim()) { dropoff.focus(); dropoff.classList.add('is-invalid'); return; }
                pickup.classList.remove('is-invalid');
                dropoff.classList.remove('is-invalid');
            }
        }

        // Hide all sections
        document.querySelectorAll('.booking-section').forEach(s => s.classList.remove('active'));

        // Show target section
        document.getElementById('step' + step).classList.add('active');

        // Update step indicators
        for (let i = 1; i <= 3; i++) {
            const dot = document.getElementById('stepDot' + i);
            if (i < step) {
                dot.className = 'step-dot completed';
                dot.innerHTML = '<i class="fas fa-check" style="font-size:12px;"></i>';
            } else if (i === step) {
                dot.className = 'step-dot active';
                dot.innerHTML = i;
            } else {
                dot.className = 'step-dot inactive';
                dot.innerHTML = i;
            }
        }
        for (let i = 1; i <= 2; i++) {
            const line = document.getElementById('stepLine' + i);
            line.className = i < step ? 'step-line active' : 'step-line';
        }

        currentStep = step;
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function selectPayment(el, method) {
        document.querySelectorAll('.payment-option').forEach(p => {
            p.classList.remove('selected');
            p.querySelector('input').checked = false;
        });
        el.classList.add('selected');
        el.querySelector('input').checked = true;
    }

    // Handle form submission with SweetAlert
    document.getElementById('bookingForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const form = this;
        const btn = document.getElementById('submitBtn');
        const originalBtnText = btn.innerHTML;

        // Disable button
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

        const formData = new FormData(form);
        formData.append('ajax', '1');
        formData.append('submit_booking', '1');

        Swal.fire({
            title: 'Processing Booking',
            text: 'Please wait while we secure your seats...',
            icon: 'info',
            allowOutsideClick: false,
            showConfirmButton: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
            .then(response => {
                // Check if response is redirect (not JSON)
                if (response.redirected) {
                    window.location.href = response.url;
                    return;
                }
                return response.text().then(text => {
                    try {
                        return JSON.parse(text);
                    } catch (e) {
                        console.error('Invalid JSON:', text);
                        throw new Error('Server returned invalid response');
                    }
                });
            })
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Successfully Registered!',
                        text: 'Your booking has been confirmed.',
                        icon: 'success',
                        confirmButtonText: 'View Ticket',
                        confirmButtonColor: '#1B5E20',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'booking_confirmation.php?id=' + data.booking_id;
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Booking Failed',
                        text: data.message || 'Unknown error occurred',
                        icon: 'error',
                        confirmButtonColor: '#d33'
                    });
                    btn.disabled = false;
                    btn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    title: 'System Error',
                    text: 'An unexpected error occurred (' + error.message + '). Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            });
    });
</script>

<?php include('../includes/footer.php'); ?>