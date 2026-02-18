<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch Ambulance Providers
$stmt = $pdo->query("SELECT * FROM health_providers WHERE type = 'ambulance_service' ORDER BY rating DESC");
$providers = $stmt->fetchAll();

// Handle Emergency Request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_ambulance'])) {
    if (!isset($_SESSION['id'])) {
        redirectWithMessage('../login.php', 'warning', 'Authentication required for emergency services.');
    }

    $user_id = $_SESSION['id'];
    $provider_id = intval($_POST['provider_id']);
    $emergency_type = sanitize($_POST['emergency_type']);
    $address = sanitize($_POST['address']);
    $phone = sanitize($_POST['phone']);

    try {
        $stmt = $pdo->prepare("INSERT INTO health_ambulance_requests (user_id, provider_id, current_location, emergency_type, contact_phone, status) VALUES (?, ?, ?, ?, ?, 'dispatched')");
        $stmt->execute([$user_id, $provider_id, $address, $emergency_type, $phone]);

        $request_id = $pdo->lastInsertId();
        header("Location: ambulance_tracking.php?id=" . $request_id);
        exit();
    } catch (Exception $e) {
        $error = "Request failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="emergency-bg min-vh-100 py-5" style="background: #FFF5F5;">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6 text-center mb-5">
                <div class="ambulance-icon mb-4"><i class="fas fa-ambulance text-danger" style="font-size: 5rem;"></i>
                </div>
                <h1 class="display-4 fw-bold text-dark">Emergency Response</h1>
                <p class="lead text-muted">Request immediate medical transport with GPS tracking</p>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                    <div class="bg-danger text-white p-4 text-center">
                        <h4 class="fw-bold mb-0">Request Now</h4>
                    </div>
                    <div class="card-body p-5">
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="request_ambulance" value="1">

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Select Service Provider</label>
                                <select name="provider_id"
                                    class="form-select form-select-lg rounded-pill border-0 bg-light px-4" required>
                                    <?php foreach ($providers as $p): ?>
                                        <option value="<?php echo $p['id']; ?>">
                                            <?php echo htmlspecialchars($p['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Type of Emergency</label>
                                <div class="row g-2">
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="emergency_type" id="e1"
                                            value="Accident" checked>
                                        <label class="btn btn-outline-danger w-100 rounded-4 py-3" for="e1">
                                            <i class="fas fa-car-crash d-block mb-2 fs-3"></i> Accident
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="emergency_type" id="e2"
                                            value="Heart Attack">
                                        <label class="btn btn-outline-danger w-100 rounded-4 py-3" for="e2">
                                            <i class="fas fa-heartbeat d-block mb-2 fs-3"></i> Cardiac
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="emergency_type" id="e3"
                                            value="Breathing">
                                        <label class="btn btn-outline-danger w-100 rounded-4 py-3" for="e3">
                                            <i class="fas fa-lungs d-block mb-2 fs-3"></i> Breathing
                                        </label>
                                    </div>
                                    <div class="col-6">
                                        <input type="radio" class="btn-check" name="emergency_type" id="e4"
                                            value="Other">
                                        <label class="btn btn-outline-danger w-100 rounded-4 py-3" for="e4">
                                            <i class="fas fa-plus d-block mb-2 fs-3"></i> Other
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-bold">Pickup Location (Address)</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-0 rounded-start-pill ps-4"><i
                                            class="fas fa-map-marker-alt text-danger"></i></span>
                                    <input type="text" name="address"
                                        class="form-control form-control-lg border-0 bg-light rounded-end-pill pe-4"
                                        placeholder="Your current address..." required>
                                </div>
                            </div>

                            <div class="mb-5">
                                <label class="form-label small fw-bold">Contact Phone Number</label>
                                <input type="tel" name="phone"
                                    class="form-control form-control-lg rounded-pill border-0 bg-light px-4"
                                    placeholder="+251..." required
                                    value="<?php echo htmlspecialchars($_SESSION['phone'] ?? ''); ?>">
                            </div>

                            <div class="d-grid">
                                <button type="submit"
                                    class="btn btn-danger btn-lg rounded-pill py-3 fw-bold shadow-lg animate-pulse">
                                    <i class="fas fa-phone-alt me-2"></i>CONFIRM EMERGENCY CALL
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-4 d-none d-lg-block">
                <div class="card border-0 shadow rounded-5 p-4 bg-white h-100">
                    <h5 class="fw-bold text-danger mb-4"><i class="fas fa-first-aid me-2"></i>First Aid Tips</h5>
                    <div class="mb-4">
                        <h6 class="fw-bold">1. Stay Calm</h6>
                        <p class="small text-muted">Keep the patient calm and stay with them until the ambulance
                            arrives.</p>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold">2. Clear the Way</h6>
                        <p class="small text-muted">Ensure the entrance to your home is clear and visible to paramedics.
                        </p>
                    </div>
                    <div class="mb-4">
                        <h6 class="fw-bold">3. Check Breathing</h6>
                        <p class="small text-muted">If the patient is unconscious, check for regular breathing and
                            heartbeat.</p>
                    </div>
                    <div class="mt-auto p-4 bg-light rounded-4 text-center">
                        <p class="small mb-0">Help is on the way. Our average response time is <b>12 minutes</b>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .emergency-bg {
        background-image: radial-gradient(circle at 10% 20%, rgba(255, 10, 10, 0.05) 0%, rgba(255, 255, 255, 1) 90%);
    }

    .animate-pulse {
        animation: pulse 1.5s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        70% {
            transform: scale(1.02);
            box-shadow: 0 0 0 15px rgba(220, 53, 69, 0);
        }

        100% {
            transform: scale(1);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }
</style>

<?php include '../includes/footer.php'; ?>