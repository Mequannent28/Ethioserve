<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if logged in and has doctor role
if (!isLoggedIn() || getCurrentUserRole() !== 'doctor') {
    redirectWithMessage('../login.php', 'warning', 'Please login as a doctor to access this dashboard.');
}

$user_id = getCurrentUserId();

// Fetch Doctor Record
$stmt = $pdo->prepare("SELECT p.*, s.name as specialty_name 
                       FROM health_providers p 
                       LEFT JOIN health_specialties s ON p.specialty_id = s.id 
                       WHERE p.user_id = ?");
$stmt->execute([$user_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    // If no provider record yet, maybe redirect to a profile setup page
    // For now, let's just show an error or create a dummy one
    die("Doctor profile not found for this user account. Please contact admin.");
}

$provider_id = $doctor['id'];

// Handle Status Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_appointment_status'])) {
    $appointment_id = intval($_POST['appointment_id']);
    $new_status = $_POST['status'];

    $allowed_statuses = ['pending', 'confirmed', 'completed', 'cancelled'];
    if (in_array($new_status, $allowed_statuses)) {
        $stmt = $pdo->prepare("UPDATE health_appointments SET status = ? WHERE id = ? AND provider_id = ?");
        $stmt->execute([$new_status, $appointment_id, $provider_id]);

        // Notify patient (could be a message or notification table if exists)
        $status_msg = "Your appointment status has been updated to: " . ucfirst($new_status);
        // Find user_id for this appointment
        $aStmt = $pdo->prepare("SELECT user_id FROM health_appointments WHERE id = ?");
        $aStmt->execute([$appointment_id]);
        $appt = $aStmt->fetch();
        if ($appt) {
            $stmtMsg = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message) VALUES (?, 'doctor', ?, ?, ?)");
            $stmtMsg->execute([$provider_id, $provider_id, $appt['user_id'], $status_msg]);
        }

        header("Location: dashboard.php?success=Status updated");
        exit();
    }
}

