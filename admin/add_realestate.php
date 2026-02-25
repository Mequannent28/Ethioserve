<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

$error = '';
$success = '';

// Fetch potential agents
$stmt = $pdo->query("SELECT id, full_name, role FROM users WHERE role IN ('broker', 'agent', 'admin') ORDER BY full_name");
$agents = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_property'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $agent_id = (int) $_POST['agent_id'];
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $price = (float) $_POST['price'];
        $type = sanitize($_POST['type']);
        $category = sanitize($_POST['category']);
        $location = sanitize($_POST['location']);
        $city = sanitize($_POST['city']);
        $bedrooms = (int) $_POST['bedrooms'];
        $bathrooms = (int) $_POST['bathrooms'];
        $area_sqm = (float) $_POST['area_sqm'];
        $image_url = sanitize($_POST['image_url']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO real_estate_properties 
                (agent_id, title, description, price, type, category, location, city, bedrooms, bathrooms, area_sqm, image_url, is_featured, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')");

            $stmt->execute([$agent_id, $title, $description, $price, $type, $category, $location, $city, $bedrooms, $bathrooms, $area_sqm, $image_url, $is_featured]);

            redirectWithMessage('manage_realestate.php', 'success', 'Property added successfully');
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
    <title>Add Property - Admin Panel</title>
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
                        <h2 class="fw-bold mb-1">Add New Property</h2>
                        <p class="text-muted mb-0">List a new real estate property on the platform.</p>
                    </div>
                    <a href="manage_realestate.php" class="btn btn-outline-secondary rounded-pill px-4">
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
                        <input type="hidden" name="add_property" value="1">

                        <div class="row">
                            <div class="col-lg-8">
                                <h5 class="section-title">Property Details</h5>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Property Title</label>
                                        <input type="text" name="title" class="form-control rounded-3" required
                                            placeholder="e.g. Modern Villa in Bole">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label fw-bold">Description</label>
                                        <textarea name="description" class="form-control rounded-3" rows="5" required
                                            placeholder="Detailed description of the property..."></textarea>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Price (ETB)</label>
                                        <input type="number" name="price" class="form-control rounded-3" required
                                            placeholder="0.00">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Type</label>
                                        <select name="type" class="form-select rounded-3">
                                            <option value="sale">For Sale</option>
                                            <option value="rent">For Rent</option>
                                            <option value="lease">Lease</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Category</label>
                                        <select name="category" class="form-select rounded-3">
                                            <option value="house">House</option>
                                            <option value="apartment">Apartment</option>
                                            <option value="villa">Villa</option>
                                            <option value="condominium">Condominium</option>
                                            <option value="office">Office</option>
                                            <option value="land">Land</option>
                                            <option value="commercial">Commercial</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold">Area (sqm)</label>
                                        <input type="number" step="0.01" name="area_sqm" class="form-control rounded-3"
                                            placeholder="e.g. 250">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Bedrooms</label>
                                        <input type="number" name="bedrooms" class="form-control rounded-3" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-bold">Bathrooms</label>
                                        <input type="number" name="bathrooms" class="form-control rounded-3" value="0">
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check mt-4 pt-2">
                                            <input class="form-check-input" type="checkbox" name="is_featured"
                                                id="isFeatured">
                                            <label class="form-check-label fw-bold" for="isFeatured">Mark as
                                                Featured</label>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-lg-4">
                                <div class="bg-light p-4 rounded-4 mb-4">
                                    <h5 class="fw-bold mb-3 small text-uppercase text-muted">Management</h5>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Assign Agent</label>
                                        <select name="agent_id" class="form-select rounded-3" required>
                                            <?php foreach ($agents as $agent): ?>
                                                <option value="<?php echo $agent['id']; ?>">
                                                    <?php echo htmlspecialchars($agent['full_name']); ?> (
                                                    <?php echo ucfirst($agent['role']); ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">City</label>
                                        <input type="text" name="city" class="form-control rounded-3"
                                            value="Addis Ababa" required>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Location / Address</label>
                                        <input type="text" name="location" class="form-control rounded-3"
                                            placeholder="e.g. Bole Atlas" required>
                                    </div>
                                </div>

                                <div class="bg-light p-4 rounded-4">
                                    <h5 class="fw-bold mb-3 small text-uppercase text-muted">Media</h5>
                                    <div class="mb-0">
                                        <label class="form-label fw-bold">Main Image URL</label>
                                        <input type="url" name="image_url" class="form-control rounded-3"
                                            placeholder="https://...">
                                    </div>
                                </div>

                                <div class="mt-4">
                                    <button type="submit"
                                        class="btn btn-primary-green w-100 py-3 rounded-pill fw-bold shadow-sm">
                                        <i class="fas fa-check-circle me-2"></i>Publish Property
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