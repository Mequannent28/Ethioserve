<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Handle search
$search = sanitize($_GET['search'] ?? '');
$searchCondition = '';
$params = [];

if (!empty($search)) {
    $searchCondition = "AND (h.name LIKE ? OR h.location LIKE ? OR h.cuisine_type LIKE ? OR m.name LIKE ? OR m.description LIKE ?)";
    $searchParam = "%$search%";
    $params = [$searchParam, $searchParam, $searchParam, $searchParam, $searchParam];
}

// Fetch approved hotels from database with potential menu item search
$sql = "SELECT DISTINCT h.* FROM hotels h 
        LEFT JOIN menu_items m ON h.id = m.hotel_id 
        WHERE h.status = 'approved' $searchCondition 
        ORDER BY h.rating DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hotels = $stmt->fetchAll();

// Get cart count for display
$cart_count = getCartCount();

// ============================================================
// SERVICE COUNT BADGES - Real counts from database
// ============================================================
$count_taxis = 0;
$count_restaurants = 0;
$count_buses = 0;
$count_hotels = 0;
$count_brokers = 0;
$count_education = 0;
$count_listings = 0;

try {
    $count_taxis = $pdo->query("SELECT COUNT(*) FROM taxi_companies WHERE status='approved'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_restaurants = $pdo->query("SELECT COUNT(*) FROM restaurants")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_buses = $pdo->query("SELECT COUNT(*) FROM buses WHERE is_active=1")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_hotels = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status='approved'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_brokers = $pdo->query("SELECT COUNT(*) FROM brokers")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_education = $pdo->query("SELECT COUNT(DISTINCT grade) FROM education_resources WHERE status='active'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_listings = $pdo->query("SELECT COUNT(*) FROM listings WHERE status='approved'")->fetchColumn();
} catch (Exception $e) {
}

include('../includes/header.php');
?>

<main class="container py-4">
    <?php echo displayFlashMessage(); ?>

    <!-- Hero Banner Slider -->
    <div id="heroCarousel" class="carousel slide hero-slider mb-5" data-bs-ride="carousel">
        <div class="carousel-indicators">
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="0" class="active"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="1"></button>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="2"></button>
        </div>
        <div class="carousel-inner rounded-5 shadow">
            <div class="carousel-item active">
                <div class="p-5 bg-primary-green text-white text-center position-relative overflow-hidden"
                    style="min-height: 350px; background-image: linear-gradient(rgba(27, 94, 32, 0.75), rgba(27, 94, 32, 0.85)), url('https://images.unsplash.com/photo-1541014741259-df529411b96a?auto=format&fit=crop&w=1200&q=80'); background-size: cover; background-position: center;">
                    <div class="position-relative z-1 py-4">
                        <span class="badge bg-gold text-dark rounded-pill px-3 py-2 mb-3 fw-bold">PROMO OF THE
                            DAY</span>
                        <h1 class="display-5 fw-bold mb-3">Delicious Ethiopian Food</h1>
                        <p class="lead mb-4 mx-auto" style="max-width: 600px;">Experience the authentic taste of
                            Ethiopia delivered to your doorstep in minutes.</p>
                        <a href="#hotels" class="btn btn-gold btn-lg px-5 rounded-pill fw-bold shadow">Order Now</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-5 bg-warning text-dark text-center position-relative overflow-hidden"
                    style="min-height: 350px; background-image: linear-gradient(rgba(249, 168, 37, 0.7), rgba(249, 168, 37, 0.75)), url('https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=1200&q=80'); background-size: cover; background-position: center;">
                    <div class="position-relative z-1 py-4">
                        <span class="badge bg-primary-green text-white rounded-pill px-3 py-2 mb-3 fw-bold">BEST
                            RATES</span>
                        <h1 class="display-5 fw-bold mb-3 text-dark">Book Your Perfect Stay</h1>
                        <p class="lead mb-4 mx-auto text-dark" style="max-width: 600px;">Rent Halls, Rooms, or Book
                            Tables at the finest hotels with exclusive rates.</p>
                        <a href="booking.php"
                            class="btn btn-primary-green btn-lg px-5 rounded-pill fw-bold shadow">Explore Booking</a>
                    </div>
                </div>
            </div>
            <div class="carousel-item">
                <div class="p-5 bg-danger text-white text-center position-relative overflow-hidden"
                    style="min-height: 350px; background-image: linear-gradient(rgba(198, 40, 40, 0.7), rgba(198, 40, 40, 0.8)), url('https://images.unsplash.com/photo-1450101499163-c8848c66ca85?auto=format&fit=crop&w=1200&q=80'); background-size: cover; background-position: center;">
                    <div class="position-relative z-1 py-4">
                        <span class="badge bg-white text-danger rounded-pill px-3 py-2 mb-3 fw-bold">EARN
                            COMMISSION</span>
                        <h1 class="display-5 fw-bold mb-3">Professional Brokerage</h1>
                        <p class="lead mb-4 mx-auto" style="max-width: 600px;">Join our elite network of brokers and
                            connect users with premium services.</p>
                        <a href="../register.php?role=broker"
                            class="btn btn-gold btn-lg px-5 rounded-pill fw-bold shadow">Join as Broker</a>
                    </div>
                </div>
            </div>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon p-3 bg-dark bg-opacity-25 rounded-circle"></span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon p-3 bg-dark bg-opacity-25 rounded-circle"></span>
        </button>
    </div>

    <!-- Super App Service Grid -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="fw-bold mb-0">Our Services</h4>
            <span class="text-muted small">Everything you need in one app</span>
        </div>
        <div class="service-grid">
            <!-- Taxi -->
            <a href="taxi.php" class="service-card shadow-sm position-relative">
                <?php if ($count_taxis > 0): ?>
                    <span class="count-badge" style="background:#1B5E20;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_taxis; ?> Available</span>
                <?php endif; ?>
                <div class="service-icon bg-taxi">
                    <i class="fas fa-taxi"></i>
                </div>
                <p class="service-label">Taxi</p>
            </a>

            <!-- Food Delivery -->
            <a href="restaurants.php" class="service-card shadow-sm position-relative">
                <?php if ($count_restaurants > 0): ?>
                    <span class="count-badge" style="background:#E65100;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_restaurants; ?> Open</span>
                <?php endif; ?>
                <div class="service-icon bg-food">
                    <i class="fas fa-utensils"></i>
                </div>
                <p class="service-label">Restaurants</p>
            </a>

            <!-- Train & Bus -->
            <a href="buses.php" class="service-card shadow-sm position-relative">
                <?php if ($count_buses > 0): ?>
                    <span class="count-badge" style="background:#00897B;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_buses; ?> Active</span>
                <?php endif; ?>
                <div class="service-icon bg-bus">
                    <i class="fas fa-bus"></i>
                </div>
                <p class="service-label">Bus</p>
            </a>

            <!-- Flights -->
            <a href="flights.php" class="service-card shadow-sm position-relative">
                <span class="count-badge" style="background:#6A1B9A;"><i class="fas fa-plane me-1"
                        style="font-size:0.5rem;"></i> Book Now</span>
                <div class="service-icon bg-flight">
                    <i class="fas fa-plane"></i>
                </div>
                <p class="service-label">Flights</p>
            </a>

            <!-- Movies -->
            <a href="coming_soon.php?service=Movies" class="service-card shadow-sm position-relative">
                <span class="count-badge" style="background:#AD1457;"><i class="fas fa-clock me-1"
                        style="font-size:0.5rem;"></i> Soon</span>
                <div class="service-icon bg-movies">
                    <i class="fas fa-film"></i>
                </div>
                <p class="service-label">Movies</p>
            </a>

            <!-- Coupons -->
            <a href="coming_soon.php?service=Coupons" class="service-card shadow-sm position-relative">
                <span class="count-badge" style="background:#F57F17;"><i class="fas fa-clock me-1"
                        style="font-size:0.5rem;"></i> Soon</span>
                <div class="service-icon bg-coupons">
                    <i class="fas fa-ticket-alt"></i>
                </div>
                <p class="service-label">Coupons</p>
            </a>

            <!-- Hotels -->
            <a href="booking.php" class="service-card shadow-sm position-relative">
                <?php if ($count_hotels > 0): ?>
                    <span class="count-badge" style="background:#0D47A1;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_hotels; ?> Listed</span>
                <?php endif; ?>
                <div class="service-icon bg-hotels">
                    <i class="fas fa-hotel"></i>
                </div>
                <p class="service-label">Hotels</p>
            </a>

            <!-- Experiences -->
            <a href="coming_soon.php?service=Experiences" class="service-card shadow-sm position-relative">
                <span class="count-badge" style="background:#C62828;"><i class="fas fa-clock me-1"
                        style="font-size:0.5rem;"></i> Soon</span>
                <div class="service-icon bg-experiences">
                    <i class="fas fa-skating"></i>
                </div>
                <p class="service-label">Experiences</p>
            </a>

            <!-- Home Services -->
            <a href="listings.php?type=home_service" class="service-card shadow-sm position-relative">
                <?php if ($count_listings > 0): ?>
                    <span class="count-badge" style="background:#4527A0;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_listings; ?> Services</span>
                <?php endif; ?>
                <div class="service-icon bg-home-services">
                    <i class="fas fa-wrench"></i>
                </div>
                <p class="service-label">Home</p>
            </a>

            <!-- Transport -->
            <a href="buses.php" class="service-card shadow-sm position-relative">
                <?php if ($count_buses > 0): ?>
                    <span class="count-badge" style="background:#00695C;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_buses; ?> Routes</span>
                <?php endif; ?>
                <div class="service-icon bg-transport">
                    <i class="fas fa-bus-alt"></i>
                </div>
                <p class="service-label">Transport</p>
            </a>

            <!-- House Rent -->
            <a href="rent.php" class="service-card shadow-sm position-relative">
                <?php if ($count_listings > 0): ?>
                    <span class="count-badge" style="background:#BF360C;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_listings; ?> Homes</span>
                <?php endif; ?>
                <div class="service-icon bg-rent">
                    <i class="fas fa-home"></i>
                </div>
                <p class="service-label">Rent</p>
            </a>

            <!-- Broker Hub -->
            <a href="../broker/dashboard.php" class="service-card shadow-sm position-relative">
                <?php if ($count_brokers > 0): ?>
                    <span class="count-badge" style="background:#F9A825;color:#333;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_brokers; ?> Brokers</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #F9A825; color: white;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <p class="service-label">Broker</p>
            </a>

            <!-- Education -->
            <a href="education.php" class="service-card shadow-sm position-relative">
                <?php if ($count_education > 0): ?>
                    <span class="count-badge" style="background:#1565C0;"><i class="fas fa-graduation-cap me-1"
                            style="font-size:0.5rem;"></i> <?php echo $count_education; ?> Grades</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#1565C0;">NEW</span>
                <?php endif; ?>
                <div class="service-icon bg-education">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <p class="service-label">Education</p>
            </a>
        </div>
    </section>

    <!-- Category Section -->
    <section class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">Explore Categories</h3>
        </div>
        <div class="row g-4 text-center">
            <div class="col-6 col-md-3">
                <a href="?search=breakfast" class="text-decoration-none">
                    <div class="category-card shadow-sm border p-4 rounded-3 hover-lift">
                        <i class="fas fa-utensils text-primary-green mb-3 fs-1"></i>
                        <h6 class="fw-bold mb-0">Breakfast</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?search=lunch" class="text-decoration-none">
                    <div class="category-card shadow-sm border p-4 rounded-3 hover-lift">
                        <i class="fas fa-hamburger text-primary-green mb-3 fs-1"></i>
                        <h6 class="fw-bold mb-0">Lunch</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?search=dinner" class="text-decoration-none">
                    <div class="category-card shadow-sm border p-4 rounded-3 hover-lift">
                        <i class="fas fa-drumstick-bite text-primary-green mb-3 fs-1"></i>
                        <h6 class="fw-bold mb-0">Dinner</h6>
                    </div>
                </a>
            </div>
            <div class="col-6 col-md-3">
                <a href="?search=drinks" class="text-decoration-none">
                    <div class="category-card shadow-sm border p-4 rounded-3 hover-lift">
                        <i class="fas fa-cocktail text-primary-green mb-3 fs-1"></i>
                        <h6 class="fw-bold mb-0">Drinks</h6>
                    </div>
                </a>
            </div>
        </div>
    </section>

    <!-- Hotel Grid Section -->
    <section id="hotels" class="mb-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="fw-bold">
                <?php if (!empty($search)): ?>
                    Search Results for "<?php echo htmlspecialchars($search); ?>"
                <?php else: ?>
                    Top Hotels & Restaurants
                <?php endif; ?>
            </h3>
            <?php if (!empty($search)): ?>
                <a href="index.php" class="btn btn-outline-secondary rounded-pill">
                    <i class="fas fa-times me-2"></i>Clear Search
                </a>
            <?php endif; ?>
        </div>

        <?php if (empty($hotels)): ?>
            <div class="card border-0 shadow-sm p-5 text-center">
                <i class="fas fa-search text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">No restaurants found</h4>
                <p class="text-muted">Try a different search term or browse all restaurants</p>
                <a href="index.php" class="btn btn-primary-green rounded-pill px-4">View All Restaurants</a>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($hotels as $hotel): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="card h-100 hover-lift border-0 shadow-sm">
                            <div class="position-absolute top-0 end-0 m-3 z-1">
                                <span class="badge bg-white text-dark shadow-sm rounded-pill px-3 py-2">
                                    <i class="fas fa-star text-warning me-1"></i>
                                    <?php echo number_format($hotel['rating'], 1); ?>
                                </span>
                            </div>
                            <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80'); ?>"
                                class="card-img-top" alt="<?php echo htmlspecialchars($hotel['name']); ?>"
                                style="height: 220px; object-fit: cover;">
                            <div class="card-body p-4">
                                <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($hotel['name']); ?></h5>
                                <p class="text-muted small mb-2">
                                    <i
                                        class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo htmlspecialchars($hotel['location']); ?>
                                </p>
                                <?php if ($hotel['cuisine_type']): ?>
                                    <p class="text-muted small mb-2">
                                        <i
                                            class="fas fa-utensils me-2 text-primary-green"></i><?php echo htmlspecialchars($hotel['cuisine_type']); ?>
                                    </p>
                                <?php endif; ?>
                                <div class="d-flex align-items-center gap-3 mb-3">
                                    <span class="text-muted small">
                                        <i
                                            class="fas fa-clock me-1"></i><?php echo htmlspecialchars($hotel['delivery_time'] ?? '30-45 min'); ?>
                                    </span>
                                    <span class="text-muted small">
                                        <i class="fas fa-shopping-bag me-1"></i>Min:
                                        <?php echo number_format($hotel['min_order']); ?> ETB
                                    </span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-auto">
                                    <span class="text-primary-green fw-bold">Min Order:
                                        <?php echo number_format($hotel['min_order']); ?> ETB</span>
                                    <a href="menu.php?id=<?php echo $hotel['id']; ?>"
                                        class="btn btn-primary-green rounded-pill px-4">
                                        View Menu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <!-- How It Works Section -->
    <section class="mb-5 py-5 bg-light rounded-4">
        <div class="text-center mb-5">
            <h3 class="fw-bold">How It Works</h3>
            <p class="text-muted">Order your favorite food in 3 easy steps</p>
        </div>
        <div class="row g-4 text-center">
            <div class="col-md-4">
                <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                    <div class="bg-primary-green text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 60px; height: 60px;">
                        <i class="fas fa-search fs-4"></i>
                    </div>
                    <h5 class="fw-bold">Choose Restaurant</h5>
                    <p class="text-muted mb-0">Browse through our curated list of premium restaurants and hotels</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                    <div class="bg-warning text-dark rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 60px; height: 60px;">
                        <i class="fas fa-utensils fs-4"></i>
                    </div>
                    <h5 class="fw-bold">Select Your Meal</h5>
                    <p class="text-muted mb-0">Pick from a variety of delicious dishes and add to your cart</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="bg-white rounded-4 p-4 shadow-sm h-100">
                    <div class="bg-danger text-white rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
                        style="width: 60px; height: 60px;">
                        <i class="fas fa-motorcycle fs-4"></i>
                    </div>
                    <h5 class="fw-bold">Fast Delivery</h5>
                    <p class="text-muted mb-0">Get your food delivered hot and fresh to your doorstep</p>
                </div>
            </div>
        </div>
    </section>
</main>

<!-- Floating Cart Button -->
<?php if ($cart_count > 0): ?>
    <a href="cart.php"
        class="position-fixed bottom-0 end-0 m-4 btn btn-primary-green btn-lg rounded-pill shadow-lg p-3 z-3 d-flex align-items-center gap-3">
        <div class="bg-white text-primary-green rounded-circle px-2 py-1 small fw-bold"><?php echo $cart_count; ?></div>
        <span>View Cart</span>
        <i class="fas fa-shopping-basket"></i>
    </a>
<?php endif; ?>

<!-- Float Scripts -->
<style>
    html {
        scroll-behavior: smooth;
    }

    .category-card {
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .category-card:hover {
        background-color: #1B5E20 !important;
        border-color: #1B5E20 !important;
    }

    .category-card:hover i,
    .category-card:hover h6 {
        color: #ffffff !important;
    }

    /* Service count badges */
    .count-badge {
        position: absolute;
        top: -8px;
        left: 50%;
        transform: translateX(-50%);
        background: #1B5E20;
        color: #fff;
        font-size: 0.62rem;
        font-weight: 700;
        padding: 3px 10px;
        border-radius: 50px;
        white-space: nowrap;
        z-index: 5;
        display: inline-flex;
        align-items: center;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        animation: badgePulse 3s ease-in-out infinite;
    }

    @keyframes badgePulse {

        0%,
        100% {
            transform: translateX(-50%) scale(1);
        }

        50% {
            transform: translateX(-50%) scale(1.05);
        }
    }
</style>

<script>
    // Handle smooth scrolling for anchors
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
</script>

<?php include('../includes/footer.php'); ?>