<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_taxi.php', 'danger', 'Company ID is required');
}

$stmt = $pdo->prepare("SELECT t.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone
                     FROM taxi_companies t 
                     JOIN users u ON t.user_id = u.id 
                     WHERE t.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('manage_taxi.php', 'danger', 'Company not found');
}

// Fetch stats
$stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_vehicles WHERE company_id = ?");
$stmt->execute([$id]);
$vehicle_count = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Taxi Company -
        <?php echo htmlspecialchars($item['company_name']); ?>
    </title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .taxi-header-bg {
            height: 250px;
            background: linear-gradient(135deg, #FFD600, #FFA000);
            border-radius: 0 0 40px 40px;
            display: flex;
            align-items: flex-end;
            padding-bottom: 40px;
            color: black;
        }

        .info-card {
            border-radius: 20px;
            border: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
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
            <div class="taxi-header-bg px-5">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-end">
                        <div>
                            <nav aria-label="breadcrumb">
                                <ol class="breadcrumb mb-2">
                                    <li class="breadcrumb-item"><a href="manage_taxi.php"
                                            class="text-dark opacity-50">Manage Taxi</a></li>
                                    <li class="breadcrumb-item active text-dark" aria-current="page">View Company</li>
                                </ol>
                            </nav>
                            <h1 class="display-4 fw-bold mb-0">
                                <?php echo htmlspecialchars($item['company_name']); ?>
                            </h1>
                            <p class="fs-5 mb-0 fw-medium"><i class="fas fa-map-marker-alt me-2"></i>
                                <?php echo htmlspecialchars($item['address'] ?? 'N/A'); ?>
                            </p>
                        </div>
                        <div class="mb-2">
                            <a href="edit_taxi.php?id=<?php echo $item['id']; ?>"
                                class="btn btn-dark rounded-pill px-4 fw-bold">
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
                            <h5 class="fw-bold mb-4">Operational Insights</h5>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-warning bg-opacity-20 text-dark">
                                            <i class="fas fa-car"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Total Vehicles</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo $vehicle_count; ?> Cars
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-success bg-opacity-10 text-success">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Customer Rating</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo number_format($item['rating'] ?? 0, 1); ?> / 5.0
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div class="icon-box bg-info bg-opacity-10 text-info">
                                            <i class="fas fa-calendar-check"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Join Date</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo date('M d, Y', strtotime($item['created_at'])); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center">
                                        <div
                                            class="icon-box <?php echo $item['status'] == 'approved' ? 'bg-success' : 'bg-warning'; ?> bg-opacity-10 <?php echo $item['status'] == 'approved' ? 'text-success' : 'text-warning'; ?>">
                                            <i class="fas fa-shield-alt"></i>
                                        </div>
                                        <div>
                                            <small class="text-muted d-block">Verification Status</small>
                                            <span class="fw-bold fs-5">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card info-card p-4">
                            <h5 class="fw-bold mb-4">Service Description</h5>
                            <p class="text-muted">
                                <?php echo nl2br(htmlspecialchars($item['description'] ?? 'No description available for this taxi company.')); ?>
                            </p>
                        </div>
                    </div>

                    <div class="col-md-4 py-5">
                        <div class="card info-card p-4 mb-4">
                            <h6 class="fw-bold mb-4 text-center">Proprietor Contact</h6>
                            <div class="text-center mb-4">
                                <img src="<?php echo 'https://ui-avatars.com/api/?name=' . urlencode($item['owner_name']) . '&background=FFC107&color=000&size=128'; ?>"
                                    class="rounded-circle shadow-sm mb-3" width="100" height="100"
                                    style="object-fit:cover;">
                                <h6 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($item['owner_name']); ?>
                                </h6>
                                <span class="badge bg-warning text-dark rounded-pill">Taxi Proprietor</span>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <small class="text-muted d-block"><i class="fas fa-envelope me-2"></i>Email</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($item['owner_email']); ?>
                                </span>
                            </div>
                            <div class="mb-0">
                                <small class="text-muted d-block"><i class="fas fa-phone me-2"></i>Primary Phone</small>
                                <span class="fw-medium">
                                    <?php echo htmlspecialchars($item['owner_phone'] ?: 'N/A'); ?>
                                </span>
                            </div>
                        </div>

                        <div class="card info-card p-4 bg-dark text-white">
                            <h5 class="fw-bold mb-3">Company Actions</h5>
                            <form method="POST" action="manage_taxi.php" class="d-grid gap-2">
                                <?php echo csrfField(); ?>
                                <input type="hidden" name="company_id" value="<?php echo $item['id']; ?>">
                                <input type="hidden" name="update_status" value="1">
                                <?php if ($item['status'] !== 'approved'): ?>
                                    <button type="submit" name="status" value="approved"
                                        class="btn btn-warning fw-bold rounded-pill">Verify Company</button>
                                <?php endif; ?>
                                <?php if ($item['status'] !== 'rejected'): ?>
                                    <button type="submit" name="status" value="rejected"
                                        class="btn btn-outline-light fw-bold rounded-pill">Suspend</button>
                                <?php endif; ?>
                                <a href="manage_taxi.php?delete=<?php echo $item['id']; ?>"
                                    class="btn btn-danger fw-bold rounded-pill mt-2"
                                    onclick="return confirm('Delete taxi company permanently?')">
                                    <i class="fas fa-trash me-2"></i>Delete Company
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