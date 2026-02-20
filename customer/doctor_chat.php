<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to chat with a doctor.');
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

// Handle Message Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $msg = sanitize($_POST['message'] ?? '');
    $type = sanitize($_POST['message_type'] ?? 'text');
    $attachment = null;

    // Handle File Upload
    if (isset($_FILES['attachment']) && $_FILES['attachment']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/chat/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);

        $file_name = time() . '_' . basename($_FILES['attachment']['name']);
        $target_file = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['attachment']['tmp_name'], $target_file)) {
            $attachment = 'uploads/chat/' . $file_name;
            $ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            $type = in_array($ext, ['jpg', 'jpeg', 'png', 'gif']) ? 'image' : 'file';
        }
    }

    if (!empty($msg) || $attachment) {
        try {
            $stmt = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message, message_type, attachment_url) VALUES (?, 'customer', ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $doctor_id, $user_id, $msg, $type, $attachment]);
            header("Location: doctor_chat.php?doctor_id=" . $doctor_id);
            exit();
        } catch (Exception $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    }
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM doctor_messages WHERE provider_id = ? AND customer_id = ? ORDER BY created_at ASC");
$stmt->execute([$doctor_id, $user_id]);
$messages = $stmt->fetchAll();

// Mark messages from doctor as read
$pdo->prepare("UPDATE doctor_messages SET is_read = 1 WHERE provider_id = ? AND customer_id = ? AND sender_type = 'doctor' AND is_read = 0")->execute([$doctor_id, $user_id]);

include '../includes/header.php';
?>

