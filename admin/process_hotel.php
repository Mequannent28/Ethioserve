<?php
session_start();
require_once '../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: manage_hotels.php");
    exit();
}

if ($action === 'status') {
    $status = $_GET['status'];
    if (in_array($status, ['approved', 'rejected', 'pending'])) {
        $stmt = $pdo->prepare("UPDATE hotels SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
    }
    header("Location: manage_hotels.php?success=status_updated");
    exit();

} elseif ($action === 'delete') {
    // Delete the hotel (cascade handles menu items etc)
    $stmt = $pdo->prepare("DELETE FROM hotels WHERE id = ?");
    $stmt->execute([$id]);

    header("Location: manage_hotels.php?success=deleted");
    exit();
}
?>