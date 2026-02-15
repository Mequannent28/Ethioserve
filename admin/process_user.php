<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

if (!$id) {
    header("Location: manage_users.php");
    exit();
}

if ($action === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?"); // Don't delete self
    $stmt->execute([$id, $_SESSION['user_id']]);
    header("Location: manage_users.php?success=deleted");
    exit();
}

if ($action === 'role') {
    $new_role = $_GET['role'];
    $valid_roles = ['customer', 'hotel', 'broker', 'admin'];
    if (in_array($new_role, $valid_roles)) {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
        $stmt->execute([$new_role, $id]);
    }
    header("Location: manage_users.php?success=role_updated");
    exit();
}
