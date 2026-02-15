<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in and is a broker
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'broker') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$flash = getFlashMessage();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_listing'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage('post_listing.php', 'error', 'Invalid security token');
    }

    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = (float) $_POST['price'];
    $location = sanitize($_POST['location']);
    $type = sanitize($_POST['type']);
    $image_url = sanitize($_POST['image_url']);

    // Get broker ID
    $stmt = $pdo->prepare("SELECT id FROM brokers WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $broker = $stmt->fetch();

    if ($broker) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO listings (user_id, title, description, price, location, type, image_url, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'available', NOW())
            ");
            $stmt->execute([
                $user_id,
                $title,
                $description,
                $price,
                $location,
                $type,
                $image_url
            ]);
            redirectWithMessage('../customer/listings.php?type=' . $type, 'success', 'Listing posted successfully!');
        } catch (Exception $e) {
            redirectWithMessage('post_listing.php', 'error', 'Failed to post listing. Please try again.');
        }
    }
}

$current_page = 'post_listing.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Listing - Broker Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
</head>

<body>
    <div class="d-flex">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="flex-grow-1 p-5 bg-light min-vh-100">
            <div class="max-width-800 mx-auto">
                <div class="mb-5">
                    <h2 class="fw-bold">Create New Listing</h2>
                    <p class="text-muted">Post a new house, car, or service listing to the marketplace.</p>
                </div>

                <?php if ($flash): ?>
                    <div
                        class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-alert="dismiss"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <form action="post_listing.php" method="POST" class="card-body p-4">
                        <?php echo csrfField(); ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Listing Title</label>
                                <input type="text" name="title"
                                    class="form-control form-control-lg rounded-3 border-light bg-light"
                                    placeholder="e.g. Modern Apartment in Bole" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Listing Type</label>
                                <select name="type" class="form-select form-select-lg rounded-3 border-light bg-light"
                                    required>
                                    <option value="house_rent">House Rent</option>
                                    <option value="car_rent">Car Rent</option>
                                    <option value="bus_ticket">Bus Ticket</option>
                                    <option value="home_service">Home Service</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Price (ETB)</label>
                                <div class="input-group input-group-lg">
                                    <span
                                        class="input-group-text bg-light border-light rounded-start-3 text-muted">ETB</span>
                                    <input type="number" name="price"
                                        class="form-control border-light bg-light rounded-end-3" placeholder="0.00"
                                        required>
                                </div>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Location</label>
                                <input type="text" name="location"
                                    class="form-control form-control-lg rounded-3 border-light bg-light"
                                    placeholder="e.g. Bole, Addis Ababa" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                                <textarea name="description" class="form-control rounded-3 border-light bg-light"
                                    rows="4" placeholder="Describe the listing in detail..." required></textarea>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Image URL</label>
                                <input type="url" name="image_url" class="form-control rounded-3 border-light bg-light"
                                    placeholder="https://example.com/image.jpg">
                                <div class="form-text">Provide a direct link to a high-quality image.</div>
                            </div>

                            <div class="col-12 pt-3">
                                <button type="submit" name="submit_listing"
                                    class="btn btn-primary-green btn-lg rounded-pill px-5 w-100 shadow">
                                    <i class="fas fa-paper-plane me-2"></i> Publish Listing
                                </button>
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