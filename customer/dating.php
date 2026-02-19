<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$user_id = $_SESSION['id'] ?? null;
$my_profile = null;

if ($user_id) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM dating_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $my_profile = $stmt->fetch();
    } catch (Exception $e) {
    }
}

// Handle AJAX swipe actions
if (isset($_GET['action']) && $user_id) {
    $target_id = intval($_GET['target_id'] ?? 0);
    $action = $_GET['action'];

    if ($target_id && in_array($action, ['like', 'dislike', 'superlike'])) {
        try {
            $stmt = $pdo->prepare("INSERT IGNORE INTO dating_swipes (swiper_id, swiped_id, type) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $target_id, $action]);

            $matched = false;
            if ($action === 'like' || $action === 'superlike') {
                $stmt = $pdo->prepare("SELECT id FROM dating_swipes WHERE swiper_id = ? AND swiped_id = ? AND (type='like' OR type='superlike')");
                $stmt->execute([$target_id, $user_id]);
                if ($stmt->fetch()) {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO dating_matches (user1_id, user2_id) VALUES (LEAST(?,?), GREATEST(?,?))");
                    $stmt->execute([$user_id, $target_id, $user_id, $target_id]);
                    $matched = true;
                    $_SESSION['match_found'] = $target_id;
                }
            }

            if (isset($_GET['ajax'])) {
                echo json_encode(['status' => 'ok', 'matched' => $matched]);
                exit();
            }
            header("Location: dating.php");
            exit();
        } catch (Exception $e) {
            if (isset($_GET['ajax'])) {
                echo json_encode(['status' => 'error']);
                exit();
            }
        }
    }
}

// Load data
$potential_matches = [];  // Queue for swipe stack
$guest_profiles = [];  // For non-logged-in guests
$browse_profiles = [];  // Full browse grid for logged-in users
$my_match_ids = [];  // IDs of mutual matches (contact revealed)
$match_data = null;

if ($user_id && $my_profile) {
    // ── Swipe queue ──
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name, u.email, u.phone
            FROM dating_profiles p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id != ?
            AND p.user_id NOT IN (SELECT swiped_id FROM dating_swipes WHERE swiper_id = ?)
            AND (p.gender = ? OR ? = 'everyone')
            ORDER BY RAND()
            LIMIT 6
        ");
        $stmt->execute([$user_id, $user_id, $my_profile['looking_for'], $my_profile['looking_for']]);
        $potential_matches = $stmt->fetchAll();
    } catch (Exception $e) {
    }

    // ── Browse all profiles (everyone, with contact) ──
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name, u.email, u.phone
            FROM dating_profiles p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id != ?
            ORDER BY p.created_at DESC
            LIMIT 30
        ");
        $stmt->execute([$user_id]);
        $browse_profiles = $stmt->fetchAll();
    } catch (Exception $e) {
    }

    // ── My mutual match IDs (contact unlocked) ──
    try {
        $stmt = $pdo->prepare("
            SELECT CASE WHEN user1_id = ? THEN user2_id ELSE user1_id END as other_id
            FROM dating_matches
            WHERE user1_id = ? OR user2_id = ?
        ");
        $stmt->execute([$user_id, $user_id, $user_id]);
        $my_match_ids = array_column($stmt->fetchAll(), 'other_id');
    } catch (Exception $e) {
    }

    // ── Match popup ──
    if (isset($_SESSION['match_found'])) {
        $mid = $_SESSION['match_found'];
        unset($_SESSION['match_found']);
        try {
            $stmt = $pdo->prepare("SELECT u.full_name, p.profile_pic FROM users u JOIN dating_profiles p ON u.id = p.user_id WHERE u.id = ?");
            $stmt->execute([$mid]);
            $match_data = $stmt->fetch();
            if ($match_data)
                $match_data['user_id'] = $mid;
        } catch (Exception $e) {
        }
    }

} elseif ($user_id && !$my_profile) {
    // Logged-in but no profile yet — show all profiles UNBLURRED
    try {
        $stmt = $pdo->prepare("
            SELECT p.*, u.full_name, u.email, u.phone
            FROM dating_profiles p
            JOIN users u ON p.user_id = u.id
            WHERE p.user_id != ?
            ORDER BY p.created_at DESC
            LIMIT 24
        ");
        $stmt->execute([$user_id]);
        $browse_profiles = $stmt->fetchAll();
    } catch (Exception $e) {
    }

} else {
    // Pure guest — blurred previews
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.full_name
            FROM dating_profiles p
            JOIN users u ON p.user_id = u.id
            ORDER BY RAND()
            LIMIT 12
        ");
        $guest_profiles = $stmt->fetchAll();
    } catch (Exception $e) {
    }
}
include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Dating – EthioServe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet">
</head>

