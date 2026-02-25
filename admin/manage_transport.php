<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle delete booking
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM bus_bookings WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_transport.php', 'success', 'Booking deleted');
    } catch (Exception $e) {
    }
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int) $_POST['item_id'];
        $status = sanitize($_POST['status']);
        if (in_array($status, ['confirmed', 'cancelled'])) {
            try {
                $stmt = $pdo->prepare("UPDATE bus_bookings SET status = ? WHERE id = ?");
                $stmt->execute([$status, $id]);
                redirectWithMessage('manage_transport.php', 'success', 'Booking status updated');
            } catch (Exception $e) {
            }
        }
    }
}

// Fetch all bus bookings
$bookings = [];
try {
    $stmt = $pdo->query("SELECT bb.*, u.full_name as customer_name, u.phone as customer_phone,
                         s.departure_time, s.arrival_time, s.price as schedule_price,
                         r.origin, r.destination, r.distance_km, r.estimated_hours,
                         tc.company_name, b.bus_number
                         FROM bus_bookings bb 
                         LEFT JOIN users u ON bb.customer_id = u.id
                         LEFT JOIN schedules s ON bb.schedule_id = s.id
                         LEFT JOIN routes r ON s.route_id = r.id
                         LEFT JOIN buses b ON s.bus_id = b.id
                         LEFT JOIN transport_companies tc ON b.company_id = tc.id
                         ORDER BY bb.created_at DESC
                         LIMIT 100");
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
}

$pending_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'pending'));
$confirmed_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'confirmed'));
$cancelled_bookings = count(array_filter($bookings, fn($b) => $b['status'] === 'cancelled'));
$total_revenue = array_sum(array_column($bookings, 'total_amount'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Transport - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .stat-card {
            transition: transform 0.3s;
            border-radius: 15px;
        }

        .stat-card:hover {
            transform: translateY(-5px);
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
                    <h2 class="fw-bold mb-1"><i class="fas fa-bus-alt text-primary me-2"></i>Manage Transport</h2>
                    <p class="text-muted mb-0">Manage all bus bookings, schedules, and routes</p>
                </div>
                <a href="add_bus_booking.php" class="btn btn-primary-green rounded-pill px-4">
                    <i class="fas fa-plus me-2"></i>Add Booking
                </a>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Pending Bookings</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $pending_bookings; ?>
                                </h3>
                            </div>
                            <i class="fas fa-clock fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-success text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Confirmed</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $confirmed_bookings; ?>
                                </h3>
                            </div>
                            <i class="fas fa-check-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-danger text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Cancelled</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $cancelled_bookings; ?>
                                </h3>
                            </div>
                            <i class="fas fa-times-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Revenue</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo number_format($total_revenue); ?> <small>ETB</small>
                                </h3>
                            </div>
                            <i class="fas fa-coins fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white p-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-ticket-alt me-2 text-primary"></i>All Bus Bookings</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Ref</th>
                                <th>Passenger</th>
                                <th>Route</th>
                                <th>Company</th>
                                <th>Travel Date</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-bus-alt fs-1 mb-3 d-block"></i>
                                        No bus bookings yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td class="px-4 fw-bold font-monospace small">
                                            <?php echo htmlspecialchars($b['booking_reference'] ?? '#' . $b['id']); ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($b['customer_name'] ?? $b['passenger_names'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo $b['num_passengers'] ?? 1; ?> passenger(s)
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small">
                                                <i class="fas fa-map-marker-alt text-success me-1"></i>
                                                <?php echo htmlspecialchars($b['origin'] ?? 'N/A'); ?>
                                                <i class="fas fa-arrow-right mx-1 text-muted"></i>
                                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                                <?php echo htmlspecialchars($b['destination'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($b['company_name'] ?? 'N/A'); ?>
                                            </span></td>
                                        <td class="small">
                                            <?php echo $b['travel_date'] ? date('M d, Y', strtotime($b['travel_date'])) : 'N/A'; ?>
                                        </td>
                                        <td class="fw-bold text-success">
                                            <?php echo number_format($b['total_amount']); ?> ETB
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($b['status']); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <?php if ($b['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $b['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" name="status" value="confirmed"
                                                        class="btn btn-sm btn-success rounded-pill px-2"><i
                                                            class="fas fa-check"></i></button>
                                                    <button type="submit" name="status" value="cancelled"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-2"><i
                                                            class="fas fa-times"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <a href="?delete=<?php echo $b['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Delete this booking?')"><i
                                                        class="fas fa-trash"></i></a>
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>