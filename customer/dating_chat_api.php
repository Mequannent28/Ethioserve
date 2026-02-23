<?php ob_start();
error_reporting(0);
ini_set('display_errors', '0');
require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';

ob_end_clean();
header('Content-Type: application/json; charset=utf-8');
// ─── Auth ───────────────────────────────────────────────────────────────────
$user_id = (int) ($_SESSION['user_id'] ?? $_SESSION['id'] ?? 0);
if (!$user_id) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit;
}
// ─── Input ──────────────────────────────────────────────────────────────────
$action = trim($_POST['action'] ?? $_GET['action'] ?? '');
$msg_id = (int) ($_POST['msg_id'] ?? $_GET['msg_id'] ?? 0);
// ─── Helper ─────────────────────────────────────────────────────────────────
function fetchMsg(PDO $pdo, int $id): ?array
{
    $s = $pdo->prepare("SELECT * FROM dating_messages WHERE id = ?");
    $s->execute([$id]);
    $r = $s->fetch(PDO::FETCH_ASSOC);
    return $r ?: null;
}
function jsonOut(array $data): void
{
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
// ─── PING ───────────────────────────────────────────────────────────────────
if ($action === 'ping') {
    jsonOut(['success' => true, 'user_id' => $user_id, 'msg' => 'API OK']);
}
// ─── DELETE ─────────────────────────────────────────────────────────────────
if ($action === 'delete') {
    if (!$msg_id)
        jsonOut(['success' => false, 'error' => 'No message ID']);
    $row = fetchMsg($pdo, $msg_id);
    if (!$row)
        jsonOut(['success' => false, 'error' => 'Message not found']);
    if ((int) $row['sender_id'] !== $user_id)
        jsonOut(['success' => false, 'error' => 'Not your message']);
    try {
        // Delete associated file if it exists
        if (!empty($row['attachment_url'])) {
            $filePath = '../' . $row['attachment_url'];
            if (file_exists($filePath)) {
                @unlink($filePath);
            }
        }
        $pdo->prepare("DELETE FROM dating_messages WHERE id = ?")->execute([$msg_id]);
        jsonOut(['success' => true]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
}
// ─── EDIT ───────────────────────────────────────────────────────────────────
if ($action === 'edit') {
    $new_text = trim($_POST['text'] ?? '');
    if (!$msg_id || $new_text === '')
        jsonOut(['success' => false, 'error' => 'Missing data']);
    $row = fetchMsg($pdo, $msg_id);
    if (!$row || (int) $row['sender_id'] !== $user_id)
        jsonOut(['success' => false, 'error' => 'Not allowed']);
    try {
        $pdo->prepare("UPDATE dating_messages SET message=?, is_edited=1, edited_at=NOW() WHERE id=?")
            ->execute([$new_text, $msg_id]);
        jsonOut(['success' => true, 'text' => htmlspecialchars($new_text)]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
}
// ─── PIN / UNPIN ────────────────────────────────────────────────────────────
if ($action === 'pin' || $action === 'unpin') {
    if (!$msg_id)
        jsonOut(['success' => false, 'error' => 'No message ID']);
    $row = fetchMsg($pdo, $msg_id);
    if (!$row)
        jsonOut(['success' => false, 'error' => 'Message not found']);
    if ((int) $row['sender_id'] !== $user_id && (int) $row['receiver_id'] !== $user_id)
        jsonOut(['success' => false, 'error' => 'Not your conversation']);
    $pinVal = ($action === 'pin') ? 1 : 0;
    try {
        if ($pinVal === 1) {
            $other = ((int) $row['sender_id'] === $user_id) ? (int) $row['receiver_id'] : (int) $row['sender_id'];
            $pdo->prepare("UPDATE dating_messages SET is_pinned=0 WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)")
                ->execute([$user_id, $other, $other, $user_id]);
        }
        $pdo->prepare("UPDATE dating_messages SET is_pinned=? WHERE id=?")->execute([$pinVal, $msg_id]);
        jsonOut(['success' => true, 'pinned' => $pinVal]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
}
// ─── FORWARD ────────────────────────────────────────────────────────────────
if ($action === 'forward') {
    $to = (int) ($_POST['to_user_id'] ?? 0);
    if (!$msg_id || !$to)
        jsonOut(['success' => false, 'error' => 'Missing data']);
    $s = $pdo->prepare("SELECT * FROM dating_messages WHERE id=? AND (sender_id=? OR receiver_id=?)");
    $s->execute([$msg_id, $user_id, $user_id]);
    $row = $s->fetch(PDO::FETCH_ASSOC);
    if (!$row)
        jsonOut(['success' => false, 'error' => 'Not found or not allowed']);
    try {
        $pdo->prepare("INSERT INTO dating_messages (sender_id,receiver_id,message,message_type,attachment_url,forwarded_from)
             SELECT ?,?,message,message_type,attachment_url,? FROM dating_messages WHERE id=?")
            ->execute([$user_id, $to, $msg_id, $msg_id]);
    } catch (Exception $e) {
        try {
            $pdo->prepare("INSERT INTO dating_messages (sender_id,receiver_id,message,message_type,attachment_url)
                           SELECT ?,?,message,message_type,attachment_url FROM dating_messages WHERE id=?")
                ->execute([$user_id, $to, $msg_id]);
        } catch (Exception $e2) {
            jsonOut(['success' => false, 'error' => $e2->getMessage()]);
        }
    }
    jsonOut(['success' => true]);
}
// ─── DETAILS ────────────────────────────────────────────────────────────────
if ($action === 'details') {
    if (!$msg_id)
        jsonOut(['success' => false, 'error' => 'No message ID']);
    try {
        $s = $pdo->prepare("
            SELECT m.*, u.full_name,
                   COALESCE(m.is_edited, 0) AS is_edited,
                   COALESCE(m.is_pinned, 0) AS is_pinned,
                   COALESCE(m.is_read,   0) AS is_read,
                   m.edited_at
            FROM dating_messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        ");
        $s->execute([$msg_id, $user_id, $user_id]);
        $row = $s->fetch(PDO::FETCH_ASSOC);
        if (!$row)
            jsonOut(['success' => false, 'error' => 'Message not found or access denied']);
        jsonOut(['success' => true, 'message' => $row]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => 'DB error: ' . $e->getMessage()]);
    }
}
// ─── GET PINNED ─────────────────────────────────────────────────────────────
if ($action === 'get_pinned') {
    $other_id = (int) ($_GET['other_id'] ?? 0);
    if (!$other_id)
        jsonOut(['pinned' => null]);
    try {
        $s = $pdo->prepare("SELECT * FROM dating_messages WHERE is_pinned=1 AND ((sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?)) ORDER BY created_at DESC LIMIT 1");
        $s->execute([$user_id, $other_id, $other_id, $user_id]);
        jsonOut(['pinned' => $s->fetch(PDO::FETCH_ASSOC) ?: null]);
    } catch (Exception $e) {
        jsonOut(['pinned' => null]);
    }
}
// ─── LOAD NEW MESSAGES ──────────────────────────────────────────────────────
if ($action === 'load_messages') {
    $last_id = (int) ($_POST['last_id'] ?? $_GET['last_id'] ?? 0);
    $other_id = (int) ($_POST['other_id'] ?? $_GET['other_id'] ?? 0);
    if (!$other_id)
        jsonOut(['success' => true, 'messages' => []]);
    try {
        $s = $pdo->prepare("
            SELECT m.*, u.full_name as sender_name 
            FROM dating_messages m 
            JOIN users u ON m.sender_id = u.id
            WHERE m.id > ? 
            AND ((m.sender_id=? AND m.receiver_id=?) OR (m.sender_id=? AND m.receiver_id=?))
            ORDER BY m.id ASC
        ");
        $s->execute([$last_id, $user_id, $other_id, $other_id, $user_id]);
        $new_msgs = $s->fetchAll(PDO::FETCH_ASSOC);
        // Mark as read
        if (!empty($new_msgs)) {
            $pdo->prepare("UPDATE dating_messages SET is_read=1 WHERE sender_id=? AND receiver_id=? AND is_read=0 AND id > ?")
                ->execute([$other_id, $user_id, $last_id]);
        }
        jsonOut(['success' => true, 'messages' => $new_msgs]);
    } catch (Exception $e) {
        jsonOut(['success' => false, 'error' => $e->getMessage()]);
    }
}
jsonOut(['success' => false, 'error' => 'Unknown action: ' . htmlspecialchars($action)]);
