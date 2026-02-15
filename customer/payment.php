<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    header("Location: ../login.php");
    exit();
}

$type = sanitize($_GET['type'] ?? 'order');
$id = (int) ($_GET['order_id'] ?? $_GET['booking_id'] ?? 0);
$method = sanitize($_GET['method'] ?? 'telebirr');

$item = null;
$amount = 0;
$title = "";
$hotel_phone = "";

if ($type === 'flight') {
    $stmt = $pdo->prepare("
        SELECT fb.*, f.price, f.destination, f.airline 
        FROM flight_bookings fb 
        JOIN flights f ON fb.flight_id = f.id 
        WHERE fb.id = ? AND fb.customer_id = ?
    ");
    $stmt->execute([$id, getCurrentUserId()]);
    $item = $stmt->fetch();
    if ($item) {
        $amount = $item['price'] * 1.05; // Including service fee
        $title = "Flight to " . htmlspecialchars($item['destination']);
    }
} else {
    $stmt = $pdo->prepare("
        SELECT o.*, h.name as hotel_name, h.phone as hotel_phone, h.email as hotel_email 
        FROM orders o 
        JOIN hotels h ON o.hotel_id = h.id 
        WHERE o.id = ? AND o.customer_id = ?
    ");
    $stmt->execute([$id, getCurrentUserId()]);
    $item = $stmt->fetch();
    if ($item) {
        $amount = $item['total_amount'];
        $title = "Order #" . str_pad($id, 5, '0', STR_PAD_LEFT);
        $hotel_phone = $item['hotel_phone'] ?? '';
    }
}

if (!$item) {
    die("Item not found");
}

if ($item['payment_status'] === 'paid') {
    if ($type === 'flight') {
        header("Location: track_order.php?flight_booking=$id");
    } else {
        header("Location: track_order.php?id=$id");
    }
    exit();
}

include('../includes/header.php');
?>

<main class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4 text-center p-5">
                <div class="mb-4">
                    <?php if ($method === 'chapa'): ?>
                        <div
                            style="background:linear-gradient(135deg,#7B61FF,#00D4AA);width:80px;height:80px;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto;">
                            <span style="color:#fff;font-size:24px;font-weight:bold;">C</span>
                        </div>
                        <h3 class="fw-bold mt-3">Chapa Payment</h3>
                        <p class="text-muted small">Secure online payment</p>
                    <?php elseif ($method === 'telebirr'): ?>
                        <img src="https://img.icons8.com/color/96/000000/smartphone.png" width="80">
                        <h3 class="fw-bold mt-3">Telebirr Payment</h3>
                    <?php else: ?>
                        <img src="https://img.icons8.com/color/96/000000/bank.png" width="80">
                        <h3 class="fw-bold mt-3">CBE Birr Payment</h3>
                    <?php endif; ?>
                </div>

                <div class="bg-light rounded-4 p-4 mb-4 text-start">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Item</span>
                        <span class="fw-bold"><?php echo $title; ?></span>
                    </div>
                    <?php if ($type === 'flight'): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">PNR Code</span>
                            <span class="fw-bold text-primary"><?php echo htmlspecialchars($item['pnr_code']); ?></span>
                        </div>
                    <?php endif; ?>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Amount</span>
                        <span class="fw-bold text-primary-green">
                            <?php echo number_format($amount); ?> ETB
                        </span>
                    </div>
                    <?php if (!empty($hotel_phone)): ?>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Restaurant Phone</span>
                            <span class="fw-bold">
                                <a href="tel:<?php echo htmlspecialchars($hotel_phone); ?>" class="text-decoration-none">
                                    <i class="fas fa-phone-alt text-success me-1"></i>
                                    <?php echo htmlspecialchars($hotel_phone); ?>
                                </a>
                            </span>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if ($method === 'chapa'): ?>
                    <!-- Chapa Payment Flow -->
                    <div id="chapaPayment">
                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                                <i class="fas fa-shield-alt text-success"></i>
                                <small class="text-muted">Secured by Chapa Payment Gateway</small>
                            </div>
                        </div>
                        <button class="btn rounded-pill btn-lg w-100 py-3 mb-3 text-white"
                            style="background:linear-gradient(135deg,#7B61FF,#00D4AA);border:none;"
                            onclick="initiateChapaPayment()">
                            <i class="fas fa-credit-card me-2"></i> Pay with Chapa
                        </button>
                        <p class="text-muted small mb-3">
                            <i class="fas fa-info-circle me-1"></i>
                            You will be redirected to Chapa's secure checkout page
                        </p>
                        <div class="d-flex justify-content-center gap-3 mb-3">
                            <img src="https://img.icons8.com/color/32/mastercard.png" title="Mastercard">
                            <img src="https://img.icons8.com/color/32/visa.png" title="VISA">
                            <img src="https://img.icons8.com/color/32/amex.png" title="Amex">
                        </div>
                        <button class="btn btn-outline-secondary rounded-pill w-100" onclick="window.history.back()">
                            Cancel
                        </button>
                    </div>
                <?php else: ?>
                    <!-- Telebirr / CBE Birr Flow -->
                    <div id="paymentProcess">
                        <div class="mb-4">
                            <p class="text-muted">Please confirm payment on your mobile device...</p>
                            <div class="spinner-border text-primary-green" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <button class="btn btn-primary-green rounded-pill btn-lg w-100 py-3 mb-3"
                            onclick="simulatePayment()">
                            Confirm Payment
                        </button>
                        <button class="btn btn-outline-secondary rounded-pill w-100" onclick="window.history.back()">
                            Cancel
                        </button>
                    </div>
                <?php endif; ?>

                <div id="paymentSuccess" style="display: none;">
                    <div class="text-success mb-4">
                        <i class="fas fa-check-circle" style="font-size: 4rem;"></i>
                    </div>
                    <h4 class="fw-bold">Payment Successful!</h4>
                    <p class="text-muted mb-2">Your <?php echo $type === 'flight' ? 'ticket' : 'order'; ?> has been
                        confirmed.</p>
                    <?php if (!empty($hotel_phone)): ?>
                        <p class="mb-4">
                            <a href="tel:<?php echo htmlspecialchars($hotel_phone); ?>"
                                class="text-decoration-none text-success">
                                <i class="fas fa-phone-alt me-1"></i> Call Restaurant:
                                <?php echo htmlspecialchars($hotel_phone); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                    <a href="track_order.php?<?php echo $type === 'flight' ? "flight_booking=$id" : "id=$id"; ?>"
                        class="btn btn-primary-green rounded-pill w-100 py-3">
                        Track Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    const apiUrl = '<?php echo BASE_URL; ?>/api.php';

    function simulatePayment() {
        const processDiv = document.getElementById('paymentProcess');
        const successDiv = document.getElementById('paymentSuccess');

        processDiv.style.opacity = '0.5';
        processDiv.querySelector('button').disabled = true;

        const action = '<?php echo $type === 'flight' ? 'update_flight_payment' : 'update_payment_status'; ?>';
        const idParam = '<?php echo $type === 'flight' ? 'booking_id' : 'order_id'; ?>';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&${idParam}=<?php echo $id; ?>&status=paid&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
            .then(res => {
                if (!res.ok) throw new Error('Server error: ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    processDiv.style.display = 'none';
                    successDiv.style.display = 'block';
                } else {
                    alert(data.message);
                    processDiv.style.opacity = '1';
                    processDiv.querySelector('button').disabled = false;
                }
            })
            .catch(err => {
                console.error('Payment error:', err);
                alert('Payment failed. Please try again.');
                processDiv.style.opacity = '1';
                processDiv.querySelector('button').disabled = false;
            });
    }

    function initiateChapaPayment() {
        const btn = event.target.closest('button');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Connecting to Chapa...';

        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=initiate_chapa_payment&order_id=<?php echo $id; ?>&payment_type=<?php echo $type; ?>&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
            .then(res => {
                if (!res.ok) throw new Error('Server error: ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success && data.checkout_url) {
                    // Redirect to Chapa checkout
                    window.location.href = data.checkout_url;
                } else {
                    // Fallback: simulate payment for demo purposes
                    alert('Chapa integration requires an API key. For now, simulating payment success.');
                    simulateChapaSuccess();
                }
            })
            .catch(err => {
                // Fallback: simulate payment for demo
                simulateChapaSuccess();
            });
    }

    function simulateChapaSuccess() {
        // Update payment status first
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_payment_status&order_id=<?php echo $id; ?>&status=paid&csrf_token=<?php echo generateCSRFToken(); ?>`
        })
            .then(res => {
                if (!res.ok) throw new Error('Server error: ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    const chapaDiv = document.getElementById('chapaPayment');
                    const successDiv = document.getElementById('paymentSuccess');
                    if (chapaDiv) chapaDiv.style.display = 'none';
                    successDiv.style.display = 'block';
                }
            });
    }
</script>

<?php include('../includes/footer.php'); ?>