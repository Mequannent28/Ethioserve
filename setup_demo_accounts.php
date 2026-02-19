<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

// Complete Demo Setup Script: Create user accounts for ALL platform roles
// This matches the "Quick Login" buttons in login.php
$demo_users = [
    [
        'username' => 'customer1',
        'full_name' => 'Demo Customer',
        'email' => 'customer@demo.com',
        'password' => 'password',
        'role' => 'customer',
        'phone' => '0900112233'
    ],
    [
        'username' => 'hilton_owner',
        'full_name' => 'Hilton Admin',
        'email' => 'hotel@demo.com',
        'password' => 'password',
        'role' => 'hotel',
        'phone' => '0911223344'
    ],
    [
        'username' => 'lucy_restaurant',
        'full_name' => 'Lucy Restaurant Pro',
        'email' => 'rest@demo.com',
        'password' => 'password',
        'role' => 'restaurant',
        'phone' => '0922334455'
    ],
    [
        'username' => 'ride_addis',
        'full_name' => 'Ride Taxi Service',
        'email' => 'taxi@demo.com',
        'password' => 'password',
        'role' => 'taxi',
        'phone' => '0933445566'
    ],
    [
        'username' => 'broker1',
        'full_name' => 'Ethio Broker Agent',
        'email' => 'broker@demo.com',
        'password' => 'password',
        'role' => 'broker',
        'phone' => '0944556677'
    ],
    [
        'username' => 'golden_bus',
        'full_name' => 'Golden Bus Admin',
        'email' => 'transport@demo.com',
        'password' => 'password',
        'role' => 'transport',
        'phone' => '0955667788'
    ],
    [
        'username' => 'student1',
        'full_name' => 'Sample Student',
        'email' => 'student@demo.com',
        'password' => 'password',
        'role' => 'student',
        'phone' => '0966778899'
    ],
    [
        'username' => 'selam_dating',
        'full_name' => 'Selam (Dating)',
        'email' => 'selam@demo.com',
        'password' => 'password',
        'role' => 'dating',
        'phone' => '0977889900'
    ],
    [
        'username' => 'dawit_dating',
        'full_name' => 'Dawit (Dating)',
        'email' => 'dawit@demo.com',
        'password' => 'password',
        'role' => 'dating',
        'phone' => '0988990011'
    ],
    [
        'username' => 'admin',
        'full_name' => 'System Administrator',
        'email' => 'admin@demo.com',
        'password' => 'password',
        'role' => 'admin',
        'phone' => '0999001122'
    ],
    [
        'username' => 'cloud_company',
        'full_name' => 'Red Cloud ICT Solution',
        'email' => 'redcloud@demo.com',
        'password' => 'password',
        'role' => 'employer',
        'phone' => '0912121212'
    ],
    [
        'username' => 'dr_dawit',
        'full_name' => 'Dr. Dawit Telemed',
        'email' => 'doctor@demo.com',
        'password' => 'password',
        'role' => 'doctor',
        'phone' => '0931313131'
    ]
];

echo "<h2>🚀 EthioServe Global Demo Account Provisioning</h2><hr>";

try {
    foreach ($demo_users as $user_data) {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$user_data['username']]);
        $existing = $stmt->fetch();

        if (!$existing) {
            $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);

            $pdo->prepare("INSERT INTO users (username, full_name, email, password, role, phone) VALUES (?, ?, ?, ?, ?, ?)")
                ->execute([
                    $user_data['username'],
                    $user_data['full_name'],
                    $user_data['email'],
                    $hashed_password,
                    $user_data['role'],
                    $user_data['phone']
                ]);
            echo "✅ Created: <strong>{$user_data['username']}</strong> [{$user_data['role']}]<br>";
        } else {
            // Update password just in case user wants "password" as the standard
            $hashed_password = password_hash($user_data['password'], PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $existing['id']]);
            echo "ℹ️ User <strong>{$user_data['username']}</strong> already exists (Password Reset to 'password')<br>";
        }
    }

    echo "<hr><div style='padding:20px; background:#e8f5e9; border-radius:12px; border:2px solid #2e7d32;'>";
    echo "<h3 style='color:#2e7d32;'>🎉 Success! All Demo Actors Are Ready.</h3>";
    echo "<p>You can now use the <strong>Quick Login</strong> buttons on the login page.</p>";
    echo "<p><strong>Standard Password:</strong> password</p>";
    echo "<a href='login.php' style='display:inline-block; padding:10px 20px; background:#2e7d32; color:#fff; text-decoration:none; border-radius:30px; font-weight:bold;'>Go to Login Page →</a>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='color:red; font-weight:bold;'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
