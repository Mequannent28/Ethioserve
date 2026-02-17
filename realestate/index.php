<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Fetch Featured Properties
$stmt = $pdo->query("SELECT * FROM real_estate_properties WHERE is_featured = 1 LIMIT 6");
$featured_properties = $stmt->fetchAll();

// Fetch Latest Properties
$stmt = $pdo->query("SELECT * FROM real_estate_properties ORDER BY created_at DESC LIMIT 6");
$latest_properties = $stmt->fetchAll();

$page_title = "Real Estate - Find Your Dream Home in Ethiopia";
require_once '../includes/header.php';
?>

<style>
    .hero-real-estate {
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.7)), url('https://images.unsplash.com/photo-1560518883-ce09059eeffa?ixlib=rb-1.2.1&auto=format&fit=crop&w=1920&q=80');
        background-size: cover;
        background-position: center;
        height: 600px;
        display: flex;
        align-items: center;
        justify_content: center;
        color: white;
        text-align: center;
    }

    .search-box-transparent {
        background: rgba(255, 255, 255, 0.95);
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        max-width: 900px;
        width: 100%;
        margin: 0 auto;
    }

    .property-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        border: none;
        border-radius: 12px;
        overflow: hidden;
    }

    .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    .property-img-wrapper {
        position: relative;
        height: 250px;
        overflow: hidden;
    }

    .property-img-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .property-card:hover .property-img-wrapper img {
        transform: scale(1.05);
    }

    .price-badge {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(27, 94, 32, 0.9);
        color: white;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.9rem;
    }

    .status-badge {
        position: absolute;
        top: 20px;
        left: 20px;
        background: rgba(255, 193, 7, 0.9);
        color: #333;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.8rem;
        text-transform: uppercase;
    }

    .feature-icon {
        width: 60px;
        height: 60px;
        background: #e8f5e9;
        color: #1b5e20;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 15px;
        transition: 0.3s;
    }

    .category-card:hover .feature-icon {
        background: #1b5e20;
        color: white;
    }
</style>

<!-- Hero Section -->
<div class="hero-real-estate">
    <div class="container">
        <h1 class="display-3 fw-bold mb-4">Find Your Perfect Place</h1>
        <p class="lead mb-5 opacity-75">Discover the best properties for sale and rent across Ethiopia.</p>

        <div class="search-box-transparent text-start">
            <form action="properties.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label text-dark fw-bold small">Location</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i
                                class="fas fa-map-marker-alt text-primary"></i></span>
                        <input type="text" name="location" class="form-control border-start-0 ps-0"
                            placeholder="City, Area...">
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark fw-bold small">Property Type</label>
                    <select name="type" class="form-select">
                        <option value="">All Types</option>
                        <option value="apartment">Apartment</option>
                        <option value="house">House</option>
                        <option value="villa">Villa</option>
                        <option value="office">Office</option>
                        <option value="land">Land</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label text-dark fw-bold small">Purpose</label>
                    <div class="btn-group w-100" role="group">
                        <input type="radio" class="btn-check" name="status" id="btnradio1" value="rent" checked>
                        <label class="btn btn-outline-success" for="btnradio1">Rent</label>
                        <input type="radio" class="btn-check" name="status" id="btnradio2" value="sale">
                        <label class="btn btn-outline-success" for="btnradio2">Buy</label>
                    </div>
                </div>
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary-green w-100 py-2 fw-bold">
                        <i class="fas fa-search me-2"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Key Features / Benefits -->
