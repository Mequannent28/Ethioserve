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

if ($action === 'initiate_call') {
    $doctor_id = intval($_POST['doctor_id'] ?? 0);
    $receiver_id = intval($_POST['receiver_id'] ?? 0);
    $room_id = $_POST['room_id'] ?? '';

    // Auto-detect receiver if only doctor_id is provided
    if (!$receiver_id && $doctor_id) {
        $stmt = $pdo->prepare("SELECT user_id FROM health_providers WHERE id = ?");
        $stmt->execute([$doctor_id]);
        $row = $stmt->fetch();
        if ($row)
            $receiver_id = $row['user_id'];
    }

    if (!$receiver_id || !$room_id) {
        echo json_encode(['error' => 'Missing receiver or room ID']);
        exit();
    }

    if ($receiver_id === $user_id) {
        echo json_encode(['error' => 'You cannot call yourself']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO video_calls (caller_id, receiver_id, provider_id, room_id, status) VALUES (?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $receiver_id, $doctor_id, $room_id]);
        $call_id = $pdo->lastInsertId();

        echo json_encode(['success' => true, 'call_id' => $call_id]);
    } catch (Exception $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
    exit();
}

if ($action === 'check_incoming') {
    // Check for pending calls for current user
    $stmt = $pdo->prepare("SELECT c.*, u.full_name as caller_name 
                           FROM video_calls c
                           JOIN users u ON c.caller_id = u.id
                           WHERE c.receiver_id = ? AND c.status = 'pending' AND c.created_at > (NOW() - INTERVAL 3 MINUTE)
                           ORDER BY c.created_at DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $call = $stmt->fetch();

    if ($call) {
        echo json_encode(['call' => $call]);
    } else {
        echo json_encode(['call' => null]);
    }
    exit();
}

if ($action === 'respond_call') {
    $call_id = intval($_POST['call_id']);
    $status = $_POST['status']; // accepted, rejected

    $stmt = $pdo->prepare("UPDATE video_calls SET status = ? WHERE id = ? AND receiver_id = ?");
    $stmt->execute([$status, $call_id, $user_id]);

    echo json_encode(['success' => true]);
    exit();
}

if ($action === 'get_call_status') {
    $call_id = intval($_GET['call_id'] ?? 0);
    $stmt = $pdo->prepare("SELECT status FROM video_calls WHERE id = ?");
    $stmt->execute([$call_id]);
    echo json_encode(['status' => $stmt->fetchColumn()]);
    exit();
}
