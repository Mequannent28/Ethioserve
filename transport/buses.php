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

// Handle bus operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Add new bus
        if (isset($_POST['add_bus'])) {
            $bus_number = sanitize($_POST['bus_number']);
            $bus_type_id = (int)$_POST['bus_type_id'];
            $total_seats = (int)$_POST['total_seats'];
            $amenities = sanitize($_POST['amenities']);
            $plate_number = sanitize($_POST['plate_number']);
            
            $stmt = $pdo->prepare("
                INSERT INTO buses (company_id, bus_type_id, bus_number, plate_number, total_seats, amenities, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->execute([$company['id'], $bus_type_id, $bus_number, $plate_number, $total_seats, $amenities]);
            redirectWithMessage('buses.php', 'success', 'Bus added successfully');
        }
        
        // Update bus
        if (isset($_POST['update_bus'])) {
            $bus_id = (int)$_POST['bus_id'];
            $bus_number = sanitize($_POST['bus_number']);
            $bus_type_id = (int)$_POST['bus_type_id'];
            $total_seats = (int)$_POST['total_seats'];
            $amenities = sanitize($_POST['amenities']);
            $plate_number = sanitize($_POST['plate_number']);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE buses SET bus_type_id = ?, bus_number = ?, plate_number = ?, total_seats = ?, amenities = ?, is_active = ?
                WHERE id = ? AND company_id = ?
            ");
            $stmt->execute([$bus_type_id, $bus_number, $plate_number, $total_seats, $amenities, $is_active, $bus_id, $company['id']]);
            redirectWithMessage('buses.php', 'success', 'Bus updated successfully');
        }
        
        // Delete bus
        if (isset($_POST['delete_bus'])) {
            $bus_id = (int)$_POST['bus_id'];
            
            // Check if bus has active schedules
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM schedules WHERE bus_id = ? AND is_active = TRUE");
            $stmt->execute([$bus_id]);
            if ($stmt->fetchColumn() > 0) {
                redirectWithMessage('buses.php', 'error', 'Cannot delete bus with active schedules');
            }
            
            $stmt = $pdo->prepare("DELETE FROM buses WHERE id = ? AND company_id = ?");
            $stmt->execute([$bus_id, $company['id']]);
            redirectWithMessage('buses.php', 'success', 'Bus deleted successfully');
        }
    }
}

// Get bus types
$stmt = $pdo->query("SELECT * FROM bus_types ORDER BY name");
$bus_types = $stmt->fetchAll();

