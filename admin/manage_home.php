<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle status updates
if (isset($_GET['id']) && isset($_GET['status'])) {
    $id = intval($_GET['id']);
    $status = $_GET['status'];
    $allowed_status = ['pending', 'confirmed', 'in_progress', 'completed', 'cancelled'];

    if (in_array($status, $allowed_status)) {
        $stmt = $pdo->prepare("UPDATE home_service_bookings SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        redirectWithMessage('manage_home.php', 'success', 'Booking status updated to ' . ucfirst($status));
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $stmt = $pdo->prepare("DELETE FROM home_service_bookings WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_home.php', 'success', 'Booking deleted');
}

// Fetch Bookings
$bookings = [];
try {
    $stmt = $pdo->query("SELECT b.*, c.name as category_name, o.name as service_name, u.full_name as customer_name, u.email as customer_email, p_u.full_name as provider_name 
                         FROM home_service_bookings b
                         JOIN home_service_categories c ON b.category_id = c.id
                         JOIN home_service_options o ON b.option_id = o.id
                         JOIN users u ON b.customer_id = u.id
                         LEFT JOIN home_service_providers p ON b.provider_id = p.id
                         LEFT JOIN users p_u ON p.user_id = p_u.id
                         ORDER BY b.created_at DESC");
    $bookings = $stmt->fetchAll();
} catch (Exception $e) {
}

// Stats
$stats = [
    'pending' => 0,
    'confirmed' => 0,
    'completed' => 0,
    'total_val' => 0
];
foreach ($bookings as $b) {
    if (isset($stats[$b['status']]))
        $stats[$b['status']]++;
    if ($b['status'] === 'completed')
        $stats['total_val'] += $b['total_price'];
}

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Home Services - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
            background: #f8f9fa;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            min-height: 100vh;
        }

        .stat-card {
            border-radius: 20px;
            border: none;
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .status-pill {
            font-size: 0.75rem;
            font-weight: 600;
            border-radius: 50px;
            padding: 5px 15px;
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
                    <h2 class="fw-bold mb-1">Home Service Bookings</h2>
                    <p class="text-muted mb-0">Manage customer requests and service providers</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-white shadow-sm rounded-pill px-4"><i
                            class="fas fa-download me-2"></i>Report</button>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-5">
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">New Requests</p>
                                <h3 class="fw-bold mb-0"><?php echo $stats['pending']; ?></h3>
                            </div>
                            <div class="bg-warning bg-opacity-10 p-2 rounded-3 text-warning"><i
                                    class="fas fa-clock fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Active / Confirmed</p>
                                <h3 class="fw-bold mb-0"><?php echo $stats['confirmed']; ?></h3>
                            </div>
                            <div class="bg-info bg-opacity-10 p-2 rounded-3 text-info"><i class="fas fa-sync fs-4"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm p-4 bg-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="text-muted small mb-1">Completed</p>
                                <h3 class="fw-bold mb-0"><?php echo $stats['completed']; ?></h3>
                            </div>
                            <div class="bg-success bg-opacity-10 p-2 rounded-3 text-success"><i
                                    class="fas fa-check-double fs-4"></i></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card shadow-sm p-4 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <p class="small mb-1 opacity-75">Revenue (Completed)</p>
                                <h3 class="fw-bold mb-0"><?php echo number_format($stats['total_val']); ?> <small
                                        class="fs-6">ETB</small></h3>
                            </div>
                            <div class="bg-white bg-opacity-25 p-2 rounded-3"><i class="fas fa-coins fs-4"></i></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">ID / Service</th>
                                <th>Customer</th>
                                <th>Schedule</th>
                                <th>Address</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bookings)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No bookings found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bookings as $b): ?>
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">#<?php echo $b['id']; ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($b['service_name']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($b['customer_name']); ?></div>
                                            <div class="small text-muted"><?php echo htmlspecialchars($b['customer_email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><?php echo date('M d, Y', strtotime($b['scheduled_at'])); ?>
                                            </div>
                                            <div class="fw-bold small">
                                                <?php echo date('h:i A', strtotime($b['scheduled_at'])); ?></div>
                                        </td>
                                        <td>
                                            <div class="small text-wrap" style="max-width: 150px;">
                                                <?php echo htmlspecialchars($b['service_address']); ?></div>
                                        </td>
                                        <td class="fw-bold"><?php echo number_format($b['total_price']); ?></td>
                                        <td>
                                            <?php
                                            $colors = [
                                                'pending' => 'bg-warning text-dark',
                                                'confirmed' => 'bg-info text-white',
                                                'in_progress' => 'bg-primary text-white',
                                                'completed' => 'bg-success text-white',
                                                'cancelled' => 'bg-danger text-white'
                                            ];
                                            ?>
                                            <span class="status-pill <?php echo $colors[$b['status']]; ?>">
                                                <?php echo ucfirst($b['status']); ?>
                                            </span>
                                        </td>
                                        <td class="text-end pe-4">
                                            <div class="dropdown">
                                                <button class="btn btn-sm btn-light rounded-pill" type="button"
                                                    data-bs-toggle="dropdown">
                                                    Update <i class="fas fa-chevron-down ms-1 small"></i>
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-3">
                                                    <li><a class="dropdown-item py-2"
                                                            href="?id=<?php echo $b['id']; ?>&status=confirmed">Confirm</a></li>
                                                    <li><a class="dropdown-item py-2"
                                                            href="?id=<?php echo $b['id']; ?>&status=in_progress">Mark
                                                            In-Progress</a></li>
                                                    <li><a class="dropdown-item py-2"
                                                            href="?id=<?php echo $b['id']; ?>&status=completed">Mark
                                                            Completed</a></li>
                                                    <li>
                                                        <hr class="dropdown-divider">
                                                    </li>
                                                    <li><a class="dropdown-item py-2 text-danger"
                                                            href="?delete=<?php echo $b['id']; ?>"
                                                            onclick="return confirm('Delete booking?')">Delete</a></li>
                                                </ul>
                                            </div>
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