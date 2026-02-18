<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch Specialties
$stmt = $pdo->query("SELECT * FROM health_specialties ORDER BY name ASC");
$specialties = $stmt->fetchAll();

// Fetch Doctors with Specialty
$where = "WHERE type = 'doctor'";
$params = [];
if (isset($_GET['specialty']) && !empty($_GET['specialty'])) {
    $where .= " AND specialty_id = ?";
    $params[] = $_GET['specialty'];
}

$stmt = $pdo->prepare("SELECT p.*, s.name as specialty_name 
                       FROM health_providers p 
                       LEFT JOIN health_specialties s ON p.specialty_id = s.id 
                       $where ORDER BY p.rating DESC");
$stmt->execute($params);
$doctors = $stmt->fetchAll();

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_doctor'])) {
    if (!isset($_SESSION['id'])) {
        redirectWithMessage('../login.php', 'warning', 'Please login to book a doctor.');
    }

    $user_id = $_SESSION['id'];
    $provider_id = intval($_POST['provider_id']);
    $type = $_POST['appointment_type'];
    $scheduled_at = $_POST['scheduled_at'];
    $reason = sanitize($_POST['reason']);

    try {
        $stmt = $pdo->prepare("INSERT INTO health_appointments (user_id, provider_id, appointment_type, scheduled_at, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $provider_id, $type, $scheduled_at, $reason]);
        redirectWithMessage('medical_records.php', 'success', 'Your appointment has been requested! You will receive a confirmation soon.');
    } catch (Exception $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4 align-items-end mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-1">Book an Expert Doctor</h2>
            <p class="text-muted mb-0">Select from Ethiopia's top-rated medical specialists</p>
        </div>
        <div class="col-lg-6">
            <form class="row g-2" method="GET">
                <div class="col-md-9">
                    <select name="specialty" class="form-select rounded-pill px-4 py-2 border-0 shadow-sm"
                        onchange="this.form.submit()">
                        <option value="">All Specialties</option>
                        <?php foreach ($specialties as $s): ?>
                            <option value="<?php echo $s['id']; ?>" <?php echo ($_GET['specialty'] ?? '') == $s['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($s['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 text-end">
                    <a href="medical_records.php" class="btn btn-outline-primary rounded-pill w-100 py-2">My
                        Bookings</a>
                </div>
            </form>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($doctors as $d): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-shadow transition-all">
                    <div class="position-relative">
                        <img src="<?php echo htmlspecialchars($d['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400'); ?>"
                            class="card-img-top" style="height: 250px; object-fit: cover;"
                            alt="<?php echo htmlspecialchars($d['name']); ?>">
                        <div class="position-absolute top-0 end-0 m-3">
                            <span class="badge bg-warning text-dark"><i class="fas fa-star me-1 text-white"></i>
                                <?php echo $d['rating']; ?>
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-1 text-primary small fw-bold text-uppercase">
                            <?php echo htmlspecialchars($d['specialty_name']); ?>
                        </div>
                        <h5 class="fw-bold text-dark mb-2">
                            <?php echo htmlspecialchars($d['name']); ?>
                        </h5>
                        <p class="text-muted small mb-3">
                            <?php echo htmlspecialchars($d['bio']); ?>
                        </p>
                        <div class="d-flex align-items-center text-muted small mb-4">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <?php echo htmlspecialchars($d['location']); ?>
                        </div>
                        <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold" data-bs-toggle="modal"
                            data-bs-target="#bookModal<?php echo $d['id']; ?>">
                            Book Appointment
                        </button>
                    </div>
                </div>
            </div>

            <!-- Booking Modal -->
            <div class="modal fade" id="bookModal<?php echo $d['id']; ?>" tabindex="-1">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content border-0 rounded-4 shadow">
                        <div class="modal-header border-0 p-4 pb-0">
                            <h5 class="modal-title fw-bold">Book Appointment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST" class="modal-body p-4">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="provider_id" value="<?php echo $d['id']; ?>">
                            <input type="hidden" name="book_doctor" value="1">

                            <div class="d-flex align-items-center mb-4 p-3 bg-light rounded-4">
                                <img src="<?php echo htmlspecialchars($d['image_url']); ?>" class="rounded-circle me-3"
                                    width="50" height="50" style="object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-0">
                                        <?php echo htmlspecialchars($d['name']); ?>
                                    </h6>
                                    <span class="small text-primary">
                                        <?php echo htmlspecialchars($d['specialty_name']); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Appointment Type</label>
                                <div class="d-flex gap-2">
                                    <input type="radio" class="btn-check" name="appointment_type"
                                        id="type1-<?php echo $d['id']; ?>" value="in_person" checked>
                                    <label class="btn btn-outline-primary rounded-pill px-4 flex-grow-1"
                                        for="type1-<?php echo $d['id']; ?>">In-Person</label>

                                    <input type="radio" class="btn-check" name="appointment_type"
                                        id="type2-<?php echo $d['id']; ?>" value="virtual">
                                    <label class="btn btn-outline-primary rounded-pill px-4 flex-grow-1"
                                        for="type2-<?php echo $d['id']; ?>">Virtual</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-bold">Date & Time</label>
                                <input type="datetime-local" name="scheduled_at"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-2" required
                                    min="<?php echo date('Y-m-d\TH:i'); ?>">
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Reason for Visit</label>
                                <textarea name="reason" class="form-control border-0 bg-light px-4 py-3" rows="3"
                                    placeholder="Briefly describe your symptoms or reason for visit..."
                                    style="border-radius:15px;" required></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow-sm">
                                Confirm Appointment Request
                            </button>
                            <p class="text-center text-muted small mt-3">You will receive a notification once the doctor
                                confirms.</p>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
        transform: translateY(-5px);
    }
</style>

<?php include '../includes/footer.php'; ?>