<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$is_logged_in = isLoggedIn();
$flash = getFlashMessage();

// Add columns if they don't exist
try {
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) AFTER image_url");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS bedrooms INT DEFAULT 0 AFTER video_url");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS bathrooms INT DEFAULT 0 AFTER bedrooms");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS area_sqm INT DEFAULT 0 AFTER bathrooms");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS features TEXT AFTER area_sqm");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS contact_phone VARCHAR(20) AFTER features");
    $pdo->exec("ALTER TABLE listings ADD COLUMN IF NOT EXISTS contact_name VARCHAR(100) AFTER contact_phone");
} catch (Exception $e) {
}

// Create rental requests table  
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS rental_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT,
        customer_id INT,
        customer_name VARCHAR(100),
        customer_phone VARCHAR(20),
        customer_email VARCHAR(100),
        message TEXT,
        status ENUM('pending','approved','rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE
    )");
} catch (Exception $e) {
}

// Handle rental request POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_request'])) {
    if (!$is_logged_in) {
        redirectWithMessage('rent.php', 'error', 'Please login to send a rental request.');
    }
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $listing_id = (int) $_POST['listing_id'];
        $customer_name = sanitize($_POST['customer_name']);
        $customer_phone = sanitize($_POST['customer_phone']);
        $customer_email = sanitize($_POST['customer_email']);
        $message = sanitize($_POST['message']);
        $customer_id = getCurrentUserId();

        try {
            $stmt = $pdo->prepare("INSERT INTO rental_requests (listing_id, customer_id, customer_name, customer_phone, customer_email, message) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$listing_id, $customer_id, $customer_name, $customer_phone, $customer_email, $message]);
            redirectWithMessage('rent.php?category=' . ($_POST['category'] ?? 'house_rent'), 'success', 'Your rental request has been sent! The owner will contact you soon.');
        } catch (Exception $e) {
            redirectWithMessage('rent.php', 'error', 'Failed to send request. Please try again.');
        }
    }
}

// Active tab
$category = sanitize($_GET['category'] ?? 'house_rent');

// Fetch listings
$stmt = $pdo->prepare("SELECT * FROM listings WHERE type = ? AND status = 'available' ORDER BY created_at DESC");
$stmt->execute([$category]);
$listings = $stmt->fetchAll();

