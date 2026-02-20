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

// Fetch Providers for this Category
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name, u.phone as user_phone
    FROM home_service_providers p
    JOIN users u ON p.user_id = u.id
    JOIN provider_services ps ON p.id = ps.provider_id
    WHERE ps.category_id = ? AND p.availability_status = 'available'
    ORDER BY p.rating DESC
");
$stmt->execute([$cat_id]);
$providers = $stmt->fetchAll();

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_service'])) {
    $is_ajax = isset($_POST['ajax']) && $_POST['ajax'] === '1';

    if (!isset($_SESSION['id'])) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Please login to book a service.']);
            exit;
        }
        redirectWithMessage('../login.php', 'warning', 'Please login to book a service.');
    }

    $customer_id = $_SESSION['id'];
    $option_id = intval($_POST['option_id']);
    $provider_id = !empty($_POST['provider_id']) ? intval($_POST['provider_id']) : null;
    $scheduled_at = $_POST['scheduled_at'];
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);
    $notes = sanitize($_POST['notes'] ?? '');

    // Get price
    $stmt = $pdo->prepare("SELECT base_price FROM home_service_options WHERE id = ?");
    $stmt->execute([$option_id]);
    $price = $stmt->fetchColumn();

    try {
        $stmt = $pdo->prepare("INSERT INTO home_service_bookings (customer_id, category_id, option_id, provider_id, scheduled_at, service_address, contact_phone, notes, total_price, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$customer_id, $cat_id, $option_id, $provider_id, $scheduled_at, $address, $phone, $notes, $price]);

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Your booking request has been sent! A service provider will be assigned soon.']);
            exit;
        }
        redirectWithMessage('my_home_bookings.php', 'success', 'Your booking request has been sent! A service provider will be assigned soon.');
    } catch (Exception $e) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => "Booking failed: " . $e->getMessage()]);
            exit;
        }
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

            <!-- Professionals Section -->
            <h4 class="fw-bold mb-4">1. Choose a Registered Professional</h4>
            <?php if (empty($providers)): ?>
                <div class="alert alert-light border-0 rounded-4 p-4 mb-5">
                    <p class="text-muted mb-0">No specific professionals listed for this category. We will assign the best
                        available expert for you.</p>
                </div>
            <?php else: ?>
                <div class="row g-4 mb-5">
                    <?php foreach ($providers as $pro): ?>
                        <div class="col-12">
                            <div class="provider-card card border-0 shadow-sm rounded-4 overflow-hidden hover-shadow transition-all"
                                onclick="selectProvider(<?php echo $pro['id']; ?>, '<?php echo addslashes($pro['full_name']); ?>')">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-start">
                                        <div class="me-4 position-relative">
                                            <img src="<?php echo $pro['profile_image'] ?: '../assets/img/default-avatar.png'; ?>"
                                                class="rounded-circle shadow-sm"
                                                style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #f8f9fa;">
                                            <span
                                                class="position-absolute bottom-0 end-0 bg-success border border-white border-2 rounded-circle"
                                                style="width:18px; height:18px;"></span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <div>
                                                    <h5 class="fw-bold mb-0">
                                                        <?php echo htmlspecialchars($pro['full_name']); ?>
                                                        <i class="fas fa-check-circle text-primary ms-1"
                                                            style="font-size: 0.8em;" title="Verified Pro"></i>
                                                    </h5>
                                                    <div class="text-warning small mb-2">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <i
                                                                class="fas fa-star <?php echo $i <= round($pro['rating']) ? '' : 'text-muted opacity-25'; ?>"></i>
                                                        <?php endfor; ?>
                                                        <span class="text-muted ms-2">(<?php echo $pro['total_reviews']; ?>
                                                            reviews)</span>
                                                    </div>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input provider-radio" type="radio" name="temp_pro"
                                                        id="pro<?php echo $pro['id']; ?>" value="<?php echo $pro['id']; ?>">
                                                </div>
                                            </div>

                                            <p class="text-muted small mb-3">
                                                <?php echo htmlspecialchars($pro['bio']); ?>
                                            </p>

                                            <div class="row g-2">
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center text-muted small">
                                                        <i class="fas fa-briefcase me-2 text-primary-green"></i>
                                                        <span><strong>Experience:</strong>
                                                            <?php echo $pro['experience_years']; ?>+ Years</span>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="d-flex align-items-center text-muted small">
                                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                                        <span><strong>Location:</strong>
                                                            <?php echo htmlspecialchars($pro['location']); ?></span>
                                                    </div>
                                                </div>
                                                <?php if ($pro['degree_type']): ?>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <i class="fas fa-graduation-cap me-2 text-info"></i>
                                                            <span><strong>Education:</strong>
                                                                <?php echo htmlspecialchars($pro['degree_type']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($pro['certification']): ?>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <i class="fas fa-certificate me-2 text-warning"></i>
                                                            <span><strong>Certification:</strong>
                                                                <?php echo htmlspecialchars($pro['certification']); ?></span>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <h4 class="fw-bold mb-4">2. Select a Service Task</h4>
            <div class="row g-3 mb-5">
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
                        <input type="hidden" name="provider_id" id="selectedProviderId">
                        <input type="hidden" name="book_service" value="1">

                        <div id="estimateBox" class="bg-light rounded-4 p-3 mb-4 d-none">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Professional Type:</span>
                                <span
                                    class="fw-bold text-dark"><?php echo htmlspecialchars($category['name']); ?></span>
                            </div>
                            <div id="selectedProDisplay"
                                class="d-flex justify-content-between small text-muted mb-1 d-none">
                                <span>Assigned Pro:</span>
                                <span id="displayProviderName" class="fw-bold text-primary-green"></span>
                            </div>
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Selected Task:</span>
                                <span id="displayServiceName" class="fw-bold text-dark"></span>
                            </div>
                            <div class="d-flex justify-content-between h5 fw-bold text-dark mb-0 pt-2 border-top mt-2">
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

    .provider-card {
        cursor: pointer;
        border: 2px solid transparent !important;
    }

    .provider-card:hover {
        background-color: #f8f9fa;
        border-color: #2E7D32 !important;
    }

    .provider-card.selected {
        background-color: #E8F5E9;
        border: 2px solid #2E7D32 !important;
    }
</style>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    function selectOption(id, name, price) {
        // UI Update
        document.querySelectorAll('.service-option-card').forEach(c => c.classList.remove('selected'));
        // Find the card that was clicked
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

    function selectProvider(id, name) {
        // UI Update
        document.querySelectorAll('.provider-card').forEach(c => c.classList.remove('selected'));
        const card = event.currentTarget;
        card.classList.add('selected');

        document.getElementById('pro' + id).checked = true;

        // Form Update
        document.getElementById('selectedProviderId').value = id;
        document.getElementById('displayProviderName').innerText = name;
        document.getElementById('selectedProDisplay').classList.remove('d-none');
    }

    // Handle Form Submission with SweetAlert
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

        Swal.fire({
            title: 'Processing Booking',
            text: 'Please wait while we secure your booking...',
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
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        title: 'Success!',
                        text: data.message,
                        icon: 'success',
                        confirmButtonText: 'View My Bookings',
                        confirmButtonColor: '#2E7D32',
                        allowOutsideClick: false
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = 'my_home_bookings.php';
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
                    text: 'An unexpected error occurred. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#d33'
                });
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            });
    });
</script>

<?php include '../includes/footer.php'; ?>