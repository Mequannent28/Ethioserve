<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Not logged in']);
    exit();
}

$user_id = getCurrentUserId();
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$msg_id = intval($_POST['msg_id'] ?? $_GET['msg_id'] ?? 0);

/* ─── Helper: verify sender owns message ─── */
function ownMessage($pdo, $msg_id, $user_id)
{
    $s = $pdo->prepare("SELECT id, sender_id, receiver_id FROM dating_messages WHERE id = ?");
    $s->execute([$msg_id]);
    return $s->fetch();
}

/* ═══════════════════════════════════════════
   DELETE
═══════════════════════════════════════════ */
if ($action === 'delete') {
    $row = ownMessage($pdo, $msg_id, $user_id);
    if (!$row || $row['sender_id'] != $user_id) {
        echo json_encode(['error' => 'Not allowed']);
        exit();
    }
    $pdo->prepare("DELETE FROM dating_messages WHERE id = ?")->execute([$msg_id]);
    echo json_encode(['success' => true]);
    exit();
}

/* ═══════════════════════════════════════════
   EDIT
═══════════════════════════════════════════ */
if ($action === 'edit') {
    $new_text = trim($_POST['text'] ?? '');
    if (!$new_text) {
        echo json_encode(['error' => 'Empty text']);
        exit();
    }

    $row = ownMessage($pdo, $msg_id, $user_id);
    if (!$row || $row['sender_id'] != $user_id) {
        echo json_encode(['error' => 'Not allowed']);
        exit();
    }
    $pdo->prepare("UPDATE dating_messages SET message = ?, is_edited = 1, edited_at = NOW() WHERE id = ?")
        ->execute([$new_text, $msg_id]);
    echo json_encode(['success' => true, 'text' => htmlspecialchars($new_text)]);
    exit();
}

/* ═══════════════════════════════════════════
   PIN / UNPIN
═══════════════════════════════════════════ */
if ($action === 'pin' || $action === 'unpin') {
    $pinVal = ($action === 'pin') ? 1 : 0;
    // Unpin all first in this conversation
    if ($pinVal === 1) {
        $row = ownMessage($pdo, $msg_id, $user_id);
        if (!$row) {
            echo json_encode(['error' => 'Not found']);
            exit();
        }
        $other = ($row['sender_id'] == $user_id) ? $row['receiver_id'] : $row['sender_id'];
        $pdo->prepare("UPDATE dating_messages SET is_pinned = 0 WHERE (sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)")
            ->execute([$user_id, $other, $other, $user_id]);
    }
    $pdo->prepare("UPDATE dating_messages SET is_pinned = ? WHERE id = ?")->execute([$pinVal, $msg_id]);
    echo json_encode(['success' => true, 'pinned' => $pinVal]);
    exit();
}

/* ═══════════════════════════════════════════
   FORWARD  (creates new message in target convo)
═══════════════════════════════════════════ */
if ($action === 'forward') {
    $to_user_id = intval($_POST['to_user_id'] ?? 0);
    if (!$to_user_id) {
        echo json_encode(['error' => 'No target']);
        exit();
    }

    $row = ownMessage($pdo, $msg_id, $user_id);
    if (!$row) {
        echo json_encode(['error' => 'Not found']);
        exit();
    }

    $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type, attachment_url, forwarded_from)
                   SELECT ?, ?, message, message_type, attachment_url, ? FROM dating_messages WHERE id = ?")
        ->execute([$user_id, $to_user_id, $msg_id, $msg_id]);
    echo json_encode(['success' => true]);
    exit();
}

/* ═══════════════════════════════════════════
   GET DETAILS
═══════════════════════════════════════════ */
if ($action === 'details') {
    $s = $pdo->prepare("SELECT m.*, u.full_name FROM dating_messages m JOIN users u ON m.sender_id = u.id WHERE m.id = ?");
    $s->execute([$msg_id]);
    $row = $s->fetch();
    if (!$row) {
        echo json_encode(['error' => 'Not found']);
        exit();
    }
    echo json_encode(['success' => true, 'message' => $row]);
    exit();
}

/* ═══════════════════════════════════════════
   GET PINNED for a conversation
═══════════════════════════════════════════ */
if ($action === 'get_pinned') {
    $other_id = intval($_GET['other_id'] ?? 0);
    $s = $pdo->prepare("SELECT * FROM dating_messages WHERE is_pinned = 1 AND ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) ORDER BY created_at DESC LIMIT 1");
    $s->execute([$user_id, $other_id, $other_id, $user_id]);
    $pinned = $s->fetch();
    echo json_encode(['pinned' => $pinned ?: null]);
    exit();
}

echo json_encode(['error' => 'Unknown action']);
?>