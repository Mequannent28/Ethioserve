<?php
session_start();
require_once '../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'broker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get broker info
$stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
$stmt->execute([$user_id]);
$broker = $stmt->fetch();

// Fetch referrals for this broker
$stmt = $pdo->prepare("SELECT r.*, o.total_amount, u.full_name as customer_name 
                       FROM referrals r 
                       JOIN orders o ON r.order_id = o.id 
                       JOIN users u ON o.customer_id = u.id 
                       WHERE r.broker_id = ? 
                       ORDER BY r.created_at DESC");
$stmt->execute([$broker['id']]);
$referrals = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-primary-green sidebar collapse shadow">
            <div class="position-sticky pt-3">
                <div class="px-4 mb-4 text-center">
                    <h4 class="text-white fw-bold">Broker Hub</h4>
                    <p class="text-white-50 small">
                        <?php echo htmlspecialchars($_SESSION['full_name']); ?>
                    </p>
                </div>
                <ul class="nav flex-column">
                    <li class="nav-item">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="referrals.php">
                            <i class="fas fa-user-friends"></i> My Referrals
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">
                            <i class="fas fa-wallet"></i> Earnings
                        </a>
                    </li>
                    <li class="nav-item pt-5">
                        <a class="nav-link text-warning" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 py-4">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Referrals</h1>
                <div class="bg-white border rounded-pill px-4 py-2 shadow-sm">
                    <span class="text-muted small fw-bold">YOUR CODE:</span>
                    <span class="text-primary-green fw-bold ms-2">
                        <?php echo htmlspecialchars($broker['referral_code']); ?>
                    </span>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Date</th>
                                <th>Customer</th>
                                <th>Order Value</th>
                                <th>Your Commission</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($referrals)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="fas fa-search mb-3 fs-1 opacity-25 d-block"></i>
                                        No referrals linked to your code yet.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($referrals as $ref): ?>
                                    <tr>
                                        <td class="px-4 small text-muted">
                                            <?php echo date('M d, Y', strtotime($ref['created_at'])); ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo htmlspecialchars($ref['customer_name']); ?>
                                        </td>
                                        <td>
                                            <?php echo number_format($ref['total_amount'], 2); ?> ETB
                                        </td>
                                        <td class="text-primary-green fw-bold">
                                            <?php echo number_format($ref['commission_amount'], 2); ?> ETB
                                        </td>
                                        <td>
                                            <?php if ($ref['status'] === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success rounded-pill px-3">Paid</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning rounded-pill px-3">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</div>

<?php include '../includes/footer.php'; ?>