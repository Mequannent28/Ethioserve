<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('admin');

// Handle item status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $item_id = (int) $_POST['item_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['available', 'sold', 'hidden', 'pending'])) {
            $stmt = $pdo->prepare("UPDATE exchange_materials SET status = ? WHERE id = ?");
            $stmt->execute([$status, $item_id]);
            redirectWithMessage('manage_exchange.php', 'success', 'Item status updated successfully');
        }
    }
}

// Handle item deletion
if (isset($_GET['delete'])) {
    $item_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM exchange_materials WHERE id = ?");
    $stmt->execute([$item_id]);
    redirectWithMessage('manage_exchange.php', 'success', 'Item deleted successfully');
}

// Fetch all materials with seller info
$stmt = $pdo->query("SELECT em.*, u.full_name as seller_name, u.email as seller_email, u.phone as seller_phone 
                     FROM exchange_materials em 
                     JOIN users u ON em.user_id = u.id 
                     ORDER BY em.created_at DESC");
$materials = $stmt->fetchAll();

// Count by status
$available_count = 0;
$sold_count = 0;
$pending_count = 0;

foreach ($materials as $m) {
    if ($m['status'] === 'available')
        $available_count++;
    elseif ($m['status'] === 'pending')
        $pending_count++;
    elseif ($m['status'] === 'sold')
        $sold_count++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Exchange - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            width: 100%;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                width: 100%;
            }
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
                    <h2 class="fw-bold mb-0">Manage Exchange Material</h2>
                    <p class="text-muted">Review and manage community marketplace listings</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Active Listings</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $available_count; ?>
                                </h3>
                            </div>
                            <i class="fas fa-boxes fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Items Sold</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $sold_count; ?>
                                </h3>
                            </div>
                            <i class="fas fa-check-double fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Pending Review</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $pending_count; ?>
                                </h3>
                            </div>
                            <i class="fas fa-clock fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Materials Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Item</th>
                                <th>Seller</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($materials)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-search fs-1 mb-3 d-block"></i>
                                        No items found in marketplace.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($materials as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                                                    class="rounded-3" width="60" height="60" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <?php echo ucfirst($item['condition']); ?>
                                                    </small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($item['seller_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($item['seller_phone']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($item['category']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary-green">
                                                <?php echo number_format($item['price']); ?> ETB
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($item['status'] === 'available'): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php elseif ($item['status'] === 'sold'): ?>
                                                <span class="badge bg-danger">Sold</span>
                                            <?php elseif ($item['status'] === 'hidden'): ?>
                                                <span class="badge bg-secondary">Hidden</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#viewItem<?php echo $item['id']; ?>"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#editItem<?php echo $item['id']; ?>"
                                                    title="Edit Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete=<?php echo $item['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Are you sure you want to delete this item?')"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Edit Status Modal -->
                                    <div class="modal fade" id="editItem<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Update Item Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Adjust Status</label>
                                                            <select name="status" class="form-select rounded-pill">
                                                                <option value="available" <?php echo $item['status'] === 'available' ? 'selected' : ''; ?>
                                                                    >Available</option>
                                                                <option value="sold" <?php echo $item['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                                <option value="hidden" <?php echo $item['status'] === 'hidden' ? 'selected' : ''; ?>>Hidden</option>
                                                                <option value="pending" <?php echo $item['status'] === 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer border-0">
                                                        <button type="button" class="btn btn-light rounded-pill px-4"
                                                            data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit"
                                                            class="btn btn-primary-green rounded-pill px-4">Update
                                                            Status</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View Details Modal -->
                                    <div class="modal fade" id="viewItem<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content border-0 rounded-4 shadow">
                                                <div class="modal-header border-0 bg-light">
                                                    <h5 class="modal-title fw-bold">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <div class="row g-0">
                                                        <div class="col-md-5">
                                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                                                                class="w-100 h-100"
                                                                style="object-fit: cover; min-height: 300px;">
                                                        </div>
                                                        <div class="col-md-7 p-4">
                                                            <div class="mb-3">
                                                                <span class="badge bg-primary-green mb-2">
                                                                    <?php echo $item['category']; ?>
                                                                </span>
                                                                <h6 class="fw-bold fs-4 text-primary">
                                                                    <?php echo number_format($item['price']); ?> ETB
                                                                </h6>
                                                            </div>
                                                            <p class="text-muted">
                                                                <?php echo htmlspecialchars($item['description']); ?>
                                                            </p>
                                                            <hr>
                                                            <div class="row small g-2">
                                                                <div class="col-6"><strong>Condition:</strong>
                                                                    <?php echo ucfirst($item['condition']); ?>
                                                                </div>
                                                                <div class="col-6"><strong>Location:</strong>
                                                                    <?php echo htmlspecialchars($item['location']); ?>
                                                                </div>
                                                                <div class="col-6"><strong>Date Posted:</strong>
                                                                    <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                                                </div>
                                                            </div>
                                                            <div class="mt-4 p-3 bg-light rounded-3">
                                                                <h6 class="fw-bold mb-2">Seller Contact Info</h6>
                                                                <p class="mb-1"><i class="fas fa-user me-2 text-muted"></i>
                                                                    <?php echo htmlspecialchars($item['seller_name']); ?>
                                                                </p>
                                                                <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i>
                                                                    <?php echo htmlspecialchars($item['seller_phone']); ?>
                                                                </p>
                                                                <p class="mb-0"><i class="fas fa-envelope me-2 text-muted"></i>
                                                                    <?php echo htmlspecialchars($item['seller_email']); ?>
                                                                </p>
                                                            </div>
                                                        </div>
                                                    </div>
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