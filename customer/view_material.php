<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT em.*, u.username, u.full_name as seller_name, u.email as seller_email, u.created_at as seller_since 
                       FROM exchange_materials em 
                       JOIN users u ON em.user_id = u.id 
                       WHERE em.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    redirectWithMessage('exchange_material.php', 'danger', 'Item not found.');
}

include '../includes/header.php';
?>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php">Home</a></li>
            <li class="breadcrumb-item"><a href="exchange_material.php">Exchange</a></li>
            <li class="breadcrumb-item active" aria-current="page">
                <?php echo htmlspecialchars($item['category']); ?>
            </li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Item Gallery -->
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="main-image-wrapper p-2 bg-light text-center" style="height: 500px;">
                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: '../assets/img/placeholder.jpg'); ?>"
                        class="h-100 mw-100 object-fit-contain" alt="<?php echo htmlspecialchars($item['title']); ?>">
                </div>
            </div>

            <div class="mt-5">
                <h4 class="fw-bold mb-4">Description</h4>
                <div class="card border-0 shadow-sm rounded-4 p-4">
                    <p class="mb-0 fs-5 lh-lg" style="white-space: pre-line;">
                        <?php echo htmlspecialchars($item['description']); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Sticky Sidebar -->
        <div class="col-lg-5">
            <div class="sticky-top" style="top: 100px;">
                <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                    <h1 class="fw-bold h2 mb-2">
                        <?php echo htmlspecialchars($item['title']); ?>
                    </h1>
                    <div class="d-flex align-items-center mb-3">
                        <span class="badge bg-primary-green rounded-pill me-2">
                            <?php echo htmlspecialchars($item['category']); ?>
                        </span>
                        <span
                            class="badge <?php echo $item['condition'] == 'new' ? 'bg-success' : 'bg-warning text-dark'; ?> rounded-pill">
                            <?php echo ucfirst($item['condition']); ?> condition
                        </span>
                    </div>

                    <h2 class="display-5 fw-extrabold text-primary-green mb-4">
                        <?php echo number_format($item['price'], 2); ?> <small class="text-muted fs-4">ETB</small>
                    </h2>

                    <div class="p-3 bg-light rounded-3 mb-4">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-map-marker-alt text-danger me-2"></i>
                            <span class="fw-bold">Location:</span>
                            <span class="ms-2">
                                <?php echo htmlspecialchars($item['location']); ?>
                            </span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock text-muted me-2"></i>
                            <span class="fw-bold">Posted:</span>
                            <span class="ms-2">
                                <?php echo time_ago($item['created_at']); ?>
                            </span>
                        </div>
                    </div>

                    <div class="d-grid gap-3">
                        <button class="btn btn-primary-green btn-lg rounded-pill fw-bold py-3 shadow" id="show-contact">
                            <i class="fas fa-phone-alt me-2"></i> Show Contact Number
                        </button>
                        <div id="contact-area"
                            class="alert alert-warning border-2 border-warning text-center d-none py-3 h4 fw-bold">
                            <?php echo htmlspecialchars($item['phone']); ?>
                        </div>
                        <a href="mailto:<?php echo htmlspecialchars($item['seller_email']); ?>"
                            class="btn btn-outline-primary btn-lg rounded-pill fw-bold py-3">
                            <i class="fas fa-envelope me-2"></i> Send Message
                        </a>
                    </div>
                </div>

                <!-- Seller Profile -->
                <div class="card border-0 shadow-sm rounded-4 p-4 text-center">
                    <div class="mb-3">
                        <div class="avatar-circle bg-light d-inline-flex align-items-center justify-content-center"
                            style="width: 80px; height: 80px; border-radius: 50%;">
                            <i class="fas fa-user-circle text-muted" style="font-size: 3rem;"></i>
                        </div>
                    </div>
                    <h5 class="fw-bold mb-1">
                        <?php echo htmlspecialchars($item['seller_name']); ?>
                    </h5>
                    <p class="text-muted small mb-1">@<?php echo htmlspecialchars($item['username']); ?></p>
                    <p class="text-muted small mb-3">Member since
                        <?php echo date('M Y', strtotime($item['seller_since'])); ?>
                    </p>

                    <a href="seller_profile.php?id=<?php echo $item['user_id']; ?>"
                        class="btn btn-sm btn-light rounded-pill px-4">View Profile</a>
                </div>

                <!-- Safety Tips -->
                <div class="card border-0 bg-light-warning rounded-4 p-4 mt-4">
                    <h6 class="fw-bold mb-3"><i class="fas fa-shield-alt me-2 text-warning"></i> Safety Tips</h6>
                    <ul class="small mb-0 list-unstyled">
                        <li class="mb-2">1. Meet the seller in a public place</li>
                        <li class="mb-2">2. Inspect the item before you buy</li>
                        <li class="mb-0">3. Only pay after collecting the item</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('show-contact').addEventListener('click', function () {
        this.classList.add('d-none');
        document.getElementById('contact-area').classList.remove('d-none');
    });
</script>

<style>
    .bg-light-warning {
        background-color: #FFFDE7;
    }

    .object-fit-contain {
        object-fit: contain;
    }
</style>

<?php include '../includes/footer.php'; ?>