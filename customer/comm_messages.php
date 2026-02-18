<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;
if (!$user_id) {
    header("Location: ../login.php");
    exit();
}

// Fetch distinct conversations for the current user
$stmt = $pdo->prepare("
    SELECT m.*, 
    u.name as other_user_name,
    CASE 
        WHEN m.item_type = 'marketplace' THEN (SELECT title FROM comm_marketplace WHERE id = m.item_id)
        ELSE (SELECT item_name FROM comm_lost_found WHERE id = m.item_id)
    END as item_title
    FROM comm_messages m
    LEFT JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
    WHERE (m.sender_id = ? OR m.receiver_id = ?) 
    AND u.id != ?
    GROUP BY m.item_id, m.item_type, 
    CASE WHEN m.sender_id = ? THEN m.receiver_id ELSE m.sender_id END
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id]);
$conversations = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="messages-page py-5 min-vh-100" style="background: #f0f2f5;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="fw-bold"><i class="fas fa-comments me-2 text-primary-green"></i>Community Messages</h2>
                    <a href="community.php" class="btn btn-sm btn-outline-dark rounded-pill px-3">Back to Community</a>
                </div>

                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="list-group list-group-flush">
                        <?php if (empty($conversations)): ?>
                            <div class="p-5 text-center bg-white">
                                <i class="fas fa-comment-slash text-muted mb-3" style="font-size: 3rem;"></i>
                                <p class="text-muted">No messages yet. When you chat with buyers or sellers, they will
                                    appear here.</p>
                                <a href="community.php" class="btn btn-primary-green rounded-pill px-4 mt-2">Explore
                                    Community</a>
                            </div>
                        <?php else: ?>
                            <?php foreach ($conversations as $c):
                                $other_id = ($c['sender_id'] == $user_id) ? $c['receiver_id'] : $c['sender_id'];
                                ?>
                                <a href="view_item.php?id=<?php echo $c['item_id']; ?>&type=<?php echo $c['item_type']; ?>"
                                    class="list-group-item list-group-item-action p-4 border-0 hover-lift bg-white mb-1 rounded-3 mx-2">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary-green bg-opacity-10 text-primary-green rounded-circle d-flex align-items-center justify-content-center me-3"
                                            style="width: 50px; height: 50px; flex-shrink: 0;">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div class="flex-grow-1 overflow-hidden">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="fw-bold mb-0 text-dark">
                                                    <?php echo htmlspecialchars($c['other_user_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <?php echo date('M d, h:i A', strtotime($c['created_at'])); ?>
                                                </small>
                                            </div>
                                            <p class="mb-1 text-primary-green small fw-bold"><i class="fas fa-tag me-1"></i>
                                                <?php echo htmlspecialchars($c['item_title']); ?>
                                            </p>
                                            <p class="mb-0 text-muted text-truncate small">
                                                <?php echo ($c['sender_id'] == $user_id ? 'You: ' : '') . htmlspecialchars($c['message']); ?>
                                            </p>
                                        </div>
                                        <?php if ($c['is_read'] == 0 && $c['receiver_id'] == $user_id): ?>
                                            <span class="badge bg-danger rounded-pill ms-2">New</span>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .hover-lift {
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .hover-lift:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        z-index: 1;
    }

    .list-group-item-action:active {
        background-color: #f8f9fa;
        color: inherit;
    }
</style>

<?php include '../includes/footer.php'; ?>