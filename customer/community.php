<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;
$tab = $_GET['tab'] ?? 'news';

// Fetch Data based on tab
if ($tab == 'news') {
    $stmt = $pdo->query("SELECT * FROM comm_news ORDER BY created_at DESC");
    $news = $stmt->fetchAll();
} elseif ($tab == 'events') {
    $stmt = $pdo->query("SELECT * FROM comm_events ORDER BY event_date ASC");
    $events = $stmt->fetchAll();
} elseif ($tab == 'marketplace') {
    $stmt = $pdo->query("SELECT * FROM comm_marketplace WHERE status = 'available' ORDER BY created_at DESC");
    $items = $stmt->fetchAll();
} elseif ($tab == 'lostfound') {
    $stmt = $pdo->query("SELECT * FROM comm_lost_found ORDER BY created_at DESC");
    $lostfound = $stmt->fetchAll();
} elseif ($tab == 'social') {
    $stmt = $pdo->query("SELECT * FROM comm_social_links ORDER BY name ASC");
    $social_links = $stmt->fetchAll();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['report_lost_found'])) {
        $type = sanitize($_POST['type']);
        $item_name = sanitize($_POST['item_name']);
        $description = sanitize($_POST['description']);
        $location = sanitize($_POST['location']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $image_url = '';

        // Handle File Upload
        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../uploads/community/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('comm_') . '.' . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = "../uploads/community/" . $file_name;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO comm_lost_found (type, item_name, description, location, contact_phone, image_url, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$type, $item_name, $description, $location, $contact_phone, $image_url, $user_id]);
        header("Location: community.php?tab=lostfound&success=1");
        exit();
    }

    if (isset($_POST['list_marketplace_item'])) {
        $title = sanitize($_POST['title']);
        $description = sanitize($_POST['description']);
        $price = floatval($_POST['price']);
        $category = sanitize($_POST['category']);
        $contact_phone = sanitize($_POST['contact_phone']);
        $image_url = '';

        if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
            $target_dir = "../uploads/community/";
            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $file_name = uniqid('market_') . '.' . $file_ext;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
                $image_url = "../uploads/community/" . $file_name;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO comm_marketplace (title, description, price, contact_phone, image_url, category, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $description, $price, $contact_phone, $image_url, $category, $user_id]);
        header("Location: community.php?tab=marketplace&success=1");
        exit();
    }
}

$view_link = $_GET['view_link'] ?? null;
$link_name = $_GET['link_name'] ?? 'Media Portal';
if ($view_link) {
    // Sanitize URL for safety
    $view_link = filter_var($view_link, FILTER_SANITIZE_URL);
}

include '../includes/header.php';
?>

