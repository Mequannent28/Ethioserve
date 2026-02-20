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

// Mark messages as read
try {
    $stmt = $pdo->prepare("UPDATE dating_messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
    $stmt->execute([$other_user_id, $user_id]);
} catch (Exception $e) {
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM dating_messages WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?) ORDER BY created_at ASC");
$stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$messages = $stmt->fetchAll();

include '../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<style>
    .chat-container {
        background: linear-gradient(135deg, #fdfbfb 0%, #ebedee 100%);
    }

    .chat-body::-webkit-scrollbar {
        width: 6px;
    }

    .chat-body::-webkit-scrollbar-thumb {
        background-color: rgba(0, 0, 0, 0.1);
        border-radius: 10px;
    }

    .sent-bubble {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%) !important;
        border-bottom-right-radius: 4px !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(27, 94, 32, 0.2) !important;
    }

    .received-bubble {
        background: #212529 !important;
        border-bottom-left-radius: 4px !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15) !important;
    }

    .message-bubble p {
        color: inherit !important;
    }

    .message-bubble {
        transition: all 0.2s ease;
    }

    .message-bubble:hover {
        transform: translateY(-2px);
    }

    .btn-danger {
        background: linear-gradient(135deg, #ff416c, #ff4b2b);
        border: none;
        transition: 0.3s;
    }

    /* Fixed layout to prevent jumping */
    .chat-card {
        height: calc(100vh - 120px);
        display: flex;
        flex-direction: column;
        max-height: 800px;
    }

    .chat-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: white;
        flex-shrink: 0;
    }

    .chat-body {
        flex-grow: 1;
        overflow-y: auto;
        overscroll-behavior: contain;
        background-attachment: fixed;
        /* Keeps background stable */
    }

    .chat-footer {
        flex-shrink: 0;
        background: white;
    }

    @media (max-width: 768px) {
        .chat-container {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
        }

        .chat-card {
            height: calc(100vh - 70px);
            /* Adjust for mobile header/footer */
            max-height: none;
            border-radius: 0 !important;
        }

        .container {
            max-width: 100% !important;
            padding: 0 !important;
        }
    }
</style>

