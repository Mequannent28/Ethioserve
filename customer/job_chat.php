<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to chat.');
}

$user_id = getCurrentUserId();
$application_id = intval($_GET['application_id'] ?? 0);

if (!$application_id) {
    header("Location: index.php");
    exit();
}

// Fetch application and linked info
$stmt = $pdo->prepare("SELECT a.*, jl.title as job_title, u.full_name as applicant_name, c.company_name, c.user_id as employer_id
                       FROM job_applications a
                       JOIN job_listings jl ON a.job_id = jl.id
                       LEFT JOIN job_companies c ON jl.company_id = c.id
                       JOIN users u ON a.applicant_id = u.id
                       WHERE a.id = ?");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    redirectWithMessage('index.php', 'danger', 'Application not found.');
}

// Security: Check if user is either the applicant or the employer
if ($user_id != $application['applicant_id'] && $user_id != $application['employer_id']) {
    redirectWithMessage('index.php', 'danger', 'Access denied.');
}

$is_employer = ($user_id == $application['employer_id']);
$other_user_name = $is_employer ? $application['applicant_name'] : $application['company_name'];
$receiver_id = $is_employer ? $application['applicant_id'] : $application['employer_id'];

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
            $stmt = $pdo->prepare("INSERT INTO job_messages (application_id, sender_id, receiver_id, message, message_type, attachment_url) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$application_id, $user_id, $receiver_id, $msg, $type, $attachment]);
            header("Location: job_chat.php?application_id=" . $application_id);
            exit();
        } catch (Exception $e) {
            $error = "Failed to send message: " . $e->getMessage();
        }
    }
}

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM job_messages WHERE application_id = ? ORDER BY created_at ASC");
$stmt->execute([$application_id]);
$messages = $stmt->fetchAll();

// Mark messages as read
$pdo->prepare("UPDATE job_messages SET is_read = 1 WHERE application_id = ? AND receiver_id = ? AND is_read = 0")->execute([$application_id, $user_id]);

$base_url = BASE_URL;
include '../includes/header.php';
?>

