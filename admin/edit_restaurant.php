<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_restaurants.php', 'danger', 'Restaurant ID is required');
}

// Fetch Restaurant and Owner info
$stmt = $pdo->prepare("SELECT r.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone, u.id as user_id 
                     FROM restaurants r 
                     JOIN users u ON r.user_id = u.id 
                     WHERE r.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('manage_restaurants.php', 'danger', 'Restaurant not found');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $cuisine = sanitize($_POST['cuisine_type']);
        $rating = floatval($_POST['rating'] ?? 0);
        $status = sanitize($_POST['status']);
        $description = $_POST['description'];

        $owner_name = sanitize($_POST['owner_name']);
        $owner_email = sanitize($_POST['owner_email']);
        $owner_phone = sanitize($_POST['owner_phone']);

        try {
            $pdo->beginTransaction();

            // Update Restaurant
            $stmt = $pdo->prepare("UPDATE restaurants SET name = ?, location = ?, cuisine_type = ?, rating = ?, status = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $location, $cuisine, $rating, $status, $description, $id]);

            // Update User
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$owner_name, $owner_email, $owner_phone, $item['user_id']]);

            $pdo->commit();
            redirectWithMessage('view_restaurant.php?id=' . $id, 'success', 'Restaurant updated successfully');
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
    <title>Edit Restaurant -
        <?php echo htmlspecialchars($item['name']); ?>
    </title>
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
            border-left: 4px solid var(--primary-green);
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
                    <div>
                        <h2 class="fw-bold mb-0">Edit Restaurant</h2>
                        <p class="text-muted">Update profile for <strong>
                                <?php echo htmlspecialchars($item['name']); ?>
                            </strong></p>
                    </div>
                    <a href="view_restaurant.php?id=<?php echo $id; ?>"
                        class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to View
                    </a>
                </div>

                <?php if (isset($error)): ?>
                    <div class="alert alert-danger rounded-4 border-0">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="edit-container">
                    <?php echo csrfField(); ?>

                    <div class="form-section">
                        <h5 class="section-title">Restaurant Details</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Restaurant Name</label>
                                <input type="text" name="name" class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Location</label>
                                <input type="text" name="location"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['location']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Cuisine Type</label>
                                <input type="text" name="cuisine_type"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['cuisine_type']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Rating (0 - 5.0)</label>
                                <input type="number" step="0.1" max="5" name="rating"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo $item['rating'] ?? 0; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status</label>
                                <select name="status" class="form-select rounded-pill bg-light border-0 px-4">
                                    <option value="pending" <?php echo $item['status'] == 'pending' ? 'selected' : ''; ?>
                                        >Pending</option>
                                    <option value="approved" <?php echo $item['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="rejected" <?php echo $item['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="description" rows="5"
                                    class="form-control rounded-4 bg-light border-0 px-4 py-3"><?php echo htmlspecialchars($item['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="form-section">
                        <h5 class="section-title">Owner Information</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="owner_name"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="owner_email"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="text" name="owner_phone"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($item['owner_phone']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end mb-5">
                        <a href="manage_restaurants.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold">Cancel</a>
                        <button type="submit" name="save_changes"
                            class="btn btn-primary-green rounded-pill px-5 py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i>Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>