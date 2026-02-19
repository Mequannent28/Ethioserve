<?php
require_once 'includes/db.php';

try {
    $pdo->beginTransaction();

    $username = 'test_dating_user';
    $email = 'test_dating@ethioserve.com';
    $full_name = 'Dating Tester';
    $pass = password_hash('password123', PASSWORD_DEFAULT);

    // 1. Create User
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
    $stmt->execute([$username, $email, $pass, $full_name]);
    $uid = $pdo->lastInsertId();

    // 2. Create Dating Profile
    $stmt = $pdo->prepare("INSERT INTO dating_profiles (user_id, age, gender, looking_for, bio, location_name, profile_pic, interests) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $uid,
        25,
        'male',
        'female',
        'Hello! I am a test account created to explore the EthioServe dating platform. I love technology and meeting new people.',
        'Bole, Addis Ababa',
        'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=600&q=80',
        'Technology, Travel, Music'
    ]);

    $pdo->commit();
    echo "SUCCESS: Created user <b>test_dating_user</b> with password <b>password123</b>";
} catch (Exception $e) {
    if ($pdo->inTransaction())
        $pdo->rollBack();
    echo "ERROR: " . $e->getMessage();
}
