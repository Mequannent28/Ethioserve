<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is a teacher
requireRole('teacher');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat with Students - Teacher Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root { --edu-green: #1B5E20; --edu-gold: #F9A825; }
        body { background: #f8f9fa; font-family: 'Inter', sans-serif; overflow: hidden; }
        .main-content { margin-left: 260px; height: 100vh; padding: 0; }
        
        @media (max-width: 992px) {
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <?php include('../includes/sidebar_teacher.php'); ?>

    <div class="main-content">
        <iframe src="../chat.php<?= isset($_GET['user_id']) ? '?user_id=' . urlencode($_GET['user_id']) : '' ?>" 
                style="width: 100%; height: 100%; border: none;"></iframe>
    </div>

</body>
</html>
