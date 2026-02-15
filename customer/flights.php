<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$is_logged_in = isLoggedIn();
$flash = getFlashMessage();

// Search parameters
$origin = sanitize($_GET['origin'] ?? 'Addis Ababa (ADD)');
$destination = sanitize($_GET['destination'] ?? '');
$travel_date = sanitize($_GET['travel_date'] ?? date('Y-m-d'));
$trip_type = sanitize($_GET['trip_type'] ?? 'one_way');

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_flight'])) {
    if (!$is_logged_in) {
        redirectWithMessage('flights.php', 'error', 'Please login to book a flight');
    }

    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('flights.php', 'error', 'Invalid security token. Please try again.');
    }

    $flight_id = (int)($_POST['flight_id'] ?? 0);
    $title = sanitize($_POST['title'] ?? '');
    $given_names = sanitize($_POST['given_names'] ?? '');
    $last_name = sanitize($_POST['last_name'] ?? '');
    $passport_number = sanitize($_POST['passport_number'] ?? '');
    $dob = sanitize($_POST['dob'] ?? '');
    $gender = sanitize($_POST['gender'] ?? 'male');
    $pnr = generatePNR();
    $post_trip_type = sanitize($_POST['trip_type'] ?? 'one_way');

    $passenger_name = trim("$title $given_names $last_name");

    if (empty($passenger_name) || empty($passport_number)) {
        redirectWithMessage('flights.php', 'error', 'Passenger name and ID are required.');
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO flight_bookings 
            (customer_id, flight_id, passenger_name, title, given_names, last_name, 
             passport_number, date_of_birth, gender, pnr_code, trip_type, status, payment_status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
            
        $stmt->execute([
            getCurrentUserId(), 
            $flight_id, 
            $passenger_name, 
            $title, 
            $given_names, 
            $last_name, 
            $passport_number, 
            $dob, 
            $gender, 
            $pnr, 
            $post_trip_type
        ]);

        $booking_id = $pdo->lastInsertId();
        
        // Success! Redirect to tracker
        header("Location: track_order.php?flight_booking=$booking_id&new_pnr=$pnr");
        exit();
    } catch (PDOException $e) {
        // Log the error for the developer and show message to user
        error_log("Booking Error: " . $e->getMessage());
        redirectWithMessage('flights.php', 'error', 'Database Error: ' . $e->getMessage());
    } catch (Exception $e) {
        redirectWithMessage('flights.php', 'error', 'Error: ' . $e->getMessage());
    }
}

// Build query for flights
$sql = "SELECT * FROM flights WHERE status = 'scheduled'";
$params = [];

if (empty($travel_date)) {
    $sql .= " AND departure_time > NOW()";
}

if (!empty($destination)) {
    $sql .= " AND (destination LIKE ? OR destination LIKE ? OR flight_number LIKE ?)";
    $params[] = "%$destination%";
    $params[] = "%" . trim(explode(' ', $destination)[0]) . "%";
    $params[] = "%$destination%";
}

if (!empty($travel_date)) {
    $sql .= " AND DATE(departure_time) = ?";
    $params[] = $travel_date;
} else {
    // Default to upcoming flights if no date is picked
    $sql .= " AND departure_time > NOW()";
}

$sql .= " ORDER BY departure_time ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$flights = $stmt->fetchAll();

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/style.css">

<div class="container mt-3">
    <?php echo displayFlashMessage(); ?>
</div>