<div class="community-page py-5 min-vh-100" style="background: #f0f2f5;">
    <div class="container">
        <!-- Hero Section -->
        <div
            class="bg-primary-green text-white p-5 rounded-5 shadow-sm mb-5 text-center position-relative overflow-hidden">
            <div class="position-relative z-1">
                <h1 class="display-5 fw-bold mb-3">Community Hub</h1>
                <p class="lead mb-4">Stay connected with Addis Ababa’s local updates, events, and marketplace.</p>
                <div class="d-flex justify-content-center gap-2">
                    <?php
                    $unread_count = 0;
                    if ($user_id) {
                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM comm_messages WHERE receiver_id = ? AND is_read = 0");
                        $stmt->execute([$user_id]);
                        $unread_count = $stmt->fetchColumn();
                    }
                    ?>
                    <a href="comm_messages.php"
                        class="btn btn-light rounded-pill px-4 fw-bold shadow-sm position-relative">
                        <i class="fas fa-comments me-2 text-primary-green"></i> My Messages
                        <?php if ($unread_count > 0): ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">unread messages</span>
                            </span>
                        <?php endif; ?>
                    </a>
                </div>
            </div>
            <i class="fas fa-users position-absolute end-0 bottom-0 opacity-10"
                style="font-size: 15rem; transform: translate(20%, 20%);"></i>
        </div>

        <?php if ($view_link): ?>
            <!-- Media Portal / Iframe View -->
            <div class="media-portal mb-5 animate-fade-in">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                    <div class="bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <a href="?tab=social" class="btn btn-sm btn-outline-light rounded-pill me-3"><i
                                    class="fas fa-arrow-left me-1"></i> Back</a>
                            <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($link_name); ?></h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <small class="text-white-50 d-none d-md-inline-block small"><i
                                    class="fas fa-info-circle me-1"></i> Site errors are from source server</small>
                            <button onclick="window.location.reload()" class="btn btn-sm btn-light rounded-circle"
                                title="Reload"><i class="fas fa-sync-alt"></i></button>
                            <a href="<?php echo $view_link; ?>" target="_blank"
                                class="btn btn-sm btn-primary-green rounded-pill px-3">Open Browser <i
                                    class="fas fa-external-link-alt ms-1"></i></a>
                        </div>
                    </div>
                    <div class="bg-white position-relative" style="height: 750px;">
                        <div id="loader" class="position-absolute top-50 start-50 translate-middle text-center w-100 p-4">
                            <div class="spinner-border text-primary-green mb-3" role="status"></div>
                            <p class="text-muted fw-bold">Connecting to Media Provider...</p>
                            <p class="text-muted small">If the player is blocked or shows errors, it's due to the provider's
                                server settings.</p>
                        </div>
                        <iframe src="<?php echo $view_link; ?>" class="w-100 h-100 border-0"
                            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                            allowfullscreen onload="document.getElementById('loader').style.display='none'"></iframe>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Navigation Tabs -->
        <div class="nav-scroller mb-5">
            <nav class="nav nav-pills nav-fill bg-white p-2 rounded-pill shadow-sm">
                <a class="nav-link rounded-pill py-3 fw-bold <?php echo $tab == 'news' ? 'active bg-primary-green text-white' : 'text-dark'; ?>"
                    href="?tab=news">
                    <i class="fas fa-newspaper me-2"></i>Local News
                </a>
                <a class="nav-link rounded-pill py-3 fw-bold <?php echo $tab == 'events' ? 'active bg-primary-green text-white' : 'text-dark'; ?>"
                    href="?tab=events">
                    <i class="fas fa-calendar-alt me-2"></i>Events
                </a>
                <a class="nav-link rounded-pill py-3 fw-bold <?php echo $tab == 'marketplace' ? 'active bg-primary-green text-white' : 'text-dark'; ?>"
                    href="?tab=marketplace">
                    <i class="fas fa-shopping-bag me-2"></i>Marketplace
                </a>
                <a class="nav-link rounded-pill py-3 fw-bold <?php echo $tab == 'lostfound' ? 'active bg-primary-green text-white' : 'text-dark'; ?>"
                    href="?tab=lostfound">
                    <i class="fas fa-search-location me-2"></i>Lost & Found
                </a>
                <a class="nav-link rounded-pill py-3 fw-bold <?php echo $tab == 'social' ? 'active bg-primary-green text-white' : 'text-dark'; ?>"
                    href="?tab=social">
                    <i class="fas fa-globe me-2"></i>Official Media
                </a>
            </nav>
        </div>

        <!-- Content Area -->
        <div class="tab-content animate-fade-in">

            <!-- NEWS TAB -->
            <?php if ($tab == 'news'): ?>
                <div class="row g-4">
                    <?php if (empty($news)): ?>
                        <p class="text-center py-5">No news yet.</p>
                    <?php endif; ?>
                    <?php foreach ($news as $n): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 hover-lift">
                                <img src="<?php echo htmlspecialchars($n['image_url']); ?>" class="card-img-top"
                                    style="height: 200px; object-fit: cover;">
                                <div class="card-body p-4">
                                    <span class="badge bg-light text-primary-green mb-2">
                                        <?php echo ucfirst($n['category']); ?>
                                    </span>
                                    <h5 class="fw-bold mb-3">
                                        <?php echo htmlspecialchars($n['title']); ?>
                                    </h5>
                                    <p class="text-muted small mb-0">
                                        <?php echo substr(htmlspecialchars($n['content']), 0, 150); ?>...
                                    </p>
                                </div>
                                <div class="card-footer bg-white border-0 px-4 py-3 border-top">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted"><i class="far fa-clock me-1"></i>
                                            <?php echo date('M d, Y', strtotime($n['created_at'])); ?>
                                        </small>
                                        <a href="#" class="btn btn-primary-green btn-sm rounded-pill px-3">Read More</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- EVENTS TAB -->
            <?php elseif ($tab == 'events'): ?>
                <div class="row g-4">
                    <?php if (empty($events)): ?>
                        <p class="text-center py-5">No events found.</p>
                    <?php endif; ?>
                    <?php foreach ($events as $e): ?>
                        <div class="col-md-6">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 hover-lift">
                                <div class="row g-0 h-100">
                                    <div class="col-md-5">
                                        <img src="<?php echo htmlspecialchars($e['image_url']); ?>" class="w-100 h-100"
                                            style="object-fit: cover; min-height: 200px;">
                                    </div>
                                    <div class="col-md-7">
                                        <div class="card-body p-4">
                                            <div class="d-flex justify-content-between mb-2">
                                                <span class="badge bg-danger rounded-pill px-3">Upcoming</span>
                                                <span class="text-muted small fw-bold text-uppercase">
                                                    <?php echo $e['category']; ?>
                                                </span>
                                            </div>
                                            <h5 class="fw-bold mb-2">
                                                <?php echo htmlspecialchars($e['title']); ?>
                                            </h5>
                                            <p class="text-muted small mb-3"><i
                                                    class="fas fa-map-marker-alt text-danger me-2"></i>
                                                <?php echo htmlspecialchars($e['location']); ?>
                                            </p>
                                            <div class="bg-light p-3 rounded-3 mb-3">
                                                <h6 class="mb-0 fw-bold"><i class="far fa-calendar-check me-2"></i>
                                                    <?php echo date('D, M d, Y - h:i A', strtotime($e['event_date'])); ?>
                                                </h6>
                                            </div>
                                            <button class="btn btn-primary-green w-100 rounded-pill shadow-sm">RSVP Now</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- MARKETPLACE TAB -->
            <?php elseif ($tab == 'marketplace'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold">Recently Listed</h4>
                    <button class="btn btn-dark rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#listModal"><i
                            class="fas fa-plus me-2"></i>Sell Item</button>
                </div>

                <?php if (isset($_GET['success']) && $tab == 'marketplace'): ?>
                    <div class="alert alert-success rounded-pill border-0 shadow-sm ps-4 mb-4">
                        <i class="fas fa-check-circle me-2"></i> Your item has been listed for sale!
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <?php if (empty($items)): ?>
                        <p class="text-center py-5">No items for sale.</p>
                    <?php endif; ?>
                    <?php foreach ($items as $i): ?>
                        <div class="col-6 col-md-4 col-lg-3">
                            <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 hover-lift bg-white">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($i['image_url']); ?>" class="card-img-top"
                                        style="height: 180px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-white text-dark shadow-sm rounded-pill px-3 py-2 fw-bold">ETB
                                            <?php echo number_format($i['price']); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body p-3">
                                    <h6 class="fw-bold mb-1 text-truncate">
                                        <?php echo htmlspecialchars($i['title']); ?>
                                    </h6>
                                    <p class="text-muted small mb-3 text-truncate">
                                        <?php echo htmlspecialchars($i['category']); ?>
                                    </p>
                                    <div class="d-grid gap-2">
                                        <a href="view_item.php?id=<?php echo $i['id']; ?>&type=marketplace"
                                            class="btn btn-primary-green btn-sm rounded-pill">View Detail</a>
                                        <a href="tel:<?php echo $i['contact_phone']; ?>"
                                            class="btn btn-outline-dark btn-sm rounded-pill">Call Owner</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- LOST & FOUND TAB -->
            <?php elseif ($tab == 'lostfound'): ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="fw-bold">Help the Community</h4>
                    <button class="btn btn-danger rounded-pill px-4" data-bs-toggle="modal" data-bs-target="#reportModal"><i
                            class="fas fa-bullhorn me-2"></i>Report
                        Lost/Found</button>
                </div>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success rounded-pill border-0 shadow-sm ps-4 mb-4">
                        <i class="fas fa-check-circle me-2"></i> Your report has been posted successfully!
                    </div>
                <?php endif; ?>

                <div class="row g-4">
                    <?php if (empty($lostfound)): ?>
                        <div class="col-12 text-center py-5 bg-white rounded-5 shadow-sm">
                            <i class="fas fa-box-open text-muted mb-3" style="font-size: 3rem;"></i>
                            <p class="text-muted">Everything seems to be where it belongs! No reports yet.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($lostfound as $lf): ?>
                            <div class="col-md-6 col-lg-4">
                                <div class="card border-0 shadow-sm rounded-4 overflow-hidden h-100 bg-white hover-lift">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($lf['image_url'] ?: 'https://images.unsplash.com/photo-1596464716127-f2a82984de30?w=600'); ?>"
                                            class="card-img-top" style="height: 200px; object-fit: cover;">
                                        <div class="position-absolute top-0 start-0 m-3">
                                            <span
                                                class="badge <?php echo $lf['type'] == 'lost' ? 'bg-danger' : 'bg-success'; ?> rounded-pill px-3 py-2 text-uppercase fw-bold">
                                                <?php echo $lf['type']; ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body p-4">
                                        <h5 class="fw-bold mb-2"><?php echo htmlspecialchars($lf['item_name']); ?></h5>
                                        <p class="text-muted small mb-3"><i
                                                class="fas fa-map-marker-alt text-danger me-2"></i><?php echo htmlspecialchars($lf['location']); ?>
                                        </p>
                                        <p class="text-muted small mb-0"><?php echo htmlspecialchars($lf['description']); ?></p>
                                    </div>
                                    <div class="card-footer bg-white border-0 p-4 pt-0">
                                        <hr class="mt-0">
                                        <div class="d-grid gap-2">
                                            <a href="view_item.php?id=<?php echo $lf['id']; ?>&type=lostfound"
                                                class="btn btn-outline-primary-green btn-sm rounded-pill">View Detail & Chat</a>
                                            <div class="d-flex justify-content-between align-items-center mt-1">
                                                <a href="tel:<?php echo $lf['contact_phone']; ?>"
                                                    class="btn btn-dark btn-sm rounded-pill px-3">Call</a>
                                                <small
                                                    class="text-muted"><?php echo date('M d', strtotime($lf['created_at'])); ?></small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- SOCIAL LINKS TAB -->
            <?php elseif ($tab == 'social'): ?>
                <div class="row g-4">
                    <?php foreach ($social_links as $s): ?>
                        <div class="col-md-6 col-lg-4">
                            <a href="?tab=social&view_link=<?php echo urlencode($s['url']); ?>&link_name=<?php echo urlencode($s['name']); ?>"
                                class="text-decoration-none">
                                <div
                                    class="card border-0 shadow-sm rounded-4 p-4 h-100 hover-lift bg-white d-flex flex-row align-items-center">
                                    <div class="bg-primary-green bg-opacity-10 text-primary-green rounded-circle d-flex align-items-center justify-content-center me-4"
                                        style="width: 60px; height: 60px;">
                                        <i class="fas <?php echo $s['icon']; ?> fs-4"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="fw-bold text-dark mb-1">
                                            <?php echo htmlspecialchars($s['name']); ?>
                                        </h6>
                                        <span class="badge bg-light text-muted fw-normal">
                                            <?php echo ucfirst($s['category']); ?>
                                        </span>
                                    </div>
                                    <i class="fas fa-expand text-muted small"></i>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<style>
    .hover-lift {
        transition: all 0.3s ease;
    }

    .hover-lift:hover {
        transform: translateY(-5px);
        box-shadow: 0 1rem 3rem rgba(0, 0, 0, .1) !important;
    }

    .nav-pills .nav-link {
        white-space: nowrap;
    }

    .tab-content.animate-fade-in {
        animation: fadeIn 0.4s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<!-- Report Lost/Found Modal -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">Report Lost or Found Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Report Type</label>
                        <select name="type" class="form-select rounded-3 p-3" required>
                            <option value="lost">I Lost Something</option>
                            <option value="found">I Found Something</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Name</label>
                        <input type="text" name="item_name" class="form-control rounded-3 p-3"
                            placeholder="e.g. Black Leather Wallet" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control rounded-3 p-3" rows="3"
                            placeholder="Describe the item, contents, or unique marks..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Location</label>
                        <input type="text" name="location" class="form-control rounded-3 p-3"
                            placeholder="e.g. Near Edna Mall" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Phone</label>
                        <input type="tel" name="contact_phone" class="form-control rounded-3 p-3" placeholder="09..."
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Upload Image (Optional)</label>
                        <input type="file" name="image" class="form-control rounded-3 p-3" accept="image/*">
                        <small class="text-muted">Browse from your computer or smartphone camera.</small>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="report_lost_found" class="btn btn-primary-green rounded-pill px-4">Post
                        Report</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Sell Item Modal -->
<div class="modal fade" id="listModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 rounded-4 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold">List Item for Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Item Title</label>
                        <input type="text" name="title" class="form-control rounded-3 p-3"
                            placeholder="e.g. Sofa Set for Sale" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Category</label>
                            <select name="category" class="form-select rounded-3 p-3" required>
                                <option value="electronics">Electronics</option>
                                <option value="furniture">Furniture</option>
                                <option value="vehicles">Vehicles</option>
                                <option value="property">Property</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label fw-bold">Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-3 p-3" placeholder="0.00"
                                required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Description</label>
                        <textarea name="description" class="form-control rounded-3 p-3" rows="3"
                            placeholder="Condition, features, age..." required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Contact Phone</label>
                        <input type="tel" name="contact_phone" class="form-control rounded-3 p-3" placeholder="09..."
                            required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Upload Image</label>
                        <input type="file" name="image" class="form-control rounded-3 p-3" accept="image/*" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0 p-4">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="list_marketplace_item"
                        class="btn btn-primary-green rounded-pill px-4">Post Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>