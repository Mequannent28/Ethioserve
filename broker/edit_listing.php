<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in as broker or owner
requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$listing_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$flash = getFlashMessage();

// Fetch listing details and verify ownership
$stmt = $pdo->prepare("SELECT * FROM listings WHERE id = ? AND user_id = ?");
$stmt->execute([$listing_id, $user_id]);
$listing = $stmt->fetch();

if (!$listing) {
    redirectWithMessage('my_listings.php', 'error', 'Listing not found or unauthorized.');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_update'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        redirectWithMessage("edit_listing.php?id=$listing_id", 'error', 'Invalid security token');
    }

    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $price = (float) $_POST['price'];
    $location = sanitize($_POST['location']);
    $type = sanitize($_POST['type']);
    $image_url = sanitize($_POST['image_url']);
    $status = sanitize($_POST['status']);
    
    $bedrooms = (int)($_POST['bedrooms'] ?? 0);
    $bathrooms = (int)($_POST['bathrooms'] ?? 0);
    $area_sqm = (int)($_POST['area_sqm'] ?? 0);
    $features = sanitize($_POST['features'] ?? '');
    $contact_name = sanitize($_POST['contact_name'] ?? '');
    $contact_phone = sanitize($_POST['contact_phone'] ?? '');

    // Handle Image Upload
    $uploaded_img = uploadFile('listing_image', 'uploads/listings');
    if ($uploaded_img) {
        $image_url = $uploaded_img;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE listings 
            SET title = ?, description = ?, price = ?, location = ?, type = ?, image_url = ?, status = ?,
                bedrooms = ?, bathrooms = ?, area_sqm = ?, features = ?, contact_name = ?, contact_phone = ?
            WHERE id = ? AND user_id = ?
        ");
        $stmt->execute([
            $title, $description, $price, $location, $type, $image_url, $status,
            $bedrooms, $bathrooms, $area_sqm, $features, $contact_name, $contact_phone,
            $listing_id, $user_id
        ]);
        redirectWithMessage('my_listings.php', 'success', 'Listing updated successfully!');
    } catch (Exception $e) {
        redirectWithMessage("edit_listing.php?id=$listing_id", 'error', 'Failed to update listing.');
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Listing - Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { overflow-x: hidden; background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { flex: 1; width: 100%; margin-left: 260px; background: #f0f2f5; min-height: 100vh; transition: all 0.3s ease; }
        .max-width-800 { max-width: 820px; margin: 0 auto; width: 100%; }
        
        .mobile-header { display: none; background: #fff; padding: 15px 20px; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 90; }
        
        @media (max-width: 991px) { 
            .main-content { margin-left: 0; padding: 0 !important; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; width: 100%; }
            .max-width-800 { max-width: 100%; padding: 20px !important; }
            .sidebar-broker { margin-top: 0; }
        }
        
        .sidebar-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 95; }
        .sidebar-overlay.show { display: block; }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>

        <div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>
        
        <div class="main-content d-flex flex-column align-items-center p-0">
            <!-- Mobile Header -->
            <div class="mobile-header d-lg-none">
                <button class="btn btn-light border shadow-sm rounded-3" onclick="toggleSidebar()">
                    <i class="fas fa-bars"></i>
                </button>
                <h5 class="mb-0 fw-bold text-success">EthioServe</h5>
                <div style="width: 40px;"></div>
            </div>

            <div class="p-4 p-md-5 w-100 d-flex flex-column align-items-center">
                <div class="max-width-800 w-100">
                <div class="mb-5">
                    <h2 class="fw-bold mb-0">Edit Listing</h2>
                    <p class="text-muted">Update details for "<?php echo htmlspecialchars($listing['title']); ?>"</p>
                </div>

                <?php if ($flash): ?>
                    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <form action="edit_listing.php?id=<?php echo $listing_id; ?>" method="POST" enctype="multipart/form-data" class="card-body p-4">
                        <?php echo csrfField(); ?>

                        <div class="row g-4">
                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Listing Title</label>
                                <input type="text" name="title" class="form-control form-control-lg rounded-3 border-light bg-light" value="<?php echo htmlspecialchars($listing['title']); ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Listing Type</label>
                                <select name="type" class="form-select form-select-lg rounded-3 border-light bg-light" required>
                                    <option value="house_rent" <?php echo $listing['type'] === 'house_rent' ? 'selected' : ''; ?>>House Rent</option>
                                    <option value="car_rent" <?php echo $listing['type'] === 'car_rent' ? 'selected' : ''; ?>>Car Rent</option>
                                    <option value="bus_ticket" <?php echo $listing['type'] === 'bus_ticket' ? 'selected' : ''; ?>>Bus Ticket</option>
                                    <option value="home_service" <?php echo $listing['type'] === 'home_service' ? 'selected' : ''; ?>>Home Service</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Price (ETB)</label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-light border-light rounded-start-3 text-muted">ETB</span>
                                    <input type="number" name="price" class="form-control border-light bg-light rounded-end-3" value="<?php echo $listing['price']; ?>" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Status</label>
                                <select name="status" class="form-select form-select-lg rounded-3 border-light bg-light" required>
                                    <option value="available" <?php echo $listing['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                    <option value="rented" <?php echo $listing['status'] === 'rented' ? 'selected' : ''; ?>>Rented</option>
                                    <option value="not_available" <?php echo $listing['status'] === 'not_available' ? 'selected' : ''; ?>>Not Available / Hidden</option>
                                    <option value="pending" <?php echo $listing['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="taken" <?php echo $listing['status'] === 'taken' ? 'selected' : ''; ?>>Taken</option>
                                    <option value="on_process" <?php echo $listing['status'] === 'on_process' ? 'selected' : ''; ?>>On Process</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Location</label>
                                <input type="text" name="location" class="form-control form-control-lg rounded-3 border-light bg-light" value="<?php echo htmlspecialchars($listing['location']); ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Description</label>
                                <textarea name="description" class="form-control rounded-3 border-light bg-light" rows="4" required><?php echo htmlspecialchars($listing['description']); ?></textarea>
                            </div>

                            <hr class="my-3 opacity-10">
                            <h6 class="fw-bold mb-0">Property Details (for House Rent)</h6>

                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Bedrooms</label>
                                <input type="number" name="bedrooms" class="form-control rounded-3 border-light bg-light" value="<?php echo $listing['bedrooms']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control rounded-3 border-light bg-light" value="<?php echo $listing['bathrooms']; ?>">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Area (m²)</label>
                                <input type="number" name="area_sqm" class="form-control rounded-3 border-light bg-light" value="<?php echo $listing['area_sqm']; ?>">
                            </div>

                            <div class="col-12 text-center my-3">
                                <p class="small text-muted mb-2 text-uppercase fw-bold">Current Listing Image</p>
                                <img src="<?php 
                                    if (empty($listing['image_url'])) {
                                        echo 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=400';
                                    } elseif (strpos($listing['image_url'], 'http') === 0) {
                                        echo htmlspecialchars($listing['image_url']);
                                    } else {
                                        echo BASE_URL . '/' . htmlspecialchars($listing['image_url']);
                                    }
                                ?>" class="img-thumbnail rounded-3" style="max-height: 150px;">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Update Image URL (Optional)</label>
                                <input type="url" name="image_url" class="form-control rounded-3 border-light bg-light" value="<?php echo htmlspecialchars($listing['image_url']); ?>">
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Upload New Photo</label>
                                <div class="upload-container text-center p-4 border-2 border-dashed rounded-4 bg-light position-relative" id="drop-area">
                                    <input type="file" name="listing_image" id="listing_image" class="position-absolute h-100 w-100 opacity-0 top-0 start-0 cursor-pointer" accept="image/*">
                                    <div class="upload-icon mb-2">
                                        <i class="fas fa-camera fs-2 text-success opacity-50"></i>
                                    </div>
                                    <p class="mb-0 text-muted small" id="file-name">Click to change photo</p>
                                    <div id="image-preview" class="mt-3 d-none">
                                        <img src="" class="img-fluid rounded-3 shadow-sm mx-auto" style="max-height: 150px;">
                                    </div>
                                </div>
                            </div>

                            <hr class="my-3 opacity-10">
                            <h6 class="fw-bold mb-0">Public Contact Details</h6>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Contact Name</label>
                                <input type="text" name="contact_name" class="form-control rounded-3 border-light bg-light" value="<?php echo htmlspecialchars($listing['contact_name']); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Contact Phone</label>
                                <input type="tel" name="contact_phone" class="form-control rounded-3 border-light bg-light" value="<?php echo htmlspecialchars($listing['contact_phone']); ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Features & Amenities</label>
                                <input type="text" name="features" class="form-control rounded-3 border-light bg-light" placeholder="WiFi, AC, Monitoring" value="<?php echo htmlspecialchars($listing['features']); ?>">
                            </div>

                            <div class="col-12 pt-3">
                                <button type="submit" name="submit_update" class="btn btn-primary-green btn-lg rounded-pill px-5 w-100 shadow">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                                <a href="my_listings.php" class="btn btn-link w-100 mt-2 text-muted">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('listing_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Click to change photo';
            document.getElementById('file-name').textContent = fileName;
            
            const [file] = this.files;
            if (file) {
                const preview = document.getElementById('image-preview');
                const previewImg = preview.querySelector('img');
                previewImg.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
            }
        });
    </script>
    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('brokerSidebar');
            const overlay = document.getElementById('sidebarOverlay');
            sidebar.classList.toggle('show');
            overlay.classList.toggle('show');
        }
    </script>
</body>
</html>
