<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/recycle_bin_helper.php';

// Check if user is logged in and is a broker or owner
requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Handle Soft Delete (Move to Recycle Bin)
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    // Verify ownership
    $stmt = $pdo->prepare("SELECT id FROM listings WHERE id = ? AND user_id = ?");
    $stmt->execute([$delete_id, $user_id]);
    if ($stmt->fetch()) {
        if (moveToRecycleBin($pdo, 'listings', $delete_id, 'broker', $user_id, 'Manual delete by owner')) {
            $stmt = $pdo->prepare("DELETE FROM listings WHERE id = ?");
            $stmt->execute([$delete_id]);
            setFlashMessage('Listing moved to recycle bin.', 'success');
        } else {
            setFlashMessage('Failed to move listing to recycle bin.', 'error');
        }
    } else {
        setFlashMessage('Unauthorized or listing not found.', 'error');
    }
    header("Location: my_listings.php");
    exit();
}

// Handle Status Changes
if (isset($_GET['set_status']) && isset($_GET['id'])) {
    $item_id = (int)$_GET['id'];
    $new_status = $_GET['set_status'];
    
    // Validate status
    $allowed_statuses = ['available', 'rented', 'not_available'];
    if (in_array($new_status, $allowed_statuses)) {
        // Verify ownership
        $stmt = $pdo->prepare("SELECT status FROM listings WHERE id = ? AND user_id = ?");
        $stmt->execute([$item_id, $user_id]);
        if ($stmt->fetch()) {
            if ($new_status === 'rented') {
                $duration = (int)($_GET['duration'] ?? 1);
                $stmt = $pdo->prepare("UPDATE listings SET status = ?, rented_duration = ?, rented_at = CURRENT_TIMESTAMP WHERE id = ?");
                $stmt->execute([$new_status, $duration, $item_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE listings SET status = ?, rented_duration = NULL, rented_at = NULL WHERE id = ?");
                $stmt->execute([$new_status, $item_id]);
            }
            setFlashMessage('Listing status updated to ' . str_replace('_', ' ', $new_status), 'success');
        }
    }
    header("Location: my_listings.php");
    exit();
}

// Get all listings for this user
$stmt = $pdo->prepare("SELECT * FROM listings WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$listings = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings - Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { overflow-x: hidden; background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { padding: 30px 32px; flex: 1; width: 100%; margin-left: 260px; background: #f0f2f5; min-height: 100vh; transition: all 0.3s ease; }
        .mobile-header { display: none; background: #fff; padding: 15px 20px; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 90; }
        
        .listing-card { border: none; border-radius: 15px; overflow: hidden; transition: 0.3s; }
        .listing-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
        .listing-img { height: 180px; object-fit: cover; }
        .badge-status { position: absolute; top: 15px; left: 15px; }

        @media (max-width: 991px) { 
            .main-content { margin-left: 0; padding: 20px !important; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; width: 100%; }
        }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 95; }
        .sidebar-overlay.show { display: block; }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <div class="main-content p-0">
            <!-- Mobile Header -->
            <div class="mobile-header d-lg-none">
                <button class="btn btn-light border shadow-sm rounded-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0 fw-bold text-success">EthioServe</h5>
                <div style="width: 40px;"></div>
            </div>

            <div class="p-4 p-md-5">
            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                    <?php echo htmlspecialchars($flash['message']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">My Listings</h2>
                    <p class="text-muted">Manage your posted houses, cars, and services.</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="recycle_bin.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-trash-alt me-2"></i> Recycle Bin
                    </a>
                    <a href="post_listing.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i> Add Listing
                    </a>
                </div>
            </div>

            <!-- Search & Filters -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-3">
                    <form class="row g-2">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text bg-transparent border-end-0 text-muted"><i class="fas fa-search"></i></span>
                                <input type="text" id="searchListing" class="form-control border-start-0 ps-0" placeholder="Search by title or location...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <select id="filterType" class="form-select">
                                <option value="">All Types</option>
                                <option value="house_rent">House Rent</option>
                                <option value="car_rent">Car Rent</option>
                                <option value="bus_ticket">Bus Ticket</option>
                                <option value="home_service">Home Service</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="grid-container" id="listingGrid">
                <div class="row g-4">
                <?php if (empty($listings)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 100px; height: 100px;">
                            <i class="fas fa-home text-muted fs-1"></i>
                        </div>
                        <h4 class="text-muted">You haven't posted any listings yet.</h4>
                        <a href="post_listing.php" class="btn btn-primary-green rounded-pill mt-3 px-4">Post Your First Item</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($listings as $item): ?>
                        <div class="col-md-4 listing-item" data-title="<?php echo strtolower($item['title']); ?>" data-location="<?php echo strtolower($item['location']); ?>" data-type="<?php echo $item['type']; ?>">
                            <div class="card listing-card h-100 shadow-sm position-relative border-0" style="border-radius: 20px; overflow: hidden;">
                                <?php 
                                    $statusCls = 'bg-success';
                                    $statusText = $item['status'] ?: 'available';
                                    if ($item['status'] === 'on_process') { $statusCls = 'bg-warning text-dark'; $statusText = 'On Process'; }
                                    if ($item['status'] === 'rented') { $statusCls = 'bg-danger'; $statusText = 'Rented'; }
                                    if ($item['status'] === 'not_available') { $statusCls = 'bg-danger'; $statusText = 'Not Available'; }
                                ?>
                                <span class="badge badge-status <?php echo $statusCls; ?> rounded-pill px-3 py-2 shadow-sm" style="position: absolute; top: 15px; left: 15px; z-index: 10;">
                                    <?php 
                                        if ($item['status'] === 'rented' && $item['rented_duration']) {
                                            echo 'Rented for ' . $item['rented_duration'] . ' ' . ($item['rented_duration'] > 1 ? 'Months' : 'Month');
                                        } else {
                                            echo ucfirst(str_replace('_', ' ', $statusText)); 
                                        }
                                    ?>
                                </span>
                                <img src="<?php 
                                    if (empty($item['image_url'])) {
                                        echo 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=800';
                                    } elseif (strpos($item['image_url'], 'http') === 0) {
                                        echo htmlspecialchars($item['image_url']);
                                    } else {
                                        echo BASE_URL . '/' . htmlspecialchars($item['image_url']);
                                    }
                                ?>" class="card-img-top listing-img" alt="Listing Image" style="height: 220px; object-fit: cover;">
                                <div class="card-body p-4">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0 text-truncate" style="max-width: 70%;"><?php echo htmlspecialchars($item['title']); ?></h5>
                                        <span class="badge bg-light text-success border-success border-opacity-25 border text-uppercase small" style="font-size: 0.65rem;">
                                            <?php echo str_replace('_', ' ', $item['type']); ?>
                                        </span>
                                    </div>
                                    <p class="text-muted small mb-3"><i class="fas fa-map-marker-alt me-1 text-danger opacity-75"></i> <?php echo htmlspecialchars($item['location']); ?></p>
                                    
                                    <?php if (($item['type'] === 'house_rent' || $item['type'] === 'real_estate') && ($item['bedrooms'] || $item['bathrooms'])): ?>
                                        <div class="d-flex gap-3 mb-3 text-muted small border-top pt-3">
                                            <span><i class="fas fa-bed me-1 text-primary"></i> <?php echo $item['bedrooms']; ?> Bed</span>
                                            <span><i class="fas fa-bath me-1 text-info"></i> <?php echo $item['bathrooms']; ?> Bath</span>
                                            <?php if ($item['area_sqm']): ?>
                                                <span><i class="fas fa-ruler-combined me-1"></i> <?php echo $item['area_sqm']; ?> m²</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="text-muted small d-block">Price</span>
                                            <h5 class="text-primary-green fw-bold mb-0"><?php echo number_format($item['price']); ?> <small class="fw-normal fs-6">ETB</small></h5>
                                        </div>
                                        <div class="d-flex gap-1">
                                            <?php if ($item['status'] === 'rented'): ?>
                                                <button onclick="confirmAvailable(<?php echo $item['id']; ?>, 'rented')" class="btn btn-success btn-sm rounded-circle shadow-sm" title="Mark as Available" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-handshake"></i>
                                                </button>
                                            <?php elseif ($item['status'] === 'not_available'): ?>
                                                <button onclick="confirmAvailable(<?php echo $item['id']; ?>, 'hidden')" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Unhide Listing" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-eye-slash text-danger"></i>
                                                </button>
                                            <?php else: ?>
                                                <button onclick="promptRented(<?php echo $item['id']; ?>)" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Mark as Rented" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-handshake text-muted"></i>
                                                </button>
                                                <button onclick="confirmNotAvailable(<?php echo $item['id']; ?>)" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Hide Listing" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="fas fa-eye text-success"></i>
                                                </button>
                                            <?php endif; ?>

                                            <a href="edit_listing.php?id=<?php echo $item['id']; ?>" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Edit" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-edit text-primary"></i>
                                            </a>
                                            <button onclick="confirmDelete(<?php echo $item['id']; ?>)" class="btn btn-light btn-sm rounded-circle shadow-sm" title="Delete" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-trash text-danger"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <?php if ($item['status'] === 'rented' && $item['rented_at']): ?>
                                        <?php 
                                            $rented_at = strtotime($item['rented_at']);
                                            $duration_months = (int)($item['rented_duration'] ?: 1);
                                            $expiry_timestamp = $rented_at + ($duration_months * 30 * 24 * 60 * 60);
                                            $days_left = ceil(($expiry_timestamp - time()) / (24 * 60 * 60));
                                            
                                            $urgency_class = $days_left > 7 ? 'bg-success' : ($days_left > 3 ? 'bg-warning text-dark' : 'bg-danger');
                                        ?>
                                        <div class="mt-4 p-3 rounded-4 <?php echo $urgency_class; ?> bg-opacity-10 border border-<?php echo str_replace('bg-', '', explode(' ', $urgency_class)[0]); ?>">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <span class="small fw-bold <?php echo str_replace('bg-', 'text-', explode(' ', $urgency_class)[0]); ?>">
                                                    <i class="fas fa-hourglass-half me-1"></i> 
                                                    <?php echo $days_left > 0 ? $days_left . ' Days Left' : 'Time Up!'; ?>
                                                </span>
                                                <span class="small text-muted" style="font-size: 0.65rem;">Expires: <?php echo date('M d', $expiry_timestamp); ?></span>
                                            </div>
                                            <div class="progress mb-2" style="height: 5px; border-radius: 10px;">
                                                <div class="progress-bar <?php echo $urgency_class; ?> rounded-pill" style="width: <?php echo max(0, min(100, ($days_left / ($duration_months * 30)) * 100)); ?>%"></div>
                                            </div>
                                            <div class="d-flex justify-content-between align-items-center mt-2">
                                                <span class="small text-muted" style="font-size: 0.75rem;"><i class="fas fa-coins me-1 text-warning"></i> Next Rent:</span>
                                                <span class="small fw-bold text-dark"><?php echo number_format($item['price']); ?> ETB</span>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Live Search & Filter
        const searchInput = document.getElementById('searchListing');
        const typeFilter = document.getElementById('filterType');
        const items = document.querySelectorAll('.listing-item');

        function filterListings() {
            const query = (searchInput.value || '').toLowerCase();
            const typeValue = typeFilter.value;

            items.forEach(item => {
                const title = item.getAttribute('data-title') || '';
                const loc = item.getAttribute('data-location') || '';
                const type = item.getAttribute('data-type') || '';

                const matchesSearch = title.includes(query) || loc.includes(query);
                const matchesType = typeValue === '' || type === typeValue;

                if (matchesSearch && matchesType) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        if (searchInput) searchInput.addEventListener('input', filterListings);
        if (typeFilter) typeFilter.addEventListener('change', filterListings);

        function promptRented(id) {
            Swal.fire({
                title: 'Mark as Rented',
                text: 'How many months is the rental duration?',
                input: 'select',
                inputOptions: {
                    '1': '1 Month',
                    '2': '2 Months',
                    '3': '3 Months',
                    '6': '6 Months',
                    '12': '1 Year',
                    '24': '2 Years'
                },
                inputPlaceholder: 'Select duration',
                showCancelButton: true,
                confirmButtonColor: '#1B5E20',
                confirmButtonText: 'Confirm Rented'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `my_listings.php?set_status=rented&id=${id}&duration=${result.value}`;
                }
            })
        }

        function confirmNotAvailable(id) {
            Swal.fire({
                title: 'Hide Listing?',
                text: "This property will be hidden from customers until you unhide it.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#6c757d',
                cancelButtonColor: '#1B5E20',
                confirmButtonText: 'Yes, hide it'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `my_listings.php?set_status=not_available&id=${id}`;
                }
            })
        }

        function confirmAvailable(id, type) {
            let title = type === 'rented' ? 'Mark as Available?' : 'Unhide Listing?';
            let text = type === 'rented' ? 'Customers will be able to rent this again.' : 'This listing will be visible to customers again.';
            
            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#1B5E20',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, make available'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `my_listings.php?set_status=available&id=${id}`;
                }
            })
        }

        function confirmDelete(id) {
            Swal.fire({
                title: 'Move to Recycle Bin?',
                text: "You can restore this listing later from the Recycle Bin.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#1B5E20',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'my_listings.php?delete_id=' + id;
                }
            })
        }
    </script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('brokerSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    </script>
</body>
</html>