// Get company buses
$stmt = $pdo->prepare("
    SELECT b.*, bt.name as bus_type, bt.seat_layout,
           (SELECT COUNT(*) FROM schedules WHERE bus_id = b.id AND is_active = TRUE) as active_schedules
    FROM buses b
    JOIN bus_types bt ON b.bus_type_id = bt.id
    WHERE b.company_id = ?
    ORDER BY b.created_at DESC
");
$stmt->execute([$company['id']]);
$buses = $stmt->fetchAll();

include('../includes/header.php');
?>

<?php include('../includes/sidebar_transport.php'); ?>

<main class="container py-4">
    <?php echo displayFlashMessage(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-bus text-primary-green me-2"></i>Manage Buses</h4>
        <div>
            <button type="button" class="btn btn-primary-green rounded-pill" data-bs-toggle="modal" data-bs-target="#addBusModal">
                <i class="fas fa-plus me-2"></i>Add New Bus
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-bus text-primary fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo count($buses); ?></h3>
                    <small class="text-muted">Total Buses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-check-circle text-success fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo count(array_filter($buses, fn($b) => $b['is_active'])); ?></h3>
                    <small class="text-muted">Active Buses</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-chair text-info fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo array_sum(array_column($buses, 'total_seats')); ?></h3>
                    <small class="text-muted">Total Seats</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <i class="fas fa-calendar text-warning fs-1 mb-2"></i>
                    <h3 class="fw-bold"><?php echo array_sum(array_column($buses, 'active_schedules')); ?></h3>
                    <small class="text-muted">Active Schedules</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Buses List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Bus Number</th>
                            <th>Plate Number</th>
                            <th>Type</th>
                            <th>Seats</th>
                            <th>Amenities</th>
                            <th>Schedules</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($buses)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-5">
                                    <i class="fas fa-bus text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted mb-0">No buses added yet</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($buses as $bus): ?>
                                <tr>
                                    <td>
                                        <span class="fw-bold"><?php echo htmlspecialchars($bus['bus_number']); ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($bus['plate_number']); ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($bus['bus_type']); ?></span>
                                    </td>
                                    <td><?php echo $bus['total_seats']; ?> seats</td>
                                    <td>
                                        <?php 
                                        $amenities = explode(',', $bus['amenities'] ?? '');
                                        foreach (array_slice($amenities, 0, 3) as $amenity): 
                                        ?>
                                            <span class="badge bg-light text-dark small me-1"><?php echo trim($amenity); ?></span>
                                        <?php endforeach; ?>
                                        <?php if (count($amenities) > 3): ?>
                                            <span class="badge bg-light text-dark small">+<?php echo count($amenities) - 3; ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo $bus['active_schedules']; ?> active</td>
                                    <td>
                                        <?php if ($bus['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editBusModal<?php echo $bus['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-info rounded-pill me-1" 
                                                onclick="window.location.href='schedules.php?bus=<?php echo $bus['id']; ?>'">
                                            <i class="fas fa-calendar"></i>
                                        </button>
                                        <?php if ($bus['active_schedules'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteBusModal<?php echo $bus['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Bus Modal -->
                                <div class="modal fade" id="editBusModal<?php echo $bus['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary-green text-white">
                                                <h5 class="modal-title">Edit Bus</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Bus Number</label>
                                                        <input type="text" name="bus_number" class="form-control rounded-pill bg-light border-0" 
                                                               value="<?php echo htmlspecialchars($bus['bus_number']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Plate Number</label>
                                                        <input type="text" name="plate_number" class="form-control rounded-pill bg-light border-0" 
                                                               value="<?php echo htmlspecialchars($bus['plate_number']); ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Bus Type</label>
                                                        <select name="bus_type_id" class="form-select rounded-pill bg-light border-0" required>
                                                            <?php foreach ($bus_types as $type): ?>
                                                                <option value="<?php echo $type['id']; ?>" <?php echo $bus['bus_type_id'] == $type['id'] ? 'selected' : ''; ?>>
                                                                    <?php echo htmlspecialchars($type['name']); ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Total Seats</label>
                                                        <input type="number" name="total_seats" class="form-control rounded-pill bg-light border-0" 
                                                               value="<?php echo $bus['total_seats']; ?>" min="1" max="100" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Amenities (comma separated)</label>
                                                        <input type="text" name="amenities" class="form-control rounded-pill bg-light border-0" 
                                                               value="<?php echo htmlspecialchars($bus['amenities']); ?>" 
                                                               placeholder="AC, WiFi, TV, USB Charging">
                                                    </div>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" name="is_active" id="active<?php echo $bus['id']; ?>" 
                                                               <?php echo $bus['is_active'] ? 'checked' : ''; ?>>
                                                        <label class="form-check-label" for="active<?php echo $bus['id']; ?>">
                                                            Active (available for booking)
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_bus" class="btn btn-primary-green rounded-pill">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Bus Modal -->
                                <div class="modal fade" id="deleteBusModal<?php echo $bus['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Delete Bus</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="bus_id" value="<?php echo $bus['id']; ?>">
                                                <div class="modal-body">
                                                    <p>Are you sure you want to delete bus <strong><?php echo htmlspecialchars($bus['bus_number']); ?></strong>?</p>
                                                    <p class="text-muted small">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_bus" class="btn btn-danger rounded-pill">
                                                        <i class="fas fa-trash me-2"></i>Delete Bus
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

<!-- Add Bus Modal -->
<div class="modal fade" id="addBusModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary-green text-white">
                <h5 class="modal-title"><i class="fas fa-bus me-2"></i>Add New Bus</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Bus Number/Name</label>
                        <input type="text" name="bus_number" class="form-control rounded-pill bg-light border-0" 
                               placeholder="e.g., Bus 001" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Plate Number</label>
                        <input type="text" name="plate_number" class="form-control rounded-pill bg-light border-0" 
                               placeholder="e.g., AA-1234-A1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Bus Type</label>
                        <select name="bus_type_id" class="form-select rounded-pill bg-light border-0" required>
                            <option value="">Select Type</option>
                            <?php foreach ($bus_types as $type): ?>
                                <option value="<?php echo $type['id']; ?>"><?php echo htmlspecialchars($type['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Total Seats</label>
                        <input type="number" name="total_seats" class="form-control rounded-pill bg-light border-0" 
                               placeholder="e.g., 44" min="1" max="100" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Amenities (comma separated)</label>
                        <input type="text" name="amenities" class="form-control rounded-pill bg-light border-0" 
                               placeholder="e.g., AC, WiFi, TV, USB Charging">
                        <small class="text-muted">Enter amenities like: AC, WiFi, TV, USB Charging, Reclining Seats, etc.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_bus" class="btn btn-primary-green rounded-pill">
                        <i class="fas fa-plus me-2"></i>Add Bus
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
