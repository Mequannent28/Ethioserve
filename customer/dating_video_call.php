<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to start a video call.');
}

$user_id = getCurrentUserId();
$other_user_id = intval($_GET['user_id'] ?? 0);

if (!$other_user_id) {
    header("Location: dating.php");
    exit();
}

// Fetch other user info
try {
    $stmt = $pdo->prepare("
        SELECT u.full_name, p.profile_pic, p.location_name
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
    redirectWithMessage('dating.php', 'danger', 'User not found.');
}

// Generate a unique room ID for this call
$room_id = 'ethioserve-dating-' . min($user_id, $other_user_id) . '-' . max($user_id, $other_user_id);

include '../includes/header.php';
?>

<div class="video-call-page min-vh-100" style="background: #0a0a1a;">

    <!-- Pre-Call Screen -->
    <div id="preCallScreen" class="min-vh-100 d-flex align-items-center justify-content-center"
        style="background: linear-gradient(135deg, #1a0a28 0%, #4a148c 50%, #311b92 100%);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="text-center mb-5">
                        <div class="position-relative d-inline-block mb-4">
                            <img src="<?php echo htmlspecialchars($other_user['profile_pic'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200'); ?>"
                                class="rounded-circle border border-4 border-white shadow-lg" width="120" height="120"
                                style="object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white"
                                style="width:24px;height:24px;"></div>
                        </div>
                        <h3 class="text-white fw-bold mb-1">
                            <?php echo htmlspecialchars($other_user['full_name']); ?>
                        </h3>
                        <p class="text-white-50 small">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($other_user['location_name'] ?: 'Online'); ?>
                        </p>
                    </div>

                    <!-- Camera Preview -->
                    <div class="card border-0 rounded-5 overflow-hidden shadow-lg mb-4" style="background: #1a1a2e;">
                        <div class="position-relative" style="aspect-ratio: 16/9;">
                            <video id="localPreview" autoplay muted playsinline class="w-100 h-100"
                                style="object-fit: cover; background: #1a1a2e;"></video>
                            <div id="cameraOff" class="position-absolute top-0 start-0 w-100 h-100 d-none"
                                style="background: #1a1a2e;">
                                <div class="d-flex align-items-center justify-content-center h-100">
                                    <div class="text-center">
                                        <div class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-2"
                                            style="width:60px;height:60px;">
                                            <i class="fas fa-video-slash text-white-50 fs-4"></i>
                                        </div>
                                        <p class="text-white-50 small mb-0">Camera is off</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Media Controls (Pre-call) -->
                    <div class="d-flex justify-content-center gap-3 mb-4">
                        <button id="toggleMicPre"
                            class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center active-control"
                            style="width:56px;height:56px;" title="Toggle Microphone">
                            <i class="fas fa-microphone fs-5"></i>
                        </button>
                        <button id="toggleCamPre"
                            class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center active-control"
                            style="width:56px;height:56px;" title="Toggle Camera">
                            <i class="fas fa-video fs-5"></i>
                        </button>
                    </div>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-column gap-3 align-items-center">
                        <button id="startCallBtn"
                            class="btn btn-danger btn-lg rounded-pill px-5 py-3 fw-bold shadow-lg call-btn-pulse"
                            style="min-width: 260px; font-size: 1.1rem; background-color: #e91e63; border: none;">
                            <i class="fas fa-video me-2"></i>Start Video Call
                        </button>
                        <div class="d-flex gap-2">
                            <a href="dating_chat.php?user_id=<?php echo $other_user_id; ?>"
                                class="btn btn-outline-light rounded-pill px-4">
                                <i class="fas fa-comments me-2"></i>Chat Instead
                            </a>
                            <a href="dating.php" class="btn btn-outline-light rounded-pill px-4">
                                <i class="fas fa-arrow-left me-2"></i>Go Back
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- In-Call Screen (Hidden by default) -->
    <div id="inCallScreen" class="min-vh-100 d-none position-relative" style="background: #0a0a1a;">
        <!-- Remote Video -->
        <div id="remoteVideoContainer" class="position-absolute top-0 start-0 w-100 h-100">
            <div class="d-flex align-items-center justify-content-center h-100" id="waitingScreen"
                style="background: linear-gradient(135deg, #1a0a28, #4a148c);">
                <div class="text-center">
                    <div class="position-relative d-inline-block mb-4">
                        <img src="<?php echo htmlspecialchars($other_user['profile_pic'] ?: 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=200'); ?>"
                            class="rounded-circle border border-3 border-white shadow" width="100" height="100"
                            style="object-fit:cover;">
                        <div class="calling-ring"></div>
                    </div>
                    <h4 class="text-white fw-bold mb-1">
                        <?php echo htmlspecialchars($other_user['full_name']); ?>
                    </h4>
                    <p class="text-white-50 mb-3" id="callStatus">Connecting...</p>
                    <div class="spinner-grow spinner-grow-sm text-danger" role="status"></div>
                </div>
            </div>
        </div>

        <!-- Local Video (PiP) -->
        <div id="localVideoContainer"
            class="position-absolute rounded-4 overflow-hidden shadow-lg border border-2 border-dark"
            style="bottom: 120px; right: 20px; width: 140px; height: 180px; z-index: 10; cursor: move;">
            <video id="localVideo" autoplay muted playsinline class="w-100 h-100"
                style="object-fit: cover; transform: scaleX(-1);"></video>
        </div>

        <!-- Call Timer -->
        <div class="position-absolute top-0 start-50 translate-middle-x mt-4" style="z-index: 10;">
            <div class="bg-dark bg-opacity-50 rounded-pill px-4 py-2 text-white d-flex align-items-center gap-2"
                style="backdrop-filter: blur(10px);">
                <span class="bg-danger rounded-circle" style="width:8px;height:8px;display:inline-block;"></span>
                <span id="callTimer" class="fw-bold small">00:00</span>
            </div>
        </div>

        <!-- Call Controls -->
        <div class="position-absolute bottom-0 start-0 w-100 p-4" style="z-index: 10;">
            <div class="d-flex justify-content-center gap-3">
                <button id="toggleMic"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:56px;height:56px;">
                    <i class="fas fa-microphone"></i>
                </button>
                <button id="toggleCam"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:56px;height:56px;">
                    <i class="fas fa-video"></i>
                </button>
                <button id="endCallBtn"
                    class="btn btn-danger rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;">
                    <i class="fas fa-phone-slash"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    .call-btn-pulse {
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {
        0% {
            box-shadow: 0 0 0 0 rgba(233, 30, 99, 0.5);
        }

        50% {
            box-shadow: 0 0 0 15px rgba(233, 30, 99, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(233, 30, 99, 0);
        }
    }

    .calling-ring {
        position: absolute;
        top: -10px;
        left: -10px;
        width: calc(100% + 20px);
        height: calc(100% + 20px);
        border: 3px solid rgba(255, 255, 255, 0.3);
        border-radius: 50%;
        animation: ring 1.5s ease-out infinite;
    }

    @keyframes ring {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        100% {
            transform: scale(1.4);
            opacity: 0;
        }
    }

    .video-call-page .navbar,
    .video-call-page footer {
        display: none !important;
    }
</style>

<script>
    let localStream = null;
    let micEnabled = true;
    let camEnabled = true;

    async function startPreview() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('localPreview').srcObject = localStream;
        } catch (err) {
            document.getElementById('cameraOff').classList.remove('d-none');
        }
    }
    startPreview();

    document.getElementById('startCallBtn').addEventListener('click', function () {
        document.getElementById('preCallScreen').classList.add('d-none');
        document.getElementById('inCallScreen').classList.remove('d-none');
        if (localStream) document.getElementById('localVideo').srcObject = localStream;

        let seconds = 0;
        setInterval(() => {
            seconds++;
            const m = String(Math.floor(seconds / 60)).padStart(2, '0');
            const s = String(seconds % 60).padStart(2, '0');
            document.getElementById('callTimer').textContent = m + ':' + s;
        }, 1000);
    });

    document.getElementById('endCallBtn').addEventListener('click', () => window.location.href = 'dating_chat.php?user_id=<?php echo $other_user_id; ?>');
</script>

<?php include '../includes/header.php'; ?>