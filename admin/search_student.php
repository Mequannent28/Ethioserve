<?php
session_start();
require '../includes/db.php';
require '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'school_admin') {
    echo json_encode([]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
if (strlen($q) < 2) {
    echo json_encode([]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            u.id as user_id, u.full_name, u.email, u.phone,
            p.class_id, p.student_id_number, p.date_of_birth, p.gender,
            p.parent_name, p.parent_phone, p.emergency_contact, 
            p.previous_school, p.health_conditions, p.blood_group, p.home_address
        FROM users u
        LEFT JOIN sms_student_profiles p ON u.id = p.user_id
        WHERE u.role = 'student' AND (u.full_name LIKE ? OR u.email LIKE ? OR p.student_id_number LIKE ?)
        LIMIT 10
    ");
    $searchTerm = "%$q%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($results);
} catch (Exception $e) {
    echo json_encode([]);
}
