<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM listings WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_rent.php', 'success', 'Listing deleted');
    } catch (Exception $e) {}
}

// Handle status toggle
if (isset($_GET['toggle_status'])) {
    $id = (int) $_GET['toggle_status'];
    try {
        $stmt = $pdo->prepare("UPDATE listings SET status = IF(status='available','taken','available') WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_rent.php', 'success', 'Listing status updated');
    } catch (Exception $e) {}
}

// Handle add listing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_listing'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $type = sanitize($_POST['type']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $price = (float) $_POST['price'];
        $location = sanitize($_POST['location']);
        $image_url = sanitize($_POST['image_url'] ?? '');
        $video_url = sanitize($_POST['video_url'] ?? '');
        $bedrooms = (int) ($_POST['bedrooms'] ?? 0);
        $bathrooms = (int) ($_POST['bathrooms'] ?? 0);
        $area_sqm = (int) ($_POST['area_sqm'] ?? 0);
        $features = sanitize($_POST['features'] ?? '');
        $contact_phone = sanitize($_POST['contact_phone'] ?? '');
        $contact_name = sanitize($_POST['contact_name'] ?? '');

        try {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, type, title, description, price, location, image_url, video_url, bedrooms, bathrooms, area_sqm, features, contact_phone, contact_name, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([getCurrentUserId(), $type, $title, $description, $price, $location, $image_url, $video_url, $bedrooms, $bathrooms, $area_sqm, $features, $contact_phone, $contact_name]);
            redirectWithMessage('manage_rent.php', 'success', 'Listing added!');
        } catch (Exception $e) {
            redirectWithMessage('manage_rent.php', 'error', 'Failed: ' . $e->getMessage());
        }
    }
}

// Ensure rental_requests table
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
} catch (Exception $e) {}

// Active tab
$filter = sanitize($_GET['type'] ?? 'all');

