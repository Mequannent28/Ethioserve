<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_bus.php', 'danger', 'Company ID is required');
}

$stmt = $pdo->prepare("SELECT b.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
                     FROM transport_companies b 
                     JOIN users u ON b.user_id = u.id 
                     WHERE b.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('manage_bus.php', 'danger', 'Company not found');
}

// Stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bus_vehicles WHERE company_id = ?");
$stmt->execute([$id]);
$vehicle_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Bus Company -
        <?php echo htmlspecialchars($item['company_name']); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .bus-header-bg {
            height: 250px;
            background: linear-gradient(135deg, #0D47A1, #1976D2);
            border-radius: 0 0 40px 40px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 40px;
            color: white;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content p-0">
            <div class="bus-header-bg px-5">
                <div class="container-fluid d-flex justify-content-between align-items-end">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb mb-2">
                                <li class="breadcrumb-item"><a href="manage_bus.php"
                                        class="text-white opacity-50">Manage Bus</a></li>
                                <li class="breadcrumb-item active text-white" aria-current="page">View</li>
                            </ol>
                        </nav>
                        <h1 class="display-4 fw-bold mb-0">
                            <?php echo htmlspecialchars($item['company_name']); ?>
                        </h1>
                        <p class="fs-5 mb-0"><i class="fas fa-map-marker-alt me-2"></i>
                            <?php echo htmlspecialchars($item['location'] ?? 'N/A'); ?>
                        </p>
                    </div>
                    <div class="mb-2"><a href="edit_bus.php?id=<?php echo $item['id']; ?>"
                            class="btn btn-warning rounded-pill px-4 fw-bold">Edit Profile</a></div>
                </div>
            </div>
            <div class="container-fluid px-5 py-5">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                            <h5 class="fw-bold mb-4">Operations Summary</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-primary bg-opacity-10 p-3 rounded-3 me-3"><i
                                                class="fas fa-bus text-primary"></i></div>
                                        <div><small class="text-muted d-block">Fleet Size</small><span
                                                class="fw-bold fs-5">
                                                <?php echo $vehicle_count; ?> Buses
                                            </span></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-warning bg-opacity-10 p-3 rounded-3 me-3"><i
                                                class="fas fa-star text-warning"></i></div>
                                        <div><small class="text-muted d-block">Rating</small><span class="fw-bold fs-5">
                                                <?php echo number_format($item['rating'] ?? 0, 1); ?>
                                            </span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold mb-4">Company Profile</h5>
                            <p class="text-muted">
                                <?php echo nl2br(htmlspecialchars($item['description'] ?? 'No bio available.')); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 text-center">
                            <img src="<?php echo 'https://ui-avatars.com/api/?name=' . urlencode($item['owner_name']); ?>"
                                class="rounded-circle mb-3 mx-auto" width="80" height="80">
                            <h6 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($item['owner_name']); ?>
                            </h6>
                            <p class="text-muted small">
                                <?php echo htmlspecialchars($item['owner_email']); ?>
                            </p>
                            <p class="fw-bold text-primary">
                                <?php echo htmlspecialchars($item['owner_phone'] ?: 'No Phone'); ?>
                            </p>
                        </div>
                        <div class="card border-0 shadow-sm rounded-4 p-4 bg-dark text-white">
                            <h5 class="fw-bold mb-3">Admin Actions</h5>
                            <form method="POST" action="manage_bus.php" class="d-grid gap-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="company_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <button type="submit" name="status" value="approved"
                                    class="btn btn-primary fw-bold rounded-pill">Approve</button>
                                <button type="submit" name="status" value="rejected"
                                    class="btn btn-outline-light fw-bold rounded-pill">Reject</button>
                                <a href="manage_bus.php?delete=<?php echo $item['id']; ?>"
                                    class="btn btn-danger fw-bold rounded-pill mt-2"
                                    onclick="return confirm('Delete permanently?')">Delete</a>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>