<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$request_id = intval($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT a.*, p.name as provider_name, p.phone as provider_phone 
                       FROM health_ambulance_requests a 
                       LEFT JOIN health_providers p ON a.provider_id = p.id 
                       WHERE a.id = ?");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    header("Location: health_services.php");
    exit();
}

include '../includes/header.php';
?>

<div class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="mb-4">
                <div class="spinner-grow text-danger" role="status" style="width: 3rem; height: 3rem;"></div>
            </div>
            <h2 class="fw-bold mb-2">Ambulance Dispatched</h2>
            <p class="text-muted">Stay on the line. An ambulance from <b>
                    <?php echo htmlspecialchars($request['provider_name']); ?>
                </b> is heading to your location.</p>

            <div class="card border-0 shadow-lg rounded-5 overflow-hidden my-5">
                <div class="ratio ratio-16x9 bg-light">
                    <!-- Placeholder for Maps -->
                    <div class="d-flex align-items-center justify-content-center text-muted">
                        <div>
                            <i class="fas fa-map-marked-alt fs-1 mb-2"></i>
                            <p>Live GPS Tracking Connected</p>
                            <h4 class="fw-bold text-dark">ETA: 8 Minutes</h4>
                        </div>
                    </div>
                </div>
                <div class="card-body p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-start">
                            <h6 class="fw-bold mb-1">Driver: Dawit Kebede</h6>
                            <span class="badge bg-success rounded-pill">On the way</span>
                        </div>
                        <a href="tel:<?php echo $request['provider_phone']; ?>"
                            class="btn btn-danger rounded-pill px-4">
                            <i class="fas fa-phone-alt me-2"></i>Contact
                        </a>
                    </div>
                </div>
            </div>

            <div class="alert alert-info rounded-4 py-3">
                <i class="fas fa-info-circle me-2"></i>Keep your phone active. The driver may call you for landmark
                details.
            </div>

            <a href="health_services.php" class="btn btn-link text-muted mt-4">Cancel Request</a>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>