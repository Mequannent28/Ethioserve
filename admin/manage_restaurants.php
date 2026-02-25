<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int) $_POST['item_id'];
        $status = sanitize($_POST['status']);
        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE restaurants SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            redirectWithMessage('manage_restaurants.php', 'success', 'Restaurant status updated');
        }
    }
}

// Handle manual restaurant addition removed - now handled in add_restaurant.php

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM restaurants WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_restaurants.php', 'success', 'Restaurant deleted successfully');
}

// Fetch all restaurants
$stmt = $pdo->query("SELECT r.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                     FROM restaurants r 
                     JOIN users u ON r.user_id = u.id 
                     ORDER BY CASE WHEN r.status = 'pending' THEN 1 WHEN r.status = 'approved' THEN 2 ELSE 3 END, r.name");
$items = $stmt->fetchAll();

// Handle Bulk Import
if (isset($_FILES['restaurant_csv']) && $_FILES['restaurant_csv']['error'] === UPLOAD_ERR_OK) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $file = $_FILES['restaurant_csv']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            fgetcsv($handle, 1000, ","); // Skip header
            $inserted = 0;
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $name = sanitize($data[0] ?? '');
                $loc = sanitize($data[1] ?? '');
                $cuisine = sanitize($data[2] ?? 'Ethiopian');
                $email = sanitize($data[3] ?? '');

                if (!empty($name) && !empty($email)) {
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    if (!$user) {
                        $pwd = password_hash('welcome123', PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'restaurant')");
                        $stmt->execute([strtolower(str_replace(' ', '', $name)) . rand(10, 99), $email, $pwd, $name . " Owner"]);
                        $user_id = $pdo->lastInsertId();
                    } else {
                        $user_id = $user['id'];
                    }

                    $stmt = $pdo->prepare("INSERT INTO restaurants (user_id, name, address, cuisine_type, status) VALUES (?, ?, ?, ?, 'approved')");
                    $stmt->execute([$user_id, $name, $loc, $cuisine]);
                    $inserted++;
                }
            }
            fclose($handle);
            redirectWithMessage('manage_restaurants.php', 'success', "Imported $inserted restaurants.");
        }
    }
}

$pending = count(array_filter($items, fn($i) => $i['status'] === 'pending'));
$approved = count(array_filter($items, fn($i) => $i['status'] === 'approved'));
$rejected = count(array_filter($items, fn($i) => $i['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Restaurants - Admin</title>
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
                    <h2 class="fw-bold mb-1"><i class="fas fa-utensils text-primary-green me-2"></i>Manage Restaurants
                    </h2>
                    <p class="text-muted mb-0">Approve, reject, or manage restaurant registrations</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="add_restaurant.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Restaurant
                    </a>
                    <button type="button" class="btn btn-outline-primary-green rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#importModal">
                        <i class="fas fa-file-excel me-2"></i>Import Excel
                    </button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Pending</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $pending; ?>
                                </h3>
                            </div>
                            <i class="fas fa-clock fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Approved</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $approved; ?>
                                </h3>
                            </div>
                            <i class="fas fa-check-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-danger text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Rejected</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $rejected; ?>
                                </h3>
                            </div>
                            <i class="fas fa-times-circle fs-1 opacity-50"></i>
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
                                <th class="px-4">Restaurant</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-utensils fs-1 mb-3 d-block"></i>
                                        No restaurants registered yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=100'); ?>"
                                                    class="rounded-3" width="50" height="50" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <?php echo htmlspecialchars($item['cuisine_type'] ?? 'Restaurant'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($item['owner_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($item['owner_email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-danger me-1 small"></i>
                                            <?php echo htmlspecialchars($item['location']); ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <?php echo number_format($item['rating'], 1); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($item['status']); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <?php if ($item['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" name="status" value="approved"
                                                        class="btn btn-sm btn-success rounded-pill px-3 me-1">
                                                        <i class="fas fa-check me-1"></i> Approve
                                                    </button>
                                                    <button type="submit" name="status" value="rejected"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="fas fa-times me-1"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="d-flex gap-2">
                                                    <a href="view_restaurant.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-view-vivid btn-action" title="Full Page View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_restaurant.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-edit-vivid btn-action" title="Full Page Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-delete-vivid btn-action"
                                                        onclick="return confirm('Delete this restaurant?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
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

    <!-- Import Modal -->
    <div class="modal fade" id="importModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content border-0 rounded-4">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Bulk Import Restaurants</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="bg-light p-3 rounded-4 mb-3 border">
                        <small class="text-muted d-block mb-1">Required CSV Columns:</small>
                        <code class="text-dark fw-bold">Name, Location, Cuisine, Email</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select CSV File</label>
                        <input type="file" name="restaurant_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-upload me-2"></i>Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Add Restaurant Modal removed - handled in add_restaurant.php -->
</body>

</html>