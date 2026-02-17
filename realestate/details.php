<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

if (!isset($_GET['id'])) {
    header("Location: properties.php");
    exit();
}

$id = (int) $_GET['id'];
$stmt = $pdo->prepare("SELECT p.*, u.full_name as agent_name, u.phone as agent_phone, u.email as agent_email 
                       FROM real_estate_properties p 
                       JOIN users u ON p.agent_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$property = $stmt->fetch();

if (!$property) {
    header("Location: properties.php");
    exit();
}

// Handle Inquiry
$inquiry_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_inquiry'])) {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $message = sanitize($_POST['message']);

    $stmt = $pdo->prepare("INSERT INTO real_estate_inquiries (property_id, user_id, name, email, phone, message) VALUES (?, ?, ?, ?, ?, ?)");
    $user_id = isLoggedIn() ? getCurrentUserId() : NULL;

    if ($stmt->execute([$id, $user_id, $name, $email, $phone, $message])) {
        $inquiry_success = true;
    }
}

$page_title = $property['title'] . " - Real Estate";
require_once '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Real Estate</a></li>
            <li class="breadcrumb-item"><a href="properties.php">Properties</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($property['title']); ?>
            </li>
        </ol>
    </nav>

    <?php if ($inquiry_success): ?>
        <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
            <strong><i class="fas fa-check-circle me-2"></i> Inquiry Sent!</strong> The agent will contact you soon.
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="row g-5">
        <!-- Property Images & Details -->
        <div class="col-lg-8">
            <div class="position-relative overflow-hidden rounded-4 mb-4 shadow-sm">
                <img src="<?php echo htmlspecialchars($property['image_url']); ?>" class="img-fluid w-100"
                    style="height: 500px; object-fit: cover;">
                <div class="position-absolute top-0 end-0 m-4">
                    <span class="badge bg-white text-dark shadow fw-bold px-3 py-2 fs-6">
                        <?php echo ucfirst($property['type']); ?>
                    </span>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h1 class="fw-bold mb-0">
                    <?php echo htmlspecialchars($property['title']); ?>
                </h1>
                <h2 class="text-primary-green fw-bold mb-0">
                    <?php echo number_format($property['price']); ?> ETB
                </h2>
            </div>

            <p class="text-muted fs-5 mb-4"><i class="fas fa-map-marker-alt text-danger me-2"></i>
                <?php echo htmlspecialchars($property['location']); ?>,
                <?php echo htmlspecialchars($property['city']); ?>
            </p>

            <div class="row g-4 mb-5 text-center">
                <div class="col-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <i class="fas fa-bed fs-3 text-muted mb-2"></i>
                        <div class="fw-bold">
                            <?php echo $property['bedrooms']; ?> Bedrooms
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <i class="fas fa-bath fs-3 text-muted mb-2"></i>
                        <div class="fw-bold">
                            <?php echo $property['bathrooms']; ?> Bathrooms
                        </div>
                    </div>
                </div>
                <div class="col-4">
                    <div class="p-3 bg-light rounded-3 h-100 border">
                        <i class="fas fa-ruler-combined fs-3 text-muted mb-2"></i>
                        <div class="fw-bold">
                            <?php echo $property['area_sqm']; ?> m²
                        </div>
                    </div>
                </div>
            </div>

            <h4 class="fw-bold mb-3">Description</h4>
            <div class="bg-white p-4 rounded-4 shadow-sm border mb-4 text-muted" style="line-height: 1.8;">
                <?php echo nl2br(htmlspecialchars($property['description'])); ?>
            </div>

            <!-- Agent Info (Mobile Only) -->
            <div class="d-block d-lg-none mt-4">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 text-center">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($property['agent_name']); ?>&background=random"
                            class="rounded-circle mb-3" width="80">
                        <h5 class="fw-bold">
                            <?php echo htmlspecialchars($property['agent_name']); ?>
                        </h5>
                        <p class="text-muted small">Real Estate Agent</p>
                        <a href="tel:<?php echo htmlspecialchars($property['agent_phone']); ?>"
                            class="btn btn-primary-green w-100 rounded-pill mb-2"><i class="fas fa-phone me-2"></i> Call
                            Agent</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar / Inquiry Form -->
        <div class="col-lg-4">
            <div class="sticky-top" style="top: 100px;">
                <div class="card border-0 shadow rounded-4 overflow-hidden">
                    <div class="card-header bg-primary-green text-white p-4 text-center">
                        <h5 class="mb-0 fw-bold">Contact Agent</h5>
                        <p class="mb-0 small opacity-75">Interested in this property?</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center mb-4">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($property['agent_name']); ?>&background=random"
                                class="rounded-circle me-3" width="50">
                            <div>
                                <h6 class="fw-bold mb-0">
                                    <?php echo htmlspecialchars($property['agent_name']); ?>
                                </h6>
                                <small class="text-muted">Agent</small>
                            </div>
                        </div>

                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Your Name</label>
                                <input type="text" name="name" class="form-control rounded-pill bg-light border-0 px-3"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Email Address</label>
                                <input type="email" name="email"
                                    class="form-control rounded-pill bg-light border-0 px-3" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Phone Number</label>
                                <input type="tel" name="phone" class="form-control rounded-pill bg-light border-0 px-3"
                                    required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">Message</label>
                                <textarea name="message" class="form-control rounded-4 bg-light border-0 p-3" rows="4"
                                    required>I am interested in <?php echo htmlspecialchars($property['title']); ?>. Please contact me.</textarea>
                            </div>
                            <button type="submit" name="send_inquiry"
                                class="btn btn-dark w-100 rounded-pill fw-bold py-2">Send Inquiry</button>
                        </form>

                        <hr class="my-4 text-muted opacity-25">

                        <div class="text-center">
                            <p class="small text-muted mb-2">Or call directly:</p>
                            <a href="tel:<?php echo htmlspecialchars($property['agent_phone']); ?>"
                                class="fw-bold text-decoration-none fs-5 text-primary-green">
                                <i class="fas fa-phone-alt me-2"></i>
                                <?php echo htmlspecialchars($property['agent_phone']); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>