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

<div class="video-call-page min-vh-100 position-relative" style="background: #0a0a1a; overflow: hidden;">

    <!-- Background Decoration -->
    <div class="position-absolute top-0 start-0 w-100 h-100 opacity-20"
        style="background: radial-gradient(circle at 20% 30%, #4a148c 0%, transparent 50%), radial-gradient(circle at 80% 70%, #311b92 0%, transparent 50%);">
    </div>

    <!-- Incoming/Connecting Overlay -->
    <div id="connectingOverlay"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center z-3"
        style="background: linear-gradient(135deg, #1a0a28, #4a148c); transition: all 0.5s ease;">
        <div class="text-center">
            <div class="position-relative d-inline-block mb-4">
                <img src="<?php echo htmlspecialchars($other_user['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($other_user['full_name'])); ?>"
                    class="rounded-circle border border-4 border-white shadow-lg animate__animated animate__pulse animate__infinite"
                    width="150" height="150" style="object-fit: cover;">
                <div class="calling-ring"></div>
            </div>
            <h2 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($other_user['full_name']); ?></h2>
            <p class="text-white-50 fs-5 mb-4" id="callStatusText">
                <?php echo $incoming ? 'Joining call...' : 'Calling...'; ?></p>

            <div class="d-flex justify-content-center gap-3">
                <button id="cancelCallBtn"
                    class="btn btn-danger rounded-circle p-4 shadow-lg animate__animated animate__bounceIn">
                    <i class="fas fa-phone-slash fs-2"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- Jitsi Container -->
    <div id="jitsi-container" class="w-100 h-100 position-absolute top-0 start-0 z-1 d-none"></div>

    <!-- Mobile-optimized Bottom Controls -->
    <div id="callControls" class="position-fixed bottom-0 start-0 w-100 p-4 z-2 d-none">
        <div class="d-flex justify-content-center align-items-center gap-4">
            <button id="toggleMic" class="btn btn-light rounded-circle shadow-lg" style="width:60px;height:60px;">
                <i class="fas fa-microphone fs-4"></i>
            </button>
            <button id="endCallBtn" class="btn btn-danger rounded-circle shadow-lg" style="width:75px;height:75px;">
                <i class="fas fa-phone-slash fs-2"></i>
            </button>
            <button id="toggleCam" class="btn btn-light rounded-circle shadow-lg" style="width:60px;height:60px;">
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

    .calling-ring {
        position: absolute;
        top: -15px;
        left: -15px;
        width: calc(100% + 30px);
        height: calc(100% + 30px);
        border: 4px solid rgba(255, 255, 255, 0.4);
        border-radius: 50%;
        animation: ring-expand 2s infinite;
    }

    @keyframes ring-expand {
        0% {
            transform: scale(1);
            opacity: 1;
        }

        100% {
            transform: scale(1.5);
            opacity: 0;
        }
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
        let api = null;
        let callId = null;

        // Load calling sounds from header if available, else use fallback
        const ringtoneOut = document.getElementById('ringtoneOutgoing') || new Audio('https://assets.mixkit.co/sfx/preview/mixkit-outgoing-call-waiting-ringtone-1353.mp3');

        function startCall() {
            if (!isIncoming) {
                // Initiate via signaling
                const fd = new FormData();
                fd.append('action', 'initiate_call');
                fd.append('receiver_id', otherUserId);
                fd.append('room_id', roomId);
                fd.append('call_type', 'dating');

                fetch('../includes/signaling.php', { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            callId = data.call_id;
                            ringtoneOut.play().catch(e => console.warn("Sound blocked", e));
                            checkCallResponse();
                        } else {
                            alert("Could not start call: " + data.error);
                            window.location.href = 'dating_chat.php?user_id=' + otherUserId;
                        }
                    });
            } else {
                // Directly join for incoming
                initializeJitsi();
            }
        }

        function checkCallResponse() {
            if (isIncoming || !callId) return;

            fetch('../includes/signaling.php?action=get_call_status&call_id=' + callId)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'accepted') {
                        ringtoneOut.pause();
                        initializeJitsi();
                    } else if (data.status === 'rejected') {
                        ringtoneOut.pause();
                        document.getElementById('callStatusText').textContent = "Call Declined";
                        setTimeout(() => window.location.href = 'dating_chat.php?user_id=' + otherUserId, 2000);
                    } else {
                        setTimeout(checkCallResponse, 3000);
                    }
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
                    startWithVideoMuted: false
                }
            };
            api = new JitsiMeetExternalAPI(domain, options);

            api.addEventListeners({
                readyToClose: () => endCall(),
                videoConferenceLeft: () => endCall()
            });
        }

        function endCall() {
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