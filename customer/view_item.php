<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;
$item_id = $_GET['id'] ?? null;
$type = $_GET['type'] ?? 'marketplace';

if (!$item_id) {
    header("Location: community.php");
    exit();
}

// Fetch Item Details
if ($type == 'marketplace') {
    $stmt = $pdo->prepare("SELECT m.*, u.name as seller_name FROM comm_marketplace m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = ?");
} else {
    $stmt = $pdo->prepare("SELECT lf.*, u.name as seller_name FROM comm_lost_found lf LEFT JOIN users u ON lf.user_id = u.id WHERE lf.id = ?");
}
$stmt->execute([$item_id]);
$item = $stmt->fetch();

if (!$item) {
    die("Item not found.");
}

// Mark messages as read
if ($user_id) {
    $stmt = $pdo->prepare("UPDATE comm_messages SET is_read = 1 WHERE item_id = ? AND item_type = ? AND receiver_id = ?");
    $stmt->execute([$item_id, $type, $user_id]);
}

// Handle Chat Message
$msg_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    if (!$user_id) {
        header("Location: ../login.php");
        exit();
    }
    $message = sanitize($_POST['message']);
    $receiver_id = $item['user_id'];

    if ($receiver_id != $user_id) {
        $stmt = $pdo->prepare("INSERT INTO comm_messages (sender_id, receiver_id, item_id, item_type, message) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $receiver_id, $item_id, $type, $message]);
        $msg_success = true;
    }
}

include '../includes/header.php';
?>

<div class="item-detail-page py-5 min-vh-100" style="background: #f0f2f5;">
    <div class="container">
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="community.php" class="text-primary-green">Community</a></li>
                <li class="breadcrumb-item"><a
                        href="community.php?tab=<?php echo $type == 'marketplace' ? 'marketplace' : 'lostfound'; ?>"
                        class="text-primary-green">
                        <?php echo ucfirst($type); ?>
                    </a></li>
                <li class="breadcrumb-item active">
                    <?php echo htmlspecialchars($item['title'] ?? $item['item_name']); ?>
                </li>
            </ol>
        </nav>

        <div class="row g-4">
            <!-- Left: Image and Info -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                    <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?w=800'); ?>"
                        class="img-fluid w-100" style="max-height: 500px; object-fit: cover;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h2 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($item['title'] ?? $item['item_name']); ?>
                                </h2>
                                <span class="badge bg-light text-primary-green px-3 py-2 rounded-pill">
                                    <?php echo htmlspecialchars($item['category'] ?? ucfirst($item['type'])); ?>
                                </span>
                            </div>
                            <?php if (isset($item['price'])): ?>
                                <h3 class="text-primary-green fw-bold">ETB
                                    <?php echo number_format($item['price']); ?>
                                </h3>
                            <?php endif; ?>
                        </div>

                        <div class="mb-4">
                            <h5 class="fw-bold"><i class="fas fa-align-left me-2 text-muted"></i>Description</h5>
                            <p class="text-muted lead">
                                <?php echo nl2br(htmlspecialchars($item['description'])); ?>
                            </p>
                        </div>

                        <?php if (isset($item['location'])): ?>
                            <div class="mb-4">
                                <h5 class="fw-bold"><i class="fas fa-map-marker-alt me-2 text-danger"></i>Location</h5>
                                <p class="text-muted">
                                    <?php echo htmlspecialchars($item['location']); ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <div class="d-flex align-items-center p-3 bg-light rounded-4">
                            <div class="bg-primary-green text-white rounded-circle d-flex align-items-center justify-content-center me-3"
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-user"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">Listed by</small>
                                <h6 class="mb-0 fw-bold">
                                    <?php echo htmlspecialchars($item['seller_name'] ?: 'Community Member'); ?>
                                </h6>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right: Contact / Chat -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 100px;">
                    <h5 class="fw-bold mb-4">Contact Seller</h5>

                    <a href="tel:<?php echo $item['contact_phone']; ?>"
                        class="btn btn-primary-green w-100 rounded-pill py-3 mb-3 fw-bold">
                        <i class="fas fa-phone-alt me-2"></i> Call Now (+251...)
                    </a>

                    <hr class="my-4">

                    <h6 class="fw-bold mb-3">Community Chat</h6>
                    <div class="chat-history mb-3 p-3 bg-light rounded-4 overflow-auto" style="max-height: 300px;">
                        <?php
                        $stmt = $pdo->prepare("SELECT * FROM comm_messages WHERE item_id = ? AND item_type = ? AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at ASC");
                        $stmt->execute([$item_id, $type, $user_id, $item['user_id'], $item['user_id'], $user_id]);
                        $chat_msgs = $stmt->fetchAll();

                        if (empty($chat_msgs)): ?>
                            <p class="text-muted small text-center my-4">No messages yet. Start the conversation!</p>
                        <?php else: ?>
                            <?php foreach ($chat_msgs as $m): ?>
                                <div
                                    class="mb-2 d-flex <?php echo $m['sender_id'] == $user_id ? 'justify-content-end' : ''; ?>">
                                    <div class="p-2 px-3 rounded-4 small <?php echo $m['sender_id'] == $user_id ? 'bg-primary-green text-white' : 'bg-white border text-dark'; ?>"
                                        style="max-width: 85%;">
                                        <?php echo htmlspecialchars($m['message']); ?>
                                        <div class="<?php echo $m['sender_id'] == $user_id ? 'text-white-50' : 'text-muted'; ?>"
                                            style="font-size: 0.65rem;">
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <?php if ($msg_success): ?>
                        <div class="alert alert-success border-0 rounded-4 small py-2 mb-3">
                            <i class="fas fa-check-circle me-1"></i> Message sent!
                        </div>
                    <?php endif; ?>

                    <?php if ($user_id == $item['user_id']): ?>
                        <div class="alert alert-info border-0 rounded-4 small">
                            You are managing this listing. Reply to interested buyers from your Messages.
                        </div>
                    <?php else: ?>
                        <form method="POST">
                            <div class="input-group">
                                <textarea name="message" class="form-control rounded-4 border-light p-3" rows="2"
                                    placeholder="Type your message..." required></textarea>
                                <button type="submit" name="send_message" class="btn btn-dark rounded-4 px-3 ms-2">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <div class="mt-4 p-3 bg-light rounded-4">
                        <small class="text-muted d-block mb-1"><i class="fas fa-shield-alt me-2"></i>Safety Tips</small>
                        <ul class="text-muted small mb-0 ps-3">
                            <li>Meet in public places</li>
                            <li>Verify items before payment</li>
                            <li>No advance payments</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>