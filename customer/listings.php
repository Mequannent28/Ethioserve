<?php
include('../includes/header.php');
require_once '../includes/db.php';

$type = isset($_GET['type']) ? $_GET['type'] : 'house_rent';
$titles = [
    'house_rent' => 'House Rent & Listings',
    'car_rent' => 'Car Rental Services',
    'bus_ticket' => 'Bus Tickets & Transport',
    'home_service' => 'Home & Maintenance Services'
];

$title = $titles[$type] ?? 'Services';

// Fetch listings by type
$stmt = $pdo->prepare("SELECT * FROM listings WHERE type = ? AND status = 'available' ORDER BY created_at DESC");
$stmt->execute([$type]);
$listings = $stmt->fetchAll();
?>

<main class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="fw-bold mb-1"><?php echo $title; ?></h1>
            <p class="text-muted">Explore the best <?php echo str_replace('_', ' ', $type); ?> deals in Addis Ababa.</p>
        </div>
        <div class="d-flex gap-2">
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'broker' || $_SESSION['role'] === 'admin')): ?>
                <a href="../broker/post_listing.php" class="btn btn-primary-green rounded-pill px-4">
                    <i class="fas fa-plus me-2"></i> Post Listing
                </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-outline-primary-green rounded-pill px-4">
                <i class="fas fa-arrow-left me-2"></i> Back
            </a>
        </div>
    </div>

    <!-- Filter Pills -->
    <div class="d-flex gap-2 overflow-auto pb-3 mb-4 no-scrollbar">
        <button class="btn btn-primary-green rounded-pill px-4">All</button>
        <button class="btn btn-white shadow-sm border rounded-pill px-4">Latest</button>
        <button class="btn btn-white shadow-sm border rounded-pill px-4">Popular</button>
        <button class="btn btn-white shadow-sm border rounded-pill px-4">Price: Low to High</button>
    </div>

    <div class="row g-4">
        <?php if (empty($listings)): ?>
            <div class="col-12 text-center py-5">
                <i class="fas fa-search-minus text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">No
                    <?php echo str_replace('_', ' ', $type); ?> listings found
                </h4>
                <p>Check back later or try a different category.</p>
            </div>
        <?php else: ?>
            <?php foreach ($listings as $item): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 hover-lift">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=800&q=80'); ?>"
                                class="w-100" style="height: 200px; object-fit: cover;" alt="Listing">
                            <div class="position-absolute bottom-0 start-0 m-3">
                                <span class="badge bg-white text-primary-green shadow-sm rounded-pill px-3 py-2 fw-bold">
                                    <?php echo number_format($item['price']); ?> ETB /
                                    <?php echo $type === 'bus_ticket' ? 'Ticket' : ($type === 'car_rent' ? 'Day' : 'Month'); ?>
                                </span>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-2">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h5>
                            <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                <?php echo htmlspecialchars($item['location']); ?>
                            </p>
                            <p class="text-muted small mb-4 line-clamp-2">
                                <?php echo htmlspecialchars($item['description']); ?>
                            </p>

                            <div class="d-flex gap-2">
                                <button class="btn btn-primary-green rounded-pill flex-grow-1 py-2">
                                    <i class="fas fa-phone-alt me-2"></i> Contact Seller
                                </button>
                                <button class="btn btn-outline-secondary rounded-pill p-2" style="width: 45px; height: 45px;">
                                    <i class="far fa-heart"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</main>

<style>
    .no-scrollbar::-webkit-scrollbar {
        display: none;
    }

    .no-scrollbar {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }

    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include('../includes/footer.php'); ?>