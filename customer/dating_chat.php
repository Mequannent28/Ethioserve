<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$other_user_id = intval($_GET['user_id'] ?? 0);

if (!$other_user_id) {
    header("Location: dating.php");
    exit();
}

try {
    $stmt = $pdo->prepare("SELECT u.full_name, u.email, u.phone, p.profile_pic, p.age, p.location_name, p.bio, p.last_active FROM users u LEFT JOIN dating_profiles p ON u.id = p.user_id WHERE u.id = ?");
    $stmt->execute([$other_user_id]);
    $other_user = $stmt->fetch();
} catch (Exception $e) {
    $other_user = null;
}

if (!$other_user) {
    header("Location: dating.php");
    exit();
}

$is_matched = false;
try {
    $stmt = $pdo->prepare("SELECT id FROM dating_matches WHERE (user1_id=? AND user2_id=?) OR (user1_id=? AND user2_id=?)");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $is_matched = (bool) $stmt->fetch();
} catch (Exception $e) {
}

// Handle POST (new message)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
    $msg = sanitize($_POST['message'] ?? '');
    $reply_to = intval($_POST['reply_to_id'] ?? 0) ?: null;
    $type = 'text';
    $attachment = null;

    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/dating_chat/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = time() . '_' . basename($_FILES['image']['name']);
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $file_name)) {
            $attachment = 'uploads/dating_chat/' . $file_name;
            $type = 'image';
        }
    }

    if (!empty($msg) || $attachment) {
        try {
            $stmt = $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type, attachment_url, reply_to_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $other_user_id, $msg, $type, $attachment, $reply_to]);
        } catch (Exception $e) {
        }
        header("Location: dating_chat.php?user_id=" . $other_user_id);
        exit();
    }
}

// Mark read
try {
    $pdo->prepare("UPDATE dating_messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0")->execute([$other_user_id, $user_id]);
} catch (Exception $e) {
}

