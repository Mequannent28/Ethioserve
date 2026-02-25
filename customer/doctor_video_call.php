<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to start a video call.');
}

$user_id = getCurrentUserId();
$doctor_id = intval($_GET['doctor_id'] ?? 0);
$incoming = intval($_GET['incoming'] ?? 0);
$is_video = intval($_GET['is_video'] ?? 1);

if (!$doctor_id) {
    header("Location: doctors.php");
    exit();
}

// Fetch doctor info
try {
    $stmt = $pdo->prepare("SELECT p.*, s.name as specialty_name 
                           FROM health_providers p 
                           LEFT JOIN health_specialties s ON p.specialty_id = s.id 
                           WHERE p.id = ? AND p.type = 'doctor'");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();
} catch (Exception $e) {
    $doctor = null;
}

if (!$doctor) {
    redirectWithMessage('doctors.php', 'danger', 'Doctor not found.');
}

// Room ID from URL (if incoming) or generated (if outgoing)
$room_id = $_GET['room_id'] ?? ('ethioserve-doc-' . $doctor_id . '-patient-' . $user_id);

include '../includes/header.php';
?>

<div class="video-call-page min-vh-100 position-relative" style="background: #0a0a1a; overflow: hidden;">
    <!-- Connecting Overlay -->
    <div id="connectingOverlay"
        class="position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center z-3"
        style="background: linear-gradient(135deg, #0a1628, #1a237e); transition: all 0.5s ease; z-index: 3000;">
        <div class="text-center">
            <div class="position-relative d-inline-block mb-4">
                <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($doctor['name'])); ?>"
                    class="rounded-circle border border-4 border-white shadow-lg animate__animated animate__pulse animate__infinite"
                    width="150" height="150" style="object-fit: cover;">
                <div class="calling-ring"></div>
            </div>
            <h2 class="text-white fw-bold mb-2"><?php echo htmlspecialchars($doctor['name']); ?></h2>
            <p class="text-white-50 fs-5 mb-4" id="callStatusText">
                <?php echo $incoming ? 'Joining ' . ($is_video ? 'video' : 'voice') . ' call...' : 'Calling ' . ($is_video ? 'video' : 'voice') . '...'; ?>
            </p>

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

    <!-- Controls -->
    <div id="callControls" class="position-fixed bottom-0 start-0 w-100 p-4 z-2 d-none" style="z-index: 2000;">
        <div class="d-flex justify-content-center align-items-center gap-4">
            <button id="toggleMic" class="btn btn-light rounded-circle shadow-lg" style="width:60px;height:60px;">
                <i class="fas fa-microphone fs-4"></i>
            </button>
            <button id="endCallBtn" class="btn btn-danger rounded-circle shadow-lg" style="width:75px;height:75px;">
                <i class="fas fa-phone-slash fs-2"></i>
            </button>
            <button id="toggleCam" class="btn btn-light rounded-circle shadow-lg"
                style="width:60px;height:60px; display: <?php echo $is_video ? 'flex' : 'none'; ?>">
                <i class="fas fa-video fs-4"></i>
            </button>
        </div>
    </div>
</div>

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
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const roomId = '<?php echo $room_id; ?>';
        const doctorId = <?php echo $doctor_id; ?>;
        const isIncoming = <?php echo $incoming; ?>;
        const isVideo = <?php echo $is_video; ?>;
        let api = null;
        let callId = null;

        const ringtoneOut = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-outgoing-call-waiting-ringtone-1353.mp3');
        const signalingUrl = '<?php echo BASE_URL; ?>/signaling.php';

        function startCall() {
            if (!isIncoming) {
                const fd = new FormData();
                fd.append('action', 'initiate_call');
                fd.append('receiver_id', doctorId);
                fd.append('room_id', roomId);
                fd.append('call_type', 'telemed');
                fd.append('is_video', isVideo);

                fetch(signalingUrl, { method: 'POST', body: fd })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            callId = data.call_id;
                            ringtoneOut.play().catch(() => { });
                            checkCallResponse();
                        } else {
                            alert("Could not start call: " + data.error);
                            window.location.href = 'doctor_chat.php?doctor_id=' + doctorId;
                        }
                    });
            } else {
                initializeJitsi();
            }
        }

        function checkCallResponse() {
            if (isIncoming || !callId) return;
            fetch(signalingUrl + '?action=get_call_status&call_id=' + callId)
                .then(r => r.json())
                .then(data => {
                    if (data.status === 'accepted') {
                        ringtoneOut.pause();
                        initializeJitsi();
                    } else if (data.status === 'rejected') {
                        ringtoneOut.pause();
                        document.getElementById('callStatusText').textContent = "Call Declined";
                        setTimeout(() => window.location.href = 'doctor_chat.php?doctor_id=' + doctorId, 2000);
                    } else if (data.status === 'ended') {
                        ringtoneOut.pause();
                        document.getElementById('callStatusText').textContent = "Call Ended";
                        setTimeout(() => window.location.href = 'doctor_chat.php?doctor_id=' + doctorId, 2000);
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
                userInfo: { displayName: '<?php echo $_SESSION['full_name']; ?>' },
                interfaceConfigOverwrite: { TOOLBAR_BUTTONS: [], SETTINGS_SECTIONS: [], SHOW_JITSI_WATERMARK: false },
                configOverwrite: { startWithAudioMuted: false, startWithVideoMuted: !isVideo }
            };
            api = new JitsiMeetExternalAPI(domain, options);
            if (!isVideo) api.executeCommand('toggleVideo');

            api.addEventListeners({
                readyToClose: () => endCall(),
                videoConferenceLeft: () => endCall()
            });
        }

        function endCall() {
            if (callId) {
                const fd = new FormData();
                fd.append('action', 'end_call');
                fd.append('call_id', callId);
                fetch(signalingUrl, { method: 'POST', body: fd }).catch(() => { });
            }
            if (api) api.dispose();
            if (ringtoneOut) ringtoneOut.pause();
            window.location.href = 'doctor_chat.php?doctor_id=' + doctorId;
        }

        document.getElementById('cancelCallBtn').onclick = endCall;
        document.getElementById('endCallBtn').onclick = endCall;
        document.getElementById('toggleMic').onclick = () => { api.executeCommand('toggleAudio'); document.getElementById('toggleMic').classList.toggle('btn-secondary'); };
        document.getElementById('toggleCam').onclick = () => { api.executeCommand('toggleVideo'); document.getElementById('toggleCam').classList.toggle('btn-secondary'); };

        startCall();
    });
</script>

<?php include '../includes/footer.php'; ?>