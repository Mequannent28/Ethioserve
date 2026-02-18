<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch stats for the dashboard if needed
$provider_counts = $pdo->query("SELECT type, COUNT(*) as count FROM health_providers GROUP BY type")->fetchAll(PDO::FETCH_KEY_PAIR);

include '../includes/header.php';
?>

<div class="health-services-hero py-5 mb-5 text-white"
    style="background: linear-gradient(135deg, #2196F3 0%, #1565C0 100%);">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-7 text-center text-lg-start">
                <h1 class="display-3 fw-bold mb-3">Your Complete Health Companion</h1>
                <p class="lead mb-4 opacity-90">Expert medical care, medicines, and lab tests available at your
                    fingertips. Safe, secure, and reliable Ethiopian healthcare.</p>
                <div class="d-flex flex-wrap gap-3 justify-content-center justify-content-lg-start">
                    <a href="ambulance.php" class="btn btn-danger btn-lg rounded-pill px-4 shadow-sm animate-pulse">
                        <i class="fas fa-ambulance me-2"></i>Emergency Ambulance
                    </a>
                    <a href="telemedicine.php" class="btn btn-light btn-lg rounded-pill px-4 shadow-sm text-primary">
                        <i class="fas fa-video me-2"></i>Virtual Consultation
                    </a>
                </div>
            </div>
            <div class="col-lg-5 d-none d-lg-block">
                <img src="https://images.unsplash.com/photo-1505751172157-c72464a700fb?w=800"
                    class="img-fluid rounded-4 shadow-lg border border-4 border-white border-opacity-25"
                    alt="Healthcare">
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row g-4 mb-5">
        <!-- Quick Actions -->
        <div class="col-md-3">
            <a href="doctors.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center hover-up transition-all bg-white">
                    <div class="icon-box mb-3 mx-auto"
                        style="width: 70px; height: 70px; background: #E3F2FD; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-user-md text-primary fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Book a Doctor</h5>
                    <p class="text-muted small mb-0">In-person visits or virtual chat</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="pharmacy.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center hover-up transition-all bg-white">
                    <div class="icon-box mb-3 mx-auto"
                        style="width: 70px; height: 70px; background: #E8F5E9; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-pills text-success fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Pharmacy</h5>
                    <p class="text-muted small mb-0">Order medicines for delivery</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="lab.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center hover-up transition-all bg-white">
                    <div class="icon-box mb-3 mx-auto"
                        style="width: 70px; height: 70px; background: #FFF3E0; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-flask text-warning fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Lab Booking</h5>
                    <p class="text-muted small mb-0">Diagnostic tests & reports</p>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a href="medical_records.php" class="text-decoration-none">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center hover-up transition-all bg-white">
                    <div class="icon-box mb-3 mx-auto"
                        style="width: 70px; height: 70px; background: #F3E5F5; border-radius: 20px; display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-file-medical text-purple fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark mb-2">Medical History</h5>
                    <p class="text-muted small mb-0">View records & prescriptions</p>
                </div>
            </a>
        </div>
    </div>

    <!-- Health Stats / Featured Services -->
    <div class="row g-4 align-items-center mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-4">Why Ethioserve Health?</h2>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-shield-alt text-primary fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold">Secured Medical Data</h5>
                    <p class="text-muted">Your health information is encrypted and only accessible by you and authorized
                        healthcare providers.</p>
                </div>
            </div>
            <div class="d-flex mb-4">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-clock text-primary fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold">24/7 Availability</h5>
                    <p class="text-muted">From emergency ambulances to late-night pharmacy deliveries, we are here for
                        you around the clock.</p>
                </div>
            </div>
            <div class="d-flex mb-0">
                <div class="flex-shrink-0 me-3">
                    <i class="fas fa-star text-primary fs-3"></i>
                </div>
                <div>
                    <h5 class="fw-bold">Top-Rated Professionals</h5>
                    <p class="text-muted">Browse reviews and ratings to choose the best doctors and labs authenticated
                        by our platform.</p>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow rounded-4 p-4 bg-light">
                <h4 class="fw-bold mb-4">Quick Health Tips</h4>
                <div class="list-group list-group-flush bg-transparent">
                    <div class="list-group-item bg-transparent border-0 px-0 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-3">01</span>
                            <span class="fw-medium text-dark">Drink at least 8 glasses of water daily for better
                                hydration.</span>
                        </div>
                    </div>
                    <div class="list-group-item bg-transparent border-0 px-0 mb-3">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-3">02</span>
                            <span class="fw-medium text-dark">Regular checkups can help find problems before they
                                start.</span>
                        </div>
                    </div>
                    <div class="list-group-item bg-transparent border-0 px-0">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-primary rounded-pill me-3">03</span>
                            <span class="fw-medium text-dark">Wash your hands frequently to prevent the spread of
                                infections.</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-up:hover {
        transform: translateY(-8px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .15) !important;
    }

    .text-purple {
        color: #9C27B0;
    }

    .animate-pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.05);
        }

        100% {
            transform: scale(1);
        }
    }
</style>

<?php include '../includes/footer.php'; ?>