// Fetch Appointments
$stmt = $pdo->prepare("SELECT a.*, u.full_name as patient_name, u.email as patient_email, u.phone as patient_phone 
                       FROM health_appointments a
                       JOIN users u ON a.user_id = u.id
                       WHERE a.provider_id = ? 
                       ORDER BY a.scheduled_at DESC");
$stmt->execute([$provider_id]);
$appointments = $stmt->fetchAll();

// Fetch Recent Messages
$stmt = $pdo->prepare("SELECT m.id, m.message, m.is_read, m.sender_type, m.created_at, m.customer_id, u.full_name as patient_name 
                       FROM (
                           SELECT MAX(id) as id, customer_id 
                           FROM doctor_messages 
                           WHERE provider_id = ? 
                           GROUP BY customer_id
                       ) latest_msgs 
                       JOIN doctor_messages m ON latest_msgs.id = m.id 
                       JOIN users u ON m.customer_id = u.id 
                       ORDER BY m.created_at DESC 
                       LIMIT 5");
$stmt->execute([$provider_id]);
$recent_chats = $stmt->fetchAll();

// Stats
$stats = [
    'total' => count($appointments),
    'pending' => count(array_filter($appointments, fn($a) => $a['status'] === 'pending')),
    'confirmed' => count(array_filter($appointments, fn($a) => $a['status'] === 'confirmed')),
    'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'completed')),
];

include '../includes/header.php';
?>

<div class="doctor-dashboard-hero py-4 mb-4 text-white"
    style="background: linear-gradient(135deg, #0d47a1 0%, #1565C0 100%);">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center gap-4">
                    <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=400'); ?>"
                        class="rounded-circle border border-4 border-white-50 shadow-sm"
                        style="width: 100px; height: 100px; object-fit: cover;">
                    <div>
                        <h2 class="fw-bold mb-1">Welcome,
                            <?php echo htmlspecialchars($doctor['name']); ?>
                        </h2>
                        <p class="mb-0 text-white-50">
                            <i class="fas fa-stethoscope me-2"></i>
                            <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                            <span class="mx-2">|</span>
                            <i class="fas fa-map-marker-alt me-2 text-danger text-opacity-75"></i>
                            <?php echo htmlspecialchars($doctor['location']); ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="../customer/doctors.php" class="btn btn-light rounded-pill px-4 fw-bold">View Public
                    Profile</a>
            </div>
        </div>
    </div>
</div>

<div class="container py-2">
    <!-- Stats Row -->
    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="bg-primary bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width: 60px; height: 60px;">
                    <i class="fas fa-users text-primary fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0">
                    <?php echo $stats['total']; ?>
                </h3>
                <small class="text-muted">Total Patients</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="bg-warning bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width: 60px; height: 60px;">
                    <i class="fas fa-clock text-warning fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-warning">
                    <?php echo $stats['pending']; ?>
                </h3>
                <small class="text-muted">Pending Requests</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="bg-success bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width: 60px; height: 60px;">
                    <i class="fas fa-check-circle text-success fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-success">
                    <?php echo $stats['confirmed']; ?>
                </h3>
                <small class="text-muted">Confirmed Today</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                <div class="bg-info bg-opacity-10 rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3"
                    style="width: 60px; height: 60px;">
                    <i class="fas fa-history text-info fs-4"></i>
                </div>
                <h3 class="fw-bold mb-0 text-info">
                    <?php echo $stats['completed']; ?>
                </h3>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <!-- Appointments Table -->
        <div class="col-lg-8" id="appointments">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100">
                <div
                    class="card-header bg-white border-0 p-4 d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="fw-bold mb-0">Patient Appointments</h5>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary btn-sm rounded-pill px-3">Filter</button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($appointments)): ?>
                        <div class="text-center py-5">
                            <img src="https://illustrations.popsy.co/blue/medical-exam.svg" style="max-width: 150px;"
                                class="mb-4">
                            <h6 class="text-muted">No appointments found.</h6>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light text-muted small text-uppercase">
                                    <tr>
                                        <th class="ps-4">Patient</th>
                                        <th>Schedule</th>
                                        <th>Status</th>
                                        <th class="text-end pe-4">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($appointments, 0, 8) as $a): ?>
                                        <tr>
                                            <td class="ps-4">
                                                <div class="fw-bold text-dark">
                                                    <?php echo htmlspecialchars($a['patient_name']); ?>
                                                </div>
                                                <small
                                                    class="text-muted"><?php echo $a['appointment_type'] === 'virtual' ? '<i class="fas fa-video me-1"></i>Virtual' : '<i class="fas fa-hospital me-1"></i>In-Person'; ?></small>
                                            </td>
                                            <td>
                                                <div class="small fw-medium">
                                                    <?php echo date('M d', strtotime($a['scheduled_at'])); ?>
                                                </div>
                                                <div class="text-muted small">
                                                    <?php echo date('h:i A', strtotime($a['scheduled_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $status_map = ['pending' => 'warning', 'confirmed' => 'primary', 'completed' => 'success', 'cancelled' => 'danger'];
                                                $c = $status_map[$a['status']] ?? 'secondary';
                                                ?>
                                                <span
                                                    class="badge rounded-pill bg-<?php echo $c; ?> bg-opacity-10 text-<?php echo $c; ?>"
                                                    style="font-size: 0.7rem;">
                                                    <?php echo ucfirst($a['status']); ?>
                                                </span>
                                            </td>
                                            <td class="text-end pe-4">
                                                <div class="dropdown">
                                                    <button class="btn btn-light btn-sm rounded-circle" type="button"
                                                        data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul
                                                        class="dropdown-menu dropdown-menu-end border-0 shadow-lg rounded-4 p-2">
                                                        <li><a class="dropdown-item rounded-3"
                                                                href="chat.php?customer_id=<?php echo $a['user_id']; ?>"><i
                                                                    class="fas fa-comments me-2"></i>Chat</a></li>
                                                        <li><a class="dropdown-item rounded-3 text-primary fw-bold"
                                                                href="video_call.php?customer_id=<?php echo $a['user_id']; ?>"><i
                                                                    class="fas fa-video me-2"></i>Video Call</a></li>

                                                        <li>
                                                            <hr class="dropdown-divider">
                                                        </li>
                                                        <li>
                                                            <form method="POST" style="padding: 0;">
                                                                <input type="hidden" name="appointment_id"
                                                                    value="<?php echo $a['id']; ?>">
                                                                <input type="hidden" name="update_appointment_status" value="1">
                                                                <?php if ($a['status'] === 'pending'): ?>
                                                                    <button type="submit" name="status" value="confirmed"
                                                                        class="dropdown-item rounded-3 text-primary">Confirm</button>
                                                                <?php endif; ?>
                                                                <?php if ($a['status'] === 'confirmed'): ?>
                                                                    <button type="submit" name="status" value="completed"
                                                                        class="dropdown-item rounded-3 text-success">Complete</button>
                                                                <?php endif; ?>
                                                                <button type="submit" name="status" value="cancelled"
                                                                    class="dropdown-item rounded-3 text-danger">Cancel</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Messaging Sidebar -->
        <div class="col-lg-4" id="chats">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <div class="card-header bg-white border-0 p-4 border-bottom">
                    <h5 class="fw-bold mb-0"><i class="fas fa-comments text-primary me-2"></i>Recent Chats</h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($recent_chats)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comment-slash fs-1 text-muted opacity-25 mb-3"></i>
                            <p class="text-muted small">No recent messages.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_chats as $chat): ?>
                                <a href="chat.php?customer_id=<?php echo $chat['customer_id']; ?>"
                                    class="list-group-item list-group-item-action p-3 border-0 border-bottom">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center text-primary fw-bold"
                                            style="width: 45px; height: 45px;">
                                            <?php echo strtoupper(substr($chat['patient_name'], 0, 1)); ?>
                                        </div>
                                        <div class="flex-grow-1 min-w-0">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <?php echo htmlspecialchars($chat['patient_name']); ?>
                                                </h6>
                                                <div class="d-flex align-items-center gap-2">
                                                    <?php if ($chat['is_read'] == 0 && $chat['sender_type'] == 'customer'): ?>
                                                        <span class="badge rounded-circle p-1 bg-danger"></span>
                                                    <?php endif; ?>
                                                    <a href="video_call.php?customer_id=<?php echo $chat['customer_id']; ?>"
                                                        class="btn btn-sm btn-outline-primary rounded-pill px-2 d-flex align-items-center gap-1"
                                                        style="font-size: 0.65rem;" title="Start Video Call">
                                                        <i class="fas fa-video"></i> Call
                                                    </a>
                                                    <small class="text-muted"
                                                        style="font-size: 0.7rem;"><?php echo date('H:i', strtotime($chat['created_at'])); ?></small>
                                                </div>
                                            </div>
                                            <p
                                                class="text-muted small mb-0 text-truncate <?php echo ($chat['is_read'] == 0 && $chat['sender_type'] == 'customer') ? 'fw-bold text-dark' : ''; ?>">
                                                <?php echo htmlspecialchars($chat['message']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        </div>
                        <div class="p-3 bg-light text-center">
                            <a href="#" class="small fw-bold text-primary text-decoration-none">View All Messages</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Health Insights Card -->
            <div class="card border-0 shadow-sm rounded-4 p-4 bg-primary text-white position-relative overflow-hidden">
                <div style="position: absolute; top: -20px; right: -20px; font-size: 8rem; opacity: 0.1;">
                    <i class="fas fa-chart-line"></i>
                </div>
                <h6 class="fw-bold mb-3">Weekly Performance</h6>
                <div class="d-flex align-items-end gap-3 mb-3">
                    <h2 class="fw-bold mb-0"><?php echo $stats['confirmed'] + $stats['completed']; ?></h2>
                    <span class="small text-white-50 mb-1"><i class="fas fa-arrow-up me-1 text-success"></i>+12% vs last
                        week</span>
                </div>
                <div class="progress" style="height: 6px; background: rgba(255,255,255,0.2);">
                    <div class="progress-bar bg-white" style="width: 75%;"></div>
                </div>
                <small class="d-block mt-3 text-white-50">75% of scheduled slots filled</small>
            </div>
        </div>
    </div>
</div>

<style>
    .doctor-dashboard-hero {
        position: relative;
        overflow: hidden;
    }

    .doctor-dashboard-hero::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        background: url('https://www.transparenttextures.com/patterns/cubes.png');
        opacity: 0.1;
    }

    .hover-lift {
        transition: transform 0.2s ease;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
    }
</style>

<?php include '../includes/footer.php'; ?>