// Fetch messages with reply info
$stmt = $pdo->prepare("
    SELECT m.*, 
           r.message AS reply_text, r.message_type AS reply_type, ru.full_name AS reply_sender
    FROM dating_messages m
    LEFT JOIN dating_messages r ON m.reply_to_id = r.id
    LEFT JOIN users ru ON r.sender_id = ru.id
    WHERE (m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?)
    ORDER BY m.created_at ASC
");
$stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
$messages = $stmt->fetchAll();

// Fetch pinned
$pinned_msg = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM dating_messages WHERE is_pinned=1 AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) LIMIT 1");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $pinned_msg = $stmt->fetch();
} catch (Exception $e) {
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<style>
    /* ─── Base ─── */
    .chat-page {
        background: #f0f2f5;
        min-height: 100vh;
    }

    /* ─── Chat card ─── */
    .chat-card {
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 8px 40px rgba(0, 0, 0, 0.10);
    }

    /* ─── Header ─── */
    .chat-header {
        background: #fff;
        padding: 12px 16px;
        border-bottom: 1px solid #eee;
        position: sticky;
        top: 0;
        z-index: 200;
    }

    /* ─── Pinned bar ─── */
    .pinned-bar {
        background: linear-gradient(90deg, #e8f5e9, #f1f8e9);
        border-left: 4px solid #2E7D32;
        font-size: .8rem;
        cursor: pointer;
        padding: 8px 14px;
    }

    /* ─── Messages area ─── */
    .chat-body {
        padding: 14px 10px 80px;
        background-image: url('https://www.transparenttextures.com/patterns/cubes.png');
        background-color: #e5ddd5;
    }

    @media (min-width: 769px) {
        .chat-body {
            min-height: 420px;
            max-height: 580px;
            overflow-y: auto;
        }
    }

    @media (max-width: 768px) {
        .chat-body {
            min-height: 300px;
            overflow-y: visible;
            padding-bottom: 100px;
        }
    }

    /* ─── Bubbles ─── */
    .msg-row {
        display: flex;
        margin-bottom: 4px;
    }

    .msg-row.me {
        justify-content: flex-end;
    }

    .msg-row.them {
        justify-content: flex-start;
    }

    .bubble {
        max-width: 72%;
        padding: 8px 12px 4px;
        border-radius: 12px;
        position: relative;
        word-break: break-word;
        font-size: .93rem;
        line-height: 1.45;
        cursor: pointer;
        transition: filter .15s;
    }

    .bubble:active {
        filter: brightness(.92);
    }

    .me .bubble {
        background: linear-gradient(135deg, #1B5E20, #388e3c);
        color: #fff;
        border-bottom-right-radius: 4px;
    }

    .them .bubble {
        background: #fff;
        color: #111;
        border-bottom-left-radius: 4px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, .1);
    }

    /* ─── Reply preview inside bubble ─── */
    .reply-preview {
        background: rgba(255, 255, 255, .20);
        border-left: 3px solid rgba(255, 255, 255, .8);
        border-radius: 6px;
        padding: 4px 8px;
        margin-bottom: 6px;
        font-size: .78rem;
    }

    .them .reply-preview {
        background: #f0f0f0;
        border-left-color: #2E7D32;
        color: #444;
    }

    /* ─── Forwarded label ─── */
    .fwd-label {
        font-size: .72rem;
        color: rgba(255, 255, 255, .7);
        margin-bottom: 2px;
    }

    .them .fwd-label {
        color: #888;
    }

    /* ─── Timestamp & check ─── */
    .meta {
        font-size: .65rem;
        opacity: .75;
        text-align: right;
        margin-top: 2px;
        white-space: nowrap;
    }

    .me .meta {
        color: rgba(255, 255, 255, .85);
    }

    .them .meta {
        color: #888;
    }

    /* ─── Pinned highlight ─── */
    .bubble.pinned-highlight {
        box-shadow: 0 0 0 3px #fdd835 !important;
    }

    /* ─── Context Menu ─── */
    #ctxMenu {
        display: none;
        position: fixed;
        z-index: 9999;
        background: #fff;
        border-radius: 14px;
        box-shadow: 0 8px 40px rgba(0, 0, 0, .22);
        min-width: 200px;
        padding: 6px 0;
        animation: ctxOpen .15s ease;
    }

    @keyframes ctxOpen {
        from {
            opacity: 0;
            transform: scale(.92);
        }

        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    .ctx-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 10px 18px;
        cursor: pointer;
        font-size: .92rem;
        transition: background .15s;
    }

    .ctx-item:hover {
        background: #f5f5f5;
    }

    .ctx-item i {
        width: 20px;
        text-align: center;
    }

    .ctx-sep {
        border-top: 1px solid #eee;
        margin: 4px 0;
    }

    .ctx-danger {
        color: #e53935;
    }

    /* ─── Reply bar (input area) ─── */
    #replyBar {
        display: none;
        background: #f8f8f8;
        border-top: 2px solid #2E7D32;
        padding: 8px 14px;
        font-size: .82rem;
        align-items: center;
        gap: 8px;
    }

    #replyBar .reply-cancel {
        cursor: pointer;
        color: #999;
        font-size: 1.1rem;
    }

    #replyBar .reply-info {
        flex: 1;
    }

    #replyBar .reply-name {
        font-weight: 600;
        color: #2E7D32;
    }

    /* ─── Edit bar ─── */
    #editBar {
        display: none;
        background: #fffde7;
        border-top: 2px solid #f9a825;
        padding: 8px 14px;
        font-size: .82rem;
        align-items: center;
        gap: 8px;
    }

    #editBar .edit-cancel {
        cursor: pointer;
        color: #999;
        font-size: 1.1rem;
    }

    /* ─── Input bar ─── */
    .chat-footer {
        background: #fff;
        padding: 10px 12px;
        border-top: 1px solid #eee;
        position: sticky;
        bottom: 0;
        z-index: 150;
    }

    .chat-footer form {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .msg-input {
        flex: 1;
        border-radius: 25px;
        border: 1.5px solid #e0e0e0;
        padding: 10px 18px;
        font-size: .93rem;
        outline: none;
        transition: border .2s;
    }

    .msg-input:focus {
        border-color: #2E7D32;
    }

    .send-btn {
        width: 46px;
        height: 46px;
        border-radius: 50%;
        border: none;
        background: linear-gradient(135deg, #1B5E20, #388e3c);
        color: #fff;
        font-size: 1.05rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: transform .15s;
        flex-shrink: 0;
    }

    .send-btn:active {
        transform: scale(.92);
    }

    /* ─── Details Modal ─── */
    #detailsModal .modal-content {
        border-radius: 18px;
    }

    /* ─── Forward Modal ─── */
    #forwardModal .modal-content {
        border-radius: 18px;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 10px;
        cursor: pointer;
        transition: background .15s;
    }

    .contact-item:hover {
        background: #f0f0f0;
    }
