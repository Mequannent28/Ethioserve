<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to start a video call.');
}

$user_id = getCurrentUserId();
$other_user_id = intval($_GET['user_id'] ?? 0);
$incoming = intval($_GET['incoming'] ?? 0);

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

// Room ID from URL (if incoming) or generated (if outgoing)
$room_id = $_GET['room_id'] ?? ('ethioserve-dating-' . min($user_id, $other_user_id) . '-' . max($user_id, $other_user_id));

include '../includes/header.php';
?>

<div class="video-call-page min-vh-100 position-relative"
    style="background: #050510; overflow: hidden; font-family: 'Poppins', sans-serif;">

    <!-- Background Decoration - Dynamic Gradients -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 10% 10%, rgba(131, 58, 180, 0.2) 0%, transparent 40%), 
                    radial-gradient(circle at 90% 90%, rgba(253, 29, 29, 0.2) 0%, transparent 40%),
                    radial-gradient(circle at 50% 50%, rgba(252, 176, 69, 0.1) 0%, transparent 60%);">
    </div>

    <!-- Incoming/Connecting Overlay (The "Calling" Screen) -->
    <div id="connectingOverlay"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center z-3"
        style="background: linear-gradient(135deg, #1a0a2e 0%, #050510 100%); transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1);">

        <div class="text-center animate__animated animate__fadeIn">
            <!-- Profile Pic with Animated Rings -->
            <div class="position-relative d-inline-block mb-5">
                <div class="caller-avatar-container">
                    <img src="<?php echo htmlspecialchars($other_user['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($other_user['full_name']) . '&background=fd1d1d&color=fff'); ?>"
                        class="rounded-circle border border-4 border-white shadow-2xl position-relative z-2" width="180"
                        height="180" style="object-fit: cover;">
                    <div class="pulse-ring ring-1"></div>
                    <div class="pulse-ring ring-2"></div>
                    <div class="pulse-ring ring-3"></div>
                </div>
            </div>

            <h1 class="text-white fw-bold mb-2 tracking-tight"><?php echo htmlspecialchars($other_user['full_name']); ?>
            </h1>
            <div class="d-flex align-items-center justify-content-center gap-2 mb-5">
                <span class="badge rounded-pill bg-danger px-3 py-2 animate__animated animate__pulse animate__infinite">
                    <i class="fas <?php echo intval($_GET['is_video'] ?? 1) ? 'fa-video' : 'fa-phone-alt'; ?> me-1"></i>
                    <span id="callStatusText"><?php echo $incoming ? 'CONNECTING...' : 'CALLING...'; ?></span>
                </span>
            </div>

            <!-- Action Buttons for Calling State -->
            <div class="d-flex justify-content-center">
                <button id="cancelCallBtn"
                    class="btn btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-lg hover-scale"
                    style="width:90px;height:90px; transition: all 0.3s ease;">
                    <i class="fas fa-phone-slash fs-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Jitsi Container (The actual call surface) -->
    <div id="jitsi-container"
        class="w-100 h-100 position-absolute top-0 start-0 z-1 d-none animate__animated animate__fadeIn"></div>

    <!-- Custom Control Overlay (Shows after connection) -->
    <div id="callControls"
        class="position-fixed bottom-0 start-0 w-100 p-5 z-2 d-none animate__animated animate__slideInUp">
        <div class="d-flex justify-content-center align-items-center gap-4">
            <button id="toggleMic" class="btn btn-blur-light rounded-circle shadow-lg" style="width:65px;height:65px;">
                <i class="fas fa-microphone fs-4"></i>
            </button>

            <button id="endCallBtn"
                class="btn btn-danger rounded-circle shadow-lg hover-scale-lg d-flex align-items-center justify-content-center"
                style="width:85px;height:85px;">
                <i class="fas fa-phone-slash fs-2"></i>
            </button>

            <button id="toggleCam" class="btn btn-blur-light rounded-circle shadow-lg" style="width:65px;height:65px;">
                <i class="fas fa-video fs-4"></i>
            </button>
        </div>
    </div>

</div>

<!-- External Scripts -->
<script src='https://meet.jit.si/external_api.js'></script>

