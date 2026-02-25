<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn() || getCurrentUserRole() !== 'doctor') {
    redirectWithMessage('../login.php', 'warning', 'Please login as a doctor.');
}

$doctor_user_id = getCurrentUserId();
$customer_id = intval($_GET['customer_id'] ?? 0);

// Fetch provider (doctor) info
$stmt = $pdo->prepare("SELECT p.*, s.name as specialty_name 
                       FROM health_providers p 
                       LEFT JOIN health_specialties s ON p.specialty_id = s.id 
                       WHERE p.user_id = ?");
$stmt->execute([$doctor_user_id]);
$doctor = $stmt->fetch();

if (!$customer_id || !$doctor) {
    header("Location: dashboard.php");
    exit();
}

// Fetch customer info
$stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    redirectWithMessage('dashboard.php', 'danger', 'Patient not found.');
}

include '../includes/header.php';
?>

<div class="video-call-page min-vh-100 position-relative"
    style="background: #050510; overflow: hidden; font-family: 'Poppins', sans-serif;">

    <!-- Premium Background Gradients -->
    <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 10% 10%, rgba(27, 94, 32, 0.15) 0%, transparent 40%), 
                    radial-gradient(circle at 90% 90%, rgba(30, 136, 229, 0.15) 0%, transparent 40%);">
    </div>

    <div class="container-fluid position-relative py-4 h-100 d-flex flex-column" style="z-index: 1;">
        <!-- Doctor Info Header (Matching Screenshot Style) -->
        <div class="text-center mb-4 animate__animated animate__fadeInDown">
            <div class="position-relative d-inline-block mb-2">
                <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200'); ?>"
                    class="rounded-circle border border-3 border-white shadow-lg" width="80" height="80"
                    style="object-fit: cover;">
                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white"
                    style="width:16px;height:16px;"></div>
            </div>
            <h4 class="text-white fw-bold mb-0"><?php echo htmlspecialchars($doctor['name']); ?></h4>
            <p class="text-white-50 small mb-0">
                <i class="fas fa-stethoscope me-1 text-primary"></i>
                <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                <span class="mx-2">|</span>
                <i class="fas fa-user-injured me-1 text-warning"></i>
                Patient: <?php echo htmlspecialchars($customer['full_name']); ?>
            </p>
        </div>

        <!-- Main Content Area -->
        <div class="row flex-grow-1 justify-content-center align-items-center">
            <div class="col-lg-10 h-100">
                <div class="card border-0 rounded-5 overflow-hidden shadow-2xl h-100 position-relative"
                    style="background: #000; box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.7);">

                    <!-- Jitsi Container -->
                    <div id="jitsi-container" class="w-100 h-100 d-none animate__animated animate__fadeIn"></div>

                    <!-- "Waiting for Patient" UI -->
                    <div id="waitingOverlay"
                        class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-75 z-2">
                        <div class="text-center text-white animate__animated animate__pulse animate__infinite">
                            <div class="spinner-grow text-primary mb-4" role="status"
                                style="width: 4rem; height: 4rem;"></div>
                            <h3 class="fw-bold tracking-tight">CALLING PATIENT...</h3>
                            <p class="text-white-50">Please wait while we connect you to
                                <?php echo htmlspecialchars($customer['full_name']); ?>
                            </p>

                            <div class="mt-5">
                                <button onclick="window.location.href='dashboard.php'"
                                    class="btn btn-outline-danger btn-lg rounded-pill px-5">
                                    <i class="fas fa-times me-2"></i>Cancel Call
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Custom Floating Controls -->
                    <div id="callControls"
                        class="position-absolute bottom-0 start-50 translate-middle-x mb-4 d-none gap-3 px-5 py-3 rounded-pill bg-black bg-opacity-40 blur-card z-3">
                        <button id="toggleMic"
                            class="btn btn-blur-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                            style="width:55px;height:55px;"><i class="fas fa-microphone fs-4"></i></button>
                        <button id="toggleCam"
                            class="btn btn-blur-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                            style="width:55px;height:55px;"><i class="fas fa-video fs-4"></i></button>
                        <button id="endCallBtn"
                            class="btn btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center hover-scale-lg"
                            style="width:70px;height:70px;"><i class="fas fa-phone-slash fs-3"></i></button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src='https://meet.jit.si/external_api.js'></script>
