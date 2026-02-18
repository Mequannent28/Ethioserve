<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('admin');

// Handle property status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $property_id = (int) $_POST['property_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['available', 'sold', 'rented', 'pending'])) {
            $stmt = $pdo->prepare("UPDATE real_estate_properties SET status = ? WHERE id = ?");
            $stmt->execute([$status, $property_id]);
            redirectWithMessage('manage_realestate.php', 'success', 'Property status updated successfully');
        }
    }
}

// Handle property deletion
if (isset($_GET['delete'])) {
    $property_id = (int) $_GET['delete'];
    // Delete associated images or just relies on DB cascade? 
    // Ideally delete files, but for now just DB delete.
    $stmt = $pdo->prepare("DELETE FROM real_estate_properties WHERE id = ?");
    $stmt->execute([$property_id]);
    redirectWithMessage('manage_realestate.php', 'success', 'Property deleted successfully');
}

// Fetch all properties with agent info
$stmt = $pdo->query("SELECT p.*, u.full_name as agent_name, u.email as agent_email, u.phone as agent_phone 
                     FROM real_estate_properties p 
                     JOIN users u ON p.agent_id = u.id 
                     ORDER BY p.created_at DESC");
$properties = $stmt->fetchAll();

// Count by status
$available_count = 0;
$sold_rented_count = 0;
$pending_count = 0;

foreach ($properties as $p) {
    if ($p['status'] === 'available')
        $available_count++;
    elseif ($p['status'] === 'pending')
        $pending_count++;
    else
        $sold_rented_count++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Real Estate - Admin</title>
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
                    <h2 class="fw-bold mb-0">Manage Real Estate</h2>
                    <p class="text-muted">Oversee property listings and agent activities</p>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Available Listings</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $available_count; ?>
                                </h3>
                            </div>
                            <i class="fas fa-home fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm p-3 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Sold / Rented</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $sold_rented_count; ?>
                                </h3>
                            </div>
                            <i class="fas fa-handshake fs-1 opacity-50"></i>
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

            <!-- Properties Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Property</th>
                                <th>Agent</th>
                                <th>Location</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($properties)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-city fs-1 mb-3 d-block"></i>
                                        No properties found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($properties as $prop): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($prop['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                                                    class="rounded-3" width="60" height="60" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold text-truncate" style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($prop['title']); ?>
                                                    </h6>
                                                    <span class="text-muted small badge bg-light text-dark border">
                                                        <?php echo ucfirst($prop['category']); ?> •
                                                        <?php echo ucfirst($prop['type']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($prop['agent_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($prop['agent_phone']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <i class="fas fa-map-marker-alt text-danger me-2 small"></i>
                                            <?php echo htmlspecialchars($prop['location']); ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-primary-green">
                                                <?php echo number_format($prop['price']); ?> ETB
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($prop['status'] === 'available'): ?>
                                                <span class="badge bg-success">Available</span>
                                            <?php elseif ($prop['status'] === 'sold'): ?>
                                                <span class="badge bg-danger">Sold</span>
                                            <?php elseif ($prop['status'] === 'rented'): ?>
                                                <span class="badge bg-info">Rented</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#viewProp<?php echo $prop['id']; ?>"
                                                    title="View Details">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                    data-bs-toggle="modal" data-bs-target="#editProp<?php echo $prop['id']; ?>"
                                                    title="Edit Status">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="?delete=<?php echo $prop['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Are you sure you want to delete this property?')"
                                                    title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- View/Edit Status Modal -->
                                    <div class="modal fade" id="editProp<?php echo $prop['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0">
                                                    <h5 class="modal-title fw-bold">Update Property Status</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="property_id" value="<?php echo $prop['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <div class="modal-body p-4">
                                                        <div class="mb-3">
                                                            <label class="form-label small fw-bold">Current Status</label>
                                                            <select name="status" class="form-select rounded-pill">
                                                                <option value="available" <?php echo $prop['status'] === 'available' ? 'selected' : ''; ?>
                                                                    >Available</option>
                                                                <option value="sold" <?php echo $prop['status'] === 'sold' ? 'selected' : ''; ?>>Sold</option>
                                                                <option value="rented" <?php echo $prop['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                                                <option value="pending" <?php echo $prop['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
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

                                    <!-- View Details Modal (Simplified) -->
                                    <div class="modal fade" id="viewProp<?php echo $prop['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0 bg-light">
                                                    <h5 class="modal-title fw-bold">
                                                        <?php echo htmlspecialchars($prop['title']); ?>
                                                    </h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-0">
                                                    <img src="<?php echo htmlspecialchars($prop['image_url']); ?>" class="w-100"
                                                        style="height: 300px; object-fit: cover;">
                                                    <div class="p-4">
                                                        <div class="row">
                                                            <div class="col-md-8">
                                                                <p>
                                                                    <?php echo htmlspecialchars($prop['description']); ?>
                                                                </p>
                                                                <div class="row g-3">
                                                                    <div class="col-6"><i class="fas fa-bed me-2"></i>
                                                                        <?php echo $prop['bedrooms']; ?> Beds
                                                                    </div>
                                                                    <div class="col-6"><i class="fas fa-bath me-2"></i>
                                                                        <?php echo $prop['bathrooms']; ?> Baths
                                                                    </div>
                                                                    <div class="col-6"><i
                                                                            class="fas fa-ruler-combined me-2"></i>
                                                                        <?php echo $prop['area_sqm']; ?> sqm
                                                                    </div>
                                                                    <div class="col-6"><i
                                                                            class="fas fa-map-marker-alt me-2"></i>
                                                                        <?php echo htmlspecialchars($prop['city']); ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            <div class="col-md-4 border-start">
                                                                <h6>Agent Info</h6>
                                                                <p class="mb-1 fw-bold">
                                                                    <?php echo htmlspecialchars($prop['agent_name']); ?>
                                                                </p>
                                                                <p class="mb-1 small">
                                                                    <?php echo htmlspecialchars($prop['agent_email']); ?>
                                                                </p>
                                                                <p class="mb-1 small">
                                                                    <?php echo htmlspecialchars($prop['agent_phone']); ?>
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