<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch categories
$stmt = $pdo->query("SELECT * FROM home_service_categories ORDER BY name ASC");
$categories = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="home-services-hero py-5 mb-5 text-white"
    style="background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);">
    <div class="container text-center py-5">
        <h1 class="display-4 fw-bold mb-3">Professional Home Services</h1>
        <p class="lead mb-4">Reliable help for your home at your fingertips. From plumbing to painting, we do it all.
        </p>

        <div class="search-bar-wrapper mx-auto" style="max-width: 600px;">
            <div class="input-group input-group-lg shadow-sm">
                <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="serviceSearch" class="form-control border-0"
                    placeholder="What service do you need today?">
            </div>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-0">Browse by Category</h2>
            <p class="text-muted">Choose a service to see options and get instant estimates</p>
        </div>
        <a href="my_home_bookings.php" class="btn btn-outline-primary rounded-pill px-4">
            <i class="fas fa-calendar-alt me-2"></i>My Bookings
        </a>
    </div>

    <div class="row g-4" id="categoryGrid">
        <?php foreach ($categories as $cat): ?>
            <div class="col-md-6 col-lg-4 category-card-wrapper">
                <a href="home_service_details.php?id=<?php echo $cat['id']; ?>" class="text-decoration-none">
                    <div
                        class="card h-100 border-0 shadow-sm rounded-4 p-4 text-center hover-lift transition-all hov-green">
                        <div class="icon-circle mb-4 mx-auto"
                            style="width: 80px; height: 80px; background: #FFF3E0; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                            <i class="<?php echo $cat['icon']; ?> text-warning fs-1"></i>
                        </div>
                        <h4 class="fw-bold text-dark mb-2">
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </h4>
                        <p class="text-muted small mb-0">
                            <?php echo htmlspecialchars($cat['description']); ?>
                        </p>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<div class="bg-light py-5">
    <div class="container mt-4">
        <h3 class="fw-bold text-center mb-5">Why Choose Our Home Services?</h3>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <div class="mb-3"><i class="fas fa-user-check text-success fs-1"></i></div>
                <h5 class="fw-bold">Verified Pros</h5>
                <p class="text-muted">Every service provider is thoroughly background-checked and vetted.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3"><i class="fas fa-tag text-success fs-1"></i></div>
                <h5 class="fw-bold">Fair Pricing</h5>
                <p class="text-muted">Upfront pricing with no hidden charges. Pay only for the work done.</p>
            </div>
            <div class="col-md-4 text-center">
                <div class="mb-3"><i class="fas fa-shield-alt text-success fs-1"></i></div>
                <h5 class="fw-bold">Service Guarantee</h5>
                <p class="text-muted">Not satisfied with the result? We'll make it right at no extra cost.</p>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-lift:hover {
        transform: translateY(-10px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
    }

    .hov-green:hover .icon-circle {
        background: #E8F5E9 !important;
    }

    .hov-green:hover i {
        color: #4CAF50 !important;
    }
</style>

<script>
    document.getElementById('serviceSearch').addEventListener('input', function () {
        const query = this.value.toLowerCase();
        document.querySelectorAll('.category-card-wrapper').forEach(card => {
            const text = card.innerText.toLowerCase();
            card.style.display = text.includes(query) ? 'block' : 'none';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>