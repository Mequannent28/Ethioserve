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
$booking_success = false;
$booked_doctor_name = '';
$booked_doctor_id = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_doctor'])) {
    if (!isLoggedIn()) {
        redirectWithMessage('../login.php', 'warning', 'Please login to book a doctor.');
    }

    $user_id = getCurrentUserId();
    $provider_id = intval($_POST['provider_id']);
    $type = $_POST['appointment_type'];
    $scheduled_at = $_POST['scheduled_at'];
    $reason = sanitize($_POST['reason']);

    try {
        // Save the appointment
        $stmt = $pdo->prepare("INSERT INTO health_appointments (user_id, provider_id, appointment_type, scheduled_at, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $provider_id, $type, $scheduled_at, $reason]);

        // Send automated message from doctor to the patient chat
        $auto_msg = "Your appointment has been received! The doctor will review your request and contact you soon. Please keep an eye on this chat for updates.";
        $stmt2 = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message) VALUES (?, 'doctor', ?, ?, ?)");
        $stmt2->execute([$provider_id, $provider_id, $user_id, $auto_msg]);

        // Get doctor name for the success modal
        $dStmt = $pdo->prepare("SELECT name FROM health_providers WHERE id = ?");
        $dStmt->execute([$provider_id]);
        $dRow = $dStmt->fetch();
        $booked_doctor_name = $dRow['name'] ?? 'the doctor';
        $booked_doctor_id = $provider_id;
        $booking_success = true;
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
                        <!-- Quick Actions: Video Call, Chat, E-Prescription -->
                        <div class="d-flex justify-content-center gap-4 mb-3 pt-2 border-top">
                            <a href="doctor_video_call.php?doctor_id=<?php echo $d['id']; ?>"
                                class="text-decoration-none text-center doctor-action-btn">
                                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                                    style="width:48px;height:48px;">
                                    <i class="fas fa-video text-primary"></i>
                                </div>
                                <small class="text-muted fw-medium" style="font-size:0.7rem;">Video Call</small>
                            </a>
                            <a href="doctor_chat.php?doctor_id=<?php echo $d['id']; ?>"
                                class="text-decoration-none text-center doctor-action-btn">
                                <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                                    style="width:48px;height:48px;">
                                    <i class="fas fa-comments text-success"></i>
                                </div>
                                <small class="text-muted fw-medium" style="font-size:0.7rem;">Chat</small>
                            </a>
                            <a href="medical_records.php" class="text-decoration-none text-center doctor-action-btn">
                                <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-1"
                                    style="width:48px;height:48px;">
                                    <i class="fas fa-file-prescription text-warning"></i>
                                </div>
                                <small class="text-muted fw-medium" style="font-size:0.7rem;">E-Prescription</small>
                            </a>
                        </div>

                        <button class="btn btn-primary w-100 rounded-pill py-2 fw-bold" data-bs-toggle="modal"
                            data-bs-target="#bookModal<?php echo $d['id']; ?>">
                            <i class="fas fa-calendar-check me-2"></i>Book Appointment
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

<?php if (isset($error)): ?>
    <div class="alert alert-danger container mt-3"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<!-- Booking Success Modal -->
<div class="modal fade" id="bookingSuccessModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow-lg overflow-hidden">
            <div class="modal-body p-0">
                <!-- Success Header -->
                <div style="background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);"
                    class="text-center p-5 pb-4">
                    <div class="success-icon-wrapper mb-3">
                        <div class="success-ring">
                            <div class="checkmark-circle">
                                <i class="fas fa-check" style="font-size:2rem; color:#fff;"></i>
                            </div>
                        </div>
                    </div>
                    <h4 class="fw-bold text-white mb-1">Successfully Booked!</h4>
                    <p class="text-white-50 mb-0" style="font-size:0.9rem;">Your appointment has been confirmed</p>
                </div>

                <!-- Message Section -->
                <div class="p-4">
                    <!-- Doctor message bubble -->
                    <div class="d-flex align-items-start gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center"
                                style="width:44px;height:44px;">
                                <i class="fas fa-user-md text-primary"></i>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="bg-light rounded-4 p-3" style="border-radius:4px 18px 18px 18px !important;">
                                <p class="mb-1 fw-semibold text-dark" style="font-size:0.85rem;">
                                    Message from <span id="successDoctorName" class="text-primary"></span>
                                </p>
                                <p class="mb-0 text-muted" style="font-size:0.88rem;line-height:1.5;">
                                    ✅ Your <strong id="successApptType"></strong> appointment has been received! The
                                    doctor will review your request and
                                    <strong>contact you soon</strong>. Please keep an eye on this chat for updates.
                                </p>
                            </div>
                            <small class="text-muted" style="font-size:0.7rem;"><i
                                    class="fas fa-check-double text-info"></i> Just now</small>
                        </div>
                    </div>

                    <!-- Appointment Summary Card -->
                    <div class="card border-0 rounded-4 bg-light mb-4 shadow-sm border-start border-4 border-primary">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-white rounded-3 p-2 shadow-sm">
                                        <i id="successApptIcon" class="fas fa-calendar-alt text-primary"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold small" id="successApptDateDisplay"></div>
                                        <div class="text-muted" style="font-size:0.7rem;" id="successApptTimeDisplay">
                                        </div>
                                    </div>
                                </div>
                                <span
                                    class="badge rounded-pill bg-primary bg-opacity-10 text-primary border border-primary border-opacity-10"
                                    id="successApptTypeBadge"></span>
                            </div>
                        </div>
                    </div>

                    <!-- Info pills -->
                    <div class="d-flex gap-2 flex-wrap justify-content-center mb-4">
                        <span class="badge rounded-pill px-3 py-2"
                            style="background:#e8f5e9;color:#2e7d32;font-size:0.8rem;"><i
                                class="fas fa-calendar-check me-1"></i>Appointment Saved</span>
                        <span class="badge rounded-pill px-3 py-2"
                            style="background:#e3f2fd;color:#1565c0;font-size:0.8rem;"><i
                                class="fas fa-comment-medical me-1"></i>Message Sent</span>
                        <span class="badge rounded-pill px-3 py-2"
                            style="background:#fff8e1;color:#f57f17;font-size:0.8rem;"><i
                                class="fas fa-bell me-1"></i>Pending Confirmation</span>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex gap-2">
                        <a id="goChatBtn" href="#" class="btn btn-primary rounded-pill flex-grow-1 py-2 fw-bold">
                            <i class="fas fa-comments me-2"></i>Open Chat
                        </a>
                        <button type="button" class="btn btn-outline-secondary rounded-pill px-3"
                            data-bs-dismiss="modal">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="text-center mt-3">
                        <a href="medical_records.php" class="text-muted small text-decoration-none">
                            <i class="fas fa-notes-medical me-1"></i>View My Appointments
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
        transform: translateY(-5px);
    }

    .doctor-action-btn:hover>div {
        transform: scale(1.15);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .doctor-action-btn>div {
        transition: all 0.25s ease;
    }

    .doctor-action-btn:hover small {
        color: #333 !important;
    }

    /* Success Modal Animations */
    .success-ring {
        width: 90px;
        height: 90px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.15);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto;
        animation: ringPulse 1.5s ease-in-out infinite;
    }

    .checkmark-circle {
        width: 64px;
        height: 64px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.25);
        display: flex;
        align-items: center;
        justify-content: center;
        animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) both;
    }

    @keyframes ringPulse {

        0%,
        100% {
            box-shadow: 0 0 0 0 rgba(255, 255, 255, 0.4);
        }

        50% {
            box-shadow: 0 0 0 15px rgba(255, 255, 255, 0);
        }
    }

    @keyframes popIn {
        0% {
            transform: scale(0);
            opacity: 0;
        }

        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    #bookingSuccessModal .modal-content {
        animation: slideUp 0.4s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(30px);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
</style>

<script>
    // Show success modal if booking was successful
    const bookingSuccess = <?php echo $booking_success ? 'true' : 'false'; ?>;
    const bookedDoctorName = <?php echo json_encode($booked_doctor_name); ?>;
    const bookedDoctorId = <?php echo intval($booked_doctor_id); ?>;
    const bookedApptType = <?php echo json_encode($_POST['appointment_type'] ?? ''); ?>;
    const bookedApptDate = <?php echo json_encode($_POST['scheduled_at'] ?? ''); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        if (bookingSuccess) {
            document.getElementById('successDoctorName').textContent = bookedDoctorName;
            document.getElementById('goChatBtn').href = 'doctor_chat.php?doctor_id=' + bookedDoctorId;

            // Set visual details
            const typeLabel = (bookedApptType === 'virtual') ? 'Telemedicine' : 'In-Person';
            const typeIcon = (bookedApptType === 'virtual') ? 'fa-video' : 'fa-hospital';
            
            document.getElementById('successApptType').textContent = typeLabel;
            document.getElementById('successApptTypeBadge').textContent = typeLabel;
            document.getElementById('successApptIcon').className = 'fas ' + typeIcon + ' text-primary';
            
            if (bookedApptDate) {
                const dateObj = new Date(bookedApptDate);
                document.getElementById('successApptDateDisplay').textContent = dateObj.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
                document.getElementById('successApptTimeDisplay').textContent = dateObj.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            }

            // Small delay so the modal fade-in animation works smoothly
            setTimeout(function () {
                const successModal = new bootstrap.Modal(
                    document.getElementById('bookingSuccessModal'),
                    { backdrop: 'static', keyboard: false }
                );
                successModal.show();
            }, 300);
        }
    });
</script>

<?php include '../includes/footer.php'; ?>