<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();
$user_id = getCurrentUserId();
$request_id = intval($_GET['request_id'] ?? 0);

if (!$request_id) {
    header("Location: rental_requests.php");
    exit();
}

// Fetch request and verify ownership + check if approved
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.user_id as owner_id, l.price,
           o.full_name as owner_name
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users o ON l.user_id = o.id
    WHERE rr.id = ? AND rr.customer_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request || $request['status'] !== 'approved') {
    redirectWithMessage('rental_requests.php', 'warning', 'Invalid request or payment not yet authorized.');
}

// Fetch owner payment methods
$stmt = $pdo->prepare("SELECT * FROM owner_payment_methods WHERE user_id = ? AND is_active = 1");
$stmt->execute([$request['owner_id']]);
$payment_methods = $stmt->fetchAll();

// Handle proof submission
$success = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_proof'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $method_id = intval($_POST['payment_method_id']);
        $ref_number = sanitize($_POST['reference_number']);
        $amount = floatval($_POST['amount']);
        $note = sanitize($_POST['note']);
        $proof_path = null;

        if (isset($_FILES['proof_image']) && $_FILES['proof_image']['error'] === UPLOAD_ERR_OK) {
            $proof_path = uploadFile('proof_image', 'payments/proofs/');
        }

        try {
            // Check if reference number already exists
            $stmt_check = $pdo->prepare("SELECT id FROM rental_payment_proofs WHERE reference_number = ? AND status != 'rejected'");
            $stmt_check->execute([$ref_number]);
            if ($stmt_check->fetch()) {
                $error = "This reference number has already been used for another payment. Please check your transaction details.";
            } else {
                $stmt = $pdo->prepare("INSERT INTO rental_payment_proofs (request_id, customer_id, payment_method_id, reference_number, proof_image_path, amount, note) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$request_id, $user_id, $method_id, $ref_number, $proof_path, $amount, $note]);
                $success = "Payment proof submitted successfully! The owner will review it shortly.";
            }
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Check for existing submissions
$stmt = $pdo->prepare("SELECT * FROM rental_payment_proofs WHERE request_id = ? ORDER BY submitted_at DESC");
$stmt->execute([$request_id]);
$proofs = $stmt->fetchAll();

include '../includes/header.php';
?>
<div class="container py-5" style="max-width: 900px; font-family: 'Poppins', sans-serif;">
    <div class="mb-5 text-center">
        <h2 class="fw-bold mb-1">Secure Rental Payment</h2>
        <p class="text-muted">Pay for "<?php echo htmlspecialchars($request['listing_title']); ?>" using the owner's preferred methods.</p>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><i class="fas fa-check-circle me-2"></i><?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Payment Methods -->
        <div class="col-md-6">
            <h5 class="fw-bold mb-3"><i class="fas fa-wallet text-primary-green me-2"></i>1. Choose Method & Pay</h5>
            <?php if (empty($payment_methods)): ?>
                <div class="alert alert-warning rounded-4">
                    <i class="fas fa-info-circle me-2"></i> The owner hasn't set up online payment methods yet. Please contact them via chat for payment details.
                    <div class="mt-3">
                        <a href="rental_chat.php?request_id=<?php echo $request_id; ?>" class="btn btn-warning btn-sm rounded-pill fw-bold">Open Chat</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="accordion shadow-sm rounded-4 overflow-hidden border-0" id="paymentMethods">
                    <?php foreach ($payment_methods as $i => $m): ?>
                        <div class="accordion-item border-0 border-bottom">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $i !== 0 ? 'collapsed' : ''; ?> fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#method<?php echo $m['id']; ?>">
                                    <span class="text-uppercase me-2"><?php echo $m['method_type']; ?></span>
                                    <small class="text-muted fw-normal"><?php echo htmlspecialchars($m['account_number']); ?></small>
                                </button>
                            </h2>
                            <div id="method<?php echo $m['id']; ?>" class="accordion-collapse collapse <?php echo $i === 0 ? 'show' : ''; ?>" data-bs-parent="#paymentMethods">
                                <div class="accordion-body bg-light">
                                    <div class="text-center mb-3">
                                        <?php if ($m['qr_image_path']): ?>
                                            <p class="small fw-bold text-uppercase mb-2">Scan QR to Pay</p>
                                            <img src="<?php echo BASE_URL . '/' . $m['qr_image_path']; ?>" class="img-fluid rounded shadow-sm mb-3" style="max-width: 180px;">
                                        <?php endif; ?>
                                        <div class="bg-white p-3 rounded-3 shadow-sm text-start">
                                            <p class="mb-1 small"><strong>Account Name:</strong> <?php echo htmlspecialchars($m['account_name']); ?></p>
                                            <p class="mb-1 small"><strong>Account Number:</strong> <?php echo htmlspecialchars($m['account_number']); ?></p>
                                            <?php if ($m['instructions']): ?>
                                                <hr class="my-2">
                                                <p class="mb-0 small text-muted"><em><?php echo nl2br(htmlspecialchars($m['instructions'])); ?></em></p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <p class="small text-muted text-center mb-0">Total Due: <strong class="text-dark"><?php echo number_format($request['price']); ?> ETB</strong></p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <!-- Submission Form -->
        <div class="col-md-6">
            <h5 class="fw-bold mb-3"><i class="fas fa-file-invoice-dollar text-primary-green me-2"></i>2. Submit Proof</h5>
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <form method="POST" enctype="multipart/form-data">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="submit_proof" value="1">
                    
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Select Method Used</label>
                        <select name="payment_method_id" class="form-select" required>
                            <?php foreach ($payment_methods as $m): ?>
                                <option value="<?php echo $m['id']; ?>"><?php echo strtoupper($m['method_type']) . ' - ' . $m['account_number']; ?></option>
                            <?php endforeach; ?>
                            <option value="0">Other / Offline</option>
                        </select>
                    </div>

                    <div class="row g-2">
                        <div class="col-8">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Reference Number</label>
                                <input type="text" name="reference_number" class="form-control" placeholder="Transaction ID" required>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="mb-3">
                                <label class="form-label small fw-bold">Amount Payed</label>
                                <input type="number" name="amount" class="form-control" value="<?php echo (float)$request['price']; ?>" step="0.01" required>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Upload Screenshot / Receipt</label>
                        <input type="file" name="proof_image" class="form-control" accept="image/*" required>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-bold">Additional Note</label>
                        <textarea name="note" class="form-control" rows="2" placeholder="Any details for the owner..."></textarea>
                    </div>

                    <button type="submit" class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow">
                        Submit Payment Proof
                    </button>
                </form>
            </div>

            <?php if (!empty($proofs)): ?>
                <div class="mt-4">
                    <h6 class="fw-bold mb-3">Recent Submissions</h6>
                    <?php foreach ($proofs as $p): ?>
                        <div class="bg-white p-3 rounded-4 shadow-sm mb-2 d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted d-block"><?php echo date('M d, Y', strtotime($p['submitted_at'])); ?></small>
                                <span class="fw-bold">Ref: <?php echo htmlspecialchars($p['reference_number']); ?></span>
                            </div>
                            <?php 
                                $pBadge = 'bg-warning-subtle text-warning';
                                if ($p['status'] === 'confirmed') $pBadge = 'bg-success-subtle text-success';
                                if ($p['status'] === 'rejected') $pBadge = 'bg-danger-subtle text-danger';
                            ?>
                            <span class="badge <?php echo $pBadge; ?> rounded-pill px-3"><?php echo ucfirst($p['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .btn-primary-green { background: #1B5E20; color: #fff; border: none; }
    .btn-primary-green:hover { background: #2E7D32; color: #fff; }
    .accordion-button:not(.collapsed) { background-color: rgba(27, 94, 32, 0.05); color: #1B5E20; }
    .accordion-button:focus { box-shadow: none; }
</style>
<?php include '../includes/footer.php'; ?>