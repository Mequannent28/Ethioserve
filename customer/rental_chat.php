<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();
$user_id = getCurrentUserId();
$request_id = intval($_GET['request_id'] ?? 0);

if (!$request_id) {
    header("Location: rental_requests.php");
    exit();
}

// Fetch request and verify ownership
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.user_id as owner_id, 
           o.full_name as owner_name, o.phone as owner_phone
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users o ON l.user_id = o.id
    WHERE rr.id = ? AND rr.customer_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    redirectWithMessage('rental_requests.php', 'danger', 'Access denied.');
}

$owner_name = $request['owner_name'] ?: 'Property Owner';

// Fetch Messages
$stmt = $pdo->prepare("SELECT * FROM rental_chat_messages WHERE request_id = ? ORDER BY created_at ASC");
$stmt->execute([$request_id]);
$messages = $stmt->fetchAll();

// Mark messages from owner as read
$pdo->prepare("UPDATE rental_chat_messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$request_id, $user_id]);

$last_msg_id = 0;
foreach($messages as $m) $last_msg_id = max($last_msg_id, $m['id']);

$base_url = BASE_URL;
include '../includes/header.php';
?>
<div class="container py-4" style="font-family: 'Poppins', sans-serif;">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card border-0 shadow rounded-4 overflow-hidden" style="height: calc(100vh - 120px); display: flex; flex-direction: column;">
                
                <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3">
                        <a href="rental_requests.php" class="btn btn-light rounded-circle"><i class="fas fa-arrow-left"></i></a>
                        <div class="bg-primary-green text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:40px;height:40px;">
                            <?php echo strtoupper(substr($owner_name, 0, 1)); ?>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($owner_name); ?></h6>
                            <small class="text-muted"><?php echo htmlspecialchars($request['listing_title']); ?></small>
                        </div>
                    </div>
                </div>

                <div class="card-body p-4 bg-light overflow-y-auto" id="chatContainer" style="flex:1; position: relative;">
                    <?php if (empty($messages)): ?>
                        <div class="text-center my-auto py-5 opacity-50" id="noMessages">
                            <i class="fas fa-comments fs-2 mb-3"></i>
                            <p>No messages yet. Send a message to the owner.</p>
                        </div>
                    <?php endif; ?>
                    
                    <div id="messagesList">
                        <?php foreach ($messages as $msg): 
                            $is_sent = ($msg['sender_id'] == $user_id);
                        ?>
                            <div class="msg-wrapper <?php echo $is_sent ? 'sent' : 'received'; ?> mb-3 d-flex flex-column" id="msg-<?php echo $msg['id']; ?>">
                                <div class="msg-bubble p-3 rounded-4 shadow-sm <?php echo $is_sent ? 'bg-primary-green text-white align-self-end' : 'bg-white align-self-start'; ?>" style="max-width: 80%; border-bottom-<?php echo $is_sent ? 'right' : 'left'; ?>-radius: 4px;">
                                    <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                    <small class="d-block mt-1 opacity-75 text-end" style="font-size:0.65rem;">
                                        <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                        <?php if ($is_sent): ?>
                                            <i class="fas fa-check-double ms-1 <?php echo $msg['is_read'] ? 'text-info' : ''; ?>"></i>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Typing Indicator -->
                    <div id="typingIndicator" class="d-none mb-3">
                        <div class="bg-white p-2 px-3 rounded-pill shadow-sm d-inline-flex align-items-center gap-2" style="font-size: 0.75rem; color: #666; border: 1px solid #eee;">
                            <div class="typing-dots">
                                <span></span><span></span><span></span>
                            </div>
                            <span><?php echo htmlspecialchars($owner_name); ?> is typing...</span>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white p-3">
                    <form id="chatForm" class="d-flex gap-2 align-items-end">
                        <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                        <input type="hidden" name="receiver_id" value="<?php echo $request['owner_id']; ?>">
                        
                        <textarea id="messageInput" name="message" class="form-control rounded-4 border-0 bg-light p-3 px-4 shadow-none" rows="1" placeholder="Type message to owner..." style="resize:none;" required></textarea>
                        
                        <button type="submit" class="btn btn-primary-green rounded-circle d-flex align-items-center justify-content-center" style="width:50px; height:50px; flex-shrink:0;">
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-green { background: #1B5E20 !important; color: #fff !important; }
    #messageInput:focus { background: #fff !important; box-shadow: 0 4px 12px rgba(0,0,0,0.05) !important; border: 1px solid #1B5E20 !important; }
    
    .typing-dots span { width: 4px; height: 4px; background: #999; border-radius: 50%; display: inline-block; animation: typing 1.4s infinite ease-in-out; }
    .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
    .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
    @keyframes typing {
        0%, 80%, 100% { transform: scale(0); }
        40% { transform: scale(1); }
    }
</style>

<script>
    const chatContainer = document.getElementById('chatContainer');
    const messagesList = document.getElementById('messagesList');
    const messageInput = document.getElementById('messageInput');
    const chatForm = document.getElementById('chatForm');
    const typingIndicator = document.getElementById('typingIndicator');
    const noMessages = document.getElementById('noMessages');
    let lastId = <?php echo $last_msg_id; ?>;
    let isTypingSent = false;

    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }
    scrollToBottom();

    chatForm.onsubmit = async (e) => {
        e.preventDefault();
        const msg = messageInput.value.trim();
        if (!msg) return;

        const formData = new FormData(chatForm);
        messageInput.value = '';
        messageInput.style.height = 'auto';
        if (noMessages) noMessages.remove();

        try {
            const response = await fetch('rental_chat_api.php?action=send', { method: 'POST', body: formData });
            const res = await response.json();
            if (res.success) fetchMessages();
        } catch (err) { console.error(err); }
    };

    async function fetchMessages() {
        try {
            const response = await fetch(`rental_chat_api.php?action=fetch&request_id=<?php echo $request_id; ?>&last_id=${lastId}`);
            const data = await response.json();
            
            if (data.success) {
                // Toggle Typing
                if (data.is_typing) {
                    typingIndicator.classList.remove('d-none');
                } else {
                    typingIndicator.classList.add('d-none');
                }

                if (data.messages.length > 0) {
                    data.messages.forEach(m => {
                        if (document.getElementById(`msg-${m.id}`)) return;
                        const isMe = (m.sender_id == <?php echo $user_id; ?>);
                        const html = `
                            <div class="msg-wrapper ${isMe ? 'sent' : 'received'} mb-3 d-flex flex-column" id="msg-${m.id}">
                                <div class="msg-bubble p-3 rounded-4 shadow-sm ${isMe ? 'bg-primary-green text-white align-self-end' : 'bg-white align-self-start'}" style="max-width: 80%; border-bottom-${isMe ? 'right' : 'left'}-radius: 4px;">
                                    <div>${m.message.replace(/\n/g, '<br>')}</div>
                                    <small class="d-block mt-1 opacity-75 text-end" style="font-size:0.65rem;">
                                        ${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}
                                    </small>
                                </div>
                            </div>
                        `;
                        messagesList.insertAdjacentHTML('beforeend', html);
                        lastId = Math.max(lastId, m.id);
                    });
                    scrollToBottom();
                }
            }
        } catch (err) { console.error(err); }
    }

    setInterval(fetchMessages, 3000);

    // Send typing status
    messageInput.oninput = () => {
        if (!isTypingSent) {
            isTypingSent = true;
            fetch('rental_chat_api.php?action=typing', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `request_id=<?php echo $request_id; ?>`
            });
            setTimeout(() => { isTypingSent = false; }, 3000);
        }
    };

    messageInput.onkeydown = (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            chatForm.dispatchEvent(new Event('submit'));
        }
    };
</script>

<?php include '../includes/footer.php'; ?>
