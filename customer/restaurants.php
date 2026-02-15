<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$is_logged_in = isLoggedIn();

// Fetch restaurants (hotels with cuisine_type defined or simply all approved hotels)
$stmt = $pdo->query("SELECT * FROM hotels WHERE status = 'approved' ORDER BY rating DESC");
$establishments = $stmt->fetchAll();

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --rest-primary: #E64A19;
        --rest-bg: #fffbf9;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--rest-bg);
    }

    .rest-hero {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        height: 350px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        text-align: center;
        border-radius: 0 0 50px 50px;
        margin-bottom: 50px;
    }

    .rest-card {
        background: white;
        border-radius: 30px;
        overflow: hidden;
        border: none;
        box-shadow: 0 15px 35px rgba(230, 74, 25, 0.05);
        transition: 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        height: 100%;
    }

    .rest-card:hover {
        transform: translateY(-15px);
        box-shadow: 0 25px 50px rgba(230, 74, 25, 0.15);
    }

    .rest-img {
        height: 250px;
        object-fit: cover;
        width: 100%;
    }

    .rating-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: white;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 700;
        color: #FFA000;
        display: flex;
        align-items: center;
        gap: 5px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    .cuisine-tag {
        background: #FBE9E7;
        color: var(--rest-primary);
        padding: 5px 15px;
        border-radius: 50px;
        font-size: 12px;
        font-weight: 700;
        display: inline-block;
        margin-bottom: 10px;
    }

    .btn-order {
        background: var(--rest-primary);
        color: white;
        border-radius: 20px;
        padding: 12px;
        font-weight: 700;
        width: 100%;
        border: none;
        transition: 0.3s;
    }

    .btn-order:hover {
        background: #BF360C;
        box-shadow: 0 10px 20px rgba(230, 74, 25, 0.3);
    }
</style>

<div class="rest-hero">
    <div class="container">
        <h1 class="display-3 fw-bold mb-2">Taste of Ethiopia</h1>
        <p class="lead opacity-75">Discover and order from the finest restaurants in Addis Ababa</p>
    </div>
</div>

<div class="container pb-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <h2 class="fw-bold mb-0">Popular Restaurants</h2>
        <div class="dropdown">
            <button class="btn btn-white shadow-sm rounded-pill px-4 dropdown-toggle" type="button"
                data-bs-toggle="dropdown">
                Filter by Cuisine
            </button>
            <ul class="dropdown-menu border-0 shadow-lg p-3 rounded-4">
                <li><a class="dropdown-item rounded-3" href="#">Traditional Ethiopian</a></li>
                <li><a class="dropdown-item rounded-3" href="#">International</a></li>
                <li><a class="dropdown-item rounded-3" href="#">Fast Food</a></li>
            </ul>
        </div>
    </div>

    <div class="row g-4">
        <?php foreach ($establishments as $rest): ?>
            <div class="col-lg-4 col-md-6">
                <div class="rest-card">
                    <div class="position-relative">
                        <img src="<?php echo htmlspecialchars($rest['image_url'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80'); ?>"
                            class="rest-img">
                        <div class="rating-badge">
                            <i class="fas fa-star"></i>
                            <?php echo number_format($rest['rating'], 1); ?>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <span class="cuisine-tag">
                            <?php echo htmlspecialchars($rest['cuisine_type'] ?: 'Multi-Cuisine'); ?>
                        </span>
                        <h4 class="fw-bold mb-2">
                            <?php echo htmlspecialchars($rest['name']); ?>
                        </h4>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-map-marker-alt text-danger me-1"></i>
                            <?php echo htmlspecialchars($rest['location']); ?>
                        </p>
                        <?php if (!empty($rest['phone'])): ?>
                            <p class="small mb-3">
                                <a href="tel:<?php echo htmlspecialchars($rest['phone']); ?>"
                                    class="text-decoration-none text-success">
                                    <i class="fas fa-phone-alt me-1"></i>
                                    <?php echo htmlspecialchars($rest['phone']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <p class="text-muted small mb-4 line-clamp-2">
                            <?php echo htmlspecialchars($rest['description']); ?>
                        </p>

                        <div class="d-flex gap-3 align-items-center mb-4 text-muted small">
                            <span><i class="far fa-clock me-1"></i>
                                <?php echo htmlspecialchars($rest['delivery_time'] ?: '30-45'); ?> mins
                            </span>
                            <span><i class="fas fa-motorcycle me-1"></i>
                                <?php echo $rest['delivery_fee'] > 0 ? number_format($rest['delivery_fee']) . ' ETB' : 'Free'; ?>
                            </span>
                        </div>

                        <a href="menu.php?id=<?php echo $rest['id']; ?>"
                            class="btn-order text-center d-block text-decoration-none">
                            View Menu & Order
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
    }
</style>

<?php include('../includes/footer.php'); ?>