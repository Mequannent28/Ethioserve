<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('taxi');

$user_id = getCurrentUserId();

$stmt = $pdo->prepare("SELECT * FROM taxi_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: dashboard.php");
    exit();
}

$company_id = $company['id'];

// Handle add vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['add_vehicle'])) {
        $plate = sanitize($_POST['plate_number']);
        $model = sanitize($_POST['vehicle_model']);
        $type = sanitize($_POST['vehicle_type']);
        $driver = sanitize($_POST['driver_name']);
        $driver_phone = sanitize($_POST['driver_phone']);

        $stmt = $pdo->prepare("INSERT INTO taxi_vehicles (company_id, plate_number, vehicle_model, vehicle_type, driver_name, driver_phone, status) VALUES (?, ?, ?, ?, ?, ?, 'available')");
        $stmt->execute([$company_id, $plate, $model, $type, $driver, $driver_phone]);
        redirectWithMessage('vehicles.php', 'success', 'Vehicle added!');
    }

    if (isset($_POST['delete_vehicle'])) {
        $id = (int) $_POST['vehicle_id'];
        $stmt = $pdo->prepare("DELETE FROM taxi_vehicles WHERE id = ? AND company_id = ?");
        $stmt->execute([$id, $company_id]);
        redirectWithMessage('vehicles.php', 'success', 'Vehicle removed!');
    }

    if (isset($_POST['toggle_status'])) {
        $id = (int) $_POST['vehicle_id'];
        $new_status = sanitize($_POST['new_status']);
        if (in_array($new_status, ['available', 'on_trip', 'maintenance', 'offline'])) {
            $stmt = $pdo->prepare("UPDATE taxi_vehicles SET status = ? WHERE id = ? AND company_id = ?");
            $stmt->execute([$new_status, $id, $company_id]);
            redirectWithMessage('vehicles.php', 'success', 'Vehicle status updated!');
        }
    }
}

// Get vehicles
$stmt = $pdo->prepare("SELECT * FROM taxi_vehicles WHERE company_id = ? ORDER BY status, driver_name");
$stmt->execute([$company_id]);
$vehicles = $stmt->fetchAll();

$available = count(array_filter($vehicles, fn($v) => $v['status'] === 'available'));
$on_trip = count(array_filter($vehicles, fn($v) => $v['status'] === 'on_trip'));
$maintenance = count(array_filter($vehicles, fn($v) => $v['status'] === 'maintenance'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vehicles - Taxi Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .vehicle-card {
            transition: all 0.3s;
            border-radius: 12px;
        }

        .vehicle-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-available {
            background: #4CAF50;
        }

        .status-on_trip {
            background: #1976D2;
        }

        .status-maintenance {
            background: #F9A825;
        }

        .status-offline {
            background: #9E9E9E;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_taxi.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0"><i class="fas fa-car me-2 text-primary-green"></i>Vehicle Management</h2>
                    <p class="text-muted">Manage your fleet of
                        <?php echo count($vehicles); ?> vehicles
                    </p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#addVehicleModal">
                    <i class="fas fa-plus me-2"></i>Add Vehicle
                </button>
            </div>

            <!-- Fleet Stats -->
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <h3 class="fw-bold text-primary-green">
                            <?php echo count($vehicles); ?>
                        </h3>
                        <small class="text-muted">Total Fleet</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <h3 class="fw-bold text-success">
                            <?php echo $available; ?>
                        </h3>
                        <small class="text-muted">Available</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <h3 class="fw-bold text-primary">
                            <?php echo $on_trip; ?>
                        </h3>
                        <small class="text-muted">On Trip</small>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm text-center p-3">
                        <h3 class="fw-bold text-warning">
                            <?php echo $maintenance; ?>
                        </h3>
                        <small class="text-muted">Maintenance</small>
                    </div>
                </div>
            </div>

            <!-- Vehicles Grid -->
            <?php if (empty($vehicles)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-car-side text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No vehicles yet</h4>
                    <p class="text-muted">Click "Add Vehicle" to start building your fleet.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($vehicles as $vehicle): ?>
                        <div class="col-md-4">
                            <div class="card border-0 shadow-sm vehicle-card">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="fw-bold mb-0">
                                                <?php echo htmlspecialchars($vehicle['plate_number']); ?>
                                            </h5>
                                            <small class="text-muted">
                                                <?php echo htmlspecialchars($vehicle['vehicle_model']); ?>
                                            </small>
                                        </div>
                                        <span class="badge bg-light text-dark rounded-pill px-3">
                                            <span class="status-dot status-<?php echo $vehicle['status']; ?> me-1"></span>
                                            <?php echo ucfirst(str_replace('_', ' ', $vehicle['status'])); ?>
                                        </span>
                                    </div>

                                    <div class="mb-3">
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fas fa-tag text-muted"></i>
                                            <span>
                                                <?php echo ucfirst($vehicle['vehicle_type']); ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2 mb-2">
                                            <i class="fas fa-user text-muted"></i>
                                            <span>
                                                <?php echo htmlspecialchars($vehicle['driver_name']); ?>
                                            </span>
                                        </div>
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fas fa-phone text-muted"></i>
                                            <span>
                                                <?php echo htmlspecialchars($vehicle['driver_phone']); ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="d-flex gap-2">
                                        <!-- Status Toggle Dropdown -->
                                        <div class="dropdown flex-grow-1">
                                            <button
                                                class="btn btn-sm btn-outline-primary-green rounded-pill w-100 dropdown-toggle"
                                                data-bs-toggle="dropdown">
                                                <i class="fas fa-sync-alt me-1"></i>Change Status
                                            </button>
                                            <ul class="dropdown-menu">
                                                <?php foreach (['available', 'on_trip', 'maintenance', 'offline'] as $stat): ?>
                                                    <?php if ($stat !== $vehicle['status']): ?>
                                                        <li>
                                                            <form method="POST">
                                                                <?php echo csrfField(); ?>
                                                                <input type="hidden" name="vehicle_id"
                                                                    value="<?php echo $vehicle['id']; ?>">
                                                                <input type="hidden" name="new_status" value="<?php echo $stat; ?>">
                                                                <button type="submit" name="toggle_status" value="1"
                                                                    class="dropdown-item">
                                                                    <span class="status-dot status-<?php echo $stat; ?> me-2"></span>
                                                                    <?php echo ucfirst(str_replace('_', ' ', $stat)); ?>
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                        </div>
                                        <form method="POST" onsubmit="return confirm('Remove this vehicle?');">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="vehicle_id" value="<?php echo $vehicle['id']; ?>">
                                            <button type="submit" name="delete_vehicle" value="1"
                                                class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Vehicle Modal -->
    <div class="modal fade" id="addVehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add Vehicle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Plate Number</label>
                        <input type="text" name="plate_number" class="form-control" required
                            placeholder="e.g. AA 12345">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Model</label>
                        <input type="text" name="vehicle_model" class="form-control" required
                            placeholder="e.g. Toyota Corolla">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Vehicle Type</label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="sedan">Sedan</option>
                            <option value="suv">SUV</option>
                            <option value="minivan">Minivan</option>
                            <option value="van">Van</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Driver Name</label>
                        <input type="text" name="driver_name" class="form-control" required
                            placeholder="Driver's full name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Driver Phone</label>
                        <input type="text" name="driver_phone" class="form-control" required
                            placeholder="e.g. +251 9XX XXX XXX">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_vehicle" value="1" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Vehicle
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>