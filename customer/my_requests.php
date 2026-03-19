<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Get all rental requests sent by this customer
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.type as listing_type, l.image_url as listing_img, l.price as listing_price,
           u.full_name as owner_name, u.phone as owner_phone
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    JOIN users u ON l.user_id = u.id
    WHERE rr.customer_id = ?
    ORDER BY rr.created_at DESC
");
$stmt->execute([$user_id]);
$requests = $stmt->fetchAll();

include('../includes/header.php');
?>

<div class="container py-5">
    <div class="mb-5">
        <h2 class="fw-bold mb-2">My Rental Inquiries</h2>
        <p class="text-muted">Track the status of your house and car rental requests.</p>
    </div>

    <?php if ($flash): ?>
        <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
            <?php echo htmlspecialchars($flash['message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($requests)): ?>
        <div class="card border-0 shadow-sm rounded-4 py-5 text-center">
            <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                <i class="fas fa-home text-muted fs-1"></i>
            </div>
            <h4 class="text-muted">You haven't sent any requests yet.</h4>
            <p class="text-muted">Browse our listings and contact owners for houses or cars.</p>
            <a href="rent.php" class="btn btn-primary-green rounded-pill px-4 mt-3">Browse Rentals</a>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($requests as $req): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-md-3">
                                <img src="<?php echo $req['listing_img'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400'; ?>" class="img-fluid h-100 w-100" style="object-fit: cover; min-height: 150px;" alt="Listing">
                            </div>
                            <div class="col-md-9">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <span class="badge bg-success bg-opacity-10 text-success rounded-pill px-3 py-1 mb-2 small text-uppercase fw-bold">
                                                <?php echo str_replace('_', ' ', $req['listing_type']); ?>
                                            </span>
                                            <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($req['listing_title']); ?></h5>
                                            <h6 class="text-primary-green fw-bold"><?php echo number_format($req['listing_price']); ?> ETB</h6>
                                        </div>
                                        <div class="text-end">
                                            <?php 
                                                $statusClass = 'bg-warning';
                                                if($req['status'] == 'approved') $statusClass = 'bg-success';
                                                if($req['status'] == 'rejected') $statusClass = 'bg-danger';
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> rounded-pill px-4 py-2 text-white">
                                                <?php echo ucfirst($req['status']); ?>
                                            </span>
                                            <p class="text-muted small mt-2 mb-0"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-light p-3 rounded-3 mb-3">
                                        <p class="mb-0 small text-muted"><strong>Your Message:</strong> <?php echo nl2br(htmlspecialchars($req['message'])); ?></p>
                                    </div>

                                    <?php if ($req['status'] === 'approved'): ?>
                                        <div class="alert alert-success border-0 rounded-3 mb-0">
                                            <div class="d-flex align-items-center gap-3">
                                                <i class="fas fa-check-circle fs-4"></i>
                                                <div>
                                                    <strong>The owner approved your inquiry!</strong>
                                                    <p class="mb-0 small">You can now contact <strong><?php echo htmlspecialchars($req['owner_name']); ?></strong> at <strong><?php echo htmlspecialchars($req['owner_phone']); ?></strong> to proceed.</p>
                                                </div>
                                                <a href="tel:<?php echo $req['owner_phone']; ?>" class="btn btn-success rounded-pill px-4 ms-auto">Call Now</a>
                                            </div>
                                        </div>
                                    <?php elseif ($req['status'] === 'pending'): ?>
                                        <p class="text-muted small mb-0"><i class="fas fa-info-circle me-1"></i> Waiting for owner's response. We'll notify you here.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include('../includes/footer.php'); ?>
