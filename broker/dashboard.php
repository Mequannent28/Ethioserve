<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$user_name = getCurrentUserName();

// Get broker record (create if missing)
$stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
$stmt->execute([$user_id]);
$broker = $stmt->fetch();

if (!$broker) {
    $ref_code = 'REF' . strtoupper(substr(uniqid(), -6));
    $stmt = $pdo->prepare("INSERT INTO brokers (user_id, referral_code) VALUES (?, ?)");
    $stmt->execute([$user_id, $ref_code]);
    $stmt = $pdo->prepare("SELECT * FROM brokers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $broker = $stmt->fetch();
}

// Stats: Listings
$stmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ?");
$stmt->execute([$user_id]);
$listings_count = (int)$stmt->fetchColumn();

// Stats: Active (available) listings
$stmt = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE user_id = ? AND status = 'available'");
$stmt->execute([$user_id]);
$active_listings = (int)$stmt->fetchColumn();

// Stats: Total requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_requests rr JOIN listings l ON rr.listing_id = l.id WHERE l.user_id = ?");
$stmt->execute([$user_id]);
$requests_count = (int)$stmt->fetchColumn();

// Stats: Pending requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_requests rr JOIN listings l ON rr.listing_id = l.id WHERE l.user_id = ? AND rr.status = 'pending'");
$stmt->execute([$user_id]);
$pending_requests = (int)$stmt->fetchColumn();

// Stats: Pending commissions from referrals
$pending_commissions = 0;
if ($broker) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM referrals WHERE broker_id = ? AND status = 'pending'");
    $stmt->execute([$broker['id']]);
    $pending_commissions = (float)$stmt->fetchColumn();
}

// Stats: Total earned commissions
$total_earned = 0;
if ($broker) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(commission_amount), 0) FROM referrals WHERE broker_id = ? AND status = 'paid'");
    $stmt->execute([$broker['id']]);
    $total_earned = (float)$stmt->fetchColumn();
}

