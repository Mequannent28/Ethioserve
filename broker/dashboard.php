<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a broker
requireRole('broker');

$user_id = getCurrentUserId();

// Get broker details
$stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
$stmt->execute([$user_id]);
$broker = $stmt->fetch();

if (!$broker) {
    // Auto-create broker record if it doesn't exist but user has the role
    $referral_code = generateReferralCode();
    $stmt = $pdo->prepare("INSERT INTO brokers (user_id, referral_code, created_at) VALUES (?, ?, NOW())");
    $stmt->execute([$user_id, $referral_code]);

    $stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $broker = $stmt->fetch();
}

$broker_id = $broker['id'];

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM referrals WHERE broker_id = ?");
$stmt->execute([$broker_id]);
$referred_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT SUM(commission_amount) FROM referrals WHERE broker_id = ? AND status = 'paid'");
$stmt->execute([$broker_id]);
$total_earnings = $stmt->fetchColumn() ?: 0;

$stmt = $pdo->prepare("SELECT SUM(commission_amount) FROM referrals WHERE broker_id = ? AND status = 'pending'");
$stmt->execute([$broker_id]);
$pending_commissions = $stmt->fetchColumn() ?: 0;

// Get recent referrals with order details
$stmt = $pdo->prepare("
    SELECT r.*, o.total_amount as order_amount, o.status as order_status,
           u.full_name as customer_name, h.name as hotel_name
    FROM referrals r
    JOIN orders o ON r.order_id = o.id
    JOIN users u ON o.customer_id = u.id
    JOIN hotels h ON o.hotel_id = h.id
    WHERE r.broker_id = ?
    ORDER BY r.created_at DESC
    LIMIT 10
");
$stmt->execute([$broker_id]);
$recent_referrals = $stmt->fetchAll();

// Get monthly earnings for chart
$stmt = $pdo->prepare("
    SELECT 
        DATE_FORMAT(r.created_at, '%Y-%m') as month,
        SUM(r.commission_amount) as earnings
    FROM referrals r
    WHERE r.broker_id = ? AND r.status = 'paid'
    GROUP BY DATE_FORMAT(r.created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 6
");
$stmt->execute([$broker_id]);
$monthly_earnings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Broker Dashboard - EthioServe</title>
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
            background-color: #f8f9fa;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Top Nav -->
            <div class="d-flex justify-content-between align-items-center mb-5">
                <div>
                    <h2 class="fw-bold mb-0">Broker Portal</h2>
                    <p class="text-muted">
                        Welcome, <?php echo htmlspecialchars(getCurrentUserName()); ?> |
                        Your Code: <strong
                            class="text-primary-green"><?php echo htmlspecialchars($broker['referral_code']); ?></strong>
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary-green rounded-pill px-4"
                        onclick="copyReferral('<?php echo htmlspecialchars($broker['referral_code']); ?>')">
                        <i class="fas fa-copy me-2"></i> Copy Code
                    </button>
                    <button class="btn btn-warning rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#shareModal">
                        <i class="fas fa-share-alt me-2"></i> Share Link
                    </button>
                    <a href="../logout.php" class="btn btn-white shadow-sm rounded-pill px-4 text-danger">
                        <i class="fas fa-sign-out-alt"></i>
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="card p-4 border-0 shadow-sm bg-primary-green text-white">
                        <p class="small mb-1 fw-bold text-uppercase opacity-75">Total Referrals</p>
                        <h2 class="fw-bold mb-3"><?php echo number_format($referred_count); ?></h2>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-white text-success rounded-pill">Lifetime activity</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 border-0 shadow-sm">
                        <p class="text-muted small mb-1 fw-bold text-uppercase">Total Earnings</p>
                        <h2 class="fw-bold mb-3"><?php echo number_format($total_earnings, 2); ?> <span
                                class="fs-6 fw-normal">ETB</span></h2>
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-success small fw-bold"><i class="fas fa-check-circle me-1"></i> Paid
                                out</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 border-0 shadow-sm border-start border-warning border-4">
                        <p class="text-muted small mb-1 fw-bold text-uppercase">Pending Commission</p>
                        <h2 class="fw-bold mb-3"><?php echo number_format($pending_commissions, 2); ?> <span
                                class="fs-6 fw-normal">ETB</span></h2>
                        <?php if ($pending_commissions > 0): ?>
                            <button class="btn btn-gold btn-sm rounded-pill px-3" data-bs-toggle="modal"
                                data-bs-target="#withdrawModal">
                                <i class="fas fa-wallet me-1"></i> Request Withdrawal
                            </button>
                        <?php else: ?>
                            <span class="text-muted small">No pending commissions</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- How Referrals Work -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="fas fa-info-circle text-primary-green me-2"></i>How Your
                        Referrals Work</h5>
                    <div class="row g-4">
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <i class="fas fa-share-alt text-primary-green"></i>
                                </div>
                                <h6 class="fw-bold">1. Share Code</h6>
                                <p class="small text-muted mb-0">Share your referral code with friends</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <i class="fas fa-shopping-cart text-primary-green"></i>
                                </div>
                                <h6 class="fw-bold">2. They Order</h6>
                                <p class="small text-muted mb-0">They use your code when ordering</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <i class="fas fa-percentage text-primary-green"></i>
                                </div>
                                <h6 class="fw-bold">3. Earn 5%</h6>
                                <p class="small text-muted mb-0">Get 5% commission on every order</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center">
                                <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                    style="width: 50px; height: 50px;">
                                    <i class="fas fa-money-bill-wave text-primary-green"></i>
                                </div>
                                <h6 class="fw-bold">4. Get Paid</h6>
                                <p class="small text-muted mb-0">Withdraw your earnings anytime</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Referrals Table -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0 py-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Recent Referral Activity</h5>
                    <a href="referrals.php" class="btn btn-light btn-sm rounded-pill">View All</a>
                </div>

                <?php if (empty($recent_referrals)): ?>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-users text-muted mb-3" style="font-size: 4rem;"></i>
                        <h5 class="text-muted">No referrals yet</h5>
                        <p class="text-muted">Share your referral code to start earning commissions!</p>
                        <button class="btn btn-primary-green rounded-pill px-4"
                            onclick="copyReferral('<?php echo htmlspecialchars($broker['referral_code']); ?>')">
                            <i class="fas fa-copy me-2"></i> Copy Your Code
                        </button>
                    </div>
                <?php else: ?>
                    <div class="table-responsive px-4 pb-4">
                        <table class="table table-hover align-middle">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 px-3">Order ID</th>
                                    <th class="border-0">Customer</th>
                                    <th class="border-0">Restaurant</th>
                                    <th class="border-0">Order Amount</th>
                                    <th class="border-0">Commission (5%)</th>
                                    <th class="border-0">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_referrals as $ref): ?>
                                    <tr>
                                        <td class="px-3 fw-bold text-primary-green">
                                            #<?php echo str_pad($ref['order_id'], 5, '0', STR_PAD_LEFT); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($ref['customer_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ref['hotel_name']); ?></td>
                                        <td><?php echo number_format($ref['order_amount']); ?> ETB</td>
                                        <td class="fw-bold text-success">
                                            <?php echo number_format($ref['commission_amount'], 2); ?> ETB</td>
                                        <td>
                                            <?php if ($ref['status'] === 'paid'): ?>
                                                <span
                                                    class="badge bg-success-subtle text-success px-3 py-2 rounded-pill">Paid</span>
                                            <?php else: ?>
                                                <span
                                                    class="badge bg-warning-subtle text-warning px-3 py-2 rounded-pill">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Share Modal -->
    <div class="modal fade" id="shareModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Share Your Referral Link</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Your Referral Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control bg-light border-0"
                                value="<?php echo htmlspecialchars($broker['referral_code']); ?>" readonly
                                id="referralCode">
                            <button class="btn btn-primary-green"
                                onclick="copyReferral('<?php echo htmlspecialchars($broker['referral_code']); ?>')">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Share via</label>
                        <div class="d-flex gap-2">
                            <a href="https://wa.me/?text=Use%20my%20referral%20code%20<?php echo urlencode($broker['referral_code']); ?>%20on%20EthioServe%20and%20get%20discounts!"
                                target="_blank" class="btn btn-success rounded-pill flex-grow-1">
                                <i class="fab fa-whatsapp me-2"></i>WhatsApp
                            </a>
                            <a href="https://t.me/share/url?text=Use%20my%20referral%20code%20<?php echo urlencode($broker['referral_code']); ?>%20on%20EthioServe!"
                                target="_blank" class="btn btn-info rounded-pill flex-grow-1 text-white">
                                <i class="fab fa-telegram me-2"></i>Telegram
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Withdrawal Modal -->
    <div class="modal fade" id="withdrawModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Request Withdrawal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        You have <strong><?php echo number_format($pending_commissions, 2); ?> ETB</strong> pending
                        commission.
                    </div>
                    <form>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Payment Method</label>
                            <select class="form-select rounded-pill bg-light border-0">
                                <option>Telebirr</option>
                                <option>CBE Birr</option>
                                <option>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Account Number</label>
                            <input type="text" class="form-control rounded-pill bg-light border-0"
                                placeholder="Enter your account number">
                        </div>
                        <button type="button" class="btn btn-primary-green w-100 rounded-pill py-3"
                            onclick="alert('Withdrawal request submitted! Our team will process it within 24-48 hours.')">
                            <i class="fas fa-paper-plane me-2"></i> Submit Request
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function copyReferral(code) {
            navigator.clipboard.writeText(code).then(() => {
                alert('Referral code copied: ' + code);
            }).catch(() => {
                // Fallback
                const input = document.getElementById('referralCode');
                input.select();
                document.execCommand('copy');
                alert('Referral code copied: ' + code);
            });
        }
    </script>
</body>

</html>