<body style="background:#FFF0F3; font-family:'Inter',sans-serif;">

    <!-- ===================== MATCH POPUP (logged-in) ===================== -->
    <?php if ($match_data): ?>
        <div id="matchPopup"
            class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
            style="background:rgba(0,0,0,0.85);z-index:9999;">
            <div class="text-center text-white p-5 animate-pop">
                <div class="mb-4">
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <?php if ($my_profile && $my_profile['profile_pic']): ?>
                            <img src="<?php echo htmlspecialchars($my_profile['profile_pic']); ?>"
                                class="rounded-circle border border-4 border-white shadow" width="110" height="110"
                                style="object-fit:cover;">
                        <?php endif; ?>
                        <img src="<?php echo htmlspecialchars($match_data['profile_pic']); ?>"
                            class="rounded-circle border border-4 border-danger shadow" width="110" height="110"
                            style="object-fit:cover;">
                    </div>
                    <div class="mb-3" style="font-size:3rem;">💘</div>
                    <h1 class="fw-black mb-2" style="font-size:2.5rem;">It's a Match!</h1>
                    <p class="opacity-75 fs-5 mb-5">You and
                        <strong><?php echo htmlspecialchars($match_data['full_name']); ?></strong> liked each other.
                    </p>
                </div>
                <div class="d-flex flex-column gap-3 mx-auto" style="max-width:300px;">
                    <a href="dating_chat.php?user_id=<?php echo $match_data['user_id']; ?>"
                        class="btn btn-danger btn-lg rounded-pill fw-bold py-3 shadow">
                        <i class="fas fa-comment-heart me-2"></i> Send a Message
                    </a>
                    <button onclick="document.getElementById('matchPopup').remove()"
                        class="btn btn-outline-light rounded-pill fw-bold py-3">
                        Keep Swiping
                    </button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ===================== MAIN CONTAINER ===================== -->
    <div class="container py-4" style="max-width:560px;">

        <!-- HEADER -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h2 class="fw-black mb-0 text-danger" style="letter-spacing:-1px;">
                    <i class="fas fa-fire me-2"></i>EthioDate
                </h2>
                <p class="text-muted small mb-0">Find your match in Ethiopia</p>
            </div>
            <div class="d-flex gap-2 align-items-center">
                <?php if ($user_id): ?>
                    <a href="dating_matches.php" class="btn btn-white rounded-pill shadow-sm px-3 position-relative"
                        style="border:1px solid #eee;">
                        <i class="fas fa-comments text-danger me-1"></i> Matches
                    </a>
                    <a href="dating_setup.php" class="btn rounded-circle shadow-sm"
                        style="width:42px;height:42px;border:1px solid #eee;background:#fff;display:flex;align-items:center;justify-content:center;">
                        <i class="fas fa-sliders-h text-dark"></i>
                    </a>
                <?php else: ?>
                    <a href="../login.php?redirect=customer/dating.php"
                        class="btn btn-danger rounded-pill px-4 fw-bold shadow">
                        <i class="fas fa-sign-in-alt me-1"></i> Login
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if (!$user_id || !$my_profile): ?>
            <!-- ============================================================ -->
            <!-- GUEST / NO-PROFILE VIEW                                      -->
            <!-- ============================================================ -->

            <!-- Hero CTA Card -->
            <div class="card border-0 shadow-lg rounded-5 overflow-hidden mb-4"
                style="background:linear-gradient(135deg,#ff4b6e,#ff8c69);">
                <div class="card-body p-5 text-white text-center">
                    <div class="mb-3" style="font-size:3rem;">💕</div>
                    <h2 class="fw-black mb-2">Ethiopia's #1 Dating App</h2>
                    <p class="opacity-80 mb-4">Browse
                        <?php echo count($guest_profiles) > 0 ? count($guest_profiles) . '+' : 'thousands of'; ?> real
                        profiles in Addis & beyond. Login to see full profiles & match!
                    </p>
                    <div class="d-grid gap-3">
                        <?php if (!$user_id): ?>
                            <a href="../login.php?redirect=customer/dating.php" class="btn btn-white fw-bold rounded-pill py-3"
                                style="color:#ff4b6e;font-size:1.05rem;">
                                <i class="fas fa-heart me-2"></i> Login to Find Matches
                            </a>
                            <a href="../register.php?redirect=customer/dating_setup.php"
                                class="btn btn-outline-light fw-bold rounded-pill py-3">
                                Create Free Account
                            </a>
                        <?php else: ?>
                            <a href="dating_setup.php" class="btn btn-white fw-bold rounded-pill py-3" style="color:#ff4b6e;">
                                <i class="fas fa-user-edit me-2"></i> Complete My Profile
                            </a>
                        <?php endif; ?>
                    </div>
                    <!-- Stats -->
                    <div class="row g-0 mt-4 pt-4 border-top border-white border-opacity-25">
                        <div class="col-4 text-center">
                            <div class="fw-black fs-4">5K+</div>
                            <small class="opacity-75">Users</small>
                        </div>
                        <div class="col-4 text-center border-start border-end border-white border-opacity-25">
                            <div class="fw-black fs-4">200+</div>
                            <small class="opacity-75">Matches</small>
                        </div>
                        <div class="col-4 text-center">
                            <div class="fw-black fs-4">100%</div>
                            <small class="opacity-75">Private</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── BROWSE PROFILES for logged-in (no profile yet) OR guest blur ── -->

            <?php if ($user_id && !$my_profile && !empty($browse_profiles)): ?>
                <!-- Logged-in but profile not set up: unblurred browse + contact locked -->
                <div class="alert border-0 rounded-4 mb-4" style="background:#fff3cd;">
                    <i class="fas fa-info-circle text-warning me-2"></i>
                    <strong>Set up your dating profile</strong> to start swiping and matching!
                    <a href="dating_setup.php" class="btn btn-sm btn-warning rounded-pill ms-3 fw-bold">Setup Profile</a>
                </div>
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Discover People</h5>
                    <span class="badge bg-danger rounded-pill"><?php echo count($browse_profiles); ?> profiles</span>
                </div>
                <div class="row g-3">
                    <?php foreach ($browse_profiles as $gp):
                        $interests = array_filter(array_map('trim', explode(',', $gp['interests'] ?? '')));
                        ?>
                        <div class="col-6">
                            <div class="profile-browse-card card border-0 shadow-sm rounded-4 overflow-hidden">
                                <!-- Photo UNBLURRED for logged-in users -->
                                <div class="position-relative" style="height:210px;overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars($gp['profile_pic'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400'); ?>"
                                        class="w-100 h-100" style="object-fit:cover;">
                                    <div class="position-absolute bottom-0 start-0 w-100 p-2 text-white"
                                        style="background:linear-gradient(transparent,rgba(0,0,0,0.75));">
                                        <div class="fw-bold small"><?php echo htmlspecialchars($gp['full_name']); ?>,
                                            <?php echo intval($gp['age']); ?></div>
                                        <div style="font-size:0.7rem;opacity:0.85;"><i
                                                class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($gp['location_name'] ?? 'Addis Ababa'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <p class="text-muted mb-2" style="font-size:0.78rem;line-height:1.4;">
                                        <?php echo htmlspecialchars(mb_substr($gp['bio'] ?? '', 0, 70)) . (strlen($gp['bio'] ?? '') > 70 ? '...' : ''); ?>
                                    </p>
                                    <?php if (!empty($interests)): ?>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            <?php foreach (array_slice($interests, 0, 3) as $tag): ?>
                                                <span class="badge rounded-pill"
                                                    style="background:#fff0f3;color:#ff4b6e;font-size:0.65rem;font-weight:600;border:1px solid #ffc2cc;"><?php echo htmlspecialchars($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <!-- Contact locked: need profile + match -->
                                    <div class="rounded-3 p-2 text-center" style="background:#fff5f7;border:1px dashed #ffc2cc;">
                                        <span style="font-size:0.7rem;color:#ff4b6e;font-weight:600;"><i
                                                class="fas fa-lock me-1"></i> Setup profile &amp; match to unlock contact</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

            <?php elseif (!$user_id && !empty($guest_profiles)): ?>
                <!-- Pure guest: blurred photos -->
                <div class="mb-3 d-flex justify-content-between align-items-center">
                    <h5 class="fw-bold mb-0">Discover People</h5>
                    <span class="badge bg-danger rounded-pill"><?php echo count($guest_profiles); ?> online</span>
                </div>
                <div class="row g-3">
                    <?php foreach ($guest_profiles as $gp):
                        $interests = array_filter(array_map('trim', explode(',', $gp['interests'] ?? '')));
                        ?>
                        <div class="col-6">
                            <div class="profile-guest-card card border-0 shadow-sm rounded-4 overflow-hidden position-relative"
                                style="cursor:pointer;"
                                onclick="document.querySelector('#loginPrompt').scrollIntoView({behavior:'smooth'})">
                                <div class="position-relative" style="height:200px;overflow:hidden;">
                                    <img src="<?php echo htmlspecialchars($gp['profile_pic'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400'); ?>"
                                        class="w-100 h-100" style="object-fit:cover;filter:blur(8px);transform:scale(1.1);">
                                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center"
                                        style="background:rgba(255,75,110,0.15);">
                                        <div class="bg-white rounded-pill px-3 py-1 shadow-sm"
                                            style="font-size:0.7rem;font-weight:700;color:#ff4b6e;"><i class="fas fa-lock me-1"></i>
                                            Login to View</div>
                                    </div>
                                    <div class="position-absolute bottom-0 start-0 w-100 p-2 text-white"
                                        style="background:linear-gradient(transparent,rgba(0,0,0,0.75));">
                                        <div class="fw-bold small"><?php echo htmlspecialchars($gp['full_name']); ?>,
                                            <?php echo intval($gp['age']); ?></div>
                                        <div style="font-size:0.7rem;opacity:0.85;"><i
                                                class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($gp['location_name'] ?? 'Addis Ababa'); ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="p-3">
                                    <p class="text-muted mb-2" style="font-size:0.78rem;line-height:1.4;">
                                        <?php echo htmlspecialchars(mb_substr($gp['bio'] ?? 'Interesting person.', 0, 60)) . (strlen($gp['bio'] ?? '') > 60 ? '...' : ''); ?>
                                    </p>
                                    <?php if (!empty($interests)): ?>
                                        <div class="d-flex flex-wrap gap-1 mb-2">
                                            <?php foreach (array_slice($interests, 0, 3) as $tag): ?>
                                                <span class="badge rounded-pill"
                                                    style="background:#fff0f3;color:#ff4b6e;font-size:0.65rem;font-weight:600;border:1px solid #ffc2cc;"><?php echo htmlspecialchars($tag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="rounded-3 p-2 text-center" style="background:#fff5f7;border:1px dashed #ffc2cc;">
                                        <span style="font-size:0.7rem;color:#ff4b6e;font-weight:600;"><i
                                                class="fas fa-eye-slash me-1"></i> Login to reveal contact</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Login prompt anchor -->
                <div id="loginPrompt" class="mt-5 text-center py-5 rounded-5 shadow"
                    style="background:linear-gradient(135deg,#ff4b6e,#ff8c69);color:#fff;">
                    <div class="mb-3" style="font-size:2.5rem;">🔓</div>
                    <h4 class="fw-black mb-2">Unlock Full Profiles</h4>
                    <p class="opacity-80 mb-4">See full photos, contact details &amp; start messaging!</p>
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <a href="../login.php?redirect=customer/dating.php" class="btn btn-white fw-bold rounded-pill px-5 py-3"
                            style="color:#ff4b6e;">
                            <i class="fas fa-sign-in-alt me-2"></i> Login Now
                        </a>
                        <a href="../register.php" class="btn btn-outline-light fw-bold rounded-pill px-5 py-3">Register Free</a>
                    </div>
                </div>
            <?php endif; ?>

        <?php elseif (!empty($potential_matches)): ?>
            <!-- ============================================================ -->
            <!-- LOGGED-IN SWIPE VIEW                                         -->
            <!-- ============================================================ -->

            <!-- Swipe Stack Container -->
            <div id="swipeStack" class="position-relative mb-4" style="height:580px;">
                <?php foreach (array_reverse($potential_matches) as $i => $pm):
                    $interests = array_filter(array_map('trim', explode(',', $pm['interests'] ?? '')));
                    $isTop = ($i === count($potential_matches) - 1);
                    ?>
                    <div class="swipe-card <?php echo $isTop ? 'active-card' : ''; ?>" data-id="<?php echo $pm['user_id']; ?>"
                        style="position:absolute;top:0;left:0;width:100%;<?php echo !$isTop ? 'transform:scale(0.95) translateY(10px);opacity:0.7;' : ''; ?>">
                        <div class="card border-0 shadow-lg rounded-5 overflow-hidden bg-white h-100">
                            <!-- Photo -->
                            <div class="position-relative" style="height:400px;overflow:hidden;">
                                <img src="<?php echo htmlspecialchars($pm['profile_pic'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400'); ?>"
                                    class="w-100 h-100" style="object-fit:cover;" alt="">

                                <!-- Like/Nope overlays -->
                                <div class="like-stamp position-absolute top-0 start-0 m-4"
                                    style="opacity:0;transform:rotate(-20deg);border:5px solid #00e676;border-radius:10px;padding:8px 20px;">
                                    <span style="color:#00e676;font-size:2rem;font-weight:900;">LIKE</span>
                                </div>
                                <div class="nope-stamp position-absolute top-0 end-0 m-4"
                                    style="opacity:0;transform:rotate(20deg);border:5px solid #ff4b6e;border-radius:10px;padding:8px 20px;">
                                    <span style="color:#ff4b6e;font-size:2rem;font-weight:900;">NOPE</span>
                                </div>

                                <!-- Info overlay on photo -->
                                <div class="position-absolute bottom-0 start-0 w-100 p-4 text-white"
                                    style="background:linear-gradient(transparent,rgba(0,0,0,0.85));">
                                    <h2 class="fw-black mb-1" style="text-shadow:0 2px 8px rgba(0,0,0,0.4);">
                                        <?php echo htmlspecialchars($pm['full_name']); ?>,
                                        <span style="font-weight:500;"><?php echo intval($pm['age']); ?></span>
                                    </h2>
                                    <p class="mb-2 opacity-90" style="font-size:0.9rem;">
                                        <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                        <?php echo htmlspecialchars($pm['location_name'] ?? 'Addis Ababa'); ?>
                                    </p>
                                    <!-- Interest badges on photo -->
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach (array_slice($interests, 0, 4) as $tag): ?>
                                            <span class="badge px-3 py-2 rounded-pill"
                                                style="background:rgba(255,255,255,0.25);backdrop-filter:blur(6px);font-size:0.75rem;">
                                                <?php echo htmlspecialchars($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Card body: bio + contact (contact locked until match) -->
                            <div class="card-body px-4 py-3">
                                <p class="text-muted mb-3" style="font-size:0.9rem;line-height:1.6;">
                                    <?php echo htmlspecialchars($pm['bio'] ?? ''); ?>
                                </p>

                                <!-- Locked contact info -->
                                <div class="rounded-4 p-3" style="background:#fff5f7;border:1px dashed #ffaabb;">
                                    <div class="d-flex align-items-center gap-2">
                                        <i class="fas fa-lock text-danger"></i>
                                        <span class="fw-bold text-danger small">Contact info locked</span>
                                    </div>
                                    <p class="text-muted mb-0 mt-1" style="font-size:0.78rem;">Match with this person to unlock
                                        their contact details.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Action Buttons -->
            <div class="d-flex justify-content-center align-items-center gap-4 mb-5">
                <!-- Dislike -->
                <button
                    class="action-btn dislike-btn rounded-circle shadow border-0 d-flex align-items-center justify-content-center"
                    style="width:70px;height:70px;background:#fff;" id="btnDislike" title="Nope">
                    <i class="fas fa-times text-muted" style="font-size:1.8rem;"></i>
                </button>

                <!-- Superlike -->
                <button
                    class="action-btn super-btn rounded-circle shadow border-0 d-flex align-items-center justify-content-center"
                    style="width:55px;height:55px;background:#00b4d8;" id="btnSuper" title="Super Like">
                    <i class="fas fa-star text-white" style="font-size:1.3rem;"></i>
                </button>

                <!-- Like -->
                <button
                    class="action-btn like-btn rounded-circle shadow border-0 d-flex align-items-center justify-content-center"
                    style="width:70px;height:70px;background:linear-gradient(135deg,#ff4b6e,#ff8c69);" id="btnLike"
                    title="Like">
                    <i class="fas fa-heart text-white" style="font-size:1.8rem;"></i>
                </button>
            </div>

            <!-- Profile Progress bar -->
            <div class="text-center text-muted small mb-3">
                <span id="cardsLeft"><?php echo count($potential_matches); ?></span> people near you
            </div>

        <?php elseif ($user_id && $my_profile): ?>
            <!-- No more swipe matches - show full browse grid -->
            <div class="text-center py-4 mb-4">
                <div style="font-size:4rem;">😴</div>
                <h4 class="fw-black">You've swiped everyone nearby!</h4>
                <p class="text-muted">Browse all profiles below, or check your matches.</p>
                <div class="d-flex gap-3 justify-content-center">
                    <a href="dating_matches.php" class="btn btn-danger rounded-pill px-4 fw-bold">
                        <i class="fas fa-comments me-2"></i>My Matches
                    </a>
                    <a href="dating_setup.php" class="btn btn-outline-danger rounded-pill px-4 fw-bold">
                        <i class="fas fa-sliders-h me-2"></i>Preferences
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- ── BROWSE ALL for logged-in users (below swipe or standalone) ── -->
        <?php if ($user_id && $my_profile && !empty($browse_profiles)): ?>
            <div class="mt-5 mb-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0"><i class="fas fa-users text-danger me-2"></i>Browse All Profiles</h5>
                <span class="badge bg-danger rounded-pill"><?php echo count($browse_profiles); ?> people</span>
            </div>
            <div class="row g-3">
                <?php foreach ($browse_profiles as $bp):
                    $interests = array_filter(array_map('trim', explode(',', $bp['interests'] ?? '')));
                    $is_matched = in_array($bp['user_id'], $my_match_ids);
                    ?>
                    <div class="col-6">
                        <div class="profile-browse-card card border-0 shadow-sm rounded-4 overflow-hidden">
                            <!-- Full unblurred photo -->
                            <div class="position-relative" style="height:210px;overflow:hidden;">
                                <img src="<?php echo htmlspecialchars($bp['profile_pic'] ?: 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=400'); ?>"
                                    class="w-100 h-100" style="object-fit:cover;">
                                <?php if ($is_matched): ?>
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger rounded-pill shadow"><i
                                                class="fas fa-heart me-1"></i>Matched</span>
                                    </div>
                                <?php endif; ?>
                                <div class="position-absolute bottom-0 start-0 w-100 p-2 text-white"
                                    style="background:linear-gradient(transparent,rgba(0,0,0,0.8));">
                                    <div class="fw-bold small"><?php echo htmlspecialchars($bp['full_name']); ?>,
                                        <?php echo intval($bp['age']); ?></div>
                                    <div style="font-size:0.7rem;opacity:0.85;"><i
                                            class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($bp['location_name'] ?? 'Addis Ababa'); ?>
                                    </div>
                                </div>
                            </div>

                            <div class="p-3">
                                <!-- Full bio -->
                                <p class="text-muted mb-2" style="font-size:0.78rem;line-height:1.5;">
                                    <?php echo htmlspecialchars(mb_substr($bp['bio'] ?? '', 0, 80)) . (strlen($bp['bio'] ?? '') > 80 ? '...' : ''); ?>
                                </p>

                                <!-- Interest tags -->
                                <?php if (!empty($interests)): ?>
                                    <div class="d-flex flex-wrap gap-1 mb-3">
                                        <?php foreach (array_slice($interests, 0, 4) as $tag): ?>
                                            <span class="badge rounded-pill"
                                                style="background:#fff0f3;color:#ff4b6e;font-size:0.65rem;font-weight:600;border:1px solid #ffc2cc;">
                                                <?php echo htmlspecialchars($tag); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Contact: revealed if matched, locked if not -->
                                <?php if ($is_matched): ?>
                                    <div class="rounded-3 p-2" style="background:#fff0f3;border:1px solid #ffc2cc;">
                                        <div class="fw-bold text-danger mb-1" style="font-size:0.75rem;"><i
                                                class="fas fa-unlock me-1"></i>Contact Unlocked!</div>
                                        <?php if (!empty($bp['phone'])): ?>
                                            <div style="font-size:0.8rem;"><i class="fas fa-phone me-1 text-success"></i>
                                                <a href="tel:<?php echo htmlspecialchars($bp['phone']); ?>"
                                                    class="text-dark text-decoration-none fw-bold"><?php echo htmlspecialchars($bp['phone']); ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($bp['email'])): ?>
                                            <div style="font-size:0.8rem;"><i class="fas fa-envelope me-1 text-primary"></i>
                                                <a href="mailto:<?php echo htmlspecialchars($bp['email']); ?>"
                                                    class="text-dark text-decoration-none"><?php echo htmlspecialchars($bp['email']); ?></a>
                                            </div>
                                        <?php endif; ?>
                                        <a href="dating_chat.php?user_id=<?php echo $bp['user_id']; ?>"
                                            class="btn btn-danger btn-sm rounded-pill w-100 mt-2 fw-bold">
                                            <i class="fas fa-comment-heart me-1"></i>Message
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="rounded-3 p-2 text-center" style="background:#fff5f7;border:1px dashed #ffc2cc;">
                                        <span style="font-size:0.72rem;color:#ff4b6e;font-weight:600;">
                                            <i class="fas fa-lock me-1"></i>Match to unlock contact
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
        </div>
    <?php endif; ?>

    </div><!-- /container -->

    <!-- ================================================================== -->
    <!-- STYLES                                                              -->
    <!-- ================================================================== -->
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }

        /* Guest profile cards */
        .profile-guest-card {
            transition: transform 0.25s, box-shadow 0.25s;
        }

        .profile-guest-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(255, 75, 110, 0.15) !important;
        }

        /* Swipe stack */
        .swipe-card {
            transition: transform 0.35s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.35s;
            border-radius: 24px;
            touch-action: none;
            user-select: none;
        }

        .swipe-card.swiping {
            transition: none;
        }

        .swipe-card.fly-left {
            transform: translateX(-150%) rotate(-25deg) !important;
            opacity: 0 !important;
            transition: transform 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.45s !important;
        }

        .swipe-card.fly-right {
            transform: translateX(150%) rotate(25deg) !important;
            opacity: 0 !important;
            transition: transform 0.45s cubic-bezier(0.25, 0.46, 0.45, 0.94), opacity 0.45s !important;
        }

        .swipe-card.fly-up {
            transform: translateY(-150%) !important;
            opacity: 0 !important;
            transition: transform 0.4s, opacity 0.4s !important;
        }

        /* Action buttons */
        .action-btn {
            transition: transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.275), box-shadow 0.2s;
            cursor: pointer;
        }

        .action-btn:hover {
            transform: scale(1.12);
        }

        .action-btn:active {
            transform: scale(0.95);
        }

        .like-btn:hover {
            box-shadow: 0 0 25px rgba(255, 75, 110, 0.5) !important;
        }

        .dislike-btn:hover {
            box-shadow: 0 0 20px rgba(150, 150, 150, 0.3) !important;
        }

        .super-btn:hover {
            box-shadow: 0 0 20px rgba(0, 180, 216, 0.5) !important;
        }

        /* Stamps */
        .like-stamp,
        .nope-stamp {
            pointer-events: none;
            z-index: 10;
        }

        /* Pop animation for match modal */
        @keyframes popIn {
            0% {
                transform: scale(0.5);
                opacity: 0;
            }

            70% {
                transform: scale(1.05);
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        .animate-pop {
            animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
        }

        /* Heartbeat pulse on heart button */
        @keyframes heartbeat {

            0%,
            100% {
                transform: scale(1)
            }

            20% {
                transform: scale(1.2)
            }

            40% {
                transform: scale(1)
            }
        }

        #btnLike:hover i {
            animation: heartbeat 0.6s ease;
        }
    </style>

    <!-- ================================================================== -->
    <!-- SWIPE JS                                                            -->
    <!-- ================================================================== -->
    <?php if ($user_id && $my_profile && !empty($potential_matches)): ?>
        <script>
            (function () {
                const stack = document.getElementById('swipeStack');
                const cards = Array.from(stack.querySelectorAll('.swipe-card')).reverse(); // top-first order
                let currentIdx = 0;

                function getTopCard() {
                    for (let i = currentIdx; i < cards.length; i++) {
                        if (!cards[i].classList.contains('fly-left') && !cards[i].classList.contains('fly-right') && !cards[i].classList.contains('fly-up')) {
                            return cards[i];
                        }
                    }
                    return null;
                }

                function updateStack() {
                    const visible = cards.filter(c => !c.classList.contains('fly-left') && !c.classList.contains('fly-right') && !c.classList.contains('fly-up'));
                    visible.forEach((c, i) => {
                        if (i === 0) {
                            c.style.transform = 'scale(1) translateY(0)';
                            c.style.opacity = '1';
                            c.style.zIndex = '10';
                        } else if (i === 1) {
                            c.style.transform = 'scale(0.95) translateY(12px)';
                            c.style.opacity = '0.75';
                            c.style.zIndex = '9';
                        } else {
                            c.style.transform = 'scale(0.90) translateY(24px)';
                            c.style.opacity = '0.5';
                            c.style.zIndex = '8';
                        }
                    });
                    document.getElementById('cardsLeft').textContent = visible.length;
                }

                function swipe(direction) {
                    const card = getTopCard();
                    if (!card) return;
                    const targetId = card.dataset.id;
                    let action, likeStamp, nopeStamp;
                    likeStamp = card.querySelector('.like-stamp');
                    nopeStamp = card.querySelector('.nope-stamp');

                    if (direction === 'right') {
                        action = 'like';
                        if (likeStamp) likeStamp.style.opacity = '1';
                        card.classList.add('fly-right');
                    } else if (direction === 'left') {
                        action = 'dislike';
                        if (nopeStamp) nopeStamp.style.opacity = '1';
                        card.classList.add('fly-left');
                    } else if (direction === 'up') {
                        action = 'superlike';
                        card.classList.add('fly-up');
                    }

                    // Send to server
                    fetch(`dating.php?action=${action}&target_id=${targetId}&ajax=1`)
                        .then(r => r.json())
                        .then(data => {
                            if (data.matched) {
                                window.location.href = 'dating.php';
                            }
                        }).catch(() => { });

                    setTimeout(() => {
                        updateStack();
                        currentIdx++;
                        if (getTopCard() === null) {
                            setTimeout(() => {
                                stack.innerHTML = `
                    <div class="text-center py-5 mt-4">
                        <div style="font-size:5rem;">😴</div>
                        <h4 class="fw-black mt-3">You've seen everyone!</h4>
                        <p class="text-muted">Check back later for new people.</p>
                        <a href="dating_matches.php" class="btn btn-danger rounded-pill px-5 py-3 fw-bold mt-3 shadow">
                            <i class="fas fa-comments me-2"></i>View My Matches
                        </a>
                    </div>`;
                            }, 500);
                        }
                    }, 350);
                }

                // Button click handlers
                document.getElementById('btnLike').addEventListener('click', () => swipe('right'));
                document.getElementById('btnDislike').addEventListener('click', () => swipe('left'));
                document.getElementById('btnSuper').addEventListener('click', () => swipe('up'));

                // Touch / drag swipe on top card
                function enableDrag(card) {
                    let startX = 0, startY = 0, dx = 0;
                    const likeStamp = card.querySelector('.like-stamp');
                    const nopeStamp = card.querySelector('.nope-stamp');

                    function onStart(e) {
                        if (card !== getTopCard()) return;
                        startX = e.touches ? e.touches[0].clientX : e.clientX;
                        startY = e.touches ? e.touches[0].clientY : e.clientY;
                        card.classList.add('swiping');
                        document.addEventListener(e.touches ? 'touchmove' : 'mousemove', onMove, { passive: false });
                        document.addEventListener(e.touches ? 'touchend' : 'mouseup', onEnd);
                    }
                    function onMove(e) {
                        dx = (e.touches ? e.touches[0].clientX : e.clientX) - startX;
                        let dy = (e.touches ? e.touches[0].clientY : e.clientY) - startY;
                        let rotate = dx * 0.08;
                        card.style.transform = `translateX(${dx}px) translateY(${dy * 0.3}px) rotate(${rotate}deg)`;
                        let ratio = Math.min(Math.abs(dx) / 80, 1);
                        if (dx > 0) {
                            if (likeStamp) likeStamp.style.opacity = ratio;
                            if (nopeStamp) nopeStamp.style.opacity = 0;
                        } else {
                            if (nopeStamp) nopeStamp.style.opacity = ratio;
                            if (likeStamp) likeStamp.style.opacity = 0;
                        }
                        if (e.cancelable) e.preventDefault();
                    }
                    function onEnd(e) {
                        card.classList.remove('swiping');
                        document.removeEventListener('touchmove', onMove);
                        document.removeEventListener('mousemove', onMove);
                        document.removeEventListener('touchend', onEnd);
                        document.removeEventListener('mouseup', onEnd);
                        if (likeStamp) likeStamp.style.opacity = 0;
                        if (nopeStamp) nopeStamp.style.opacity = 0;
                        if (dx > 80) { swipe('right'); }
                        else if (dx < -80) { swipe('left'); }
                        else { card.style.transform = ''; }
                        dx = 0;
                    }
                    card.addEventListener('mousedown', onStart);
                    card.addEventListener('touchstart', onStart, { passive: true });
                }

                cards.forEach(enableDrag);
                updateStack();
            })();
        </script>
    <?php endif; ?>

    <?php include '../includes/footer.php'; ?>