// View single listing detail
$detail = null;
if (isset($_GET['view'])) {
    $stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND status = 'available'");
    $stmt->execute([(int) $_GET['view']]);
    $detail = $stmt->fetch();
}

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --rent-primary: #1B5E20;
        --rent-gold: #F9A825;
        --rent-bg: #f5f7f5;
        --rent-dark: #0d2818;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--rent-bg);
    }

    /* Hero */
    .rent-hero {
        background: linear-gradient(135deg, rgba(13, 40, 24, 0.85), rgba(27, 94, 32, 0.7)), url('https://images.unsplash.com/photo-1512917774080-9991f1c4c750?auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        padding: 80px 0 120px;
        color: white;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .rent-hero::before {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        height: 80px;
        background: var(--rent-bg);
        border-radius: 50px 50px 0 0;
    }

    .rent-hero h1 {
        font-size: 3rem;
        font-weight: 800;
        letter-spacing: -1px;
        margin-bottom: 10px;
    }

    .rent-hero h1 span {
        color: var(--rent-gold);
    }

    /* Category Pills */
    .category-container {
        position: relative;
        z-index: 10;
        margin-top: -45px;
        margin-bottom: 40px;
    }

    .category-pills {
        background: white;
        padding: 8px;
        border-radius: 60px;
        display: inline-flex;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.08);
        gap: 4px;
    }

    .pill-btn {
        padding: 14px 32px;
        border-radius: 50px;
        border: none;
        background: transparent;
        font-weight: 700;
        transition: all 0.35s ease;
        color: #888;
        text-decoration: none;
        font-size: 0.95rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .pill-btn:hover {
        color: var(--rent-primary);
        background: rgba(27, 94, 32, 0.08);
    }

    .pill-btn.active {
        background: var(--rent-primary);
        color: white;
        box-shadow: 0 8px 25px rgba(27, 94, 32, 0.35);
    }

    .pill-btn .count-badge {
        background: rgba(255, 255, 255, 0.25);
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 0.75rem;
    }

    .pill-btn.active .count-badge {
        background: rgba(255, 255, 255, 0.25);
    }

    /* Cards */
    .listing-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: none;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.04);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        height: 100%;
        cursor: pointer;
    }

    .listing-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.12);
    }

    .listing-media {
        position: relative;
        overflow: hidden;
        height: 240px;
    }

    .listing-media img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .listing-card:hover .listing-media img {
        transform: scale(1.08);
    }

    .media-badges {
        position: absolute;
        top: 15px;
        left: 15px;
        display: flex;
        gap: 8px;
        z-index: 5;
    }

    .media-badge {
        padding: 6px 14px;
        border-radius: 25px;
        font-size: 0.7rem;
        font-weight: 700;
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .badge-type {
        background: rgba(27, 94, 32, 0.85);
        color: white;
    }

    .badge-video {
        background: rgba(0, 0, 0, 0.6);
        color: white;
    }

    .price-tag {
        position: absolute;
        bottom: 15px;
        right: 15px;
        background: white;
        padding: 10px 18px;
        border-radius: 15px;
        font-weight: 800;
        color: var(--rent-primary);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
        font-size: 1rem;
        z-index: 5;
    }

    .price-tag small {
        font-weight: 400;
        color: #999;
        font-size: 0.7rem;
    }

    .fav-btn {
        position: absolute;
        top: 15px;
        right: 15px;
        background: white;
        border: none;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        cursor: pointer;
        transition: all 0.3s;
        z-index: 5;
    }

    .fav-btn:hover {
        transform: scale(1.1);
        color: red;
    }

    .listing-body {
        padding: 22px;
    }

    .listing-body h5 {
        font-weight: 700;
        margin-bottom: 8px;
        font-size: 1.1rem;
        color: #1a1a1a;
    }

    .listing-body .location {
        color: #888;
        font-size: 0.85rem;
        margin-bottom: 12px;
    }

    .listing-body .description {
        color: #666;
        font-size: 0.85rem;
        line-height: 1.6;
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
        margin-bottom: 15px;
    }

    .listing-features {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 15px;
        padding-top: 15px;
        border-top: 1px solid #f0f0f0;
    }

    .feature-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.8rem;
        color: #666;
        background: #f8f9fa;
        padding: 5px 12px;
        border-radius: 20px;
    }

    .feature-item i {
        color: var(--rent-primary);
        font-size: 0.7rem;
    }

    .listing-bottom {
        display: flex;
        gap: 10px;
    }

    .btn-view-detail {
        flex: 1;
        background: linear-gradient(135deg, var(--rent-primary), #2e7d32);
        color: white;
        border-radius: 12px;
        padding: 12px;
        font-weight: 700;
        border: none;
        transition: all 0.3s;
        text-align: center;
        text-decoration: none;
        font-size: 0.9rem;
    }

    .btn-view-detail:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(27, 94, 32, 0.3);
        color: white;
    }

    .btn-contact {
        background: #f8f9fa;
        border: 2px solid #eee;
        border-radius: 12px;
        padding: 12px 16px;
        font-weight: 700;
        color: #333;
        transition: all 0.3s;
    }

    .btn-contact:hover {
        background: var(--rent-gold);
        border-color: var(--rent-gold);
        color: #333;
    }

    /* Detail Page */
    .detail-hero-img {
        width: 100%;
        height: 400px;
        object-fit: cover;
        border-radius: 25px;
        box-shadow: 0 15px 40px rgba(0, 0, 0, 0.12);
    }

    .detail-video-wrap {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        aspect-ratio: 16/9;
    }

    .detail-video-wrap iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    .detail-info-card {
        background: white;
        border-radius: 20px;
        padding: 30px;
        box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
    }

    .detail-feature-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        gap: 10px;
    }

    .detail-feature {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 12px;
        text-align: center;
        font-size: 0.85rem;
        color: #555;
    }

    .detail-feature i {
        display: block;
        font-size: 1.2rem;
        color: var(--rent-primary);
        margin-bottom: 5px;
    }

    .request-card {
        background: linear-gradient(135deg, var(--rent-primary), #2e7d32);
        border-radius: 25px;
        padding: 30px;
        color: white;
        position: sticky;
        top: 20px;
    }

    .request-card .form-control,
    .request-card .form-control:focus {
        background: rgba(255, 255, 255, 0.15);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        border-radius: 12px;
        padding: 12px 16px;
    }

    .request-card .form-control::placeholder {
        color: rgba(255, 255, 255, 0.6);
    }

    .request-card textarea.form-control {
        resize: none;
    }

    .btn-send-req {
        background: var(--rent-gold);
        color: #333;
        border: none;
        border-radius: 12px;
        padding: 14px;
        font-weight: 800;
        width: 100%;
        font-size: 1rem;
        transition: all 0.3s;
    }

    .btn-send-req:hover {
        background: #fbc02d;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(249, 168, 37, 0.4);
    }

    .empty-state {
        padding: 80px 20px;
        text-align: center;
    }

    .empty-state i {
        font-size: 4rem;
        color: #ddd;
        margin-bottom: 20px;
    }

    /* Success Toast */
    .success-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        animation: slideIn 0.5s ease;
    }

    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }

        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @media (max-width: 768px) {
        .rent-hero h1 {
            font-size: 2rem;
        }

        .pill-btn {
            padding: 10px 20px;
            font-size: 0.85rem;
        }

        .listing-media {
            height: 200px;
        }
    }
