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

<div class="video-call-page min-vh-100 position-relative" style="background: #0a0a1a; overflow: hidden;">

    <!-- Design Background (Like User Screenshot) -->
    <div class="position-absolute top-0 start-0 w-100 h-100"
        style="background: linear-gradient(180deg, #1a237e 0%, #0a0a1a 100%); opacity: 0.8; z-index: 0;"></div>

    <div class="container position-relative py-5" style="z-index: 1;">
        <!-- Doctor Info Header (Matching Screenshot) -->
        <div class="text-center mb-5 animate-fade-in">
            <div class="position-relative d-inline-block mb-3">
                <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=200'); ?>"
                    class="rounded-circle border border-4 border-white shadow-lg" width="100" height="100"
                    style="object-fit: cover;">
                <div class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white"
                    style="width:20px;height:20px;"></div>
            </div>
            <h2 class="text-white fw-bold mb-1"><?php echo htmlspecialchars($doctor['name']); ?></h2>
            <p class="text-white-50 mb-1">
                <i class="fas fa-stethoscope me-1 text-primary"></i>
                <?php echo htmlspecialchars($doctor['specialty_name']); ?>
            </p>
            <p class="text-white-50 small">
                <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                <?php echo htmlspecialchars($doctor['location']); ?>
            </p>
        </div>

        <!-- Main Video Screen -->
        <div class="row justify-content-center">
            <div class="col-lg-10">
                <div class="card border-0 rounded-5 overflow-hidden shadow-2xl bg-dark"
                    style="background: #000; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);">
                    <div class="position-relative" style="aspect-ratio: 16/9;">
                        <!-- Local Video (Main feed for doctor in this phase) -->
                        <video id="localVideo" autoplay muted playsinline class="w-100 h-100"
                            style="object-fit: cover;"></video>

                        <!-- Overlay for "Waiting for Patient" -->
                        <div id="waitingOverlay"
                            class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center bg-black bg-opacity-75">
                            <div class="text-center text-white">
                                <div class="spinner-border text-primary mb-3" role="status"></div>
                                <h4 class="fw-bold">Waiting for patient to connect...</h4>
                                <p class="text-white-50 small">Patient:
                                    <?php echo htmlspecialchars($customer['full_name']); ?>
                                </p>
                            </div>
                        </div>

                        <!-- Patient PiP (Simulated) -->
                        <div class="position-absolute top-4 start-4 p-3" style="top: 20px; left: 20px; width: 180px;">
                            <div
                                class="ratio ratio-4x3 rounded-4 overflow-hidden border border-white border-opacity-25 bg-secondary shadow-lg">
                                <div class="d-flex align-items-center justify-content-center flex-column text-white">
                                    <div class="bg-primary rounded-circle p-2 mb-1"><i class="fas fa-user"></i></div>
                                    <small
                                        style="font-size: 0.6rem;"><?php echo htmlspecialchars($customer['full_name']); ?></small>
                                </div>
                            </div>
                        </div>

                        <!-- Control Buttons (Floating) -->
                        <div
                            class="position-absolute bottom-0 start-50 translate-middle-x mb-4 d-flex gap-3 px-4 py-3 rounded-pill bg-black bg-opacity-50 blur-card">
                            <button id="toggleMic" class="btn btn-light rounded-circle p-3 shadow"
                                style="width:55px;height:55px;"><i class="fas fa-microphone"></i></button>
                            <button id="toggleCam" class="btn btn-light rounded-circle p-3 shadow"
                                style="width:55px;height:55px;"><i class="fas fa-video"></i></button>
                            <button id="endCall" onclick="window.location.href='dashboard.php'"
                                class="btn btn-danger rounded-circle p-3 shadow" style="width:55px;height:55px;"><i
                                    class="fas fa-phone-slash"></i></button>
                            <button class="btn btn-light rounded-circle p-3 shadow" style="width:55px;height:55px;"><i
                                    class="fas fa-cog"></i></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .video-call-page .navbar,
    .video-call-page footer {
        display: none !important;
    }

    .shadow-2xl {
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    }

    .blur-card {
        backdrop-filter: blur(10px);
    }

    .animate-fade-in {
        animation: fadeIn 1s ease-out;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>

<script>
    let localStream = null;
    let micEnabled = true;
    let camEnabled = true;

    async function initCall() {
        try {
            localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true });
            document.getElementById('localVideo').srcObject = localStream;

            // Signaling: Initiate Call as Doctor
            const room_id = 'ethioserve-doc-<?php echo $doctor_user_id; ?>-patient-<?php echo $customer_id; ?>-' + new Date().toISOString().slice(0, 10).replace(/-/g, '');
            const formData = new FormData();
            formData.append('action', 'initiate_call');
            formData.append('receiver_id', '<?php echo $customer_id; ?>');
            formData.append('doctor_id', '<?php echo $doctor['id']; ?>');
            formData.append('room_id', room_id);

            const ringtoneOut = document.getElementById('ringtoneOutgoing');
            if (ringtoneOut) ringtoneOut.play().catch(() => { });

            fetch('<?php echo $base_url; ?>/includes/signaling.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if (data.call_id) {
                        const statusChecker = setInterval(() => {
                            fetch('<?php echo $base_url; ?>/includes/signaling.php?action=get_call_status&call_id=' + data.call_id)
                                .then(r => r.json())
                                .then(s => {
                                    if (s.status === 'accepted') {
                                        if (ringtoneOut) ringtoneOut.pause();
                                        clearInterval(statusChecker);
                                        document.getElementById('waitingOverlay').classList.add('d-none');
                                    } else if (s.status === 'rejected') {
                                        if (ringtoneOut) ringtoneOut.pause();
                                        clearInterval(statusChecker);
                                        alert("Patient rejected the call.");
                                        window.location.href = 'dashboard.php';
                                    }
                                });
                        }, 3000);
                    }
                });

        } catch (err) {
            console.error("Error accessing media devices:", err);
            alert("Please allow camera and microphone access to start the call.");
        }
    }

    document.getElementById('toggleMic').onclick = function () {
        micEnabled = !micEnabled;
        localStream.getAudioTracks()[0].enabled = micEnabled;
        this.innerHTML = micEnabled ? '<i class="fas fa-microphone"></i>' : '<i class="fas fa-microphone-slash"></i>';
        this.classList.toggle('btn-danger', !micEnabled);
        this.classList.toggle('btn-light', micEnabled);
    };

    document.getElementById('toggleCam').onclick = function () {
        camEnabled = !camEnabled;
        localStream.getVideoTracks()[0].enabled = camEnabled;
        this.innerHTML = camEnabled ? '<i class="fas fa-video"></i>' : '<i class="fas fa-video-slash"></i>';
        this.classList.toggle('btn-danger', !camEnabled);
        this.classList.toggle('btn-light', camEnabled);
    };

    initCall();
</script>

<?php include '../includes/footer.php'; ?>