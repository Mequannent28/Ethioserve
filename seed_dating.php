<?php
require_once 'includes/db.php';

echo "<h2>Dating Feature Seeder</h2>";

try {
    // 1. Create Sample Users if they don't exist
    $samples = [
        ['selam_dating', 'selam@example.com', 'Selam Hailu', 'female', 24, 'Addis Ababa', 'I love coffee and exploring new places in the city.'],
        ['dawit_dating', 'dawit@example.com', 'Dawit Mekonnen', 'male', 27, 'Bole, Addis', 'Software engineer looking for someone to share ideas with.'],
        ['beaza_dating', 'beaza@example.com', 'Beaza Tadesse', 'female', 22, 'Kazanchis', 'Student at AAU. Loves music and traditional dance.'],
        ['aman_dating', 'aman@example.com', 'Aman Yoseph', 'male', 29, 'Piazza', 'Business owner, traveler, and foodie.'],
        ['eden_dating', 'eden@example.com', 'Eden Tesfaye', 'female', 25, 'Sarbet', 'Yoga instructor who loves nature.']
    ];

    $profile_pics = [
        'female' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=400&h=600&q=80',
        'male' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=400&h=600&q=80'
    ];

    foreach ($samples as $s) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$s[0]]);
        $user = $stmt->fetch();

        if (!$user) {
            $pass = password_hash('password123', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$s[0], $s[1], $pass, $s[2]]);
            $uid = $pdo->lastInsertId();
            echo "Created user: {$s[2]}<br>";
        } else {
            $uid = $user['id'];
        }

        // Check if profile exists
        $stmt = $pdo->prepare("SELECT id FROM dating_profiles WHERE user_id = ?");
        $stmt->execute([$uid]);
        if (!$stmt->fetch()) {
            $pic = $profile_pics[$s[3]];
            $stmt = $pdo->prepare("INSERT INTO dating_profiles (user_id, gender, age, bio, location_name, profile_pic, looking_for, interests) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $uid,
                $s[3],
                $s[4],
                $s[5],
                $s[5],
                $pic,
                ($s[3] == 'male' ? 'female' : 'male'),
                'Coffee, Travel, Music'
            ]);
            echo "Created dating profile for: {$s[2]}<br>";
        }
    }

    echo "<h3 style='color: green;'>Seeding Complete!</h3>";
    echo "<a href='customer/dating.php'>Go to Dating Section</a>";

} catch (Exception $e) {
    echo "<h3 style='color: red;'>Seeding Failed: " . $e->getMessage() . "</h3>";
}
