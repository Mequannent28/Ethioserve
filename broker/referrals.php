<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$user_role = $_SESSION['role'] ?? '';

// Handle Owner Payout (Mark as Paid or Rejected)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ref_id = (int)($_POST['ref_id'] ?? 0);
    
    // Verify that the user is the owner of the listing for this referral
    $stmt = $pdo->prepare("
        SELECT r.id, r.commission_amount, b.id as broker_id_table 
        FROM referrals r 
        JOIN rental_requests rr ON r.request_id = rr.id 
        JOIN listings l ON rr.listing_id = l.id 
        JOIN brokers b ON r.broker_id = b.id
        WHERE r.id = ? AND l.user_id = ? AND r.status = 'pending'
    ");
    $stmt->execute([$ref_id, $user_id]);
    $ref_to_pay = $stmt->fetch();
    
    if ($ref_to_pay) {
        if (isset($_POST['mark_paid'])) {
            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare("UPDATE referrals SET status = 'paid' WHERE id = ?");
                $stmt->execute([$ref_id]);
                
                $stmt = $pdo->prepare("UPDATE brokers SET total_earnings = total_earnings + ? WHERE id = ?");
                $stmt->execute([$ref_to_pay['commission_amount'], $ref_to_pay['broker_id_table']]);
                
                $pdo->commit();
                setFlashMessage('Commission approved and marked as PAID.', 'success');
            } catch (Exception $e) {
                $pdo->rollBack();
                setFlashMessage('Error processing payout.', 'error');
            }
        } elseif (isset($_POST['reject_ref'])) {
            $stmt = $pdo->prepare("UPDATE referrals SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$ref_id]);
            setFlashMessage('Referral commission has been rejected.', 'info');
        }
    }
    header("Location: referrals.php");
    exit();
}

// Get user's own broker info (for their own referrals)
$stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
$stmt->execute([$user_id]);
$my_broker = $stmt->fetch();

if (!$my_broker && $user_role === 'broker') {
    $ref_code = 'REF' . strtoupper(substr(uniqid(), -6));
    $stmt = $pdo->prepare("INSERT INTO brokers (user_id, referral_code) VALUES (?, ?)");
    $stmt->execute([$user_id, $ref_code]);
    $stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_broker = $stmt->fetch();
}

// Totals
$total_earned = 0;
$pending_amount = 0;

// Fetch all relevant referrals
// 1. Where I am the broker (Earnings)
// 2. Where I am the owner of the property (Payouts)
$referrals = [];
$my_broker_id = $my_broker ? $my_broker['id'] : 0;