<div class="doctor-chat-page py-4 min-vh-100" style="background: linear-gradient(135deg, #f0f4ff 0%, #e8f5e9 100%);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-lg rounded-5 overflow-hidden" style="backdrop-filter: blur(10px);">

                    <!-- Chat Header -->
                    <div class="chat-header p-3 border-bottom"
                        style="background: linear-gradient(135deg, #1565C0 0%, #0D47A1 100%);">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <a href="doctors.php"
                                    class="btn btn-outline-light rounded-circle me-3 d-flex align-items-center justify-content-center"
                                    style="width:40px;height:40px;">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <div class="position-relative me-3">
                                    <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=100'); ?>"
                                        class="rounded-circle border border-2 border-white" width="50" height="50"
                                        style="object-fit: cover;">
                                    <span
                                        class="position-absolute bottom-0 end-0 bg-success rounded-circle border border-2 border-white"
                                        style="width:14px;height:14px;"></span>
                                </div>
                                <div>



                                    <h6 class="fw-bold mb-0 text-white">
                                        <?php echo htmlspecialchars($doctor['name']); ?>
                                    </h6>
                                    <span class="small" style="color: rgba(255,255,255,0.8);">
                                        <i class="fas fa-stethoscope me-1"></i>
                                        <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="doctor_video_call.php?doctor_id=<?php echo $doctor_id; ?>"
                                    class="btn btn-outline-light rounded-circle d-flex align-items-center justify-content-center"
                                    style="width:42px;height:42px;" title="Video Call">
                                    <i class="fas fa-video"></i>
                                </a>
                                <div class="dropdown">
                                    <button
                                        class="btn btn-outline-light rounded-circle d-flex align-items-center justify-content-center"
                                        style="width:42px;height:42px;" data-bs-toggle="dropdown">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4">
                                        <li><a class="dropdown-item py-2" href="doctors.php"><i
                                                    class="fas fa-user-md me-2 text-primary"></i>View Profile</a></li>
                                        <li><a class="dropdown-item py-2" href="medical_records.php"><i
                                                    class="fas fa-file-medical me-2 text-success"></i>My Records</a>
                                        </li>
                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item py-2 text-danger" href="#"><i
                                                    class="fas fa-flag me-2"></i>Report Issue</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Doctor Info Banner -->
                    <div class="px-4 py-3 bg-light border-bottom">
                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt text-danger me-1 small"></i>
                                <span class="small text-muted">
                                    <?php echo htmlspecialchars($doctor['location']); ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-star text-warning me-1 small"></i>
                                <span class="small text-muted">
                                    <?php echo $doctor['rating']; ?> Rating
                                </span>
                            </div>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-shield-alt text-success me-1 small"></i>
                                <span class="small text-muted">Encrypted chat</span>
                            </div>
                        </div>
                    </div>

                    <!-- Chat Body -->
                    <div class="chat-body p-4" id="chatBody"
                        style="height: 480px; overflow-y: auto; background: url('data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 width=%2240%22 height=%2240%22><circle cx=%2220%22 cy=%2220%22 r=%221%22 fill=%22rgba(0,0,0,0.03)%22/></svg>') repeat;">

                        <?php if (empty($messages)): ?>
                            <div class="text-center py-5">
                                <div class="mb-3">
                                    <div class="bg-primary bg-opacity-10 d-inline-flex align-items-center justify-content-center rounded-circle"
                                        style="width:80px;height:80px;">
                                        <i class="fas fa-comments text-primary fs-2"></i>
                                    </div>
                                </div>
                                <h5 class="fw-bold text-dark">Start Your Consultation</h5>
                                <p class="text-muted small mb-3">Send a message to
                                    <?php echo htmlspecialchars($doctor['name']); ?>
                                </p>
                                <div class="d-flex flex-wrap gap-2 justify-content-center">
                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 quick-msg"
                                        data-msg="Hello Doctor, I need a consultation.">
                                        👋 Hello Doctor
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 quick-msg"
                                        data-msg="I have some symptoms I'd like to discuss.">
                                        🩺 Discuss Symptoms
                                    </button>
                                    <button class="btn btn-outline-primary btn-sm rounded-pill px-3 quick-msg"
                                        data-msg="I'd like to schedule a follow-up appointment.">
                                        📅 Follow-up
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php foreach ($messages as $m):
                            $is_me = ($m['sender_type'] === 'customer');
                            ?>
                            <div
                                class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?> chat-msg-animate">
                                <?php if (!$is_me): ?>
                                    <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=100'); ?>"
                                        class="rounded-circle me-2 align-self-end" width="32" height="32"
                                        style="object-fit: cover;">
                                <?php endif; ?>
                                <div class="message-bubble p-3 <?php echo $is_me ? 'sent-bubble' : 'received-bubble shadow-sm'; ?>"
                                    style="max-width: 75%; border-radius: <?php echo $is_me ? '20px 20px 4px 20px' : '20px 20px 20px 4px'; ?>;">
                                    <div class="message-content">
                                        <?php if ($m['message_type'] === 'text'): ?>
                                            <p class="mb-1" style="line-height: 1.5;">
                                                <?php echo nl2br(htmlspecialchars($m['message'])); ?>
                                            </p>
                                        <?php elseif ($m['message_type'] === 'image'): ?>
                                            <div class="chat-image-container mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank">
                                                    <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                        class="img-fluid rounded-3 shadow-sm chat-image">
                                                </a>
                                            </div>
                                            <?php if (!empty($m['message'])): ?>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($m['message']); ?></p>
                                            <?php endif; ?>
                                        <?php elseif ($m['message_type'] === 'location'): ?>
                                            <div class="location-message mb-2">
                                                <a href="<?php echo $m['message']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle">
                                                        <i class="fas fa-map-marker-alt text-danger"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">Shared
                                                            Location</div>
                                                        <div class="text-muted" style="font-size: 0.65rem;">Tap to view on
                                                            Google Maps</div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php elseif ($m['message_type'] === 'file'): ?>
                                            <div class="file-message mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle">
                                                        <i class="fas fa-file text-primary"></i>
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div class="fw-bold text-dark text-truncate"
                                                            style="font-size: 0.75rem; max-width: 150px;">
                                                            <?php echo basename($m['attachment_url']); ?></div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div
                                        class="d-flex align-items-center gap-1 <?php echo $is_me ? 'justify-content-end' : ''; ?>">
                                        <small class="<?php echo $is_me ? 'text-white-50' : 'text-muted'; ?>"
                                            style="font-size: 0.65rem;">
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                        </small>
                                        <?php if ($is_me): ?>
                                            <i class="fas fa-check-double small"
                                                style="font-size:0.6rem; color: <?php echo $m['is_read'] ? '#4FC3F7' : 'rgba(255,255,255,0.5)'; ?>;"></i>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <div id="chatEnd"></div>
                    </div>

                    <!-- Chat Footer -->
                    <div class="chat-footer p-3 bg-white border-top">
                        <form method="POST" enctype="multipart/form-data" class="d-flex gap-2 align-items-end"
                            id="chatForm">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="message_type" id="messageType" value="text">

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light rounded-circle flex-shrink-0" id="attachBtn"
                                    style="width:40px;height:40px;" title="Attach File">
                                    <i class="fas fa-paperclip text-muted"></i>
                                </button>
                                <button type="button" class="btn btn-light rounded-circle flex-shrink-0"
                                    id="locationBtn" style="width:40px;height:40px;" title="Share Location">
                                    <i class="fas fa-map-marker-alt text-muted"></i>
                                </button>
                            </div>

                            <input type="file" name="attachment" id="fileInput" class="d-none">

                            <div class="flex-grow-1 position-relative">
                                <div id="filePreview" class="bg-light p-2 mb-2 rounded-4 d-none">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 small">
                                            <i class="fas fa-file text-primary"></i>
                                            <span id="fileName" class="text-truncate" style="max-width: 150px;"></span>
                                        </div>
                                        <button type="button" class="btn-close" style="font-size: 0.6rem;"
                                            id="clearFile"></button>
                                    </div>
                                </div>
                                <textarea name="message" id="messageInput"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-2"
                                    placeholder="Type your message..." required autocomplete="off" rows="1"
                                    style="resize: none; max-height: 120px; overflow-y: auto;"></textarea>
                            </div>
                            <button type="submit" class="btn rounded-circle flex-shrink-0 shadow-sm send-btn"
                                style="width: 45px; height: 45px; display: flex; align-items: center; justify-content: center; background: linear-gradient(135deg, #1565C0, #0D47A1); color: white;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                        <div class="text-center mt-2">
                            <small class="text-muted" style="font-size: 0.7rem;">
                                <i class="fas fa-lock me-1"></i>Messages are private between you and your doctor
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-image {
        max-height: 250px;
        width: auto;
        border-radius: 12px;
        transition: transform 0.2s;
    }

    .chat-image:hover {
        transform: scale(1.02);
    }

    .chat-msg-animate {
        animation: fadeInMsg 0.3s ease-out;
    }

    @keyframes fadeInMsg {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .send-btn:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(21, 101, 192, 0.4) !important;
    }

    .send-btn {
        transition: all 0.2s ease;
    }

    .quick-msg:hover {
        background-color: #1565C0 !important;
        color: white !important;
        border-color: #1565C0 !important;
    }

    #messageInput:focus {
        box-shadow: 0 0 0 3px rgba(21, 101, 192, 0.15);
        background-color: #fff !important;
    }

    .chat-body::-webkit-scrollbar {
        width: 5px;
    }

    .chat-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .chat-body::-webkit-scrollbar-thumb {
        background: #ccc;
        border-radius: 10px;
    }

    .chat-body::-webkit-scrollbar-thumb:hover {
        background: #999;
    }

    .sent-bubble {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%) !important;
        color: #ffffff !important;
    }

    .received-bubble {
        background: #212529 !important;
        color: #ffffff !important;
    }

    .message-bubble {
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        transition: transform 0.2s;
    }

    .message-bubble:hover {
        transform: translateY(-2px);
    }
