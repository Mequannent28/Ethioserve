<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('admin');

// Handle hotel status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $hotel_id = (int) $_POST['hotel_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE hotels SET status = ? WHERE id = ?");
            $stmt->execute([$status, $hotel_id]);
            redirectWithMessage('manage_hotels.php', 'success', 'Hotel status updated successfully');
        }
    }
}

// Handle hotel edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_hotel'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $hotel_id = (int) $_POST['hotel_id'];
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $cuisine_type = sanitize($_POST['cuisine_type'] ?? '');
        $rating = floatval($_POST['rating'] ?? 0);
        $status = sanitize($_POST['status']);

        if (in_array($status, ['approved', 'rejected', 'pending'])) {
            $stmt = $pdo->prepare("UPDATE hotels SET name = ?, location = ?, cuisine_type = ?, rating = ?, status = ? WHERE id = ?");
            $stmt->execute([$name, $location, $cuisine_type, $rating, $status, $hotel_id]);
            redirectWithMessage('manage_hotels.php', 'success', 'Hotel updated successfully');
        }
    }
}

// Handle hotel deletion
if (isset($_GET['delete'])) {
    $hotel_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([$hotel_id]);
    redirectWithMessage('manage_hotels.php', 'success', 'Hotel deleted successfully');
}

// Fetch all hotels with their owner names
$stmt = $pdo->query("SELECT h.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                     FROM hotels h 
                     JOIN users u ON h.user_id = u.id 
                     ORDER BY 
                     CASE 
                         WHEN h.status = 'pending' THEN 1 
                         WHEN h.status = 'approved' THEN 2 
                         ELSE 3 
                     END,
                     h.name");
$hotels = $stmt->fetchAll();

// Count by status
$pending_count = 0;
$approved_count = 0;
$rejected_count = 0;
foreach ($hotels as $h) {
    if ($h['status'] === 'pending')
        $pending_count++;
    elseif ($h['status'] === 'approved')
        $approved_count++;
    else
        $rejected_count++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hotels - Admin</title>
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
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Manage Hotels</h2>
                    <p class="text-muted">Approve, reject, or manage hotel registrations</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Pending Approval</p>
                                <h3 class="fw-bold mb-0"><?php echo $pending_count; ?></h3>
                            </div>
                            <i class="fas fa-clock fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Approved</p>
                                <h3 class="fw-bold mb-0"><?php echo $approved_count; ?></h3>
                            </div>
                            <i class="fas fa-check-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-danger text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Rejected</p>
                                <h3 class="fw-bold mb-0"><?php echo $rejected_count; ?></h3>
                            </div>
                            <i class="fas fa-times-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hotels Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Hotel</th>
                                <th>Owner</th>
                                <th>Location</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($hotels)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-hotel fs-1 mb-3 d-block"></i>
                                            No hotels registered yet
                                        </td>
                                    </tr>
                            <?php else: ?>
                                    <?php foreach ($hotels as $hotel): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <div class="d-flex align-items-center gap-3">
                                                        <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=100'); ?>"
                                                            class="rounded-3" width="50" height="50" style="object-fit: cover;">
                                                        <div>
                                                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($hotel['name']); ?>
                                                            </h6>
                                                            <span class="text-muted small">
                                                                <?php echo htmlspecialchars($hotel['cuisine_type'] ?? 'Restaurant'); ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($hotel['owner_name']); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($hotel['owner_email']); ?>
                                                    </div>
                                                    <?php if ($hotel['owner_phone']): ?>
                                                            <div class="text-muted small"><i
                                                                    class="fas fa-phone me-1"></i><?php echo htmlspecialchars($hotel['owner_phone']); ?>
                                                            </div>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <i class="fas fa-map-marker-alt text-danger me-2 small"></i>
                                                    <?php echo htmlspecialchars($hotel['location']); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-star text-warning me-1"></i>
                                                        <?php echo number_format($hotel['rating'], 1); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($hotel['status'] === 'approved'): ?>
                                                            <span class="badge bg-success">Approved</span>
                                                    <?php elseif ($hotel['status'] === 'pending'): ?>
                                                            <span class="badge bg-warning text-dark">Pending</span>
                                                    <?php else: ?>
                                                            <span class="badge bg-danger">Rejected</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end px-4">
                                                    <?php if ($hotel['status'] === 'pending'): ?>
                                                            <form method="POST" class="d-inline">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
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
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#viewHotel<?php echo $hotel['id']; ?>"
                                                                    title="View Details">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                                    data-bs-toggle="modal"
                                                                    data-bs-target="#editHotel<?php echo $hotel['id']; ?>"
                                                                    title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <a href="?delete=<?php echo $hotel['id']; ?>"
                                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                                    onclick="return confirm('Are you sure you want to delete this hotel? This will also delete all menu items and orders.')"
                                                                    title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </a>
                                                            </div>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- View Hotel Modal -->
                                            <div class="modal fade" id="viewHotel<?php echo $hotel['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered modal-lg">
                                                    <div class="modal-content border-0 rounded-4">
                                                        <div class="modal-header border-0 pb-0">
                                                            <h5 class="modal-title fw-bold"><i class="fas fa-hotel text-primary-green me-2"></i>Hotel Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body p-4">
                                                            <div class="row g-4">
                                                                <div class="col-md-4 text-center">
                                                                    <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1566073771259-6a8506099945?w=300'); ?>"
                                                                        class="rounded-4 shadow-sm w-100" style="height:200px;object-fit:cover;">
                                                                    <div class="mt-3">
                                                                        <?php if ($hotel['status'] === 'approved'): ?>
                                                                                <span class="badge bg-success px-3 py-2">Approved</span>
                                                                        <?php elseif ($hotel['status'] === 'pending'): ?>
                                                                                <span class="badge bg-warning text-dark px-3 py-2">Pending</span>
                                                                        <?php else: ?>
                                                                                <span class="badge bg-danger px-3 py-2">Rejected</span>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <div class="col-md-8">
                                                                    <h4 class="fw-bold mb-3"><?php echo htmlspecialchars($hotel['name']); ?></h4>
                                                                    <div class="mb-2">
                                                                        <span class="text-muted"><i class="fas fa-utensils me-2"></i>Cuisine:</span>
                                                                        <strong><?php echo htmlspecialchars($hotel['cuisine_type'] ?? 'Restaurant'); ?></strong>
                                                                    </div>
                                                                    <div class="mb-2">
                                                                        <span class="text-muted"><i class="fas fa-map-marker-alt me-2"></i>Location:</span>
                                                                        <strong><?php echo htmlspecialchars($hotel['location']); ?></strong>
                                                                    </div>
                                                                    <div class="mb-2">
                                                                        <span class="text-muted"><i class="fas fa-star me-2 text-warning"></i>Rating:</span>
                                                                        <strong><?php echo number_format($hotel['rating'], 1); ?> / 5.0</strong>
                                                                    </div>
                                                                    <hr>
                                                                    <h6 class="fw-bold text-muted mb-2"><i class="fas fa-user me-2"></i>Owner Information</h6>
                                                                    <div class="mb-1"><strong><?php echo htmlspecialchars($hotel['owner_name']); ?></strong></div>
                                                                    <div class="mb-1 text-muted"><i class="fas fa-envelope me-2"></i><?php echo htmlspecialchars($hotel['owner_email']); ?></div>
                                                                    <?php if ($hotel['owner_phone']): ?>
                                                                            <div class="text-muted"><i class="fas fa-phone me-2"></i><?php echo htmlspecialchars($hotel['owner_phone']); ?></div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer border-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Close</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Edit Hotel Modal -->
                                            <div class="modal fade" id="editHotel<?php echo $hotel['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content border-0 rounded-4">
                                                        <div class="modal-header border-0">
                                                            <h5 class="modal-title fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Hotel</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                                            <input type="hidden" name="edit_hotel" value="1">
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">Hotel Name</label>
                                                                    <input type="text" name="name" class="form-control rounded-pill bg-light border-0 px-4"
                                                                        value="<?php echo htmlspecialchars($hotel['name']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">Location</label>
                                                                    <input type="text" name="location" class="form-control rounded-pill bg-light border-0 px-4"
                                                                        value="<?php echo htmlspecialchars($hotel['location']); ?>" required>
                                                                </div>
                                                                <div class="row g-3 mb-3">
                                                                    <div class="col-md-6">
                                                                        <label class="form-label small fw-bold">Cuisine Type</label>
                                                                        <input type="text" name="cuisine_type" class="form-control rounded-pill bg-light border-0 px-4"
                                                                            value="<?php echo htmlspecialchars($hotel['cuisine_type'] ?? ''); ?>">
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <label class="form-label small fw-bold">Rating</label>
                                                                        <input type="number" name="rating" class="form-control rounded-pill bg-light border-0 px-4"
                                                                            value="<?php echo $hotel['rating']; ?>" min="0" max="5" step="0.1">
                                                                    </div>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-bold">Status</label>
                                                                    <select name="status" class="form-select rounded-pill bg-light border-0 px-4">
                                                                        <option value="approved" <?php echo $hotel['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                        <option value="pending" <?php echo $hotel['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                        <option value="rejected" <?php echo $hotel['status'] === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer border-0">
                                                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary-green rounded-pill px-4">Save Changes</button>
                                                            </div>
                                                        </form>
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