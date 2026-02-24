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
$hotels = [];
try {
    $sql = "SELECT DISTINCT h.* FROM hotels h 
            LEFT JOIN menu_items m ON h.id = m.hotel_id 
            WHERE h.status = 'approved' $searchCondition 
            ORDER BY h.rating DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $hotels = $stmt->fetchAll();
} catch (Exception $e) {
    // Table may not exist yet
}

// Initialize other results
$restaurants = [];
$taxis = [];
$buses = [];
$health_results = [];
$dating_results = [];
$jobs = [];
$real_estate = [];
$education_resources = [];
$home_services = [];
$exchange_materials = [];
$community_news = [];
$community_events = [];
$marketplace = [];
$brokers = [];
$flights = [];
$courses = [];
$users = [];
$bus_routes = [];

if (!empty($search)) {
    $searchParam = "%$search%";

    // Search Restaurants
    try {
        $resSql = "SELECT * FROM restaurants WHERE status = 'approved' AND (name LIKE ? OR address LIKE ? OR cuisine_type LIKE ?)";
        $resStmt = $pdo->prepare($resSql);
        $resStmt->execute([$searchParam, $searchParam, $searchParam]);
        $restaurants = $resStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Taxis
    try {
        $taxiSql = "SELECT * FROM taxi_companies WHERE status = 'approved' AND (company_name LIKE ? OR address LIKE ? OR description LIKE ?)";
        $taxiStmt = $pdo->prepare($taxiSql);
        $taxiStmt->execute([$searchParam, $searchParam, $searchParam]);
        $taxis = $taxiStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Buses
    try {
        $busStmt = $pdo->prepare("SELECT * FROM transport_companies WHERE status = 'approved' AND (company_name LIKE ? OR address LIKE ? OR description LIKE ?)");
        $busStmt->execute([$searchParam, $searchParam, $searchParam]);
        $buses = $busStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Bus Routes
    try {
        $routeStmt = $pdo->prepare("SELECT r.*, tc.company_name FROM routes r JOIN transport_companies tc ON r.company_id = tc.id WHERE r.is_active = 1 AND (r.origin LIKE ? OR r.destination LIKE ?)");
        $routeStmt->execute([$searchParam, $searchParam]);
        $bus_routes = $routeStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Health
    try {
        $healthStmt = $pdo->prepare("SELECT * FROM health_providers WHERE is_available = 1 AND (name LIKE ? OR location LIKE ? OR bio LIKE ? OR specialization LIKE ?)");
        $healthStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
        $health_results = $healthStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Dating
    try {
        $datingStmt = $pdo->prepare("SELECT p.*, u.full_name FROM dating_profiles p JOIN users u ON p.user_id = u.id WHERE p.is_active = 1 AND (u.full_name LIKE ? OR p.bio LIKE ? OR p.location_name LIKE ?)");
        $datingStmt->execute([$searchParam, $searchParam, $searchParam]);
        $dating_results = $datingStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Jobs
    try {
        $jobStmt = $pdo->prepare("SELECT * FROM job_listings WHERE status = 'active' AND (title LIKE ? OR company LIKE ? OR description LIKE ? OR location LIKE ? OR skills_required LIKE ?)");
        $jobStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam, $searchParam]);
        $jobs = $jobStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Real Estate
    try {
        $reStmt = $pdo->prepare("SELECT * FROM real_estate_properties WHERE status = 'available' AND (title LIKE ? OR location LIKE ? OR description LIKE ? OR property_type LIKE ?)");
        $reStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
        $real_estate = $reStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Education Resources
    try {
        $eduStmt = $pdo->prepare("SELECT * FROM education_resources WHERE status = 'active' AND (title LIKE ? OR subject LIKE ? OR description LIKE ? OR grade LIKE ?)");
        $eduStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
        $education_resources = $eduStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Home Services
    try {
        $homeStmt = $pdo->prepare("SELECT * FROM home_service_categories WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?)");
        $homeStmt->execute([$searchParam, $searchParam]);
        $home_services = $homeStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Exchange Materials
    try {
        $exStmt = $pdo->prepare("SELECT * FROM exchange_materials WHERE status = 'available' AND (title LIKE ? OR subject LIKE ? OR description LIKE ?)");
        $exStmt->execute([$searchParam, $searchParam, $searchParam]);
        $exchange_materials = $exStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Community News
    try {
        $newsStmt = $pdo->prepare("SELECT * FROM comm_news WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)");
        $newsStmt->execute([$searchParam, $searchParam]);
        $community_news = $newsStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Community Events
    try {
        $evtStmt = $pdo->prepare("SELECT * FROM comm_events WHERE status = 'published' AND (title LIKE ? OR location LIKE ? OR description LIKE ?)");
        $evtStmt->execute([$searchParam, $searchParam, $searchParam]);
        $community_events = $evtStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Marketplace
    try {
        $mktStmt = $pdo->prepare("SELECT * FROM comm_marketplace WHERE status = 'available' AND (title LIKE ? OR description LIKE ? OR category LIKE ?)");
        $mktStmt->execute([$searchParam, $searchParam, $searchParam]);
        $marketplace = $mktStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Brokers
    try {
        $brokerStmt = $pdo->prepare("SELECT b.*, u.full_name, u.email FROM brokers b JOIN users u ON b.user_id = u.id WHERE u.status = 'active' AND (b.business_name LIKE ? OR b.location LIKE ? OR b.description LIKE ?)");
        $brokerStmt->execute([$searchParam, $searchParam, $searchParam]);
        $brokers = $brokerStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Flights
    try {
        $flightStmt = $pdo->prepare("SELECT * FROM flights WHERE status = 'active' AND (airline LIKE ? OR flight_number LIKE ? OR departure_city LIKE ? OR arrival_city LIKE ?)");
        $flightStmt->execute([$searchParam, $searchParam, $searchParam, $searchParam]);
        $flights = $flightStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Courses
    try {
        $courseStmt = $pdo->prepare("SELECT * FROM courses WHERE status = 'published' AND (title LIKE ? OR description LIKE ? OR instructor LIKE ?)");
        $courseStmt->execute([$searchParam, $searchParam, $searchParam]);
        $courses = $courseStmt->fetchAll();
    } catch (Exception $e) {
    }

    // Search Users (by name)
    try {
        $userStmt = $pdo->prepare("SELECT id, full_name, email, role FROM users WHERE status = 'active' AND (full_name LIKE ? OR email LIKE ?) LIMIT 10");
        $userStmt->execute([$searchParam, $searchParam]);
        $users = $userStmt->fetchAll();
    } catch (Exception $e) {
    }
}

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
$count_real_estate = 0;
$count_exchange = 0;
$count_home_services = 0;
$count_health = 0;
$count_dating = 0;
$count_community = 0;
$count_jobs = 0;

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
try {
    $count_real_estate = $pdo->query("SELECT COUNT(*) FROM real_estate_properties WHERE status='available'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_exchange = $pdo->query("SELECT COUNT(*) FROM exchange_materials WHERE status='available'")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_home_services = $pdo->query("SELECT COUNT(*) FROM home_service_categories")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_health = $pdo->query("SELECT COUNT(*) FROM health_providers WHERE is_available=1")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_dating = $pdo->query("SELECT COUNT(*) FROM dating_profiles")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_community = $pdo->query("SELECT (SELECT COUNT(*) FROM comm_news) + (SELECT COUNT(*) FROM comm_events) + (SELECT COUNT(*) FROM comm_marketplace WHERE status='available') as total")->fetchColumn();
} catch (Exception $e) {
}
try {
    $count_jobs = $pdo->query("SELECT COUNT(*) FROM job_listings WHERE status='active'")->fetchColumn();
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

            <!-- Real Estate -->
            <a href="../realestate/index.php" class="service-card shadow-sm position-relative">
                <?php if ($count_real_estate > 0): ?>
                    <span class="count-badge" style="background:#5D4037;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_real_estate; ?> Homes</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #5D4037; color: white;">
                    <i class="fas fa-building"></i>
                </div>
                <p class="service-label">Real Estate</p>
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

            <!-- Jobs & Freelance -->
            <a href="jobs.php" class="service-card shadow-sm position-relative">
                <?php if ($count_jobs > 0): ?>
                    <span class="count-badge" style="background:#1565C0;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_jobs; ?> Jobs</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#1565C0;"><i class="fas fa-briefcase me-1"
                            style="font-size:0.4rem;"></i>NEW</span>
                <?php endif; ?>
                <div class="service-icon" style="background:linear-gradient(135deg,#1565C0,#0d47a1);color:white;">
                    <i class="fas fa-briefcase"></i>
                </div>
                <p class="service-label">Jobs</p>
            </a>

            <!-- Dating Services -->
            <a href="dating.php" class="service-card shadow-sm position-relative">
                <?php if ($count_dating > 0): ?>
                    <span class="count-badge" style="background:#E91E63;"><i class="fas fa-heart me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_dating; ?> Active</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#E91E63;"><i class="fas fa-heart me-1"
                            style="font-size:0.4rem;"></i> Find Love</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #E91E63; color: white;">
                    <i class="fas fa-heart"></i>
                </div>
                <p class="service-label">Dating</p>
            </a>

            <!-- Community Hub -->
            <a href="community.php" class="service-card shadow-sm position-relative">
                <?php if ($count_community > 0): ?>
                    <span class="count-badge" style="background:#0288D1;"><i class="fas fa-bullhorn me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_community; ?> Updates</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#0288D1;"><i class="fas fa-users me-1"
                            style="font-size:0.4rem;"></i> My Comm</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #0288D1; color: white;">
                    <i class="fas fa-users"></i>
                </div>
                <p class="service-label">Community</p>
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

            <!-- Health Services -->
            <a href="health_services.php" class="service-card shadow-sm position-relative">
                <?php if ($count_health > 0): ?>
                    <span class="count-badge" style="background:#2E7D32;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_health; ?> Pros</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#2E7D32;"><i class="fas fa-heartbeat me-1"
                            style="font-size:0.4rem;"></i> Healthcare</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #2E7D32; color: white;">
                    <i class="fas fa-heartbeat"></i>
                </div>
                <p class="service-label">Health</p>
            </a>

            <!-- Home Services -->
            <a href="home_services.php" class="service-card shadow-sm position-relative">
                <?php if ($count_home_services > 0): ?>
                    <span class="count-badge" style="background:#4527A0;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_home_services; ?> Categories</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#4527A0;"><i class="fas fa-wrench me-1"
                            style="font-size:0.4rem;"></i> Pro Help</span>
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

            <!-- Exchange Material -->
            <a href="exchange_material.php" class="service-card shadow-sm position-relative">
                <?php if ($count_exchange > 0): ?>
                    <span class="count-badge" style="background:#5C6BC0;"><i class="fas fa-circle me-1"
                            style="font-size:0.4rem;"></i> <?php echo $count_exchange; ?> Items</span>
                <?php else: ?>
                    <span class="count-badge" style="background:#5C6BC0;"><i class="fas fa-sync-alt me-1"
                            style="font-size:0.5rem;"></i> Buy/Sell</span>
                <?php endif; ?>
                <div class="service-icon" style="background-color: #5C6BC0; color: white;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <p class="service-label">Exchange</p>
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

        <?php if (!empty($search) && $has_results): ?>
            <!-- Search Results Summary -->
            <div class="alert alert-info mb-4">
                <div class="d-flex flex-wrap gap-3 align-items-center">
                    <span class="fw-bold"><i class="fas fa-search me-2"></i>Found:</span>
                    <?php if (count($hotels) > 0): ?><span class="badge bg-primary"><?php echo count($hotels); ?>
                            Hotels</span><?php endif; ?>
                    <?php if (count($restaurants) > 0): ?><span
                            class="badge bg-warning text-dark"><?php echo count($restaurants); ?>
                            Restaurants</span><?php endif; ?>
                    <?php if (count($taxis) > 0): ?><span class="badge bg-warning text-dark"><?php echo count($taxis); ?>
                            Taxis</span><?php endif; ?>
                    <?php if (count($buses) > 0): ?><span class="badge bg-success"><?php echo count($buses); ?>
                            Buses</span><?php endif; ?>
                    <?php if (count($bus_routes) > 0): ?><span class="badge bg-info"><?php echo count($bus_routes); ?>
                            Routes</span><?php endif; ?>
                    <?php if (count($health_results) > 0): ?><span
                            class="badge bg-success"><?php echo count($health_results); ?> Healthcare</span><?php endif; ?>
                    <?php if (count($dating_results) > 0): ?><span
                            class="badge bg-danger"><?php echo count($dating_results); ?> Dating Profiles</span><?php endif; ?>
                    <?php if (count($jobs) > 0): ?><span class="badge bg-primary"><?php echo count($jobs); ?>
                            Jobs</span><?php endif; ?>
                    <?php if (count($real_estate) > 0): ?><span
                            class="badge bg-secondary"><?php echo count($real_estate); ?> Properties</span><?php endif; ?>
                    <?php if (count($education_resources) > 0): ?><span
                            class="badge bg-info"><?php echo count($education_resources); ?> Education</span><?php endif; ?>
                    <?php if (count($home_services) > 0): ?><span
                            class="badge bg-purple"><?php echo count($home_services); ?> Home Services</span><?php endif; ?>
                    <?php if (count($exchange_materials) > 0): ?><span
                            class="badge bg-secondary"><?php echo count($exchange_materials); ?> Exchange
                            Items</span><?php endif; ?>
                    <?php if (count($community_news) > 0): ?><span
                            class="badge bg-info"><?php echo count($community_news); ?> News</span><?php endif; ?>
                    <?php if (count($community_events) > 0): ?><span
                            class="badge bg-success"><?php echo count($community_events); ?> Events</span><?php endif; ?>
                    <?php if (count($marketplace) > 0): ?><span
                            class="badge bg-warning text-dark"><?php echo count($marketplace); ?>
                            Marketplace</span><?php endif; ?>
                    <?php if (count($brokers) > 0): ?><span
                            class="badge bg-warning text-dark"><?php echo count($brokers); ?> Brokers</span><?php endif; ?>
                    <?php if (count($flights) > 0): ?><span class="badge bg-purple"><?php echo count($flights); ?>
                            Flights</span><?php endif; ?>
                    <?php if (count($courses) > 0): ?><span class="badge bg-info"><?php echo count($courses); ?>
                            Courses</span><?php endif; ?>
                    <?php if (count($users) > 0): ?><span class="badge bg-secondary"><?php echo count($users); ?>
                            Users</span><?php endif; ?>
                </div>
            </div>

            <!-- Quick Filter Buttons -->
            <div class="mb-4">
                <div class="d-flex flex-wrap gap-2">
                    <span class="text-muted small me-2">Quick filter:</span>
                    <a href="?search=<?php echo urlencode($search); ?>&filter=all"
                        class="btn btn-sm btn-outline-secondary rounded-pill">All Results</a>
                    <?php if (count($hotels) > 0): ?><a href="#hotels-section"
                            class="btn btn-sm btn-outline-primary rounded-pill">Hotels</a><?php endif; ?>
                    <?php if (count($restaurants) > 0): ?><a href="#restaurants-section"
                            class="btn btn-sm btn-outline-warning rounded-pill">Restaurants</a><?php endif; ?>
                    <?php if (count($jobs) > 0): ?><a href="#jobs-section"
                            class="btn btn-sm btn-outline-primary rounded-pill">Jobs</a><?php endif; ?>
                    <?php if (count($health_results) > 0): ?><a href="#health-section"
                            class="btn btn-sm btn-outline-success rounded-pill">Healthcare</a><?php endif; ?>
                    <?php if (count($dating_results) > 0): ?><a href="#dating-section"
                            class="btn btn-sm btn-outline-danger rounded-pill">Dating</a><?php endif; ?>
                    <?php if (count($real_estate) > 0): ?><a href="#realestate-section"
                            class="btn btn-sm btn-outline-secondary rounded-pill">Real Estate</a><?php endif; ?>
                    <?php if (count($education_resources) > 0): ?><a href="#education-section"
                            class="btn btn-sm btn-outline-info rounded-pill">Education</a><?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php
        $has_results = !empty($hotels) || !empty($restaurants) || !empty($taxis) || !empty($buses) ||
            !empty($health_results) || !empty($dating_results) || !empty($jobs) ||
            !empty($real_estate) || !empty($education_resources) || !empty($home_services) ||
            !empty($exchange_materials) || !empty($community_news) || !empty($community_events) ||
            !empty($marketplace) || !empty($brokers) || !empty($flights) || !empty($courses) ||
            !empty($users) || !empty($bus_routes);
        ?>
        <?php if (!$has_results): ?>
            <div class="card border-0 shadow-sm p-5 text-center">
                <i class="fas fa-search text-muted mb-3" style="font-size: 4rem;"></i>
                <h4 class="text-muted">No results found</h4>
                <p class="text-muted">Try a different search term or browse all services</p>
                <a href="index.php" class="btn btn-primary-green rounded-pill px-4">View All Services</a>
            </div>
        <?php else: ?>

            <!-- HOTELS RESULTS -->
            <?php if (!empty($hotels)): ?>
                <?php if (!empty($search)): ?>
                    <h4 class="mb-3 text-primary-green"><i class="fas fa-hotel me-2"></i>Hotels</h4><?php endif; ?>
                <div class="row g-4 mb-4">
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

            <!-- RESTAURANTS RESULTS -->
            <?php if (!empty($restaurants)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-utensils me-2"></i>Restaurants</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($restaurants as $res): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="position-absolute top-0 end-0 m-3 z-1">
                                    <span class="badge bg-white text-dark shadow-sm rounded-pill px-3 py-2">
                                        <i class="fas fa-star text-warning me-1"></i> New
                                    </span>
                                </div>
                                <img src="<?php echo htmlspecialchars($res['image_url'] ?: 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=800&q=80'); ?>"
                                    class="card-img-top" alt="<?php echo htmlspecialchars($res['name']); ?>"
                                    style="height: 220px; object-fit: cover;">
                                <div class="card-body p-4">
                                    <h5 class="card-title fw-bold mb-1"><?php echo htmlspecialchars($res['name']); ?></h5>
                                    <p class="text-muted small mb-2">
                                        <i
                                            class="fas fa-map-marker-alt me-2 text-danger"></i><?php echo htmlspecialchars($res['address']); ?>
                                    </p>
                                    <p class="text-muted small mb-2">
                                        <i
                                            class="fas fa-utensils me-2 text-primary-green"></i><?php echo htmlspecialchars($res['cuisine_type']); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-4">
                                        <span class="text-muted small"><i class="fas fa-clock me-1"></i> Open Now</span>
                                        <a href="restaurants.php" class="btn btn-primary-green rounded-pill px-4">
                                            Visit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- TAXI RESULTS -->
            <?php if (!empty($taxis)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-taxi me-2"></i>Taxis</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($taxis as $taxi): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-taxi"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title fw-bold mb-0">
                                                <?php echo htmlspecialchars($taxi['company_name']); ?>
                                            </h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($taxi['address']); ?></small>
                                        </div>
                                    </div>
                                    <p class="mb-3">
                                        <?php echo htmlspecialchars($taxi['description'] ?? 'Reliable taxi service.'); ?>
                                    </p>
                                    <a href="taxi.php" class="btn btn-warning w-100 rounded-pill fw-bold">Book Ride</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- BUS RESULTS -->
            <?php if (!empty($buses)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-bus me-2"></i>Bus Companies</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($buses as $bus): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary-green text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-bus"></i>
                                        </div>
                                        <div>
                                            <h5 class="card-title fw-bold mb-0">
                                                <?php echo htmlspecialchars($bus['company_name']); ?>
                                            </h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($bus['address']); ?></small>
                                        </div>
                                    </div>
                                    <p class="mb-3">
                                        <?php echo htmlspecialchars($bus['description'] ?? 'Comfortable bus travel.'); ?>
                                    </p>
                                    <a href="buses.php" class="btn btn-outline-primary-green w-100 rounded-pill fw-bold">Buy
                                        Ticket</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- HEALTH RESULTS -->
            <?php if (!empty($health_results)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-heartbeat me-2"></i>Medical Providers
                </h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($health_results as $h): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user-md"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($h['name']); ?></h5>
                                            <span
                                                class="badge bg-light text-success border-success border"><?php echo ucfirst($h['type']); ?></span>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3"><?php echo htmlspecialchars($h['location']); ?></p>
                                    <a href="health_services.php" class="btn btn-outline-success w-100 rounded-pill">Book Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- DATING RESULTS -->
            <?php if (!empty($dating_results)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-heart me-2 text-danger"></i>Find Love
                </h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($dating_results as $d): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm overflow-hidden">
                                <img src="<?php echo htmlspecialchars($d['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($d['full_name']) . '&background=E91E63&color=fff'); ?>"
                                    class="card-img-top" style="height: 180px; object-fit: cover;">
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($d['full_name']); ?>,
                                        <?php echo $d['age']; ?>
                                    </h6>
                                    <p class="text-muted small text-truncate mb-3"><?php echo htmlspecialchars($d['bio'] ?? ''); ?>
                                    </p>
                                    <a href="dating.php" class="btn btn-danger btn-sm w-100 rounded-pill">View Profile</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- JOBS RESULTS -->
            <?php if (!empty($jobs)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-briefcase me-2"></i>Jobs</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($jobs as $job): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-briefcase"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($job['company']); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($job['location']); ?></p>
                                    <p class="text-muted small mb-3"><i class="fas fa-money-bill me-1"></i>
                                        <?php echo htmlspecialchars($job['salary_range'] ?? 'Negotiable'); ?></p>
                                    <a href="jobs.php" class="btn btn-primary w-100 rounded-pill">Apply Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- REAL ESTATE RESULTS -->
            <?php if (!empty($real_estate)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-building me-2"></i>Real Estate</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($real_estate as $re): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <img src="<?php echo htmlspecialchars($re['image_url'] ?? 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?auto=format&fit=crop&w=800&q=80'); ?>"
                                    class="card-img-top" style="height: 180px; object-fit: cover;">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($re['title']); ?></h5>
                                    <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($re['location']); ?></p>
                                    <p class="text-muted small mb-2"><i class="fas fa-home me-1"></i>
                                        <?php echo htmlspecialchars($re['property_type']); ?></p>
                                    <p class="text-primary-green fw-bold mb-3"><?php echo number_format($re['price']); ?> ETB</p>
                                    <a href="../realestate/index.php" class="btn btn-outline-primary w-100 rounded-pill">View
                                        Details</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- EDUCATION RESULTS -->
            <?php if (!empty($education_resources)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-graduation-cap me-2"></i>Education
                    Resources</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($education_resources as $edu): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-book"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($edu['title']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($edu['subject']); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2"><i class="fas fa-graduation-cap me-1"></i> Grade:
                                        <?php echo htmlspecialchars($edu['grade']); ?>
                                    </p>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars($edu['resource_type'] ?? 'Resource'); ?>
                                    </p>
                                    <a href="education.php" class="btn btn-info text-white w-100 rounded-pill">Access</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- HOME SERVICES RESULTS -->
            <?php if (!empty($home_services)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-wrench me-2"></i>Home Services</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($home_services as $hs): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-purple text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; background-color: #4527A0;">
                                            <i class="fas fa-tools"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($hs['name']); ?></h5>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars($hs['description'] ?? 'Professional home service'); ?>
                                    </p>
                                    <a href="home_services.php" class="btn btn-outline-primary w-100 rounded-pill"
                                        style="border-color: #4527A0; color: #4527A0;">Book Service</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- EXCHANGE MATERIALS RESULTS -->
            <?php if (!empty($exchange_materials)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-exchange-alt me-2"></i>Exchange
                    Materials</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($exchange_materials as $em): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; background-color: #5C6BC0;">
                                            <i class="fas fa-book-open"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($em['title']); ?></h5>
                                            <small class="text-muted"><?php echo htmlspecialchars($em['subject'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2"><i class="fas fa-layer-group me-1"></i> Grade:
                                        <?php echo htmlspecialchars($em['grade_level'] ?? 'N/A'); ?>
                                    </p>
                                    <p class="text-primary-green fw-bold mb-3"><?php echo number_format($em['price'] ?? 0); ?> ETB
                                    </p>
                                    <a href="exchange_material.php" class="btn btn-outline-primary w-100 rounded-pill"
                                        style="border-color: #5C6BC0; color: #5C6BC0;">View Item</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- COMMUNITY NEWS RESULTS -->
            <?php if (!empty($community_news)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-newspaper me-2"></i>Community News</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($community_news as $news): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($news['title']); ?></h5>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars(substr($news['content'] ?? '', 0, 100)); ?>...
                                    </p>
                                    <a href="community.php" class="btn btn-outline-info w-100 rounded-pill">Read More</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- COMMUNITY EVENTS RESULTS -->
            <?php if (!empty($community_events)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-calendar-alt me-2"></i>Community Events
                </h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($community_events as $evt): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($evt['title']); ?></h5>
                                    <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($evt['location']); ?></p>
                                    <p class="text-muted small mb-3"><i class="fas fa-calendar me-1"></i>
                                        <?php echo date('M d, Y', strtotime($evt['event_date'])); ?></p>
                                    <a href="community.php" class="btn btn-outline-success w-100 rounded-pill">View Event</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- MARKETPLACE RESULTS -->
            <?php if (!empty($marketplace)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-store me-2"></i>Marketplace</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($marketplace as $mkt): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($mkt['title']); ?></h5>
                                    <p class="text-muted small mb-2"><?php echo htmlspecialchars($mkt['category'] ?? 'Item'); ?></p>
                                    <p class="text-primary-green fw-bold mb-3"><?php echo number_format($mkt['price'] ?? 0); ?> ETB
                                    </p>
                                    <a href="community.php" class="btn btn-outline-warning w-100 rounded-pill">View Item</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- BROKERS RESULTS -->
            <?php if (!empty($brokers)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-user-tie me-2"></i>Brokers</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($brokers as $broker): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px;">
                                            <i class="fas fa-user-tie"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($broker['business_name']); ?></h5>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($broker['location'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars($broker['description'] ?? 'Professional broker services'); ?>
                                    </p>
                                    <?php if (!empty($broker['rating'])): ?>
                                        <p class="text-warning mb-3"><i class="fas fa-star me-1"></i>
                                            <?php echo number_format($broker['rating'], 1); ?></p>
                                    <?php endif; ?>
                                    <a href="../broker/dashboard.php" class="btn btn-warning w-100 rounded-pill">Contact</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- FLIGHTS RESULTS -->
            <?php if (!empty($flights)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-plane me-2"></i>Flights</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($flights as $flight): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; background-color: #6A1B9A;">
                                            <i class="fas fa-plane"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($flight['airline']); ?></h5>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($flight['flight_number']); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2">
                                        <i class="fas fa-plane-departure me-1"></i>
                                        <?php echo htmlspecialchars($flight['departure_city']); ?>
                                        <i class="fas fa-arrow-right mx-2"></i>
                                        <i class="fas fa-plane-arrival me-1"></i>
                                        <?php echo htmlspecialchars($flight['arrival_city']); ?>
                                    </p>
                                    <p class="text-primary-green fw-bold mb-3">
                                        <?php echo number_format($flight['base_price'] ?? 0); ?> ETB
                                    </p>
                                    <a href="flights.php" class="btn btn-outline-primary w-100 rounded-pill"
                                        style="border-color: #6A1B9A; color: #6A1B9A;">Book Flight</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- BUS ROUTES RESULTS -->
            <?php if (!empty($bus_routes)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-route me-2"></i>Bus Routes</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($bus_routes as $route): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; background-color: #00695C;">
                                            <i class="fas fa-route"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($route['origin']); ?> →
                                                <?php echo htmlspecialchars($route['destination']); ?>
                                            </h5>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($route['company_name']); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-primary-green fw-bold mb-3">
                                        <?php echo number_format($route['base_price'] ?? 0); ?> ETB
                                    </p>
                                    <a href="buses.php" class="btn btn-outline-primary w-100 rounded-pill"
                                        style="border-color: #00695C; color: #00695C;">Book Ticket</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- COURSES RESULTS -->
            <?php if (!empty($courses)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-play-circle me-2"></i>Courses</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($courses as $course): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <div class="text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; background-color: #00BCD4;">
                                            <i class="fas fa-play-circle"></i>
                                        </div>
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($course['title']); ?></h5>
                                            <small
                                                class="text-muted"><?php echo htmlspecialchars($course['instructor'] ?? 'Instructor'); ?></small>
                                        </div>
                                    </div>
                                    <p class="text-muted small mb-2"><i class="fas fa-clock me-1"></i>
                                        <?php echo htmlspecialchars($course['duration'] ?? 0); ?> hours</p>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 80)); ?>...
                                    </p>
                                    <a href="lms.php" class="btn btn-outline-info w-100 rounded-pill">Enroll Now</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- USERS RESULTS -->
            <?php if (!empty($users)): ?>
                <h4 class="mb-3 text-primary-green separator-top pt-4"><i class="fas fa-users me-2"></i>Users</h4>
                <div class="row g-4 mb-4">
                    <?php foreach ($users as $user): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 hover-lift border-0 shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex align-items-center mb-3">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=607D8B&color=fff"
                                            class="rounded-circle me-3" width="50" height="50" alt="User">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                            <span class="badge bg-secondary"><?php echo ucfirst($user['role']); ?></span>
                                        </div>
                                    </div>
                                    <a href="profile.php" class="btn btn-outline-secondary w-100 rounded-pill">View Profile</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

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

    /* Purple badge color */
    .bg-purple {
        background-color: #4527A0 !important;
    }

    /* Separator for search results */
    .separator-top {
        border-top: 2px solid #e8f5e9;
        margin-top: 1rem;
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