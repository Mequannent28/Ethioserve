<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

/**
 * fix_dating_login.php
 * This script ensures all dating demo accounts are correctly provisioned,
 * have the 'dating' role, a valid profile, and password reset to 'password'.
 */

$dating_users = [
    [
        'username' => 'selam_dating',
        'email' => 'selam@demo.com',
        'full_name' => 'Selam Hailu',
        'gender' => 'female',
        'looking_for' => 'male',
        'age' => 24,
        'bio' => 'Loves Ethiopian coffee, modern art, and weekend trips to Bishoftu. Looking for someone kind and ambitious.',
        'location' => 'Addis Ababa, Bole',
        'pic' => 'https://images.unsplash.com/photo-1523824921871-d6f1a15151f1?w=400&h=400&fit=crop'
    ],
    [
        'username' => 'dawit_dating',
        'email' => 'dawit@demo.com',
        'full_name' => 'Dawit Mekonnen',
        'gender' => 'male',
        'looking_for' => 'female',
        'age' => 27,
        'bio' => 'Tech enthusiast and marathon runner. Always up for a good conversation and some traditional food.',
        'location' => 'Addis Ababa, Kazanchis',
        'pic' => 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?w=400&h=400&fit=crop'
    ],
    [
        'username' => 'beaza_dating',
        'email' => 'beaza@demo.com',
        'full_name' => 'Beaza Tadesse',
        'gender' => 'female',
        'looking_for' => 'male',
        'age' => 22,
        'bio' => 'Architecture student who loves exploring old buildings and new ideas. Let\'s grab a juice!',
        'location' => 'Addis Ababa, Piazza',
        'pic' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=400&h=400&fit=crop'
    ],
    [
        'username' => 'aman_dating',
        'email' => 'aman@demo.com',
        'full_name' => 'Aman Yoseph',
        'gender' => 'male',
        'looking_for' => 'female',
        'age' => 29,
        'bio' => 'Chef in a local restaurant. I make the best Doro Wot in town. Looking for a partner to share life with.',
        'location' => 'Addis Ababa, Sarbet',
        'pic' => 'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?w=400&h=400&fit=crop'
    ],
    [
        'username' => 'eden_dating',
        'email' => 'eden@demo.com',
        'full_name' => 'Eden Tesfaye',
        'gender' => 'female',
        'looking_for' => 'everyone',
        'age' => 25,
        'bio' => 'Passionate about Ethiopian literature and classical music. Shy at first, but talkative once we get to know each other.',
        'location' => 'Addis Ababa, CMC',
        'pic' => 'https://images.unsplash.com/photo-1531123897727-8f129e1688ce?w=400&h=400&fit=crop'
    ]
];

$password_hashed = password_hash('password', PASSWORD_DEFAULT);

echo "<!DOCTYPE html><html><head><title>Dating Setup</title><style>body{font-family:sans-serif; padding:40px; background:#f0f2f5;} .card{background:white; padding:20px; border-radius:15px; box-shadow:0 4px 20px rgba(0,0,0,0.1); max-width:600px; margin:auto;} h2{color:#1B5E20;} code{background:#eee; padding:2px 5px; border-radius:4px;} .user-row{margin-bottom:10px; padding:10px; border-bottom:1px solid #eee;}</style></head><body><div class='card'>";
echo "<h2>❤️ EthioServe Dating Demo Provisoner</h2><hr>";

try {
    foreach ($dating_users as $u) {
        // 1. Create or Update User
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$u['username']]);
        $user = $stmt->fetch();

        if ($user) {
            $user_id = $user['id'];
            $stmt = $pdo->prepare("UPDATE users SET email = ?, full_name = ?, password = ?, role = 'dating' WHERE id = ?");
            $stmt->execute([$u['email'], $u['full_name'], $password_hashed, $user_id]);
            echo "<div class='user-row'>ℹ️ Updated <strong>{$u['username']}</strong></div>";
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, full_name, password, role) VALUES (?, ?, ?, ?, 'dating')");
            $stmt->execute([$u['username'], $u['email'], $u['full_name'], $password_hashed]);
            $user_id = $pdo->lastInsertId();
            echo "<div class='user-row'>✨ Created <strong>{$u['username']}</strong></div>";
        }

        // 2. Create or Update Profile
        $stmt = $pdo->prepare("SELECT id FROM dating_profiles WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $profile = $stmt->fetch();

        if ($profile) {
            $stmt = $pdo->prepare("UPDATE dating_profiles SET age = ?, gender = ?, looking_for = ?, bio = ?, location_name = ?, profile_pic = ? WHERE user_id = ?");
            $stmt->execute([$u['age'], $u['gender'], $u['looking_for'], $u['bio'], $u['location'], $u['pic'], $user_id]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO dating_profiles (user_id, age, gender, looking_for, bio, location_name, profile_pic) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $u['age'], $u['gender'], $u['looking_for'], $u['bio'], $u['location'], $u['pic']]);
        }
    }

    echo "<hr><p style='color:green; font-weight:bold;'>🎉 All 5 Dating Demo Accounts are now VALID and ready!</p>";
    echo "<p>Every user's password is now: <code>password</code></p>";
    echo "<a href='login.php' style='display:inline-block; margin-top:10px; padding:12px 25px; background:#1B5E20; color:white; text-decoration:none; border-radius:50px; font-weight:bold;'>Go to Login Page</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "</div></body></html>";
?>