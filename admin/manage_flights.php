<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$flash = getFlashMessage();

// Handle new flight registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_flight'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('manage_flights.php', 'error', 'Invalid security token');
    }

    $airline = sanitize($_POST['airline']);
    $flight_number = sanitize($_POST['flight_number']);
    $destination = sanitize($_POST['destination']);
    $departure_time = $_POST['departure_time'];
    $arrival_time = $_POST['arrival_time'];
    $price = (float) $_POST['price'];

    try {
        $stmt = $pdo->prepare("INSERT INTO flights (airline, flight_number, destination, departure_time, arrival_time, price) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$airline, $flight_number, $destination, $departure_time, $arrival_time, $price]);
        redirectWithMessage('manage_flights.php', 'success', 'New flight registered successfully!');
    } catch (Exception $e) {
        redirectWithMessage('manage_flights.php', 'error', 'Failed to register flight. Flight number might already exist.');
    }
}

// Handle flight deletion
if (isset($_GET['delete_id'])) {
    $id = (int) $_GET['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM flights WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_flights.php', 'success', 'Flight deleted successfully');
}

// Fetch all flights
$stmt = $pdo->query("SELECT * FROM flights ORDER BY created_at DESC");
$flights = $stmt->fetchAll();

$current_page = 'manage_flights.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Flights - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="bg-light">
    <div>
        <?php include('../includes/sidebar_admin.php'); ?>

        <div style="margin-left:240px;padding:40px;min-height:100vh;" class="overflow-auto">
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-1">Flight Management</h2>
                    <p class="text-muted mb-0">Register and manage international flight destinations.</p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#addFlightModal">
                    <i class="fas fa-plus me-2"></i>Register New Flight
                </button>
            </div>

            <?php echo displayFlashMessage(); ?>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="bg-white">
                            <tr class="text-muted small text-uppercase fw-bold">
                                <th class="px-4 py-3 border-0">Airline / No.</th>
                                <th class="py-3 border-0">Destination</th>
                                <th class="py-3 border-0">Departure</th>
                                <th class="py-3 border-0">Price</th>
                                <th class="py-3 border-0">Status</th>
                                <th class="px-4 py-3 border-0 text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($flights as $flight): ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light p-2 rounded">
                                                <i class="fas fa-plane text-primary-green"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-0 fw-bold">
                                                    <?php echo htmlspecialchars($flight['airline']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($flight['flight_number']); ?>
                                                </small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="fw-bold">
                                            <?php echo htmlspecialchars($flight['destination']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small>
                                            <?php echo date('M d, H:i', strtotime($flight['departure_time'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="text-primary-green fw-bold">
                                            <?php echo number_format($flight['price']); ?> ETB
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo getStatusBadge($flight['status']); ?>
                                    </td>
                                    <td class="px-4 text-end">
                                        <a href="?delete_id=<?php echo $flight['id']; ?>"
                                            class="btn btn-sm btn-outline-danger rounded-circle"
                                            onclick="return confirm('Archive this flight?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Flight Modal -->
    <div class="modal fade" id="addFlightModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Register New Flight</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-0">
                    <?php echo csrfField(); ?>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Airline Name</label>
                            <input type="text" name="airline" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="e.g. Ethiopian Airlines">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Flight Number</label>
                            <input type="text" name="flight_number"
                                class="form-control rounded-pill bg-light border-0 px-4" required
                                placeholder="e.g. ET302">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Destination City/Country</label>
                            <input type="text" name="destination"
                                class="form-control rounded-pill bg-light border-0 px-4" required
                                placeholder="e.g. Dubai (DXB)">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Departure Time</label>
                            <input type="datetime-local" name="departure_time"
                                class="form-control rounded-pill bg-light border-0 px-4" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Arrival Time</label>
                            <input type="datetime-local" name="arrival_time"
                                class="form-control rounded-pill bg-light border-0 px-4" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Ticket Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="0.00">
                        </div>
                    </div>

                    <button type="submit" name="add_flight"
                        class="btn btn-primary-green w-100 rounded-pill py-3 fw-bold mt-4 shadow">
                        <i class="fas fa-check-circle me-2"></i> Register Flight
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>