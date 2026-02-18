<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$cat_id = intval($_GET['id'] ?? 0);

// Fetch Category
$stmt = $pdo->prepare("SELECT * FROM home_service_categories WHERE id = ?");
$stmt->execute([$cat_id]);
$category = $stmt->fetch();

if (!$category) {
    header("Location: home_services.php");
    exit();
}

// Fetch Options
$stmt = $pdo->prepare("SELECT * FROM home_service_options WHERE category_id = ?");
$stmt->execute([$cat_id]);
$options = $stmt->fetchAll();

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {
    if (!isset($_SESSION['id'])) {
        redirectWithMessage('../login.php', 'warning', 'Please login to book a service.');
    }

    $customer_id = $_SESSION['id'];
    $option_id = intval($_POST['option_id']);
    $scheduled_at = $_POST['scheduled_at'];
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $notes = sanitize($_POST['notes'] ?? '');

    // Get price
    $stmt = $pdo->prepare("SELECT base_price FROM home_service_options WHERE id = ?");
    $stmt->execute([$option_id]);
    $price = $stmt->fetchColumn();

    try {
        $stmt = $pdo->prepare("INSERT INTO home_service_bookings (customer_id, category_id, option_id, scheduled_at, service_address, contact_phone, notes, total_price, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$customer_id, $cat_id, $option_id, $scheduled_at, $address, $phone, $notes, $price]);

        redirectWithMessage('my_home_bookings.php', 'success', 'Your booking request has been sent! A service provider will be assigned soon.');
    } catch (Exception $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="home_services.php">Home Services</a></li>
            <li class="breadcrumb-item active">
                <?php echo htmlspecialchars($category['name']); ?>
            </li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Service Options -->
        <div class="col-lg-7">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                    style="width: 60px; height: 60px;">
                    <i class="<?php echo $category['icon']; ?> fs-3"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-0">
                        <?php echo htmlspecialchars($category['name']); ?>
                    </h2>
                    <p class="text-muted mb-0">
                        <?php echo htmlspecialchars($category['description']); ?>
                    </p>
                </div>
            </div>

            <h4 class="fw-bold mb-4">Select a Service Task</h4>
            <div class="row g-3">
                <?php foreach ($options as $opt): ?>
                    <div class="col-12">
                        <div class="service-option-card card border-0 shadow-sm rounded-4 p-4 hover-shadow transition-all"
                            onclick="selectOption(<?php echo $opt['id']; ?>, '<?php echo addslashes($opt['name']); ?>', <?php echo $opt['base_price']; ?>)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input option-radio" type="radio" name="temp_opt"
                                            id="opt<?php echo $opt['id']; ?>" value="<?php echo $opt['id']; ?>">
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="fw-bold mb-1">
                                            <?php echo htmlspecialchars($opt['name']); ?>
                                        </h6>
                                        <p class="text-muted small mb-0">
                                            <?php echo htmlspecialchars($opt['description'] ?: 'Professional ' . strtolower($opt['name']) . ' for your home.'); ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-end">
                                    <span class="h5 fw-bold text-primary-green mb-0">
                                        <?php echo number_format($opt['base_price']); ?> ETB
                                    </span>
                                    <div class="text-muted small">Starting Price</div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Booking Sidebar -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden sticky-top" style="top: 100px;">
                <div class="bg-primary-green text-white p-4">
                    <h4 class="fw-bold mb-0">Book Appointment</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="bookingForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="option_id" id="selectedOptionId" required>
                        <input type="hidden" name="book_service" value="1">

                        <div id="estimateBox" class="bg-light rounded-4 p-3 mb-4 d-none">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Selected Service:</span>
                                <span id="displayServiceName" class="fw-bold"></span>
                            </div>
                            <div class="d-flex justify-content-between h5 fw-bold text-dark mb-0">
                                <span>Total Estimate:</span>
                                <span><span id="displayPrice">0</span> ETB</span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Schedule Date & Time</label>
                            <input type="datetime-local" name="scheduled_at"
                                class="form-control rounded-pill border-0 bg-light px-4 py-2" required
                                min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Service Location / Address</label>
                            <textarea name="address" class="form-control border-0 bg-light px-4 py-3" rows="2"
                                placeholder="House No, Street, Neighborhood..." style="border-radius:15px;"
                                required></textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Contact Phone</label>
                            <input type="tel" name="phone" class="form-control rounded-pill border-0 bg-light px-4 py-2"
                                placeholder="+251..." required
                                value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>">
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Special Notes (Optional)</label>
                            <textarea name="notes" class="form-control border-0 bg-light px-4 py-3" rows="2"
                                placeholder="Any specific instructions for the pro..."
                                style="border-radius:15px;"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" id="submitBtn"
                                class="btn btn-primary-green btn-lg rounded-pill py-3 fw-bold shadow-sm" disabled>
                                Confirm Booking
                            </button>
                        </div>
                    </form>

                    <div class="mt-4 p-3 rounded-4 bg-info bg-opacity-10 border border-info border-opacity-25">
                        <div class="d-flex">
                            <i class="fas fa-info-circle text-info mt-1 me-3"></i>
                            <p class="small text-muted mb-0">Payments are made directly to the service provider after
                                the job is completed and you are satisfied.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .service-option-card {
        cursor: pointer;
    }

    .service-option-card:hover {
        background-color: #f8f9fa;
        border-color: #FF9800 !important;
    }

    .service-option-card.selected {
        background-color: #FFF3E0;
        border: 2px solid #FF9800 !important;
    }

    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .08) !important;
    }
</style>

<script>
    function selectOption(id, name, price) {
        // UI Update
        document.querySelectorAll('.service-option-card').forEach(c => c.classList.remove('selected'));
        const card = event.currentTarget;
        card.classList.add('selected');

        document.getElementById('opt' + id).checked = true;

        // Form Update
        document.getElementById('selectedOptionId').value = id;
        document.getElementById('displayServiceName').innerText = name;
        document.getElementById('displayPrice').innerText = price.toLocaleString();
        document.getElementById('estimateBox').classList.remove('d-none');
        document.getElementById('submitBtn').disabled = false;
    }
</script>

<?php include '../includes/footer.php'; ?>