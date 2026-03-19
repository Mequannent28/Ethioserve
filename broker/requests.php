<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in as broker or owner
requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Handle Status Update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlashMessage('Invalid security token.', 'error');
        header('Location: requests.php');
        exit();
    }
    $request_id = (int)$_POST['request_id'];
    $new_status = sanitize($_POST['status']);
    
    if (!in_array($new_status, ['pending', 'approved', 'rejected'])) {
        setFlashMessage('Invalid status.', 'error');
        header('Location: requests.php');
        exit();
    }

    // Verify ownership of the listing associated with the request and get listing_id
    $stmt = $pdo->prepare("
        SELECT rr.id, rr.listing_id 
        FROM rental_requests rr 
        JOIN listings l ON rr.listing_id = l.id 
        WHERE rr.id = ? AND l.user_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);
    $req_data = $stmt->fetch();
    
    if ($req_data) {
        $stmt = $pdo->prepare("UPDATE rental_requests SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $request_id]);

        // If approved, mark listing as 'on_process'
        if ($new_status === 'approved') {
            $stmt_list = $pdo->prepare("UPDATE listings SET status = 'on_process' WHERE id = ?");
            $stmt_list->execute([$req_data['listing_id']]);
        } 
        // If rejected, check if we should reset to 'available'
        elseif ($new_status === 'rejected') {
            // Only reset if it was currently on_process and NO OTHER requests are approved for this listing
            $stmt_check = $pdo->prepare("SELECT COUNT(*) FROM rental_requests WHERE listing_id = ? AND status = 'approved' AND id != ?");
            $stmt_check->execute([$req_data['listing_id'], $request_id]);
            if ($stmt_check->fetchColumn() == 0) {
                $stmt_reset = $pdo->prepare("UPDATE listings SET status = 'available' WHERE id = ? AND status = 'on_process'");
                $stmt_reset->execute([$req_data['listing_id']]);
            }
        }
        
        setFlashMessage('Request status updated to ' . ucfirst($new_status) . '.', 'success');
    } else {
        setFlashMessage('Unauthorized or request not found.', 'error');
    }
    header("Location: requests.php");
    exit();
}

// Fetch all requests for this owner's listings
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.type as listing_type, l.image_url as listing_img, 
           u.full_name as customer_name_db, u.email as customer_email_db,
           b.referral_code as broker_code,
           (SELECT COUNT(*) FROM rental_payment_proofs WHERE request_id = rr.id) as proof_count,
           (SELECT COUNT(*) FROM rental_chat_messages WHERE request_id = rr.id AND receiver_id = ? AND is_read = 0) as unread_messages
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users u ON rr.customer_id = u.id
    LEFT JOIN brokers b ON rr.broker_id = b.id
    WHERE l.user_id = ?
    ORDER BY rr.created_at DESC
");
$stmt->execute([$user_id, $user_id]);
$requests = $stmt->fetchAll();

