<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$request_id = intval($_GET['request_id'] ?? 0);

if (!$request_id) {
    header("Location: requests.php");
    exit();
}

// Fetch request and verify ownership (listing owner or assigned broker)
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.user_id as owner_id, l.type as listing_type,
           u.full_name as customer_name_real
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users u ON rr.customer_id = u.id
    WHERE rr.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request || ($request['owner_id'] != $user_id && $request['broker_id'] != $user_id)) {
    redirectWithMessage('requests.php', 'danger', 'Access denied to this chat.');
}

// Update chat_initiated
if (!$request['chat_initiated']) {
    $pdo->prepare("UPDATE rental_requests SET chat_initiated = 1 WHERE id = ?")->execute([$request_id]);
}

$customer_id = $request['customer_id'];
$customer_name = $request['customer_name'] ?: ($request['customer_name_real'] ?: 'Customer');

// Fetch Messages
$stmt = $pdo->prepare("
    SELECT * FROM rental_chat_messages 
    WHERE request_id = ? 
    ORDER BY created_at ASC
");
$stmt->execute([$request_id]);
$messages = $stmt->fetchAll();

// Mark received messages as read
$pdo->prepare("UPDATE rental_chat_messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0")
    ->execute([$request_id, $user_id]);

$last_msg_id = 0;
foreach($messages as $m) $last_msg_id = max($last_msg_id, $m['id']);

$base_url = BASE_URL;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with <?php echo htmlspecialchars($customer_name); ?> - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; overflow: hidden; }
        .dashboard-wrapper { display: flex; width: 100%; height: 100vh; }
        .main-content { margin-left: 260px; width: calc(100% - 260px); height: 100%; display: flex; flex-direction: column; }
        @media (max-width: 991px) { .main-content { margin-left: 0; width: 100%; } }

        .chat-header { background: #fff; padding: 15px 25px; border-bottom: 1px solid #eee; display: flex; align-items: center; justify-content: space-between; }
        .chat-body { flex: 1; overflow-y: auto; padding: 25px; background: #f7f9fc; display: flex; flex-direction: column; gap: 15px; }
        .chat-footer { background: #fff; padding: 15px 25px; border-top: 1px solid #eee; }

        .msg-bubble { max-width: 70%; padding: 12px 18px; border-radius: 18px; font-size: 0.95rem; position: relative; }
        .msg-sent { align-self: flex-end; background: #1B5E20; color: #fff; border-bottom-right-radius: 4px; }
        .msg-received { align-self: flex-start; background: #fff; color: #333; border-bottom-left-radius: 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .msg-time { font-size: 0.7rem; opacity: 0.7; margin-top: 5px; display: block; text-align: right; }
        
        #messageInput { border: none; background: #f0f2f5; border-radius: 25px; padding: 12px 20px; resize: none; max-height: 100px; }
        #messageInput:focus { outline: none; box-shadow: 0 0 0 2px rgba(27, 94, 32, 0.1); }
        .btn-send { width: 45px; height: 45px; border-radius: 50%; background: #1B5E20; color: #fff; border: none; display: flex; align-items: center; justify-content: center; transition: 0.3s; }
        .btn-send:hover { background: #2E7D32; transform: scale(1.05); }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>
        <div class="main-content">
            <div class="chat-header shadow-sm">
                <div class="d-flex align-items-center gap-3">
                    <a href="requests.php" class="btn btn-light rounded-circle"><i class="fas fa-arrow-left"></i></a>
                    <div class="bg-primary-green text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width:45px;height:45px;font-size:1.2rem;">
                        <?php echo strtoupper(substr($customer_name, 0, 1)); ?>
                    </div>
                    <div>
                        <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($customer_name); ?></h6>
                        <small class="text-muted"><i class="fas fa-home me-1"></i> <?php echo htmlspecialchars($request['listing_title']); ?></small>
                    </div>
                </div>
                <div class="dropdown">
                    <button class="btn btn-light rounded-pill px-3 py-2" data-bs-toggle="dropdown">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end rounded-4 shadow-sm border-0">
                        <li><a class="dropdown-item py-2" href="requests.php?id=<?php echo $request_id; ?>"><i class="fas fa-file-alt me-2"></i> View Request</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger" href="#"><i class="fas fa-ban me-2"></i> Block User</a></li>
                    </ul>
                </div>
            </div>

            <div class="chat-body" id="chatContainer">
                <?php if (empty($messages)): ?>
                    <div class="text-center my-auto py-5 opacity-50">
                        <i class="fas fa-comments fs-1 mb-3"></i>
                        <p>No messages yet. Send a message to start the conversation.</p>
                    </div>
                <?php else: ?>
                    <div id="messagesList">
                        <?php foreach ($messages as $msg): 
                            $is_sent = ($msg['sender_id'] == $user_id);
                        ?>
                            <div class="msg-bubble <?php echo $is_sent ? 'msg-sent' : 'msg-received'; ?>" id="msg-<?php echo $msg['id']; ?>">
                                <?php if ($msg['message_type'] === 'image'): ?>
                                    <img src="<?php echo $base_url.'/'.$msg['file_path']; ?>" class="img-fluid rounded mb-2">
                                <?php endif; ?>
                                <div><?php echo nl2br(htmlspecialchars($msg['message'])); ?></div>
                                <span class="msg-time">
                                    <?php echo date('h:i A', strtotime($msg['created_at'])); ?>
                                    <?php if ($is_sent): ?>
                                        <i class="fas fa-check-double ms-1 <?php echo $msg['is_read'] ? 'text-info' : ''; ?>"></i>
                                    <?php endif; ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                
                <!-- Typing Indicator -->
                <div id="typingIndicator" class="d-none mb-3">
                    <div class="bg-white p-2 px-3 rounded-pill shadow-sm d-inline-flex align-items-center gap-2" style="font-size: 0.75rem; color: #666; border: 1px solid #eee;">
                        <div class="typing-dots">
                            <span></span><span></span><span></span>
                        </div>
                        <span><?php echo htmlspecialchars($customer_name); ?> is typing...</span>
                    </div>
                </div>

                <div id="scrollAnchor"></div>
            </div>

            <div class="chat-footer">
                <form id="chatForm" class="d-flex align-items-end gap-2">
                    <input type="hidden" name="request_id" value="<?php echo $request_id; ?>">
                    <input type="hidden" name="receiver_id" value="<?php echo $customer_id; ?>">
                    
                    <button type="button" class="btn btn-light rounded-circle" style="width:45px;height:45px;"><i class="fas fa-paperclip"></i></button>
                    
                    <textarea id="messageInput" name="message" class="form-control" rows="1" placeholder="Type your message..." required></textarea>
                    
                    <button type="submit" class="btn-send"><i class="fas fa-paper-plane"></i></button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .typing-dots span { width: 4px; height: 4px; background: #999; border-radius: 50%; display: inline-block; animation: typing 1.4s infinite ease-in-out; }
        .typing-dots span:nth-child(2) { animation-delay: 0.2s; }
        .typing-dots span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const chatContainer = document.getElementById('chatContainer');
        const messagesList = document.getElementById('messagesList');
        const messageInput = document.getElementById('messageInput');
        const chatForm = document.getElementById('chatForm');
        const typingIndicator = document.getElementById('typingIndicator');
        let lastId = <?php echo $last_msg_id; ?>;
        let isTypingSent = false;

        function scrollToBottom() {
            chatContainer.scrollTop = chatContainer.scrollHeight;
        }
        scrollToBottom();

        // Auto-expand textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = (this.scrollHeight) + 'px';
            
            // Typing Status
            if (!isTypingSent) {
                isTypingSent = true;
                fetch('chat_api.php?action=typing', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `request_id=<?php echo $request_id; ?>`
                });
                setTimeout(() => { isTypingSent = false; }, 3000);
            }
        });

        // Send Message
        chatForm.onsubmit = async (e) => {
            e.preventDefault();
            const msg = messageInput.value.trim();
            if (!msg) return;

            const formData = new FormData(chatForm);
            messageInput.value = '';
            messageInput.style.height = 'auto';

            try {
                const response = await fetch('chat_api.php?action=send', { method: 'POST', body: formData });
                const res = await response.json();
                if (res.success) fetchMessages();
            } catch (err) { console.error(err); }
        };

        // Poll for new messages
        async function fetchMessages() {
            try {
                const response = await fetch(`chat_api.php?action=fetch&request_id=<?php echo $request_id; ?>&last_id=${lastId}`);
                const data = await response.json();
                
                if (data.success) {
                    // Typing Status
                    if (data.is_typing) {
                        typingIndicator.classList.remove('d-none');
                    } else {
                        typingIndicator.classList.add('d-none');
                    }

                    if (data.messages.length > 0) {
                        data.messages.forEach(m => {
                            if (document.getElementById(`msg-${m.id}`)) return;
                            const isSent = (m.sender_id == <?php echo $user_id; ?>);
                            const html = `
                                <div class="msg-bubble ${isSent ? 'msg-sent' : 'msg-received'}" id="msg-${m.id}">
                                    <div>${m.message.replace(/\n/g, '<br>')}</div>
                                    <span class="msg-time">${new Date(m.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})}</span>
                                </div>
                            `;
                            if (messagesList) {
                                messagesList.insertAdjacentHTML('beforeend', html);
                            } else {
                                location.reload(); // Quick fix for empty state
                            }
                            lastId = Math.max(lastId, m.id);
                        });
                        scrollToBottom();
                    }
                }
            } catch (err) { console.error(err); }
        }

        setInterval(fetchMessages, 3000);

        // Enter to send
        messageInput.onkeydown = (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.dispatchEvent(new Event('submit'));
            }
        };
    </script>
</body>
</html>
