<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

requireLogin();

$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Redirect if not teacher or student (or admin)
if (!in_array($user_role, ['teacher', 'student', 'admin'])) {
    header("Location: index.php");
    exit();
}

$receiver_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Handle sending message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'], $_POST['receiver_id'])) {
    $msg = trim($_POST['message']);
    $rid = (int)$_POST['receiver_id'];
    
    if (!empty($msg) && $rid > 0) {
        $stmt = $pdo->prepare("INSERT INTO school_messages (sender_id, receiver_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$user_id, $rid, $msg]);
        $new_id = $pdo->lastInsertId();
        
        if (isset($_GET['ajax'])) {
            // Also return the assigned ID to the sender!
            echo json_encode(['success' => true, 'id' => $new_id]);
            exit();
        }
        header("Location: chat.php?user_id=" . $rid);
        exit();
    }
}

// Handle fetching new messages
if (isset($_GET['fetch_new'], $_GET['user_id'], $_GET['last_id'])) {
    $rid = (int)$_GET['user_id'];
    $last_id = (int)$_GET['last_id'];
    
    // Mark as read immediately when polling
    $stmt = $pdo->prepare("UPDATE school_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $stmt->execute([$user_id, $rid]);
    
    $stmt = $pdo->prepare("SELECT m.*, u.profile_photo AS sender_photo FROM school_messages m JOIN users u ON m.sender_id = u.id WHERE m.id > ? AND ((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?)) ORDER BY m.created_at ASC");
    $stmt->execute([$last_id, $user_id, $rid, $rid, $user_id]);
    
    $new_msgs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode($new_msgs);
    exit();
}

// Fetch users to chat with
if ($user_role === 'student') {
    // Students see teachers
    $stmt = $pdo->prepare("SELECT id, full_name, username, role, profile_photo, IF(last_active > DATE_SUB(NOW(), INTERVAL 2 MINUTE), 1, 0) as is_online FROM users WHERE role = 'teacher'");
    $stmt->execute();
    $contacts = $stmt->fetchAll();
} else if ($user_role === 'teacher') {
    // Teachers see students
    $stmt = $pdo->prepare("SELECT id, full_name, username, role, profile_photo, IF(last_active > DATE_SUB(NOW(), INTERVAL 2 MINUTE), 1, 0) as is_online FROM users WHERE role = 'student'");
    $stmt->execute();
    $contacts = $stmt->fetchAll();
} else {
    // Admin sees everyone
    $stmt = $pdo->prepare("SELECT id, full_name, username, role, profile_photo, IF(last_active > DATE_SUB(NOW(), INTERVAL 2 MINUTE), 1, 0) as is_online FROM users WHERE id != ? AND role IN ('teacher', 'student')");
    $stmt->execute([$user_id]);
    $contacts = $stmt->fetchAll();
}

// Fetch messages if a receiver is selected
$messages = [];
$current_receiver = null;
if ($receiver_id) {
    // Mark as read
    $stmt = $pdo->prepare("UPDATE school_messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ?");
    $stmt->execute([$user_id, $receiver_id]);
    
    // Fetch conversation
    $stmt = $pdo->prepare("
        SELECT * FROM school_messages 
        WHERE (sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?)
        ORDER BY created_at ASC
    ");
    $stmt->execute([$user_id, $receiver_id, $receiver_id, $user_id]);
    $messages = $stmt->fetchAll();
    
    $stmt = $pdo->prepare("SELECT id, full_name, role, profile_photo, IF(last_active > DATE_SUB(NOW(), INTERVAL 2 MINUTE), 1, 0) as is_online FROM users WHERE id = ?");
    $stmt->execute([$receiver_id]);
    $current_receiver = $stmt->fetch();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>School Chat - EthioServe</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --edu-green: #1B5E20;
            --edu-light: #E8F5E9;
        }
        body { background-color: #f0f2f5; font-family: 'Inter', sans-serif; overflow: hidden; }
        .chat-container { height: 100vh; display: flex; }
        .sidebar { width: 350px; background: white; border-right: 1px solid #ddd; display: flex; flex-direction: column; }
        .chat-main { flex-grow: 1; display: flex; flex-direction: column; background: #e5ddd5 url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-blend-mode: overlay; }
        
        .sidebar-header { padding: 20px; background: var(--edu-green); color: white; }
        .contact-list { flex-grow: 1; overflow-y: auto; }
        .contact-item { padding: 15px 20px; cursor: pointer; border-bottom: 1px solid #f0f0f0; transition: 0.2s; text-decoration: none; color: inherit; display: block; }
        .contact-item:hover { background: #f8f9fa; }
        .contact-item.active { background: var(--edu-light); border-left: 4px solid var(--edu-green); }
        
        .chat-header { padding: 15px 25px; background: white; border-bottom: 1px solid #ddd; display: flex; align-items: center; justify-content: space-between; }
        .chat-messages { flex-grow: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 10px; }
        .message { max-width: 70%; padding: 10px 15px; border-radius: 15px; position: relative; font-size: 0.95rem; line-height: 1.4; word-wrap: break-word; white-space: pre-wrap; }
        .message-sent { align-self: flex-end; background: #dcf8c6; border-top-right-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,0.1); }
        .message-received { align-self: flex-start; background: white; border-top-left-radius: 2px; box-shadow: 0 1px 1px rgba(0,0,0,0.1); }
        
        .message-time { font-size: 0.7rem; color: #888; margin-top: 5px; text-align: right; display: block; }
        
        .chat-input-area { padding: 20px; background: #f0f0f0; border-top: 1px solid #ddd; }
        .input-group { background: white; border-radius: 30px; padding: 5px 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .chat-input { border: none; padding: 10px; font-size: 1rem; width: 100%; }
        .chat-input:focus { outline: none; }
        
        .avatar { width: 45px; height: 45px; background: #ddd; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; font-size: 1.2rem; }
        .avatar-teacher { background: #2196F3; }
        .avatar-student { background: #4CAF50; }
        
        .role-badge { font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; text-transform: uppercase; margin-bottom: 4px; display: inline-block; }
        .online-dot { height: 10px; width: 10px; background-color: #4CAF50; border-radius: 50%; display: inline-block; border: 2px solid white; box-shadow: 0 0 0 1px #4CAF50; }
        .offline-dot { height: 10px; width: 10px; background-color: #ccc; border-radius: 50%; display: inline-block; border: 2px solid white; box-shadow: 0 0 0 1px #ccc; }
        
        /* Hide scrollbars */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #bbb; border-radius: 10px; }
    </style>
</head>
<body>
    <div class="chat-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <div class="d-flex align-items-center gap-3">
                    <?php 
                        $my_photo = $pdo->query("SELECT profile_photo FROM users WHERE id = $user_id")->fetchColumn();
                        if ($my_photo): 
                    ?>
                        <img src="<?= htmlspecialchars($my_photo) ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                    <?php else: ?>
                        <div class="avatar bg-white text-success">
                            <?= strtoupper(substr($user_role, 0, 1)) ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <h6 class="mb-0 fw-bold"><?= htmlspecialchars(getCurrentUserName()) ?></h6>
                        <small class="opacity-75"><?= ucfirst($user_role) ?></small>
                    </div>
                </div>
            </div>
            
            <div class="p-3 bg-light">
                <div class="input-group bg-white py-1">
                    <span class="input-group-text bg-transparent border-0"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" class="form-control border-0 bg-transparent" placeholder="Search contacts...">
                </div>
            </div>
            
            <div class="contact-list">
                <?php foreach($contacts as $contact): ?>
                    <a href="chat.php?user_id=<?= $contact['id'] ?>" class="contact-item <?= $receiver_id == $contact['id'] ? 'active' : '' ?>">
                        <div class="d-flex align-items-center gap-3">
                            <?php if (!empty($contact['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($contact['profile_photo']) ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                            <?php else: ?>
                                <div class="avatar <?= $contact['role'] == 'teacher' ? 'avatar-teacher' : 'avatar-student' ?>">
                                    <?= strtoupper(substr($contact['full_name'], 0, 1)) ?>
                                </div>
                            <?php endif; ?>
                            <div class="flex-grow-1">
                                <span class="role-badge bg-<?= $contact['role'] == 'teacher' ? 'primary' : 'success' ?> bg-opacity-10 text-<?= $contact['role'] == 'teacher' ? 'primary' : 'success' ?>">
                                    <?= $contact['role'] ?>
                                </span>
                                <div class="fw-bold mb-0"><?= htmlspecialchars($contact['full_name']) ?></div>
                                <div class="text-muted small">
                                    <span class="<?= $contact['is_online'] ? 'online-dot' : 'offline-dot' ?>"></span> 
                                    <?= $contact['is_online'] ? 'Online' : 'Offline' ?>
                                </div>
                                <small class="text-muted text-truncate d-block" style="max-width: 180px;">Click to chat...</small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Main Chat Area -->
        <div class="chat-main">
            <?php if ($current_receiver): ?>
                <div class="chat-header">
                    <div class="d-flex align-items-center gap-3">
                        <?php if (!empty($current_receiver['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($current_receiver['profile_photo']) ?>" style="width: 45px; height: 45px; border-radius: 50%; object-fit: cover;">
                        <?php else: ?>
                            <div class="avatar <?= $current_receiver['role'] == 'teacher' ? 'avatar-teacher' : 'avatar-student' ?>">
                                <?= strtoupper(substr($current_receiver['full_name'], 0, 1)) ?>
                            </div>
                        <?php endif; ?>
                        <div>
                            <h5 class="mb-0 fw-bold"><?= htmlspecialchars($current_receiver['full_name']) ?></h5>
                            <small class="<?= $current_receiver['is_online'] ? 'text-success' : 'text-muted' ?>">
                                <span class="<?= $current_receiver['is_online'] ? 'online-dot' : 'offline-dot' ?> me-1"></span>
                                <?= $current_receiver['is_online'] ? 'Online' : 'Offline' ?>
                            </small>
                        </div>
                    </div>
                    <div>
                        <button class="btn btn-light rounded-circle"><i class="fas fa-phone"></i></button>
                        <button class="btn btn-light rounded-circle"><i class="fas fa-video"></i></button>
                    </div>
                </div>
                
                <div class="chat-messages" id="messageList">
                    <?php if (empty($messages)): ?>
                        <div class="text-center my-auto">
                            <i class="fas fa-comments text-muted opacity-25" style="font-size: 5rem;"></i>
                            <p class="text-muted mt-3">Start a new conversation with <?= htmlspecialchars($current_receiver['full_name']) ?></p>
                        </div>
                    <?php else: ?>
                        <?php $last_id = 0; foreach($messages as $m): $last_id = $m['id']; ?>
                            <div class="message <?= $m['sender_id'] == $user_id ? 'message-sent' : 'message-received' ?>" data-id="<?= $m['id'] ?>">
                                <?= nl2br(htmlspecialchars($m['message'])) ?>
                                <span class="message-time"><?= date('H:i', strtotime($m['created_at'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                
                <div class="chat-input-area">
                    <form action="chat.php?user_id=<?= $receiver_id ?>" method="POST" id="chatForm">
                        <input type="hidden" name="receiver_id" value="<?= $receiver_id ?>">
                        <div class="input-group shadow-sm">
                            <button type="button" class="btn border-0"><i class="far fa-smile text-muted"></i></button>
                            <textarea name="message" class="chat-input" placeholder="Type a message..." required rows="1" style="resize:none; padding-top:10px;"></textarea>
                            <button type="submit" class="btn text-success"><i class="fas fa-paper-plane fa-lg"></i></button>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="m-auto text-center p-5">
                    <div class="bg-white p-5 rounded-circle d-inline-block shadow-sm mb-4">
                        <i class="fas fa-paper-plane text-success" style="font-size: 4rem;"></i>
                    </div>
                    <h3 class="fw-bold">School Communication</h3>
                    <p class="text-muted">Select a contact from the left to start chatting <br> and discussing academic progress.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Scroll to bottom
        const messageList = document.getElementById('messageList');
        if (messageList) {
            messageList.scrollTop = messageList.scrollHeight;
        }
        
        // Auto-expand textarea
        const chatInput = document.querySelector('textarea.chat-input');
        if (chatInput) {
            chatInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    document.getElementById('chatForm').dispatchEvent(new Event('submit'));
                }
            });
        }

        let lastMessageId = <?= isset($last_id) ? $last_id : 0 ?>;
        const currentUserId = <?= (int)$user_id ?>;
        const receiverId = <?= (int)$receiver_id ?>;

        // AJAX Sending for smoother experience
        const chatForm = document.getElementById('chatForm');
        if (chatForm) {
            chatForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const formData = new FormData(this);
                const msg = chatInput.value;
                if (!msg.trim()) return;

                // Optimistic UI update
                const time = new Date().getHours().toString().padStart(2, '0') + ":" + new Date().getMinutes().toString().padStart(2, '0');
                const msgDiv = document.createElement('div');
                msgDiv.className = 'message message-sent';
                // Just use textContent to avoid XSS
                const textNode = document.createTextNode(msg);
                msgDiv.appendChild(textNode);
                msgDiv.innerHTML = msgDiv.innerHTML.replace(/\n/g, '<br>') + `<span class="message-time">${time}</span>`;
                
                // Add a temporary ID to be replaced
                msgDiv.setAttribute('data-id', 'temp');
                
                messageList.appendChild(msgDiv);
                messageList.scrollTop = messageList.scrollHeight;
                chatInput.value = '';

                fetch('chat.php?ajax=1', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.id) {
                        msgDiv.setAttribute('data-id', data.id);
                        lastMessageId = Math.max(lastMessageId, parseInt(data.id));
                    }
                });
            });
        }

        // Live Polling
        if (receiverId) {
            setInterval(() => {
                fetch(`chat.php?fetch_new=1&user_id=${receiverId}&last_id=${lastMessageId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.length > 0) {
                        let playSound = false;
                        data.forEach(msg => {
                            // double check we don't already have it via optimistic update
                            if (!document.querySelector(`div[data-id="${msg.id}"]`)) {
                                const msgDiv = document.createElement('div');
                                const isSent = (msg.sender_id == currentUserId);
                                msgDiv.className = 'message ' + (isSent ? 'message-sent' : 'message-received');
                                msgDiv.setAttribute('data-id', msg.id);
                                
                                const span = document.createElement('span');
                                span.textContent = msg.message;
                                let cleanHtml = span.innerHTML.replace(/\n/g, '<br>');
                                
                                const date = new Date(msg.created_at);
                                const timeStr = date.getHours().toString().padStart(2, '0') + ':' + date.getMinutes().toString().padStart(2, '0');
                                
                                msgDiv.innerHTML = cleanHtml + `<span class="message-time">${timeStr}</span>`;
                                messageList.appendChild(msgDiv);
                                
                                lastMessageId = Math.max(lastMessageId, parseInt(msg.id));
                                
                                if (!isSent) playSound = true;
                            }
                        });
                        
                        messageList.scrollTop = messageList.scrollHeight;
                        
                        if (playSound) {
                            // Beep on new incoming message
                            const context = new (window.AudioContext || window.webkitAudioContext)();
                            const osc = context.createOscillator();
                            const gain = context.createGain();
                            osc.connect(gain);
                            gain.connect(context.destination);
                            osc.type = 'sine';
                            osc.frequency.setValueAtTime(800, context.currentTime);
                            gain.gain.setValueAtTime(0.5, context.currentTime);
                            gain.gain.exponentialRampToValueAtTime(0.01, context.currentTime + 0.5);
                            osc.start(context.currentTime);
                            osc.stop(context.currentTime + 0.5);
                            
                            // Browser notification if permitted
                            if (Notification.permission === 'granted') {
                                new Notification("New Message", {
                                    body: data[data.length-1].message,
                                    icon: "https://ui-avatars.com/api/?name=New+Message&background=1B5E20&color=fff"
                                });
                            }
                        }
                    }
                })
                .catch(err => console.log('Polling error:', err));
            }, 3000);
            
            // Ask for Notification Permissions
            if (Notification.permission === 'default') {
                Notification.requestPermission();
            }
        }
    </script>
</body>
</html>
