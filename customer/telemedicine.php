<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row align-items-center mb-5">
        <div class="col-lg-6">
            <h1 class="display-4 fw-bold mb-4">Telemedicine Consultation</h1>
            <p class="lead text-muted mb-4">Consult with top-rated doctors from the comfort of your home. Video calls,
                chat, and digital prescriptions all in one place.</p>
            <div class="d-flex flex-wrap gap-4 mb-5">
                <a href="doctors.php" class="text-decoration-none text-center telemed-action">
                    <div class="bg-primary bg-opacity-10 p-4 rounded-circle mb-3 mx-auto"
                        style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="fas fa-video text-primary fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Video Call</h6>
                </a>
                <a href="doctors.php" class="text-decoration-none text-center telemed-action">
                    <div class="bg-success bg-opacity-10 p-4 rounded-circle mb-3 mx-auto"
                        style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="fas fa-comments text-success fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark">Chat</h6>
                </a>
                <a href="doctors.php" class="text-decoration-none text-center telemed-action">
                    <div class="bg-warning bg-opacity-10 p-4 rounded-circle mb-3 mx-auto"
                        style="width: 80px; height: 80px; display: flex; align-items: center; justify-content: center; transition: all 0.3s ease;">
                        <i class="fas fa-file-prescription text-warning fs-3"></i>
                    </div>
                    <h6 class="fw-bold text-dark">E-Prescription</h6>
                </a>
            </div>
            <a href="doctors.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow">Find a Doctor Now</a>
        </div>
        <div class="col-lg-6">
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                <img src="https://images.unsplash.com/photo-1576091160550-2173dba999ef?w=800" class="card-img-top"
                    alt="Telemedicine">
                <div class="card-body p-4 bg-light">
                    <h5 class="fw-bold mb-3">How it works?</h5>
                    <ol class="list-group list-group-numbered list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent border-0 px-0 mb-2">Select a doctor and choose
                            <b>Virtual Visit</b>.
                        </li>
                        <li class="list-group-item bg-transparent border-0 px-0 mb-2">Our team will confirm your slot
                            within minutes.</li>
                        <li class="list-group-item bg-transparent border-0 px-0 mb-2">Join the call directly from your
                            <b>Medical Records</b> dashboard.
                        </li>
                        <li class="list-group-item bg-transparent border-0 px-0">Receive your digital prescription
                            immediately after the call.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .telemed-action:hover > div:first-child {
        transform: scale(1.15) translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
</style>

<?php include '../includes/footer.php'; ?>