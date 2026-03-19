<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isLoggedIn()) {
    redirectWithMessage('../login.php', 'warning', 'Please login to chat with a doctor.');
}

$user_id = getCurrentUserId();
$base_url = BASE_URL;
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

// Handle Traditional POST for non-JS (optional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $msg = sanitize($_POST['message'] ?? '');
    $type = sanitize($_POST['message_type'] ?? 'text');
    if (!empty($msg)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message, message_type) VALUES (?, 'customer', ?, ?, ?, ?)");
            $stmt->execute([$user_id, $doctor_id, $user_id, $msg, $type]);
            header("Location: " . $_SERVER['PHP_SELF'] . "?doctor_id=" . $doctor_id);
            exit();
        } catch (Exception $e) { }
    }
}

// Fetch Messages with replied-to text
$stmt = $pdo->prepare("
    SELECT m.*, rm.message as replied_message 
    FROM doctor_messages m
    LEFT JOIN doctor_messages rm ON m.reply_to_id = rm.id
    WHERE m.provider_id = ? AND m.customer_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$doctor_id, $user_id]);
$messages = $stmt->fetchAll();
$last_msg_id = 0;
foreach($messages as $m) $last_msg_id = max($last_msg_id, $m['id']);

// Mark messages from doctor as read
$pdo->prepare("UPDATE doctor_messages SET is_read = 1 WHERE provider_id = ? AND customer_id = ? AND sender_type = 'doctor' AND is_read = 0")->execute([$doctor_id, $user_id]);

include '../includes/header.php';
?>

