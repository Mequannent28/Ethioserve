<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('hotel');

$user_id = getCurrentUserId();

$stmt = $pdo->prepare("SELECT * FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header("Location: dashboard.php");
    exit();
}

$hotel_id = $hotel['id'];

// Handle booking status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_booking_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $booking_id = (int) $_POST['booking_id'];
        $new_status = sanitize($_POST['status']);
        $room_id = isset($_POST['room_id']) ? (int) $_POST['room_id'] : null;

        if (in_array($new_status, ['pending', 'approved', 'cancelled'])) {
            if ($new_status === 'approved' && $room_id) {
                // Assign room and update room status
                $stmt = $pdo->prepare("UPDATE bookings SET status = ?, room_id = ? WHERE id = ? AND hotel_id = ?");
                $stmt->execute([$new_status, $room_id, $booking_id, $hotel_id]);

                $stmt = $pdo->prepare("UPDATE hotel_rooms SET status = 'occupied' WHERE id = ? AND hotel_id = ?");
                $stmt->execute([$room_id, $hotel_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ? AND hotel_id = ?");
                $stmt->execute([$new_status, $booking_id, $hotel_id]);

                // If cancelling, maybe free the room?
                if ($new_status === 'cancelled') {
                    $stmt = $pdo->prepare("SELECT room_id FROM bookings WHERE id = ?");
                    $stmt->execute([$booking_id]);
                    $old_room = $stmt->fetchColumn();
                    if ($old_room) {
                        $stmt = $pdo->prepare("UPDATE hotel_rooms SET status = 'available' WHERE id = ?");
                        $stmt->execute([$old_room]);
                    }
                }
            }
            redirectWithMessage('bookings.php', 'success', 'Booking status updated');
        }
    }
}
// Filter
$status_filter = sanitize($_GET['status'] ?? '');
$where_clause = "WHERE b.hotel_id = ?";
$params = [$hotel_id];
if (!empty($status_filter) && in_array($status_filter, ['pending', 'approved', 'cancelled'])) {
    $where_clause .= " AND b.status = ?";
    $params[] = $status_filter;
}

