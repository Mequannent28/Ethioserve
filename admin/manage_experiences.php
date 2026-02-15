<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle add experience
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_experience'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $title = sanitize($_POST['title']);
        $category = sanitize($_POST['category']);
        $location = sanitize($_POST['location']);
        $price = (float) $_POST['price'];
        $image_url = sanitize($_POST['image_url'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        try {
            $pdo->exec("CREATE TABLE IF NOT EXISTS experiences (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                category VARCHAR(100),
                location VARCHAR(255),
                price DECIMAL(10,2) DEFAULT 0,
                image_url VARCHAR(500),
                description TEXT,
                rating DECIMAL(2,1) DEFAULT 0.0,
                status ENUM('active','inactive') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO experiences (title, category, location, price, image_url, description) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $category, $location, $price, $image_url, $description]);
            redirectWithMessage('manage_experiences.php', 'success', 'Experience added!');
        } catch (Exception $e) {
            redirectWithMessage('manage_experiences.php', 'error', 'Failed: ' . $e->getMessage());
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM experiences WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_experiences.php', 'success', 'Experience deleted');
    } catch (Exception $e) {
    }
}

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS experiences (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        category VARCHAR(100),
        location VARCHAR(255),
        price DECIMAL(10,2) DEFAULT 0,
        image_url VARCHAR(500),
        description TEXT,
        rating DECIMAL(2,1) DEFAULT 0.0,
        status ENUM('active','inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
}

$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM experiences ORDER BY created_at DESC");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Experiences - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-skating text-purple me-2"
                            style="color:#9C27B0;"></i>Manage Experiences</h2>
                    <p class="text-muted mb-0">Add and manage local experiences, tours, and activities</p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i>Add Experience
                </button>
            </div>

            <!-- Cards Grid -->
            <?php if (empty($items)): ?>
                <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                    <i class="fas fa-skating fs-1 mb-3 d-block text-muted"></i>
                    <h5 class="text-muted">No experiences yet</h5>
                    <p class="text-muted">Add tours, adventures, cultural experiences and more.</p>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($items as $item): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden">
                                <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1506929562872-bb421503ef21?w=400'); ?>"
                                    class="card-img-top" style="height:180px;object-fit:cover;">
                                <div class="card-body p-4">
                                    <span class="badge bg-light text-dark mb-2">
                                        <?php echo htmlspecialchars($item['category'] ?? 'Experience'); ?>
                                    </span>
                                    <h5 class="fw-bold">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </h5>
                                    <p class="text-muted small">
                                        <?php echo mb_strimwidth(htmlspecialchars($item['description'] ?? ''), 0, 80, '...'); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold text-primary-green">
                                            <?php echo number_format($item['price']); ?> ETB
                                        </span>
                                        <div>
                                            <span
                                                class="badge bg-<?php echo $item['status'] === 'active' ? 'success' : 'secondary'; ?>">
                                                <?php echo ucfirst($item['status']); ?>
                                            </span>
                                            <a href="?delete=<?php echo $item['id']; ?>"
                                                class="btn btn-sm btn-outline-danger rounded-pill ms-2"
                                                onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Modal -->
    <div class="modal fade" id="addModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold">Add New Experience</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-0">
                    <?php echo csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Title</label>
                            <input type="text" name="title" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="e.g. Lake Langano Weekend Trip">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select rounded-pill bg-light border-0 px-4">
                                <option value="Adventure">Adventure</option>
                                <option value="Cultural">Cultural</option>
                                <option value="Nature">Nature</option>
                                <option value="Food Tour">Food Tour</option>
                                <option value="Nightlife">Nightlife</option>
                                <option value="Wellness">Wellness</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Location</label>
                            <input type="text" name="location" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="e.g. Addis Ababa">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Image URL</label>
                            <input type="url" name="image_url" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="https://...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 px-4" rows="3"
                                style="border-radius:15px;"></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_experience"
                        class="btn btn-primary-green w-100 rounded-pill py-3 fw-bold mt-4 shadow">
                        <i class="fas fa-check-circle me-2"></i>Add Experience
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>