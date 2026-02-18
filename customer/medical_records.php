<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch Appointments
$stmt = $pdo->prepare("SELECT a.*, p.name as doctor_name, p.type as provider_type, s.name as specialty 
                       FROM health_appointments a
                       JOIN health_providers p ON a.provider_id = p.id
                       LEFT JOIN health_specialties s ON p.specialty_id = s.id
                       WHERE a.user_id = ? ORDER BY a.scheduled_at DESC");
$stmt->execute([$user_id]);
$appointments = $stmt->fetchAll();

// Fetch Pharmacy Orders
$stmt = $pdo->prepare("SELECT o.*, p.name as pharmacy_name 
                       FROM health_pharmacy_orders o
                       JOIN health_providers p ON o.pharmacy_id = p.id
                       WHERE o.user_id = ? ORDER BY o.created_at DESC");
$stmt->execute([$user_id]);
$orders = $stmt->fetchAll();

// Fetch Records
$stmt = $pdo->prepare("SELECT * FROM health_records WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$records = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">My Medical Center</h2>
            <p class="text-muted">Manage your health history, appointments, and prescriptions</p>
        </div>
        <div class="dropdown">
            <button class="btn btn-primary rounded-pill px-4 dropdown-toggle" data-bs-toggle="dropdown">New
                Service</button>
            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                <li><a class="dropdown-item py-2" href="doctors.php"><i class="fas fa-user-md me-2"></i>Book Doctor</a>
                </li>
                <li><a class="dropdown-item py-2" href="pharmacy.php"><i class="fas fa-pills me-2"></i>Order
                        Medicine</a></li>
                <li><a class="dropdown-item py-2" href="lab.php"><i class="fas fa-flask me-2"></i>Book Lab Test</a></li>
            </ul>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-5 bg-white p-2 rounded- pill shadow-sm d-inline-flex" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active rounded-pill px-4" data-bs-toggle="pill"
                data-bs-target="#pills-appt">Appointments</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#pills-orders">Lab &
                Pharmacy</button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link rounded-pill px-4" data-bs-toggle="pill" data-bs-target="#pills-records">Health
                Records</button>
        </li>
    </ul>

    <div class="tab-content" id="pills-tabContent">
        <!-- Appointments -->
        <div class="tab-pane fade show active" id="pills-appt">
            <div class="row g-4">
                <?php if (empty($appointments)): ?>
                    <div class="col-12 text-center py-5">
                        <p class="text-muted">No appointments found.</p>
                    </div>
                <?php endif; ?>
                <?php foreach ($appointments as $a): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="bg-primary bg-opacity-10 p-3 rounded-4">
                                    <i
                                        class="fas <?php echo $a['appointment_type'] == 'virtual' ? 'fa-video' : 'fa-hospital-user'; ?> text-primary fs-3"></i>
                                </div>
                                <span
                                    class="badge <?php echo $a['status'] == 'confirmed' ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill">
                                    <?php echo ucfirst($a['status']); ?>
                                </span>
                            </div>
                            <h6 class="fw-bold mb-1">
                                <?php echo htmlspecialchars($a['doctor_name']); ?>
                            </h6>
                            <div class="small text-muted mb-3">
                                <?php echo htmlspecialchars($a['specialty'] ?: 'Diagnostic'); ?>
                            </div>

                            <div class="p-3 bg-light rounded-4 mb-3">
                                <div class="small text-muted">Schedule:</div>
                                <div class="fw-bold">
                                    <?php echo date('M d, Y - h:i A', strtotime($a['scheduled_at'])); ?>
                                </div>
                            </div>

                            <?php if ($a['status'] == 'confirmed' && $a['appointment_type'] == 'virtual'): ?>
                                <a href="#" class="btn btn-primary w-100 rounded-pill fw-bold">Join Video Call</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Orders -->
        <div class="tab-pane fade" id="pills-orders">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Order ID</th>
                                <th>Pharmacy/Lab</th>
                                <th>Items</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">No orders found.</td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($orders as $o): ?>
                                <tr>
                                    <td class="ps-4 fw-bold">#PH-
                                        <?php echo $o['id']; ?>
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($o['pharmacy_name']); ?>
                                    </td>
                                    <td>
                                        <?php
                                        $items = json_decode($o['items']);
                                        echo implode(', ', array_slice($items, 0, 2)) . (count($items) > 2 ? '...' : '');
                                        ?>
                                    </td>
                                    <td class="fw-bold text-success">
                                        <?php echo number_format($o['total_price']); ?> ETB
                                    </td>
                                    <td><span class="badge bg-info text-white rounded-pill">
                                            <?php echo ucfirst($o['status']); ?>
                                        </span></td>
                                    <td class="text-end pe-4 small">
                                        <?php echo date('M d, Y', strtotime($o['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Records -->
        <div class="tab-pane fade" id="pills-records">
            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h5 class="fw-bold mb-4">Reports & Prescriptions</h5>
                        <?php if (empty($records)): ?>
                            <div class="text-center py-4 bg-light rounded-4">
                                <i class="fas fa-folder-open fs-1 text-muted mb-2"></i>
                                <p class="text-muted mb-0">Your digital reports will appear here.</p>
                            </div>
                        <?php endif; ?>
                        <?php foreach ($records as $r): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="bg-purple bg-opacity-10 p-3 rounded-3 me-3">
                                    <i class="fas fa-file-pdf text-purple fs-4"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="fw-bold mb-1">
                                        <?php echo htmlspecialchars($r['title']); ?>
                                    </h6>
                                    <div class="small text-muted">
                                        <?php echo date('M d, Y', strtotime($r['created_at'])); ?>
                                    </div>
                                </div>
                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">View</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white">
                        <h5 class="fw-bold mb-3">Sync Medical Device</h5>
                        <p class="small opacity-75">Connect your smartwatch or blood pressure monitor to sync vital
                            signs automatically.</p>
                        <button class="btn btn-light rounded-pill w-100 fw-bold">Connect Device</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-purple {
        background-color: #9C27B0;
    }

    .text-purple {
        color: #9C27B0;
    }

    .nav-pills .nav-link.active {
        background-color: #0d6efd;
        color: white;
    }
</style>

<?php include '../includes/footer.php'; ?>