// Count by status
$count_all = count($requests);
$count_pending = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$count_approved = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$count_rejected = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inquiries & Requests - Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { overflow-x: hidden; background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { padding: 30px 32px; margin-left: 260px; background: #f0f2f5; min-height: 100vh; }
        .request-card { background: #fff; border: none; border-radius: 16px; margin-bottom: 18px; transition: 0.3s; box-shadow: 0 2px 12px rgba(0,0,0,0.05); }
        .request-card:hover { box-shadow: 0 10px 28px rgba(0,0,0,0.09); transform: translateY(-2px); }
        .listing-thumb { width: 80px; height: 80px; object-fit: cover; border-radius: 12px; }
        .btn-status-pending  { background: #FFF3E0; color: #E65100; border: none; }
        .btn-status-approved { background: #E8F5E9; color: #1B5E20; border: none; }
        .btn-status-rejected { background: #FFEBEE; color: #C62828; border: none; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="main-content">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 class="fw-bold mb-0">Customer Inquiries</h2>
                    <p class="text-muted mb-0">Review and respond to customers interested in your listings.</p>
                </div>
            </div>

            <!-- Status Summary Cards -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100">
                        <h3 class="fw-bold text-dark mb-0"><?php echo $count_all; ?></h3>
                        <small class="text-muted">Total</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100" style="border-left: 4px solid #ffc107 !important;">
                        <h3 class="fw-bold text-warning mb-0"><?php echo $count_pending; ?></h3>
                        <small class="text-muted">Pending</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100" style="border-left: 4px solid #198754 !important;">
                        <h3 class="fw-bold text-success mb-0"><?php echo $count_approved; ?></h3>
                        <small class="text-muted">Approved</small>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card border-0 shadow-sm p-3 text-center h-100" style="border-left: 4px solid #dc3545 !important;">
                        <h3 class="fw-bold text-danger mb-0"><?php echo $count_rejected; ?></h3>
                        <small class="text-muted">Rejected</small>
                    </div>
                </div>
            </div>

            <!-- Filter Tabs -->
            <div class="d-flex gap-2 flex-wrap mb-4">
                <button class="btn btn-success rounded-pill px-4 filter-tab active" data-filter="all">All <span class="badge bg-white text-success ms-1"><?php echo $count_all; ?></span></button>
                <button class="btn btn-outline-warning rounded-pill px-4 filter-tab" data-filter="pending">Pending <span class="badge bg-warning ms-1"><?php echo $count_pending; ?></span></button>
                <button class="btn btn-outline-success rounded-pill px-4 filter-tab" data-filter="approved">Approved <span class="badge bg-success ms-1"><?php echo $count_approved; ?></span></button>
                <button class="btn btn-outline-danger rounded-pill px-4 filter-tab" data-filter="rejected">Rejected <span class="badge bg-danger ms-1"><?php echo $count_rejected; ?></span></button>
            </div>

            <?php if (empty($requests)): ?>
                <div class="card border-0 shadow-sm rounded-4 py-5 text-center">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                        <i class="fas fa-envelope-open text-muted fs-1"></i>
                    </div>
                    <h4 class="text-muted">No rental requests or inquiries found.</h4>
                    <p class="text-muted">Customer messages will appear here when they contact you about your items.</p>
                </div>
            <?php else: ?>
                <div class="row" id="requestsGrid">
                    <?php foreach ($requests as $req): ?>
                        <div class="col-12 req-item" data-status="<?php echo htmlspecialchars($req['status']); ?>">
                            <div class="card request-card shadow-sm">
                                <div class="card-body p-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                        <div class="d-flex gap-3">
                                            <img src="<?php echo $req['listing_img'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200'; ?>" class="listing-thumb shadow-sm" alt="Listing">
                                            <div>
                                                <h6 class="text-success fw-bold mb-1 text-uppercase small"><?php echo str_replace('_', ' ', $req['listing_type']); ?></h6>
                                                <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($req['listing_title']); ?></h5>
                                                <div class="d-flex flex-wrap gap-2 text-muted small mt-1 align-items-center">
                                                    <span><i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($req['customer_name'] ?: $req['customer_name_db']); ?></span>
                                                    <span><i class="fas fa-phone me-1"></i> <?php echo htmlspecialchars($req['customer_phone']); ?></span>
                                                    <span class="badge bg-light text-dark fw-normal border"><i class="fas fa-calendar-alt me-1 text-primary"></i> <?php echo $req['duration_months']; ?> Months</span>
                                                    <?php if ($req['referral_code_used']): ?>
                                                        <span class="text-primary-green fw-bold bg-success-subtle px-2 rounded">
                                                            <i class="fas fa-ticket-alt me-1"></i> Via Ref: <?php echo htmlspecialchars($req['referral_code_used']); ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-md-end">
                                            <div class="mb-2">
                                                <?php 
                                                    $statusClass = 'btn-status-pending';
                                                    if($req['status'] == 'approved') $statusClass = 'btn-status-approved';
                                                    if($req['status'] == 'rejected') $statusClass = 'btn-status-rejected';
                                                ?>
                                                <span class="badge <?php echo $statusClass; ?> rounded-pill px-3 py-2">
                                                    <?php echo ucfirst($req['status']); ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-0"><i class="fas fa-clock me-1"></i> <?php echo date('M d, Y h:i A', strtotime($req['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <hr class="my-4 opacity-10">
                                    <div class="bg-light p-3 rounded-3 mb-4">
                                        <p class="mb-0 small fw-bold text-muted mb-2 text-uppercase">Customer Message:</p>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($req['message'])); ?></p>
                                    </div>
                                    <div class="d-flex justify-content-end gap-2 flex-wrap">
                                        <a href="tel:<?php echo $req['customer_phone']; ?>" class="btn btn-primary-green btn-sm rounded-pill px-4">
                                            <i class="fas fa-phone-alt me-2"></i> Call Customer
                                        </a>
                                        <?php if (!empty($req['customer_email'])): ?>
                                        <a href="mailto:<?php echo htmlspecialchars($req['customer_email'] ?? $req['customer_email_db'] ?? ''); ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
                                            <i class="fas fa-envelope me-1"></i> Email
                                        </a>
                                        <?php endif; ?>
                                        <a href="chat.php?request_id=<?php echo $req['id']; ?>" class="btn btn-outline-primary-green btn-sm rounded-pill px-4 position-relative">
                                            <i class="fas fa-comments me-2"></i> Chat
                                            <?php if ($req['unread_messages'] > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                                                    <?php echo $req['unread_messages']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </a>
                                        <?php if ($req['proof_count'] > 0): ?>
                                            <a href="verify_payment.php?request_id=<?php echo $req['id']; ?>" class="btn btn-warning btn-sm rounded-pill px-4 fw-bold">
                                                <i class="fas fa-file-invoice-dollar me-1"></i> Verify Payment (<?php echo $req['proof_count']; ?>)
                                            </a>
                                        <?php endif; ?>
                                        <?php if ($req['status'] !== 'approved'): ?>
                                        <form action="requests.php" method="POST" class="d-inline">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="status" value="approved">
                                            <button type="submit" name="update_status" class="btn btn-success btn-sm rounded-pill px-3">
                                                <i class="fas fa-check me-1"></i> Approve
                                            </button>
                                        </form>
                                        <?php endif; ?>
                                        <?php if ($req['status'] !== 'rejected'): ?>
                                        <form action="requests.php" method="POST" class="d-inline">
                                            <?php echo csrfField(); ?>
                                            <input type="hidden" name="request_id" value="<?php echo $req['id']; ?>">
                                            <input type="hidden" name="status" value="rejected">
                                            <button type="submit" name="update_status" class="btn btn-danger btn-sm rounded-pill px-3">
                                                <i class="fas fa-times me-1"></i> Reject
                                            </button>
                                        </form>
                                        <?php endif; ?>
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
    <script>
        // Filter tabs
        document.querySelectorAll('.filter-tab').forEach(tab => {
            tab.addEventListener('click', function() {
                // Update active tab styles
                document.querySelectorAll('.filter-tab').forEach(t => {
                    t.classList.remove('active', 'btn-success', 'btn-warning', 'btn-danger');
                    if (t.getAttribute('data-filter') === 'pending') t.classList.add('btn-outline-warning');
                    else if (t.getAttribute('data-filter') === 'approved') t.classList.add('btn-outline-success');
                    else if (t.getAttribute('data-filter') === 'rejected') t.classList.add('btn-outline-danger');
                    else t.classList.add('btn-outline-secondary');
                });

                const filter = this.getAttribute('data-filter');
                this.classList.add('active');
                this.classList.remove('btn-outline-warning', 'btn-outline-success', 'btn-outline-danger', 'btn-outline-secondary');
                if (filter === 'pending') this.classList.add('btn-warning');
                else if (filter === 'approved') this.classList.add('btn-success');
                else if (filter === 'rejected') this.classList.add('btn-danger');
                else this.classList.add('btn-dark');

                // Filter items
                document.querySelectorAll('.req-item').forEach(item => {
                    if (filter === 'all' || item.getAttribute('data-status') === filter) {
                        item.style.display = 'block';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        });
    </script>
</body>

</html>
