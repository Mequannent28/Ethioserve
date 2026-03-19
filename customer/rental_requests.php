<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();
$user_id = getCurrentUserId();

// Fetch customer's rental requests
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.type as listing_type, l.image_url as listing_img, 
           l.status as listing_final_status, l.rented_at, l.price as listing_price,
           o.full_name as owner_name, o.phone as owner_phone,
           (SELECT COUNT(*) FROM rental_chat_messages WHERE request_id = rr.id AND receiver_id = ? AND is_read = 0) as unread_messages,
           (SELECT status FROM rental_payment_proofs WHERE request_id = rr.id ORDER BY submitted_at DESC LIMIT 1) as latest_proof_status
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users o ON l.user_id = o.id
    WHERE rr.customer_id = ?
    ORDER BY rr.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$requests = $stmt->fetchAll();

include '../includes/header.php';
?>
<style>
    :root {
        --rent-primary: #1B5E20;
        --rent-gold: #F9A825;
    }
    .progress-tracker {
        display: flex;
        justify-content: space-between;
        margin-top: 25px;
        position: relative;
        padding: 0 10px;
    }
    .progress-tracker::before {
        content: '';
        position: absolute;
        top: 15px;
        left: 0;
        right: 0;
        height: 2px;
        background: #e0e0e0;
        z-index: 1;
    }
    .step {
        position: relative;
        z-index: 2;
        text-align: center;
        background: transparent;
        flex: 1;
    }
    .step-circle {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background: #fff;
        border: 2px solid #e0e0e0;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 8px;
        font-size: 0.75rem;
        font-weight: bold;
        color: #999;
        transition: all 0.3s;
    }
    .step.active .step-circle {
        border-color: var(--rent-primary);
        color: var(--rent-primary);
    }
    .step.completed .step-circle {
        background: var(--rent-primary);
        border-color: var(--rent-primary);
        color: white;
    }
    .step-label {
        font-size: 0.65rem;
        font-weight: 700;
        color: #999;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .step.active .step-label, .step.completed .step-label {
        color: #333;
    }
    .btn-primary-green { background: var(--rent-primary); color: #fff; border: none; }
    .btn-primary-green:hover { background: #2E7D32; color: #fff; }
</style>
<div class="container py-5" style="min-height: 80vh; font-family: 'Poppins', sans-serif;">
    <div class="mb-5">
        <h2 class="fw-bold mb-1">My Rental Progress</h2>
        <p class="text-muted">Track your rental journey from inquiry to key handover.</p>
    </div>

    <?php if (empty($requests)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="fas fa-home fs-1 opacity-25 mb-3 d-block"></i>
            <h4 class="fw-bold">No requests found</h4>
            <p class="text-muted px-4">You haven't sent any rental requests yet. Explore properties to get started.</p>
            <a href="rent.php" class="btn btn-primary-green rounded-pill px-5 mt-3">Explore Properties</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($requests as $req): ?>
                <?php 
                    $curStatus = $req['status'];
                    $proofStatus = $req['latest_proof_status'];
                    $listingStatus = $req['listing_final_status'];

                    // Logic for steps
                    $step1 = 'completed'; // Request sent
                    $step2 = ($curStatus === 'approved' || $curStatus === 'rejected') ? 'completed' : 'active';
                    if ($curStatus === 'rejected') $step2 = 'failed'; 
                    
                    $step3 = ($proofStatus) ? 'completed' : (($curStatus === 'approved') ? 'active' : '');
                    $step4 = ($listingStatus === 'rented' && $proofStatus === 'confirmed') ? 'completed' : (($proofStatus === 'pending') ? 'active' : '');
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-3">
                        <div class="card-body p-4">
                            <div class="row align-items-center g-4">
                                <div class="col-md-auto text-center">
                                    <div class="position-relative">
                                        <img src="<?php echo $req['listing_img'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200'; ?>" 
                                             class="rounded-3 shadow-sm" style="width:140px; height:140px; object-fit: cover;">
                                        <?php if ($listingStatus === 'rented'): ?>
                                            <span class="badge bg-danger position-absolute top-50 start-50 translate-middle px-3 py-2 shadow-sm rounded-pill text-center" style="opacity: 0.9; transform: translate(-50%, -50%) rotate(-15deg) !important; font-size: 0.8rem; min-width: 120px;">
                                                <i class="fas fa-key d-block mb-1"></i>
                                                RENTED<br>
                                                <small style="font-size: 0.6rem;"><?php 
                                                    $dur = $req['duration_months'] ?: 1;
                                                    echo 'FOR ' . $dur . ' ' . ($dur > 1 ? 'MONTHS' : 'MONTH'); 
                                                ?></small>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($req['listing_title']); ?></h5>
                                            <div class="d-flex gap-2 flex-wrap mb-2">
                                                <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-calendar-alt text-primary-green me-1"></i> <?php echo $req['duration_months']; ?> Months</span>
                                                <span class="badge bg-light text-dark border fw-normal"><i class="fas fa-user-circle text-primary-green me-1"></i> Owner: <?php echo htmlspecialchars($req['owner_name']); ?></span>
                                            </div>

                                            <?php if ($listingStatus === 'rented' && isset($req['rented_at'])): ?>
                                                <?php 
                                                    // Note: We need rented_at from the listings table join
                                                    // I'll check if rented_at was fetched in the SQL
                                                    $rent_start = strtotime($req['rented_at']);
                                                    $dur_mos = (int)($req['duration_months'] ?: 1);
                                                    $expiry = $rent_start + ($dur_mos * 30 * 24 * 60 * 60);
                                                    $left = ceil(($expiry - time()) / (24 * 60 * 60));
                                                    $color = $left > 7 ? 'text-success' : ($left > 0 ? 'text-warning' : 'text-danger');
                                                ?>
                                                <div class="bg-light p-2 rounded-3 border-start border-4 border-<?php echo str_replace('text-', '', $color); ?> mt-2 shadow-sm d-inline-block">
                                                    <div class="small fw-bold <?php echo $color; ?>">
                                                        <i class="fas fa-history me-1"></i> 
                                                        <?php echo $left > 0 ? $left . ' Days Left' : 'Period Expired'; ?>
                                                    </div>
                                                    <div class="small text-muted" style="font-size: 0.65rem;">
                                                        Next Payment: <strong><?php echo date('M d, Y', $expiry); ?></strong> (<?php echo number_format($req['listing_price']); ?> ETB)
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-end">
                                            <p class="text-muted small mb-0"><i class="fas fa-clock me-1"></i> Sent: <?php echo date('M d, Y', strtotime($req['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Progress Tracker -->
                                    <div class="progress-tracker">
                                        <div class="step <?php echo $step1; ?>">
                                            <div class="step-circle"><i class="fas fa-paper-plane"></i></div>
                                            <div class="step-label">Requested</div>
                                        </div>
                                        <div class="step <?php echo $step2; ?> <?php if($curStatus==='rejected') echo 'text-danger'; ?>">
                                            <div class="step-circle">
                                                <i class="fas <?php echo $curStatus === 'approved' ? 'fa-check' : ($curStatus === 'rejected' ? 'fa-times' : 'fa-hourglass-half'); ?>"></i>
                                            </div>
                                            <div class="step-label"><?php echo $curStatus === 'rejected' ? 'Rejected' : 'Approved'; ?></div>
                                        </div>
                                        <div class="step <?php echo $step3; ?>">
                                            <div class="step-circle"><i class="fas fa-file-invoice-dollar"></i></div>
                                            <div class="step-label">Paid</div>
                                        </div>
                                        <div class="step <?php echo $step4; ?>">
                                            <div class="step-circle"><i class="fas fa-key"></i></div>
                                            <div class="step-label">Rented</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-auto text-md-end border-start ps-md-4">
                                    <div class="d-flex flex-column gap-2">
                                        <a href="rental_chat.php?request_id=<?php echo $req['id']; ?>" class="btn btn-primary-green rounded-pill px-4 position-relative">
                                            <i class="fas fa-comments me-2"></i> Message
                                            <?php if ($req['unread_messages'] > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.7rem;">
                                                    <?php echo $req['unread_messages']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                        <?php if ($req['status'] === 'approved'): ?>
                                            <a href="payment.php?request_id=<?php echo $req['id']; ?>" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                                                <i class="fas fa-qrcode me-2"></i> <?php echo $proofStatus ? 'Update Proof' : 'Make Payment'; ?>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php include '../includes/footer.php'; ?>
