<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $id = (int) $_POST['item_id'];
        $status = sanitize($_POST['status']);
        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE taxi_companies SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            redirectWithMessage('manage_taxi.php', 'success', 'Taxi company status updated');
        }
    }
}

// Handle manual taxi company addition removed - now handled in add_taxi.php

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM taxi_companies WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_taxi.php', 'success', 'Taxi company deleted');
}

// Fetch all taxi companies
$items = [];
try {
    $stmt = $pdo->query("SELECT tc.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                         FROM taxi_companies tc 
                         JOIN users u ON tc.user_id = u.id 
                         ORDER BY CASE WHEN tc.status = 'pending' THEN 1 WHEN tc.status = 'approved' THEN 2 ELSE 3 END, tc.company_name");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
    // Table might not exist
}

$pending = count(array_filter($items, fn($i) => $i['status'] === 'pending'));
$approved = count(array_filter($items, fn($i) => $i['status'] === 'approved'));
$rejected = count(array_filter($items, fn($i) => $i['status'] === 'rejected'));

// Get ride counts per company
$ride_counts = [];
try {
    $stmt = $pdo->query("SELECT taxi_company_id, COUNT(*) as cnt FROM taxi_rides GROUP BY taxi_company_id");
    while ($row = $stmt->fetch()) {
        $ride_counts[$row['taxi_company_id']] = $row['cnt'];
    }
} catch (Exception $e) {
}

// Get vehicle counts per company
$vehicle_counts = [];
try {
    $stmt = $pdo->query("SELECT company_id, COUNT(*) as cnt FROM taxi_vehicles GROUP BY company_id");
    while ($row = $stmt->fetch()) {
        $vehicle_counts[$row['company_id']] = $row['cnt'];
    }
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Taxi - Admin</title>
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

        .company-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
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
                    <h2 class="fw-bold mb-1"><i class="fas fa-taxi text-warning me-2"></i>Manage Taxi Companies</h2>
                    <p class="text-muted mb-0">Manage Ride, Feres, Yango and other taxi owners</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="add_taxi.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Company
                    </a>
                    <span class="badge bg-warning text-dark fs-6 rounded-pill px-3 py-2">
                        <?php echo count($items); ?> Companies
                    </span>
                </div>
            </div>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Pending</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $pending; ?>
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
                                <p class="mb-0 small fw-bold">Approved</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $approved; ?>
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
                                <p class="mb-0 small fw-bold">Rejected</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $rejected; ?>
                                </h3>
                            </div>
                            <i class="fas fa-times-circle fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Rides</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo array_sum($ride_counts); ?>
                                </h3>
                            </div>
                            <i class="fas fa-road fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Company</th>
                                <th>Owner</th>
                                <th>Contact</th>
                                <th>Vehicles</th>
                                <th>Rides</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-taxi fs-1 mb-3 d-block"></i>
                                        No taxi companies registered yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-warning bg-opacity-10 p-2 rounded-3">
                                                    <i class="fas fa-taxi text-warning fs-5"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['company_name']); ?>
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <?php echo htmlspecialchars($item['address'] ?? ''); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="fw-bold">
                                                <?php echo htmlspecialchars($item['owner_name']); ?>
                                            </div>
                                            <div class="text-muted small">
                                                <?php echo htmlspecialchars($item['owner_email']); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fas fa-phone text-success me-1"></i>
                                                <?php echo htmlspecialchars($item['phone'] ?? 'N/A'); ?>
                                            </div>
                                            <div class="small"><i class="fas fa-envelope text-info me-1"></i>
                                                <?php echo htmlspecialchars($item['email'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-car me-1"></i>
                                                <?php echo $vehicle_counts[$item['id']] ?? $item['total_vehicles'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo $ride_counts[$item['id']] ?? 0; ?> rides
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark">
                                                <i class="fas fa-star text-warning me-1"></i>
                                                <?php echo number_format($item['rating'] ?? 0, 1); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($item['status']); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <?php if ($item['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <input type="hidden" name="update_status" value="1">
                                                    <button type="submit" name="status" value="approved"
                                                        class="btn btn-sm btn-success rounded-pill px-3 me-1">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="submit" name="status" value="rejected"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <div class="d-flex gap-2">
                                                    <a href="view_taxi.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-view-vivid btn-action" title="Full Page View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_taxi.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-edit-vivid btn-action" title="Full Page Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-delete-vivid btn-action"
                                                        onclick="return confirm('Delete this taxi company and all its data?')"
                                                        title="Delete">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
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

    <!-- Add Taxi Modal removed - handled in add_taxi.php -->
</body>

</html>