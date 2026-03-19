<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = getCurrentUserId();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'doctor_send_message') {
        $doctor_id = intval($_POST['doctor_id'] ?? 0);
        $message = sanitize($_POST['message'] ?? '');
        $reply_to_id = intval($_POST['reply_to_id'] ?? 0);
        $type = sanitize($_POST['message_type'] ?? 'text');
        
        if ($doctor_id > 0 && !empty($message)) {
            try {
                // Ensure doctor exists
                $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE id = ?");
                $stmt->execute([$doctor_id]);
                if (!$stmt->fetch()) {
                    echo json_encode(['success' => false, 'message' => 'Doctor not found']);
                    exit();
                }

                $stmt = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message, message_type, reply_to_id) VALUES (?, 'customer', ?, ?, ?, ?, ?)");
                $stmt->execute([$user_id, $doctor_id, $user_id, $message, $type, $reply_to_id ?: null]);
                
                // Notify Doctor by email
                try {
                    require_once '../includes/email_service.php';
                    $stmt_doc = $pdo->prepare("SELECT u.full_name, u.email FROM users u JOIN health_providers hp ON u.id = hp.user_id WHERE hp.id = ?");
                    $stmt_doc->execute([$doctor_id]);
                    $doctor = $stmt_doc->fetch();

                    if ($doctor) {
                        $sender_name = $_SESSION['full_name'] ?? 'A customer';
                        $title = "New Inquiry from {$sender_name}";
                        $msg_body = "You have received a new health inquiry on EthioServe:\n\n\"" . substr($message, 0, 100) . "...\"";
                        sendDirectNotification($doctor['email'], $title, $msg_body, 'View Chat', '/health/doctor_chat.php');
                    }
                } catch (Exception $e) { /* Silently fail email */ }

                echo json_encode(['success' => true, 'message' => 'Message sent']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Message or doctor ID missing']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
