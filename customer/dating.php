<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;
$my_profile = null;

if ($user_id) {
    // Check if user has a dating profile
    $stmt = $pdo->prepare("SELECT * FROM dating_profiles WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $my_profile = $stmt->fetch();

    /* No forced redirect - let them see the landing card */
}

// Handle Swipe Actions
if (isset($_GET['action']) && isset($_GET['target_id'])) {
    $target_id = intval($_GET['target_id']);
    $action = $_GET['action']; // like, dislike, superlike

    if (in_array($action, ['like', 'dislike', 'superlike'])) {
        try {
            // Record Swipe
            $stmt = $pdo->prepare("INSERT IGNORE INTO dating_swipes (swiper_id, swiped_id, type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $target_id, $action]);

            // Check for Match if it was a like
            if ($action === 'like' || $action === 'superlike') {
                $stmt = $pdo->prepare("SELECT id FROM dating_swipes WHERE swiper_id = ? AND swiped_id = ? AND (type = 'like' OR type = 'superlike')");
                $stmt->execute([$target_id, $user_id]);
                if ($stmt->fetch()) {
                    // It's a MATCH!
                    $stmt = $pdo->prepare("INSERT IGNORE INTO dating_matches (user1_id, user2_id) VALUES (LEAST(?, ?), GREATEST(?, ?))");
                    $stmt->execute([$user_id, $target_id, $user_id, $target_id]);
                    $_SESSION['match_found'] = $target_id;
                }
            }
            header("Location: dating.php");
            exit();
        } catch (Exception $e) {
        }
    }
}

$potential_match = null;
if ($user_id && $my_profile) {
    // Fetch potential matches (not swiped yet)
    $stmt = $pdo->prepare("
        SELECT p.*, u.full_name 
        FROM dating_profiles p
        JOIN users u ON p.user_id = u.id
        WHERE p.user_id != ? 
        AND p.user_id NOT IN (SELECT swiped_id FROM dating_swipes WHERE swiper_id = ?)
        AND (p.gender = ? OR ? = 'everyone')
        LIMIT 1
    ");
    $stmt->execute([$user_id, $user_id, $my_profile['looking_for'], $my_profile['looking_for']]);
    $potential_match = $stmt->fetch();
}

include '../includes/header.php';
?>

<div class="dating-container py-5 min-vh-100" style="background: #FFF0F0;">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-bold mb-0 text-danger"><i class="fas fa-heart me-2"></i>Discovery</h2>
                <p class="text-muted small">Find your perfect match in Addis</p>
            </div>
            <div class="d-flex gap-2">
                <a href="dating_matches.php" class="btn btn-white rounded-pill shadow-sm px-4">
                    <i class="fas fa-comments text-danger me-2"></i>Matches
                </a>
                <a href="dating_setup.php" class="btn btn-white rounded-circle shadow-sm"
                    style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-user-edit text-dark"></i>
                </a>
            </div>
        </div>

        <?php if (isset($_SESSION['match_found'])):
            $match_id = $_SESSION['match_found'];
            unset($_SESSION['match_found']);
            $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $stmt->execute([$match_id]);
            $match_name = $stmt->fetchColumn();
            ?>
            <div class="alert alert-danger rounded-4 shadow border-0 p-4 mb-5 animate-bounce">
                <div class="text-center">
                    <h3 class="fw-bold mb-2">🎉 It's a Match!</h3>
                    <p class="mb-3">You and <strong>
                            <?php echo htmlspecialchars($match_name); ?>
                        </strong> liked each other.</p>
                    <a href="dating_chat.php?user_id=<?php echo $match_id; ?>"
                        class="btn btn-danger rounded-pill px-5 fw-bold">Send a Message</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row justify-content-center">
            <div class="col-lg-5">
                <?php if (!$user_id || !$my_profile): ?>
                    <!-- Landing / Join Card -->
                    <div class="card border-0 shadow-lg rounded-5 overflow-hidden bg-white text-center p-5">
                        <div class="mb-4">
                            <div class="bg-danger bg-opacity-10 p-4 rounded-circle d-inline-flex">
                                <i class="fas fa-heart text-danger display-4"></i>
                            </div>
                        </div>
                        <h2 class="fw-bold mb-3">Ethiopia's Premium Dating</h2>
                        <p class="text-muted mb-5">Connect with interesting people in Addis Ababa and across Ethiopia. Our
                            community is growing every day!</p>

                        <div class="d-grid gap-3">
                            <?php if (!$user_id): ?>
                                <a href="../login.php?redirect=customer/dating.php"
                                    class="btn btn-danger btn-lg rounded-pill fw-bold shadow">
                                    Login to Find Matches
                                </a>
                                <a href="../register.php" class="btn btn-outline-danger btn-lg rounded-pill fw-bold">
                                    Create New Account
                                </a>
                            <?php else: ?>
                                <a href="dating_setup.php" class="btn btn-danger btn-lg rounded-pill fw-bold shadow">
                                    Setup My Dating Profile
                                </a>
                            <?php endif; ?>
                        </div>

                        <div class="mt-5 pt-4 border-top">
                            <div class="row g-3">
                                <div class="col-4">
                                    <h4 class="fw-bold mb-0 text-danger">5K+</h4>
                                    <small class="text-muted">Users</small>
                                </div>
                                <div class="col-4 border-start">
                                    <h4 class="fw-bold mb-0 text-danger">200+</h4>
                                    <small class="text-muted">Matches</small>
                                </div>
                                <div class="col-4 border-start">
                                    <h4 class="fw-bold mb-0 text-danger">100%</h4>
                                    <small class="text-muted">Private</small>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php elseif ($potential_match): ?>
                    <div class="card swipe-card border-0 shadow-lg rounded-5 overflow-hidden bg-white">
                        <div class="position-relative">
                            <img src="<?php echo htmlspecialchars($potential_match['profile_pic']); ?>" class="card-img-top"
                                style="height: 500px; object-fit: cover;" alt="Match">
                            <div class="swipe-overlay-info position-absolute bottom-0 start-0 w-100 p-4 text-white"
                                style="background: linear-gradient(transparent, rgba(0,0,0,0.8));">
                                <h2 class="fw-bold mb-1">
                                    <?php echo htmlspecialchars($potential_match['full_name']); ?>,
                                    <?php echo $potential_match['age']; ?>
                                </h2>
                                <p class="small opacity-90 mb-2"><i class="fas fa-map-marker-alt me-2"></i>
                                    <?php echo htmlspecialchars($potential_match['location_name']); ?>
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                                    <?php
                                    $ints = explode(',', $potential_match['interests']);
                                    foreach (array_slice($ints, 0, 3) as $it):
                                        if (trim($it)): ?>
                                            <span class="badge bg-white bg-opacity-25 rounded-pill px-3 py-2 small">
                                                <?php echo htmlspecialchars(trim($it)); ?>
                                            </span>
                                        <?php endif; endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <h6 class="fw-bold text-dark mb-2">About</h6>
                            <p class="text-muted small mb-4">
                                <?php echo htmlspecialchars($potential_match['bio']); ?>
                            </p>

                            <div class="d-flex justify-content-between align-items-center gap-3">
                                <a href="?action=dislike&target_id=<?php echo $potential_match['user_id']; ?>"
                                    class="btn btn-light rounded-circle shadow-sm action-btn dislike"
                                    style="width: 65px; height: 65px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-times fs-3 text-muted"></i>
                                </a>
                                <a href="?action=superlike&target_id=<?php echo $potential_match['user_id']; ?>"
                                    class="btn btn-info rounded-circle shadow-sm action-btn superlike"
                                    style="width: 55px; height: 55px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-star fs-4 text-white"></i>
                                </a>
                                <a href="?action=like&target_id=<?php echo $potential_match['user_id']; ?>"
                                    class="btn btn-danger rounded-circle shadow-sm action-btn like"
                                    style="width: 65px; height: 65px; display: flex; align-items: center; justify-content: center;">
                                    <i class="fas fa-heart fs-3 text-white"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <div class="mb-4"><i class="fas fa-ghost text-muted" style="font-size: 5rem;"></i></div>
                        <h4 class="fw-bold text-dark">No more matches nearby</h4>
                        <p class="text-muted">Try expanding your preferences or check back later!</p>
                        <a href="dating_setup.php" class="btn btn-outline-danger rounded-pill px-5 mt-3">Edit
                            Preferences</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
    .swipe-card {
        transition: transform 0.3s ease;
    }

    .action-btn {
        transition: all 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    .action-btn:hover {
        transform: scale(1.1);
    }

    .action-btn.dislike:hover {
        background: #f8d7da !important;
    }

    .action-btn.dislike:hover i {
        color: #dc3545 !important;
    }

    .action-btn.like:hover {
        background: #dc3545 !important;
        box-shadow: 0 0 20px rgba(220, 53, 69, 0.4) !important;
    }

    .action-btn.superlike:hover {
        transform: scale(1.2) rotate(15deg);
    }

    .animate-bounce {
        animation: bounce 1s infinite;
    }

    @keyframes bounce {

        0%,
        100% {
            transform: translateY(0);
        }

        50% {
            transform: translateY(-10px);
        }
    }
</style>

<?php include '../includes/footer.php'; ?>