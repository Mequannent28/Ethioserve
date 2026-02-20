<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch mutual matches with unread counts and last message info
try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id as match_id, 
            u.id as other_user_id, 
            u.full_name, 
            p.profile_pic, 
            p.bio,
            (SELECT message FROM dating_messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_msg,
            (SELECT created_at FROM dating_messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_msg_time,
            (SELECT COUNT(*) FROM dating_messages WHERE sender_id = u.id AND receiver_id = ? AND is_read = 0) as unread_count
        FROM dating_matches m
        JOIN users u ON (m.user1_id = u.id OR m.user2_id = u.id) AND u.id != ?
        JOIN dating_profiles p ON u.id = p.user_id
        WHERE m.user1_id = ? OR m.user2_id = ?
        ORDER BY last_msg_time DESC, m.created_at DESC
    ");
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $matches = $stmt->fetchAll();

    // Fetch total notifications (likes from others that aren't matches yet)
    $stmt_likes = $pdo->prepare("
        SELECT COUNT(*) 
        FROM dating_swipes 
        WHERE swiped_id = ? AND type = 'like' 
        AND swiper_id NOT IN (
            SELECT CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END 
            FROM dating_matches WHERE user1_id = ? OR user2_id = ?
        )
    ");
    $stmt_likes->execute([$user_id, $user_id, $user_id, $user_id]);
    $likes_count = $stmt_likes->fetchColumn();

} catch (Exception $e) {
    $matches = [];
    $likes_count = 0;
}

include '../includes/header.php';
?>

<div class="dating-dashboard-wrapper py-4" style="background: #fff8f9; min-height: 100vh;">
    <div class="container" style="max-width: 900px;">

        <!-- DASHBOARD HEADER -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 gap-3">
            <div>
                <h2 class="fw-900 text-danger mb-1" style="letter-spacing: -1px;">Dating Hub</h2>
                <p class="text-muted small mb-0">Manage your connections and conversations</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dating.php" class="btn btn-danger rounded-pill px-4 fw-bold shadow-sm">
                    <i class="fas fa-fire me-2"></i>Find New People
                </a>
                <a href="dating_setup.php"
                    class="btn btn-white border rounded-circle d-flex align-items-center justify-content-center shadow-sm"
                    style="width:45px; height:45px;" title="Profile Settings">
                    <i class="fas fa-cog text-muted"></i>
                </a>
            </div>
        </div>

        <!-- STATS / NOTIFICATION MINI-CARDS -->
        <div class="row g-3 mb-5">
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-white">
                    <div class="text-danger mb-1" style="font-size: 1.5rem;"><i class="fas fa-heart"></i></div>
                    <h4 class="fw-bold mb-0"><?php echo count($matches); ?></h4>
                    <small class="text-muted">Total Matches</small>
                </div>
            </div>
            <div class="col-6 col-md-4">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-white position-relative">
                    <?php if ($likes_count > 0): ?>
                        <span
                            class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger shadow-sm"
                            style="z-index: 5;">
                            <?php echo $likes_count; ?>
                        </span>
                    <?php endif; ?>
                    <div class="text-warning mb-1" style="font-size: 1.5rem;"><i class="fas fa-star"></i></div>
                    <h4 class="fw-bold mb-0"><?php echo $likes_count; ?></h4>
                    <small class="text-muted">New Likes</small>
                </div>
            </div>
            <div class="col-12 col-md-4 d-none d-md-block">
                <div class="card border-0 shadow-sm rounded-4 p-3 text-center h-100 bg-white">
                    <div class="text-primary mb-1" style="font-size: 1.5rem;"><i class="fas fa-bolt"></i></div>
                    <h4 class="fw-bold mb-0">Active</h4>
                    <small class="text-muted">Profile Status</small>
                </div>
            </div>
        </div>

        <!-- MESSAGES & CONVERSATIONS SECTION -->
        <div class="card border-0 shadow-lg rounded-5 overflow-hidden bg-white mb-5">
            <div class="card-header bg-white border-0 pt-4 px-4 pb-0">
                <h5 class="fw-bold mb-0 d-flex align-items-center">
                    <i class="fas fa-comments text-danger me-2"></i>Recent Conversations
                </h5>
                <hr class="mt-3 mb-0 opacity-10">
            </div>

            <div class="card-body p-0">
                <?php if (empty($matches)): ?>
                    <div class="text-center py-5">
                        <div class="mb-4" style="font-size: 4rem;">💭</div>
                        <h5 class="fw-bold text-muted">No conversations yet</h5>
                        <p class="text-muted small mb-4">Start swiping to find someone to chat with!</p>
                        <a href="dating.php" class="btn btn-outline-danger rounded-pill px-5 fw-bold">Start Swiping</a>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($matches as $m):
                            $is_unread = ($m['unread_count'] > 0);
                            ?>
                            <a href="dating_chat.php?user_id=<?php echo $m['other_user_id']; ?>"
                                class="list-group-item list-group-item-action border-0 p-4 transition-all d-flex align-items-center gap-3 <?php echo $is_unread ? 'bg-light-danger' : ''; ?>"
                                style="border-bottom: 1px solid #f8f9fa !important;">

                                <!-- Profile Pic with status -->
                                <div class="position-relative flex-shrink-0">
                                    <img src="<?php echo htmlspecialchars($m['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($m['full_name']) . '&background=random'); ?>"
                                        class="rounded-circle shadow-sm border border-2 <?php echo $is_unread ? 'border-danger' : 'border-white'; ?>"
                                        height="65" width="65" style="object-fit: cover;">
                                    <?php if ($is_unread): ?>
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle p-2 bg-danger border border-white rounded-circle"></span>
                                    <?php endif; ?>
                                </div>

                                <!-- Message Details -->
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($m['full_name']); ?></h6>
                                        <?php if ($m['last_msg_time']): ?>
                                            <small class="text-muted" style="font-size: 0.7rem;">
                                                <?php echo date('H:i', strtotime($m['last_msg_time'])); ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mb-0 text-truncate <?php echo $is_unread ? 'fw-bold text-dark' : 'text-muted'; ?>"
                                        style="font-size: 0.85rem;">
                                        <?php
                                        if ($m['last_msg']) {
                                            echo ($is_unread ? '<i class="fas fa-circle text-danger me-1" style="font-size:0.5rem"></i> ' : '') . htmlspecialchars($m['last_msg']);
                                        } else {
                                            echo '<span class="fst-italic">Say hello to your new match! 👋</span>';
                                        }
                                        ?>
                                    </p>
                                </div>

                                <!-- Action / Arrow -->
                                <div class="ms-auto">
                                    <?php if ($is_unread): ?>
                                        <span class="badge rounded-pill bg-danger"><?php echo $m['unread_count']; ?></span>
                                    <?php else: ?>
                                        <i class="fas fa-chevron-right text-muted opacity-25"></i>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if (!empty($matches)): ?>
                <div class="card-footer bg-light border-0 py-3 text-center">
                    <a href="dating.php" class="text-danger text-decoration-none small fw-bold">
                        <i class="fas fa-plus-circle me-1"></i>Discover more people
                    </a>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<style>
    .fw-900 {
        font-weight: 900;
    }

    .bg-light-danger {
        background-color: #fffbfa;
    }

    .transition-all {
        transition: all 0.3s ease;
    }

    .list-group-item:hover {
        background-color: #fff8f9;
        transform: scale(1.01);
        z-index: 2;
        box-shadow: 0 5px 15px rgba(255, 75, 110, 0.05);
    }

    .badge {
        font-weight: 600;
        padding: 0.4em 0.8em;
    }
</style>

<?php include '../includes/footer.php'; ?>