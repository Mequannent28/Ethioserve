<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['id'])) {
    redirectWithMessage('../login.php', 'warning', 'Please login to post items for sale.');
}

$user_id = $_SESSION['id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['post_material'])) {
    $category = sanitize($_POST['category']);
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = floatval($_POST['price']);
    $condition = sanitize($_POST['condition']);
    $location = sanitize($_POST['location']);
    $phone = sanitize($_POST['phone']);

    // Basic validation
    if (empty($title))
        $errors[] = "Title is required.";
    if ($price <= 0)
        $errors[] = "Price must be greater than zero.";
    if (empty($phone))
        $errors[] = "Phone number is required.";

    // Handle Image Upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../assets/img/exchange/";
        if (!is_dir($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        $file_extension = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $file_name = uniqid() . '.' . $file_extension;
        $target_file = $target_dir . $file_name;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            $image_url = '../assets/img/exchange/' . $file_name;
        } else {
            $errors[] = "Error uploading image.";
        }
    }

    if (empty($errors)) {
        try {
            $sql = "INSERT INTO exchange_materials (user_id, category, title, description, price, `condition`, image_url, location, phone, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $category, $title, $description, $price, $condition, $image_url, $location, $phone]);

            redirectWithMessage('exchange_material.php', 'success', 'Your item has been posted successfully!');
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

$categories = ['Mobile', 'Computers', 'Electronics', 'Furniture', 'Vehicles', 'Others'];

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div
                    class="card-header bg-primary-green text-white p-4 d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold mb-0">Post New Item for Sale</h3>
                        <p class="mb-0 small opacity-75">Connect with thousands of buyers near you</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-white text-primary-green rounded-pill px-3 py-2 fw-bold">
                            <i class="fas fa-user me-1"></i> Posting as
                            <?php echo htmlspecialchars($_SESSION['full_name'] ?: $_SESSION['username']); ?>
                        </span>
                    </div>
                </div>

                <div class="card-body p-5">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $error): ?>
                                    <li>
                                        <?php echo $error; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form action="post_material.php" method="POST" enctype="multipart/form-data">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Item Title</label>
                                <input type="text" name="title" class="form-control form-control-lg rounded-3"
                                    placeholder="e.g. iPhone 13 Pro Max" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Category</label>
                                <select name="category" class="form-select form-select-lg rounded-3" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat; ?>">
                                            <?php echo $cat; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Description</label>
                                <textarea name="description" class="form-control rounded-3" rows="4"
                                    placeholder="Mention key features, condition, reason for selling..."
                                    required></textarea>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Price (ETB)</label>
                                <div class="input-group input-group-lg">
                                    <input type="number" step="0.01" name="price" class="form-control rounded-start-3"
                                        placeholder="0.00" required>
                                    <span class="input-group-text bg-light">ETB</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Condition</label>
                                <div class="d-flex gap-3 mt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="condition" value="new"
                                            id="condNew">
                                        <label class="form-check-label" for="condNew">New</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="condition" value="used"
                                            id="condUsed" checked>
                                        <label class="form-check-label" for="condUsed">Used</label>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Location</label>
                                <input type="text" name="location" class="form-control form-control-lg rounded-3"
                                    placeholder="e.g. Bole, Addis Ababa" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Phone</label>
                                <input type="text" name="phone" class="form-control form-control-lg rounded-3"
                                    placeholder="e.g. 0911223344" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold">Item Image</label>
                                <div class="upload-box p-4 border-2 border-dashed rounded-4 text-center bg-light">
                                    <i class="fas fa-cloud-upload-alt text-primary-green fs-1 mb-2"></i>
                                    <p class="mb-2">Drag and drop your image here or click to browse</p>
                                    <input type="file" name="image" class="form-control" accept="image/*" required>
                                </div>
                            </div>

                            <div class="col-12 pt-3">
                                <button type="submit" name="post_material"
                                    class="btn btn-primary-green btn-lg w-100 rounded-pill fw-bold py-3 shadow">
                                    Post Ad Now
                                </button>
                                <p class="text-center text-muted small mt-3">By clicking "Post Ad", you agree to our
                                    terms and conditions.</p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .upload-box {
        border: 2px dashed #dee2e6;
        transition: all 0.3s;
    }

    .upload-box:hover {
        border-color: #1B5E20;
        background: #f1f8f3 !important;
    }
</style>

<?php include '../includes/footer.php'; ?>