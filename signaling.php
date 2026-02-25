<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_REQUEST['action'] ?? '';
$user_id = getCurrentUserId();

if ($action === 'initiate_call') {
    $receiver_id = intval($_POST['receiver_id'] ?? $_POST['doctor_id'] ?? 0);
    $room_id = sanitize($_POST['room_id'] ?? '');
    $call_type = sanitize($_POST['call_type'] ?? 'telemed');
    $is_video = intval($_POST['is_video'] ?? 1);
    $call_id = uniqid('call_');

    if ($receiver_id <= 0 || empty($room_id)) {
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    try {
        // Clear any previous pending calls between these two to avoid confusion
        $pdo->prepare("UPDATE app_signaling SET status = 'ended' WHERE caller_id = ? AND receiver_id = ? AND status =
    'pending'")
            ->execute([$user_id, $receiver_id]);

        $stmt = $pdo->prepare("INSERT INTO app_signaling (call_id, caller_id, receiver_id, room_id, status, call_type,
    is_video) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$call_id, $user_id, $receiver_id, $room_id, $call_type, $is_video]);

        // LOG TO DATING MESSAGES
        if ($call_type === 'dating') {
            $msg_type = ($is_video == 1) ? 'video_call' : 'voice';
            $call_label = ($is_video == 1) ? "📹 Video Call" : "📞 Voice Call";
            $pdo->prepare("INSERT INTO dating_messages (sender_id, receiver_id, message, message_type) VALUES (?, ?, ?, ?)")
                ->execute([$user_id, $receiver_id, "Started a $call_label", $msg_type]);
        }

        echo json_encode(['success' => true, 'call_id' => $call_id]);
    } catch (Exception $e) {
        echo json_encode(['error' => $e->getMessage()]);
    }
} elseif ($action === 'get_call_status') {
    $call_id = sanitize($_GET['call_id']);
    $stmt = $pdo->prepare("SELECT status FROM app_signaling WHERE call_id = ?");
    $stmt->execute([$call_id]);
    $status = $stmt->fetchColumn();
    echo json_encode(['status' => $status]);
} elseif ($action === 'handle_call' || $action === 'respond_call') {
    $call_id = $_POST['call_id'] ?? '';
    $status = sanitize($_POST['status']); // accepted or rejected

    if (is_numeric($call_id)) {
        $stmt = $pdo->prepare("UPDATE app_signaling SET status = ? WHERE id = ? AND receiver_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE app_signaling SET status = ? WHERE call_id = ? AND receiver_id = ?");
    }
    $stmt->execute([$status, $call_id, $user_id]);
    echo json_encode(['success' => true]);
} elseif ($action === 'check_incoming') {
    // Poll for new calls where 'me' is the receiver, limiting to last 10 minutes to avoid old ghosts
    try {
        $stmt = $pdo->prepare("SELECT s.*, u.full_name as caller_name
    FROM app_signaling s
    JOIN users u ON s.caller_id = u.id
    WHERE s.receiver_id = ? AND s.status = 'pending'
    AND s.created_at > DATE_SUB(NOW(), INTERVAL 600 SECOND)
    ORDER BY s.created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        $call = $stmt->fetch(PDO::FETCH_ASSOC);
        echo json_encode(['call' => $call]);
    } catch (Exception $e) {
        echo json_encode(['call' => null, 'error' => $e->getMessage()]);
    }
} elseif ($action === 'end_call') {
    $call_id = $_POST['call_id'] ?? '';
    if ($call_id) {
        $pdo->prepare("UPDATE app_signaling SET status = 'ended' WHERE call_id = ? OR id = ?")->execute([
            $call_id,
            $call_id
        ]);
    }
    echo json_encode(['success' => true]);
}