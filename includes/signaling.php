<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$action = $_REQUEST['action'] ?? '';
$user_id = getCurrentUserId();

if ($action === 'initiate_call') {
    // Flexible receiver ID (could be doctor_id or user_id for dating)
    $receiver_id = intval($_POST['receiver_id'] ?? $_POST['doctor_id'] ?? 0);
    $room_id = sanitize($_POST['room_id'] ?? '');
    $call_type = sanitize($_POST['call_type'] ?? 'telemed'); // telemed, dating
    $is_video = intval($_POST['is_video'] ?? 1); // 1 for video, 0 for audio
    $call_id = uniqid('call_');

    if ($receiver_id <= 0 || empty($room_id)) {
        echo json_encode(['error' => 'Missing parameters']);
        exit();
    }

    try {
        // We use a unified table 'all_signaling' or just adapt health_signaling to be generic
        // Let's create/use 'signaling_calls' table instead
        $stmt = $pdo->prepare("INSERT INTO app_signaling (call_id, caller_id, receiver_id, room_id, status, call_type, is_video) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$call_id, $user_id, $receiver_id, $room_id, $call_type, $is_video]);
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

    // Support both numeric id and uuid call_id
    if (is_numeric($call_id)) {
        $stmt = $pdo->prepare("UPDATE app_signaling SET status = ? WHERE id = ? AND receiver_id = ?");
    } else {
        $stmt = $pdo->prepare("UPDATE app_signaling SET status = ? WHERE call_id = ? AND receiver_id = ?");
    }
    $stmt->execute([$status, $call_id, $user_id]);
    echo json_encode(['success' => true]);
} elseif ($action === 'check_incoming') {
    // Poll for new calls where 'me' is the receiver
    $stmt = $pdo->prepare("SELECT s.*, u.full_name as caller_name 
                           FROM app_signaling s 
                           JOIN users u ON s.caller_id = u.id 
                           WHERE s.receiver_id = ? AND s.status = 'pending' 
                           ORDER BY s.created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $call = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode(['call' => $call]);
}
