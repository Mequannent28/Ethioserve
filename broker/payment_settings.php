<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$success = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_payment'])) {
    if (verifyCSRFToken($_POST['csrf_token'])) {
        $method_type = sanitize($_POST['method_type']);
        $account_name = sanitize($_POST['account_name']);
        $account_number = sanitize($_POST['account_number']);
        $instructions = sanitize($_POST['instructions']);
        $qr_image_path = null;

        // Handle QR Upload
        if (isset($_FILES['qr_code']) && $_FILES['qr_code']['error'] === UPLOAD_ERR_OK) {
            $qr_image_path = uploadFile('qr_code', 'payments/qr/');
        }

        try {
            // Check if exists
            $stmt = $pdo->prepare("SELECT id, qr_image_path FROM owner_payment_methods WHERE user_id = ? AND method_type = ?");
            $stmt->execute([$user_id, $method_type]);
            $existing = $stmt->fetch();

            if ($existing) {
                $final_qr = $qr_image_path ?: $existing['qr_image_path'];
                $stmt = $pdo->prepare("UPDATE owner_payment_methods SET account_name = ?, account_number = ?, qr_image_path = ?, instructions = ? WHERE id = ?");
                $stmt->execute([$account_name, $account_number, $final_qr, $instructions, $existing['id']]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO owner_payment_methods (user_id, method_type, account_name, account_number, qr_image_path, instructions) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $method_type, $account_name, $account_number, $qr_image_path, $instructions]);
            }
            $success = "Payment method saved successfully!";
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch existing methods
$stmt = $pdo->prepare("SELECT * FROM owner_payment_methods WHERE user_id = ?");
$stmt->execute([$user_id]);
$methods = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Settings - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { margin-left: 260px; padding: 32px; width: 100%; min-height: 100vh; }
        .payment-card { border: none; border-radius: 16px; transition: 0.3s; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .qr-preview { width: 150px; height: 150px; object-fit: contain; border: 1px dashed #ccc; border-radius: 8px; padding: 10px; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>
        <div class="main-content">
            <div class="mb-4">
                <h2 class="fw-bold mb-1">Payment Settings</h2>
                <p class="text-muted">Setup your QR codes and bank details for rental payments.</p>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success rounded-4 border-0 shadow-sm mb-4"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="row g-4">
                <div class="col-lg-8">
                    <div class="payment-card p-4">
                        <form method="POST" enctype="multipart/form-data">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="save_payment" value="1">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Method</label>
                                    <select name="method_type" class="form-select rounded-3" required>
                                        <option value="telebirr">Telebirr</option>
                                        <option value="cbe_birr">CBE Birr</option>
                                        <option value="bank_transfer">Commercial Bank of Ethiopia (CBE)</option>
                                        <option value="awash">Awash Bank</option>
                                        <option value="dashen">Dashen Bank</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Account Name</label>
                                    <input type="text" name="account_name" class="form-control rounded-3" placeholder="Full name on account" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Account Number / Phone</label>
                                    <input type="text" name="account_number" class="form-control rounded-3" placeholder="Number or phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Upload QR Code (Highly Recommended)</label>
                                    <input type="file" name="qr_code" class="form-control rounded-3" accept="image/*">
                                    <small class="text-muted">Customers can scan this to pay instantly.</small>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Special Instructions</label>
                                    <textarea name="instructions" class="form-control rounded-3" rows="3" placeholder="e.g. Please send the reference number after payment."></textarea>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary-green px-5 py-3 rounded-pill fw-bold mt-4 shadow">
                                <i class="fas fa-save me-2"></i>Save Payment Method
                            </button>
                        </form>
                    </div>
                </div>

                <div class="col-lg-4">
                    <h5 class="fw-bold mb-3">Your Saved Methods</h5>
                    <?php if (empty($methods)): ?>
                        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                            <i class="fas fa-wallet fs-1 opacity-25 mb-3"></i>
                            <p class="text-muted px-4 small">No payment methods setup yet. Add at least one to receive payments from customers.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($methods as $m): ?>
                            <div class="payment-card p-3 mb-3 border-start border-4 border-success">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="badge bg-success-subtle text-success text-uppercase small mb-2"><?php echo $m['method_type']; ?></span>
                                        <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($m['account_name']); ?></h6>
                                        <p class="mb-0 small text-muted"><?php echo htmlspecialchars($m['account_number']); ?></p>
                                    </div>
                                    <?php if ($m['qr_image_path']): ?>
                                        <i class="fas fa-qrcode fs-4 text-success cursor-pointer" title="View QR" onclick="window.open('../<?php echo $m['qr_image_path']; ?>')"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