</style>

<?php if ($flash): ?>
    <div class="success-toast">
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> shadow-lg rounded-4 d-flex align-items-center gap-3 px-4 py-3"
            role="alert">
            <i class="fas fa-<?php echo $flash['type'] === 'success' ? 'check-circle' : 'exclamation-circle'; ?> fs-4"></i>
            <div>
                <strong><?php echo $flash['type'] === 'success' ? 'Success!' : 'Error!'; ?></strong>
                <p class="mb-0 small"><?php echo htmlspecialchars($flash['message']); ?></p>
            </div>
            <button type="button" class="btn-close" onclick="this.parentElement.parentElement.remove()"></button>
        </div>
    </div>
<?php endif; ?>

<?php if ($detail): ?>
    <!-- DETAIL VIEW -->
    <div class="container py-5" style="max-width:1100px;">
        <a href="rent.php?category=<?php echo $detail['type']; ?>" class="btn btn-light rounded-pill px-4 mb-4 shadow-sm">
            <i class="fas fa-arrow-left me-2"></i>Back to Listings
        </a>

        <div class="row g-4">
            <!-- Left: Media -->
            <div class="col-lg-7">
                <!-- Main Image -->
                <img src="<?php echo htmlspecialchars($detail['image_url'] ?: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800'); ?>"
                    class="detail-hero-img mb-4" alt="<?php echo htmlspecialchars($detail['title']); ?>">

                <!-- Video if available -->
                <?php if (!empty($detail['video_url'])): ?>
                    <div class="detail-video-wrap mb-4">
                        <iframe src="<?php echo htmlspecialchars($detail['video_url']); ?>" allowfullscreen></iframe>
                    </div>
                <?php endif; ?>

                <!-- Info Card -->
                <div class="detail-info-card">
                    <h2 class="fw-bold mb-2"><?php echo htmlspecialchars($detail['title']); ?></h2>
                    <p class="text-muted mb-3">
                        <i class="fas fa-map-marker-alt text-danger me-2"></i>
                        <?php echo htmlspecialchars($detail['location']); ?>
                    </p>

                    <div class="d-flex gap-3 mb-4">
                        <span class="badge bg-success rounded-pill px-3 py-2 fs-6">
                            <?php echo number_format($detail['price']); ?> ETB
                            <small>/<?php echo $detail['type'] === 'car_rent' ? 'day' : 'month'; ?></small>
                        </span>
                        <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                            <i class="fas fa-<?php echo $detail['type'] === 'house_rent' ? 'home' : 'car'; ?> me-1"></i>
                            <?php echo $detail['type'] === 'house_rent' ? 'House' : 'Car'; ?> Rental
                        </span>
                    </div>

                    <h5 class="fw-bold mb-3">Description</h5>
                    <p class="text-muted lh-lg"><?php echo nl2br(htmlspecialchars($detail['description'])); ?></p>

                    <?php if ($detail['type'] === 'house_rent' && ($detail['bedrooms'] || $detail['bathrooms'] || $detail['area_sqm'])): ?>
                        <hr>
                        <h5 class="fw-bold mb-3">Property Details</h5>
                        <div class="row g-3 mb-3">
                            <?php if ($detail['bedrooms']): ?>
                                <div class="col-4">
                                    <div class="detail-feature">
                                        <i class="fas fa-bed"></i>
                                        <?php echo $detail['bedrooms']; ?> Bedrooms
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($detail['bathrooms']): ?>
                                <div class="col-4">
                                    <div class="detail-feature">
                                        <i class="fas fa-bath"></i>
                                        <?php echo $detail['bathrooms']; ?> Bathrooms
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($detail['area_sqm']): ?>
                                <div class="col-4">
                                    <div class="detail-feature">
                                        <i class="fas fa-ruler-combined"></i>
                                        <?php echo $detail['area_sqm']; ?> m²
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($detail['features'])): ?>
                        <h5 class="fw-bold mb-3">Features & Amenities</h5>
                        <div class="detail-feature-grid">
                            <?php foreach (explode(',', $detail['features']) as $feat): ?>
                                <div class="detail-feature">
                                    <i class="fas fa-check-circle"></i>
                                    <?php echo htmlspecialchars(trim($feat)); ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($detail['contact_name']) || !empty($detail['contact_phone'])): ?>
                        <hr>
                        <h5 class="fw-bold mb-3"><i class="fas fa-user me-2"></i>Contact Owner</h5>
                        <p class="mb-1"><strong><?php echo htmlspecialchars($detail['contact_name'] ?? 'Owner'); ?></strong></p>
                        <?php if (!empty($detail['contact_phone'])): ?>
                            <p class="text-muted"><i
                                    class="fas fa-phone me-2"></i><?php echo htmlspecialchars($detail['contact_phone']); ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Right: Request Form -->
            <div class="col-lg-5">
                <div class="request-card">
                    <h4 class="fw-bold mb-1"><i class="fas fa-paper-plane me-2"></i>Send Rental Request</h4>
                    <p class="opacity-75 small mb-4">The owner will be notified and may contact you.</p>

                    <?php if ($is_logged_in): ?>
                        <form method="POST">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="listing_id" value="<?php echo $detail['id']; ?>">
                            <input type="hidden" name="category" value="<?php echo $detail['type']; ?>">
                            <div class="mb-3">
                                <input type="text" name="customer_name" class="form-control" placeholder="Your Full Name"
                                    value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <input type="tel" name="customer_phone" class="form-control"
                                    placeholder="Phone Number (e.g. +251...)" required>
                            </div>
                            <div class="mb-3">
                                <input type="email" name="customer_email" class="form-control" placeholder="Email Address"
                                    required>
                            </div>
                            <div class="mb-4">
                                <textarea name="message" class="form-control" rows="4"
                                    placeholder="I'm interested in this property. When can I visit?" required></textarea>
                            </div>
                            <button type="submit" name="submit_request" class="btn-send-req">
                                <i class="fas fa-paper-plane me-2"></i>Send Request
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-lock fs-1 opacity-50 mb-3 d-block"></i>
                            <p class="mb-3">Please login to send a rental request</p>
                            <a href="../login.php?redirect=customer/rent.php?view=<?php echo $detail['id']; ?>"
                                class="btn btn-light rounded-pill px-4 fw-bold">
                                <i class="fas fa-sign-in-alt me-2"></i>Login Now
                            </a>
                        </div>
                    <?php endif; ?>

                    <div class="text-center mt-4 pt-3" style="border-top:1px solid rgba(255,255,255,0.15);">
                        <p class="small opacity-75 mb-2">Or contact directly:</p>
                        <?php if (!empty($detail['contact_phone'])): ?>
                            <a href="tel:<?php echo $detail['contact_phone']; ?>"
                                class="btn btn-outline-light rounded-pill px-4">
                                <i class="fas fa-phone me-2"></i>Call Owner
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LIST VIEW -->
    <div class="rent-hero">
        <div class="position-relative" style="z-index:2;">
            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-3 fw-bold">🏠 Premium Listings</span>
            <h1>Find Your Perfect <span>Rental</span></h1>
            <p class="lead opacity-80 mb-0">Houses, apartments & cars for rent across Addis Ababa</p>
        </div>
    </div>

    <div class="container category-container text-center">
        <div class="category-pills">
            <a href="?category=house_rent" class="pill-btn <?php echo $category === 'house_rent' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Houses
                <span class="count-badge"><?php
                $hc = $pdo->query("SELECT COUNT(*) FROM listings WHERE type='house_rent' AND status='available'")->fetchColumn();
                echo $hc;
                ?></span>
            </a>
            <a href="?category=car_rent" class="pill-btn <?php echo $category === 'car_rent' ? 'active' : ''; ?>">
                <i class="fas fa-car"></i> Cars
                <span class="count-badge"><?php
                $cc = $pdo->query("SELECT COUNT(*) FROM listings WHERE type='car_rent' AND status='available'")->fetchColumn();
                echo $cc;
                ?></span>
            </a>
        </div>
    </div>

    <div class="container pb-5">
        <?php if (empty($listings)): ?>
            <div class="empty-state">
                <i class="fas fa-<?php echo $category === 'house_rent' ? 'home' : 'car'; ?> d-block"></i>
                <h4 class="fw-bold text-muted">No <?php echo str_replace('_', ' ', $category); ?> listings available</h4>
                <p class="text-muted">New listings are added frequently. Check back soon!</p>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($listings as $item): ?>
                    <div class="col-lg-4 col-md-6">
                        <div class="listing-card" onclick="window.location='rent.php?view=<?php echo $item['id']; ?>'">
                            <div class="listing-media">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?auto=format&fit=crop&w=800&q=80'); ?>"
                                    alt="<?php echo htmlspecialchars($item['title']); ?>">

                                <div class="media-badges">
                                    <span class="media-badge badge-type">
                                        <i class="fas fa-<?php echo $category === 'house_rent' ? 'home' : 'car'; ?> me-1"></i>
                                        <?php echo $category === 'house_rent' ? 'House' : 'Car'; ?>
                                    </span>
                                    <?php if (!empty($item['video_url'])): ?>
                                        <span class="media-badge badge-video">
                                            <i class="fas fa-play me-1"></i>Video
                                        </span>
                                    <?php endif; ?>
                                </div>

                                <button class="fav-btn"
                                    onclick="event.stopPropagation(); this.querySelector('i').classList.toggle('fas'); this.querySelector('i').classList.toggle('far'); this.querySelector('i').style.color = this.querySelector('i').classList.contains('fas') ? 'red' : '';">
                                    <i class="far fa-heart"></i>
                                </button>

                                <div class="price-tag">
                                    <?php echo number_format($item['price']); ?> ETB
                                    <small>/<?php echo $category === 'car_rent' ? 'Day' : 'Mo'; ?></small>
                                </div>
                            </div>
                            <div class="listing-body">
                                <h5><?php echo htmlspecialchars($item['title']); ?></h5>
                                <p class="location">
                                    <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                                <p class="description"><?php echo htmlspecialchars($item['description']); ?></p>

                                <div class="listing-features">
                                    <?php if ($category === 'house_rent'): ?>
                                        <?php if (!empty($item['bedrooms'])): ?>
                                            <span class="feature-item"><i class="fas fa-bed"></i> <?php echo $item['bedrooms']; ?>
                                                Beds</span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['bathrooms'])): ?>
                                            <span class="feature-item"><i class="fas fa-bath"></i> <?php echo $item['bathrooms']; ?>
                                                Baths</span>
                                        <?php endif; ?>
                                        <?php if (!empty($item['area_sqm'])): ?>
                                            <span class="feature-item"><i class="fas fa-ruler-combined"></i>
                                                <?php echo $item['area_sqm']; ?>m²</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <?php
                                        $feats = $item['features'] ? explode(',', $item['features']) : [];
                                        $show = array_slice($feats, 0, 3);
                                        foreach ($show as $f): ?>
                                            <span class="feature-item"><i class="fas fa-check"></i> <?php echo trim($f); ?></span>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>

                                <div class="listing-bottom">
                                    <a href="rent.php?view=<?php echo $item['id']; ?>" class="btn-view-detail"
                                        onclick="event.stopPropagation();">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    <?php if (!empty($item['contact_phone'])): ?>
                                        <a href="tel:<?php echo $item['contact_phone']; ?>" class="btn-contact"
                                            onclick="event.stopPropagation();">
                                            <i class="fas fa-phone"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<script>
    // Auto-dismiss flash message
    setTimeout(() => {
        const toast = document.querySelector('.success-toast');
        if (toast) {
            toast.style.transition = 'opacity 0.5s';
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 500);
        }
    }, 5000);
</script>

<?php include('../includes/footer.php'); ?>