// Stats: Total payments needing verification
$stmt = $pdo->prepare("
    SELECT COUNT(*) 
    FROM rental_payment_proofs p
    JOIN rental_requests rr ON p.request_id = rr.id
    JOIN listings l ON rr.listing_id = l.id
    WHERE l.user_id = ? AND p.status = 'pending'
");
$stmt->execute([$user_id]);
$pending_payments = (int)$stmt->fetchColumn();

// Recent requests (last 8)
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.type as listing_type, l.image_url as listing_img
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    WHERE l.user_id = ?
    ORDER BY rr.created_at DESC
    LIMIT 8
");
$stmt->execute([$user_id]);
$recent_requests = $stmt->fetchAll();

// Recent Listings (last 5)
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$user_id]);
$recent_listings = $stmt->fetchAll();

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        * {
            font-family: 'Poppins', sans-serif;
        }

        body {
            overflow-x: hidden;
            background-color: #f0f2f5;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
        }

        .main-content {
            padding: 30px 32px;
            background-color: #f0f2f5;
            min-height: 100vh;
            flex: 1;
        }

        /* ---- Stat Cards ---- */
        .stat-card {
            border-radius: 18px;
            border: none;
            padding: 24px 22px;
            color: #fff;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 40px rgba(0, 0, 0, 0.13);
        }

        .stat-card::after {
            content: '';
            position: absolute;
            right: -20px;
            top: -20px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.08);
        }

        .stat-card .label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            opacity: 0.8;
            margin-bottom: 8px;
        }

        .stat-card .value {
            font-size: 2.2rem;
            font-weight: 800;
            line-height: 1;
            margin-bottom: 8px;
        }

        .stat-card .sub {
            font-size: 0.78rem;
            opacity: 0.7;
        }

        .stat-card .icon-bg {
            position: absolute;
            right: 22px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 2.4rem;
            opacity: 0.2;
        }

        .bg-green-grad { background: linear-gradient(135deg, #1B5E20, #43A047); }
        .bg-blue-grad  { background: linear-gradient(135deg, #1565C0, #42A5F5); }
        .bg-amber-grad { background: linear-gradient(135deg, #E65100, #FFA726); }
        .bg-teal-grad  { background: linear-gradient(135deg, #00695C, #26A69A); }

        /* ---- Cards ---- */
        .content-card {
            background: #fff;
            border-radius: 18px;
            border: none;
            box-shadow: 0 2px 14px rgba(0, 0, 0, 0.05);
        }

        .content-card .card-header-custom {
            padding: 20px 22px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #f0f0f0;
        }

        .content-card .card-header-custom h5 {
            font-weight: 700;
            margin: 0;
            font-size: 1rem;
        }

        /* ---- Status badges ---- */
        .badge-pending  { background: #FFF3E0; color: #E65100; }
        .badge-approved { background: #E8F5E9; color: #1B5E20; }
        .badge-rejected { background: #FFEBEE; color: #C62828; }

        /* ---- Listing type pill ---- */
        .type-pill {
            font-size: 0.65rem;
            font-weight: 700;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            background: #E8F5E9;
            color: #1B5E20;
        }

        /* ---- Quick action ---- */
        .quick-action {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 14px 16px;
            border-radius: 14px;
            background: #f8f9fa;
            border: 1.5px solid #eee;
            cursor: pointer;
            text-decoration: none;
            color: #333;
            transition: all 0.25s;
        }

        .quick-action:hover {
            border-color: #1B5E20;
            background: #E8F5E9;
            transform: translateY(-2px);
        }

        .quick-action .qa-icon {
            width: 46px;
            height: 46px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            flex-shrink: 0;
        }

        /* ---- Page header ---- */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 28px;
            flex-wrap: wrap;
            gap: 12px;
        }

        .page-header h2 {
            font-weight: 800;
            margin: 0;
            font-size: 1.65rem;
            color: #1a1a1a;
        }

        .referral-code-box {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            border-radius: 16px;
            padding: 20px;
            color: white;
        }

        .referral-code-box .code {
            font-size: 1.5rem;
            font-weight: 800;
            letter-spacing: 3px;
            font-family: monospace;
        }

        @media (max-width: 991px) {
            .main-content { padding: 20px 16px; }
            .stat-card .value { font-size: 1.75rem; }
        }
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

            <!-- Page Header -->
            <div class="page-header">
                <div>
                    <h2><i class="fas fa-th-large text-primary-green me-2 opacity-75"></i>Property Owner Dashboard</h2>
                    <p class="text-muted mb-0">Welcome back, <strong><?php echo htmlspecialchars($user_name); ?></strong>! Here's what's happening.</p>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($pending_requests > 0): ?>
                        <a href="requests.php" class="btn btn-warning rounded-pill px-4 fw-bold shadow-sm">
                            <i class="fas fa-bell me-2"></i>
                            <?php echo $pending_requests; ?> Pending Request<?php echo $pending_requests > 1 ? 's' : ''; ?>
                        </a>
                    <?php endif; ?>
                    <a href="post_listing.php" class="btn btn-primary-green rounded-pill px-4 fw-bold shadow-sm">
                        <i class="fas fa-plus me-2"></i> Add Listing
                    </a>
                </div>
            </div>

            <!-- ===== STAT CARDS ===== -->
            <div class="row g-4 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-green-grad shadow-sm">
                        <p class="label">Total Listings</p>
                        <div class="value"><?php echo $listings_count; ?></div>
                        <p class="sub"><i class="fas fa-home me-1"></i> <?php echo $active_listings; ?> active</p>
                        <i class="fas fa-home icon-bg"></i>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-amber-grad shadow-sm">
                        <p class="label">Pending Requests</p>
                        <div class="value"><?php echo $pending_requests; ?></div>
                        <p class="sub"><i class="fas fa-envelope-open me-1"></i> <?php echo $requests_count; ?> total</p>
                        <i class="fas fa-envelope-open-text icon-bg"></i>
                    </div>
                </div>
                
                <?php if ($broker && $_SESSION['role'] === 'broker'): ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-blue-grad shadow-sm">
                        <p class="label">Commissions</p>
                        <div class="value"><?php echo number_format($pending_commissions / 1000, 1); ?>k</div>
                        <p class="sub"><i class="fas fa-coins me-1"></i> ETB pending</p>
                        <i class="fas fa-coins icon-bg"></i>
                    </div>
                </div>
                <?php endif; ?>

                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-teal-grad shadow-sm">
                        <p class="label">Verify Payments</p>
                        <div class="value"><?php echo $pending_payments; ?></div>
                        <p class="sub"><i class="fas fa-file-invoice-dollar me-1"></i> Awaiting review</p>
                        <i class="fas fa-money-bill-wave icon-bg"></i>
                    </div>
                </div>

                <?php if ($_SESSION['role'] === 'property_owner'): ?>
                <div class="col-6 col-xl-3">
                    <div class="stat-card bg-blue-grad shadow-sm">
                        <p class="label">Total Earned</p>
                        <div class="value"><?php echo number_format($total_earned / 1000, 1); ?>k</div>
                        <p class="sub"><i class="fas fa-check-circle me-1"></i> ETB confirmed</p>
                        <i class="fas fa-check-double icon-bg"></i>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- ===== MAIN BODY ===== -->
            <div class="row g-4">

                <!-- Recent Requests -->
                <div class="col-lg-8">
                    <div class="content-card h-100">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-envelope-open-text text-primary-green me-2"></i>Recent Inquiries</h5>
                            <a href="requests.php" class="btn btn-sm btn-light rounded-pill px-3">View All</a>
                        </div>

                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fs-1 opacity-25 mb-3 d-block"></i>
                                <p class="fw-bold mb-1">No requests yet</p>
                                <small>When customers contact you, they'll appear here.</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" style="font-size: 0.875rem;">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="px-4 py-3">Listing</th>
                                            <th>Customer</th>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th class="text-end px-4">Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requests as $req): ?>
                                            <tr>
                                                <td class="px-4">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <img src="<?php echo $req['listing_img'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=60'; ?>"
                                                             width="42" height="42"
                                                             style="border-radius:10px;object-fit:cover;" alt="">
                                                        <div>
                                                            <div class="fw-bold text-truncate" style="max-width:160px;">
                                                                <?php echo htmlspecialchars($req['listing_title']); ?>
                                                            </div>
                                                            <span class="type-pill"><?php echo str_replace('_', ' ', $req['listing_type']); ?></span>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="fw-bold"><?php echo htmlspecialchars($req['customer_name'] ?? 'N/A'); ?></div>
                                                    <small class="text-muted"><?php echo htmlspecialchars($req['customer_phone'] ?? ''); ?></small>
                                                </td>
                                                <td class="text-muted small"><?php echo date('M d, Y', strtotime($req['created_at'])); ?></td>
                                                <td>
                                                    <?php
                                                    $s = $req['status'];
                                                    $cls = $s === 'approved' ? 'badge-approved' : ($s === 'rejected' ? 'badge-rejected' : 'badge-pending');
                                                    ?>
                                                    <span class="badge rounded-pill px-3 py-2 fw-bold <?php echo $cls; ?>">
                                                        <?php echo ucfirst($s); ?>
                                                    </span>
                                                </td>
                                                <td class="text-end px-4">
                                                    <a href="requests.php" class="btn btn-sm btn-outline-secondary rounded-pill">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right Column -->
                <div class="col-lg-4 d-flex flex-column gap-4">

                    <!-- Referral Code Card -->
                    <?php if ($broker): ?>
                    <div class="referral-code-box shadow-sm">
                        <p class="small fw-bold opacity-75 text-uppercase mb-2"><i class="fas fa-share-alt me-2"></i>Your Referral Code</p>
                        <div class="code mb-2"><?php echo htmlspecialchars($broker['referral_code'] ?? 'N/A'); ?></div>
                        <p class="small opacity-70 mb-3">Share this code so customers can use it at checkout and you earn commissions.</p>
                        <button class="btn btn-warning btn-sm rounded-pill fw-bold px-4"
                            onclick="navigator.clipboard.writeText('<?php echo htmlspecialchars($broker['referral_code']); ?>').then(()=>this.innerHTML='<i class=\'fas fa-check me-1\'></i>Copied!')">
                            <i class="fas fa-copy me-2"></i>Copy Code
                        </button>
                    </div>
                    <?php endif; ?>

                    <!-- Quick Actions -->
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-bolt text-warning me-2"></i>Quick Actions</h5>
                        </div>
                        <div class="card-body p-3 d-flex flex-column gap-3">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="post_listing.php" class="quick-action h-100 flex-column text-center p-3">
                                        <div class="qa-icon bg-success-subtle text-success mb-2 mx-auto"><i class="fas fa-plus"></i></div>
                                        <span class="small fw-bold">Post New</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="my_listings.php" class="quick-action h-100 flex-column text-center p-3">
                                        <div class="qa-icon bg-primary-subtle text-primary mb-2 mx-auto"><i class="fas fa-list"></i></div>
                                        <span class="small fw-bold">Listings</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="requests.php" class="quick-action h-100 flex-column text-center p-3">
                                        <div class="qa-icon bg-warning-subtle text-warning mb-2 mx-auto">
                                            <i class="fas fa-envelope-open"></i>
                                            <?php if ($pending_requests > 0): ?>
                                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                                                    <?php echo $pending_requests; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <span class="small fw-bold">Requests</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="payment_settings.php" class="quick-action h-100 flex-column text-center p-3">
                                        <div class="qa-icon bg-info-subtle text-info mb-2 mx-auto"><i class="fas fa-qrcode"></i></div>
                                        <span class="small fw-bold">Payment</span>
                                    </a>
                                </div>
                            </div>

                            <a href="referrals.php" class="quick-action">
                                <div class="qa-icon bg-success-subtle text-success"><i class="fas fa-hand-holding-usd"></i></div>
                                <div>
                                    <div class="fw-bold small">Earnings & Referrals</div>
                                    <div class="text-muted" style="font-size:0.7rem;">Track your commissions</div>
                                </div>
                            </a>

                            <?php if ($pending_payments > 0): ?>
                            <a href="requests.php" class="quick-action border-warning bg-warning-subtle">
                                <div class="qa-icon bg-warning text-dark"><i class="fas fa-file-invoice-dollar"></i></div>
                                <div>
                                    <div class="fw-bold small">Verify <?php echo $pending_payments; ?> Payments</div>
                                    <div class="text-muted" style="font-size:0.7rem;">New proofs submitted</div>
                                </div>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Recent Listings snapshot -->
                    <?php if (!empty($recent_listings)): ?>
                    <div class="content-card">
                        <div class="card-header-custom">
                            <h5><i class="fas fa-home text-primary-green me-2"></i>My Listings</h5>
                            <a href="my_listings.php" class="btn btn-sm btn-light rounded-pill px-3">See All</a>
                        </div>
                        <div class="p-3 d-flex flex-column gap-2">
                            <?php foreach ($recent_listings as $listing): ?>
                                <div class="d-flex align-items-center gap-3 p-2 rounded-3 hover-bg"
                                     style="transition:0.2s;" onmouseover="this.style.background='#f8f9fa'" onmouseout="this.style.background='transparent'">
                                    <img src="<?php echo htmlspecialchars($listing['image_url'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=60'); ?>"
                                         width="46" height="46" style="border-radius:10px;object-fit:cover;" alt="">
                                    <div style="flex:1;min-width:0;">
                                        <div class="fw-bold text-truncate" style="font-size:0.83rem;max-width:160px;">
                                            <?php echo htmlspecialchars($listing['title']); ?>
                                        </div>
                                        <div class="text-muted" style="font-size:0.73rem;"><?php echo number_format($listing['price']); ?> ETB</div>
                                    </div>
                                    <span class="badge rounded-pill px-2 py-1 <?php echo $listing['status'] === 'available' ? 'badge-approved' : 'badge-pending'; ?>"
                                          style="font-size:0.64rem;">
                                        <?php echo ucfirst($listing['status']); ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div><!-- /main-content -->
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>