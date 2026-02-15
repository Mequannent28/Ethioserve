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

// Handle ride status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_ride_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $ride_id = (int) $_POST['ride_id'];
        $new_status = sanitize($_POST['status']);
        $valid = ['requested', 'accepted', 'in_progress', 'completed', 'cancelled'];
        if (in_array($new_status, $valid)) {
            $stmt = $pdo->prepare("UPDATE taxi_rides SET status = ? WHERE id = ? AND taxi_company_id = ?");
            $stmt->execute([$new_status, $ride_id, $company_id]);
            if ($new_status === 'completed') {
                $stmt = $pdo->prepare("UPDATE taxi_rides SET payment_status = 'paid' WHERE id = ? AND taxi_company_id = ?");
                $stmt->execute([$ride_id, $company_id]);
            }
            redirectWithMessage('rides.php', 'success', 'Ride status updated!');
        }
    }
}

// Filter
$status_filter = sanitize($_GET['status'] ?? '');
$where = "WHERE tr.taxi_company_id = ?";
$params = [$company_id];
if (!empty($status_filter) && in_array($status_filter, ['requested', 'accepted', 'in_progress', 'completed', 'cancelled'])) {
    $where .= " AND tr.status = ?";
    $params[] = $status_filter;
}

$stmt = $pdo->prepare("
    SELECT tr.*, u.full_name as customer_name, u.phone as cust_phone
    FROM taxi_rides tr
    LEFT JOIN users u ON tr.customer_id = u.id
    $where
    ORDER BY tr.created_at DESC
");
$stmt->execute($params);
$rides = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rides - Taxi Dashboard</title>
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
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_taxi.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0"><i class="fas fa-route me-2 text-primary-green"></i>All Rides</h2>
                    <p class="text-muted">Manage rides for
                        <?php echo htmlspecialchars($company['company_name']); ?>
                    </p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="?status=requested"
                        class="btn btn-outline-warning rounded-pill <?php echo $status_filter === 'requested' ? 'active' : ''; ?>">Requested</a>
                    <a href="?status=accepted"
                        class="btn btn-outline-info rounded-pill <?php echo $status_filter === 'accepted' ? 'active' : ''; ?>">Accepted</a>
                    <a href="?status=in_progress"
                        class="btn btn-outline-primary rounded-pill <?php echo $status_filter === 'in_progress' ? 'active' : ''; ?>">In
                        Progress</a>
                    <a href="?status=completed"
                        class="btn btn-outline-success rounded-pill <?php echo $status_filter === 'completed' ? 'active' : ''; ?>">Completed</a>
                    <a href="rides.php" class="btn btn-outline-secondary rounded-pill">All</a>
                </div>
            </div>

            <?php if (empty($rides)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-taxi text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No rides found</h4>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($rides as $ride): ?>
                        <div class="col-lg-6">
                            <div class="card border-0 shadow-sm h-100">
                                <div
                                    class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="fw-bold mb-0">
                                            <?php echo htmlspecialchars($ride['ride_reference'] ?? '#' . $ride['id']); ?>
                                        </h6>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y - h:i A', strtotime($ride['created_at'])); ?>
                                        </small>
                                    </div>
                                    <?php echo getStatusBadge($ride['status']); ?>
                                </div>
                                <div class="card-body">
                                    <!-- Passenger Info -->
                                    <div class="d-flex align-items-center gap-3 mb-3 p-2 bg-light rounded-3">
                                        <div class="bg-warning rounded-circle p-2"
                                            style="width:40px;height:40px;display:flex;align-items:center;justify-content:center;">
                                            <i class="fas fa-user text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0 fw-bold">
                                                <?php echo htmlspecialchars($ride['passenger_name'] ?? $ride['customer_name'] ?? 'N/A'); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-1"></i>
                                                <?php echo htmlspecialchars($ride['passenger_phone'] ?? $ride['cust_phone'] ?? 'No phone'); ?>
                                            </small>
                                        </div>
                                    </div>

                                    <!-- Route -->
                                    <div class="mb-3 p-3 bg-light rounded-3">
                                        <div class="d-flex align-items-start gap-2 mb-2">
                                            <i class="fas fa-map-marker-alt text-success mt-1"></i>
                                            <div>
                                                <small class="text-muted">Pickup</small>
                                                <p class="mb-0 fw-bold">
                                                    <?php echo htmlspecialchars($ride['pickup_location']); ?>
                                                </p>
                                            </div>
                                        </div>
                                        <div class="d-flex align-items-start gap-2">
                                            <i class="fas fa-flag-checkered text-danger mt-1"></i>
                                            <div>
                                                <small class="text-muted">Drop-off</small>
                                                <p class="mb-0 fw-bold">
                                                    <?php echo htmlspecialchars($ride['dropoff_location']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fare -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Fare</span>
                                        <span class="fw-bold text-primary-green fs-5">
                                            <?php echo number_format($ride['fare']); ?> ETB
                                        </span>
                                    </div>

                                    <?php if ($ride['driver_name']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">Driver: <strong>
                                                    <?php echo htmlspecialchars($ride['driver_name']); ?>
                                                </strong>
                                                <?php if ($ride['vehicle_plate']): ?> (
                                                    <?php echo htmlspecialchars($ride['vehicle_plate']); ?>)
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="mt-3 pt-3 border-top">
                                        <form method="POST" class="d-flex gap-2 flex-wrap">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="ride_id" value="<?php echo $ride['id']; ?>">
                                            <input type="hidden" name="update_ride_status" value="1">

                                            <?php if ($ride['status'] === 'requested'): ?>
                                                <button type="submit" name="status" value="accepted"
                                                    class="btn btn-primary-green rounded-pill flex-grow-1">
                                                    <i class="fas fa-check me-1"></i>Accept Ride
                                                </button>
                                                <button type="submit" name="status" value="cancelled"
                                                    class="btn btn-outline-danger rounded-pill">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            <?php elseif ($ride['status'] === 'accepted'): ?>
                                                <button type="submit" name="status" value="in_progress"
                                                    class="btn btn-primary rounded-pill flex-grow-1">
                                                    <i class="fas fa-play me-1"></i>Start Ride
                                                </button>
                                            <?php elseif ($ride['status'] === 'in_progress'): ?>
                                                <button type="submit" name="status" value="completed"
                                                    class="btn btn-success rounded-pill flex-grow-1">
                                                    <i class="fas fa-flag-checkered me-1"></i>Complete Ride
                                                </button>
                                            <?php elseif ($ride['status'] === 'completed'): ?>
                                                <span class="text-success"><i class="fas fa-check-circle me-1"></i>Ride
                                                    completed</span>
                                            <?php else: ?>
                                                <span class="text-muted">Ride was cancelled</span>
                                            <?php endif; ?>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>