</style>

<script>
    // Auto scroll to bottom
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    // Auto-resize textarea
    const messageInput = document.getElementById('messageInput');
    messageInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Submit on Enter (Shift+Enter for new line)
    messageInput.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            if (this.value.trim()) {
                document.getElementById('chatForm').submit();
            }
        }
    });

    // Quick message buttons
    document.querySelectorAll('.quick-msg').forEach(btn => {
        btn.addEventListener('click', function () {
            messageInput.value = this.dataset.msg;
            messageInput.focus();
        });
    });

    // Geolocation
    const locationBtn = document.getElementById('locationBtn');
    const messageType = document.getElementById('messageType');

    locationBtn.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert("Geolocation is not supported by your browser");
            return;
        }

        locationBtn.disabled = true;
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-muted"></i>';

        navigator.geolocation.getCurrentPosition(
            (position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;
                const mapsUrl = `https://www.google.com/maps?q=${lat},${lon}`;

                messageInput.value = mapsUrl;
                messageType.value = 'location';
                document.getElementById('chatForm').submit();
            },
            () => {
                alert("Unable to retrieve your location");
                locationBtn.disabled = false;
                locationBtn.innerHTML = '<i class="fas fa-map-marker-alt text-muted"></i>';
            }
        );
    });

    // File Attachment Logic
    const attachBtn = document.getElementById('attachBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const clearFile = document.getElementById('clearFile');

    attachBtn.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', function () {
        if (this.files && this.files[0]) {
            fileName.textContent = this.files[0].name;
            filePreview.classList.remove('d-none');
            messageInput.required = false;
        }
    });

    clearFile.addEventListener('click', () => {
        fileInput.value = '';
        filePreview.classList.add('d-none');
        messageInput.required = true;
    });

    // Auto-refresh messages every 5 seconds
    setInterval(() => {
        fetch(window.location.href)
            .then(r => r.text())
            .then(html => {
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');
                const newChatBody = doc.getElementById('chatBody');
                if (newChatBody && chatBody.innerHTML !== newChatBody.innerHTML) {
                    const wasAtBottom = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 50;
                    chatBody.innerHTML = newChatBody.innerHTML;
                    if (wasAtBottom) chatBody.scrollTop = chatBody.scrollHeight;
                }
            }).catch(() => { });
    }, 5000);
</script>

<?php include '../includes/footer.php'; ?>