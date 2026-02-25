<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$users = $pdo->query("SELECT id, full_name, username FROM users ORDER BY full_name")->fetchAll();
$schedules = $pdo->query("SELECT s.id, s.departure_time, r.origin, r.destination, s.price, tc.company_name
                         FROM schedules s
                         JOIN routes r ON s.route_id = r.id
                         JOIN buses b ON s.bus_id = b.id
                         JOIN transport_companies tc ON b.company_id = tc.id
                         WHERE s.departure_time >= NOW()
                         ORDER BY s.departure_time ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_booking'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $customer_id = (int) $_POST['customer_id'];
        $schedule_id = (int) $_POST['schedule_id'];
        $travel_date = $_POST['travel_date'];
        $num_passengers = (int) $_POST['num_passengers'];
        $total_amount = (float) $_POST['total_amount'];
        $status = sanitize($_POST['status']);
        $payment_status = sanitize($_POST['payment_status']);
        $passenger_names = sanitize($_POST['passenger_names']);

        $booking_ref = 'BUS-' . strtoupper(substr(uniqid(), -6));

        try {
            $stmt = $pdo->prepare("INSERT INTO bus_bookings 
                (customer_id, schedule_id, travel_date, num_passengers, passenger_names, total_amount, status, payment_status, booking_reference) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$customer_id, $schedule_id, $travel_date, $num_passengers, $passenger_names, $total_amount, $status, $payment_status, $booking_ref]);

            redirectWithMessage('manage_transport.php', 'success', 'Bus booking added successfully! Ref: ' . $booking_ref);
        } catch (Exception $e) {
            $error = "Failed to add booking: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Bus Booking - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f4f6f9;
        }

        .main-content {
            margin-left: 260px;
            padding: 40px;
        }

        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .form-label {
            font-weight: 600;
            color: #444;
        }

        .btn-primary {
            background-color: #1565C0;
            border: none;
            border-radius: 50px;
            padding: 12px 30px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex align-items-center gap-3 mb-4">
                    <a href="manage_transport.php" class="btn btn-white rounded-circle shadow-sm"
                        style="width:45px; height:45px; display:flex; align-items:center; justify-content:center;">
                        <i class="fas fa-arrow-left"></i>
                    </a>
                    <h2 class="fw-bold mb-0">Add Bus Booking</h2>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger rounded-4">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="card p-4 p-md-5">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="add_booking" value="1">

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Customer *</label>
                            <select name="customer_id" class="form-select rounded-pill px-4 py-2" required>
                                <option value="">-- Select Customer --</option>
                                <?php foreach ($users as $user): ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['full_name']); ?> (@
                                        <?php echo htmlspecialchars($user['username']); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Schedule *</label>
                            <select name="schedule_id" class="form-select rounded-pill px-4 py-2" id="schedule_select"
                                required>
                                <option value="">-- Select Schedule --</option>
                                <?php foreach ($schedules as $s): ?>
                                    <option value="<?php echo $s['id']; ?>" data-price="<?php echo $s['price']; ?>">
                                        <?php echo htmlspecialchars($s['company_name']); ?>:
                                        <?php echo htmlspecialchars($s['origin']); ?> ->
                                        <?php echo htmlspecialchars($s['destination']); ?> (
                                        <?php echo date('M d, H:i', strtotime($s['departure_time'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Travel Date *</label>
                            <input type="date" name="travel_date" class="form-control rounded-pill px-4 py-2" required
                                value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Number of Passengers *</label>
                            <input type="number" name="num_passengers" id="num_passengers"
                                class="form-control rounded-pill px-4 py-2" required min="1" value="1">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Total Amount (ETB) *</label>
                            <input type="number" step="0.01" name="total_amount" id="total_amount"
                                class="form-control rounded-pill px-4 py-2" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Status</label>
                            <select name="payment_status" class="form-select rounded-pill px-4 py-2">
                                <option value="pending">Pending</option>
                                <option value="paid">Paid</option>
                                <option value="failed">Failed</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Booking Status</label>
                            <select name="status" class="form-select rounded-pill px-4 py-2">
                                <option value="pending">Pending</option>
                                <option value="confirmed">Confirmed</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label">Passenger Names (for manifest)</label>
                            <textarea name="passenger_names" class="form-control rounded-4 px-4 py-3" rows="3"
                                placeholder="Enter comma separated names..."></textarea>
                        </div>

                        <div class="col-12 mt-5">
                            <button type="submit" class="btn btn-primary px-5">
                                <i class="fas fa-check-circle me-2"></i>Create Booking
                            </button>
                            <a href="manage_transport.php" class="btn btn-light rounded-pill px-4 ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script>
        document.getElementById('schedule_select').addEventListener('change', updatePrice);
        document.getElementById('num_passengers').addEventListener('input', updatePrice);

        function updatePrice() {
            const select = document.getElementById('schedule_select');
            const num = document.getElementById('num_passengers').value || 1;
            const option = select.options[select.selectedIndex];
            if (option.dataset.price) {
                document.getElementById('total_amount').value = (option.dataset.price * num).toFixed(2);
            }
        }
    </script>
</body>

</html>