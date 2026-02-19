<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to start a video call.');
}

$user_id = getCurrentUserId();
$doctor_id = intval($_GET['doctor_id'] ?? 0);

if (!$doctor_id) {
    header("Location: doctors.php");
    exit();
}

// Fetch doctor info
$stmt = $pdo->prepare("SELECT p.*, s.name as specialty_name 
                       FROM health_providers p 
                       LEFT JOIN health_specialties s ON p.specialty_id = s.id 
                       WHERE p.id = ? AND p.type = 'doctor'");
$stmt->execute([$doctor_id]);
$doctor = $stmt->fetch();

if (!$doctor) {
    redirectWithMessage('doctors.php', 'danger', 'Doctor not found.');
}

// Fetch user info
$stmt = $pdo->prepare("SELECT full_name, email, phone FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Generate a unique room ID for this call
$room_id = 'ethioserve-doc-' . $doctor_id . '-patient-' . $user_id . '-' . date('Ymd');

include '../includes/header.php';
?>

<div class="video-call-page min-vh-100" style="background: #0a0a1a;">

    <!-- Pre-Call Screen -->
    <div id="preCallScreen" class="min-vh-100 d-flex align-items-center justify-content-center"
        style="background: linear-gradient(135deg, #0a1628 0%, #1a237e 50%, #0d47a1 100%);">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-md-8">
                    <div class="text-center mb-5">
                        <div class="position-relative d-inline-block mb-4">
                            <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200'); ?>"
                                class="rounded-circle border border-4 border-white shadow-lg" width="120" height="120"
                                style="object-fit: cover;">
                            <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-3 border-white"
                                style="width:24px;height:24px;"></div>
                        </div>
                        <h3 class="text-white fw-bold mb-1">
                            <?php echo htmlspecialchars($doctor['name']); ?>
                        </h3>
                        <p class="text-white-50 mb-1">
                            <i class="fas fa-stethoscope me-1"></i>
                            <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                        </p>
                        <p class="text-white-50 small">
                            <i class="fas fa-map-marker-alt me-1"></i>
                            <?php echo htmlspecialchars($doctor['location']); ?>
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
                            class="btn btn-success btn-lg rounded-pill px-5 py-3 fw-bold shadow-lg call-btn-pulse"
                            style="min-width: 260px; font-size: 1.1rem;">
                            <i class="fas fa-video me-2"></i>Start Video Call
                        </button>
                        <div class="d-flex gap-2">
                            <a href="doctor_chat.php?doctor_id=<?php echo $doctor_id; ?>"
                                class="btn btn-outline-light rounded-pill px-4">
                                <i class="fas fa-comments me-2"></i>Chat Instead
                            </a>
                            <a href="doctors.php" class="btn btn-outline-light rounded-pill px-4">
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

        <!-- Remote Video (Full screen) -->
        <div id="remoteVideoContainer" class="position-absolute top-0 start-0 w-100 h-100">
            <div class="d-flex align-items-center justify-content-center h-100" id="waitingScreen"
                style="background: linear-gradient(135deg, #0a1628, #1a237e);">
                <div class="text-center">
                    <div class="position-relative d-inline-block mb-4">
                        <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200'); ?>"
                            class="rounded-circle border border-3 border-white shadow" width="100" height="100"
                            style="object-fit:cover;">
                        <div class="calling-ring"></div>
                    </div>
                    <h4 class="text-white fw-bold mb-1">
                        <?php echo htmlspecialchars($doctor['name']); ?>
                    </h4>
                    <p class="text-white-50 mb-3" id="callStatus">Connecting...</p>
                    <div class="spinner-grow spinner-grow-sm text-primary" role="status"></div>
                </div>
            </div>
        </div>

        <!-- Local Video (PiP) -->
        <div id="localVideoContainer"
            class="position-absolute rounded-4 overflow-hidden shadow-lg border border-2 border-dark"
            style="bottom: 120px; right: 20px; width: 180px; height: 240px; z-index: 10; cursor: move;">
            <video id="localVideo" autoplay muted playsinline class="w-100 h-100"
                style="object-fit: cover; transform: scaleX(-1);"></video>
        </div>

        <!-- Call Timer -->
        <div class="position-absolute top-0 start-50 translate-middle-x mt-4" style="z-index: 10;">
            <div class="bg-dark bg-opacity-50 rounded-pill px-4 py-2 text-white d-flex align-items-center gap-2"
                style="backdrop-filter: blur(10px);">
                <span class="bg-danger rounded-circle" style="width:8px;height:8px;display:inline-block;"></span>
                <span id="callTimer" class="fw-bold small">00:00</span>
                <span class="text-white-50 small">| Encrypted</span>
            </div>
        </div>

        <!-- Header Info -->
        <div class="position-absolute top-0 start-0 m-4" style="z-index: 10;">
            <div class="d-flex align-items-center gap-2 bg-dark bg-opacity-50 rounded-pill px-3 py-2"
                style="backdrop-filter: blur(10px);">
                <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=50'); ?>"
                    class="rounded-circle" width="32" height="32" style="object-fit:cover;">
                <span class="text-white small fw-bold">
                    <?php echo htmlspecialchars($doctor['name']); ?>
                </span>
            </div>
        </div>

        <!-- Call Controls (Bottom) -->
        <div class="position-absolute bottom-0 start-0 w-100 p-4" style="z-index: 10;">
            <div class="d-flex justify-content-center gap-3">
                <button id="toggleMic"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center active-control"
                    style="width:56px;height:56px;" title="Toggle Microphone">
                    <i class="fas fa-microphone fs-5"></i>
                </button>
                <button id="toggleCam"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center active-control"
                    style="width:56px;height:56px;" title="Toggle Camera">
                    <i class="fas fa-video fs-5"></i>
                </button>
                <button id="toggleChat"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:56px;height:56px;" title="Open Chat">
                    <i class="fas fa-comments fs-5"></i>
                </button>
                <button id="shareScreen"
                    class="btn btn-light rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:56px;height:56px;" title="Share Screen">
                    <i class="fas fa-desktop fs-5"></i>
                </button>
                <button id="endCallBtn"
                    class="btn btn-danger rounded-circle shadow d-flex align-items-center justify-content-center"
                    style="width:64px;height:64px;" title="End Call">
                    <i class="fas fa-phone-slash fs-4"></i>
                </button>
            </div>
        </div>

        <!-- In-Call Chat Panel (Slide-in from right) -->
        <div id="chatPanel" class="position-absolute top-0 end-0 h-100 d-none" style="width:350px;z-index:20;">
            <div class="bg-white h-100 shadow-lg d-flex flex-column" style="border-radius: 20px 0 0 20px;">
                <div class="p-3 border-bottom d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0"><i class="fas fa-comments text-primary me-2"></i>In-Call Chat</h6>
                    <button id="closeChatPanel" class="btn btn-sm btn-light rounded-circle">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div id="inCallChatBody" class="flex-grow-1 p-3 overflow-auto" style="background: #f8f9fa;">
                    <div class="text-center py-4 text-muted small">
                        <i class="fas fa-lock me-1"></i>Chat messages during call
                    </div>
                </div>
                <div class="p-3 border-top">
                    <form id="inCallChatForm" class="d-flex gap-2" onsubmit="return sendInCallMsg(event)">
                        <input type="text" id="inCallMsgInput" class="form-control rounded-pill border-0 bg-light px-3"
                            placeholder="Type..." autocomplete="off">
                        <button type="submit" class="btn btn-primary rounded-circle" style="width:40px;height:40px;">
                            <i class="fas fa-paper-plane small"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Call Ended Screen -->
    <div id="callEndedScreen" class="min-vh-100 d-none d-flex align-items-center justify-content-center"
        style="background: linear-gradient(135deg, #0a1628, #1a237e);">
        <div class="text-center">
            <div class="bg-white bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-4"
                style="width:80px;height:80px;">
                <i class="fas fa-phone-slash text-white fs-2"></i>
            </div>
            <h3 class="text-white fw-bold mb-2">Call Ended</h3>
            <p class="text-white-50 mb-1">with
                <?php echo htmlspecialchars($doctor['name']); ?>
            </p>
            <p class="text-white-50 small mb-4">Duration: <span id="finalDuration">00:00</span></p>
            <div class="d-flex flex-column gap-3 align-items-center">
                <a href="doctor_chat.php?doctor_id=<?php echo $doctor_id; ?>"
                    class="btn btn-primary rounded-pill px-5 py-2 fw-bold">
                    <i class="fas fa-comments me-2"></i>Continue via Chat
                </a>
                <a href="doctors.php" class="btn btn-outline-light rounded-pill px-5 py-2">
                    <i class="fas fa-arrow-left me-2"></i>Back to Doctors
                </a>
                <a href="medical_records.php" class="btn btn-outline-light rounded-pill px-5 py-2">
                    <i class="fas fa-file-medical me-2"></i>View Medical Records
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    .call-btn-pulse {
        animation: pulse-green 2s infinite;
    }

    @keyframes pulse-green {
        0% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.5);
        }

        50% {
            box-shadow: 0 0 0 15px rgba(40, 167, 69, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(40, 167, 69, 0);
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

    .active-control.muted {
        background: #dc3545 !important;
        color: white !important;
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
    let callTimer = null;
    let callSeconds = 0;

    // --- Camera Preview ---
    async function startPreview() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('localPreview').srcObject = localStream;
        } catch (err) {
            console.warn('Camera access denied:', err);
            document.getElementById('cameraOff').classList.remove('d-none');
        }
    }
    startPreview();

    // --- Toggle Mic (Pre-call) ---
    document.getElementById('toggleMicPre').addEventListener('click', function () {
        micEnabled = !micEnabled;
        if (localStream) {
            localStream.getAudioTracks().forEach(t => t.enabled = micEnabled);
        }
        this.classList.toggle('muted');
        this.innerHTML = micEnabled
            ? '<i class="fas fa-microphone fs-5"></i>'
            : '<i class="fas fa-microphone-slash fs-5"></i>';
    });

    // --- Toggle Camera (Pre-call) ---
    document.getElementById('toggleCamPre').addEventListener('click', function () {
        camEnabled = !camEnabled;
        if (localStream) {
            localStream.getVideoTracks().forEach(t => t.enabled = camEnabled);
        }
        this.classList.toggle('muted');
        this.innerHTML = camEnabled
            ? '<i class="fas fa-video fs-5"></i>'
            : '<i class="fas fa-video-slash fs-5"></i>';
        document.getElementById('cameraOff').classList.toggle('d-none', camEnabled);
    });

    // --- Start Call ---
    let activeCallId = null;
    let callStatusChecker = null;

    document.getElementById('startCallBtn').addEventListener('click', function () {
        document.getElementById('preCallScreen').classList.add('d-none');
        document.getElementById('inCallScreen').classList.remove('d-none');

        // Assign stream to in-call video
        if (localStream) {
            document.getElementById('localVideo').srcObject = localStream;
        }

        // Signaling: Initiate Call
        const formData = new FormData();
        formData.append('action', 'initiate_call');
        formData.append('doctor_id', '<?php echo $doctor_id; ?>');
        formData.append('room_id', '<?php echo $room_id; ?>');

        const ringtoneOut = document.getElementById('ringtoneOutgoing');
        if (ringtoneOut) ringtoneOut.play().catch(() => { });

        fetch('<?php echo $base_url; ?>/includes/signaling.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.call_id) {
                    activeCallId = data.call_id;
                    document.getElementById('callStatus').textContent = 'Ringing...';

                    // Start checking status
                    callStatusChecker = setInterval(() => {
                        fetch('<?php echo $base_url; ?>/includes/signaling.php?action=get_call_status&call_id=' + activeCallId)
                            .then(r => r.json())
                            .then(s => {
                                if (s.status === 'accepted') {
                                    if (ringtoneOut) ringtoneOut.pause();
                                    clearInterval(callStatusChecker);
                                    document.getElementById('callStatus').textContent = 'Connected';
                                    startTimer();
                                } else if (s.status === 'rejected') {
                                    if (ringtoneOut) ringtoneOut.pause();
                                    clearInterval(callStatusChecker);
                                    document.getElementById('callStatus').textContent = 'Call Rejected';
                                    setTimeout(() => window.location.href = 'doctors.php', 2000);
                                }
                            });
                    }, 2000);
                }
            });
    });

    // --- Call Timer ---
    function startTimer() {
        callSeconds = 0;
        callTimer = setInterval(() => {
            callSeconds++;
            const m = String(Math.floor(callSeconds / 60)).padStart(2, '0');
            const s = String(callSeconds % 60).padStart(2, '0');
            document.getElementById('callTimer').textContent = m + ':' + s;
        }, 1000);
    }

    // --- Toggle Mic (In-call) ---
    document.getElementById('toggleMic').addEventListener('click', function () {
        micEnabled = !micEnabled;
        if (localStream) {
            localStream.getAudioTracks().forEach(t => t.enabled = micEnabled);
        }
        this.classList.toggle('muted');
        this.innerHTML = micEnabled
            ? '<i class="fas fa-microphone fs-5"></i>'
            : '<i class="fas fa-microphone-slash fs-5"></i>';
    });

    // --- Toggle Camera (In-call) ---
    document.getElementById('toggleCam').addEventListener('click', function () {
        camEnabled = !camEnabled;
        if (localStream) {
            localStream.getVideoTracks().forEach(t => t.enabled = camEnabled);
        }
        this.classList.toggle('muted');
        this.innerHTML = camEnabled
            ? '<i class="fas fa-video fs-5"></i>'
            : '<i class="fas fa-video-slash fs-5"></i>';
    });

    // --- Toggle Chat Panel ---
    document.getElementById('toggleChat').addEventListener('click', function () {
        document.getElementById('chatPanel').classList.toggle('d-none');
    });
    document.getElementById('closeChatPanel').addEventListener('click', function () {
        document.getElementById('chatPanel').classList.add('d-none');
    });

    // --- Screen Share ---
    document.getElementById('shareScreen').addEventListener('click', async function () {
        try {
            const screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true });
            document.getElementById('localVideo').srcObject = screenStream;
            this.classList.add('active-control', 'muted');
            this.style.background = '#0d6efd';
            this.style.color = 'white';

            screenStream.getVideoTracks()[0].onended = () => {
                document.getElementById('localVideo').srcObject = localStream;
                this.classList.remove('muted');
                this.style.background = '';
                this.style.color = '';
            };
        } catch (err) {
            console.log('Screen sharing cancelled');
        }
    });

    // --- End Call ---
    document.getElementById('endCallBtn').addEventListener('click', function () {
        clearInterval(callTimer);

        if (localStream) {
            localStream.getTracks().forEach(t => t.stop());
        }
        const m = String(Math.floor(callSeconds / 60)).padStart(2, '0');
        const s = String(callSeconds % 60).padStart(2, '0');
        document.getElementById('finalDuration').textContent = m + ':' + s;

        document.getElementById('inCallScreen').classList.add('d-none');
        document.getElementById('callEndedScreen').classList.remove('d-none');
    });

    // --- In-Call Chat ---
    function sendInCallMsg(e) {
        e.preventDefault();
        const input = document.getElementById('inCallMsgInput');
        const msg = input.value.trim();
        if (!msg) return false;

        const chatBody = document.getElementById('inCallChatBody');
        const time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
        chatBody.innerHTML += `
            <div class="d-flex justify-content-end mb-2">
                <div class="p-2 px-3 text-white small" style="background:linear-gradient(135deg,#1565C0,#0D47A1);border-radius:15px 15px 4px 15px;max-width:80%;">
                    ${msg}<br><small class="text-white-50">${time}</small>
                </div>
            </div>`;
        chatBody.scrollTop = chatBody.scrollHeight;

        // Also save via AJAX
        const formData = new FormData();
        formData.append('message', msg);
        formData.append('csrf_token', document.querySelector('[name=csrf_token]')?.value || '');
        fetch('doctor_chat.php?doctor_id=<?php echo $doctor_id; ?>', {
            method: 'POST',
            body: formData
        }).catch(() => { });

        input.value = '';
        return false;
    }
    // --- Make PiP draggable ---
    const pip = document.getElementById('localVideoContainer');
    let isDragging = false, dragStartX, dragStartY, pipStartX, pipStartY;
    pip.addEventListener('mousedown', (e) => {
        isDragging = true;
        dragStartX = e.clientX;
        dragStartY = e.clientY;
        pipStartX = pip.offsetLeft;
        pipStartY = pip.offsetTop;
    });
    document.addEventListener('mousemove', (e) => {
        if (!isDragging) return;
        pip.style.left = (pipStartX + e.clientX - dragStartX) + 'px';
        pip.style.top = (pipStartY + e.clientY - dragStartY) + 'px';
        pip.style.right = 'auto';
        pip.style.bottom = 'auto';
    });
    document.addEventListener('mouseup', () => isDragging = false);
</script>

<?php include '../includes/footer.php'; ?>