</style>

<?php
// Fetch contacts for forward modal (all users current user has chatted with)
$contacts = [];
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.full_name, p.profile_pic
        FROM users u
        LEFT JOIN dating_profiles p ON u.id = p.user_id
        WHERE u.id != ? AND u.id IN (
            SELECT DISTINCT sender_id FROM dating_messages WHERE receiver_id = ?
            UNION
            SELECT DISTINCT receiver_id FROM dating_messages WHERE sender_id = ?
        ) LIMIT 20
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $contacts = $stmt->fetchAll();
} catch (Exception $e) {
}
?>

<div class="chat-page py-3">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-6">
                <div class="chat-card bg-white">

                    <!-- ─── Header ─── -->
                    <div class="chat-header d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-3">
                            <a href="dating_matches.php" class="btn btn-light rounded-circle p-2"><i
                                    class="fas fa-arrow-left"></i></a>
                            <div class="position-relative">
                                <img src="<?php echo htmlspecialchars($other_user['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($other_user['full_name'])); ?>"
                                    class="rounded-circle border border-2 border-white" width="44" height="44"
                                    style="object-fit:cover;">
                                <span
                                    class="position-absolute bottom-0 end-0 bg-success border border-2 border-white rounded-circle"
                                    style="width:11px;height:11px;"></span>
                            </div>
                            <div>
                                <div class="fw-bold" style="font-size:.95rem;">
                                    <?php echo htmlspecialchars($other_user['full_name']); ?></div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?php echo $is_matched ? '<span class="badge bg-danger">Matched</span>' : 'Online'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="dating_video_call.php?user_id=<?php echo $other_user_id; ?>"
                                class="btn btn-light rounded-circle p-2 text-danger"><i class="fas fa-video"></i></a>
                        </div>
                    </div>

                    <!-- ─── Pinned Message Bar ─── -->
                    <?php if ($pinned_msg): ?>
                        <div class="pinned-bar d-flex align-items-center gap-2" id="pinnedBar"
                            onclick="scrollToMessage(<?php echo $pinned_msg['id']; ?>)">
                            <i class="fas fa-thumbtack text-success"></i>
                            <div class="flex-1">
                                <div class="fw-bold text-success" style="font-size:.72rem;">Pinned Message</div>
                                <div class="text-truncate" style="max-width:260px;">
                                    <?php echo htmlspecialchars(substr($pinned_msg['message'], 0, 80)); ?></div>
                            </div>
                            <button class="btn btn-sm p-0 text-muted"
                                onclick="event.stopPropagation();unpinMessage(<?php echo $pinned_msg['id']; ?>)"><i
                                    class="fas fa-times"></i></button>
                        </div>
                    <?php endif; ?>

                    <!-- ─── Reply Bar ─── -->
                    <div id="replyBar" class="d-flex">
                        <i class="fas fa-reply text-success mt-1"></i>
                        <div class="reply-info">
                            <div class="reply-name" id="replyName"></div>
                            <div id="replyText" class="text-muted text-truncate" style="max-width:220px;"></div>
                        </div>
                        <span class="reply-cancel ms-auto" onclick="cancelReply()">✕</span>
                    </div>

                    <!-- ─── Edit Bar ─── -->
                    <div id="editBar" class="d-flex">
                        <i class="fas fa-pencil-alt text-warning mt-1"></i>
                        <div class="flex-1 ms-2">
                            <div class="fw-bold text-warning">Edit Message</div>
                            <div id="editOriginal" class="text-muted text-truncate" style="max-width:220px;"></div>
                        </div>
                        <span class="edit-cancel ms-auto" onclick="cancelEdit()">✕</span>
                    </div>

                    <!-- ─── Messages ─── -->
                    <div class="chat-body" id="chatBody">
                        <div id="messageContainer">
                            <?php if (empty($messages)): ?>
                                <div class="text-center py-5 animate__animated animate__fadeIn">
                                    <span style="font-size:3.5rem;">👋</span>
                                    <h5 class="fw-bold mt-3">Start the conversation!</h5>
                                    <p class="text-muted small">Say hi to
                                        <?php echo htmlspecialchars($other_user['full_name']); ?></p>
                                </div>
                            <?php endif; ?>

                            <?php foreach ($messages as $m):
                                $is_me = ($m['sender_id'] == $user_id);
                                $rowClass = $is_me ? 'me' : 'them';
                                $isPinned = $m['is_pinned'];
                                ?>
                                <div class="msg-row <?php echo $rowClass; ?> animate__animated animate__fadeInUp animate__faster"
                                    id="msg-<?php echo $m['id']; ?>" data-id="<?php echo $m['id']; ?>"
                                    data-me="<?php echo $is_me ? '1' : '0'; ?>"
                                    data-text="<?php echo htmlspecialchars($m['message']); ?>"
                                    data-type="<?php echo $m['message_type']; ?>"
                                    data-pinned="<?php echo $isPinned ? '1' : '0'; ?>">

                                    <div class="bubble <?php echo $isPinned ? 'pinned-highlight' : ''; ?>"
                                        oncontextmenu="showCtx(event,this.closest('.msg-row'))"
                                        onclick="handleTap(event,this.closest('.msg-row'))">

                                        <?php if ($m['forwarded_from']): ?>
                                            <div class="fwd-label"><i class="fas fa-forward me-1"></i>Forwarded</div>
                                        <?php endif; ?>

                                        <?php if ($m['reply_to_id'] && $m['reply_text'] !== null): ?>
                                            <div class="reply-preview">
                                                <div class="fw-bold" style="font-size:.72rem;">
                                                    <?php echo htmlspecialchars($m['reply_sender'] ?? ''); ?></div>
                                                <div class="text-truncate">
                                                    <?php echo htmlspecialchars(substr($m['reply_text'], 0, 60)); ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <?php if ($m['message_type'] === 'image' && $m['attachment_url']): ?>
                                            <div class="mb-1 rounded-3 overflow-hidden" style="max-width:240px;">
                                                <img src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                    class="img-fluid w-100"
                                                    style="cursor:pointer;max-height:220px;object-fit:cover;"
                                                    onclick="window.open(this.src)">
                                            </div>
                                        <?php endif; ?>

                                        <?php if (!empty($m['message'])): ?>
                                            <div><?php echo nl2br(htmlspecialchars($m['message'])); ?></div>
                                        <?php endif; ?>

                                        <div class="meta">
                                            <?php if ($m['is_edited']): ?><span style="font-size:.62rem;">edited</span>
                                            <?php endif; ?>
                                            <?php echo date('h:i A', strtotime($m['created_at'])); ?>
                                            <?php if ($is_me): ?> <i class="fas fa-check-double"
                                                    style="font-size:.63rem; color:<?php echo $m['is_read'] ? '#a5d6a7' : 'rgba(255,255,255,.6)'; ?>;"></i><?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <div id="chatEnd"></div>
                    </div>

                    <!-- ─── Footer ─── -->
                    <div class="chat-footer">
                        <form method="POST" enctype="multipart/form-data" id="chatForm">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="reply_to_id" id="replyToId" value="">
                            <input type="file" name="image" id="imgInput" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-light rounded-circle p-2 border-0"
                                onclick="document.getElementById('imgInput').click()">
                                <i class="fas fa-image text-primary"></i>
                            </button>
                            <input type="text" name="message" id="msgInput" class="msg-input" placeholder="Message..."
                                autocomplete="off">
                            <button type="submit" class="send-btn"><i class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>

                </div><!-- chat-card -->
            </div>
        </div>
    </div>
