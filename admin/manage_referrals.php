<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireRole('admin');

// Handle Payout (Mark as Paid)
if (isset($_POST['payout_id'])) {
    $ref_id = (int)$_POST['payout_id'];
    
    try {
        $pdo->beginTransaction();
        
        // 1. Get referral details
        $stmt = $pdo->prepare("SELECT r.*, b.id as broker_id_table FROM referrals r JOIN brokers b ON r.broker_id = b.id WHERE r.id = ?");
        $stmt->execute([$ref_id]);
        $ref = $stmt->fetch();
        
        if ($ref && $ref['status'] === 'pending') {
            // 2. Mark as paid
            $stmt = $pdo->prepare("UPDATE referrals SET status = 'paid' WHERE id = ?");
            $stmt->execute([$ref_id]);
            
            // 3. Update broker's total earnings
            $stmt = $pdo->prepare("UPDATE brokers SET total_earnings = total_earnings + ? WHERE id = ?");
            $stmt->execute([$ref['commission_amount'], $ref['broker_id_table']]);
            
            $pdo->commit();
            setFlashMessage('Commission marked as PAID and broker account updated.', 'success');
        } else {
            $pdo->rollBack();
            setFlashMessage('Referral not found or already paid.', 'error');
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        setFlashMessage('Failed to update payout: ' . $e->getMessage(), 'error');
    }
    header("Location: manage_referrals.php");
    exit();
}

// Fetch all referrals with details
$stmt = $pdo->prepare("
    SELECT r.*, 
           COALESCE(u_order.full_name, u_rent.full_name, 'Guest') as customer_name,
           b_user.full_name as broker_name,
           CASE WHEN r.request_id IS NOT NULL THEN 'Rental' ELSE 'Order' END as ref_type,
           COALESCE(l.title, 'Market Purchase') as item_name
    FROM referrals r
    JOIN brokers b ON r.broker_id = b.id
    JOIN users b_user ON b.user_id = b_user.id
    LEFT JOIN orders o ON r.order_id = o.id
    LEFT JOIN users u_order ON o.customer_id = u_order.id
    LEFT JOIN rental_requests rr ON r.request_id = rr.id
    LEFT JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users u_rent ON rr.customer_id = u_rent.id
    GROUP BY r.id
    ORDER BY r.status ASC, r.created_at DESC
");
$stmt->execute();
$referrals = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Referrals & Payouts - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .main-content { margin-left: 260px; padding: 32px; min-height: 100vh; }
        .payout-card { background: #fff; border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1"><i class="fas fa-hand-holding-usd text-success me-2"></i>Broker Referrals & Payouts</h2>
                <p class="text-muted">Review and approve commission payouts to your agents and brokers.</p>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> rounded-4 border-0 shadow-sm mb-4"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <div class="card payout-card overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Agent / Broker</th>
                                <th>Type</th>
                                <th>Customer</th>
                                <th>Item / Service</th>
                                <th>Commission</th>
                                <th>Status</th>
                                <th class="text-end px-4">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($referrals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">No referrals recorded yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($referrals as $r): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($r['broker_name']); ?></div>
                                            <small class="text-muted">Agent ID: #<?php echo $r['broker_id']; ?></small>
                                        </td>
                                        <td>
                                            <span class="badge rounded-pill <?php echo $r['ref_type'] === 'Rental' ? 'bg-primary-green' : 'bg-info'; ?> px-3">
                                                <?php echo $r['ref_type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['customer_name']); ?></td>
                                        <td class="small"><?php echo htmlspecialchars($r['item_name']); ?></td>
                                        <td class="fw-bold text-success"><?php echo number_format($r['commission_amount']); ?> ETB</td>
                                        <td>
                                            <?php if ($r['status'] === 'paid'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success px-3 py-2 rounded-pill">
                                                    <i class="fas fa-check-circle me-1"></i> Paid
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning px-3 py-2 rounded-pill">
                                                    <i class="fas fa-clock me-1"></i> Pending
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <?php if ($r['status'] === 'pending'): ?>
                                                <form method="POST" class="d-inline">
                                                    <input type="hidden" name="payout_id" value="<?php echo $r['id']; ?>">
                                                    <button type="submit" class="btn btn-primary-green btn-sm rounded-pill px-3" onclick="return confirm('Mark this commission as PAID TO BROKER?')">
                                                        Pay Out
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small">Completed</span>
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
</body>
</html>