<div class="job-chat-page py-4" style="background: #f8f9fa; min-vh-100;">
    <div class="container text-start">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div
                        class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <a href="javascript:history.back()"
                                class="btn btn-outline-secondary btn-sm rounded-circle"><i
                                    class="fas fa-arrow-left"></i></a>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($other_user_name); ?></h6>
                                <small
                                    class="text-muted"><?php echo htmlspecialchars($application['job_title']); ?></small>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4" id="chatBody" style="height: 500px; overflow-y: auto;">
                        <?php foreach ($messages as $m):
                            $is_me = ($m['sender_id'] == $user_id); ?>
                            <div
                                class="d-flex mb-3 <?php echo $is_me ? 'justify-content-end' : 'justify-content-start'; ?>">
                                <div class="p-3 rounded-4 shadow-sm"
                                    style="max-width: 80%; background: <?php echo $is_me ? '#0d6efd' : '#fff'; ?>; color: <?php echo $is_me ? '#fff' : '#333'; ?>; border-radius: <?php echo $is_me ? '20px 20px 4px 20px' : '20px 20px 20px 4px'; ?>;">
                                    <div class="message-content">
                                        <?php if ($m['message_type'] === 'text'): ?>
                                            <p class="mb-1"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                                        <?php elseif ($m['message_type'] === 'image'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank">
                                                    <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                        class="img-fluid rounded-3" style="max-height: 200px;">
                                                </a>
                                            </div>
                                            <?php if (!empty($m['message'])): ?>
                                                <p class="mb-1 small"><?php echo htmlspecialchars($m['message']); ?></p>
                                            <?php endif; ?>
                                        <?php elseif ($m['message_type'] === 'location'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $m['message']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-danger bg-opacity-10 p-2 rounded-circle"><i
                                                            class="fas fa-map-marker-alt text-danger"></i></div>
                                                    <div>
                                                        <div class="fw-bold text-dark" style="font-size: 0.75rem;">Shared
                                                            Location</div>
                                                        <div class="text-muted" style="font-size: 0.65rem;">View on Maps</div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php elseif ($m['message_type'] === 'file'): ?>
                                            <div class="mb-2">
                                                <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank"
                                                    class="btn btn-sm btn-light rounded-pill border-0 px-3 w-100 text-start d-flex align-items-center gap-2">
                                                    <div class="bg-primary bg-opacity-10 p-2 rounded-circle"><i
                                                            class="fas fa-file text-primary"></i></div>
                                                    <div class="min-w-0">
                                                        <div class="fw-bold text-dark text-truncate"
                                                            style="font-size: 0.75rem; max-width: 150px;">
                                                            <?php echo basename($m['attachment_url']); ?></div>
                                                    </div>
                                                </a>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <small class="opacity-75" style="font-size: 0.7rem;">
                                        <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        <?php if (empty($messages)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-comments fs-1 text-muted opacity-25 mb-3"></i>
                                <p class="text-muted">Start a conversation about this job application.</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card-footer bg-white border-top p-3">
                        <form method="POST" enctype="multipart/form-data" id="chatForm"
                            class="d-flex gap-2 align-items-end">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="message_type" id="messageType" value="text">

                            <div class="d-flex gap-1">
                                <button type="button" class="btn btn-light rounded-circle" id="attachBtn"
                                    style="width:40px;height:40px;"><i class="fas fa-paperclip text-muted"></i></button>
                                <button type="button" class="btn btn-light rounded-circle" id="locationBtn"
                                    style="width:40px;height:40px;"><i
                                        class="fas fa-map-marker-alt text-muted"></i></button>
                            </div>

                            <input type="file" name="attachment" id="fileInput" class="d-none">

                            <div class="flex-grow-1">
                                <div id="filePreview" class="bg-light p-2 mb-2 rounded-3 d-none">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2 small">
                                            <i class="fas fa-file text-primary"></i> <span id="fileName"
                                                class="text-truncate" style="max-width: 150px;"></span>
                                        </div>
                                        <button type="button" class="btn-close" style="font-size: 0.6rem;"
                                            id="clearFile"></button>
                                    </div>
                                </div>
                                <textarea name="message" id="messageInput"
                                    class="form-control rounded-pill border-0 bg-light px-4 py-2"
                                    placeholder="Type a message..." required rows="1" style="resize:none;"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary rounded-circle shadow-sm flex-shrink-0"
                                style="width: 45px; height: 45px;">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    const messageInput = document.getElementById('messageInput');
    const messageType = document.getElementById('messageType');
    const locationBtn = document.getElementById('locationBtn');
    const attachBtn = document.getElementById('attachBtn');
    const fileInput = document.getElementById('fileInput');
    const filePreview = document.getElementById('filePreview');
    const fileName = document.getElementById('fileName');
    const clearFile = document.getElementById('clearFile');

    locationBtn.onclick = () => {
        if (!navigator.geolocation) { alert("Not supported"); return; }
        locationBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        navigator.geolocation.getCurrentPosition(pos => {
            messageInput.value = `https://www.google.com/maps?q=${pos.coords.latitude},${pos.coords.longitude}`;
            messageType.value = 'location';
            document.getElementById('chatForm').submit();
        }, () => { alert("Error"); locationBtn.innerHTML = '<i class="fas fa-map-marker-alt text-muted"></i>'; });
    };

    attachBtn.onclick = () => fileInput.click();
    fileInput.onchange = function () {
        if (this.files[0]) { fileName.textContent = this.files[0].name; filePreview.classList.remove('d-none'); messageInput.required = false; }
    };
    clearFile.onclick = () => { fileInput.value = ''; filePreview.classList.add('d-none'); messageInput.required = true; };
</script>

<?php include '../includes/footer.php'; ?>