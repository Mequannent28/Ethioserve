<?php
include('../includes/header.php');
$service = isset($_GET['service']) ? htmlspecialchars($_GET['service']) : 'Service';
?>

<main class="container py-5 text-center">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="p-5 bg-white shadow-sm rounded-4">
                <div class="mb-4">
                    <span class="badge bg-warning text-dark rounded-pill px-3 py-2">
                        <i class="fas fa-tools me-2"></i> Under Development
                    </span>
                </div>
                <h1 class="fw-bold mb-3">
                    <?php echo $service; ?> is Coming Soon
                </h1>
                <p class="text-muted lead mb-5">We're working hard to bring you the best
                    <?php echo strtolower($service); ?> experience in Ethiopia. Stay tuned!
                </p>

                <div class="d-flex flex-column gap-3">
                    <a href="index.php" class="btn btn-primary-green btn-lg rounded-pill">
                        <i class="fas fa-home me-2"></i> Back to Home
                    </a>
                    <p class="text-muted small">Need help? <a href="#"
                            class="text-primary-green text-decoration-none">Contact Support</a></p>
                </div>
            </div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>