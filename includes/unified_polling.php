<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

if (!isLoggedIn()) {
    echo json_encode(['status' => 'error', 'message' => 'Not logged in']);
    exit();
}

$user_id = getCurrentUserId();

// Update last active time for online status
$stmt = $pdo->prepare("UPDATE users SET last_active = NOW() WHERE id = ?");
$stmt->execute([$user_id]);

// Use a 5-minute window to catch any recent messages that might have been delayed or missed due to slight clock drift.
// The frontend deduplicates by ID anyway.
$current_time_window = "5 MINUTE"; 

try {
    $new_messages = [];

    // 1. Dating Messages
    $stmt = $pdo->prepare("
        SELECT 'dating' as type, m.id, m.sender_id, m.message, m.message_type, m.created_at, u.full_name as sender_name, p.profile_pic as sender_photo
        FROM dating_messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN dating_profiles p ON u.id = p.user_id
        WHERE m.receiver_id = ? AND m.is_read = 0 AND m.created_at > DATE_SUB(NOW(), INTERVAL $current_time_window)
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $dating_msg = $stmt->fetch();
    if ($dating_msg) {
        $dating_msg['link'] = BASE_URL . '/customer/dating_chat.php?user_id=' . $dating_msg['sender_id'];
        $dating_msg['sender_photo'] = $dating_msg['sender_photo'] ? BASE_URL . '/uploads/dating/' . $dating_msg['sender_photo'] : null;
        $new_messages[] = $dating_msg;
    }

    // 2. Job Messages
    $stmt = $pdo->prepare("
        SELECT 'job' as type, m.id, m.sender_id, m.message, 'text' as message_type, m.created_at, u.full_name as sender_name, u.profile_photo as sender_photo,
               m.application_id
        FROM job_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ? AND m.is_read = 0 AND m.created_at > DATE_SUB(NOW(), INTERVAL $current_time_window)
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $job_msg = $stmt->fetch();
    if ($job_msg) {
        $job_msg['link'] = BASE_URL . '/customer/job_chat.php?application_id=' . $job_msg['application_id'];
        $new_messages[] = $job_msg;
    }

    // 3. Doctor/Health Messages 
    // We need to check if the user is a patient (customer_id = user_id AND sender_type='doctor')
    // OR if the user is a doctor (provider_id's user_id = user_id AND sender_type='customer')
    $stmt = $pdo->prepare("
        SELECT 'health' as type, m.id, m.sender_id, m.message, 'text' as message_type, m.created_at, u.full_name as sender_name, u.profile_photo as sender_photo,
               m.provider_id
        FROM doctor_messages m
        JOIN users u ON m.sender_id = u.id
        LEFT JOIN health_providers hp ON m.provider_id = hp.id
        WHERE (
            (m.customer_id = ? AND m.sender_type = 'doctor') OR
            (hp.user_id = ? AND m.sender_type = 'customer')
        ) 
        AND m.is_read = 0 
        AND m.created_at > DATE_SUB(NOW(), INTERVAL $current_time_window)
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id, $user_id]);
    $health_msg = $stmt->fetch();
    if ($health_msg) {
        $health_msg['link'] = BASE_URL . '/customer/doctor_chat.php?doctor_id=' . $health_msg['provider_id'];
        $new_messages[] = $health_msg;
    }

    // 4. School Messages
    $stmt = $pdo->prepare("
        SELECT 'school' as type, m.id, m.sender_id, m.message, 'text' as message_type, m.created_at, u.full_name as sender_name, u.role
        FROM school_messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ? AND m.is_read = 0 AND m.created_at > DATE_SUB(NOW(), INTERVAL $current_time_window)
        ORDER BY m.created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$user_id]);
    $school_msg = $stmt->fetch();
    if ($school_msg) {
        $school_msg['link'] = BASE_URL . '/chat.php?user_id=' . $school_msg['sender_id'];
        $new_messages[] = $school_msg;
    }

    if (!empty($new_messages)) {
        echo json_encode(['status' => 'ok', 'new_message' => true, 'messages' => $new_messages]);
    } else {
        echo json_encode(['status' => 'ok', 'new_message' => false]);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

