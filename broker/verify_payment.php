<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$request_id = intval($_GET['request_id'] ?? 0);

if (!$request_id) {
    header("Location: requests.php");
    exit();
}

// Verify ownership of the request
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.user_id as owner_id
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    WHERE rr.id = ? AND l.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    redirectWithMessage('requests.php', 'danger', 'Unauthorized or request not found.');
}

// Handle action (Confirm/Reject)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_proof'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $proof_id = intval($_POST['proof_id']);
        $new_status = sanitize($_POST['status']);
        
        try {
            $pdo->beginTransaction();
            
            // Update proof status
            $stmt = $pdo->prepare("UPDATE rental_payment_proofs SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $proof_id]);

            if ($new_status === 'confirmed') {
                // Fetch the proof to get the amount
                $stmt_p = $pdo->prepare("SELECT amount FROM rental_payment_proofs WHERE id = ?");
                $stmt_p->execute([$proof_id]);
                $p_data = $stmt_p->fetch();

                // 1. Mark listing as rented with duration
                $stmt_list = $pdo->prepare("UPDATE listings SET status = 'rented', rented_duration = ?, rented_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt_list->execute([$request['duration_months'] ?? 1, $request['listing_id']]);

                // 2. Mark request as approved (in case it wasn't already, though it should be)
                $stmt_req = $pdo->prepare("UPDATE rental_requests SET status = 'approved' WHERE id = ?");
                $stmt_req->execute([$request_id]);

                // 3. Handle broker referral if applicable
                if ($request['broker_id'] && $p_data) {
                    $check_ref = $pdo->prepare("SELECT id FROM referrals WHERE request_id = ?");
                    $check_ref->execute([$request_id]);
                    if (!$check_ref->fetch()) {
                        $commission = $p_data['amount'] * 0.15; // 15% commission
                        $stmt_ref = $pdo->prepare("INSERT INTO referrals (broker_id, request_id, commission_amount, status) VALUES (?, ?, ?, 'pending')");
                        $stmt_ref->execute([$request['broker_id'], $request_id, $commission]);
                    }
                }
            }
            
            $pdo->commit();
            setFlashMessage('Payment proof status updated to ' . $new_status, 'success');
        } catch (Exception $e) {
            $pdo->rollBack();
            setFlashMessage('Update failed: ' . $e->getMessage(), 'error');
        }
        header("Location: verify_payment.php?request_id=" . $request_id);
        exit();
    }
}

// Fetch all proofs for this request
$stmt = $pdo->prepare("
    SELECT p.*, pm.method_type, pm.account_number 
    FROM rental_payment_proofs p
    LEFT JOIN owner_payment_methods pm ON p.payment_method_id = pm.id
    WHERE p.request_id = ?
    ORDER BY p.submitted_at DESC
");
$stmt->execute([$request_id]);
$proofs = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Payment Proofs - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { margin-left: 260px; padding: 32px; width: 100%; min-height: 100vh; }
        .proof-card { background: #fff; border-radius: 16px; border: none; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .proof-img { width: 100%; max-height: 500px; object-fit: contain; border-radius: 8px; cursor: pointer; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>
        <div class="main-content">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Verify Payments</h2>
                    <p class="text-muted">Reviewing payments for: <strong><?php echo htmlspecialchars($request['listing_title']); ?></strong></p>
                </div>
                <a href="requests.php" class="btn btn-outline-secondary rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Back</a>
            </div>

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> rounded-4 border-0 shadow-sm mb-4"><?php echo $flash['message']; ?></div>
            <?php endif; ?>

            <?php if (empty($proofs)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-search-dollar fs-1 opacity-25 mb-3"></i>
                    <p>No payment proofs submitted for this request yet.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($proofs as $p): ?>
                        <div class="col-12">
                            <div class="proof-card p-4">
                                <div class="row g-4">
                                    <div class="col-lg-6">
                                        <h6 class="fw-bold mb-3 text-uppercase small text-muted">Receipt Screenshot</h6>
                                        <?php if ($p['proof_image_path']): ?>
                                            <img src="<?php echo BASE_URL . '/' . $p['proof_image_path']; ?>" class="proof-img shadow-sm" onclick="window.open(this.src)" title="Click to enlarge">
                                        <?php else: ?>
                                            <div class="p-5 bg-light text-center text-muted rounded">No image attached</div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-lg-6">
                                        <div class="d-flex justify-content-between align-items-start mb-4">
                                            <div>
                                                <h5 class="fw-bold mb-1">Amount: <?php echo number_format($p['amount']); ?> ETB</h5>
                                                <span class="badge <?php echo $p['status'] === 'confirmed' ? 'bg-success' : ($p['status'] === 'rejected' ? 'bg-danger' : 'bg-warning'); ?> rounded-pill px-3 py-2">
                                                    Status: <?php echo ucfirst($p['status']); ?>
                                                </span>
                                            </div>
                                            <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($p['submitted_at'])); ?></small>
                                        </div>

                                        <div class="bg-light p-3 rounded-4 mb-4">
                                            <div class="row g-3">
                                                <div class="col-sm-6">
                                                    <p class="mb-0 small text-muted">Reference Number</p>
                                                    <p class="mb-0 fw-bold"><?php echo htmlspecialchars($p['reference_number']); ?></p>
                                                </div>
                                                <div class="col-sm-6">
                                                    <p class="mb-0 small text-muted">Method Used</p>
                                                    <p class="mb-0 fw-bold"><?php echo strtoupper($p['method_type'] ?: 'Unknown'); ?></p>
                                                </div>
                                                <div class="col-12">
                                                    <p class="mb-0 small text-muted">Customer Note</p>
                                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($p['note'] ?: 'No note provided.')); ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if ($p['status'] === 'pending'): ?>
                                            <div class="d-flex gap-2">
                                                <form method="POST" class="flex-grow-1">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="proof_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="action_proof" value="1">
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="btn btn-success w-100 py-3 rounded-pill fw-bold" onclick="return confirm('Mark this payment as RECEIVED?')">
                                                        <i class="fas fa-check-circle me-1"></i> Confirm Payment
                                                    </button>
                                                </form>
                                                <form method="POST" class="flex-grow-1">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="proof_id" value="<?php echo $p['id']; ?>">
                                                    <input type="hidden" name="action_proof" value="1">
                                                    <input type="hidden" name="status" value="rejected">
                                                    <button type="submit" class="btn btn-danger w-100 py-3 rounded-pill fw-bold" onclick="return confirm('REJECT this payment proof?')">
                                                        <i class="fas fa-times-circle me-1"></i> Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info py-3 border-0 rounded-4">
                                                This proof was marked as <strong><?php echo $p['status']; ?></strong> on <?php echo date('M d, Y', strtotime($p['submitted_at'])); ?>.
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="mt-4">
                                            <a href="chat.php?request_id=<?php echo $request_id; ?>" class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold">
                                                <i class="fas fa-comments me-2"></i> Discuss with Customer
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
