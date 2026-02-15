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

// Handle schedule operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        // Add new schedule
        if (isset($_POST['add_schedule'])) {
            $bus_id = (int)$_POST['bus_id'];
            $route_id = (int)$_POST['route_id'];
            $departure_time = sanitize($_POST['departure_time']);
            $arrival_time = sanitize($_POST['arrival_time']);
            $price = (float)$_POST['price'];
            $operating_days = implode(',', $_POST['operating_days'] ?? []);
            
            // Verify bus belongs to company
            $stmt = $pdo->prepare("SELECT id FROM buses WHERE id = ? AND company_id = ?");
            $stmt->execute([$bus_id, $company['id']]);
            if (!$stmt->fetch()) {
                redirectWithMessage('schedules.php', 'error', 'Invalid bus selected');
            }
            
            $stmt = $pdo->prepare("
                INSERT INTO schedules (bus_id, route_id, departure_time, arrival_time, price, operating_days, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, TRUE, NOW())
            ");
            $stmt->execute([$bus_id, $route_id, $departure_time, $arrival_time, $price, $operating_days]);
            redirectWithMessage('schedules.php', 'success', 'Schedule added successfully');
        }
        
        // Update schedule
        if (isset($_POST['update_schedule'])) {
            $schedule_id = (int)$_POST['schedule_id'];
            $bus_id = (int)$_POST['bus_id'];
            $route_id = (int)$_POST['route_id'];
            $departure_time = sanitize($_POST['departure_time']);
            $arrival_time = sanitize($_POST['arrival_time']);
            $price = (float)$_POST['price'];
            $operating_days = implode(',', $_POST['operating_days'] ?? []);
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            $stmt = $pdo->prepare("
                UPDATE schedules s
                JOIN buses b ON s.bus_id = b.id
                SET s.bus_id = ?, s.route_id = ?, s.departure_time = ?, s.arrival_time = ?, 
                    s.price = ?, s.operating_days = ?, s.is_active = ?
                WHERE s.id = ? AND b.company_id = ?
            ");
            $stmt->execute([$bus_id, $route_id, $departure_time, $arrival_time, $price, $operating_days, $is_active, $schedule_id, $company['id']]);
            redirectWithMessage('schedules.php', 'success', 'Schedule updated successfully');
        }
        
        // Delete schedule
        if (isset($_POST['delete_schedule'])) {
            $schedule_id = (int)$_POST['schedule_id'];
            
            // Check for bookings
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bus_bookings WHERE schedule_id = ? AND status != 'cancelled'");
            $stmt->execute([$schedule_id]);
            if ($stmt->fetchColumn() > 0) {
                redirectWithMessage('schedules.php', 'error', 'Cannot delete schedule with active bookings');
            }
            
            $stmt = $pdo->prepare("
                DELETE s FROM schedules s
                JOIN buses b ON s.bus_id = b.id
                WHERE s.id = ? AND b.company_id = ?
            ");
            $stmt->execute([$schedule_id, $company['id']]);
            redirectWithMessage('schedules.php', 'success', 'Schedule deleted successfully');
        }
    }
}

// Get filter
$route_filter = (int)($_GET['route'] ?? 0);
$bus_filter = (int)($_GET['bus'] ?? 0);

// Get company buses
$stmt = $pdo->prepare("SELECT * FROM buses WHERE company_id = ? AND is_active = TRUE ORDER BY bus_number");
$stmt->execute([$company['id']]);
$buses = $stmt->fetchAll();

// Get company routes
$stmt = $pdo->prepare("SELECT * FROM routes WHERE company_id = ? AND is_active = TRUE ORDER BY origin, destination");
$stmt->execute([$company['id']]);
$routes = $stmt->fetchAll();

// Build query for schedules
$where = "b.company_id = ?";
$params = [$company['id']];

if ($route_filter) {
    $where .= " AND s.route_id = ?";
    $params[] = $route_filter;
}
if ($bus_filter) {
    $where .= " AND s.bus_id = ?";
    $params[] = $bus_filter;
}

// Get schedules
$stmt = $pdo->prepare("
    SELECT s.*, r.origin, r.destination, r.estimated_hours,
           bus.bus_number, bt.name as bus_type,
           (SELECT COUNT(*) FROM bus_bookings WHERE schedule_id = s.id AND status != 'cancelled') as total_bookings
    FROM schedules s
    JOIN routes r ON s.route_id = r.id
    JOIN buses bus ON s.bus_id = bus.id
    JOIN bus_types bt ON bus.bus_type_id = bt.id
    JOIN transport_companies b ON bus.company_id = b.id
    WHERE $where
    ORDER BY r.origin, s.departure_time
");
$stmt->execute($params);
$schedules = $stmt->fetchAll();

$days_of_week = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

include('../includes/header.php');
?>

<?php include('../includes/sidebar_transport.php'); ?>

<main class="container py-4">
    <?php echo displayFlashMessage(); ?>
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold"><i class="fas fa-calendar-alt text-primary-green me-2"></i>Manage Schedules</h4>
        <button type="button" class="btn btn-primary-green rounded-pill" data-bs-toggle="modal" data-bs-target="#addScheduleModal">
            <i class="fas fa-plus me-2"></i>Add New Schedule
        </button>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filter by Route</label>
                    <select name="route" class="form-select rounded-pill bg-light border-0">
                        <option value="">All Routes</option>
                        <?php foreach ($routes as $route): ?>
                            <option value="<?php echo $route['id']; ?>" <?php echo $route_filter == $route['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small fw-bold">Filter by Bus</label>
                    <select name="bus" class="form-select rounded-pill bg-light border-0">
                        <option value="">All Buses</option>
                        <?php foreach ($buses as $bus): ?>
                            <option value="<?php echo $bus['id']; ?>" <?php echo $bus_filter == $bus['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($bus['bus_number']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <button type="submit" class="btn btn-primary-green rounded-pill me-2">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                    <a href="schedules.php" class="btn btn-outline-secondary rounded-pill">Clear</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Schedules List -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Route</th>
                            <th>Bus</th>
                            <th>Departure</th>
                            <th>Arrival</th>
                            <th>Days</th>
                            <th>Price</th>
                            <th>Bookings</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($schedules)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-calendar text-muted fs-1 mb-3 d-block"></i>
                                    <p class="text-muted mb-0">No schedules found</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($schedules as $schedule): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($schedule['origin']); ?></strong>
                                            <i class="fas fa-arrow-right text-muted mx-1"></i>
                                            <strong><?php echo htmlspecialchars($schedule['destination']); ?></strong>
                                            <br><small class="text-muted"><?php echo $schedule['estimated_hours']; ?>h journey</small>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark"><?php echo htmlspecialchars($schedule['bus_number']); ?></span>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($schedule['bus_type']); ?></small>
                                    </td>
                                    <td>
                                        <strong><?php echo date('h:i A', strtotime($schedule['departure_time'])); ?></strong>
                                    </td>
                                    <td>
                                        <?php echo $schedule['arrival_time'] ? date('h:i A', strtotime($schedule['arrival_time'])) : '--:--'; ?>
                                    </td>
                                    <td>
                                        <?php 
                                        $operating_days = explode(',', $schedule['operating_days'] ?? '');
                                        echo implode(', ', $operating_days);
                                        ?>
                                    </td>
                                    <td><strong><?php echo number_format($schedule['price']); ?> ETB</strong></td>
                                    <td><?php echo $schedule['total_bookings']; ?></td>
                                    <td>
                                        <?php if ($schedule['is_active']): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Inactive</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill me-1" 
                                                data-bs-toggle="modal" data-bs-target="#editScheduleModal<?php echo $schedule['id']; ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($schedule['total_bookings'] == 0): ?>
                                            <button type="button" class="btn btn-sm btn-outline-danger rounded-pill" 
                                                    data-bs-toggle="modal" data-bs-target="#deleteScheduleModal<?php echo $schedule['id']; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>

                                <!-- Edit Schedule Modal -->
                                <div class="modal fade" id="editScheduleModal<?php echo $schedule['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog modal-lg">
                                        <div class="modal-content">
                                            <div class="modal-header bg-primary-green text-white">
                                                <h5 class="modal-title">Edit Schedule</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                <div class="modal-body">
                                                    <div class="row g-3">
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Bus</label>
                                                            <select name="bus_id" class="form-select rounded-pill bg-light border-0" required>
                                                                <?php foreach ($buses as $bus): ?>
                                                                    <option value="<?php echo $bus['id']; ?>" <?php echo $schedule['bus_id'] == $bus['id'] ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($bus['bus_number']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <label class="form-label fw-bold">Route</label>
                                                            <select name="route_id" class="form-select rounded-pill bg-light border-0" required>
                                                                <?php foreach ($routes as $route): ?>
                                                                    <option value="<?php echo $route['id']; ?>" <?php echo $schedule['route_id'] == $route['id'] ? 'selected' : ''; ?>>
                                                                        <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Departure Time</label>
                                                            <input type="time" name="departure_time" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $schedule['departure_time']; ?>" required>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Arrival Time</label>
                                                            <input type="time" name="arrival_time" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $schedule['arrival_time']; ?>">
                                                        </div>
                                                        <div class="col-md-4">
                                                            <label class="form-label fw-bold">Price (ETB)</label>
                                                            <input type="number" name="price" step="10" class="form-control rounded-pill bg-light border-0" 
                                                                   value="<?php echo $schedule['price']; ?>" required>
                                                        </div>
                                                        <div class="col-12">
                                                            <label class="form-label fw-bold">Operating Days</label>
                                                            <div class="d-flex flex-wrap gap-2">
                                                                <?php 
                                                                $selected_days = explode(',', $schedule['operating_days'] ?? '');
                                                                foreach ($days_of_week as $day): 
                                                                ?>
                                                                    <div class="form-check form-check-inline">
                                                                        <input class="form-check-input" type="checkbox" name="operating_days[]" 
                                                                               id="edit_day_<?php echo $schedule['id']; ?>_<?php echo $day; ?>" 
                                                                               value="<?php echo $day; ?>"
                                                                               <?php echo in_array($day, $selected_days) ? 'checked' : ''; ?>>
                                                                        <label class="form-check-label" for="edit_day_<?php echo $schedule['id']; ?>_<?php echo $day; ?>">
                                                                            <?php echo $day; ?>
                                                                        </label>
                                                                    </div>
                                                                <?php endforeach; ?>
                                                            </div>
                                                        </div>
                                                        <div class="col-12">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox" name="is_active" 
                                                                       id="active<?php echo $schedule['id']; ?>" 
                                                                       <?php echo $schedule['is_active'] ? 'checked' : ''; ?>>
                                                                <label class="form-check-label" for="active<?php echo $schedule['id']; ?>">
                                                                    Active (available for booking)
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="update_schedule" class="btn btn-primary-green rounded-pill">
                                                        <i class="fas fa-save me-2"></i>Save Changes
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Delete Schedule Modal -->
                                <div class="modal fade" id="deleteScheduleModal<?php echo $schedule['id']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title">Delete Schedule</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <?php echo csrfField(); ?>
                                                <input type="hidden" name="schedule_id" value="<?php echo $schedule['id']; ?>">
                                                <div class="modal-body">
                                                    <p>Delete this schedule?</p>
                                                    <p class="text-muted small">
                                                        <?php echo htmlspecialchars($schedule['origin']); ?> → <?php echo htmlspecialchars($schedule['destination']); ?> 
                                                        at <?php echo date('h:i A', strtotime($schedule['departure_time'])); ?>
                                                    </p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="delete_schedule" class="btn btn-danger rounded-pill">
                                                        <i class="fas fa-trash me-2"></i>Delete Schedule
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

<!-- Add Schedule Modal -->
<div class="modal fade" id="addScheduleModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary-green text-white">
                <h5 class="modal-title"><i class="fas fa-calendar-plus me-2"></i>Add New Schedule</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <?php echo csrfField(); ?>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bus</label>
                            <select name="bus_id" class="form-select rounded-pill bg-light border-0" required>
                                <option value="">Select Bus</option>
                                <?php foreach ($buses as $bus): ?>
                                    <option value="<?php echo $bus['id']; ?>"><?php echo htmlspecialchars($bus['bus_number']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Route</label>
                            <select name="route_id" class="form-select rounded-pill bg-light border-0" required>
                                <option value="">Select Route</option>
                                <?php foreach ($routes as $route): ?>
                                    <option value="<?php echo $route['id']; ?>" data-price="<?php echo $route['base_price']; ?>">
                                        <?php echo htmlspecialchars($route['origin'] . ' → ' . $route['destination']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Departure Time</label>
                            <input type="time" name="departure_time" class="form-control rounded-pill bg-light border-0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Arrival Time</label>
                            <input type="time" name="arrival_time" class="form-control rounded-pill bg-light border-0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Price (ETB)</label>
                            <input type="number" name="price" step="10" class="form-control rounded-pill bg-light border-0" 
                                   id="schedulePrice" placeholder="e.g., 500" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Operating Days</label>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($days_of_week as $day): ?>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" name="operating_days[]" 
                                               id="day_<?php echo $day; ?>" value="<?php echo $day; ?>" checked>
                                        <label class="form-check-label" for="day_<?php echo $day; ?>">
                                            <?php echo $day; ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_schedule" class="btn btn-primary-green rounded-pill">
                        <i class="fas fa-plus me-2"></i>Add Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-fill price based on route selection
document.querySelector('select[name="route_id"]').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.dataset.price;
    if (price) {
        document.getElementById('schedulePrice').value = price;
    }
});
</script>

<?php include('../includes/footer.php'); ?>
