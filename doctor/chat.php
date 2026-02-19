<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'doctor') {
    redirectWithMessage('../login.php', 'warning', 'Please login as a doctor.');
}

$doctor_user_id = getCurrentUserId();
$customer_id = intval($_GET['customer_id'] ?? 0);

// Fetch provider (doctor) ID for this user
$stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
$stmt->execute([$doctor_user_id]);
$provider = $stmt->fetch();
$provider_id = $provider['id'] ?? 0;

if (!$customer_id || !$provider_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch customer info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirectWithMessage('dashboard.php', 'danger', 'Patient not found.');
}

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = sanitize($_POST['message'] ?? '');
    $type = sanitize($_POST['message_type'] ?? 'text');
    $attachment = null;

    // Handle File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/chat/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $attachment = 'uploads/chat/' . $file_name;
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $type = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'file';
        }
    }

    if (!empty($msg) || $attachment) {
        try {
            $stmt = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message, message_type, attachment_url) VALUES (?, 'doctor', ?, ?, ?, ?, ?)");
            $stmt->execute([$provider_id, $provider_id, $customer_id, $msg, $type, $attachment]);
            header("Location: chat.php?customer_id=" . $customer_id);
            exit();
        } catch (Exception $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    }
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM doctor_messages WHERE provider_id = ? AND customer_id = ? ORDER BY created_at ASC");
$stmt->execute([$provider_id, $customer_id]);
$messages = $stmt->fetchAll();

// Mark messages from customer as read
$pdo->prepare("UPDATE doctor_messages SET is_read = 1 WHERE provider_id = ? AND customer_id = ? AND sender_type = 'customer' AND is_read = 0")->execute([$provider_id, $customer_id]);

$base_url = BASE_URL;
include '../includes/header.php';
?>

<div class="doctor-chat-page py-4 bg-light min-vh-100">
    <div class="container text-start">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <!-- Header -->
                    <div
                        class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm rounded-circle me-3">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold me-3"
                                style="width:45px;height:45px;">
                                <?php echo strtoupper(substr($customer['full_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($customer['full_name']); ?></h6>
                                <span class="badge bg-success bullet-dot me-1"></span><small
                                    class="text-muted">Patient</small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="video_call.php?customer_id=<?php echo $customer_id; ?>"
                                class="btn btn-primary btn-sm rounded-pill px-3">
                                <i class="fas fa-video me-1"></i> Start Call
                            </a>
                        </div>
                    </div>

                    <!-- Chat Body -->
                    <div class="card-body p-4" id="chatBody" style="height: 500px; overflow-y: auto;">
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-5">
                                <p class="text-muted">No messages yet. Start the conversation.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($messages as $m):
                            $is_me = ($m['sender_type'] === 'doctor'); ?>
                            <div
                                class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="message-bubble p-3 rounded-4 <?php echo $is_me ? 'bg-primary text-white shadow' : 'bg-white border text-dark'; ?>"
                                    style="max-width: 75%; border-radius: <?php echo $is_me ? '20px 20px 4px 20px' : '20px 20px 20px 4px'; ?>;">
                                    <div class="message-content">
                                        <?php if ($m['message_type'] === 'text'): ?>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                                        <?php elseif ($m['message_type'] === 'image'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank">
                                                    <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                        class="img-fluid rounded-3 chat-image">
                                                </a>
                                            </div>
                                            <?php if (!empty($m['message'])): ?>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($m['message']); ?></p>
                                            <?php endif; ?>
                                        <?php elseif ($m['message_type'] === 'location'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $m['message']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle"><i
                                                            class="fas fa-map-marker-alt text-danger"></i></div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">Shared
                                                            Location</div>
                                                        <div class="text-muted" style="font-size: 0.65rem;">View on Maps</div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php elseif ($m['message_type'] === 'file'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle"><i
                                                            class="fas fa-file text-primary"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="fw-bold text-dark text-truncate"
                                                            style="font-size: 0.75rem; max-width: 150px;">
                                                            <?php echo basename($m['attachment_url']); ?>
                                                        </div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div
                                        class="d-flex align-items-center gap-1 <?php echo $is_me ? 'justify-content-end' : ''; ?>">
                                        <small class="opacity-75" style="font-size: 0.65rem;">
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                        </small>
                                        <?php if ($is_me): ?>
                                            <i class="fas fa-check-double"
                                                style="font-size: 0.6rem; color: <?php echo $m['is_read'] ? '#4FC3F7' : 'rgba(255,255,255,0.5)'; ?>;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-white border-top p-3">
                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-end"
                            id="chatForm">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="message_type" id="messageType" value="text">

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light rounded-circle" id="attachBtn"
                                    style="width:40px;height:40px;"><i class="fas fa-paperclip text-muted"></i></button>
                                <button type="button" class="btn btn-light rounded-circle" id="locationBtn"
                                    style="width:40px;height:40px;"><i
                                        class="fas fa-map-marker-alt text-muted"></i></button>
                            </div>

                            <input type="file" name="attachment" id="fileInput" class="d-none">

                            <div class="flex-grow-1">
                                <div id="filePreview" class="bg-light p-2 mb-2 rounded-3 d-none">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 small">
                                            <i class="fas fa-file text-primary"></i> <span id="fileName"
                                                class="text-truncate" style="max-width: 150px;"></span>
                                        </div>
                                        <button type="button" class="btn-close" style="font-size: 0.6rem;"
                                            id="clearFile"></button>
                                    </div>
                                </div>
                                <textarea name="message" id="messageInput"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-2"
                                    placeholder="Type a message..." required rows="1" style="resize:none;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-circle shadow-sm flex-shrink-0"
                                style="width: 45px; height: 45px;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-image {
        max-height: 200px;
        border-radius: 10px;
    }
</style>

<script>
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    const messageInput = document.getElementById('messageInput');
    const messageType = document.getElementById('messageType');
    const locationBtn = document.getElementById('locationBtn');
    const attachBtn = document.getElementById('attachBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const clearFile = document.getElementById('clearFile');

    locationBtn.onclick = () => {
        if (!navigator.geolocation) { alert("Not supported"); return; }
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        navigator.geolocation.getCurrentPosition(pos => {
            messageInput.value = `https://www.google.com/maps?q=${pos.coords.latitude},${pos.coords.longitude}`;
            messageType.value = 'location';
            document.getElementById('chatForm').submit();
        }, () => { alert("Error"); locationBtn.innerHTML = '<i class="fas fa-map-marker-alt text-muted"></i>'; });
    };

    attachBtn.onclick = () => fileInput.click();
    fileInput.onchange = function () {
        if (this.files[0]) { fileName.textContent = this.files[0].name; filePreview.classList.remove('d-none'); messageInput.required = false; }
    };
    clearFile.onclick = () => { fileInput.value = ''; filePreview.classList.add('d-none'); messageInput.required = true; };
</script>

<?php include '../includes/footer.php'; ?>