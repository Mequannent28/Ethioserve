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

// Handle manual hotel addition removed - now handled in add_hotel.php

// Handle hotel deletion
if (isset($_GET['delete'])) {
    $hotel_id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([$hotel_id]);
    redirectWithMessage('manage_hotels.php', 'success', 'Hotel deleted successfully');
}

// Handle Bulk Import
if (isset($_FILES['hotel_csv']) && $_FILES['hotel_csv']['error'] === UPLOAD_ERR_OK) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $file = $_FILES['hotel_csv']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip header
            fgetcsv($handle, 1000, ",");
            $inserted = 0;
            $errors = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Format: Name, Location, Cuisine, Phone, Email
                $h_name = sanitize($data[0] ?? '');
                $h_loc = sanitize($data[1] ?? '');
                $h_cuisine = sanitize($data[2] ?? 'International');
                $owner_phone = sanitize($data[3] ?? '');
                $owner_email = sanitize($data[4] ?? '');

                if (empty($h_name) || empty($owner_email)) {
                    $errors++;
                    continue;
                }

                // Find or Create User
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$owner_email]);
                $user = $stmt->fetch();

                if (!$user) {
                    $username = strtolower(str_replace(' ', '', $h_name)) . rand(10, 99);
                    $password = password_hash('welcome123', PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'hotel')");
                    $stmt->execute([$username, $owner_email, $password, $h_name . " Owner", $owner_phone]);
                    $user_id = $pdo->lastInsertId();
                } else {
                    $user_id = $user['id'];
                }

                // Create Hotel
                $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, location, cuisine_type, status, rating) VALUES (?, ?, ?, ?, 'approved', 4.5)");
                $stmt->execute([$user_id, $h_name, $h_loc, $h_cuisine]);
                $inserted++;
            }
            fclose($handle);
            redirectWithMessage('manage_hotels.php', 'success', "Successfully imported $inserted hotels! ($errors skipped)");
        }
    }
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
                <div class="d-flex gap-2">
                    <a href="add_hotel.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Hotel
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
                                                <div class="d-flex gap-2">
                                                    <a href="view_hotel.php?id=<?php echo $hotel['id']; ?>"
                                                        class="btn btn-sm btn-view-vivid btn-action" title="Full Page View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_hotel.php?id=<?php echo $hotel['id']; ?>"
                                                        class="btn btn-sm btn-edit-vivid btn-action" title="Full Page Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $hotel['id']; ?>"
                                                        class="btn btn-sm btn-delete-vivid btn-action"
                                                        onclick="return confirm('Are you sure you want to delete this hotel? This will also delete all menu items and orders.')"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Hotel Modal -->
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
                    <h5 class="modal-title fw-bold">Bulk Import Hotels</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">Upload a CSV file to register multiple hotels at once.</p>
                    <div class="bg-light p-3 rounded-4 mb-3 border">
                        <small class="text-muted d-block mb-1">Required CSV Columns:</small>
                        <code class="text-dark fw-bold">Name, Location, Cuisine, Phone, Email</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select CSV File</label>
                        <input type="file" name="hotel_csv" class="form-control" accept=".csv" required>
                    </div>
                    <div class="alert alert-info border-0 py-2 small mb-0">
                        <i class="fas fa-info-circle me-1"></i> New users will be created with temporary password:
                        <strong>welcome123</strong>
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

    </div>
    </form>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>