<div class="chat-container py-4 min-vh-100" style="background: #f8f9fa;">
    <div class="container">
        <div class="row justify-content-center g-0">
            <div class="col-lg-8 col-xl-6">
                <div class="card border-0 shadow-lg rounded-5 overflow-hidden chat-card">
                    <!-- Chat Header -->
                    <div class="chat-header p-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <a href="dating_matches.php" class="btn btn-light rounded-circle me-3"><i
                                    class="fas fa-arrow-left"></i></a>
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($other_user['profile_pic']); ?>"
                                    class="rounded-circle me-3 border border-2 border-white shadow-sm" width="50"
                                    height="50" style="object-fit: cover;">
                                <span
                                    class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle"
                                    style="width: 12px; height: 12px; transform: translate(-10px, -2px);"></span>
                            </div>
                            <div class="flex-grow-1">
                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($other_user['full_name']); ?></h6>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="text-muted small" style="font-size: 0.75rem;">
                                        <?php if ($is_matched): ?>
                                            <span class="badge bg-danger rounded-pill fw-normal"
                                                style="font-size: 0.6rem;">Mutual Match</span>
                                        <?php else: ?>
                                            Active Now
                                        <?php endif; ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <a href="dating_video_call.php?user_id=<?php echo $other_user_id; ?>"
                                class="btn btn-light rounded-pill px-3 d-flex align-items-center gap-2 text-danger fw-bold shadow-sm border-0"
                                style="font-size: 0.85rem;">
                                <i class="fas fa-video"></i> <span class="d-none d-md-inline">Video Call</span>
                            </a>
                        </div>
                    </div>

                    <!-- Chat Body -->
                    <div class="chat-body p-3 p-md-4"
                        style="background-image: url('https://www.transparenttextures.com/patterns/cubes.png'); background-color: #f0f2f5;">
                        <div id="message-container">
                            <?php if (empty($messages)): ?>
                                <div class="text-center py-5 no-messages">
                                    <div class="mb-4 animate__animated animate__bounceIn">
                                        <span style="font-size: 4rem;">👋</span>
                                    </div>
                                    <h5 class="fw-bold mb-2">Start the conversation!</h5>
                                    <p class="text-muted small">Send a message to say hi to
                                        <?php echo htmlspecialchars($other_user['full_name']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($messages as $m):
                                $is_me = ($m['sender_id'] == $user_id);
                                ?>
                                <div
                                    class="d-flex mb-4 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?> animate__animated animate__fadeInUp animate__faster">
                                    <div class="message-wrapper" style="max-width: 80%;">
                                        <div class="message-bubble p-3 <?php
                                        echo $is_me
                                            ? 'sent-bubble border-0'
                                            : 'received-bubble border-0';
                                        ?> rounded-4 position-relative">

                                            <?php if ($m['message_type'] === 'image'): ?>
                                                <div class="mb-2 overflow-hidden rounded-3 shadow-sm">
                                                    <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                        class="img-fluid w-100"
                                                        style="max-height: 250px; object-fit: cover; cursor: pointer;"
                                                        onclick="window.open(this.src)">
                                                </div>
                                            <?php endif; ?>

                                            <?php if (!empty($m['message'])): ?>
                                                <p class="mb-0 fw-medium"
                                                    style="font-size: 0.95rem; line-height: 1.5; word-wrap: break-word;">
                                                    <?php echo htmlspecialchars($m['message']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div
                                            class="mt-1 d-flex align-items-center gap-2 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                                            <small class="text-muted" style="font-size: 0.65rem; opacity: 0.8;">
                                                <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                            </small>
                                            <?php if ($is_me): ?>
                                                <i class="fas fa-check-double text-danger" style="font-size: 0.6rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="chatEnd"></div>
                    </div>

                    <!-- Chat Footer -->
                    <div class="chat-footer p-3 bg-white border-top">
                        <form method="POST" enctype="multipart/form-data" class="d-flex align-items-center gap-2"
                            id="datingChatForm">
                            <?php echo csrfField(); ?>
                            <input type="file" name="image" id="datingImageInput" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-light rounded-circle border-0"
                                style="width: 45px; height: 45px;"
                                onclick="document.getElementById('datingImageInput').click()" title="Attach Image">
                                <i class="fas fa-image text-primary fs-5"></i>
                            </button>
                            <div class="flex-grow-1">
                                <input type="text" name="message"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-3"
                                    placeholder="Type your message..." autocomplete="off" style="font-size: 0.95rem;">
                            </div>
                            <button type="submit"
                                class="btn btn-danger rounded-circle shadow-lg d-flex align-items-center justify-content-center"
                                style="width: 50px; height: 50px;">
                                <i class="fas fa-paper-plane fs-5"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chatBody = document.querySelector('.chat-body');
        const messageContainer = document.getElementById('message-container');

        // Initial scroll
        chatBody.scrollTop = chatBody.scrollHeight;

        // Auto submit image
        document.getElementById('datingImageInput').addEventListener('change', function () {
            if (this.files.length > 0) {
                document.getElementById('datingChatForm').submit();
            }
        });

        // Polling with safety to prevent jumps
        setInterval(() => {
            fetch(window.location.href)
                .then(r => r.text())
                .then(html => {
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newNode = doc.getElementById('message-container');

                    if (newNode && messageContainer.innerHTML !== newNode.innerHTML) {
                        const wasAtBottom = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 100;
                        messageContainer.innerHTML = newNode.innerHTML;
                        if (wasAtBottom) {
                            chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
                        }
                    }
                });
        }, 5000);
    </script>
</div>

<?php include '../includes/footer.php'; ?>