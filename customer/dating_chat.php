<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$other_user_id = intval($_GET['user_id'] ?? 0);

// Verify Match
$stmt = $pdo->prepare("SELECT id FROM dating_matches WHERE (user1_id = ? AND user2_id = ?) OR (user1_id = ? AND user2_id = ?)");
$stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
if (!$stmt->fetch()) {
    header("Location: dating_matches.php");
    exit();
}

// Fetch Other User
$stmt = $pdo->prepare("SELECT u.full_name, p.profile_pic, p.last_active FROM users u JOIN dating_profiles p ON u.id = p.user_id WHERE u.id = ?");
$stmt->execute([$other_user_id]);
$other_user = $stmt->fetch();

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['message'])) {
    $msg = sanitize($_POST['message']);
    try {
        $stmt = $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $other_user_id, $msg]);
        header("Location: dating_chat.php?user_id=" . $other_user_id);
        exit();
    } catch (Exception $e) {
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
                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle" data-bs-toggle="dropdown"><i
                                    class="fas fa-ellipsis-v"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                                <li><a class="dropdown-item py-2" href="#"><i class="fas fa-user-circle me-2"></i>View
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
                                <i class="fas fa-hand-sparkles mb-2 fs-2"></i>
                                <p>You matched! Say something nice.</p>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($messages as $m):
                            $is_me = ($m['sender_id'] == $user_id);
                            ?>
                            <div
                                class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="message-bubble p-3 <?php echo $is_me ? 'bg-danger text-white rounded-start-pill rounded-bottom-pill' : 'bg-white text-dark shadow-sm rounded-end-pill rounded-bottom-pill'; ?>"
                                    style="max-width: 80%;">
                                    <p class="mb-0">
                                        <?php echo htmlspecialchars($m['message']); ?>
                                    </p>
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
                        <form method="POST" class="d-flex gap-2">
                            <?php echo csrfField(); ?>
                            <button type="button" class="btn btn-light rounded-circle"><i
                                    class="fas fa-image"></i></button>
                            <input type="text" name="message" class="form-control rounded-pill border-0 bg-light px-4"
                                placeholder="Type a message..." required autocomplete="off">
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
</script>

<?php include '../includes/footer.php'; ?>