<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in (but NOT required)
$is_logged_in = isLoggedIn();

// Ensure bookings table has guest columns
try {
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_name VARCHAR(100) DEFAULT NULL");
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_phone VARCHAR(20) DEFAULT NULL");
    $pdo->exec("ALTER TABLE bookings ADD COLUMN IF NOT EXISTS guest_email VARCHAR(100) DEFAULT NULL");
} catch (Exception $e) {
    // Columns may already exist or DB doesn't support IF NOT EXISTS
    // Try individually
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_name VARCHAR(100) DEFAULT NULL"); } catch(Exception $e2) {}
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_phone VARCHAR(20) DEFAULT NULL"); } catch(Exception $e2) {}
    try { $pdo->exec("ALTER TABLE bookings ADD COLUMN guest_email VARCHAR(100) DEFAULT NULL"); } catch(Exception $e2) {}
}

// Handle form submission — NO login required
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_booking'])) {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('booking.php', 'error', 'Invalid security token');
    }

    $hotel_id = (int) ($_POST['hotel_id'] ?? 0);
    $booking_type = sanitize($_POST['booking_type'] ?? 'table');
    $booking_date = sanitize($_POST['booking_date'] ?? '');
    $booking_time = sanitize($_POST['booking_time'] ?? '');
    $num_guests = (int) ($_POST['num_guests'] ?? 1);
    $room_id = (int) ($_POST['room_id'] ?? 0);
    $notes = sanitize($_POST['notes'] ?? '');
    
    // Guest info (for non-logged-in users)
    $guest_name = sanitize($_POST['guest_name'] ?? '');
    $guest_phone = sanitize($_POST['guest_phone'] ?? '');
    $guest_email = sanitize($_POST['guest_email'] ?? '');

    // Validate inputs
    $errors = [];

    if (!$hotel_id) {
        $errors[] = 'Please select a venue';
    }

    if (!validateDate($booking_date)) {
        $errors[] = 'Please select a valid date';
    }

    if (!validateTime($booking_time)) {
        $errors[] = 'Please select a valid time';
    }

    // Check if date is in the future
    $booking_datetime = strtotime($booking_date . ' ' . $booking_time);
    if ($booking_datetime < time()) {
        $errors[] = 'Booking date must be in the future';
    }

    if ($booking_type === 'room' && !$room_id) {
        $errors[] = 'Please select a specific room';
    }
    
    // If not logged in, require guest info
    if (!$is_logged_in) {
        if (empty($guest_name)) $errors[] = 'Please enter your name';
        if (empty($guest_phone)) $errors[] = 'Please enter your phone number';
    }

    if (empty($errors)) {
        try {
            $customer_id = $is_logged_in ? getCurrentUserId() : 0;
            
            $stmt = $pdo->prepare("
                INSERT INTO bookings (customer_id, hotel_id, room_id, booking_date, booking_time, booking_type, guest_name, guest_phone, guest_email, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                $customer_id,
                $hotel_id,
                $room_id ?: null,
                $booking_date,
                $booking_time,
                $booking_type,
                $is_logged_in ? null : $guest_name,
                $is_logged_in ? null : $guest_phone,
                $is_logged_in ? null : $guest_email
            ]);
            $booking_id = $pdo->lastInsertId();

            if ($is_logged_in) {
                redirectWithMessage('track_order.php?booking=' . $booking_id, 'success', 'Booking request submitted successfully!');
            } else {
                // Save guest info in session so they can create an account
                $_SESSION['guest_booking'] = [
                    'booking_id' => $booking_id,
                    'name' => $guest_name,
                    'phone' => $guest_phone,
                    'email' => $guest_email,
                ];
                header("Location: booking.php?success=1&bid=" . $booking_id);
                exit;
            }
        } catch (Exception $e) {
            redirectWithMessage('booking.php', 'error', 'Failed to create booking. Please try again.');
        }
    } else {
        $_SESSION['booking_errors'] = $errors;
        $_SESSION['booking_data'] = $_POST;
    }
}

