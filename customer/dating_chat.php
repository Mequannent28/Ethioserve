<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$other_user_id = intval($_GET['user_id'] ?? 0);

if (!$other_user_id) {
    header("Location: dating.php");
    exit();
}

// Fetch Other User (no match required — any logged-in user can message)
try {
    $stmt = $pdo->prepare("
        SELECT u.full_name, u.email, u.phone, p.profile_pic, p.age, p.location_name, p.bio, p.last_active
        FROM users u
        LEFT JOIN dating_profiles p ON u.id = p.user_id
        WHERE u.id = ?
    ");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();
} catch (Exception $e) {
    $other_user = null;
}

if (!$other_user) {
    header("Location: dating.php");
    exit();
}

// Check if mutual match (for contact reveal)
$is_matched = false;
try {
    $stmt = $pdo->prepare("SELECT id FROM dating_matches WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $is_matched = (bool) $stmt->fetch();
} catch (Exception $e) {
}

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = sanitize($_POST['message'] ?? '');
    $type = 'text';
    $attachment = null;

    // Handle File Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/dating_chat/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES['image']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            $attachment = 'uploads/dating_chat/' . $file_name;
            $type = 'image';
        }
    }

    if (!empty($msg) || $attachment) {
        try {
            $stmt = $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type, attachment_url) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $other_user_id, $msg, $type, $attachment]);
            header("Location: dating_chat.php?user_id=" . $other_user_id);
            exit();
        } catch (Exception $e) {
        }
    }
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM dating_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$messages = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="chat-container py-5 min-vh-100" style="background: #f8f9fa;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card border-0 shadow-lg rounded-5 overflow-hidden">
                    <!-- Chat Header -->
                    <div class="bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="dating_matches.php" class="btn btn-light rounded-circle me-3"><i
                                    class="fas fa-arrow-left"></i></a>
                            <img src="<?php echo htmlspecialchars($other_user['profile_pic']); ?>"
                                class="rounded-circle me-3" width="50" height="50" style="object-fit: cover;">
                            <div>
                                <h6 class="fw-bold mb-0">
                                    <?php echo htmlspecialchars($other_user['full_name']); ?>
                                </h6>
                                <span class="small text-success"><i class="fas fa-circle me-1 small"></i> Online</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="dating_video_call.php?user_id=<?php echo $other_user_id; ?>"
                                class="btn btn-light rounded-circle" title="Video Call">
                                <i class="fas fa-video text-danger"></i>
                            </a>
                            <div class="dropdown">

                                <button class="btn btn-light rounded-circle" data-bs-toggle="dropdown"><i
                                        class="fas fa-ellipsis-v"></i></button>
                                <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                                    <li><a class="dropdown-item py-2" href="#"><i
                                                class="fas fa-user-circle me-2"></i>View
                                            Profile</a></li>
                                    <li><a class="dropdown-item py-2 text-danger" href="#"><i
                                                class="fas fa-flag me-2"></i>Report User</a></li>
                                </ul>
                            </div>
                        </div>

                        <!-- Chat Body -->
                        <div class="chat-body p-4 bg-light" style="height: 500px; overflow-y: auto;">
                            <?php if (empty($messages)): ?>
                                <div class="text-center py-5 text-muted">
                                    <div style="font-size:2.5rem;" class="mb-2">👋</div>
                                    <p class="fw-bold mb-1">Start the conversation!</p>
                                    <p class="small">Say hi to <?php echo htmlspecialchars($other_user['full_name']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($messages as $m):
                                $is_me = ($m['sender_id'] == $user_id);
                                ?>
                                <div
                                    class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                                    <div class="message-bubble p-3 <?php echo $is_me ? 'bg-danger text-white rounded-start-pill rounded-bottom-pill' : 'bg-white text-dark shadow-sm rounded-end-pill rounded-bottom-pill'; ?>"
                                        style="max-width: 80%;">
                                        <?php if ($m['message_type'] === 'image'): ?>
                                            <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                class="img-fluid rounded-4 mb-2 shadow-sm"
                                                style="max-height: 200px; cursor: pointer;" onclick="window.open(this.src)">
                                        <?php endif; ?>
                                        <?php if (!empty($m['message'])): ?>
                                            <p class="mb-0">
                                                <?php echo htmlspecialchars($m['message']); ?>
                                            </p>
                                        <?php endif; ?>
                                        <small class="d-block mt-1 opacity-75" style="font-size: 0.6rem;">
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            <div id="chatEnd"></div>
                        </div>

                        <!-- Chat Footer -->
                        <div class="chat-footer p-3 bg-white border-top">
                            <form method="POST" enctype="multipart/form-data" class="d-flex gap-2" id="datingChatForm">
                                <?php echo csrfField(); ?>
                                <input type="file" name="image" id="datingImageInput" class="d-none" accept="image/*">
                                <button type="button" class="btn btn-light rounded-circle"
                                    onclick="document.getElementById('datingImageInput').click()">
                                    <i class="fas fa-image text-muted"></i>
                                </button>
                                <input type="text" name="message"
                                    class="form-control rounded-pill border-0 bg-light px-4"
                                    placeholder="Type a message..." autocomplete="off">
                                <button type="submit" class="btn btn-danger rounded-circle"
                                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-paper-plane"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto scroll to bottom
        const chatBody = document.querySelector('.chat-body');
        chatBody.scrollTop = chatBody.scrollHeight;

        // Auto submit image
        document.getElementById('datingImageInput').addEventListener('change', function () {
            if (this.files.length > 0) {
                document.getElementById('datingChatForm').submit();
            }
        });

        // Auto-refresh chat every 5s
        setInterval(() => {
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newBody = doc.querySelector('.chat-body');
                    if (newBody && chatBody.innerHTML !== newBody.innerHTML) {
                        const wasAtBottom = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 50;
                        chatBody.innerHTML = newBody.innerHTML;
                        if (wasAtBottom) chatBody.scrollTop = chatBody.scrollHeight;
                    }
                });
        }, 5000);
    </script>

    <?php include '../includes/footer.php'; ?>