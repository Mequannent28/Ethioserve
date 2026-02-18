<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Handle search and filtering
$search = sanitize($_GET['search'] ?? '');
$filter_category = sanitize($_GET['category'] ?? '');
$filter_condition = sanitize($_GET['condition'] ?? '');

$query = "SELECT em.*, u.username, u.full_name as seller_name FROM exchange_materials em 
          JOIN users u ON em.user_id = u.id 
          WHERE em.status = 'available'";

$params = [];
if (!empty($search)) {
    $query .= " AND (em.title LIKE ? OR em.description LIKE ?)";
    $params[] = "%$search%";
}
if (!empty($filter_category)) {
    $query .= " AND em.category = ?";
    $params[] = $filter_category;
}
if (!empty($filter_condition)) {
    $query .= " AND em.condition = ?";
    $params[] = $filter_condition;
}

$query .= " ORDER BY em.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$materials = $stmt->fetchAll();

// Get categories for the filter
$categories = ['Mobile', 'Computers', 'Electronics', 'Furniture', 'Vehicles', 'Others'];

include '../includes/header.php';
?>

<div class="exchange-hero py-5 mb-5 text-white" style="background: linear-gradient(135deg, #5C6BC0 0%, #3949AB 100%);">
    <div class="container text-center py-4">
        <h1 class="display-4 fw-bold mb-3">Exchange Materials</h1>
        <p class="lead mb-4">Sell what you don't need, buy what you love. Simple, fast, and local.</p>

        <div class="search-box-wrapper mx-auto" style="max-width: 800px;">
            <form action="exchange_material.php" method="GET" class="row g-2">
                <div class="col-md-8">
                    <div class="input-group input-group-lg shadow-sm">
                        <span class="input-group-text bg-white border-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-0"
                            placeholder="Search for mobiles, computers, and more..."
                            value="<?php echo htmlspecialchars($search); ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="category" class="form-select form-select-lg border-0 shadow-sm">
                        <option value="">Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat; ?>" <?php echo $filter_category == $cat ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-warning btn-lg w-100 fw-bold shadow-sm">Search</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="container mb-5">
    <div class="row align-items-center mb-4">
        <div class="col-md-6">
            <h3 class="fw-bold mb-0">Discover Listings</h3>
            <p class="text-muted">
                <?php echo count($materials); ?> items available to buy
            </p>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="post_material.php" class="btn btn-primary-green btn-lg rounded-pill px-4 fw-bold shadow-sm">
                <i class="fas fa-plus-circle me-2"></i>Post for Sale
            </a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Sidebar Filters -->
        <div class="col-lg-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-4">Filters</h5>

                <form action="exchange_material.php" method="GET">
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Category</label>
                        <div class="category-filters">
                            <a href="exchange_material.php?search=<?php echo urlencode($search); ?>"
                                class="filter-item <?php echo empty($filter_category) ? 'active' : ''; ?>">All
                                Categories</a>
                            <?php foreach ($categories as $cat): ?>
                                <a href="exchange_material.php?category=<?php echo urlencode($cat); ?>&search=<?php echo urlencode($search); ?>"
                                    class="filter-item <?php echo $filter_category == $cat ? 'active' : ''; ?>">
                                    <?php echo $cat; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-uppercase text-muted">Condition</label>
                        <div class="d-flex gap-2">
                            <a href="exchange_material.php?condition=new&category=<?php echo urlencode($filter_category); ?>&search=<?php echo urlencode($search); ?>"
                                class="btn btn-outline-secondary btn-sm flex-grow-1 <?php echo $filter_condition == 'new' ? 'active' : ''; ?>">New</a>
                            <a href="exchange_material.php?condition=used&category=<?php echo urlencode($filter_category); ?>&search=<?php echo urlencode($search); ?>"
                                class="btn btn-outline-secondary btn-sm flex-grow-1 <?php echo $filter_condition == 'used' ? 'active' : ''; ?>">Used</a>
                        </div>
                    </div>

                    <a href="exchange_material.php" class="btn btn-light w-100 rounded-pill">Reset All</a>
                </form>
            </div>
        </div>

        <!-- Material Grid -->
        <div class="col-lg-9">
            <?php if (empty($materials)): ?>
                <div class="text-center py-5 card border-0 shadow-sm rounded-4">
                    <div class="py-5">
                        <i class="fas fa-box-open text-muted mb-4" style="font-size: 5rem;"></i>
                        <h4 class="text-muted">No items found</h4>
                        <p class="text-muted">Try adjusting your filters or search terms</p>
                        <a href="exchange_material.php" class="btn btn-primary-green rounded-pill px-4">See All Items</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($materials as $item): ?>
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift material-card">
                                <div class="position-relative">
                                    <span
                                        class="badge <?php echo $item['condition'] == 'new' ? 'bg-success' : 'bg-warning text-dark'; ?> position-absolute top-0 start-0 m-3 z-1 rounded-pill">
                                        <?php echo ucfirst($item['condition']); ?>
                                    </span>
                                    <div class="material-img-wrapper" style="height: 200px;">
                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                                            class="w-100 h-100 object-fit-cover"
                                            alt="<?php echo htmlspecialchars($item['title']); ?>">
                                    </div>
                                </div>
                                <div class="card-body p-4">
                                    <h6 class="text-primary-green fw-bold small text-uppercase mb-1">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </h6>
                                    <h5 class="card-title fw-bold mb-2 text-truncate">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </h5>
                                    <h4 class="fw-extrabold text-primary mb-3">
                                        <?php echo number_format($item['price'], 2); ?> <small
                                            class="text-muted small fs-6">ETB</small>
                                    </h4>

                                    <div class="d-flex align-items-center text-muted small mb-3">
                                        <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                        <?php echo htmlspecialchars($item['location']); ?>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                        <span class="small text-muted">
                                            <i class="fas fa-user me-1"></i>
                                            <a href="seller_profile.php?id=<?php echo $item['user_id']; ?>"
                                                class="text-decoration-none text-muted">

                                                <?php echo htmlspecialchars($item['seller_name']); ?>
                                                (@<?php echo htmlspecialchars($item['username']); ?>)
                                            </a>
                                        </span>
                                        <a href="view_material.php?id=<?php echo $item['id']; ?>"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3">View Details</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .filter-item {
        display: block;
        padding: 10px 15px;
        border-radius: 12px;
        color: #444;
        text-decoration: none;
        margin-bottom: 5px;
        transition: all 0.2s;
    }

    .filter-item:hover {
        background: #f8f9fa;
        color: #5C6BC0;
    }

    .filter-item.active {
        background: #E8EAF6;
        color: #3949AB;
        font-weight: 700;
    }

    .material-card {
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .material-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1) !important;
    }

    .object-fit-cover {
        object-fit: cover;
    }
</style>

<?php include '../includes/footer.php'; ?>