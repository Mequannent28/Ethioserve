<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('hotel');
$user_id = getCurrentUserId();

// Get hotel for this user
$stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
if (!$hotel)
    die("Hotel record not found.");
$hotel_id = $hotel['id'];

// CSRF Check for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed. Please try again.");
    }
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $room_number = sanitize($_POST['room_number']);
        $room_type = sanitize($_POST['room_type']);
        $price = (float) $_POST['price_per_night'];
        $description = sanitize($_POST['description']);

        // Handle Image Upload
        $image_url = handleImageUpload('room_image', 'rooms');

        $stmt = $pdo->prepare("INSERT INTO hotel_rooms (hotel_id, room_number, room_type, price_per_night, description, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hotel_id, $room_number, $room_type, $price, $description, $image_url]);

        redirectWithMessage('rooms_management.php', 'success', 'Room added successfully!');

    } elseif ($action === 'edit') {
        $id = (int) $_POST['id'];
        $room_number = sanitize($_POST['room_number']);
        $room_type = sanitize($_POST['room_type']);
        $price = (float) $_POST['price_per_night'];
        $description = sanitize($_POST['description']);
        $status = sanitize($_POST['status']);

        // Handle Image Upload (Optional Update)
        $new_image = handleImageUpload('room_image', 'rooms');

        if ($new_image) {
            $stmt = $pdo->prepare("UPDATE hotel_rooms SET room_number = ?, room_type = ?, price_per_night = ?, description = ?, status = ?, image_url = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$room_number, $room_type, $price, $description, $status, $new_image, $id, $hotel_id]);
        } else {
            $stmt = $pdo->prepare("UPDATE hotel_rooms SET room_number = ?, room_type = ?, price_per_night = ?, description = ?, status = ? WHERE id = ? AND hotel_id = ?");
            $stmt->execute([$room_number, $room_type, $price, $description, $status, $id, $hotel_id]);
        }

        redirectWithMessage('rooms_management.php', 'success', 'Room updated successfully!');
    }
}

if ($action === 'delete') {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM hotel_rooms WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);
    redirectWithMessage('rooms_management.php', 'success', 'Room deleted successfully!');
}