<style>
    :root {
        --ethiopian-green: #1B5E20;
        --ethiopian-gold: #F9A825;
        --ethiopian-red: #C62828;
        --light-gray: #f2f5f1;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: #f4f7f4;
    }

    .app-header {
        background: var(--ethiopian-green);
        padding: 40px 0 100px;
        color: white;
        border-radius: 0 0 30px 30px;
    }

    .booking-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        margin-top: -60px;
        padding: 25px;
        border: none;
    }

    .input-box {
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        padding: 12px 15px;
        margin-bottom: 15px;
        position: relative;
    }

    .input-box label {
        font-size: 0.75rem;
        color: #9e9e9e;
        display: block;
        margin-bottom: 2px;
        font-weight: 600;
    }

    .input-box input {
        border: none;
        width: 100%;
        font-weight: 600;
        font-size: 1rem;
        color: #212529;
        outline: none;
    }

    .swap-btn {
        width: 36px;
        height: 36px;
        background: white;
        border: 1px solid #e0e0e0;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        position: absolute;
        right: 25px;
        top: 65px;
        z-index: 2;
        cursor: pointer;
        transition: 0.3s;
    }

    .swap-btn:hover {
        border-color: var(--ethiopian-green);
        color: var(--ethiopian-green);
    }

    .btn-search-main {
        background: var(--ethiopian-gold);
        color: var(--ethiopian-green);
        border: none;
        border-radius: 12px;
        padding: 15px;
        width: 100%;
        font-weight: 700;
        font-size: 1.1rem;
        transition: 0.3s;
        box-shadow: 0 4px 15px rgba(249, 168, 37, 0.3);
    }

    .btn-search-main:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(249, 168, 37, 0.4);
    }

    /* Date Scroller */
    .date-scroller {
        display: flex;
        overflow-x: auto;
        gap: 10px;
        padding: 15px 0;
        scrollbar-width: none;
    }

    .date-scroller::-webkit-scrollbar {
        display: none;
    }

    .date-item {
        min-width: 100px;
        padding: 12px;
        background: white;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        border: 2px solid transparent;
        transition: 0.3s;
    }

    .date-item.active {
        border-color: var(--ethiopian-green);
        background: #e8f5e9;
    }

    .date-item .day {
        font-size: 0.8rem;
        color: #616161;
    }

    .date-item .date {
        font-weight: 700;
        display: block;
    }

    .date-item .price {
        font-size: 0.75rem;
        color: var(--ethiopian-green);
        font-weight: 600;
    }

    /* Flight Cards Mobile Style */
    .flight-row {
        background: white;
        border-radius: 16px;
        margin-bottom: 15px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        border: 1px solid rgba(0, 0, 0, 0.05);
        cursor: pointer;
        transition: 0.3s;
    }

    .flight-row:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
    }

    .route-line {
        height: 1px;
        background: #e0e0e0;
        position: relative;
        flex-grow: 1;
        margin: 0 15px;
    }

    .route-line::after {
        content: '\f072';
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        background: white;
        padding: 0 5px;
        color: #bdbdbd;
    }

    .price-big {
        color: var(--ethiopian-green);
        font-weight: 800;
        font-size: 1.2rem;
    }

    .lowest-badge {
        background: #e8f5e9;
        color: #2e7d32;
        font-size: 0.7rem;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 700;
    }

    /* Modal Form Styling */
    .passenger-form .form-group {
        margin-bottom: 20px;
    }

    .passenger-form label {
        font-size: 0.85rem;
        font-weight: 600;
        color: #616161;
        margin-bottom: 8px;
        display: block;
    }

    .gender-picker {
        display: flex;
        gap: 10px;
    }

    .gender-option {
        flex: 1;
        padding: 12px;
        border: 1px solid #e0e0e0;
        border-radius: 12px;
        text-align: center;
        cursor: pointer;
        font-weight: 600;
        transition: 0.3s;
    }

    .gender-radio:checked+.gender-option {
        background: var(--ethiopian-green);
        color: white;
        border-color: var(--ethiopian-green);
    }

    .shebamiles-banner {
        background: linear-gradient(rgba(0, 0, 0, 0.05), rgba(0, 0, 0, 0.05)), url('https://images.unsplash.com/photo-1542296332-2e4473faf563?q=80&w=2070&auto=format&fit=crop');
        background-size: cover;
        background-position: center;
        border-radius: 20px;
        padding: 30px;
        color: #333;
        margin: 30px 0;
        position: relative;
    }
</style>

<div class="app-header">
    <div class="container text-center">
        <h2 class="fw-bold mb-1">Book a Flight</h2>
        <p class="opacity-75">Make your flight unforgettable!</p>
    </div>