// Fetch all approved hotels for the venue dropdown
$stmt = $pdo->query("SELECT id, name, location FROM hotels WHERE status = 'approved' ORDER BY name ASC");
$hotels = $stmt->fetchAll();

// Get flash message and old form data
$flash = getFlashMessage();
$errors = $_SESSION['booking_errors'] ?? [];
$old_data = $_SESSION['booking_data'] ?? [];
unset($_SESSION['booking_errors'], $_SESSION['booking_data']);

// Get pre-selected hotel from URL or previous data
$selected_hotel_id = (int) ($_GET['hotel_id'] ?? ($old_data['hotel_id'] ?? 0));

// Check if booking was just successful (guest user)
$booking_success = isset($_GET['success']) && $_GET['success'] == 1;
$booking_id = (int)($_GET['bid'] ?? 0);
$guest_booking = $_SESSION['guest_booking'] ?? null;

include('../includes/header.php');
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="text-center mb-5">
                <h1 class="fw-bold">Book a Service</h1>
                <p class="text-muted lead">Reserve Halls, Conference Rooms, or Dinner Tables at top-tier venues.</p>
            </div>

            <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
            <?php endif; ?>

            <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
            <?php endif; ?>

            <?php if ($booking_success && $guest_booking): ?>
                <!-- SUCCESS: Guest booking confirmed -->
                <div class="card border-0 shadow p-5 rounded-4 text-center mb-4" style="background:linear-gradient(135deg,#f0fdf4,#dcfce7);">
                    <div class="mb-3">
                        <div class="d-inline-flex align-items-center justify-content-center rounded-circle bg-success text-white" style="width:80px;height:80px;font-size:2rem;">
                            <i class="fas fa-check"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold text-success">Booking Confirmed!</h3>
                    <p class="text-muted mb-1">Your booking #<?php echo $booking_id; ?> has been submitted successfully.</p>
                    <p class="text-muted mb-4">We will contact you at <strong><?php echo htmlspecialchars($guest_booking['phone']); ?></strong> to confirm.</p>
                    
                    <div class="card border-0 shadow-sm p-4 rounded-3 mb-4 mx-auto" style="max-width:500px;background:#fff;">
                        <h6 class="fw-bold mb-3"><i class="fas fa-user-plus text-primary me-2"></i>Create an Account (Optional)</h6>
                        <p class="text-muted small mb-3">Create an account to track your booking, get updates, and book faster next time.</p>
                        <a href="../register.php<?php echo !empty($guest_booking['email']) ? '?email=' . urlencode($guest_booking['email']) . '&name=' . urlencode($guest_booking['name']) : ''; ?>" 
                           class="btn btn-primary-green rounded-pill px-4 py-2 me-2">
                            <i class="fas fa-user-plus me-1"></i> Create Account
                        </a>
                        <a href="booking.php" class="btn btn-outline-secondary rounded-pill px-4 py-2 mt-2 mt-sm-0">
                            <i class="fas fa-plus me-1"></i> Book Again
                        </a>
                    </div>
                </div>
                <?php unset($_SESSION['guest_booking']); ?>
            <?php else: ?>

            <div class="card border-0 shadow p-0 overflow-hidden rounded-4">
                <div class="row g-0">
                    <!-- Left Illustration -->
                    <div class="col-lg-5 d-none d-lg-block bg-primary-green p-5 text-white position-relative">
                        <h3 class="fw-bold mb-4">Why Book with Us?</h3>
                        <ul class="list-unstyled d-flex flex-column gap-4">
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary-green rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                    <i class="fas fa-check"></i>
                                </div>
                                <span>Instant Confirmation</span>
                            </li>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary-green rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                    <i class="fas fa-money-check-alt"></i>
                                </div>
                                <span>Flexible Payment Options</span>
                            </li>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary-green rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                    <i class="fas fa-headset"></i>
                                </div>
                                <span>24/7 Concierge Service</span>
                            </li>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary-green rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                    <i class="fas fa-calendar-alt"></i>
                                </div>
                                <span>Easy Rescheduling</span>
                            </li>
                            <li class="d-flex align-items-center gap-3">
                                <div class="bg-white text-primary-green rounded-circle p-2" style="width: 40px; height: 40px; text-align: center;">
                                    <i class="fas fa-user-check"></i>
                                </div>
                                <span>No Account Required!</span>
                            </li>
                        </ul>
                        <div class="mt-5 p-4 bg-white text-dark rounded-4 shadow-sm">
                            <p class="small mb-0 fw-bold">
                                <i class="fas fa-quote-left text-primary-green me-2"></i>
                                The best way to secure your event space in Addis!
                            </p>
                        </div>
                    </div>

                    <!-- Right Form — OPEN TO EVERYONE -->
                    <div class="col-lg-7 p-5">
                        <form method="POST" id="bookingForm">
                            <?php echo csrfField(); ?>
                        
                            <div class="row g-4">
                                <?php if (!$is_logged_in): ?>
                                    <!-- Guest info fields -->
                                    <div class="col-12">
                                        <div class="alert alert-info py-2 px-3 rounded-3 border-0" style="background:rgba(21,101,192,0.08);">
                                            <small><i class="fas fa-info-circle me-1"></i> No account needed! Just fill in your details below.</small>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-uppercase">Your Name <span class="text-danger">*</span></label>
                                        <input type="text" name="guest_name" class="form-control rounded-pill px-4 py-3 bg-light border-0" 
                                               placeholder="Full Name" required
                                               value="<?php echo htmlspecialchars($old_data['guest_name'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-uppercase">Phone Number <span class="text-danger">*</span></label>
                                        <input type="tel" name="guest_phone" class="form-control rounded-pill px-4 py-3 bg-light border-0" 
                                               placeholder="09xxxxxxxx" required
                                               value="<?php echo htmlspecialchars($old_data['guest_phone'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold small text-uppercase">Email <span class="text-muted">(Optional)</span></label>
                                        <input type="email" name="guest_email" class="form-control rounded-pill px-4 py-3 bg-light border-0" 
                                               placeholder="your@email.com"
                                               value="<?php echo htmlspecialchars($old_data['guest_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-12"><hr class="my-0"></div>
                                <?php endif; ?>

                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Select Venue</label>
                                    <select class="form-select rounded-pill px-4 py-3 bg-light border-0" name="hotel_id" required>
                                        <option value="" disabled <?php echo !$selected_hotel_id ? 'selected' : ''; ?>>Choose a Hotel / Restaurant</option>
                                        <?php foreach ($hotels as $hotel): ?>
                                                <option value="<?php echo $hotel['id']; ?>" 
                                                    <?php echo $selected_hotel_id == $hotel['id'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($hotel['name']); ?> - <?php echo htmlspecialchars($hotel['location']); ?>
                                                </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Booking Type</label>
                                    <div class="d-flex gap-3">
                                        <input type="radio" class="btn-check" name="booking_type" id="typeRoom" value="room" 
                                            <?php echo ($old_data['booking_type'] ?? 'room') === 'room' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green rounded-pill px-4 flex-grow-1" for="typeRoom">
                                            <i class="fas fa-bed me-2"></i>Room
                                        </label>

                                        <input type="radio" class="btn-check" name="booking_type" id="typeTable" value="table"
                                            <?php echo ($old_data['booking_type'] ?? '') === 'table' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green rounded-pill px-4 flex-grow-1" for="typeTable">
                                            <i class="fas fa-utensils me-2"></i>Table
                                        </label>

                                        <input type="radio" class="btn-check" name="booking_type" id="typeHall" value="hall"
                                            <?php echo ($old_data['booking_type'] ?? '') === 'hall' ? 'checked' : ''; ?>>
                                        <label class="btn btn-outline-primary-green rounded-pill px-4 flex-grow-1" for="typeHall">
                                            <i class="fas fa-building me-2"></i>Hall
                                        </label>
                                    </div>
                                </div>

                                <!-- Dynamic Room Selection Container -->
                                <div class="col-12 d-none" id="roomSelectionContainer">
                                    <label class="form-label fw-bold small text-uppercase">Select Available Room</label>
                                    <div id="roomsList" class="row g-3">
                                        <!-- Rooms will be loaded here via JS -->
                                    </div>
                                    <input type="hidden" name="room_id" id="selectedRoomId">
                                </div>
                            
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Pick Date</label>
                                    <input type="date" name="booking_date" class="form-control rounded-pill px-4 py-3 bg-light border-0" 
                                           min="<?php echo date('Y-m-d'); ?>" required
                                           value="<?php echo htmlspecialchars($old_data['booking_date'] ?? ''); ?>">
                                </div>
                            
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-uppercase">Select Time</label>
                                    <input type="time" name="booking_time" class="form-control rounded-pill px-4 py-3 bg-light border-0" required
                                           value="<?php echo htmlspecialchars($old_data['booking_time'] ?? ''); ?>">
                                </div>
                            
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Number of Guests</label>
                                    <input type="number" name="num_guests" class="form-control rounded-pill px-4 py-3 bg-light border-0" 
                                           placeholder="e.g. 4" min="1" max="100"
                                           value="<?php echo htmlspecialchars($old_data['num_guests'] ?? '1'); ?>">
                                </div>
                            
                                <div class="col-12">
                                    <label class="form-label fw-bold small text-uppercase">Special Requests (Optional)</label>
                                    <textarea name="notes" class="form-control rounded-3 bg-light border-0" rows="2"
                                              placeholder="Any special requests or dietary requirements..."><?php echo htmlspecialchars($old_data['notes'] ?? ''); ?></textarea>
                                </div>
                            
                                <div class="col-12 mt-5">
                                    <button type="submit" name="submit_booking" class="btn btn-primary-green btn-lg w-100 rounded-pill py-3 shadow">
                                        <i class="fas fa-calendar-check me-2"></i> Submit Booking Request
                                    </button>
                                    <?php if (!$is_logged_in): ?>
                                        <p class="text-center text-muted small mt-2">
                                            Already have an account? <a href="../login.php?redirect=booking" class="fw-bold">Login</a> to track your bookings
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <?php endif; ?>

            <!-- User's Existing Bookings (only if logged in) -->
            <?php if ($is_logged_in): ?>
                    <div class="mt-5">
                        <h4 class="fw-bold mb-4">Your Recent Bookings</h4>
                        <?php
                        $stmt = $pdo->prepare("
                        SELECT b.*, h.name as hotel_name, h.location 
                        FROM bookings b 
                        JOIN hotels h ON b.hotel_id = h.id 
                        WHERE b.customer_id = ? 
                        ORDER BY b.created_at DESC 
                        LIMIT 5
                    ");
                        $stmt->execute([getCurrentUserId()]);
                        $user_bookings = $stmt->fetchAll();
                        ?>
                    
                        <?php if (empty($user_bookings)): ?>
                                <div class="card border-0 shadow-sm p-4 text-center">
                                    <p class="text-muted mb-0">No bookings yet. Make your first booking above!</p>
                                </div>
                        <?php else: ?>
                                <div class="card border-0 shadow-sm">
                                    <div class="table-responsive">
                                        <table class="table align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="border-0 px-4">Venue</th>
                                                    <th class="border-0">Type</th>
                                                    <th class="border-0">Date & Time</th>
                                                    <th class="border-0">Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($user_bookings as $booking): ?>
                                                        <tr>
                                                            <td class="px-4">
                                                                <strong><?php echo htmlspecialchars($booking['hotel_name']); ?></strong>
                                                                <br><small class="text-muted"><?php echo htmlspecialchars($booking['location']); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-light text-dark">
                                                                    <i class="fas fa-<?php
                                                                    echo $booking['booking_type'] === 'room' ? 'bed' :
                                                                        ($booking['booking_type'] === 'table' ? 'utensils' : 'building');
                                                                    ?> me-1"></i>
                                                                    <?php echo ucfirst($booking['booking_type']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                                                <br><small class="text-muted"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></small>
                                                            </td>
                                                            <td><?php echo getStatusBadge($booking['status']); ?></td>
                                                        </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                        <?php endif; ?>
                    </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
// Set minimum date to today
document.querySelector('input[type="date"]')?.setAttribute('min', new Date().toISOString().split('T')[0]);

// Dynamic Room Loading Logic
const hotelSelect = document.querySelector('select[name="hotel_id"]');
const bookingTypeRadios = document.querySelectorAll('input[name="booking_type"]');
const roomContainer = document.getElementById('roomSelectionContainer');
const roomsList = document.getElementById('roomsList');
const roomInput = document.getElementById('selectedRoomId');

function updateRooms() {
    const hotelId = hotelSelect.value;
    const bookingType = document.querySelector('input[name="booking_type"]:checked')?.value;

    if (bookingType === 'room' && hotelId) {
        roomContainer.classList.remove('d-none');
        roomsList.innerHTML = '<div class="col-12 py-3 text-center"><div class="spinner-border spinner-border-sm text-primary-green me-2"></div><span class="small">Checking room availability...</span></div>';
        
        fetch(`ajax_get_rooms.php?hotel_id=${hotelId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.rooms.length > 0) {
                    let html = '';
                    data.rooms.forEach(room => {
                        html += `
                            <div class="col-md-6">
                                <div class="room-option card border rounded-4 p-3 h-100 cursor-pointer transition-all" 
                                     onclick="selectRoom(this, ${room.id})" 
                                     style="border: 2px solid #eee !important;">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold mb-0">Room ${room.room_number}</h6>
                                        <span class="fw-bold text-primary-green">${Number(room.price_per_night).toLocaleString()} ETB</span>
                                    </div>
                                    <p class="small text-muted mb-0">${room.room_type}</p>
                                    ${room.description ? `<p class="x-small text-muted mt-1 mb-0">${room.description}</p>` : ''}
                                </div>
                            </div>
                        `;
                    });
                    roomsList.innerHTML = html;
                } else {
                    roomsList.innerHTML = '<div class="col-12 py-3"><div class="alert alert-warning small border-0 mb-0">No available rooms found for this hotel. Please choose another venue or booking type.</div></div>';
                    roomInput.value = '';
                }
            })
            .catch(err => {
                roomsList.innerHTML = '<div class="col-12 py-3 text-danger small">Error loading rooms. Please check your connection.</div>';
            });
    } else {
        roomContainer.classList.add('d-none');
        roomInput.value = '';
    }
}

function selectRoom(el, id) {
    // Deselect all
    document.querySelectorAll('.room-option').forEach(card => {
        card.style.borderColor = '#eee';
        card.classList.remove('bg-light');
    });
    // Select current
    el.style.borderColor = '#1B5E20';
    el.classList.add('bg-light');
    roomInput.value = id;
}

hotelSelect.addEventListener('change', updateRooms);
bookingTypeRadios.forEach(radio => radio.addEventListener('change', updateRooms));

// Run once on load
if (hotelSelect.value) updateRooms();
</script>

<style>
.cursor-pointer { cursor: pointer; }
.transition-all { transition: all 0.2s ease-in-out; }
.room-option:hover { transform: translateY(-2px); box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
.x-small { font-size: 0.75rem; }
</style>

<?php include('../includes/footer.php'); ?>
