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
            $stmt = $pdo->prepare("UPDATE transport_companies SET status = ? WHERE id = ?");
            $stmt->execute([$status, $id]);
            redirectWithMessage('manage_bus.php', 'success', 'Bus company status updated');
        }
    }
}

// Handle manual registration removed - now handled in add_bus.php

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM transport_companies WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_bus.php', 'success', 'Bus company deleted');
}

// Fetch all bus companies
$items = [];
try {
    $stmt = $pdo->query("SELECT tc.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                         FROM transport_companies tc 
                         JOIN users u ON tc.user_id = u.id 
                         ORDER BY CASE WHEN tc.status = 'pending' THEN 1 WHEN tc.status = 'approved' THEN 2 ELSE 3 END, tc.company_name");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}

$pending = count(array_filter($items, fn($i) => $i['status'] === 'pending'));
$approved = count(array_filter($items, fn($i) => $i['status'] === 'approved'));
$rejected = count(array_filter($items, fn($i) => $i['status'] === 'rejected'));

// Get bus counts per company
$bus_counts = [];
try {
    $stmt = $pdo->query("SELECT company_id, COUNT(*) as cnt FROM buses GROUP BY company_id");
    while ($row = $stmt->fetch()) {
        $bus_counts[$row['company_id']] = $row['cnt'];
    }
} catch (Exception $e) {
}

// Get route counts per company
$route_counts = [];
try {
    $stmt = $pdo->query("SELECT company_id, COUNT(*) as cnt FROM routes GROUP BY company_id");
    while ($row = $stmt->fetch()) {
        $route_counts[$row['company_id']] = $row['cnt'];
    }
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bus Companies - Admin</title>
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
                    <h2 class="fw-bold mb-1"><i class="fas fa-bus text-info me-2"></i>Manage Bus Companies</h2>
                    <p class="text-muted mb-0">Manage Golden, Abay, Walya, Selam, Sky and other bus owners</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="add_bus.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Company
                    </a>
                    <span class="badge bg-info fs-6 rounded-pill px-3 py-2">
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
                    <div class="card stat-card border-0 shadow-sm p-3 bg-primary text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Buses</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo array_sum($bus_counts); ?>
                                </h3>
                            </div>
                            <i class="fas fa-bus fs-1 opacity-50"></i>
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
                                <th>Buses</th>
                                <th>Routes</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">
                                        <i class="fas fa-bus fs-1 mb-3 d-block"></i>
                                        No bus companies registered yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="bg-info bg-opacity-10 p-2 rounded-3">
                                                    <i class="fas fa-bus text-info fs-5"></i>
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
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><i class="fas fa-bus me-1"></i>
                                                <?php echo $bus_counts[$item['id']] ?? $item['total_buses'] ?? 0; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark"><i class="fas fa-route me-1"></i>
                                                <?php echo $route_counts[$item['id']] ?? 0; ?>
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
                                                        class="btn btn-sm btn-success rounded-pill px-3 me-1"><i
                                                            class="fas fa-check"></i></button>
                                                    <button type="submit" name="status" value="rejected"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3"><i
                                                            class="fas fa-times"></i></button>
                                                </form>
                                            <?php else: ?>
                                                <div class="d-flex gap-2">
                                                    <a href="view_bus.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-view-vivid btn-action" title="Full Page View">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="edit_bus.php?id=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-edit-vivid btn-action" title="Full Page Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?delete=<?php echo $item['id']; ?>"
                                                        class="btn btn-sm btn-delete-vivid btn-action"
                                                        onclick="return confirm('Delete this company?')" title="Delete">
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

    <!-- Add Bus Modal removed - handled in add_bus.php -->
</body>

</html>