</div>

<div class="container">
    <div class="booking-card">
        <form action="" method="GET">
            <div class="position-relative">
                <div class="input-box">
                    <label><i class="fas fa-plane-departure me-1"></i> FROM</label>
                    <input type="text" name="origin" value="<?php echo htmlspecialchars($origin); ?>" readonly>
                </div>

                <div class="swap-btn"><i class="fas fa-exchange-alt fa-rotate-90"></i></div>

                <div class="input-box">
                    <label><i class="fas fa-plane-arrival me-1"></i> TO</label>
                    <input type="text" name="destination" placeholder="Where to?"
                        value="<?php echo htmlspecialchars($destination); ?>" required>
                </div>
            </div>

            <div class="row g-2">
                <div class="col-6">
                    <div class="input-box">
                        <label>DEPARTURE</label>
                        <input type="date" name="travel_date" value="<?php echo htmlspecialchars($travel_date); ?>"
                            min="<?php echo date('Y-m-d'); ?>">
                    </div>
                </div>
                <div class="col-6">
                    <div class="input-box">
                        <label>PASSENGERS</label>
                        <input type="text" value="1 Adult, Economy" readonly style="background: transparent;">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-search-main mt-2">
                Search Flights
            </button>
        </form>
    </div>

    <!-- Recent Searches (Screen 1) -->
    <div class="mt-4 mb-2">
        <h6 class="fw-bold mb-3">Recent searches</h6>
        <div class="d-flex overflow-auto gap-3 pb-3" style="scrollbar-width: none;">
            <div class="card border-0 shadow-sm p-3 rounded-4" style="min-width: 200px;">
                <small class="text-muted d-block mb-1">2026-02-19</small>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-bold">ADD</span>
                    <div class="route-line mx-2" style="width: 40px;"></div>
                    <span class="fw-bold">BJR</span>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>Addis Ababa</span>
                    <span>Bahir Dar</span>
                </div>
            </div>
            <div class="card border-0 shadow-sm p-3 rounded-4" style="min-width: 200px;">
                <small class="text-muted d-block mb-1">2026-02-21</small>
                <div class="d-flex align-items-center justify-content-between">
                    <span class="fw-bold">ADD</span>
                    <div class="route-line mx-2" style="width: 40px;"></div>
                    <span class="fw-bold">DXB</span>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-1">
                    <span>Addis Ababa</span>
                    <span>Dubai</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Date Selection Bar -->
    <div class="date-scroller">
        <?php
        for ($i = -2; $i <= 4; $i++):
            $d = date('Y-m-d', strtotime("$travel_date $i days"));
            if ($d < date('Y-m-d'))
                continue;
            $active = ($d == $travel_date) ? 'active' : '';
            ?>
            <div class="date-item <?php echo $active; ?>"
                onclick="window.location.href='?origin=<?php echo urlencode($origin); ?>&destination=<?php echo urlencode($destination); ?>&travel_date=<?php echo $d; ?>'">
                <span class="day"><?php echo date('D, M d', strtotime($d)); ?></span>
                <span class="price">ETB 8,500</span>
            </div>
        <?php endfor; ?>
    </div>

    <div class="d-flex justify-content-between align-items-center mb-3 mt-4">
        <h5 class="fw-bold m-0"><?php echo count($flights); ?> flights are available</h5>
        <div class="dropdown">
            <button class="btn btn-sm btn-white border rounded-pill dropdown-toggle" type="button"
                data-bs-toggle="dropdown">
                Sort by
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="#">Price (Low to High)</a></li>
                <li><a class="dropdown-item" href="#">Duration</a></li>
            </ul>
        </div>
    </div>

    <?php if (empty($flights)): ?>
        <div class="text-center py-5">
            <i class="fas fa-plane-slash fa-4x text-light mb-3"></i>
            <h5>No flights found for this route today.</h5>
            <p class="text-muted">Try a different date or destination.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($flights as $flight): ?>
        <div class="flight-row" id="flightCard<?php echo $flight['id']; ?>">
            <div class="d-flex justify-content-between mb-2">
                <small class="text-muted"><?php echo date('D, M d', strtotime($flight['departure_time'])); ?></small>
                <small class="text-muted"><?php echo date('D, M d', strtotime($flight['arrival_time'])); ?></small>
            </div>

            <div class="d-flex align-items-center mb-3">
                <div class="text-center" style="min-width: 60px;">
                    <h4 class="fw-bold mb-0"><?php echo date('H:i', strtotime($flight['departure_time'])); ?></h4>
                    <small class="fw-bold">ADD</small>
                </div>

                <div class="route-line text-center">
                    <small class="text-muted d-block" style="margin-top: -25px;">Nonstop</small>
                    <small class="text-muted">2h 15m</small>
                </div>

                <div class="text-center" style="min-width: 60px;">
                    <h4 class="fw-bold mb-0"><?php echo date('H:i', strtotime($flight['arrival_time'])); ?></h4>
                    <small class="fw-bold">
                        <?php
                        preg_match('/\((.*?)\)/', $flight['destination'], $matches);
                        echo $matches[1] ?? 'DEST';
                        ?>
                    </small>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-end pt-2 border-top">
                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=ET&background=1B5E20&color=fff" class="rounded" width="24"
                        height="24">
                    <small class="text-muted">Earn Up to 100 miles</small>
                </div>
                <div class="text-end">
                    <div class="price-big"><?php echo number_format($flight['price']); ?> <small>ETB</small></div>
                    <span class="lowest-badge">Lowest price</span>
                </div>
            </div>
        </div>

        <!-- Booking Modal (Redesigned as per Screen 3) -->
        <div class="modal fade" id="bookModal<?php echo $flight['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content border-0 rounded-4 overflow-hidden shadow-lg">
                    <div class="modal-header bg-white p-4 border-0 border-bottom">
                        <h5 class="modal-title fw-bold">Passenger details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST" class="modal-body p-4 passenger-form">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                        <input type="hidden" name="trip_type" value="<?php echo htmlspecialchars($trip_type); ?>">

                        <div class="alert alert-light border d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h6 class="fw-bold mb-1">ADD &rarr; <?php echo htmlspecialchars($flight['destination']); ?>
                                </h6>
                                <small
                                    class="text-muted"><?php echo date('M d Y', strtotime($flight['departure_time'])); ?></small>
                            </div>
                            <div class="text-end">
                                <small class="text-muted d-block">Total Price:</small>
                                <span class="fw-bold text-primary-green">ETB
                                    <?php echo number_format($flight['price']); ?></span>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label>Title*</label>
                                <select name="title" class="form-select border-0 border-bottom rounded-0 px-0" required>
                                    <option value="Mr.">Mr.</option>
                                    <option value="Ms.">Ms.</option>
                                    <option value="Mrs.">Mrs.</option>
                                    <option value="Dr.">Dr.</option>
                                </select>
                            </div>
                            <div class="col-md-8 mb-3">
                                <label>Given Name (First and Middle Name)*</label>
                                <input type="text" name="given_names"
                                    class="form-control border-0 border-bottom rounded-0 px-0" required
                                    placeholder="Enter given names">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label>Last/Surname*</label>
                            <input type="text" name="last_name" class="form-control border-0 border-bottom rounded-0 px-0"
                                required placeholder="Enter surname">
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-4">
                                <label>Date of Birth*</label>
                                <input type="date" name="dob" class="form-control border-0 border-bottom rounded-0 px-0"
                                    required>
                            </div>
                            <div class="col-md-6 mb-4">
                                <label>Gender*</label>
                                <div class="gender-picker">
                                    <input type="radio" name="gender" value="male" id="male<?php echo $flight['id']; ?>"
                                        class="d-none gender-radio" checked>
                                    <label for="male<?php echo $flight['id']; ?>" class="gender-option">Male</label>

                                    <input type="radio" name="gender" value="female" id="female<?php echo $flight['id']; ?>"
                                        class="d-none gender-radio">
                                    <label for="female<?php echo $flight['id']; ?>" class="gender-option">Female</label>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label>Passport Number / ID*</label>
                            <input type="text" name="passport_number"
                                class="form-control border-0 border-bottom rounded-0 px-0" required
                                placeholder="Enter ID number">
                        </div>

                        <div class="bg-light p-3 rounded-3 mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="terms<?php echo $flight['id']; ?>"
                                    required>
                                <label class="form-check-label small" for="terms<?php echo $flight['id']; ?>">
                                    I agree to the terms and conditions and I confirm that the passenger names match the
                                    identification documents.
                                </label>
                            </div>
                        </div>

                        <button type="submit" name="book_flight" class="btn-search-main">
                            Confirm Booking & Generate PNR
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <script>
            document.getElementById('flightCard<?php echo $flight['id']; ?>').addEventListener('click', function () {
                const modal = new bootstrap.Modal(document.getElementById('bookModal<?php echo $flight['id']; ?>'));
                modal.show();
            });
        </script>
    <?php endforeach; ?>

    <div class="shebamiles-banner">
        <div class="row">
            <div class="col-8">
                <h5 class="fw-bold mb-2">Join ShebaMiles</h5>
                <p class="small mb-3">Join Ethiopian Airlines loyalty program and enjoy exclusive member benefits!</p>
                <a href="#" class="text-primary-green fw-bold text-decoration-none d-flex align-items-center gap-2">
                    Join Now <i class="fas fa-arrow-right"></i>
                </a>
            </div>
            <div class="col-4 d-flex align-items-center">
                <i class="fas fa-award fa-4x text-gold opacity-50"></i>
            </div>
        </div>
    </div>

    <div class="mb-5 py-4">
        <h5 class="fw-bold mb-3">Best fares from Addis Ababa</h5>
        <div class="row g-3">
            <div class="col-6 col-md-3">
                <a href="?destination=Bahir Dar&travel_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1548347643-1bc181ba4532?w=500" class="card-img-top" height="120" style="object-fit: cover;">
                        <div class="card-body p-2 text-center">
                            <small class="fw-bold d-block">Bahir Dar (BJR)</small>
                            <small class="text-primary-green fw-bold">From 7,402 ETB</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?destination=Gondar&travel_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1542296332-2e4473faf563?w=500" class="card-img-top" height="120" style="object-fit: cover;">
                        <div class="card-body p-2 text-center">
                            <small class="fw-bold d-block">Gondar (GDQ)</small>
                            <small class="text-primary-green fw-bold">From 7,800 ETB</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?destination=Dubai&travel_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1518684079-3c830dcef090?w=500" class="card-img-top" height="120" style="object-fit: cover;">
                        <div class="card-body p-2 text-center">
                            <small class="fw-bold d-block">Dubai (DXB)</small>
                            <small class="text-primary-green fw-bold">From 28,000 ETB</small>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?destination=Nairobi&travel_date=<?php echo date('Y-m-d'); ?>" class="text-decoration-none text-dark">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                        <img src="https://images.unsplash.com/photo-1583064629151-5ea9d673574c?w=500" class="card-img-top" height="120" style="object-fit: cover;">
                        <div class="card-body p-2 text-center">
                            <small class="fw-bold d-block">Nairobi (NBO)</small>
                            <small class="text-primary-green fw-bold">From 12,500 ETB</small>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bottom Navigation Mimic -->
<div class="sticky-bottom bg-white border-top d-lg-none py-2">
    <div class="container d-flex justify-content-between text-center px-4">
        <div class="text-primary-green">
            <i class="fas fa-home d-block"></i>
            <small class="x-small fw-bold">Home</small>
        </div>
        <div class="text-muted">
            <i class="fas fa-plane d-block"></i>
            <small class="x-small">Book</small>
        </div>
        <div class="text-muted">
            <i class="fas fa-suitcase d-block"></i>
            <small class="x-small">My Trips</small>
        </div>
        <div class="text-muted">
            <i class="fas fa-check-circle d-block"></i>
            <small class="x-small">Check-in</small>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>