<div class="container py-5 my-4">
    <div class="row g-4 text-center">
        <div class="col-md-4 category-card">
            <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                <div class="d-flex justify-content-center">
                    <div class="feature-icon"><i class="fas fa-home"></i></div>
                </div>
                <h4 class="fw-bold mb-3">Buy a Home</h4>
                <p class="text-muted">Find your dream home with our immersive photo experience and the most listings,
                    including things you won't find anywhere else.</p>
                <a href="properties.php?type=sale" class="btn btn-outline-dark rounded-pill px-4 mt-2">Browse Homes</a>
            </div>
        </div>
        <div class="col-md-4 category-card">
            <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                <div class="d-flex justify-content-center">
                    <div class="feature-icon"><i class="fas fa-key"></i></div>
                </div>
                <h4 class="fw-bold mb-3">Rent a Home</h4>
                <p class="text-muted">We’re creating a seamless online experience – from shopping on the largest rental
                    network, to applying, to paying rent.</p>
                <a href="properties.php?type=rent" class="btn btn-outline-dark rounded-pill px-4 mt-2">Find Rentals</a>
            </div>
        </div>
        <div class="col-md-4 category-card">
            <div class="p-4 rounded-4 bg-white shadow-sm h-100">
                <div class="d-flex justify-content-center">
                    <div class="feature-icon"><i class="fas fa-chart-line"></i></div>
                </div>
                <h4 class="fw-bold mb-3">See Opportunities</h4>
                <p class="text-muted">Explore commercial properties and investment opportunities with high ROI potential
                    across the country.</p>
                <a href="properties.php?category=commercial" class="btn btn-outline-dark rounded-pill px-4 mt-2">Explore
                    Commercial</a>
            </div>
        </div>
    </div>
</div>

<!-- Featured Properties Section -->
<section class="py-5 bg-light">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-5">
            <div>
                <h2 class="fw-bold display-6">Featured Properties</h2>
                <p class="text-muted mb-0">Handpicked properties just for you.</p>
            </div>
            <a href="properties.php" class="btn btn-link text-success fw-bold text-decoration-none">View All <i
                    class="fas fa-arrow-right ms-1"></i></a>
        </div>

        <div class="row g-4">
            <?php foreach ($featured_properties as $prop): ?>
                <div class="col-md-4">
                    <div class="card property-card h-100">
                        <div class="property-img-wrapper">
                            <img src="<?php echo htmlspecialchars($prop['image_url']); ?>"
                                alt="<?php echo htmlspecialchars($prop['title']); ?>">
                            <div class="price-badge">
                                <?php echo number_format($prop['price']); ?> ETB
                            </div>
                            <div class="status-badge">
                                <?php echo ucfirst($prop['type']); ?>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <div class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                <?php echo htmlspecialchars($prop['location']); ?>
                            </div>
                            <h5 class="card-title fw-bold mb-3 text-truncate">
                                <?php echo htmlspecialchars($prop['title']); ?>
                            </h5>
                            <p class="card-text text-muted small mb-3 text-truncate">
                                <?php echo htmlspecialchars($prop['description']); ?>
                            </p>

                            <div class="d-flex justify-content-between border-top pt-3">
                                <span class="small"><i class="fas fa-bed me-1"></i>
                                    <?php echo $prop['bedrooms']; ?> Beds
                                </span>
                                <span class="small"><i class="fas fa-bath me-1"></i>
                                    <?php echo $prop['bathrooms']; ?> Baths
                                </span>
                                <span class="small"><i class="fas fa-ruler-combined me-1"></i>
                                    <?php echo $prop['area_sqm']; ?> m²
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 p-3 pt-0">
                            <a href="details.php?id=<?php echo $prop['id']; ?>"
                                class="btn btn-outline-success w-100 rounded-pill">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- Call to Action -->
<section class="py-5 bg-dark text-white text-center" style="background: linear-gradient(45deg, #1b5e20, #2e7d32);">
    <div class="container py-4">
        <h2 class="display-5 fw-bold mb-3">Are you a Real Estate Agent?</h2>
        <p class="lead mb-4 opacity-75">Join our platform to list your properties and reach millions of potential
            buyers.</p>
        <a href="../broker/dashboard.php"
            class="btn btn-light rounded-pill btn-lg px-5 text-success fw-bold shadow">List Your Property</a>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>