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
            $stmt = $pdo->prepare("UPDATE hotels SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            redirectWithMessage('manage_restaurants.php', 'success', 'Restaurant status updated');
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_restaurants.php', 'success', 'Restaurant deleted successfully');
}

// Fetch all restaurants (from hotels table)
$stmt = $pdo->query("SELECT h.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                     FROM hotels h 
                     JOIN users u ON h.user_id = u.id 
                     ORDER BY CASE WHEN h.status = 'pending' THEN 1 WHEN h.status = 'approved' THEN 2 ELSE 3 END, h.name");
$items = $stmt->fetchAll();

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
                <span class="badge bg-primary-green fs-6 rounded-pill px-3 py-2">
                    <?php echo count($items); ?> Total
                </span>
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
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                        data-bs-toggle="modal" data-bs-target="#viewItem<?php echo $item['id']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-outline-danger rounded-pill"
                                                        onclick="return confirm('Delete this restaurant?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Modal -->
                                    <div class="modal fade" id="viewItem<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold"><i
                                                            class="fas fa-utensils text-primary-green me-2"></i>Restaurant
                                                        Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="text-center mb-3">
                                                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?w=300'); ?>"
                                                            class="rounded-4 shadow-sm"
                                                            style="width:100%;max-height:200px;object-fit:cover;">
                                                    </div>
                                                    <h4 class="fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </h4>
                                                    <p class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>
                                                        <?php echo htmlspecialchars($item['location']); ?>
                                                    </p>
                                                    <p><i class="fas fa-utensils me-2"></i>
                                                        <?php echo htmlspecialchars($item['cuisine_type'] ?? 'N/A'); ?>
                                                    </p>
                                                    <p><i class="fas fa-star text-warning me-2"></i>
                                                        <?php echo number_format($item['rating'], 1); ?> / 5.0
                                                    </p>
                                                    <hr>
                                                    <h6 class="fw-bold"><i class="fas fa-user me-2"></i>Owner</h6>
                                                    <p class="mb-1">
                                                        <?php echo htmlspecialchars($item['owner_name']); ?>
                                                    </p>
                                                    <p class="text-muted small">
                                                        <?php echo htmlspecialchars($item['owner_email']); ?>
                                                    </p>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn btn-light rounded-pill px-4"
                                                        data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>