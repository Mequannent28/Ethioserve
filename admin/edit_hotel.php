<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$id = $_GET['id'] ?? null;
if (!$id) {
    redirectWithMessage('manage_hotels.php', 'danger', 'Hotel ID is required');
}

// Fetch Hotel and Owner info
$stmt = $pdo->prepare("SELECT h.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone, u.id as user_id 
                     FROM hotels h 
                     JOIN users u ON h.user_id = u.id 
                     WHERE h.id = ?");
$stmt->execute([$id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    redirectWithMessage('manage_hotels.php', 'danger', 'Hotel not found');
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_changes'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $name = sanitize($_POST['name']);
        $location = sanitize($_POST['location']);
        $cuisine = sanitize($_POST['cuisine_type']);
        $rating = floatval($_POST['rating']);
        $status = sanitize($_POST['status']);
        $description = $_POST['description']; // Allowing some HTML if specified, but sanitize for basics

        $owner_name = sanitize($_POST['owner_name']);
        $owner_email = sanitize($_POST['owner_email']);
        $owner_phone = sanitize($_POST['owner_phone']);

        try {
            $pdo->beginTransaction();

            // Update Hotel
            $stmt = $pdo->prepare("UPDATE hotels SET name = ?, location = ?, cuisine_type = ?, rating = ?, status = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $location, $cuisine, $rating, $status, $description, $id]);

            // Update Owner (User)
            $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ? WHERE id = ?");
            $stmt->execute([$owner_name, $owner_email, $owner_phone, $hotel['user_id']]);

            $pdo->commit();
            redirectWithMessage('view_hotel.php?id=' . $id, 'success', 'Hotel profile updated successfully');
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
    <title>Edit Hotel -
        <?php echo htmlspecialchars($hotel['name']); ?>
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
                        <h2 class="fw-bold mb-0">Edit Hotel Profile</h2>
                        <p class="text-muted">Modify information for <strong>
                                <?php echo htmlspecialchars($hotel['name']); ?>
                            </strong></p>
                    </div>
                    <a href="view_hotel.php?id=<?php echo $id; ?>" class="btn btn-outline-secondary rounded-pill px-4">
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

                    <!-- Hotel Details -->
                    <div class="form-section">
                        <h5 class="section-title">Hotel Details</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Hotel Name</label>
                                <input type="text" name="name" class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Location / Address</label>
                                <input type="text" name="location"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['location']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Cuisine / Service Type</label>
                                <input type="text" name="cuisine_type"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['cuisine_type']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Rating (0 - 5.0)</label>
                                <input type="number" step="0.1" max="5" name="rating"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo $hotel['rating']; ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Status</label>
                                <select name="status" class="form-select rounded-pill bg-light border-0 px-4">
                                    <option value="pending" <?php echo $hotel['status'] == 'pending' ? 'selected' : ''; ?>>Pending Approval</option>
                                    <option value="approved" <?php echo $hotel['status'] == 'approved' ? 'selected' : ''; ?>>Approved / Live</option>
                                    <option value="rejected" <?php echo $hotel['status'] == 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Description</label>
                                <textarea name="description" rows="5"
                                    class="form-control rounded-4 bg-light border-0 px-4 py-3"
                                    placeholder="Enter a compelling description..."><?php echo htmlspecialchars($hotel['description'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Owner Details -->
                    <div class="form-section">
                        <h5 class="section-title">Owner Information</h5>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label small fw-bold">Full Name</label>
                                <input type="text" name="owner_name"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['owner_name']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Email Address</label>
                                <input type="email" name="owner_email"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['owner_email']); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Phone Number</label>
                                <input type="text" name="owner_phone"
                                    class="form-control rounded-pill bg-light border-0 px-4"
                                    value="<?php echo htmlspecialchars($hotel['owner_phone']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-3 justify-content-end mb-5">
                        <a href="manage_hotels.php" class="btn btn-light rounded-pill px-5 py-2 fw-bold">Cancel</a>
                        <button type="submit" name="save_changes"
                            class="btn btn-primary-green rounded-pill px-5 py-2 fw-bold shadow-sm">
                            <i class="fas fa-save me-2"></i>Save Final Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>