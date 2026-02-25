<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireRole('admin');
// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM brokers WHERE id = ?");
    $stmt->execute([$id]);
    redirectWithMessage('manage_brokers.php', 'success', 'Broker deleted');
}
// Fetch all brokers
$items = [];
try {
    $stmt = $pdo->query("SELECT b.*, u.full_name, u.email, u.phone, u.created_at as joined_at 
                         FROM brokers b 
                       JOIN users u ON b.user_id = u.id 
                         ORDER BY b.total_earnings DESC");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}
// Referral stats
$total_earnings = array_sum(array_column($items, 'total_earnings'));
$total_referrals = 0;
try {
    $total_referrals = $pdo->query("SELECT COUNT(*) FROM referrals")->fetchColumn();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Brokers - Admin</title>
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
                    <h2 class="fw-bold mb-1"><i class="fas fa-user-tie text-warning me-2"></i>Manage Brokers</h2>
                    <p class="text-muted mb-0">Manage broker accounts, referral codes, and commissions</p>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <a href="add_user.php?role=broker" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Broker
                    </a>
                    <span class="badge bg-warning text-dark fs-6 rounded-pill px-3 py-2">
                        <?php echo count($items); ?> Brokers
                    </span>
                </div>
            </div>
            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-primary-green text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Brokers</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo count($items); ?>
                                </h3>
                            </div>
                            <i class="fas fa-user-tie fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-warning text-dark">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Earnings</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo number_format($total_earnings); ?> <small>ETB</small>
                                </h3>
                            </div>
                            <i class="fas fa-coins fs-1 opacity-50"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card stat-card border-0 shadow-sm p-3 bg-info text-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="mb-0 small fw-bold">Total Referrals</p>
                                <h3 class="fw-bold mb-0">
                                    <?php echo $total_referrals; ?>
                                </h3>
                            </div>
                            <i class="fas fa-link fs-1 opacity-50"></i>
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
                                <th class="px-4">Broker</th>
                                <th>Referral Code</th>
                                <th>Contact</th>
                                <th>Earnings</th>
                                <th>Joined</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="fas fa-user-tie fs-1 mb-3 d-block"></i>
                                        No brokers registered yet
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($item['full_name']); ?>&background=F9A825&color=fff"
                                                    class="rounded-circle" width="45">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['full_name']); ?>
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <?php echo htmlspecialchars($item['email']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-dark px-3 py-2 font-monospace">
                                                <?php echo htmlspecialchars($item['referral_code'] ?? 'N/A'); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="small"><i class="fas fa-phone text-success me-1"></i>
                                                <?php echo htmlspecialchars($item['phone'] ?? 'N/A'); ?>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="fw-bold text-success">
                                                <?php echo number_format($item['total_earnings']); ?> ETB
                                            </span>
                                        </td>
                                        <td class="text-muted small">
                                            <?php echo date('M d, Y', strtotime($item['joined_at'])); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-info rounded-pill"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#viewItem<?php echo $item['id']; ?>"><i
                                                        class="fas fa-eye"></i></button>
                                                <a href="?delete=<?php echo $item['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Delete this broker?')"><i
                                                        class="fas fa-trash"></i></a>
                                            </div>
                                        </td>
                                    </tr>
                                    <div class="modal fade" id="viewItem<?php echo $item['id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-dialog-centered">
                                            <div class="modal-content border-0 rounded-4">
                                                <div class="modal-header border-0 pb-0">
                                                    <h5 class="modal-title fw-bold"><i
                                                            class="fas fa-user-tie text-warning me-2"></i>Broker Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body p-4">
                                                    <div class="text-center mb-3">
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($item['full_name']); ?>&background=F9A825&color=fff&size=100"
                                                            class="rounded-circle shadow">
                                                    </div>
                                                    <h4 class="fw-bold text-center">
                                                        <?php echo htmlspecialchars($item['full_name']); ?>
                                                    </h4>
                                                    <p class="text-center text-muted">
                                                        <?php echo htmlspecialchars($item['bio'] ?? 'No bio'); ?>
                                                    </p>
                                                    <hr>
                                                    <div class="row text-center">
                                                        <div class="col-6">
                                                            <p class="text-muted small mb-1">Referral Code</p>
                                                            <h5 class="fw-bold font-monospace">
                                                                <?php echo htmlspecialchars($item['referral_code'] ?? 'N/A'); ?>
                                                            </h5>
                                                        </div>
                                                        <div class="col-6">
                                                            <p class="text-muted small mb-1">Total Earnings</p>
                                                            <h5 class="fw-bold text-success">
                                                                <?php echo number_format($item['total_earnings']); ?> ETB
                                                            </h5>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="modal-footer border-0">
                                                    <button type="button" class="btn btn-light rounded-pill px-4"
                                                        data-bs-dismiss="modal">Close</button>
                                                </div>
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
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>