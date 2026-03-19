<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

header('Content-Type: application/json');

if (!isLoggedIn() || getCurrentUserRole() !== 'doctor') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = getCurrentUserId();

// Fetch provider (doctor) ID for this user
$stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
$stmt->execute([$user_id]);
$provider = $stmt->fetch();
$provider_id = $provider['id'] ?? 0;

if (!$provider_id) {
    echo json_encode(['success' => false, 'message' => 'Provider profile not found']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF check
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit();
    }

    $action = $_POST['action'] ?? '';
    
    if ($action === 'doctor_send_message') {
        $customer_id = intval($_POST['customer_id'] ?? 0);
        $message = sanitize($_POST['message'] ?? '');
        $reply_to_id = intval($_POST['reply_to_id'] ?? 0);
        $type = sanitize($_POST['message_type'] ?? 'text');
        
        if ($customer_id > 0 && !empty($message)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO doctor_messages (sender_id, sender_type, provider_id, customer_id, message, message_type, reply_to_id) VALUES (?, 'doctor', ?, ?, ?, ?, ?)");
                $stmt->execute([$provider_id, $provider_id, $customer_id, $message, $type, $reply_to_id ?: null]);
                
                // Notify Customer by email
                try {
                    require_once '../includes/email_service.php';
                    $stmt_cus = $pdo->prepare("SELECT full_name, email FROM users WHERE id = ?");
                    $stmt_cus->execute([$customer_id]);
                    $customer = $stmt_cus->fetch();

                    if ($customer) {
                        $doctor_name = $_SESSION['full_name'] ?? 'Your doctor';
                        $title = "New Reply from {$doctor_name}";
                        $msg_body = "Your doctor has replied to your inquiry on EthioServe:\n\n\"" . substr($message, 0, 100) . "...\"";
                        sendDirectNotification($customer['email'], $title, $msg_body, 'View Reply', '/customer/doctor_chat.php');
                    }
                } catch (Exception $e) { /* Silently fail email */ }

                echo json_encode(['success' => true, 'message' => 'Message sent']);
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'Database error']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid data']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid method']);
}
