<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_bus.php', 'danger', 'Company ID is required');
}

$stmt = $pdo->prepare("SELECT b.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone, u.id as user_id 
                     FROM transport_companies b 
                     JOIN users u ON b.user_id = u.id 
                     WHERE b.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('manage_bus.php', 'danger', 'Company not found');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['company_name']);
        $location = sanitize($_POST['location']);
        $status = sanitize($_POST['status']);
        $description = $_POST['description'];

        $owner_name = sanitize($_POST['owner_name']);
        $owner_email = sanitize($_POST['owner_email']);
        $owner_phone = sanitize($_POST['owner_phone']);

        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE transport_companies SET company_name = ?, address = ?, status = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $location, $status, $description, $id]);
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$owner_name, $owner_email, $owner_phone, $item['user_id']]);
            $pdo->commit();
            redirectWithMessage('view_bus.php?id=' . $id, 'success', 'Bus company updated successfully');
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Bus Company</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .edit-container {
            max-width: 900px;
            margin: 40px auto;
        }

        .form-section {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 30px;
        }

        .section-title {
            border-left: 4px solid #0D47A1;
            padding-left: 15px;
            margin-bottom: 25px;
            font-weight: 700;
        }
    </style>
</head>

<body class="bg-light">
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="container-fluid py-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold mb-0">Edit Bus Company</h2>
                    <a href="view_bus.php?id=<?php echo $id; ?>"
                        class="btn btn-outline-secondary rounded-pill">Cancel</a>
                </div>
                <form method="POST" class="edit-container">
                    <?php echo csrfField(); ?>
                    <div class="form-section">
                        <h5 class="section-title">Business Information</h5>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label small fw-bold">Company Name</label><input
                                    type="text" name="company_name"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['company_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Primary Location</label><input
                                    type="text" name="location" class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['address'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Status</label>
                                <select name="status" class="form-select rounded-pill bg-light border-0 px-4">
                                    <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>>
                                        Pending</option>
                                    <option value="approved" <?php echo $item['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $item['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12"><label class="form-label small fw-bold">Company
                                    Description</label><textarea name="description" rows="4"
                                    class="form-control rounded-4 bg-light border-0 px-4 py-3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="form-section">
                        <h5 class="section-title">Owner Details</h5>
                        <div class="row g-3">
                            <div class="col-12"><label class="form-label small fw-bold">Full Name</label><input
                                    type="text" name="owner_name"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_name']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Email</label><input
                                    type="email" name="owner_email"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_email']); ?>" required></div>
                            <div class="col-md-6"><label class="form-label small fw-bold">Phone</label><input
                                    type="text" name="owner_phone"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_phone']); ?>"></div>
                        </div>
                    </div>
                    <div class="text-end mb-5">
                        <button type="submit" name="save_changes"
                            class="btn btn-primary rounded-pill px-5 fw-bold shadow-sm">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>

</html>