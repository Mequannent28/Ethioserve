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
$stmt = $pdo->prepare("SELECT a.*, jl.title as job_title, jl.posted_by as employer_id, u.full_name as applicant_name, c.company_name
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

// Handle Traditional POST for non-JS environment (optional but good)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax'])) {
    $msg = sanitize($_POST['message'] ?? '');
    $type = sanitize($_POST['message_type'] ?? 'text');
    $attachment = null;

    if (!empty($msg)) {
        try {
            $stmt = $pdo->prepare("INSERT INTO job_messages (application_id, sender_id, receiver_id, message, message_type) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$application_id, $user_id, $receiver_id, $msg, $type]);
            header("Location: job_chat.php?application_id=" . $application_id);
            exit();
        } catch (Exception $e) { }
    }
}

// Fetch Messages with replied-to text
$stmt = $pdo->prepare("
    SELECT m.*, rm.message as replied_message 
    FROM job_messages m
    LEFT JOIN job_messages rm ON m.reply_to_id = rm.id
    WHERE m.application_id = ? 
    ORDER BY m.created_at ASC
");
$stmt->execute([$application_id]);
$messages = $stmt->fetchAll();

// Mark messages as read
$pdo->prepare("UPDATE job_messages SET is_read = 1 WHERE application_id = ? AND receiver_id = ? AND is_read = 0")->execute([$application_id, $user_id]);

$base_url = BASE_URL;
include '../includes/header.php';
?>
<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<div class="job-chat-page py-4" style="background: #f0f2f5; min-vh-100; font-family: 'Inter', sans-serif;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-10 col-xl-8">
                <div class="card border-0 shadow-lg rounded-4 overflow-hidden chat-container-card">
                    <!-- Header -->
                    <div class="card-header bg-white p-3 border-bottom d-flex align-items-center justify-content-between sticky-top z-index-10">
                        <div class="d-flex align-items-center gap-3">
                            <a href="javascript:history.back()" class="btn btn-light rounded-circle shadow-sm">
                                <i class="fas fa-arrow-left text-primary"></i>
                            </a>
                            <div class="d-flex align-items-center gap-2">
                                <div class="avatar-circle">
                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($other_user_name); ?>&background=random" 
                                         class="rounded-circle border" width="45" height="45">
                                    <span class="status-indicator online"></span>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($other_user_name); ?></h6>
                                    <span class="badge bg-primary-subtle text-primary border-0 rounded-pill small" style="font-size: 0.7rem;">
                                        <?php echo htmlspecialchars($application['job_title']); ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-light rounded-circle" data-bs-toggle="dropdown">
                                <i class="fas fa-ellipsis-v"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end rounded-3 shadow border-0">
                                <li><a class="dropdown-item py-2" href="#"><i class="fas fa-search me-2 text-muted"></i> Search</a></li>
                                <li><a class="dropdown-item py-2" href="#"><i class="fas fa-bell-slash me-2 text-muted"></i> Mute</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item py-2 text-danger" href="#"><i class="fas fa-trash me-2"></i> Clear History</a></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Chat Body -->
                    <div class="card-body p-4" id="chatBody" style="height: 550px; overflow-y: auto; background-image: url('https://www.transparenttextures.com/patterns/cubes.png');">
                        <div id="messageList">
                            <?php 
                            $last_msg_id = 0;
                            foreach ($messages as $m): 
                                $is_me = ($m['sender_id'] == $user_id);
                                $last_msg_id = $m['id'];
                            ?>
                                <div class="msg-wrapper <?php echo $is_me ? 'sent' : 'received'; ?> mb-3 animate__animated animate__fadeInUp" id="msg-<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>">
                                    <div class="msg-bubble shadow-sm position-relative">
                                        <!-- Message Context (Telegram Style) -->
                                        <div class="msg-actions">
                                            <button class="btn btn-xs btn-link text-white opacity-50 reply-btn" onclick="prepareReply(<?php echo $m['id']; ?>, '<?php echo addslashes(substr($m['message'], 0, 50)); ?>')"><i class="fas fa-reply"></i></button>
                                            <?php if($is_me): ?>
                                            <button class="btn btn-xs btn-link text-white opacity-50 delete-btn" onclick="deleteMessage(<?php echo $m['id']; ?>)"><i class="fas fa-trash"></i></button>
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
                                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($m['message'])); ?></p>
                                        </div>
                                        
                                        <div class="msg-info d-flex align-items-center justify-content-end gap-1 mt-1 px-2">
                                            <span class="time small opacity-75"><?php echo date('h:i A', strtotime($m['created_at'])); ?></span>
                                            <?php if($is_me): ?>
                                                <i class="fas <?php echo $m['is_read'] ? 'fa-check-double text-info' : 'fa-check'; ?> small"></i>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Chat Footer -->
                    <div class="card-footer bg-white border-top p-3 px-4">
                        <!-- Reply UI -->
                        <div id="replyContainer" class="d-none bg-light p-2 mb-2 rounded-3 border-start border-primary border-4 animate__animated animate__slideInUp">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="px-2">
                                    <small class="text-primary fw-bold d-block"><i class="fas fa-reply me-1"></i> Replying to</small>
                                    <div id="replyText" class="text-muted small text-truncate" style="max-width: 300px;"></div>
                                </div>
                                <button type="button" class="btn-close" onclick="cancelReply()"></button>
                            </div>
                        </div>

                        <form id="ajaxChatForm" class="d-flex gap-2 align-items-end">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="action" value="job_send_message">
                            <input type="hidden" name="application_id" value="<?php echo $application_id; ?>">
                            <input type="hidden" name="receiver_id" value="<?php echo $receiver_id; ?>">
                            <input type="hidden" name="reply_to_id" id="replyToId" value="">
                            
                            <div class="d-flex gap-1 mb-1">
                                <button type="button" class="btn btn-light rounded-circle shadow-sm emoji-btn" style="width: 42px; height: 42px;">
                                    <i class="far fa-smile text-muted"></i>
                                </button>
                                <button type="button" class="btn btn-light rounded-circle shadow-sm" onclick="document.getElementById('fileInput').click()" style="width: 42px; height: 42px;">
                                    <i class="fas fa-paperclip text-muted"></i>
                                </button>
                            </div>

                            <input type="file" id="fileInput" class="d-none">

                            <div class="flex-grow-1">
                                <textarea name="message" id="messageInput" 
                                          class="form-control rounded-4 border-0 bg-light px-4 py-2 msg-input-custom" 
                                          placeholder="Type a message..." rows="1" 
                                          style="resize: none; max-height: 120px; overflow-y: hidden; transition: height 0.1s ease;"></textarea>
                            </div>

                            <button type="submit" id="sendBtn" class="btn btn-primary rounded-circle shadow flex-shrink-0 d-flex align-items-center justify-content-center" 
                                    style="width: 50px; height: 50px; background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%); border: none;">
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
    :root {
        --sent-color: #e3f2fd;
        --recv-color: #ffffff;
        --accent-color: #1B5E20;
    }

    .chat-container-card {
        border: none !important;
        border-radius: 24px !important;
    }

    .msg-wrapper { display: flex; flex-direction: column; width: 100%; }
    .msg-wrapper.received { align-items: flex-start; }
    .msg-wrapper.sent { align-items: flex-end; }

    .msg-bubble {
        max-width: 75%;
        border-radius: 18px;
        transition: all 0.2s;
        z-index: 1;
    }

    .sent .msg-bubble {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
        color: white;
        border-bottom-right-radius: 4px;
    }

    .received .msg-bubble {
        background: white;
        color: #333;
        border-bottom-left-radius: 4px;
    }

    .msg-actions {
        position: absolute;
        top: 0;
        right: 100%;
        display: none;
        background: rgba(0,0,0,0.4);
        border-radius: 10px;
        padding: 2px 5px;
        margin-right: 5px;
        white-space: nowrap;
    }
    .received .msg-actions { right: auto; left: 100%; margin-right: 0; margin-left: 5px; }

    .msg-bubble:hover .msg-actions { display: flex; }

    .avatar-circle { position: relative; }
    .status-indicator {
        position: absolute;
        bottom: 2px;
        right: 2px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 2px solid white;
    }
    .status-indicator.online { background: #4CAF50; }

    /* Custom Transitions */
    .btn-xs { padding: 0.1rem 0.3rem; font-size: 0.7rem; }
    .msg-input-custom:focus {
        background: #fff !important;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05) !important;
        border: 1px solid #1B5E20 !important;
    }
</style>

<script>
    let lastMsgId = <?php echo $last_msg_id; ?>;
    const appId = <?php echo $application_id; ?>;
    const userId = <?php echo $user_id; ?>;
    const baseUrl = '<?php echo $base_url; ?>';
    const chatBody = document.getElementById('chatBody');
    const messageList = document.getElementById('messageList');
    const messageInput = document.getElementById('messageInput');
    const ajaxForm = document.getElementById('ajaxChatForm');
    const sendBtn = document.getElementById('sendBtn');

    // Auto-scroll function
    function scrollToBottom(force = false) {
        if (force || (chatBody.scrollHeight - chatBody.scrollTop - chatBody.clientHeight < 100)) {
            chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
        }
    }
    scrollToBottom(true);

    // Auto-expand textarea
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

        // Handle AJAX Sending
        ajaxForm.onsubmit = async (e) => {
            e.preventDefault();
            const msg = messageInput.value.trim();
            if (!msg) return;

            // 1. Clear input immediately (Optimistic UX)
            const savedMsg = msg;
            messageInput.value = '';
            messageInput.style.height = 'auto';
            cancelReply();

            // 2. Visual feedback
            const originalBtn = sendBtn.innerHTML;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin text-white"></i>';
            sendBtn.disabled = true;

            try {
                const formData = new FormData(ajaxForm);
                formData.set('message', savedMsg); // Ensure we use the trimmed msg
                
                const response = await fetch(`${baseUrl}/customer/job_chat_api.php`, {
                    method: 'POST',
                    body: formData
                });
                
                if (!response.ok) throw new Error('Network response was not ok');
                
                const res = await response.json();
                if(res.success) {
                    messageInput.value = '';
                    messageInput.style.height = '42px';
                    messageInput.style.overflowY = 'hidden';
                    cancelReply();
                    fetchNewMessages(); // Immediate fetch
                } else {
                    // Restore if failed
                    messageInput.value = savedMsg;
                    messageInput.style.height = 'auto';
                    Swal.fire('Chat Error', res.message || 'Failed to send message', 'error');
                }
            } catch (err) {
                console.error("Send error:", err);
                messageInput.value = savedMsg;
                messageInput.style.height = 'auto';
                Swal.fire('Connection Error', 'Could not connect to the chat server.', 'error');
            } finally {
                sendBtn.innerHTML = originalBtn;
                sendBtn.disabled = false;
            }
        };

    // AJAX Polling
    let isFetching = false;
    async function fetchNewMessages() {
        if (isFetching) return;
        isFetching = true;
        try {
            const res = await fetch(`${baseUrl}/api.php?action=get_chat_messages&application_id=${appId}&last_id=${lastMsgId}`);
            if (!res.ok) { isFetching = false; return; }
            const data = await res.json();
            
            if (data.success && data.messages.length > 0) {
                data.messages.forEach(m => {
                    if (document.getElementById(`msg-${m.id}`)) return; // Prevent duplicates

                    const isMe = (m.sender_id == userId);
                    const html = `
                        <div class="msg-wrapper ${isMe ? 'sent' : 'received'} mb-3 animate__animated animate__fadeInUp" id="msg-${m.id}" data-id="${m.id}">
                            <div class="msg-bubble shadow-sm position-relative">
                                <div class="msg-actions">
                                    <button class="btn btn-xs btn-link text-white opacity-50 reply-btn" onclick="prepareReply(${m.id}, '${m.message.substring(0,50).replace(/'/g, "\\'")}')"><i class="fas fa-reply"></i></button>
                                    ${isMe ? `<button class="btn btn-xs btn-link text-white opacity-50 delete-btn" onclick="deleteMessage(${m.id})"><i class="fas fa-trash"></i></button>` : ''}
                                </div>
                                <div class="msg-content p-3 rounded-4">
                                    ${m.replied_message ? `
                                        <div class="replied-snippet bg-black bg-opacity-10 p-2 mb-2 rounded-2 border-start border-3 border-white border-opacity-50 text-white">
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
                    lastMsgId = m.id;
                    
                    // Simple sound on receive
                    if (!isMe) {
                        try { new Audio('https://assets.mixkit.co/active_storage/sfx/2354/2354-preview.mp3').play(); } catch(e){}
                    }
                });
                scrollToBottom();
            }
        } catch (err) { }
        finally { isFetching = false; }
    }

    // Telegram-Style Features
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
        formData.append('action', 'delete_chat_message');
        formData.append('message_id', id);
        formData.append('csrf_token', '<?php echo generateCSRFToken(); ?>');

        const response = await fetch('<?php echo $base_url; ?>/api.php', { method: 'POST', body: formData });
        const res = await response.json();
        if (res.success) {
            document.getElementById(`msg-${id}`).classList.add('animate__fadeOutRight');
            setTimeout(() => document.getElementById(`msg-${id}`).remove(), 500);
        }
    }

    // Polling Interval
    setInterval(fetchNewMessages, 3000); // 3 seconds for real-time feel

    // Handle Enter to send
    messageInput.onkeydown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            ajaxForm.dispatchEvent(new Event('submit'));
        }
    };
</script>

<?php include '../includes/footer.php'; ?>