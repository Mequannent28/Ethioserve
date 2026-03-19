<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an employer
requireRole('employer');

$user_id = getCurrentUserId();
$stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company || !isset($_GET['id'])) {
    header("Location: lms.php");
    exit();
}

$company_id = $company['id'];
$exam_id = (int)$_GET['id'];
$reason = sanitize($_GET['reason'] ?? 'Manual deletion');

// Move to Recycle Bin before deleting
require_once '../includes/recycle_bin_helper.php';
moveToRecycleBin($pdo, 'job_exams', $exam_id, 'employer', $user_id, $reason);

// Verify ownership and delete
$stmt = $pdo->prepare("DELETE FROM job_exams WHERE id = ? AND company_id = ?");
$stmt->execute([$exam_id, $company_id]);

$_SESSION['success_message'] = "Exam moved to recycle bin.";
header("Location: lms.php");
exit();
