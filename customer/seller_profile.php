<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$seller_id = intval($_GET['id'] ?? 0);

// Fetch seller info
$stmt = $pdo->prepare("SELECT username, full_name, email, phone, created_at FROM users WHERE id = ?");
$stmt->execute([$seller_id]);
$seller = $stmt->fetch();

if (!$seller) {
    redirectWithMessage('exchange_material.php', 'danger', 'User not found.');
}

// Fetch items posted by this seller
$stmt = $pdo->prepare("SELECT * FROM exchange_materials WHERE user_id = ? AND status = 'available' ORDER BY created_at DESC");
$stmt->execute([$seller_id]);
$items = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4">
        <!-- Seller Profile Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="p-5 text-center bg-primary-green text-white">
                    <div class="avatar-wrapper mb-3 position-relative d-inline-block">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($seller['full_name'] ?: $seller['username']); ?>&background=fff&color=1B5E20&size=128"
                            class="rounded-circle shadow-lg border border-4 border-white" width="128" height="128">
                    </div>
                    <h3 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($seller['full_name']); ?>
                    </h3>
                    <p class="opacity-75 mb-0">@
                        <?php echo htmlspecialchars($seller['username']); ?>
                    </p>
                </div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-calendar-alt text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Member Since</small>
                            <strong>
                                <?php echo date('F Y', strtotime($seller['created_at'])); ?>
                            </strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <i class="fas fa-phone-alt text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Contact Phone</small>
                            <strong>
                                <?php echo htmlspecialchars($seller['phone'] ?: 'No phone provided'); ?>
                            </strong>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-0">
                        <i class="fas fa-envelope text-muted me-3 fs-5"></i>
                        <div>
                            <small class="text-muted d-block">Email Address</small>
                            <strong>
                                <?php echo htmlspecialchars($seller['email']); ?>
                            </strong>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-light p-4 border-0">
                    <div class="d-grid">
                        <a href="mailto:<?php echo $seller['email']; ?>"
                            class="btn btn-primary-green rounded-pill fw-bold">Send Message</a>
                    </div>
                </div>
            </div>

            <div class="alert alert-info rounded-4 mt-4 border-0 shadow-sm">
                <i class="fas fa-info-circle me-2"></i> Only contact sellers through the provided info. Be safe!
            </div>
        </div>

        <!-- Seller's Listings -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Listings by
                    <?php echo htmlspecialchars($seller['full_name']); ?>
                </h4>
                <span class="badge bg-light text-dark border rounded-pill px-3 py-2">
                    <?php echo count($items); ?> Items
                </span>
            </div>

            <?php if (empty($items)): ?>
                <div class="card border-0 shadow-sm rounded-4 p-5 text-center">
                    <i class="fas fa-store-slash text-muted mb-3 fs-1"></i>
                    <h5 class="text-muted">No active listings found for this user.</h5>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($items as $item): ?>
                        <div class="col-md-6">
                            <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden hover-lift">
                                <div class="material-img-wrapper" style="height: 180px;">
                                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                                        class="w-100 h-100 object-fit-cover" alt="Item image">
                                </div>
                                <div class="card-body p-4">
                                    <h6 class="text-primary-green fw-bold small text-uppercase mb-1">
                                        <?php echo htmlspecialchars($item['category']); ?>
                                    </h6>
                                    <h5 class="card-title fw-bold mb-3 text-truncate">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </h5>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="h5 fw-bold text-primary mb-0">
                                            <?php echo number_format($item['price'], 1); ?> ETB
                                        </span>
                                        <a href="view_material.php?id=<?php echo $item['id']; ?>"
                                            class="btn btn-outline-primary btn-sm rounded-pill px-3">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .object-fit-cover {
        object-fit: cover;
    }

    .hover-lift {
        transition: transform 0.2s;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
    }
</style>

<?php include '../includes/footer.php'; ?>