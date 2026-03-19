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
                INSERT INTO routes (company_id, origin, destination, distance_km, estimated_hours, base_price, is_active)
                VALUES (?, ?, ?, ?, ?, ?, TRUE)
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

$page_title = 'Manage Routes';
$top_title = 'Route Management';
include('../includes/transport_header.php');
?>

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
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php foreach ($routes as $route): ?>
        <!-- Edit Route Modal -->
        <div class="modal fade" id="editRouteModal<?php echo $route['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-primary-green text-white border-0 py-3">
                        <h5 class="modal-title fw-bold"><i class="fas fa-edit me-2"></i>Edit Route Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <div class="modal-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Origin City</label>
                                    <input type="text" name="origin" class="form-control rounded-pill bg-light border-0 px-3" 
                                           value="<?php echo htmlspecialchars($route['origin']); ?>" list="citiesList" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Destination City</label>
                                    <input type="text" name="destination" class="form-control rounded-pill bg-light border-0 px-3" 
                                           value="<?php echo htmlspecialchars($route['destination']); ?>" list="citiesList" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Distance (km)</label>
                                    <input type="number" name="distance" step="0.1" class="form-control rounded-pill bg-light border-0 px-3 route-distance-input" 
                                           value="<?php echo $route['distance_km']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Duration (hours)</label>
                                    <input type="number" name="estimated_hours" step="0.1" class="form-control rounded-pill bg-light border-0 px-3 route-duration-input" 
                                           value="<?php echo $route['estimated_hours']; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Base Price (ETB)</label>
                                    <input type="number" name="base_price" step="1" class="form-control rounded-pill bg-light border-0 px-3 route-price-input" 
                                           value="<?php echo $route['base_price']; ?>" required>
                                </div>
                                <div class="col-12 mt-3">
                                    <div class="form-check form-switch p-3 bg-light rounded-4 ms-0">
                                        <input class="form-check-input ms-0 me-3" type="checkbox" name="is_active" id="activeEdit<?php echo $route['id']; ?>" 
                                               <?php echo $route['is_active'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label fw-bold" for="activeEdit<?php echo $route['id']; ?>">
                                            Active (Available for booking)
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="update_route" class="btn btn-primary-green rounded-pill px-4 fw-bold">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Delete Route Modal -->
        <div class="modal fade" id="deleteRouteModal<?php echo $route['id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow-lg">
                    <div class="modal-header bg-danger text-white border-0">
                        <h5 class="modal-title fw-bold"><i class="fas fa-trash-alt me-2"></i>Confirm Deletion</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="route_id" value="<?php echo $route['id']; ?>">
                        <div class="modal-body p-4 text-center">
                            <i class="fas fa-exclamation-triangle text-danger fs-1 mb-3"></i>
                            <p class="mb-1">Are you sure you want to delete route:</p>
                            <h5 class="fw-bold"><?php echo htmlspecialchars($route['origin']); ?> → <?php echo htmlspecialchars($route['destination']); ?></h5>
                            <p class="text-muted small mt-2">This action cannot be undone.</p>
                        </div>
                        <div class="modal-footer border-0 justify-content-center pb-4">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="delete_route" class="btn btn-danger rounded-pill px-4 fw-bold">
                                <i class="fas fa-trash me-2"></i>Yes, Delete Route
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Add Route Modal -->
    <div class="modal fade" id="addRouteModal" tabindex="-1">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content border-0">
                <div class="modal-header bg-primary-green text-white border-0 py-3">
                    <h5 class="modal-title fw-bold"><i class="fas fa-route me-2"></i>Add New Route</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <?php echo csrfField(); ?>
                    <div class="modal-body bg-light">
                        <div class="container py-4">
                            <div class="card border-0 shadow-sm rounded-4">
                                <div class="card-body p-4 p-md-5">
                                    <div class="row g-4">
                                        <div class="col-md-6 text-center mb-3">
                                            <div class="bg-primary-green bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                                <i class="fas fa-map-marker-alt text-primary-green fs-1"></i>
                                            </div>
                                            <h4 class="fw-bold">Origin & Destination</h4>
                                            <p class="text-muted small">Select city pairs for auto-calculation</p>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">Origin City</label>
                                                <input type="text" name="origin" id="routeOrigin" class="form-control form-control-lg rounded-pill border-0 shadow-sm" 
                                                       placeholder="e.g., Addis Ababa" list="citiesList" required>
                                            </div>
                                            <div class="mb-4">
                                                <label class="form-label fw-bold">Destination City</label>
                                                <input type="text" name="destination" id="routeDestination" class="form-control form-control-lg rounded-pill border-0 shadow-sm" 
                                                       placeholder="e.g., Hawassa" list="citiesList" required>
                                            </div>
                                        </div>
                                        
                                        <div class="col-12"><hr class="my-4"></div>

                                        <div class="col-md-4">
                                            <label class="form-label fw-bold"><i class="fas fa-road me-1"></i> Distance (km)</label>
                                            <div class="input-group">
                                                <input type="number" name="distance" id="routeDistance" step="0.1" class="form-control form-control-lg rounded-pill-start border-0 shadow-sm route-distance-input" 
                                                       placeholder="0.0" required>
                                                <span class="input-group-text border-0 shadow-sm rounded-pill-end bg-white">km</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold"><i class="fas fa-clock me-1"></i> Duration (hours)</label>
                                            <div class="input-group">
                                                <input type="number" name="estimated_hours" id="routeHours" step="0.1" class="form-control form-control-lg rounded-pill-start border-0 shadow-sm route-duration-input" 
                                                       placeholder="0.0" required>
                                                <span class="input-group-text border-0 shadow-sm rounded-pill-end bg-white">hrs</span>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label fw-bold"><i class="fas fa-money-bill-wave me-1"></i> Base Price (ETB)</label>
                                            <div class="input-group">
                                                <input type="number" name="base_price" id="routePrice" step="1" class="form-control form-control-lg rounded-pill-start border-0 shadow-sm route-price-input" 
                                                       placeholder="0.0" required>
                                                <span class="input-group-text border-0 shadow-sm rounded-pill-end bg-white text-success fw-bold">ETB</span>
                                            </div>
                                        </div>

                                        <div class="col-12 mt-5">
                                            <div class="alert alert-info border-0 rounded-4 shadow-sm p-4 d-flex align-items-center gap-4">
                                                <i class="fas fa-magic fs-2"></i>
                                                <div>
                                                    <h6 class="fw-bold mb-1">Smart Auto-Generate</h6>
                                                    <p class="small mb-0">The system automatically calculates distance, time, and estimated price for major Ethiopian city pairs. You can still manually adjust any field.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-white shadow-lg border-0 py-3">
                        <button type="button" class="btn btn-lg btn-light rounded-pill px-5" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_route" class="btn btn-lg btn-primary-green rounded-pill px-5 shadow">
                            <i class="fas fa-plus me-2"></i>Create Route
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Cities Datalist -->
    <datalist id="citiesList">
        <?php foreach (['Addis Ababa', 'Dire Dawa', 'Mekelle', 'Gondar', 'Bahir Dar', 'Hawassa', 'Jimma', 'Dessie', 'Jijiga', 'Shashamane', 'Arba Minch', 'Adama (Nazret)', 'Harar', 'Axum', 'Lalibela', 'Debre Markos', 'Debre Birhan', 'Nekemte', 'Gambela', 'Assosa'] as $city): ?>
            <option value="<?php echo htmlspecialchars($city); ?>">
        <?php endforeach; ?>
    </datalist>

    <script>
    // Route Data Lookup Table
    const routeLookup = {
        "Addis Ababa": {
            "Hawassa": { km: 275, hrs: 5, price: 750 },
            "Bahir Dar": { km: 565, hrs: 10, price: 1200 },
            "Gondar": { km: 725, hrs: 12, price: 1500 },
            "Mekelle": { km: 780, hrs: 14, price: 1600 },
            "Dire Dawa": { km: 515, hrs: 9, price: 1100 },
            "Jimma": { km: 350, hrs: 7, price: 900 },
            "Adama (Nazret)": { km: 100, hrs: 1.5, price: 300 },
            "Arba Minch": { km: 450, hrs: 8, price: 1000 },
            "Dessie": { km: 400, hrs: 7, price: 900 },
            "Jijiga": { km: 630, hrs: 11, price: 1300 },
            "Shashamane": { km: 250, hrs: 4.5, price: 650 },
            "Debre Birhan": { km: 130, hrs: 2.5, price: 350 },
            "Nekemte": { km: 330, hrs: 6, price: 850 }
        },
        "Dire Dawa": {
            "Harar": { km: 55, hrs: 1, price: 200 },
            "Addis Ababa": { km: 515, hrs: 9, price: 1100 }
        },
        "Bahir Dar": {
            "Gondar": { km: 175, hrs: 3, price: 450 },
            "Addis Ababa": { km: 565, hrs: 10, price: 1200 }
        }
    };

    function autoGenerateDetails() {
        const origin = document.getElementById('routeOrigin').value.trim();
        const dest = document.getElementById('routeDestination').value.trim();
        
        if(!origin || !dest) return;

        let data = null;
        if(routeLookup[origin] && routeLookup[origin][dest]) data = routeLookup[origin][dest];
        else if(routeLookup[dest] && routeLookup[dest][origin]) data = routeLookup[dest][origin];

        if(data) {
            document.getElementById('routeDistance').value = data.km;
            document.getElementById('routeHours').value = data.hrs;
            document.getElementById('routePrice').value = data.price;
        }
    }

    document.addEventListener('input', function(e) {
        if (e.target.classList.contains('route-distance-input')) {
            const distance = parseFloat(e.target.value) || 0;
            const form = e.target.closest('form');
            const priceInput = form.querySelector('.route-price-input');
            const durationInput = form.querySelector('.route-duration-input');
            if (priceInput) priceInput.value = Math.round(distance * 2.7);
            if (durationInput) durationInput.value = (distance / 55).toFixed(1);
        }
    });

    document.getElementById('routeOrigin').addEventListener('change', autoGenerateDetails);
    document.getElementById('routeDestination').addEventListener('change', autoGenerateDetails);
    </script>
<?php include('../includes/transport_footer.php'); ?>
