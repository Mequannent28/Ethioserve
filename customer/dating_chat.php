<?php
ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once '../includes/functions.php';
require_once '../includes/db.php';

// ═══════════════════════════════════════════════════════════════════
// INLINE API — handles AJAX calls from the chat page
// Called when ?action=... or POST action=... is present
// ═══════════════════════════════════════════════════════════════════
$api_action = trim($_POST['action'] ?? $_GET['action'] ?? '');
if ($api_action !== '') {
    ob_end_clean();
    header('Content-Type: application/json; charset=utf-8');

    $api_uid = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
    $base_url = BASE_URL;
    if (!$api_uid) {
        echo json_encode(['success' => false, 'error' => 'Not logged in']);
        exit;
    }

    $api_mid = (int) ($_POST['msg_id'] ?? $_GET['msg_id'] ?? 0);

    function apiMsg(PDO $p, int $id): ?array
    {
        $s = $p->prepare("SELECT * FROM dating_messages WHERE id=?");
        $s->execute([$id]);
        return $s->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    function apiOut(array $d): void
    {
        echo json_encode($d, JSON_UNESCAPED_UNICODE);
        exit;
    }
    // PING
    if ($api_action === 'ping')
        apiOut(['success' => true, 'user_id' => $api_uid]);
    // DELETE
    if ($api_action === 'delete') {
        if (!$api_mid)
            apiOut(['success' => false, 'error' => 'No message ID']);
        $row = apiMsg($pdo, $api_mid);
        if (!$row)
            apiOut(['success' => false, 'error' => 'Message not found']);
        if ((int) $row['sender_id'] !== $api_uid)
            apiOut(['success' => false, 'error' => 'Not your message']);
        try {
            $pdo->prepare("DELETE FROM dating_messages WHERE id=?")->execute([$api_mid]);
            apiOut(['success' => true]);
        } catch (Exception $e) {
            apiOut(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    // EDIT
    if ($api_action === 'edit') {
        $txt = trim($_POST['text'] ?? '');
        if (!$api_mid || !$txt)
            apiOut(['success' => false, 'error' => 'Missing data']);
        $row = apiMsg($pdo, $api_mid);
        if (!$row || (int) $row['sender_id'] !== $api_uid)
            apiOut(['success' => false, 'error' => 'Not allowed']);
        try {
            $pdo->prepare("UPDATE dating_messages SET message=?,is_edited=1,edited_at=NOW() WHERE id=?")->execute([$txt, $api_mid]);
            apiOut(['success' => true, 'text' => htmlspecialchars($txt)]);
        } catch (Exception $e) {
            apiOut(['success' => false, 'error' => $e->getMessage()]);
        }
    }
    // PIN / UNPIN
    if ($api_action === 'pin' || $api_action === 'unpin') {
        if (!$api_mid)
            apiOut(['success' => false, 'error' => 'No message ID']);
        $row = apiMsg($pdo, $api_mid);
        if (!$row)
            apiOut(['success' => false, 'error' => 'Not found']);

        // Ownership check
        if ((int) $row['sender_id'] !== $api_uid && (int) $row['receiver_id'] !== $api_uid) {
            apiOut(['success' => false, 'error' => 'Not authorized']);
        }

        $pv = ($api_action === 'pin') ? 1 : 0;
        try {
            if ($pv === 1) {
                // Clear other pins for this conversation
                $oth = ((int) $row['sender_id'] === $api_uid) ? (int) $row['receiver_id'] : (int) $row['sender_id'];
                $pdo->prepare("UPDATE dating_messages SET is_pinned=0 WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)")
                    ->execute([$api_uid, $oth, $oth, $api_uid]);
            }
            $pdo->prepare("UPDATE dating_messages SET is_pinned=? WHERE id=?")->execute([$pv, $api_mid]);
            apiOut(['success' => true, 'pinned' => $pv]);
        } catch (Exception $e) {
            apiOut(['success' => false, 'error' => 'DB Error: ' . $e->getMessage()]);
        }
    }

    // FORWARD
    if ($api_action === 'forward') {
        $to = (int) ($_POST['to_user_id'] ?? 0);
        if (!$api_mid || !$to)
            apiOut(['success' => false, 'error' => 'Missing data']);
        $s = $pdo->prepare("SELECT * FROM dating_messages WHERE id=? AND (sender_id=? OR receiver_id=?)");
        $s->execute([$api_mid, $api_uid, $api_uid]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            apiOut(['success' => false, 'error' => 'Not found or not allowed']);
        try {
            $pdo->prepare("INSERT INTO dating_messages (sender_id,receiver_id,message,message_type,attachment_url,forwarded_from) SELECT ?,?,message,message_type,attachment_url,? FROM dating_messages WHERE id=?")->execute([$api_uid, $to, $api_mid, $api_mid]);
        } catch (Exception $e) {
            try {
                $pdo->prepare("INSERT INTO dating_messages (sender_id,receiver_id,message,message_type,attachment_url) SELECT ?,?,message,message_type,attachment_url FROM dating_messages WHERE id=?")->execute([$api_uid, $to, $api_mid]);
            } catch (Exception $e2) {
                apiOut(['success' => false, 'error' => $e2->getMessage()]);
            }
        }
        apiOut(['success' => true]);
    }

    // DETAILS
    if ($api_action === 'details') {
        if (!$api_mid)
            apiOut(['success' => false, 'error' => 'No message ID']);
        try {
            $s = $pdo->prepare("SELECT m.*, u.full_name as sender_name 
                               FROM dating_messages m 
                               JOIN users u ON m.sender_id = u.id 
                               WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)");
            $s->execute([$api_mid, $api_uid, $api_uid]);
            $msg = $s->fetch(PDO::FETCH_ASSOC);
            if (!$msg)
                apiOut(['success' => false, 'error' => 'Message not found']);

            // Format data for JS
            $msg['full_name'] = $msg['sender_name'];
            $msg['is_read'] = (int) ($msg['is_read'] ?? 0);
            $msg['is_pinned'] = (int) ($msg['is_pinned'] ?? 0);
            $msg['is_edited'] = (int) ($msg['is_edited'] ?? 0);

            apiOut(['success' => true, 'message' => $msg]);
        } catch (Exception $e) {
            apiOut(['success' => false, 'error' => 'Details Fail: ' . $e->getMessage()]);
        }
    }
    // GET_PINNED
    if ($api_action === 'get_pinned') {
        $oid = (int) ($_GET['other_id'] ?? 0);
        if (!$oid)
            apiOut(['pinned' => null]);
        try {
            $s = $pdo->prepare("SELECT * FROM dating_messages WHERE is_pinned=1 AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) ORDER BY created_at DESC LIMIT 1");
            $s->execute([$api_uid, $oid, $oid, $api_uid]);
            apiOut(['pinned' => $s->fetch(PDO::FETCH_ASSOC) ?: null]);
        } catch (Exception $e) {
            apiOut(['pinned' => null]);
        }
    }

    apiOut(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($api_action)]);
}
// ═══════════════════════════════════════════════════════════════════
// END API — normal page rendering below
// ═══════════════════════════════════════════════════════════════════
ob_end_clean();

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$other_user_id = intval($_GET['user_id'] ?? 0);
$base_url = BASE_URL;

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

    if (isset($_FILES['voice']) && $_FILES['voice']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/dating_chat/';
        if (!is_dir($upload_dir))
            mkdir($upload_dir, 0777, true);
        $file_name = 'voice_' . time() . '.webm';
        if (move_uploaded_file($_FILES['voice']['tmp_name'], $upload_dir . $file_name)) {
            $attachment = 'uploads/dating_chat/' . $file_name;
            $type = 'voice';
        }
    }

    if (!empty($msg) || $attachment) {
        $inserted = false;
        // Try advanced insert (with reply_to_id column if migration ran)
        try {
            $stmt = $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type, attachment_url, reply_to_id) VALUES (?,?,?,?,?,?)");
            $stmt->execute([$user_id, $other_user_id, $msg, $type, $attachment, $reply_to]);
            $inserted = true;
        } catch (Exception $e) {
            // Column might not exist yet — fallback to basic insert
        }
        if (!$inserted) {
            try {
                $stmt = $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type, attachment_url) VALUES (?,?,?,?,?)");
                $stmt->execute([$user_id, $other_user_id, $msg, $type, $attachment]);
            } catch (Exception $e) {
            }
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

// Fetch messages — try advanced query (with reply/pin columns), fallback to simple
$messages = [];
try {
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
} catch (Exception $e) {
    // Columns not migrated yet — use simple fallback
    $stmt = $pdo->prepare("SELECT * FROM dating_messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at ASC");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $messages = $stmt->fetchAll();
    // Add missing fields with defaults so the template doesn't break
    $messages = array_map(function ($m) {
        $m['reply_to_id'] = $m['reply_to_id'] ?? null;
        $m['reply_text'] = $m['reply_text'] ?? null;
        $m['reply_type'] = $m['reply_type'] ?? null;
        $m['reply_sender'] = $m['reply_sender'] ?? null;
        $m['is_pinned'] = $m['is_pinned'] ?? 0;
        $m['is_edited'] = $m['is_edited'] ?? 0;
        $m['edited_at'] = $m['edited_at'] ?? null;
        $m['forwarded_from'] = $m['forwarded_from'] ?? null;
        return $m;
    }, $messages);
}

// Fetch pinned message safely
$pinned_msg = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM dating_messages WHERE is_pinned=1 AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) LIMIT 1");
    $stmt->execute([$user_id, $other_user_id, $other_user_id, $user_id]);
    $pinned_msg = $stmt->fetch() ?: null;
} catch (Exception $e) {
    $pinned_msg = null; // is_pinned column not yet added — run migrate_dating_chat_v2.php
}

include '../includes/header.php';
?>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
<style>
    /* ─── Reply preview inside bubble ─── */
    .reply-preview {
        background: rgba(0, 0, 0, 0.05);
        border-left: 3px solid #2E7D32;
        padding: 4px 10px;
        margin-bottom: 6px;
        border-radius: 4px;
        font-size: .8rem;
    }

    .me .reply-preview {
        background: rgba(255, 255, 255, 0.15);
        border-left-color: #fff;
    }

    .them .reply-preview {
        background: rgba(0, 0, 0, 0.04);
        border-left-color: #2E7D32;
    }

    .reply-highlight {
        animation: pulseHighlight 1.5s ease;
    }

    @keyframes pulseHighlight {
        0% {
            background-color: rgba(46, 125, 50, 0.4);
        }

        100% {}
    }

    .animate-pulse {
        animation: pulseRecord 1s infinite;
    }

    @keyframes pulseRecord {
        0% {
            opacity: 1;
            transform: scale(1);
        }

        50% {
            opacity: 0.5;
            transform: scale(1.2);
        }

        100% {
            opacity: 1;
            transform: scale(1);
        }
    }

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
        display: none !important;
        background: #f8f9fa;
        border-left: 4px solid #2E7D32;
        padding: 8px 14px;
        margin-bottom: 8px;
        border-radius: 8px;
        font-size: .82rem;
        align-items: center;
        gap: 8px;
        transition: all .2s;
    }

    #replyBar .reply-cancel {
        cursor: pointer;
        color: #999;
        font-size: 1.1rem;
        padding-left: 10px;
    }

    /* ─── Edit bar ─── */
    #editBar {
        display: none !important;
        background: #fffde7;
        border-left: 4px solid #f9a825;
        padding: 8px 14px;
        margin-bottom: 8px;
        border-radius: 8px;
        font-size: .82rem;
        align-items: center;
        gap: 8px;
        transition: all .2s;
    }

    #editBar .edit-cancel {
        cursor: pointer;
        color: #999;
        font-size: 1.1rem;
        padding-left: 10px;
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
// Fetch ALL dating users for forward modal (excluding self)
try {
    $stmt = $pdo->prepare("
        SELECT DISTINCT u.id, u.full_name, p.profile_pic
        FROM users u
        LEFT JOIN dating_profiles p ON u.id = p.user_id
        WHERE u.id != ? AND (u.role = 'dating' OR u.id IN (
            SELECT DISTINCT sender_id   FROM dating_messages WHERE receiver_id = ?
            UNION
            SELECT DISTINCT receiver_id FROM dating_messages WHERE sender_id   = ?
        ))
        ORDER BY u.full_name
        LIMIT 30
    ");
    $stmt->execute([$user_id, $user_id, $user_id]);
    $contacts = $stmt->fetchAll();
} catch (Exception $e) {
    $contacts = [];
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
                                    <?php echo htmlspecialchars($other_user['full_name']); ?>
                                </div>
                                <div class="text-muted" style="font-size:.72rem;">
                                    <?php echo $is_matched ? '<span class="badge bg-danger">Matched</span>' : 'Online'; ?>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex gap-3">
                            <a href="dating_video_call.php?user_id=<?php echo $other_user_id; ?>&is_video=0"
                                class="btn btn-light rounded-circle p-2 text-primary" title="Voice Call"><i
                                    class="fas fa-phone"></i></a>
                            <a href="dating_video_call.php?user_id=<?php echo $other_user_id; ?>&is_video=1"
                                class="btn btn-light rounded-circle p-2 text-danger" title="Video Call"><i
                                    class="fas fa-video"></i></a>
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
                                    <?php echo htmlspecialchars(substr($pinned_msg['message'], 0, 80)); ?>
                                </div>
                            </div>
                            <button class="btn btn-sm p-0 text-muted"
                                onclick="event.stopPropagation();unpinMessage(<?php echo $pinned_msg['id']; ?>)"><i
                                    class="fas fa-times"></i></button>
                        </div>
                    <?php endif; ?>

                    <!-- ─── Messages ─── -->
                    <div class="chat-body" id="chatBody">
                        <div id="messageContainer">
                            <?php if (empty($messages)): ?>
                                <div class="text-center py-5 animate__animated animate__fadeIn">
                                    <span style="font-size:3.5rem;">👋</span>
                                    <h5 class="fw-bold mt-3">Start the conversation!</h5>
                                    <p class="text-muted small">Say hi to
                                        <?php echo htmlspecialchars($other_user['full_name']); ?>
                                    </p>
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

                                        <?php if ($m['reply_to_id']): ?>
                                            <div class="reply-preview"
                                                onclick="scrollToMessage(<?php echo $m['reply_to_id']; ?>); event.stopPropagation();"
                                                style="cursor:pointer">
                                                <div class="fw-bold"
                                                    style="font-size: .72rem; color:<?php echo $is_me ? '#e8f5e9' : '#1b5e20'; ?>;">
                                                    <?php echo htmlspecialchars($m['reply_sender'] ?? ''); ?>
                                                </div>
                                                <div class="text-truncate" style="font-size: .78rem; opacity: .85;">
                                                    <?php if ($m['reply_type'] === 'image'): ?>
                                                        <i class="fas fa-camera me-1"></i>Photo
                                                    <?php elseif ($m['reply_type'] === 'voice'): ?>
                                                        <i class="fas fa-microphone me-1"></i>Voice Message
                                                    <?php else: ?>
                                                        <?php echo htmlspecialchars(substr($m['reply_text'] ?? '', 0, 60)); ?>
                                                    <?php endif; ?>
                                                </div>
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

                                        <?php if ($m['message_type'] === 'voice' && $m['attachment_url']): ?>
                                            <div class="voice-msg py-1">
                                                <audio controls class="voice-player"
                                                    style="height:30px; filter: <?php echo $is_me ? 'invert(1) grayscale(1) brightness(2)' : ''; ?>">
                                                    <source src="<?php echo $base_url . '/' . $m['attachment_url']; ?>"
                                                        type="audio/webm">
                                                    Your browser does not support audio.
                                                </audio>
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
                        <!-- Reply Bar -->
                        <div id="replyBar" class="d-flex">
                            <i class="fas fa-reply text-success mt-1 me-2"></i>
                            <div class="reply-info">
                                <div class="reply-name" id="replyName"
                                    style="font-size: 0.75rem; font-weight: 700; color: #2E7D32;"></div>
                                <div id="replyText" class="text-muted text-truncate"
                                    style="max-width:220px; font-size: 0.82rem;"></div>
                            </div>
                            <span class="reply-cancel ms-auto" onclick="cancelReply()">✕</span>
                        </div>

                        <!-- Edit Bar -->
                        <div id="editBar" class="d-flex">
                            <i class="fas fa-pencil-alt text-warning mt-1 me-2"></i>
                            <div class="flex-1">
                                <div class="fw-bold text-warning" style="font-size: 0.75rem;">Editing Message</div>
                                <div id="editOriginal" class="text-muted text-truncate"
                                    style="max-width:220px; font-size: 0.82rem;"></div>
                            </div>
                            <span class="edit-cancel ms-auto" onclick="cancelEdit()">✕</span>
                        </div>

                        <!-- Recording Bar -->
                        <div id="recordingBar" class="d-flex align-items-center justify-content-between d-none"
                            style="background: #fff; padding: 10px; border-radius: 8px; margin-bottom: 8px; animation: fadeIn .3s;">
                            <div class="d-flex align-items-center gap-2">
                                <i class="fas fa-microphone text-danger animate-pulse"></i>
                                <span class="fw-bold text-danger" id="recordTimer">0:00</span>
                                <span class="text-muted small ms-2">Recording...</span>
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3"
                                    onclick="cancelRecording()">Cancel</button>
                                <button type="button" class="btn btn-sm btn-success rounded-pill px-3"
                                    onclick="stopAndSendRecording()">Send</button>
                            </div>
                        </div>

                        <form method="POST" enctype="multipart/form-data" id="chatForm">
                            <?php echo csrfField(); ?>
                            <input type="hidden" name="reply_to_id" id="replyToId" value="">
                            <input type="file" name="image" id="imgInput" class="d-none" accept="image/*">
                            <button type="button" class="btn btn-light rounded-circle p-2 border-0"
                                onclick="document.getElementById('imgInput').click()">
                                <i class="fas fa-image text-primary"></i>
                            </button>
                            <input type="text" name="message" id="msgInput" class="msg-input" placeholder="Message..."
                                autocomplete="off" oninput="toggleInputButtons()">
                            <button type="button" id="micBtn" class="send-btn" onclick="startVoiceRecording()"><i
                                    class="fas fa-microphone"></i></button>
                            <button type="submit" id="sendBtn" class="send-btn d-none"><i
                                    class="fas fa-paper-plane"></i></button>
                        </form>
                    </div>

                </div><!-- chat-card -->
            </div>
        </div>
    </div>
</div><!-- chat-page -->

<!-- ─── Context Menu ─── -->
<div id="ctxMenu">
    <div class="ctx-item" id="ctxReply" onclick="ctxAction('reply')">
        <i class="fas fa-reply text-success"></i> Reply
    </div>
    <div class="ctx-item" id="ctxCopy" onclick="ctxAction('copy')">
        <i class="fas fa-copy text-primary"></i> Copy
    </div>
    <div class="ctx-item" id="ctxForward" onclick="ctxAction('forward')">
        <i class="fas fa-share text-info"></i> Forward
    </div>
    <div class="ctx-item" id="ctxPin" onclick="ctxAction('pin')">
        <i class="fas fa-thumbtack text-warning"></i> Pin
    </div>
    <div class="ctx-item" id="ctxUnpin" onclick="ctxAction('unpin')">
        <i class="fas fa-thumbtack text-muted"></i> Unpin
    </div>
    <div class="ctx-item" id="ctxDetails" onclick="ctxAction('details')">
        <i class="fas fa-info-circle text-secondary"></i> Details
    </div>
    <div class="ctx-sep" id="meSep"></div>
    <div class="ctx-item" id="ctxEdit" onclick="ctxAction('edit')">
        <i class="fas fa-pencil-alt text-warning"></i> Edit
    </div>
    <div class="ctx-sep" id="delSep"></div>
    <div class="ctx-item ctx-danger" id="ctxDelete" onclick="ctxAction('delete')">
        <i class="fas fa-trash-alt"></i> Delete
    </div>
</div>

<!-- ─── Details Modal ─── -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4" style="border-radius:18px">
            <h5 class="fw-bold mb-3"><i class="fas fa-info-circle me-2 text-primary"></i>Message Details</h5>
            <div id="detailsBody" class="text-muted small"></div>
            <button class="btn btn-light rounded-pill mt-3" data-bs-dismiss="modal">Close</button>
        </div>
    </div>
</div>

<!-- ─── Forward Modal ─── -->
<div class="modal fade" id="forwardModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-4" style="border-radius:18px">
            <h5 class="fw-bold mb-3"><i class="fas fa-share me-2 text-info"></i>Forward to...</h5>
            <div id="forwardContacts" style="max-height:320px;overflow-y:auto">
                <?php if (!empty($contacts)): ?>
                    <?php foreach ($contacts as $c): ?>
                        <div class="contact-item"
                            onclick="doForward(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['full_name']); ?>')"
                            id="fwd-user-<?php echo $c['id']; ?>">
                            <img src="<?php echo htmlspecialchars($c['profile_pic'] ?: 'https://ui-avatars.com/api/?name=' . urlencode($c['full_name']) . '&background=1B5E20&color=fff'); ?>"
                                class="rounded-circle" width="40" height="40" style="object-fit:cover;flex-shrink:0">
                            <span class="fw-medium"><?php echo htmlspecialchars($c['full_name']); ?></span>
                            <i class="fas fa-check-circle text-success ms-auto d-none fwd-check"></i>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-4">
                        <i class="fas fa-users fa-2x text-muted mb-2"></i>
                        <p class="text-muted">No other dating users found.</p>
                    </div>
                <?php endif; ?>
            </div>
            <button class="btn btn-light rounded-pill mt-3 w-100" data-bs-dismiss="modal">Cancel</button>
        </div>
    </div>
</div>

<script>
    // API = THIS same page — detects 'action' POST param and returns JSON directly
    // No separate file needed, completely avoids Apache 403 restrictions!
    const API = '<?php echo rtrim(BASE_URL, "/"); ?>/customer/dating_chat.php?user_id=<?php echo $other_user_id; ?>';
    const OTHER_ID = <?php echo $other_user_id; ?>;
    const MY_ID = <?php echo $user_id; ?>;
    const OTHER_NAME = '<?php echo htmlspecialchars($other_user['full_name']); ?>';

    let activeRow = null;
    let editMsgId = null;
    let tapTimer = null;

    /* ─── Robust API fetch helper ─── */
    /* Returns a Promise that always resolves to a plain object.         */
    /* If PHP outputs non-JSON (error/warning), we surface the raw text. */
    function apiFetch(params) {
        return fetch(API, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        })
            .then(r => {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' ' + r.statusText);
                return r.text();          // read as text first
            })
            .then(raw => {
                try {
                    return JSON.parse(raw);
                } catch (_) {
                    // PHP printed something before JSON — show the raw output
                    const preview = raw.substring(0, 300).replace(/</g, '&lt;');
                    return { success: false, error: 'Invalid API response (PHP error?): ' + preview };
                }
            });
    }

    /* ─── Verify API is reachable on page load (silent) ─── */
    apiFetch({ action: 'ping' }).then(d => {
        if (!d.success) console.warn('Chat API ping failed:', d.error);
        else console.log('Chat API OK — user_id:', d.user_id);
    }).catch(e => console.warn('Chat API unreachable:', e.message));

    /* ─── Scroll to bottom on load ─── */
    const chatBody = document.getElementById('chatBody');
    chatBody.scrollTop = chatBody.scrollHeight;

    /* ─── Submit image immediately on select ─── */
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
        const hasText = !!row.dataset.text;

        // Show/hide items based on ownership & state
        show('ctxReply', true);
        show('ctxCopy', hasText);
        show('ctxForward', true);
        show('ctxPin', !isPinned);
        show('ctxUnpin', isPinned);
        show('ctxDetails', true);
        show('meSep', isMe);
        show('ctxEdit', isMe && hasText && row.dataset.type === 'text');   // edit: only own text msgs
        show('delSep', isMe);
        show('ctxDelete', isMe);              // delete: only own msgs

        const menu = document.getElementById('ctxMenu');
        menu.style.display = 'block';

        // Smart positioning
        let x = e.clientX, y = e.clientY;
        const mw = menu.offsetWidth || 210;
        const mh = menu.offsetHeight || 290;
        if (x + mw > window.innerWidth) x = window.innerWidth - mw - 10;
        if (y + mh > window.innerHeight) y = window.innerHeight - mh - 10;
        if (x < 4) x = 4;
        if (y < 4) y = 4;
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
    }

    function show(id, visible) {
        const el = document.getElementById(id);
        if (el) el.style.display = visible ? 'flex' : 'none';
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
        const isMe = activeRow.dataset.me === '1';

        /* ─── COPY ─── */
        if (action === 'copy') {
            if (!text) { showToast('Nothing to copy', 'warning'); return; }
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text)
                    .then(() => showToast('Copied to clipboard! ✓', 'success'))
                    .catch(() => {
                        legacyCopy(text);
                        showToast('Copied! ✓', 'success');
                    });
            } else {
                legacyCopy(text);
                showToast('Copied! ✓', 'success');
            }
            return;
        }

        /* ─── REPLY ─── */
        if (action === 'reply') {
            document.getElementById('replyToId').value = id;
            document.getElementById('replyName').textContent = isMe ? 'You' : OTHER_NAME;
            document.getElementById('replyText').textContent = (activeRow.dataset.type === 'voice' ? '🎤 Voice Message' : (text || '📷 Photo'));
            // Show reply bar (override CSS display:none)
            const replyBar = document.getElementById('replyBar');
            replyBar.style.setProperty('display', 'flex', 'important');
            document.getElementById('msgInput').focus();
            cancelEdit();
            return;
        }

        /* ─── EDIT (own messages only) ─── */
        if (action === 'edit') {
            if (!isMe) { showToast('You can only edit your own messages', 'warning'); return; }
            editMsgId = id;
            document.getElementById('editOriginal').textContent = text;
            const editBar = document.getElementById('editBar');
            editBar.style.setProperty('display', 'flex', 'important');
            document.getElementById('msgInput').value = text;
            document.getElementById('msgInput').focus();
            cancelReply();
            return;
        }

        /* ─── DELETE (own messages only) ─── */
        if (action === 'delete') {
            if (!isMe) { showToast('You can only delete your own messages', 'warning'); return; }
            if (!confirm('Delete this message for everyone?')) return;
            const savedRow = activeRow;
            apiFetch({ action: 'delete', msg_id: id })
                .then(d => {
                    if (d.success) {
                        savedRow.style.transition = 'opacity .3s, transform .3s';
                        savedRow.style.opacity = '0';
                        savedRow.style.transform = 'scale(0.9)';
                        setTimeout(() => savedRow.remove(), 320);
                        showToast('Message deleted 🗑️', 'danger');
                    } else {
                        showToast(d.error || 'Delete failed', 'danger');
                    }
                })
                .catch(err => showToast('Error: ' + err.message, 'danger'));
            return;
        }

        /* ─── PIN ─── */
        if (action === 'pin') {
            apiFetch({ action: 'pin', msg_id: id })
                .then(d => {
                    if (d.success) { showToast('Message pinned 📌', 'success'); setTimeout(() => location.reload(), 900); }
                    else showToast(d.error || 'Pin failed', 'warning');
                })
                .catch(err => showToast('Error: ' + err.message, 'danger'));
            return;
        }

        /* ─── UNPIN ─── */
        if (action === 'unpin') {
            unpinMessage(id);
            return;
        }

        /* ─── FORWARD ─── */
        if (action === 'forward') {
            // Reset checkmarks
            document.querySelectorAll('.fwd-check').forEach(el => el.classList.add('d-none'));
            const modal = new bootstrap.Modal(document.getElementById('forwardModal'));
            modal.show();
            return;
        }

        /* ─── DETAILS ─── */
        if (action === 'details') {
            const detailsBody = document.getElementById('detailsBody');
            detailsBody.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary"></div><div class="mt-2 text-muted small">Loading details...</div></div>';
            bootstrap.Modal.getOrCreateInstance(document.getElementById('detailsModal')).show();

            apiFetch({ action: 'details', msg_id: id })
                .then(d => {
                    if (d.success) {
                        const m = d.message;
                        const sentAt = m.created_at ? new Date(m.created_at.replace(' ', 'T')).toLocaleString() : '—';
                        const editAt = m.edited_at ? new Date(m.edited_at.replace(' ', 'T')).toLocaleString() : null;
                        const typeLbl = m.message_type === 'image' ? '📷 Image' : '💬 Text';
                        const readLbl = m.is_read == 1 ? '<span class="text-success fw-bold">✅ Read</span>' : '<span class="text-muted">⏳ Not yet read</span>';
                        const pinLbl = m.is_pinned == 1 ? '<span class="text-warning fw-bold">📌 Pinned</span>' : '<span class="text-muted">—</span>';
                        const editLbl = m.is_edited == 1 ? `<span class="text-warning">✏️ Edited${editAt ? ' at ' + editAt : ''}</span>` : '<span class="text-muted">—</span>';
                        detailsBody.innerHTML = `
                            <table class="table table-sm table-borderless mb-0" style="font-size:.9rem">
                                <tr><td class="fw-bold text-muted pe-3" style="width:90px">Sender</td><td>${escHtml(m.full_name)}</td></tr>
                                <tr><td class="fw-bold text-muted">Sent at</td><td>${sentAt}</td></tr>
                                <tr><td class="fw-bold text-muted">Type</td><td>${typeLbl}</td></tr>
                                <tr><td class="fw-bold text-muted">Read</td><td>${readLbl}</td></tr>
                                <tr><td class="fw-bold text-muted">Pinned</td><td>${pinLbl}</td></tr>
                                <tr><td class="fw-bold text-muted">Edited</td><td>${editLbl}</td></tr>
                                ${m.message ? `<tr><td class="fw-bold text-muted align-top">Content</td><td style="word-break:break-word">${escHtml(m.message)}</td></tr>` : ''}
                            </table>`;
                    } else {
                        detailsBody.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="fas fa-exclamation-circle me-2"></i>${escHtml(d.error || 'Not found')}</div>`;
                    }
                })
                .catch(err => {
                    detailsBody.innerHTML = `<div class="alert alert-danger py-2 mb-0"><i class="fas fa-wifi me-2"></i>${escHtml(err.message)}</div>`;
                });
            return;
        }
    }

    /* Fallback clipboard for http:// pages */
    function legacyCopy(text) {
        const ta = document.createElement('textarea');
        ta.value = text;
        ta.style.cssText = 'position:fixed;opacity:0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
    }

    /* Safe HTML escape */
    function escHtml(str) {
        if (!str) return '—';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function unpinMessage(id) {
        if (!confirm('Unpin this message?')) return;
        apiFetch({ action: 'unpin', msg_id: id })
            .then(d => {
                if (d.success) { showToast('Unpinned ✓', 'secondary'); setTimeout(() => location.reload(), 800); }
                else showToast(d.error || 'Failed', 'warning');
            })
            .catch(err => showToast('Error: ' + err.message, 'danger'));
    }

    function doForward(toUserId, toName) {
        if (!activeRow) return;
        // Visual feedback
        const btn = document.getElementById('fwd-user-' + toUserId);
        if (btn) { btn.style.opacity = '0.5'; btn.style.pointerEvents = 'none'; }

        apiFetch({ action: 'forward', msg_id: activeRow.dataset.id, to_user_id: toUserId })
            .then(d => {
                if (btn) {
                    const chk = btn.querySelector('.fwd-check');
                    if (chk) chk.classList.remove('d-none');
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = '';
                }
                if (d.success) {
                    showToast('📤 Forwarded to ' + toName, 'info');
                    setTimeout(() => {
                        const m = bootstrap.Modal.getInstance(document.getElementById('forwardModal'));
                        if (m) m.hide();
                    }, 900);
                } else {
                    showToast(d.error || 'Forward failed', 'danger');
                }
            })
            .catch(err => showToast('Error: ' + err.message, 'danger'));
    }

    /* ─── Reply helpers ─── */
    function cancelReply() {
        const rb = document.getElementById('replyBar');
        rb.style.setProperty('display', 'none', 'important');
        document.getElementById('replyToId').value = '';
    }
    function cancelEdit() {
        const eb = document.getElementById('editBar');
        eb.style.setProperty('display', 'none', 'important');
        editMsgId = null;
        document.getElementById('msgInput').value = '';
    }

    /* ─── Form submit: handle edit mode ─── */
    document.getElementById('chatForm').addEventListener('submit', function (e) {
        if (!editMsgId) return; // normal send

        e.preventDefault();
        const text = document.getElementById('msgInput').value.trim();
        if (!text) return;

        apiFetch({ action: 'edit', msg_id: editMsgId, text })
            .then(d => {
                if (d.success) {
                    const row = document.getElementById('msg-' + editMsgId);
                    if (row) {
                        const bubble = row.querySelector('.bubble > div:not(.reply-preview):not(.fwd-label):not(.meta)');
                        if (bubble) bubble.textContent = text;
                    }
                    cancelEdit();
                    showToast('Message edited ✓', 'success');
                } else {
                    showToast(d.error || 'Edit failed', 'warning');
                }
            })
            .catch(err => showToast('Error: ' + err.message, 'danger'));
    });

    /* ─── Scroll to original msg with highlight pulse ─── */
    function scrollToMessage(id) {
        const el = document.getElementById('msg-' + id);
        if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const bubble = el.querySelector('.bubble');
            bubble.classList.add('reply-highlight');
            setTimeout(() => bubble.classList.remove('reply-highlight'), 1500);
        }
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

    /* ─── Voice Recording Logic ─── */
    let mediaRecorder;
    let audioChunks = [];
    let recordInterval;
    let recStartTime;

    function toggleInputButtons() {
        const input = document.getElementById('msgInput');
        const micBtn = document.getElementById('micBtn');
        const sendBtn = document.getElementById('sendBtn');
        if (input.value.trim().length > 0) {
            micBtn.classList.add('d-none');
            sendBtn.classList.remove('d-none');
        } else {
            micBtn.classList.remove('d-none');
            sendBtn.classList.add('d-none');
        }
    }

    async function startVoiceRecording() {
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            alert("Your browser doesn't support voice recording.");
            return;
        }

        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream);
            audioChunks = [];

            mediaRecorder.ondataavailable = (event) => {
                audioChunks.push(event.data);
            };

            mediaRecorder.onstop = async () => {
                const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
                if (window.pendingVoiceSend) {
                    await uploadVoiceMessage(audioBlob);
                    window.pendingVoiceSend = false;
                }
                stream.getTracks().forEach(track => track.stop());
            };

            mediaRecorder.start();

            // UI Updates
            document.getElementById('recordingBar').classList.remove('d-none');
            document.getElementById('chatForm').classList.add('d-none');
            recStartTime = Date.now();
            recordInterval = setInterval(updateRecordTimer, 1000);
        } catch (err) {
            console.error(err);
            alert("Microphone access denied.");
        }
    }

    function updateRecordTimer() {
        const elapsed = Math.floor((Date.now() - recStartTime) / 1000);
        const mins = Math.floor(elapsed / 60);
        const secs = elapsed % 60;
        document.getElementById('recordTimer').textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
    }

    function cancelRecording() {
        window.pendingVoiceSend = false;
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        cleanupRecordUI();
    }

    function stopAndSendRecording() {
        window.pendingVoiceSend = true;
        if (mediaRecorder && mediaRecorder.state !== 'inactive') {
            mediaRecorder.stop();
        }
        cleanupRecordUI();
    }

    function cleanupRecordUI() {
        document.getElementById('recordingBar').classList.add('d-none');
        document.getElementById('chatForm').classList.remove('d-none');
        clearInterval(recordInterval);
        document.getElementById('recordTimer').textContent = '0:00';
    }

    async function uploadVoiceMessage(blob) {
        const fd = new FormData();
        fd.append('voice', blob, 'voice.webm');
        fd.append('message', ''); // Empty text
        fd.append('reply_to_id', document.getElementById('replyToId').value);

        // Add CSRF token
        const csrf = document.querySelector('input[name="csrf_token"]');
        if (csrf) fd.append('csrf_token', csrf.value);

        try {
            const res = await fetch(window.location.href, {
                method: 'POST',
                body: fd
            });
            if (res.ok) {
                window.location.reload();
            } else {
                alert("Upload failed.");
            }
        } catch (e) {
            alert("Error sending voice: " + e.message);
        }
    }

    /* ─── Auto-poll for new messages & incoming calls every 5s ─── */
    const msgContainer = document.getElementById('messageContainer');
    setInterval(() => {
        // 1. Check for new messages
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

        // Global Call Polling is handled by header.php ── no duplicate confirm() boxes needed here.
    }, 5000);
</script>
<?php include '../includes/footer.php'; ?>