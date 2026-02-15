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
        redirectWithMessage('manage_home.php', 'success', 'Service listing deleted');
    } catch (Exception $e) {
    }
}

// Handle add home service
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_service'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description'] ?? '');
        $price = (float) $_POST['price'];
        $location = sanitize($_POST['location']);
        $image_url = sanitize($_POST['image_url'] ?? '');

        try {
            $stmt = $pdo->prepare("INSERT INTO listings (user_id, type, title, description, price, location, image_url, status) VALUES (?, 'home_service', ?, ?, ?, ?, ?, 'available')");
            $stmt->execute([getCurrentUserId(), $title, $description, $price, $location, $image_url]);
            redirectWithMessage('manage_home.php', 'success', 'Home service added!');
        } catch (Exception $e) {
            redirectWithMessage('manage_home.php', 'error', 'Failed: ' . $e->getMessage());
        }
    }
}

// Fetch home service listings
$items = [];
try {
    $stmt = $pdo->query("SELECT l.*, u.full_name as owner_name, u.email as owner_email 
                         FROM listings l 
                         LEFT JOIN users u ON l.user_id = u.id 
                         WHERE l.type = 'home_service' 
                         ORDER BY l.created_at DESC");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$available = count(array_filter($items, fn($i) => $i['status'] === 'available'));
$taken = count(array_filter($items, fn($i) => $i['status'] === 'taken'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Home Services - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .stat-card {
            transition: transform 0.3s;
            border-radius: 15px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-wrench text-info me-2"></i>Manage Home Services</h2>
                    <p class="text-muted mb-0">Plumbing, electrical, cleaning, and other home services</p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Service
                </button>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Listings</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo count($items); ?>
                                </h3>
                            </div>
                            <i class="fas fa-wrench fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Available</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $available; ?>
                                </h3>
                            </div>
                            <i class="fas fa-check-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Taken/Booked</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $taken; ?>
                                </h3>
                            </div>
                            <i class="fas fa-calendar-check fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Service</th>
                                <th>Posted By</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-wrench fs-1 mb-3 d-block"></i>No home services listed yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1581578731548-c64695cc6952?w=100'); ?>"
                                                    class="rounded-3" width="50" height="50" style="object-fit:cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <?php echo mb_strimwidth(htmlspecialchars($item['description'] ?? ''), 0, 40, '...'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($item['owner_name'] ?? 'Admin'); ?>
                                        </td>
                                        <td class="small"><i class="fas fa-map-marker-alt text-danger me-1"></i>
                                            <?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo number_format($item['price']); ?> ETB
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($item['status']); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <a href="?delete=<?php echo $item['id']; ?>"
                                                class="btn btn-sm btn-outline-danger rounded-pill"
                                                onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Add Home Service</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-0">
                    <?php echo csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-12"><label class="form-label small fw-bold">Title</label>
                            <input type="text" name="title" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="e.g. Professional Plumbing">
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="500">
                        </div>
                        <div class="col-md-6"><label class="form-label small fw-bold">Location</label>
                            <input type="text" name="location" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="Addis Ababa">
                        </div>
                        <div class="col-12"><label class="form-label small fw-bold">Image URL</label>
                            <input type="url" name="image_url" class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                        <div class="col-12"><label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 px-4" rows="3"
                                style="border-radius:15px;"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_service"
                        class="btn btn-primary-green w-100 rounded-pill py-3 fw-bold mt-4 shadow">
                        <i class="fas fa-check-circle me-2"></i>Add Service
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>