</div><!-- chat-page -->

<!-- ─── Context Menu ─── -->
<div id="ctxMenu">
    <div class="ctx-item" id="ctxReply" onclick="ctxAction('reply')"> <i class="fas fa-reply text-success"></i> Reply
    </div>
    <div class="ctx-item" id="ctxCopy" onclick="ctxAction('copy')"> <i class="fas fa-copy text-primary"></i> Copy</div>
    <div class="ctx-item" id="ctxForward" onclick="ctxAction('forward')"> <i class="fas fa-share text-info"></i> Forward
    </div>
    <div class="ctx-item" id="ctxPin" onclick="ctxAction('pin')"> <i class="fas fa-thumbtack text-warning"></i> Pin
    </div>
    <div class="ctx-item" id="ctxUnpin" onclick="ctxAction('unpin')"> <i class="fas fa-thumbtack text-muted"></i> Unpin
    </div>
    <div class="ctx-item" id="ctxDetails" onclick="ctxAction('details')"> <i
            class="fas fa-info-circle text-secondary"></i> Details</div>
    <div class="ctx-sep" id="meSep"></div>
    <div class="ctx-item" id="ctxEdit" onclick="ctxAction('edit')"> <i class="fas fa-pencil-alt text-warning"></i> Edit
    </div>
    <div class="ctx-item ctx-danger" id="ctxDelete" onclick="ctxAction('delete')"><i class="fas fa-trash-alt"></i>
        Delete</div>
