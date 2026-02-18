<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle status updates for Appointments
if (isset($_GET['appt_id']) && isset($_GET['status'])) {
    $id = intval($_GET['appt_id']);
    $status = $_GET['status'];
    $stmt = $pdo->prepare("UPDATE health_appointments SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    redirectWithMessage('manage_health.php', 'success', 'Appointment status updated');
}

// Handle status updates for Orders
if (isset($_GET['order_id']) && isset($_GET['status'])) {
    $id = intval($_GET['order_id']);
    $status = $_GET['status'];
    $stmt = $pdo->prepare("UPDATE health_pharmacy_orders SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    redirectWithMessage('manage_health.php', 'success', 'Order status updated');
}

// Fetch Data
$appointments = $pdo->query("SELECT a.*, p.name as provider_name, u.full_name as user_name FROM health_appointments a JOIN health_providers p ON a.provider_id = p.id JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 50")->fetchAll();
$orders = $pdo->query("SELECT o.*, p.name as pharmacy_name, u.full_name as user_name FROM health_pharmacy_orders o JOIN health_providers p ON o.pharmacy_id = p.id JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 50")->fetchAll();
$ambulances = $pdo->query("SELECT a.*, u.full_name as user_name FROM health_ambulance_requests a JOIN users u ON a.user_id = u.id ORDER BY a.created_at DESC LIMIT 20")->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Manage Health - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f4f7f6;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            padding: 30px;
        }

        .tab-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <h2 class="fw-bold mb-4">Health Services Management</h2>

            <ul class="nav nav-tabs mb-4 px-3" id="healthTabs">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab"
                        data-bs-target="#appts">Appointments</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#pharmacy">Pharmacy &
                        Lab</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab"
                        data-bs-target="#emergencies">Ambulance</button></li>
            </ul>

            <div class="tab-content">
                <!-- Appointments -->
                <div class="tab-pane fade show active" id="appts">
                    <div class="card tab-card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">Patient</th>
                                        <th>Provider</th>
                                        <th>Schedule</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($appointments as $a): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                <?php echo htmlspecialchars($a['user_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($a['provider_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo date('M d, h:i A', strtotime($a['scheduled_at'])); ?>
                                            </td>
                                            <td><span class="badge bg-light text-dark border">
                                                    <?php echo ucfirst(str_replace('_', ' ', $a['appointment_type'])); ?>
                                                </span></td>
                                            <td><span
                                                    class="badge bg-<?php echo $a['status'] == 'confirmed' ? 'success' : ($a['status'] == 'pending' ? 'warning text-dark' : 'secondary'); ?> rounded-pill px-3">
                                                    <?php echo ucfirst($a['status']); ?>
                                                </span></td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button
                                                        class="btn btn-sm btn-outline-primary rounded-pill dropdown-toggle"
                                                        data-bs-toggle="dropdown">Update</button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item"
                                                                href="?appt_id=<?php echo $a['id']; ?>&status=confirmed">Confirm</a>
                                                        </li>
                                                        <li><a class="dropdown-item"
                                                                href="?appt_id=<?php echo $a['id']; ?>&status=completed">Complete</a>
                                                        </li>
                                                        <li><a class="dropdown-item text-danger"
                                                                href="?appt_id=<?php echo $a['id']; ?>&status=cancelled">Cancel</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Pharmacy Orders -->
                <div class="tab-pane fade" id="pharmacy">
                    <div class="card tab-card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="ps-4">User</th>
                                        <th>Provider</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($orders as $o): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                <?php echo htmlspecialchars($o['user_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($o['pharmacy_name']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars(implode(', ', json_decode($o['items']))); ?>
                                            </td>
                                            <td class="fw-bold">
                                                <?php echo number_format($o['total_price']); ?>
                                            </td>
                                            <td><span class="badge bg-info rounded-pill px-3">
                                                    <?php echo ucfirst($o['status']); ?>
                                                </span></td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-info rounded-pill dropdown-toggle"
                                                        data-bs-toggle="dropdown">Status</button>
                                                    <ul class="dropdown-menu shadow border-0">
                                                        <li><a class="dropdown-item"
                                                                href="?order_id=<?php echo $o['id']; ?>&status=preparing">Preparing</a>
                                                        </li>
                                                        <li><a class="dropdown-item"
                                                                href="?order_id=<?php echo $o['id']; ?>&status=out_for_delivery">Out
                                                                for Delivery</a></li>
                                                        <li><a class="dropdown-item"
                                                                href="?order_id=<?php echo $o['id']; ?>&status=delivered">Delivered</a>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Emergency -->
                <div class="tab-pane fade" id="emergencies">
                    <div class="card tab-card overflow-hidden">
                        <div class="table-responsive">
                            <table class="table table-danger table-hover align-middle mb-0">
                                <thead class="table-dark">
                                    <tr>
                                        <th class="ps-4">Patient</th>
                                        <th>Emergency Type</th>
                                        <th>Location</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ambulances as $amb): ?>
                                        <tr>
                                            <td class="ps-4 fw-bold">
                                                <?php echo htmlspecialchars($amb['user_name']); ?>
                                            </td>
                                            <td><span class="badge bg-danger">
                                                    <?php echo htmlspecialchars($amb['emergency_type']); ?>
                                                </span></td>
                                            <td>
                                                <?php echo htmlspecialchars($amb['current_location']); ?>
                                            </td>
                                            <td><a href="tel:<?php echo $amb['contact_phone']; ?>"
                                                    class="text-decoration-none fw-bold">
                                                    <?php echo htmlspecialchars($amb['contact_phone']); ?>
                                                </a></td>
                                            <td><span class="badge bg-dark rounded-pill">
                                                    <?php echo ucfirst($amb['status']); ?>
                                                </span></td>
                                            <td class="text-end pe-4 small">
                                                <?php echo date('h:i A', strtotime($amb['created_at'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>