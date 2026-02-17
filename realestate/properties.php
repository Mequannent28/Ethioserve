<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Filter Logic Used for Searching
$where_clauses = ["status = 'available'"];
$params = [];

if (!empty($_GET['location'])) {
    $where_clauses[] = "location LIKE ?";
    $params[] = "%" . $_GET['location'] . "%";
}

if (!empty($_GET['type'])) {
    $where_clauses[] = "category = ?";
    $params[] = $_GET['type'];
}

if (!empty($_GET['status'])) { // For rent/sale
    $where_clauses[] = "type = ?";
    $params[] = $_GET['status'];
}

// Build Query
$sql = "SELECT * FROM real_estate_properties";
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$properties = $stmt->fetchAll();

$page_title = "Browse Properties - Real Estate";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <!-- Search Bar (Compact) -->
    <div class="row mb-5">
        <div class="col-12">
            <form action="" method="GET" class="bg-white p-4 rounded-4 shadow-sm border">
                <div class="row g-3">
                    <div class="col-md-4">
                        <input type="text" name="location" class="form-control" placeholder="City, Location..."
                            value="<?php echo htmlspecialchars($_GET['location'] ?? ''); ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="type" class="form-select">
                            <option value="">All Types</option>
                            <option value="apartment" <?php echo ($_GET['type'] ?? '') == 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                            <option value="house" <?php echo ($_GET['type'] ?? '') == 'house' ? 'selected' : ''; ?>
                                >House</option>
                            <option value="villa" <?php echo ($_GET['type'] ?? '') == 'villa' ? 'selected' : ''; ?>
                                >Villa</option>
                            <option value="office" <?php echo ($_GET['type'] ?? '') == 'office' ? 'selected' : ''; ?>
                                >Office</option>
                            <option value="land" <?php echo ($_GET['type'] ?? '') == 'land' ? 'selected' : ''; ?>>Land
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-select">
                            <option value="">Any Purpose</option>
                            <option value="rent" <?php echo ($_GET['status'] ?? '') == 'rent' ? 'selected' : ''; ?>>For
                                Rent</option>
                            <option value="sale" <?php echo ($_GET['status'] ?? '') == 'sale' ? 'selected' : ''; ?>>For
                                Sale</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary-green w-100 fw-bold">Update</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <h3 class="fw-bold mb-4">
        <?php echo count($properties); ?> Properties Found
    </h3>

    <?php if (empty($properties)): ?>
        <div class="text-center py-5">
            <i class="fas fa-search fa-3x text-muted mb-3"></i>
            <h4 class="text-muted">No properties found matching your criteria.</h4>
            <a href="properties.php" class="btn btn-link">Clear Filters</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($properties as $prop): ?>
                <div class="col-md-4">
                    <div class="card h-100 hover-lift border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($prop['image_url']); ?>" class="card-img-top"
                                style="height: 250px; object-fit: cover;">
                            <span class="position-absolute top-0 end-0 m-3 badge bg-dark text-white p-2 px-3 rounded-pill">
                                <?php echo number_format($prop['price']); ?> ETB
                            </span>
                            <span
                                class="position-absolute top-0 left-0 m-3 badge bg-warning text-dark p-2 px-3 rounded-pill text-uppercase fw-bold">
                                <?php echo $prop['type']; ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="text-muted small mb-2 text-uppercase fw-bold text-success">
                                <?php echo ucfirst($prop['category']); ?>
                            </div>
                            <h5 class="card-title fw-bold mb-2">
                                <?php echo htmlspecialchars($prop['title']); ?>
                            </h5>
                            <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                <?php echo htmlspecialchars($prop['location']); ?>
                            </p>

                            <div class="d-flex justify-content-between text-muted small border-top pt-3">
                                <span><i class="fas fa-bed me-1"></i>
                                    <?php echo $prop['bedrooms']; ?> Beds
                                </span>
                                <span><i class="fas fa-bath me-1"></i>
                                    <?php echo $prop['bathrooms']; ?> Baths
                                </span>
                                <span><i class="fas fa-ruler-combined me-1"></i>
                                    <?php echo $prop['area_sqm']; ?> m²
                                </span>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 p-3">
                            <a href="details.php?id=<?php echo $prop['id']; ?>"
                                class="btn btn-outline-success w-100 rounded-pill fw-bold">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '../includes/footer.php'; ?>