</div>

<!-- ─── Details Modal ─── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Message Details</h5>
            <div id="detailsBody" class="text-muted small"></div>
            <button class="btn btn-light rounded-pill mt-3" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- ─── Forward Modal ─── -->
<div class="modal fade" id="forwardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4">
            <h5 class="fw-bold mb-3"><i class="fas fa-share me-2 text-info"></i>Forward to...</h5>
            <div id="forwardContacts">
                <?php foreach ($contacts as $c): ?>
                    <div class="contact-item"
                        onclick="doForward(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')">
                        <img src="<?php echo htmlspecialchars($c['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($c['full_name'])); ?>"
                            class="rounded-circle" width="40" height="40" style="object-fit:cover;">
                        <span class="fw-medium"><?php echo htmlspecialchars($c['full_name']); ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($contacts)): ?>
                    <p class="text-muted">No other conversations yet.</p>
                <?php endif; ?>
            </div>
            <button class="btn btn-light rounded-pill mt-3" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</div>

<script>
    const API = '<?php echo $base_url; ?>/includes/dating_chat_api.php';
    const OTHER_ID = <?php echo $other_user_id; ?>;
    const MY_ID = <?php echo $user_id; ?>;
    const OTHER_NAME = '<?php echo htmlspecialchars($other_user['full_name']); ?>';

    let activeRow = null;
    let editMsgId = null;
    let tapTimer = null;

    /* ─── Scroll to bottom on load ─── */
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    /* ─── Auto-resize: submit image immediately ─── */
    document.getElementById('imgInput').addEventListener('change', () => {
        if (document.getElementById('imgInput').files.length > 0) {
            document.getElementById('chatForm').submit();
        }
    });

    /* ─── Context Menu ─── */
    function showCtx(e, row) {
        e.preventDefault();
        activeRow = row;
        const isMe = row.dataset.me === '1';
        const isPinned = row.dataset.pinned === '1';

        document.getElementById('ctxEdit').style.display = isMe ? 'flex' : 'none';
        document.getElementById('ctxDelete').style.display = isMe ? 'flex' : 'none';
        document.getElementById('meSep').style.display = isMe ? 'block' : 'none';
        document.getElementById('ctxPin').style.display = !isPinned ? 'flex' : 'none';
        document.getElementById('ctxUnpin').style.display = isPinned ? 'flex' : 'none';

        const menu = document.getElementById('ctxMenu');
        menu.style.display = 'block';
        // Position
        let x = e.clientX, y = e.clientY;
        const mw = menu.offsetWidth || 200, mh = menu.offsetHeight || 260;
        if (x + mw > window.innerWidth) x = window.innerWidth - mw - 10;
        if (y + mh > window.innerHeight) y = window.innerHeight - mh - 10;
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
    }

    /* Long-press for mobile */
    function handleTap(e, row) {
        // simple single click: do nothing (let context menu handle long press)
    }

    document.addEventListener('touchstart', function (e) {
        const row = e.target.closest('.msg-row');
        if (!row) return;
        tapTimer = setTimeout(() => {
            showCtx({ preventDefault: () => { }, clientX: e.touches[0].clientX, clientY: e.touches[0].clientY }, row);
        }, 500);
    }, { passive: true });
    document.addEventListener('touchend', () => clearTimeout(tapTimer));
    document.addEventListener('touchmove', () => clearTimeout(tapTimer));

    document.addEventListener('click', (e) => {
        if (!document.getElementById('ctxMenu').contains(e.target)) {
            document.getElementById('ctxMenu').style.display = 'none';
        }
    });

    /* ─── Context Actions ─── */
    function ctxAction(action) {
        document.getElementById('ctxMenu').style.display = 'none';
        if (!activeRow) return;
        const id = activeRow.dataset.id;
        const text = activeRow.dataset.text;

        if (action === 'copy') {
            navigator.clipboard.writeText(text).then(() => showToast('Copied!', 'success'));
            return;
        }
        if (action === 'reply') {
            document.getElementById('replyToId').value = id;
            document.getElementById('replyName').textContent = activeRow.dataset.me === '1' ? 'You' : OTHER_NAME;
            document.getElementById('replyText').textContent = text || 'Image';
            document.getElementById('replyBar').style.display = 'flex';
            document.getElementById('msgInput').focus();
            cancelEdit();
            return;
        }
        if (action === 'edit') {
            editMsgId = id;
            document.getElementById('editOriginal').textContent = text;
            document.getElementById('editBar').style.display = 'flex';
            document.getElementById('msgInput').value = text;
            document.getElementById('msgInput').focus();
            cancelReply();
            return;
        }
        if (action === 'delete') {
            if (!confirm('Delete this message?')) return;
            fetch(API, { method: 'POST', body: new URLSearchParams({ action: 'delete', msg_id: id }) })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { activeRow.remove(); showToast('Message deleted', 'danger'); }
                });
            return;
        }
        if (action === 'pin') {
            fetch(API, { method: 'POST', body: new URLSearchParams({ action: 'pin', msg_id: id }) })
                .then(r => r.json())
                .then(d => {
                    if (d.success) { showToast('Message pinned 📌', 'success'); setTimeout(() => location.reload(), 800); }
                });
            return;
        }
        if (action === 'unpin') {
            unpinMessage(id);
            return;
        }
        if (action === 'forward') {
            const modal = new bootstrap.Modal(document.getElementById('forwardModal'));
            modal.show();
            return;
        }
        if (action === 'details') {
            fetch(API + '?action=details&msg_id=' + id)
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        const m = d.message;
                        document.getElementById('detailsBody').innerHTML = `
                        <table class="table table-sm table-borderless">
                            <tr><td><b>Sender</b></td><td>${m.full_name}</td></tr>
                            <tr><td><b>Sent at</b></td><td>${m.created_at}</td></tr>
                            <tr><td><b>Type</b></td><td>${m.message_type}</td></tr>
                            <tr><td><b>Edited</b></td><td>${m.is_edited ? '✅ ' + m.edited_at : '—'}</td></tr>
                            <tr><td><b>Pinned</b></td><td>${m.is_pinned ? '📌 Yes' : 'No'}</td></tr>
                            <tr><td><b>Read</b></td><td>${m.is_read ? '✅ Yes' : 'No'}</td></tr>
                            ${m.message ? '<tr><td><b>Text</b></td><td>' + m.message + '</td></tr>' : ''}
                        </table>`;
                        new bootstrap.Modal(document.getElementById('detailsModal')).show();
                    }
                });
            return;
        }
    }

    function unpinMessage(id) {
        if (!confirm('Unpin this message?')) return;
        fetch(API, { method: 'POST', body: new URLSearchParams({ action: 'unpin', msg_id: id }) })
            .then(r => r.json())
            .then(d => { if (d.success) { showToast('Unpinned', 'secondary'); setTimeout(() => location.reload(), 800); } });
    }

    function doForward(toUserId, toName) {
        if (!activeRow) return;
        fetch(API, { method: 'POST', body: new URLSearchParams({ action: 'forward', msg_id: activeRow.dataset.id, to_user_id: toUserId }) })
            .then(r => r.json())
            .then(d => {
                bootstrap.Modal.getInstance(document.getElementById('forwardModal')).hide();
                if (d.success) showToast('Forwarded to ' + toName + ' ✓', 'info');
            });
    }

    /* ─── Reply helpers ─── */
    function cancelReply() {
        document.getElementById('replyBar').style.display = 'none';
        document.getElementById('replyToId').value = '';
    }
    function cancelEdit() {
        document.getElementById('editBar').style.display = 'none';
        editMsgId = null;
        document.getElementById('msgInput').value = '';
    }

    /* ─── Form submit: handle edit mode ─── */
    document.getElementById('chatForm').addEventListener('submit', function (e) {
        if (!editMsgId) return; // normal send

        e.preventDefault();
        const text = document.getElementById('msgInput').value.trim();
        if (!text) return;

        fetch(API, { method: 'POST', body: new URLSearchParams({ action: 'edit', msg_id: editMsgId, text }) })
            .then(r => r.json())
            .then(d => {
                if (d.success) {
                    const row = document.getElementById('msg-' + editMsgId);
                    if (row) {
                        // update text in DOM
                        const bubble = row.querySelector('.bubble > div:not(.reply-preview):not(.fwd-label):not(.meta)');
                        if (bubble) bubble.textContent = text;
                    }
                    cancelEdit();
                    showToast('Message edited ✓', 'success');
                }
            });
    });

    /* ─── Scroll to pinned msg ─── */
    function scrollToMessage(id) {
        const el = document.getElementById('msg-' + id);
        if (el) { el.scrollIntoView({ behavior: 'smooth', block: 'center' }); el.querySelector('.bubble').classList.add('pinned-highlight'); }
    }

    /* ─── Toast ─── */
    function showToast(msg, type = 'success') {
        const id = 'toast-' + Date.now();
        const el = document.createElement('div');
        el.id = id;
        el.className = `toast align-items-center text-bg-${type} border-0 show`;
        el.setAttribute('role', 'alert');
        el.innerHTML = `<div class="d-flex"><div class="toast-body fw-medium">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        document.getElementById('toastNotificationContainer').prepend(el);
        setTimeout(() => el.remove(), 3000);
    }

    /* ─── Auto-poll for new messages every 5s ─── */
    const msgContainer = document.getElementById('messageContainer');
    setInterval(() => {
        fetch(window.location.href)
            .then(r => r.text())
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById('messageContainer');
                if (fresh && fresh.innerHTML !== msgContainer.innerHTML) {
                    const atBottom = chatBody.scrollHeight - chatBody.clientHeight <= chatBody.scrollTop + 120;
                    msgContainer.innerHTML = fresh.innerHTML;
                    if (atBottom) chatBody.scrollTo({ top: chatBody.scrollHeight, behavior: 'smooth' });
                }
            });
    }, 5000);
</script>

<?php include '../includes/footer.php'; ?>