$stmt = $pdo->prepare("
    SELECT r.*, 
           COALESCE(o.total_amount, rp.amount, 0) as total_amount,
           COALESCE(u_order.full_name, u_rent.full_name, 'Guest') as customer_name,
           b_user.full_name as agent_name,
           CASE WHEN r.request_id IS NOT NULL THEN 'Rental' ELSE 'Order' END as ref_type,
           COALESCE(l.title, 'Market Purchase') as item_name,
           CASE WHEN l.user_id = ? THEN 'Payout' ELSE 'Earned' END as relation,
           l.user_id as listing_owner_id
    FROM referrals r
    LEFT JOIN orders o ON r.order_id = o.id
    LEFT JOIN users u_order ON o.customer_id = u_order.id
    LEFT JOIN rental_requests rr ON r.request_id = rr.id
    LEFT JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users u_rent ON rr.customer_id = u_rent.id
    LEFT JOIN rental_payment_proofs rp ON r.request_id = rp.request_id AND rp.status = 'confirmed'
    LEFT JOIN brokers b ON r.broker_id = b.id
    LEFT JOIN users b_user ON b.user_id = b_user.id
    WHERE r.broker_id = ? OR l.user_id = ?
    GROUP BY r.id
    ORDER BY r.created_at DESC
");
$stmt->execute([$user_id, $my_broker_id, $user_id]);
$referrals = $stmt->fetchAll();

foreach ($referrals as $ref) {
    if ($ref['relation'] === 'Earned') {
        if ($ref['status'] === 'paid') {
            $total_earned += (float)$ref['commission_amount'];
        } else {
            $pending_amount += (float)$ref['commission_amount'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Referrals & Commissions - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { overflow-x: hidden; background: #f0f2f5; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { margin-left: 260px; padding: 30px 32px; background: #f0f2f5; min-height: 100vh; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }

        .stat-card {
            border-radius: 18px; border: none; padding: 24px 22px; color: #fff;
            transition: transform 0.3s ease; position: relative; overflow: hidden;
        }
        .stat-card:hover { transform: translateY(-5px); }
        .stat-card::after {
            content: ''; position: absolute; right: -20px; top: -20px;
            width: 110px; height: 110px; border-radius: 50%; background: rgba(255,255,255,0.08);
        }
        .stat-card .label { font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 1.2px; opacity: 0.8; margin-bottom: 8px; }
        .stat-card .value { font-size: 2rem; font-weight: 800; line-height: 1; }
        .stat-card .sub   { font-size: 0.78rem; opacity: 0.7; margin-top: 6px; }
        .stat-card .icon-bg { position: absolute; right: 22px; top: 50%; transform: translateY(-50%); font-size: 2.4rem; opacity: 0.18; }

        .referral-banner {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            border-radius: 18px; padding: 28px 32px; color: white;
            display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;
        }
        .code-display {
            font-family: 'Courier New', monospace; font-size: 1.8rem; font-weight: 900;
            letter-spacing: 5px; background: rgba(255,255,255,0.15); border: 1px dashed rgba(255,255,255,0.4);
            border-radius: 12px; padding: 10px 24px; display: inline-block;
        }

        .content-card { background: #fff; border-radius: 18px; border: none; box-shadow: 0 2px 14px rgba(0,0,0,0.05); }
        .content-card .card-header-custom {
            padding: 20px 24px 14px;
            display: flex; justify-content: space-between; align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }
        .content-card .card-header-custom h5 { font-weight: 700; margin: 0; font-size: 1rem; }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="main-content">

            <!-- Flash Messages -->
            <?php echo displayFlashMessage(); ?>

            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <div>
                    <h2 class="fw-bold mb-0" style="font-size:1.65rem;">Referrals &amp; Commissions</h2>
                    <p class="text-muted mb-0">Track your referral earnings and commission history.</p>
                </div>
            </div>

            <!-- Stats Row -->
            <div class="row g-4 mb-4">
                <div class="col-6 col-md-4">
                    <div class="stat-card shadow-sm" style="background: linear-gradient(135deg, #1B5E20, #43A047);">
                        <p class="label">Total Referrals</p>
                        <div class="value"><?php echo count($referrals); ?></div>
                        <p class="sub"><i class="fas fa-users me-1"></i> all time</p>
                        <i class="fas fa-user-friends icon-bg"></i>
                    </div>
                </div>
                <div class="col-6 col-md-4">
                    <div class="stat-card shadow-sm" style="background: linear-gradient(135deg, #F9A825, #F57F17);">
                        <p class="label">Pending Earnings</p>
                        <div class="value"><?php echo number_format($pending_amount / 1000, 1); ?>k</div>
                        <p class="sub"><i class="fas fa-clock me-1"></i> ETB unpaid</p>
                        <i class="fas fa-hourglass-half icon-bg"></i>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="stat-card shadow-sm" style="background: linear-gradient(135deg, #00695C, #26A69A);">
                        <p class="label">Total Earned</p>
                        <div class="value"><?php echo number_format($total_earned / 1000, 1); ?>k</div>
                        <p class="sub"><i class="fas fa-check-circle me-1"></i> ETB paid out</p>
                        <i class="fas fa-money-bill-wave icon-bg"></i>
                    </div>
                </div>
            </div>

            <!-- Referral Code Banner -->
            <div class="referral-banner shadow-sm mb-4">
                <div>
                    <p class="small fw-bold opacity-75 text-uppercase mb-2"><i class="fas fa-share-alt me-2"></i>Your Referral Code</p>
                    <div class="code-display"><?php echo htmlspecialchars($broker['referral_code'] ?? 'N/A'); ?></div>
                    <p class="small opacity-70 mt-3 mb-0">Share this code with customers. When they use it at checkout, you automatically earn a commission.</p>
                </div>
                <div>
                    <button class="btn btn-warning rounded-pill px-5 py-2 fw-bold shadow"
                        id="copyCodeBtn"
                        onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($broker['referral_code']); ?>').then(() => { this.innerHTML='<i class=\'fas fa-check me-2\'></i>Copied!'; setTimeout(() => this.innerHTML='<i class=\'fas fa-copy me-2\'></i>Copy Code', 2000); })">
                        <i class="fas fa-copy me-2"></i>Copy Code
                    </button>
                </div>
            </div>

            <!-- Referrals Table -->
            <div class="content-card">
                <div class="card-header-custom">
                    <h5><i class="fas fa-list text-primary-green me-2"></i>Referral History</h5>
                    <span class="badge bg-light text-dark border"><?php echo count($referrals); ?> records</span>
                </div>

                <?php if (empty($referrals)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="fas fa-user-plus fs-1 opacity-25 mb-3 d-block"></i>
                        <h5 class="fw-bold mb-1">No referrals yet</h5>
                        <p class="small">Share your referral code and start earning commissions when customers use it at checkout.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4 py-3">Role</th>
                                    <th>Customer</th>
                                    <th>Agent</th>
                                    <th>Item / Service</th>
                                    <th>Value</th>
                                    <th>Commission</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($referrals as $i => $ref): ?>
                                    <tr>
                                        <td class="px-4">
                                            <span class="badge rounded-pill <?php echo $ref['relation'] === 'Payout' ? 'bg-warning text-dark' : 'bg-primary-green'; ?> py-1 px-3">
                                                <?php echo $ref['relation']; ?>
                                            </span>
                                            <small class="d-block text-muted" style="font-size:0.6rem;"><?php echo $ref['ref_type']; ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($ref['customer_name']); ?></div>
                                        </td>
                                        <td>
                                            <div class="small fw-bold"><?php echo htmlspecialchars($ref['agent_name'] ?? 'N/A'); ?></div>
                                        </td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($ref['item_name']); ?></td>
                                        <td class="text-muted"><?php echo number_format($ref['total_amount']); ?> ETB</td>
                                        <td class="fw-bold text-primary-green">
                                            <?php echo number_format($ref['commission_amount']); ?> ETB
                                        </td>
                                        <td>
                                            <?php if ($ref['status'] === 'paid'): ?>
                                                <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#E8F5E9;color:#1B5E20;">Approved</span>
                                                <div class="mt-1">
                                                    <a href="javascript:void(0)" onclick="viewVoucher(<?php echo $ref['id']; ?>)" class="text-primary text-decoration-none small fw-bold">
                                                        <i class="fas fa-file-invoice-dollar me-1"></i> Get Voucher
                                                    </a>
                                                </div>
                                            <?php elseif ($ref['status'] === 'rejected'): ?>
                                                <span class="badge rounded-pill px-3 py-2 fw-bold bg-danger-subtle text-danger">Rejected</span>
                                            <?php else: ?>
                                                <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#FFF3E0;color:#E65100;">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if ($ref['relation'] === 'Payout' && $ref['status'] === 'pending'): ?>
                                                <form method="POST" class="d-flex gap-1 justify-content-end">
                                                    <input type="hidden" name="ref_id" value="<?php echo $ref['id']; ?>">
                                                    <button type="submit" name="mark_paid" class="btn btn-primary-green btn-sm rounded-pill px-3 py-1 shadow-sm" onclick="return confirm('Confirm payment to Agent <?php echo htmlspecialchars($ref['agent_name']); ?>?')">
                                                        Approve
                                                    </button>
                                                    <button type="submit" name="reject_ref" class="btn btn-outline-danger btn-sm rounded-pill px-3 py-1 shadow-sm" onclick="return confirm('Are you sure you want to REJECT this commission?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            <?php elseif ($ref['relation'] === 'Earned' && $ref['status'] === 'paid'): ?>
                                                <button type="button" class="btn btn-outline-primary btn-sm rounded-pill px-3 py-1" onclick="viewVoucher(<?php echo $ref['id']; ?>)">
                                                    <i class="fas fa-print me-1"></i> View & Print
                                                </button>
                                            <?php elseif ($ref['relation'] === 'Earned'): ?>
                                                <span class="text-muted small">My Earnings</span>
                                            <?php else: ?>
                                                <span class="text-success small"><i class="fas fa-check-circle"></i> Completed</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="bg-light">
                                <tr>
                                    <td colspan="5" class="px-4 fw-bold text-end">Totals:</td>
                                    <td class="fw-bold text-primary-green"><?php echo number_format($total_earned + $pending_amount); ?> ETB</td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Voucher Modal -->
    <div class="modal fade" id="voucherModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 pb-0 bg-light rounded-top-4">
                    <h5 class="modal-title fw-bold text-primary-green"><i class="fas fa-file-invoice-dollar me-2"></i>Commission Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-0">
                    <iframe id="voucherFrame" src="" style="width:100%; height:950px; border:none; border-radius: 0 0 16px 16px;"></iframe>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function viewVoucher(id) {
            document.getElementById('voucherFrame').src = 'print_commission.php?id=' + id;
            var myModal = new bootstrap.Modal(document.getElementById('voucherModal'));
            myModal.show();
        }
    </script>
</body>

</html>