<style>
    .video-call-page {
        z-index: 2000;
    }

    .video-call-page .navbar,
    .video-call-page footer {
        display: none !important;
    }

    /* Pulse Rings for Calling State */
    .caller-avatar-container {
        position: relative;
        z-index: 10;
    }

    .pulse-ring {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 180px;
        height: 180px;
        background: rgba(253, 29, 29, 0.4);
        border-radius: 50%;
        opacity: 0;
        z-index: 1;
    }

    .ring-1 {
        animation: pulse-ring 3s infinite;
    }

    .ring-2 {
        animation: pulse-ring 3s infinite 1s;
    }

    .ring-3 {
        animation: pulse-ring 3s infinite 2s;
    }

    @keyframes pulse-ring {
        0% {
            transform: translate(-50%, -50%) scale(1);
            opacity: 0.6;
        }

        100% {
            transform: translate(-50%, -50%) scale(2.2);
            opacity: 0;
        }
    }

    .hover-scale:hover {
        transform: scale(1.1);
    }

    .hover-scale-lg:hover {
        transform: scale(1.15);
    }

    .btn-blur-light {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-blur-light:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
        color: white;
    }

    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.8);
    }

    .tracking-tight {
        letter-spacing: -0.05em;
    }

    .z-1 {
        z-index: 1;
    }

    .z-2 {
        z-index: 2;
    }

    .z-3 {
        z-index: 3;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roomId = '<?php echo $room_id; ?>';
        const otherUserId = <?php echo $other_user_id; ?>;
        const isIncoming = <?php echo $incoming; ?>;
        const isVideo = <?php echo intval($_GET['is_video'] ?? 1); ?>;
        let api = null;
        let callId = '<?php echo $_GET['call_id'] ?? ''; ?>';

        // UI Updates for Voice only
        if (!isVideo) {
            document.getElementById('callStatusText').textContent = isIncoming ? 'Joining voice call...' : 'Calling (Voice)...';
            const icon = document.querySelector('#connectingOverlay .fas.fa-video');
            if (icon) icon.classList.replace('fa-video', 'fa-phone');
            const camBtn = document.getElementById('toggleCam');
            if (camBtn) camBtn.style.display = 'none';
        }

        // Load calling sounds from header if available, else use fallback
        const ringtoneOut = document.getElementById('ringtoneOutgoing') || new Audio('https://assets.mixkit.co/sfx/preview/mixkit-outgoing-call-waiting-ringtone-1353.mp3');

        // USE ABSOLUTE PATH FOR SIGNALING (moved to root to bypass .htaccess)
        const signalingUrl = '<?php echo BASE_URL; ?>/signaling.php';

        function startCall() {
            if (!isIncoming) {
                console.log("Initiating dating call to:", otherUserId);
                // Initiate via signaling
                const fd = new FormData();
                fd.append('action', 'initiate_call');
                fd.append('receiver_id', otherUserId);
                fd.append('room_id', roomId);
                fd.append('call_type', 'dating');
                fd.append('is_video', isVideo);

                fetch(signalingUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        console.log("Call init response:", data);
                        if (data.success) {
                            callId = data.call_id;
                            ringtoneOut.play().catch(e => console.warn("Sound blocked", e));
                            checkCallResponse();
                        } else {
                            alert("Could not start call: " + (data.error || 'Unknown error'));
                            window.location.href = 'dating_chat.php?user_id=' + otherUserId;
                        }
                    })
                    .catch(err => {
                        console.error("Fetch error:", err);
                        alert("Connection error. Please try again.");
                        window.location.href = 'dating_chat.php?user_id=' + otherUserId;
                    });
            } else {
                // Directly join for incoming
                console.log("Joining incoming call room:", roomId);
                initializeJitsi();
            }
        }

        function checkCallResponse() {
            if (isIncoming || !callId) return;

            fetch(signalingUrl + '?action=get_call_status&call_id=' + callId)
                .then(r => r.json())
                .then(data => {
                    console.log("Call status poll:", data.status);
                    if (data.status === 'accepted') {
                        ringtoneOut.pause();
                        initializeJitsi();
                    } else if (data.status === 'rejected') {
                        ringtoneOut.pause();
                        document.getElementById('callStatusText').textContent = "Call Declined";
                        document.getElementById('callStatusText').classList.add('text-danger');
                        setTimeout(() => window.location.href = 'dating_chat.php?user_id=' + otherUserId, 2000);
                    } else if (data.status === 'ended') {
                        ringtoneOut.pause();
                        document.getElementById('callStatusText').textContent = "Call Ended";
                        setTimeout(() => window.location.href = 'dating_chat.php?user_id=' + otherUserId, 2000);
                    } else {
                        setTimeout(checkCallResponse, 3000);
                    }
                })
                .catch(err => {
                    console.warn("Poll error", err);
                    setTimeout(checkCallResponse, 5000);
                });
        }

        function initializeJitsi() {
            document.getElementById('connectingOverlay').classList.add('opacity-0');
            setTimeout(() => {
                document.getElementById('connectingOverlay').classList.add('d-none');
                document.getElementById('jitsi-container').classList.remove('d-none');
                document.getElementById('callControls').classList.remove('d-none');
            }, 500);

            const domain = 'meet.jit.si';
            const options = {
                roomName: roomId,
                width: '100%',
                height: '100%',
                parentNode: document.querySelector('#jitsi-container'),
                userInfo: {
                    displayName: '<?php echo $_SESSION['full_name']; ?>'
                },
                interfaceConfigOverwrite: {
                    TOOLBAR_BUTTONS: [], // Hide default Jitsi toolbar to use our own
                    SETTINGS_SECTIONS: [],
                    SHOW_JITSI_WATERMARK: false
                },
                configOverwrite: {
                    startWithAudioMuted: false,
                    startWithVideoMuted: !isVideo
                }
            };
            api = new JitsiMeetExternalAPI(domain, options);

            if (!isVideo) {
                api.executeCommand('toggleVideo'); // Double ensure video is off
            }

            api.addEventListeners({
                readyToClose: () => endCall(),
                videoConferenceLeft: () => endCall()
            });
        }

        function endCall() {
            if (callId) {
                console.log("Ending call via signaling:", callId);
                const fd = new FormData();
                fd.append('action', 'end_call');
                fd.append('call_id', callId);
                fetch(signalingUrl, { method: 'POST', body: fd }).catch(e => console.error("Signaling end failed", e));
            }
            if (api) api.dispose();
            if (ringtoneOut) ringtoneOut.pause();
            window.location.href = 'dating_chat.php?user_id=' + otherUserId;
        }

        document.getElementById('cancelCallBtn').onclick = endCall;
        document.getElementById('endCallBtn').onclick = endCall;

        document.getElementById('toggleMic').onclick = function () {
            api.executeCommand('toggleAudio');
            this.classList.toggle('btn-light');
            this.classList.toggle('btn-secondary');
        };

        document.getElementById('toggleCam').onclick = function () {
            api.executeCommand('toggleVideo');
            this.classList.toggle('btn-light');
            this.classList.toggle('btn-secondary');
        };

        // Auto-start
        startCall();
    });
</script>

<?php include '../includes/footer.php'; ?>