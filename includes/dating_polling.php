<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = getCurrentUserId();

try {
    // Check for new dating messages
    // We only want to notify about messages that haven't been "popped up" yet.
    // However, the database doesn't have a 'notified' flag, only 'is_read'.
    // A simple way is to check FOR ANY unread message from the last 10 seconds.
    $stmt = $pdo->prepare("
        SELECT m.*, u.full_name, p.profile_pic 
        FROM dating_messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN dating_profiles p ON u.id = p.user_id
        WHERE m.receiver_id = ? AND m.is_read = 0 AND m.created_at > DATE_SUB(NOW(), INTERVAL 15 SECOND)
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $msg = $stmt->fetch();

    if ($msg) {
        echo json_encode(['status' => 'ok', 'new_message' => true, 'message' => $msg]);
    } else {
        echo json_encode(['status' => 'ok', 'new_message' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
