<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_restaurants.php', 'danger', 'Restaurant ID is required');
}

$stmt = $pdo->prepare("SELECT r.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
                     FROM restaurants r 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('manage_restaurants.php', 'danger', 'Restaurant not found');
}

// Fetch stats (e.g. food items count)
$stmt = $pdo->prepare("SELECT COUNT(*) FROM food_items WHERE restaurant_id = ?");
$stmt->execute([$id]);
$food_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Restaurant -
        <?php echo htmlspecialchars($item['name']); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .restaurant-header-bg {
            height: 300px;
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), url('<?php echo htmlspecialchars($item['image_url'] ?: "https://images.unsplash.com/photo-1517248135467-4c7ed9d421bb?w=1200"); ?>');
            background-size: cover;
            background-position: center;
            border-radius: 0 0 40px 40px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 40px;
            color: white;
        }

        .info-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s;
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
            <div class="restaurant-header-bg px-5">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item"><a href="manage_restaurants.php"
                                            class="text-white-50">Manage Restaurants</a></li>
                                    <li class="breadcrumb-item active text-white" aria-current="page">View Restaurant
                                    </li>
                                </ol>
                            </nav>
                            <h1 class="display-4 fw-bold mb-0">
                                <?php echo htmlspecialchars($item['name']); ?>
                            </h1>
                            <p class="fs-5 mb-0 opacity-75"><i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($item['location']); ?>
                            </p>
                        </div>
                        <div class="mb-2">
                            <a href="edit_restaurant.php?id=<?php echo $item['id']; ?>"
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
                                                <?php echo htmlspecialchars($item['cuisine_type'] ?: 'International'); ?>
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
                                                <?php echo number_format($item['rating'] ?? 0, 1); ?> / 5.0
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-info bg-opacity-10 text-info">
                                            <i class="fas fa-burger"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Food Items</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo $food_count; ?> Items
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="icon-box <?php echo $item['status'] == 'approved' ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10 <?php echo $item['status'] == 'approved' ? 'text-success' : 'text-warning'; ?>">
                                            <i class="fas fa-info-circle"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Status</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card info-card p-4">
                            <h5 class="fw-bold mb-4">Restaurant Description</h5>
                            <p class="text-muted lead">
                                <?php echo nl2br(htmlspecialchars($item['description'] ?? 'No special description provided. This restaurant is a valued member of the EthioServe network in ' . $item['location'] . '.')); ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4 py-5">
                        <div class="card info-card p-4 mb-4">
                            <h5 class="fw-bold mb-4 text-center">Owner Account</h5>
                            <div class="text-center mb-4">
                                <img src="<?php echo 'https://ui-avatars.com/api/?name=' . urlencode($item['owner_name']) . '&background=1B5E20&color=fff&size=128'; ?>"
                                    class="rounded-circle shadow-sm mb-3" width="100" height="100"
                                    style="object-fit: cover;">
                                <h6 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($item['owner_name']); ?>
                                </h6>
                                <span class="badge bg-primary-green rounded-pill">Restaurant Owner</span>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block"><i class="fas fa-envelope me-2"></i>Email</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($item['owner_email']); ?>
                                </span>
                            </div>
                            <div class="mb-0">
                                <small class="text-muted d-block"><i class="fas fa-phone me-2"></i>Phone</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($item['owner_phone'] ?: 'N/A'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card info-card p-4 bg-primary-green text-white">
                            <h5 class="fw-bold mb-3">Management</h5>
                            <p class="small opacity-75 mb-4">Change visibility or delete restaurant permanently.</p>
                            <form method="POST" action="manage_restaurants.php" class="d-grid gap-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <?php if ($item['status'] !== 'approved'): ?>
                                    <button type="submit" name="status" value="approved"
                                        class="btn btn-light fw-bold rounded-pill">Approve</button>
                                <?php endif; ?>
                                <?php if ($item['status'] !== 'rejected'): ?>
                                    <button type="submit" name="status" value="rejected"
                                        class="btn btn-outline-light fw-bold rounded-pill">Reject</button>
                                <?php endif; ?>
                                <a href="manage_restaurants.php?delete=<?php echo $item['id']; ?>"
                                    class="btn btn-danger fw-bold rounded-pill mt-2"
                                    onclick="return confirm('Delete this restaurant and all associated menus?')">
                                    <i class="fas fa-trash me-2"></i>Delete
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