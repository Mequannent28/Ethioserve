<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = getCurrentUserId();
    $app_id = (int) ($_POST['application_id'] ?? 0);
    $receiver_id = (int) ($_POST['receiver_id'] ?? 0);
    $msg_content = sanitize($_POST['message'] ?? '');
    $reply_to_id = (int) ($_POST['reply_to_id'] ?? 0);
    $csrf_token = $_POST['csrf_token'] ?? '';

    if (!verifyCSRFToken($csrf_token)) {
        echo json_encode(['success' => false, 'message' => 'Security token invalid. Please refresh the page.']);
        exit();
    }

    if ($receiver_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Receiver ID is missing or invalid.']);
        exit();
    }

    if (empty($msg_content)) {
        echo json_encode(['success' => false, 'message' => 'Empty message']);
        exit();
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO job_messages (application_id, sender_id, receiver_id, message, message_type, reply_to_id) VALUES (?, ?, ?, ?, 'text', ?)");
        $stmt->execute([$app_id, $user_id, $receiver_id, $msg_content, $reply_to_id ?: null]);
        
        // Notify receiver by email
        require_once '../includes/email_service.php';
        $stmt_user = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
        $stmt_user->execute([$receiver_id]);
        $receiver = $stmt_user->fetch();

        if ($receiver) {
            $sender_name = $_SESSION['full_name'] ?? 'Someone';
            $title = "New Message from {$sender_name}";
            $message = "You have received a new message regarding your application:\n\n\"" . substr($msg_content, 0, 100) . "...\"";
            $cta_url = "/employer/applications.php"; // Default to employer panel for now, can be improved
            sendDirectNotification($receiver['email'], $title, $message, 'Open Chat', $cta_url);
        }

        echo json_encode(['success' => true, 'message' => 'Sent successfully']);
    } catch (Exception $e) {
        error_log("Chat Insert Error: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