$stmt = $pdo->prepare("
    SELECT b.*, u.full_name as customer_name, u.phone as customer_phone, u.email as customer_email,
           hr.room_number, hr.room_type as assigned_room_type
    FROM bookings b
    JOIN users u ON b.customer_id = u.id
    LEFT JOIN hotel_rooms hr ON b.room_id = hr.id
    $where_clause
    ORDER BY b.booking_date ASC, b.booking_time ASC
");
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// Get available rooms for assignment
$stmt = $pdo->prepare("SELECT * FROM hotel_rooms WHERE hotel_id = ? AND status = 'available' ORDER BY room_number");
$stmt->execute([$hotel_id]);
$available_rooms = $stmt->fetchAll();

// Status counts
$counts = [];
foreach (['pending', 'approved', 'cancelled'] as $s) {
    $r = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND status = ?");
    $r->execute([$hotel_id, $s]);
    $counts[$s] = $r->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookings - Hotel Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f0f2f5;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
        }

        /* sidebar_hotel.php already applies padding-left:280px to body */
        .main-content {
            padding: 30px;
            background-color: #f0f2f5;
            min-height: 100vh;
            width: 100%;
        }

        @media (max-width: 991px) {
            .main-content {
                padding: 15px;
            }
        }

        .booking-table th {
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #888;
            font-weight: 700;
            background: #f8f9fa;
            border: none;
            padding: 12px 16px;
        }

        .booking-table td {
            vertical-align: middle;
            padding: 13px 16px;
            border-top: 1px solid #f2f2f2;
        }

        .booking-table tbody tr:hover td {
            background: #fafafa;
        }

        .booking-type-icon {
            width: 36px;
            height: 36px;
            border-radius: 9px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.95rem;
        }

        .filter-tab {
            border-radius: 50px;
            padding: 6px 18px;
            font-size: 0.82rem;
            font-weight: 600;
            border: 2px solid #dee2e6;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
        }

        .filter-tab:hover,
        .filter-tab.active {
            border-color: #1B5E20;
            background: #1B5E20;
            color: #fff !important;
        }

        .count-pill {
            font-size: 0.68rem;
            padding: 2px 7px;
            border-radius: 50px;
            font-weight: 700;
            display: inline-block;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_hotel.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <!-- Page Header -->
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                <div>
                    <h2 class="fw-bold mb-1">Booking Requests</h2>
                    <p class="text-muted mb-0 small">
                        <i class="fas fa-hotel me-1"></i> <?php echo htmlspecialchars($hotel['name']); ?>
                    </p>
                </div>
                <!-- Filter Tabs -->
                <div class="d-flex gap-2 flex-wrap">
                    <a href="bookings.php" class="filter-tab <?php echo $status_filter === '' ? 'active' : ''; ?>">
                        All <span
                            class="count-pill bg-secondary text-white ms-1"><?php echo array_sum($counts); ?></span>
                    </a>
                    <a href="?status=pending"
                        class="filter-tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">
                        Pending <span
                            class="count-pill bg-warning text-dark ms-1"><?php echo $counts['pending']; ?></span>
                    </a>
                    <a href="?status=approved"
                        class="filter-tab <?php echo $status_filter === 'approved' ? 'active' : ''; ?>">
                        Approved <span
                            class="count-pill bg-success text-white ms-1"><?php echo $counts['approved']; ?></span>
                    </a>
                    <a href="?status=cancelled"
                        class="filter-tab <?php echo $status_filter === 'cancelled' ? 'active' : ''; ?>">
                        Cancelled <span
                            class="count-pill bg-danger text-white ms-1"><?php echo $counts['cancelled']; ?></span>
                    </a>
                </div>
            </div>

            <!-- Bookings Table -->
            <div class="card border-0 shadow-sm">
                <?php if (empty($bookings)): ?>
                    <div class="p-5 text-center">
                        <i class="fas fa-calendar-times text-muted mb-3" style="font-size:3.5rem;display:block;"></i>
                        <h5 class="text-muted">No bookings found</h5>
                        <p class="text-muted small">Booking requests will appear here when customers make them.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table booking-table mb-0">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Customer</th>
                                    <th>Contact</th>
                                    <th>Booking Date &amp; Time</th>
                                    <th>Requested On</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($bookings as $booking):
                                    $typeIcon = $booking['booking_type'] === 'room' ? 'bed' : ($booking['booking_type'] === 'table' ? 'utensils' : 'building');
                                    $typeBg = $booking['booking_type'] === 'room' ? '#e8f5e9' : ($booking['booking_type'] === 'table' ? '#fff8e1' : '#e3f2fd');
                                    $typeColor = $booking['booking_type'] === 'room' ? '#2e7d32' : ($booking['booking_type'] === 'table' ? '#f57f17' : '#1565c0');
                                    ?>
                                    <tr>
                                        <td><strong
                                                class="text-primary-green">#<?php echo str_pad($booking['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                        </td>
                                        <td>
                                            <span class="booking-type-icon"
                                                style="background:<?php echo $typeBg; ?>;color:<?php echo $typeColor; ?>">
                                                <i class="fas fa-<?php echo $typeIcon; ?>"></i>
                                            </span>
                                            <span
                                                class="ms-1 small fw-bold"><?php echo ucfirst($booking['booking_type']); ?></span>
                                            <?php if ($booking['room_number']): ?>
                                                <div class="mt-1 small">
                                                    <span class="badge bg-primary bg-opacity-10 text-primary">
                                                        <i class="fas fa-key me-1"></i> Room
                                                        <?php echo $booking['room_number']; ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?php echo htmlspecialchars($booking['customer_name']); ?>
                                            </div>
                                        </td>
                                        <td><small
                                                class="text-muted"><?php echo htmlspecialchars($booking['customer_phone'] ?: $booking['customer_email']); ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold small">
                                                <?php echo date('M d, Y', strtotime($booking['booking_date'])); ?>
                                            </div>
                                            <small
                                                class="text-muted"><?php echo date('h:i A', strtotime($booking['booking_time'])); ?></small>
                                        </td>
                                        <td><small
                                                class="text-muted"><?php echo date('M d, Y', strtotime($booking['created_at'])); ?></small>
                                        </td>
                                        <td><?php echo getStatusBadge($booking['status']); ?></td>
                                        <td>
                                            <?php if ($booking['status'] === 'pending'): ?>
                                                <form method="POST" class="d-flex gap-1">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <?php if ($booking['booking_type'] === 'room'): ?>
                                                        <button type="button" class="btn btn-sm btn-success rounded-pill px-3"
                                                            data-bs-toggle="modal" data-bs-target="#assignRoomModal"
                                                            data-booking-id="<?php echo $booking['id']; ?>">
                                                            <i class="fas fa-check"></i> Approve & Assign
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" name="status" value="approved"
                                                            class="btn btn-sm btn-success rounded-pill px-3">
                                                            <i class="fas fa-check"></i> Approve
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="submit" name="status" value="cancelled"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-2">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </form>
                                            <?php elseif ($booking['status'] === 'approved'): ?>
                                                <form method="POST">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                                    <input type="hidden" name="update_booking_status" value="1">
                                                    <button type="submit" name="status" value="cancelled"
                                                        class="btn btn-sm btn-outline-danger rounded-pill px-3">
                                                        <i class="fas fa-times me-1"></i> Cancel
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted small"><i class="fas fa-ban me-1"></i>Cancelled</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white border-0 text-muted small py-3 px-4">
                        Showing <strong><?php echo count($bookings); ?></strong> booking(s)
                        <?php if ($status_filter): ?> — filtered by
                            <strong><?php echo ucfirst($status_filter); ?></strong><?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- Assign Room Modal -->
    <div class="modal fade" id="assignRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form method="POST" class="modal-content border-0 shadow-lg rounded-4">
                <?php echo csrfField(); ?>
                <input type="hidden" name="booking_id" id="modal_booking_id">
                <input type="hidden" name="update_booking_status" value="1">
                <input type="hidden" name="status" value="approved">

                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Approve & Assign Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small">Select an available room to assign to this booking request.</p>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Available Rooms</label>
                        <select name="room_id" class="form-select rounded-3" required>
                            <option value="">-- Choose a Room --</option>
                            <?php foreach ($available_rooms as $room): ?>
                                <option value="<?php echo $room['id']; ?>">
                                    Room
                                    <?php echo htmlspecialchars($room['room_number']); ?> (
                                    <?php echo htmlspecialchars($room['room_type']); ?>) -
                                    <?php echo number_format($room['price_per_night']); ?> ETB
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success rounded-pill px-4 shadow-sm">Confirm Approval</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('assignRoomModal')?.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const bookingId = button.getAttribute('data-booking-id');
            document.getElementById('modal_booking_id').value = bookingId;
        });
    </script>
</body>

</html>