// Fetch listings
if ($filter === 'all') {
    $stmt = $pdo->query("SELECT l.*, u.full_name as owner_name, u.email as owner_email 
                         FROM listings l 
                         LEFT JOIN users u ON l.user_id = u.id 
                         WHERE l.type IN ('house_rent','car_rent')
                         ORDER BY l.created_at DESC");
} else {
    $stmt = $pdo->prepare("SELECT l.*, u.full_name as owner_name, u.email as owner_email 
                           FROM listings l 
                           LEFT JOIN users u ON l.user_id = u.id 
                           WHERE l.type = ?
                           ORDER BY l.created_at DESC");
    $stmt->execute([$filter]);
}
$items = $stmt->fetchAll();

// Get rental request counts
$request_counts = [];
try {
    $stmt = $pdo->query("SELECT listing_id, COUNT(*) as cnt FROM rental_requests GROUP BY listing_id");
    while ($row = $stmt->fetch()) {
        $request_counts[$row['listing_id']] = $row['cnt'];
    }
} catch (Exception $e) {}

// Get rental requests
$rental_requests = [];
try {
    $stmt = $pdo->query("SELECT rr.*, l.title as listing_title, l.type as listing_type, l.price, u.full_name as requester_name, u.email as requester_email, u.phone as requester_phone 
                         FROM rental_requests rr 
                         LEFT JOIN listings l ON rr.listing_id = l.id 
                         LEFT JOIN users u ON rr.customer_id = u.id
                         ORDER BY rr.created_at DESC
                         LIMIT 50");
    $rental_requests = $stmt->fetchAll();
} catch (Exception $e) {}

$houses = count(array_filter($items, fn($i) => $i['type'] === 'house_rent'));
$cars = count(array_filter($items, fn($i) => $i['type'] === 'car_rent'));
$available = count(array_filter($items, fn($i) => $i['status'] === 'available'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Rent - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { overflow-x: hidden; font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); padding: 30px; background-color: #f4f6f9; min-height: 100vh; }
        .stat-card { transition: transform 0.3s; border-radius: 15px; }
        .stat-card:hover { transform: translateY(-5px); }
        .filter-pill { padding: 8px 20px; border-radius: 50px; border: 2px solid #dee2e6; background: white; color: #666; font-weight: 600; text-decoration: none; transition: 0.3s; font-size: 0.85rem; }
        .filter-pill.active, .filter-pill:hover { background: #1B5E20; color: white; border-color: #1B5E20; }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-home text-success me-2"></i>Manage Rent</h2>
                    <p class="text-muted mb-0">House rent, car rent, and rental requests</p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Listing
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 small fw-bold">Total Listings</p><h3 class="fw-bold mb-0"><?php echo count($items); ?></h3></div>
                            <i class="fas fa-list fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 small fw-bold">Houses</p><h3 class="fw-bold mb-0"><?php echo $houses; ?></h3></div>
                            <i class="fas fa-home fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 small fw-bold">Cars</p><h3 class="fw-bold mb-0"><?php echo $cars; ?></h3></div>
                            <i class="fas fa-car fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div><p class="mb-0 small fw-bold">Requests</p><h3 class="fw-bold mb-0"><?php echo count($rental_requests); ?></h3></div>
                            <i class="fas fa-inbox fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="d-flex gap-2 mb-4">
                <a href="?type=all" class="filter-pill <?php echo $filter === 'all' ? 'active' : ''; ?>">All</a>
                <a href="?type=house_rent" class="filter-pill <?php echo $filter === 'house_rent' ? 'active' : ''; ?>"><i class="fas fa-home me-1"></i> Houses</a>
                <a href="?type=car_rent" class="filter-pill <?php echo $filter === 'car_rent' ? 'active' : ''; ?>"><i class="fas fa-car me-1"></i> Cars</a>
            </div>

            <!-- Listings Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white p-3 border-0">
                    <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary-green"></i>Rental Listings</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Listing</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Requests</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr><td colspan="7" class="text-center py-5 text-muted"><i class="fas fa-home fs-1 mb-3 d-block"></i>No rent listings yet</td></tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=100'); ?>"
                                                    class="rounded-3" width="55" height="45" style="object-fit:cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['title']); ?></h6>
                                                    <span class="text-muted small"><?php echo htmlspecialchars($item['owner_name'] ?? 'Admin'); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $item['type'] === 'house_rent' ? 'info' : 'warning'; ?> text-<?php echo $item['type'] === 'house_rent' ? 'white' : 'dark'; ?>">
                                                <i class="fas fa-<?php echo $item['type'] === 'house_rent' ? 'home' : 'car'; ?> me-1"></i>
                                                <?php echo $item['type'] === 'house_rent' ? 'House' : 'Car'; ?>
                                            </span>
                                        </td>
                                        <td class="small"><i class="fas fa-map-marker-alt text-danger me-1"></i><?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?></td>
                                        <td class="fw-bold"><?php echo number_format($item['price']); ?> ETB<span class="text-muted small">/<?php echo $item['type'] === 'car_rent' ? 'day' : 'mo'; ?></span></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($request_counts[$item['id']] ?? 0) > 0 ? 'success' : 'light text-dark'; ?>">
                                                <?php echo $request_counts[$item['id']] ?? 0; ?> requests
                                            </span>
                                        </td>
                                        <td><?php echo getStatusBadge($item['status']); ?></td>
                                        <td class="text-end px-4">
                                            <a href="?toggle_status=<?php echo $item['id']; ?>&type=<?php echo $filter; ?>" class="btn btn-sm btn-outline-warning rounded-pill" title="Toggle status"><i class="fas fa-sync-alt"></i></a>
                                            <a href="?delete=<?php echo $item['id']; ?>&type=<?php echo $filter; ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Rental Requests -->
            <?php if (!empty($rental_requests)): ?>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-3 border-0">
                    <h5 class="fw-bold mb-0"><i class="fas fa-inbox me-2 text-success"></i>Rental Requests</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Requester</th>
                                <th>Listing</th>
                                <th>Message</th>
                                <th>Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rental_requests as $rr): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="fw-bold"><?php echo htmlspecialchars($rr['requester_name'] ?? $rr['customer_name'] ?? 'N/A'); ?></div>
                                        <div class="text-muted small"><?php echo htmlspecialchars($rr['requester_phone'] ?? $rr['customer_phone'] ?? ''); ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($rr['listing_title'] ?? 'Deleted'); ?></span>
                                    </td>
                                    <td class="small"><?php echo mb_strimwidth(htmlspecialchars($rr['message'] ?? ''), 0, 60, '...'); ?></td>
                                    <td class="small text-muted"><?php echo date('M d, H:i', strtotime($rr['created_at'])); ?></td>
                                    <td><?php echo getStatusBadge($rr['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Listing Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Add Rental Listing</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-0">
                    <?php echo csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Type</label>
                            <select name="type" class="form-select rounded-pill bg-light border-0 px-4" id="rentType" onchange="toggleHouseFields()">
                                <option value="house_rent">House Rent</option>
                                <option value="car_rent">Car Rent</option>
                            </select>
                        </div>
                        <div class="col-12"><label class="form-label small fw-bold">Title</label>
                            <input type="text" name="title" class="form-control rounded-pill bg-light border-0 px-4" required placeholder="e.g. 3BR Apartment in Bole"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-pill bg-light border-0 px-4" placeholder="25000"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Location</label>
                            <input type="text" name="location" class="form-control rounded-pill bg-light border-0 px-4" placeholder="Bole, Addis Ababa"></div>
                        <div class="col-12"><label class="form-label small fw-bold">Image URL</label>
                            <input type="url" name="image_url" class="form-control rounded-pill bg-light border-0 px-4" placeholder="https://..."></div>
                        <div class="col-12"><label class="form-label small fw-bold">Video URL (YouTube embed)</label>
                            <input type="url" name="video_url" class="form-control rounded-pill bg-light border-0 px-4" placeholder="https://www.youtube.com/embed/..."></div>
                        <div id="houseFields">
                            <div class="row g-3">
                                <div class="col-4"><label class="form-label small fw-bold">Bedrooms</label>
                                    <input type="number" name="bedrooms" class="form-control rounded-pill bg-light border-0 px-4" placeholder="0"></div>
                                <div class="col-4"><label class="form-label small fw-bold">Bathrooms</label>
                                    <input type="number" name="bathrooms" class="form-control rounded-pill bg-light border-0 px-4" placeholder="0"></div>
                                <div class="col-4"><label class="form-label small fw-bold">Area (m²)</label>
                                    <input type="number" name="area_sqm" class="form-control rounded-pill bg-light border-0 px-4" placeholder="0"></div>
                            </div>
                        </div>
                        <div class="col-12"><label class="form-label small fw-bold">Features (comma-separated)</label>
                            <input type="text" name="features" class="form-control rounded-pill bg-light border-0 px-4" placeholder="Garden,Parking,Security"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Contact Name</label>
                            <input type="text" name="contact_name" class="form-control rounded-pill bg-light border-0 px-4" placeholder="Owner name"></div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control rounded-pill bg-light border-0 px-4" placeholder="+251..."></div>
                        <div class="col-12"><label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 px-4" rows="3" style="border-radius:15px;"></textarea></div>
                    </div>
                    <button type="submit" name="add_listing" class="btn btn-primary-green w-100 rounded-pill py-3 fw-bold mt-4 shadow">
                        <i class="fas fa-check-circle me-2"></i>Add Listing
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleHouseFields() {
            const type = document.getElementById('rentType').value;
            document.getElementById('houseFields').style.display = type === 'house_rent' ? 'block' : 'none';
        }
    </script>
</body>

</html>
