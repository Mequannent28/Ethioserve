<?php
session_start();
require_once '../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hotel') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
$hotel_id = $hotel['id'];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];

        $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hotel_id, $category_id, $name, $description, $price, $image_url]);

        header("Location: menu_management.php?success=added");
        exit();

    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // Ensure the item belongs to this hotel
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, category_id = ?, price = ?, description = ?, image_url = ?, is_available = ? WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$name, $category_id, $price, $description, $image_url, $is_available, $id, $hotel_id]);

        header("Location: menu_management.php?success=updated");
        exit();
    }
}

if ($action === 'delete') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);

    header("Location: menu_management.php?success=deleted");
    exit();
}
?>