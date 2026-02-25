<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_hotels.php', 'danger', 'Hotel ID is required');
}

$stmt = $pdo->prepare("SELECT h.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
                     FROM hotels h 
                     JOIN users u ON h.user_id = u.id 
                     WHERE h.id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    redirectWithMessage('manage_hotels.php', 'danger', 'Hotel not found');
}
// Fetch stats or related info if any (e.g., menu items count)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM menu_items WHERE hotel_id = ?");
$stmt->execute([$id]);
$menu_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Hotel -
        <?php echo htmlspecialchars($hotel['name']); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .hotel-header-bg {
            height: 300px;
            background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('<?php echo htmlspecialchars($hotel['image_url'] ?: "https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?w=1200"); ?>');
            background-size: cover;
            background-position: center;
            border-radius: 0 0 40px 40px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 40px;
            color: white;
        }

        .profile-img-overlap {
            width: 150px;
            height: 150px;
            border-radius: 20px;
            object-fit: cover;
            border: 5px solid white;
            box-shadow: var(--shadow-md);
            margin-top: -75px;
            background: white;
        }

        .info-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
        }

        .info-card:hover {
            transform: translateY(-5px);
        }

        .icon-box {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content p-0">
            <div class="hotel-header-bg px-5">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item"><a href="manage_hotels.php" class="text-white-50">Manage
                                            Hotels</a></li>
                                    <li class="breadcrumb-item active text-white" aria-current="page">View Hotel</li>
                                </ol>
                            </nav>
                            <h1 class="display-4 fw-bold mb-0">
                                <?php echo htmlspecialchars($hotel['name']); ?>
                            </h1>
                            <p class="fs-5 mb-0 opacity-75"><i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($hotel['location']); ?>
                            </p>
                        </div>
                        <div class="mb-2">
                            <a href="edit_hotel.php?id=<?php echo $hotel['id']; ?>"
                                class="btn btn-warning rounded-pill px-4 fw-bold">
                                <i class="fas fa-edit me-2"></i>Edit Profile
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid px-5">
                <div class="row">
                    <div class="col-md-8 py-5">
                        <div class="card info-card p-4 mb-4">
                            <h5 class="fw-bold mb-4">General Information</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-primary-green bg-opacity-10 text-primary-green">
                                            <i class="fas fa-utensils"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Cuisine Type</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo htmlspecialchars($hotel['cuisine_type'] ?: 'International'); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-warning bg-opacity-10 text-warning">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Rating</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo number_format($hotel['rating'], 1); ?> / 5.0
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-info bg-opacity-10 text-info">
                                            <i class="fas fa-list-ul"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Menu Items</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo $menu_count; ?> Items
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="icon-box <?php echo $hotel['status'] == 'approved' ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10 <?php echo $hotel['status'] == 'approved' ? 'text-success' : 'text-warning'; ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Account Status</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo ucfirst($hotel['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card info-card p-4">
                            <h5 class="fw-bold mb-4">About the Hotel</h5>
                            <p class="text-muted lead">
                                <?php echo nl2br(htmlspecialchars($hotel['description'] ?? 'No description available for this hotel yet. This hotel is one of our premium partners providing exceptional service in ' . $hotel['location'] . '.')); ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4 py-5">
                        <div class="card info-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 text-center">Owner Details</h5>
                            <div class="text-center mb-4">
                                <img src="<?php echo 'https://ui-avatars.com/api/?name=' . urlencode($hotel['owner_name']) . '&background=1B5E20&color=fff&size=128'; ?>"
                                    class="rounded-circle shadow-sm mb-3" width="100" height="100"
                                    style="object-fit: cover;">
                                <h6 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($hotel['owner_name']); ?>
                                </h6>
                                <span class="badge bg-primary-green rounded-pill">Hotel Owner</span>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block"><i class="fas fa-envelope me-2"></i>Email</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($hotel['owner_email']); ?>
                                </span>
                            </div>
                            <div class="mb-0">
                                <small class="text-muted d-block"><i class="fas fa-phone me-2"></i>Phone</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($hotel['owner_phone'] ?: 'No phone provided'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card info-card p-4 bg-primary-green text-white">
                            <h5 class="fw-bold mb-3">Admin Actions</h5>
                            <p class="small opacity-75 mb-4">Quickly manage this hotel's status or delete the record.
                            </p>
                            <form method="POST" action="manage_hotels.php" class="d-grid gap-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="hotel_id" value="<?php echo $hotel['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <?php if ($hotel['status'] !== 'approved'): ?>
                                    <button type="submit" name="status" value="approved"
                                        class="btn btn-light fw-bold rounded-pill">Approve Hotel</button>
                                <?php endif; ?>
                                <?php if ($hotel['status'] !== 'rejected'): ?>
                                    <button type="submit" name="status" value="rejected"
                                        class="btn btn-outline-light fw-bold rounded-pill">Reject Hotel</button>
                                <?php endif; ?>
                                <a href="manage_hotels.php?delete=<?php echo $hotel['id']; ?>"
                                    class="btn btn-danger fw-bold rounded-pill mt-2"
                                    onclick="return confirm('Strict Action: Permanently delete this hotel?')">
                                    <i class="fas fa-trash me-2"></i>Delete Hotel
                                </a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>