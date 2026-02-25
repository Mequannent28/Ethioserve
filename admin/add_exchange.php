<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

// Fetch potential sellers (limit to 100 for performance)
$stmt = $pdo->query("SELECT id, full_name, role FROM users ORDER BY full_name LIMIT 100");
$users = $stmt->fetchAll();

$categories = ['Mobile', 'Computers', 'Electronics', 'Furniture', 'Vehicles', 'Others'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_item'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = (int) $_POST['user_id'];
        $category = sanitize($_POST['category']);
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $condition = sanitize($_POST['condition']);
        $location = sanitize($_POST['location']);
        $phone = sanitize($_POST['phone']);
        $image_url = sanitize($_POST['image_url']);

        try {
            $sql = "INSERT INTO exchange_materials (user_id, category, title, description, price, `condition`, image_url, location, phone, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $category, $title, $description, $price, $condition, $image_url, $location, $phone]);

            redirectWithMessage('manage_exchange.php', 'success', 'Exchange item added successfully');
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Exchange Item - Admin Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f4f6f9;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 240px;
            padding: 30px;
            min-height: 100vh;
        }

        .form-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        }

        .section-title {
            border-left: 5px solid var(--primary-green);
            padding-left: 15px;
            margin-bottom: 30px;
            font-weight: 700;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>
        <div class="main-content">
            <div class="container-fluid">
                <div class="d-flex justify-content-between align-items-center mb-5">
                    <div>
                        <h2 class="fw-bold mb-1">Add Exchange Item</h2>
                        <p class="text-muted mb-0">Manually post an item to the community marketplace.</p>
                    </div>
                    <a href="manage_exchange.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="fas fa-arrow-left me-2"></i>Back to List
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger rounded-4 border-0 shadow-sm mb-4">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <div class="form-card">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="add_item" value="1">

                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="section-title">Item Details</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Item Title</label>
                                        <input type="text" name="title" class="form-control rounded-3" required
                                            placeholder="e.g. iPhone 13 Pro">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea name="description" class="form-control rounded-3" rows="5" required
                                            placeholder="Describe the item..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Price (ETB)</label>
                                        <input type="number" step="0.01" name="price" class="form-control rounded-3"
                                            required placeholder="0.00">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Category</label>
                                        <select name="category" class="form-select rounded-3">
                                            <?php foreach ($categories as $cat): ?>
                                                <option value="<?php echo $cat; ?>">
                                                    <?php echo $cat; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Condition</label>
                                        <select name="condition" class="form-select rounded-3">
                                            <option value="new">New</option>
                                            <option value="used" selected>Used</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Item Image URL</label>
                                        <input type="url" name="image_url" class="form-control rounded-3"
                                            placeholder="https://...">
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="bg-light p-4 rounded-4 mb-4">
                                    <h5 class="fw-bold mb-3 small text-uppercase text-muted">Seller Information</h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Assigned User</label>
                                        <select name="user_id" class="form-select rounded-3" required>
                                            <?php foreach ($users as $user): ?>
                                                <option value="<?php echo $user['id']; ?>">
                                                    <?php echo htmlspecialchars($user['full_name']); ?> (
                                                    <?php echo ucfirst($user['role']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Contact Phone</label>
                                        <input type="text" name="phone" class="form-control rounded-3"
                                            placeholder="e.g. 0911223344" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Location</label>
                                        <input type="text" name="location" class="form-control rounded-3"
                                            placeholder="e.g. Bole, Addis Ababa" required>
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit"
                                        class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                        <i class="fas fa-check-circle me-2"></i>Post Material
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>