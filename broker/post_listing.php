<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Check if user is logged in as broker or owner
requireRole(['broker', 'property_owner']);

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

    // Handle Video Upload
    $video_url = sanitize($_POST['video_url'] ?? '');
    $uploaded_video = uploadFile('listing_video', 'uploads/listings', ['mp4', 'mov', 'avi', 'mkv', 'webm']);
    if ($uploaded_video) {
        $video_url = $uploaded_video;
    }

    try {
        $bedrooms = (int) ($_POST['bedrooms'] ?? 0);
        $bathrooms = (int) ($_POST['bathrooms'] ?? 0);
        $area_sqm = (int) ($_POST['area_sqm'] ?? 0);
        $features = sanitize($_POST['features'] ?? '');
        $contact_name = sanitize($_POST['contact_name'] ?? '');
        $contact_phone = sanitize($_POST['contact_phone'] ?? '');

        // Handle Image Upload
        $uploaded_img = uploadFile('listing_image', 'uploads/listings');
        if ($uploaded_img) {
            $image_url = $uploaded_img;
        }

        $stmt = $pdo->prepare("
            INSERT INTO listings (user_id, title, description, price, location, type, image_url, video_url, bedrooms, bathrooms, area_sqm, features, contact_name, contact_phone, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available', NOW())
        ");
        $stmt->execute([
            $user_id,
            $title,
            $description,
            $price,
            $location,
            $type,
            $image_url,
            $video_url,
            $bedrooms,
            $bathrooms,
            $area_sqm,
            $features,
            $contact_name,
            $contact_phone
        ]);
        redirectWithMessage('my_listings.php', 'success', 'Listing posted successfully!');
    } catch (Exception $e) {
        redirectWithMessage('post_listing.php', 'error', 'Failed to post listing. Error: ' . $e->getMessage());
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
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { overflow-x: hidden; background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { flex: 1; width: 100%; margin-left: 260px; background: #f0f2f5; min-height: 100vh; transition: all 0.3s ease; }
        .max-width-800 { max-width: 820px; margin: 0 auto; width: 100%; }
        
        .mobile-header { display: none; background: #fff; padding: 15px 20px; border-bottom: 1px solid #eee; position: sticky; top: 0; z-index: 90; }
        
        @media (max-width: 991px) { 
            .main-content { margin-left: 0; padding: 20px !important; }
            .mobile-header { display: flex; align-items: center; justify-content: space-between; width: 100%; }
            .max-width-800 { max-width: 100%; }
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
                    <h2 class="fw-bold mb-0">Create New Listing</h2>
                    <p class="text-muted">Post a new house, car, or service listing to the marketplace.</p>
                </div>

                <?php if ($flash): ?>
                    <div
                        class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                        <?php echo htmlspecialchars($flash['message']); ?>
                        <button type="button" class="btn-close" data-bs-alert="dismiss"></button>
                    </div>
                <?php endif; ?>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-5">
                    <form action="post_listing.php" method="POST" enctype="multipart/form-data" class="card-body p-4">
                        <?php echo csrfField(); ?>

                        <div class="row g-4">
                            <div class="col-12 text-center mb-2">
                                <div class="bg-success bg-opacity-10 p-4 rounded-4 d-inline-block">
                                    <i class="fas fa-bullhorn text-success fs-1"></i>
                                </div>
                            </div>
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

                            <!-- House Specific Fields (Conditional visibility could be added later with JS) -->
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Bedrooms</label>
                                <input type="number" name="bedrooms" class="form-control rounded-3 border-light bg-light" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Bathrooms</label>
                                <input type="number" name="bathrooms" class="form-control rounded-3 border-light bg-light" placeholder="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold small text-uppercase text-muted">Area (m²)</label>
                                <input type="number" name="area_sqm" class="form-control rounded-3 border-light bg-light" placeholder="0">
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-bold small text-uppercase text-muted">Features & Amenities</label>
                                <input type="text" name="features" class="form-control rounded-3 border-light bg-light" placeholder="e.g. WiFi, Parking, AC (Comma separated)">
                            </div>

                            <hr class="my-3 opacity-10">
                            <h6 class="fw-bold mb-0">Public Contact Details</h6>

                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Contact Name</label>
                                <input type="text" name="contact_name" class="form-control rounded-3 border-light bg-light" placeholder="Name to display" value="<?php echo htmlspecialchars($_SESSION['full_name'] ?? ''); ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small text-uppercase text-muted">Contact Phone</label>
                                <input type="tel" name="contact_phone" class="form-control rounded-3 border-light bg-light" placeholder="Phone to display">
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Upload Property Photo</label>
                                <div class="upload-container text-center p-4 border-2 border-dashed rounded-4 bg-light position-relative" id="drop-area-img" style="min-height: 200px;">
                                    <input type="file" name="listing_image" id="listing_image" class="position-absolute h-100 w-100 opacity-0 top-0 start-0 cursor-pointer" accept="image/*">
                                    <div class="upload-icon mb-2">
                                        <i class="fas fa-image fs-1 text-success opacity-50"></i>
                                    </div>
                                    <p class="mb-0 text-muted small" id="file-name-img">Click to upload photo</p>
                                    <div id="image-preview" class="mt-3 d-none">
                                        <img src="" class="img-fluid rounded-3 shadow-sm mx-auto" style="max-height: 120px;">
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 mt-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">Upload Property Video</label>
                                <div class="upload-container text-center p-4 border-2 border-dashed rounded-4 bg-light position-relative" id="drop-area-vid" style="min-height: 200px;">
                                    <input type="file" name="listing_video" id="listing_video" class="position-absolute h-100 w-100 opacity-0 top-0 start-0 cursor-pointer" accept="video/*">
                                    <div class="upload-icon mb-2">
                                        <i class="fas fa-video fs-1 text-primary opacity-50"></i>
                                    </div>
                                    <p class="mb-0 text-muted small" id="file-name-vid">Click to upload video</p>
                                    <div id="video-preview" class="mt-3 d-none">
                                        <video src="" class="img-fluid rounded-3 shadow-sm mx-auto" style="max-height: 120px;" controls></video>
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <label class="form-label fw-bold small text-uppercase text-muted">External Video URL (Optional)</label>
                                <input type="url" name="video_url" id="video_url" class="form-control rounded-3 border-light bg-light"
                                    placeholder="https://youtube.com/watch?v=...">
                                <div class="form-text">If you have a video on YouTube or Vimeo, provide the link here.</div>
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
    <script>
        document.getElementById('listing_image').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Click to upload photo';
            document.getElementById('file-name-img').textContent = fileName;
            
            const [file] = this.files;
            if (file) {
                const preview = document.getElementById('image-preview');
                const previewImg = preview.querySelector('img');
                previewImg.src = URL.createObjectURL(file);
                preview.classList.remove('d-none');
            }
        });

        document.getElementById('listing_video').addEventListener('change', function(e) {
            const fileName = e.target.files[0]?.name || 'Click to upload video';
            document.getElementById('file-name-vid').textContent = fileName;
            
            const [file] = this.files;
            if (file) {
                const preview = document.getElementById('video-preview');
                const previewVid = preview.querySelector('video');
                previewVid.src = URL.createObjectURL(file);
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