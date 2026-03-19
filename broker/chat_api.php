<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'error' => 'Not logged in']);
    exit();
}

$user_id = getCurrentUserId();
$action = $_GET['action'] ?? '';

if ($action === 'send') {
    $request_id = intval($_POST['request_id']);
    $receiver_id = intval($_POST['receiver_id']);
    $message = sanitize($_POST['message']);

    if (!$request_id || !$message) {
        echo json_encode(['success' => false, 'error' => 'Missing data']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO rental_chat_messages (request_id, sender_id, receiver_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$request_id, $user_id, $receiver_id, $message]);
        echo json_encode(['success' => true]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($action === 'fetch') {
    $request_id = intval($_GET['request_id']);
    $last_id = intval($_GET['last_id']);

    try {
        $stmt = $pdo->prepare("SELECT * FROM rental_chat_messages WHERE request_id = ? AND id > ? ORDER BY created_at ASC");
        $stmt->execute([$request_id, $last_id]);
        $messages = $stmt->fetchAll();
        
        // Mark as read
        if (!empty($messages)) {
            $pdo->prepare("UPDATE rental_chat_messages SET is_read = 1 WHERE request_id = ? AND receiver_id = ? AND is_read = 0")
                ->execute([$request_id, $user_id]);
        }

        // Fetch typing status
        $stmt_type = $pdo->prepare("SELECT customer_id, customer_typing_at, owner_typing_at FROM rental_requests WHERE id = ?");
        $stmt_type->execute([$request_id]);
        $req_data = $stmt_type->fetch();
        
        $is_typing = false;
        if ($req_data) {
            $other_at = ($user_id == $req_data['customer_id']) ? $req_data['owner_typing_at'] : $req_data['customer_typing_at'];
            if ($other_at && (time() - strtotime($other_at)) < 4) {
                $is_typing = true;
            }
        }

        echo json_encode(['success' => true, 'messages' => $messages, 'is_typing' => $is_typing]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} elseif ($action === 'typing') {
    $request_id = intval($_POST['request_id']);
    try {
        $stmt = $pdo->prepare("SELECT customer_id FROM rental_requests WHERE id = ?");
        $stmt->execute([$request_id]);
        $req = $stmt->fetch();
        if ($req) {
            $col = ($user_id == $req['customer_id']) ? 'customer_typing_at' : 'owner_typing_at';
            $pdo->prepare("UPDATE rental_requests SET $col = CURRENT_TIMESTAMP WHERE id = ?")->execute([$request_id]);
            echo json_encode(['success' => true]);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid action']);
}
