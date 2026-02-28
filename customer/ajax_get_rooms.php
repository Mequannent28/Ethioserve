<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$hotel_id = (int) ($_GET['hotel_id'] ?? 0);

if ($hotel_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid hotel ID']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, room_number, room_type, price_per_night, description, image_url 
        FROM hotel_rooms 
        WHERE hotel_id = ? AND status = 'available'
        ORDER BY room_number ASC
    ");
    $stmt->execute([$hotel_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
