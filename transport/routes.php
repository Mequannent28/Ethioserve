<?php
require_once '../includes/functions.php';
requireLogin();
requireRole('transport');
require_once '../includes/db.php';

$user_id = getCurrentUserId();

// Get transport company
$stmt = $pdo->prepare("SELECT * FROM transport_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    redirectWithMessage('dashboard.php', 'error', 'Company profile not found');
}

// Handle route operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Add new route
        if (isset($_POST['add_route'])) {
            $origin = sanitize($_POST['origin']);
            $destination = sanitize($_POST['destination']);
            $distance = (float)$_POST['distance'];
            $estimated_hours = (float)$_POST['estimated_hours'];
            $base_price = (float)$_POST['base_price'];
            
            $stmt = $pdo->prepare("
                INSERT INTO routes (company_id, origin, destination, distance_km, estimated_hours, base_price, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->execute([$company['id'], $origin, $destination, $distance, $estimated_hours, $base_price]);
            redirectWithMessage('routes.php', 'success', 'Route added successfully');
        }
        
        // Update route
        if (isset($_POST['update_route'])) {
            $route_id = (int)$_POST['route_id'];
            $origin = sanitize($_POST['origin']);
            $destination = sanitize($_POST['destination']);
            $distance = (float)$_POST['distance'];
            $estimated_hours = (float)$_POST['estimated_hours'];
            $base_price = (float)$_POST['base_price'];
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE routes SET origin = ?, destination = ?, distance_km = ?, estimated_hours = ?, base_price = ?, is_active = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$origin, $destination, $distance, $estimated_hours, $base_price, $is_active, $route_id, $company['id']]);
            redirectWithMessage('routes.php', 'success', 'Route updated successfully');
        }
        
        // Delete route
        if (isset($_POST['delete_route'])) {
            $route_id = (int)$_POST['route_id'];
            
            // Check if route has active schedules
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE route_id = ? AND is_active = TRUE");
            $stmt->execute([$route_id]);
            if ($stmt->fetchColumn() > 0) {
                redirectWithMessage('routes.php', 'error', 'Cannot delete route with active schedules');
            }
            
            $stmt = $pdo->prepare("DELETE FROM routes WHERE id = ? AND company_id = ?");
            $stmt->execute([$route_id, $company['id']]);
            redirectWithMessage('routes.php', 'success', 'Route deleted successfully');
        }
    }
}

