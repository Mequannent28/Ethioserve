<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Fetch Matches
$stmt = $pdo->prepare("
    SELECT m.id as match_id, u.id as other_user_id, u.full_name, p.profile_pic, p.bio, 
           (SELECT message FROM dating_messages WHERE (sender_id = ? AND receiver_id = u.id) OR (sender_id = u.id AND receiver_id = ?) ORDER BY created_at DESC LIMIT 1) as last_msg
    FROM dating_matches m
    JOIN users u ON (m.user1_id = u.id OR m.user2_id = u.id) AND u.id != ?
    JOIN dating_profiles p ON u.id = p.user_id
    WHERE m.user1_id = ? OR m.user2_id = ?
    ORDER BY m.created_at DESC
");
$stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id]);
$matches = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1"><i class="fas fa-comment-heart text-danger me-2"></i>My Matches</h2>
            <p class="text-muted">People who liked you back!</p>
        </div>
        <a href="dating.php" class="btn btn-outline-danger rounded-pill px-4">Back to Discovery</a>
    </div>

    <div class="row g-4">
        <?php if (empty($matches)): ?>
            <div class="col-12 text-center py-5">
                <div class="bg-light rounded-5 p-5">
                    <i class="fas fa-heart-broken text-muted mb-4" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No matches yet</h4>
                    <p class="text-muted mb-4">Keep swiping to find your special someone!</p>
                    <a href="dating.php" class="btn btn-danger rounded-pill px-5 fw-bold">Start Swiping</a>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($matches as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="dating_chat.php?user_id=<?php echo $m['other_user_id']; ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm rounded-4 p-3 hover-shadow transition-all bg-white mb-3">
                            <div class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($m['profile_pic']); ?>" class="rounded-circle me-3"
                                    width="70" height="70" style="object-fit: cover;">
                                <div class="flex-grow-1 overflow-hidden">
                                    <h6 class="fw-bold text-dark mb-1">
                                        <?php echo htmlspecialchars($m['full_name']); ?>
                                    </h6>
                                    <p class="text-muted small mb-0 text-truncate">
                                        <?php echo $m['last_msg'] ? htmlspecialchars($m['last_msg']) : '<span class="fst-italic">Say hi to your new match!</span>'; ?>
                                    </p>
                                </div>
                                <div class="ms-2">
                                    <i class="fas fa-chevron-right text-muted small"></i>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<style>
    .hover-shadow:hover {
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .1) !important;
        transform: translateX(5px);
    }
</style>

<?php include '../includes/footer.php'; ?>