<div class="doctor-chat-page py-4 min-vh-100" style="background: #f0f2f5; font-family: 'Inter', sans-serif;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden chat-container-card">
                    <!-- Header -->
                    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between sticky-top z-index-10">
                        <div class="d-flex align-items-center gap-3">
                            <a href="doctors.php" class="btn btn-light rounded-circle shadow-sm">
                                <i class="fas fa-arrow-left text-primary"></i>
                            </a>
                            <div class="avatar-circle">
                                <img src="<?php echo htmlspecialchars($doctor['image_url'] ?: 'https://images.unsplash.com/photo-1612349317150-e413f6a5b16d?w=100'); ?>" 
                                     class="rounded-circle border" width="45" height="45">
                                <span class="status-indicator online"></span>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($doctor['name']); ?></h6>
                                <span class="badge bg-primary-subtle text-primary border-0 rounded-pill small" style="font-size: 0.7rem;">
                                    <i class="fas fa-stethoscope me-1"></i> <?php echo htmlspecialchars($doctor['specialty_name']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                             <a href="doctor_video_call.php?doctor_id=<?php echo $doctor_id; ?>&is_video=0" class="btn btn-light rounded-circle shadow-sm" title="Voice Call"><i class="fas fa-phone text-muted"></i></a>
                             <a href="doctor_video_call.php?doctor_id=<?php echo $doctor_id; ?>&is_video=1" class="btn btn-light rounded-circle shadow-sm" title="Video Call"><i class="fas fa-video text-muted"></i></a>
                        </div>
                    </div>

                    <!-- Chat Body -->
                    <div class="card-body p-4" id="chatBody" style="height: 550px; overflow-y: auto; background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
                        <div id="messageList">
                            <?php foreach ($messages as $m): 
                                $is_me = ($m['sender_type'] === 'customer'); ?>
                                <div class="msg-wrapper <?php echo $is_me ? 'sent' : 'received'; ?> mb-3 animate__animated animate__fadeInUp" id="msg-<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>">
                                    <div class="msg-bubble shadow-sm position-relative">
                                        <div class="msg-actions">
                                            <button class="btn btn-xs btn-link text-white opacity-50" onclick="prepareReply(<?php echo $m['id']; ?>, '<?php echo addslashes(substr($m['message'] ?? '', 0, 50)); ?>')"><i class="fas fa-reply"></i></button>
                                            <?php if($is_me): ?>
                                            <button class="btn btn-xs btn-link text-white opacity-50" onclick="deleteMessage(<?php echo $m['id']; ?>)"><i class="fas fa-trash"></i></button>
                                            <?php endif; ?>
                                        </div>

                                        <div class="msg-content p-3 rounded-4">
                                            <?php if ($m['replied_message']): ?>
                                                <div class="replied-snippet bg-black bg-opacity-10 p-2 mb-2 rounded-2 border-start border-3 border-white border-opacity-50">
                                                    <small class="d-block opacity-75 fw-bold" style="font-size: 0.65rem;">Replying to</small>
                                                    <small class="text-truncate d-block" style="max-width: 200px; font-size: 0.75rem;"><?php echo htmlspecialchars(substr($m['replied_message'], 0, 100)); ?>...</small>
                                                </div>
                                            <?php endif; ?>
                                            <?php if ($m['message_type'] === 'image'): ?>
                                                <div class="mb-2">
                                                    <a href="<?php echo $base_url . '/' . $m['attachment_url']; ?>" target="_blank">
                                                        <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>" class="img-fluid rounded-3">
                                                    </a>
                                                </div>
                                            <?php endif; ?>
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($m['message'] ?? '')); ?></p>
                                        </div>
                                        
                                        <div class="msg-info d-flex align-items-center justify-content-end gap-1 mt-1 px-2">
                                            <span class="time small opacity-75" style="font-size: 0.65rem;"><?php echo date('h:i A', strtotime($m['created_at'])); ?></span>
                                            <?php if($is_me): ?>
                                                <i class="fas <?php echo $m['is_read'] ? 'fa-check-double text-info' : 'fa-check'; ?> small" style="font-size: 0.6rem;"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="card-footer bg-white border-top p-3 px-4">
                        <form id="ajaxDoctorForm" class="d-flex gap-2 align-items-end">
                            <input type="hidden" name="action" value="doctor_send_message">
                            <input type="hidden" name="doctor_id" value="<?php echo $doctor_id; ?>">
                            
                            <div class="d-flex gap-1 mb-1">
                                <button type="button" class="btn btn-light rounded-circle shadow-sm" onclick="document.getElementById('fileInput').click()" style="width: 42px; height: 42px;">
                                    <i class="fas fa-paperclip text-muted"></i>
                                </button>
                                <button type="button" class="btn btn-light rounded-circle shadow-sm" onclick="toggleEmojiPicker()" style="width: 42px; height: 42px;">
                                    <i class="far fa-smile text-muted"></i>
                                </button>
                            </div>

                            <div class="flex-grow-1">
                                <!-- Reply UI -->
                                <div id="replyContainer" class="d-none bg-light p-2 mb-2 rounded-3 border-start border-primary border-4 animate__animated animate__slideInUp">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="px-2">
                                            <small class="text-primary fw-bold d-block"><i class="fas fa-reply me-1"></i> Replying to</small>
                                            <div id="replyText" class="text-muted small text-truncate" style="max-width: 300px;"></div>
                                        </div>
                                        <button type="button" class="btn-close" style="font-size: 0.6rem;" onclick="cancelReply()"></button>
                                    </div>
                                </div>
                                <input type="hidden" name="reply_to_id" id="replyToId" value="">
                                <input type="hidden" name="message_type" id="messageType" value="text">
                                <textarea name="message" id="messageInput" 
                                          class="form-control rounded-4 border-0 bg-light px-4 py-2 msg-input-custom" 
                                          placeholder="Type a message..." rows="1" 
                                          style="resize: none; max-height: 120px; overflow-y: hidden; transition: height 0.1s ease;"></textarea>
                            </div>

                            <button type="submit" id="sendBtn" class="btn btn-primary rounded-circle shadow flex-shrink-0 d-flex align-items-center justify-content-center" 
                                    style="width: 50px; height: 50px; background: linear-gradient(135deg, #0D47A1 0%, #1565C0 100%); border: none;">
                                <i class="fas fa-paper-plane text-white"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .chat-container-card { border-radius: 24px !important; }
    .msg-wrapper { display: flex; flex-direction: column; width: 100%; }
    .msg-wrapper.received { align-items: flex-start; }
    .msg-wrapper.sent { align-items: flex-end; }
    .msg-bubble { max-width: 75%; border-radius: 18px; position: relative; }
    .sent .msg-bubble { background: linear-gradient(135deg, #0D47A1, #1565C0); color: white; border-bottom-right-radius: 4px; }
    .received .msg-bubble { background: white; color: #333; border-bottom-left-radius: 4px; border: 1px solid #eee; }
    .msg-actions { position: absolute; top:0; right:100%; display:none; background:rgba(0,0,0,0.4); border-radius:10px; padding:2px 5px; margin-right:5px; }
    .received .msg-actions { right:auto; left:100%; margin-left:5px; }
    .msg-bubble:hover .msg-actions { display:flex; }
    .avatar-circle { position: relative; }
    .online { background: #4CAF50; width:12px; height:12px; border-radius:50%; border:2px solid white; position:absolute; bottom:2px; right:2px; }
    .btn-xs { padding: 0.1rem 0.3rem; font-size: 0.7rem; }
    .msg-input-custom:focus {
        background: #fff !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1) !important;
        border: 1px solid #0D47A1 !important;
    }
</style>

<script>
    let lastMsgId = <?php echo $last_msg_id; ?>;
    const doctorId = <?php echo $doctor_id; ?>;
    const customerId = <?php echo $user_id; ?>;
    const chatBody = document.getElementById('chatBody');
    const messageList = document.getElementById('messageList');
    const messageInput = document.getElementById('messageInput');
    const ajaxForm = document.getElementById('ajaxDoctorForm');

    function scrollToBottom(force = false) {
        if (force || (chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight < 100)) {
            chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
        }
    }
    scrollToBottom(true);

    messageInput.addEventListener('input', function() {
        this.style.height = '42px'; // Base height
        let newHeight = this.scrollHeight;
        if (newHeight > 120) {
            newHeight = 120;
            this.style.overflowY = 'auto';
        } else {
            this.style.overflowY = 'hidden';
        }
        this.style.height = newHeight + 'px';
    });

    ajaxForm.onsubmit = async (e) => {
        e.preventDefault();
        const msg = messageInput.value.trim();
        if (!msg) return;

        messageInput.value = '';
        messageInput.style.height = '42px';
        messageInput.style.overflowY = 'hidden';

        try {
            const formData = new FormData(ajaxForm);
            formData.append('ajax', '1');
            formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

            const response = await fetch('doctor_chat_api.php', { method: 'POST', body: formData });
            const res = await response.json();
            if(res.success) fetchNewMessages();
        } catch (err) { }
    };

    async function fetchNewMessages() {
        try {
            const res = await fetch(`<?php echo $base_url; ?>/api.php?action=get_doctor_messages&provider_id=${doctorId}&customer_id=${customerId}&last_id=${lastMsgId}`);
            const data = await res.json();
            
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(m => {
                    if (document.getElementById(`msg-${m.id}`)) return;
                    const isMe = (m.sender_type === 'customer');
                    const html = `
                        <div class="msg-wrapper ${isMe ? 'sent' : 'received'} mb-3 animate__animated animate__fadeInUp" id="msg-${m.id}" data-id="${m.id}">
                            <div class="msg-bubble shadow-sm position-relative">
                                <div class="msg-actions">
                                    <button class="btn btn-xs btn-link text-white opacity-50 reply-btn" onclick="prepareReply(${m.id}, '${m.message.substring(0,50).replace(/'/g, "\\'")}')"><i class="fas fa-reply"></i></button>
                                    ${isMe ? `<button class="btn btn-xs btn-link text-white opacity-50 delete-btn" onclick="deleteMessage(${m.id})"><i class="fas fa-trash"></i></button>` : ''}
                                </div>
                                <div class="msg-content p-3 rounded-4">
                                    ${m.replied_message ? `
                                        <div class="replied-snippet bg-black bg-opacity-10 p-2 mb-2 rounded-2 border-start border-3 ${isMe ? 'border-white' : 'border-primary'} border-opacity-50">
                                            <small class="d-block opacity-75 fw-bold" style="font-size: 0.65rem;">Replying to</small>
                                            <small class="text-truncate d-block" style="max-width: 200px; font-size: 0.75rem;">${m.replied_message.substring(0,100)}...</small>
                                        </div>
                                    ` : ''}
                                    <p class="mb-0">${m.message.replace(/\n/g, '<br>')}</p>
                                </div>
                                <div class="msg-info d-flex align-items-center justify-content-end gap-1 mt-1 px-2 text-muted">
                                    <span class="time small opacity-75" style="font-size: 0.65rem;">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                    ${isMe ? `<i class="fas ${m.is_read == 1 ? 'fa-check-double text-info' : 'fa-check'} small" style="font-size: 0.6rem;"></i>` : ''}
                                </div>
                            </div>
                        </div>
                    `;
                    messageList.insertAdjacentHTML('beforeend', html);
                    lastMsgId = Math.max(lastMsgId, m.id);
                });
                scrollToBottom();
            }
        } catch (err) { }
    }

    function prepareReply(id, text) {
        document.getElementById('replyContainer').classList.remove('d-none');
        document.getElementById('replyText').textContent = text;
        document.getElementById('replyToId').value = id;
        messageInput.focus();
    }

    function cancelReply() {
        document.getElementById('replyContainer').classList.add('d-none');
        document.getElementById('replyToId').value = '';
    }

    async function deleteMessage(id) {
        if (!confirm("Delete this message?")) return;
        const formData = new FormData();
        formData.append('action', 'delete_doctor_chat_message');
        formData.append('message_id', id);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');
        const res = await (await fetch('<?php echo $base_url; ?>/api.php', { method: 'POST', body: formData })).json();
        if (res.success) {
            document.getElementById(`msg-${id}`).remove();
        }
    }

    setInterval(fetchNewMessages, 3000);

    messageInput.onkeydown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            ajaxForm.dispatchEvent(new Event('submit'));
        }
    };
</script>

<?php include '../includes/footer.php'; ?>