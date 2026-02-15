<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireLogin();

if (isset($_GET['grade'])) {
    $grade = (int) $_GET['grade'];
    $user_id = $_SESSION['user_id'];

    if ($grade >= 1 && $grade <= 12) {
        // Update user's grade
        $stmt = $pdo->prepare("UPDATE users SET grade = ? WHERE id = ?");
        $stmt->execute([$grade, $user_id]);

        // Update session
        $_SESSION['user_grade'] = $grade;

        // Determine redirect target
        $redirect = $_GET['redirect'] ?? 'education';
        $target = ($redirect === 'lms') ? 'lms.php' : 'education.php';

        // Redirect to target page with selected grade
        header("Location: $target?grade=" . $grade);
        exit();
    }
}

// Invalid grade or no grade, redirect back
header("Location: education.php");
exit();
?>