// Get company routes
$stmt = $pdo->prepare("
    SELECT r.*, 
           (SELECT COUNT(*) FROM schedules WHERE route_id = r.id AND is_active = TRUE) as active_schedules
    FROM routes r
    WHERE r.company_id = ?
    ORDER BY r.origin, r.destination
");
$stmt->execute([$company['id']]);
$routes = $stmt->fetchAll();

// Popular Ethiopian cities for reference
$ethiopian_cities = [
    'Addis Ababa', 'Dire Dawa', 'Mekelle', 'Gondar', 'Bahir Dar', 
    'Hawassa', 'Jimma', 'Dessie', 'Jijiga', 'Shashamane',
    'Arba Minch', 'Adama (Nazret)', 'Harar', 'Axum', 'Lalibela',
    'Debre Markos', 'Debre Birhan', 'Nekemte', 'Gambela', 'Assosa'
];

include('../includes/header.php');
?>

<?php include('../includes/sidebar_transport.php'); ?>

<main class="container py-4">
    <?php echo displayFlashMessage(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-route text-primary-green me-2"></i>Manage Routes</h4>
        <button type="button" class="btn btn-primary-green rounded-pill" data-bs-toggle="modal" data-bs-target="#addRouteModal">
            <i class="fas fa-plus me-2"></i>Add New Route
        </button>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-route text-primary fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo count($routes); ?></h3>
                    <small class="text-muted">Total Routes</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo count(array_filter($routes, fn($r) => $r['is_active'])); ?></h3>
                    <small class="text-muted">Active Routes</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo array_sum(array_column($routes, 'active_schedules')); ?></h3>
                    <small class="text-muted">Active Schedules</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Routes List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Route</th>
                            <th>Distance</th>
                            <th>Duration</th>
                            <th>Base Price</th>
                            <th>Schedules</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($routes)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5">
                                    <i class="fas fa-route text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted mb-0">No routes added yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($routes as $route): ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-light rounded-circle p-2">
                                                <i class="fas fa-map-marker-alt text-primary-green"></i>
                                            </div>
                                            <div>
                                                <strong><?php echo htmlspecialchars($route['origin']); ?></strong>
                                                <i class="fas fa-arrow-right text-muted mx-2"></i>
                                                <strong><?php echo htmlspecialchars($route['destination']); ?></strong>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo number_format($route['distance_km']); ?> km</td>
                                    <td><?php echo $route['estimated_hours']; ?> hours</td>
                                    <td><strong><?php echo number_format($route['base_price']); ?> ETB</strong></td>
                                    <td><?php echo $route['active_schedules']; ?> active</td>
                                    <td>
                                        <?php if ($route['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editRouteModal<?php echo $route['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill me-1" 
                                                onclick="window.location.href='schedules.php?route=<?php echo $route['id']; ?>'">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                        <?php if ($route['active_schedules'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteRouteModal<?php echo $route['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Route Modal -->
                                <div class="modal fade" id="editRouteModal<?php echo $route['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary-green text-white">
                                                <h5 class="modal-title">Edit Route</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Origin</label>
                                                            <input type="text" name="origin" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo htmlspecialchars($route['origin']); ?>" 
                                                                   list="citiesList" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Destination</label>
                                                            <input type="text" name="destination" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo htmlspecialchars($route['destination']); ?>" 
                                                                   list="citiesList" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Distance (km)</label>
                                                            <input type="number" name="distance" step="0.1" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $route['distance_km']; ?>" required>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Duration (hours)</label>
                                                            <input type="number" name="estimated_hours" step="0.5" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $route['estimated_hours']; ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label fw-bold">Base Price (ETB)</label>
                                                            <input type="number" name="base_price" step="10" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $route['base_price']; ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="is_active" id="active<?php echo $route['id']; ?>" 
                                                                       <?php echo $route['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="active<?php echo $route['id']; ?>">
                                                                    Active (available for scheduling)
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_route" class="btn btn-primary-green rounded-pill">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Route Modal -->
                                <div class="modal fade" id="deleteRouteModal<?php echo $route['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Delete Route</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                                                <div class="modal-body">
                                                    <p>Delete route <strong><?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?></strong>?</p>
                                                    <p class="text-muted small">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_route" class="btn btn-danger rounded-pill">
                                                        <i class="fas fa-trash me-2"></i>Delete Route
                                                    </button>
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
</main>

<!-- Add Route Modal -->
<div class="modal fade" id="addRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-green text-white">
                <h5 class="modal-title"><i class="fas fa-route me-2"></i>Add New Route</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Origin City</label>
                            <input type="text" name="origin" class="form-control rounded-pill bg-light border-0" 
                                   placeholder="e.g., Addis Ababa" list="citiesList" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Destination City</label>
                            <input type="text" name="destination" class="form-control rounded-pill bg-light border-0" 
                                   placeholder="e.g., Hawassa" list="citiesList" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Distance (km)</label>
                            <input type="number" name="distance" step="0.1" class="form-control rounded-pill bg-light border-0" 
                                   placeholder="e.g., 275" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Duration (hours)</label>
                            <input type="number" name="estimated_hours" step="0.5" class="form-control rounded-pill bg-light border-0" 
                                   placeholder="e.g., 5.5" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Base Price (ETB)</label>
                            <input type="number" name="base_price" step="10" class="form-control rounded-pill bg-light border-0" 
                                   placeholder="e.g., 500" required>
                            <small class="text-muted">This is the default price. You can adjust per schedule.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_route" class="btn btn-primary-green rounded-pill">
                        <i class="fas fa-plus me-2"></i>Add Route
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cities Datalist -->
<datalist id="citiesList">
    <?php foreach ($ethiopian_cities as $city): ?>
        <option value="<?php echo htmlspecialchars($city); ?>">
    <?php endforeach; ?>
</datalist>

<?php include('../includes/footer.php'); ?>