<style>
    .video-call-page .navbar,
    .video-call-page footer {
        display: none !important;
    }

    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .blur-card {
        backdrop-filter: blur(15px);
        border: 1px solid rgba(255, 255, 255, 0.1);
    }

    .btn-blur-light {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        transition: all 0.3s ease;
    }

    .btn-blur-light:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateY(-2px);
        color: white;
    }

    .hover-scale-lg:hover {
        transform: scale(1.1);
    }

    .tracking-tight {
        letter-spacing: -0.05em;
    }

    .z-2 {
        z-index: 2;
    }

    .z-3 {
        z-index: 3;
    }
</style>

<script>
    let api = null;
    let callId = null;
    const roomId = 'ethioserve-doc-<?php echo $doctor_user_id; ?>-patient-<?php echo $customer_id; ?>-' + Date.now();
    const signalingUrl = '<?php echo $base_url; ?>/signaling.php';
    const ringtoneOut = new Audio('https://assets.mixkit.co/sfx/preview/mixkit-outgoing-call-waiting-ringtone-1353.mp3');

    async function initCall() {
        try {
            // Initiate Call as Doctor
            const formData = new FormData();
            formData.append('action', 'initiate_call');
            formData.append('receiver_id', '<?php echo $customer_id; ?>');
            formData.append('doctor_id', '<?php echo $doctor['id']; ?>');
            formData.append('room_id', roomId);
            formData.append('call_type', 'telemed');
            formData.append('is_video', 1);

            ringtoneOut.play().catch(() => { });

            fetch(signalingUrl, { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.call_id) {
                        callId = data.call_id;
                        checkCallResponse();
                    } else {
                        alert("Error: " + data.error);
                        window.location.href = 'dashboard.php';
                    }
                })
                .catch(err => {
                    console.error("Signaling error:", err);
                    alert("Network error starting call.");
                    window.location.href = 'dashboard.php';
                });

        } catch (err) {
            console.error("Initialization error:", err);
        }
    }

    function checkCallResponse() {
        if (!callId) return;
        fetch(signalingUrl + '?action=get_call_status&call_id=' + callId)
            .then(r => r.json())
            .then(s => {
                if (s.status === 'accepted') {
                    ringtoneOut.pause();
                    initializeJitsi();
                } else if (s.status === 'rejected') {
                    ringtoneOut.pause();
                    alert("Patient rejected the call.");
                    window.location.href = 'dashboard.php';
                } else if (s.status === 'ended') {
                    ringtoneOut.pause();
                    window.location.href = 'dashboard.php';
                } else {
                    setTimeout(checkCallResponse, 3000);
                }
            })
            .catch(() => setTimeout(checkCallResponse, 5000));
    }

    function initializeJitsi() {
        document.getElementById('waitingOverlay').classList.add('d-none');
        document.getElementById('jitsi-container').classList.remove('d-none');
        document.getElementById('callControls').classList.remove('d-none');
        document.getElementById('callControls').classList.add('d-flex');

        const domain = 'meet.jit.si';
        const options = {
            roomName: roomId,
            width: '100%',
            height: '100%',
            parentNode: document.querySelector('#jitsi-container'),
            userInfo: { displayName: 'Dr. <?php echo addslashes($doctor['name']); ?>' },
            interfaceConfigOverwrite: { TOOLBAR_BUTTONS: [], SETTINGS_SECTIONS: [], SHOW_JITSI_WATERMARK: false },
            configOverwrite: {
                startWithAudioMuted: false,
                startWithVideoMuted: false,
                prejoinPageEnabled: false
            }
        };
        api = new JitsiMeetExternalAPI(domain, options);

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
        ringtoneOut.pause();
        window.location.href = 'dashboard.php';
    }

    document.getElementById('endCallBtn').onclick = endCall;
    document.getElementById('toggleMic').onclick = function () {
        api.executeCommand('toggleAudio');
        this.classList.toggle('btn-blur-light');
        this.classList.toggle('btn-danger');
    };
    document.getElementById('toggleCam').onclick = function () {
        api.executeCommand('toggleVideo');
        this.classList.toggle('btn-blur-light');
        this.classList.toggle('btn-danger');
    };

    initCall();
</script>

<?php include '../includes/footer.php'; ?>