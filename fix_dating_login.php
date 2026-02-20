<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

/**
 * fix_dating_login.php
 * This script ensures the 'selam_dating' and 'dawit_dating' accounts 
 * have their passwords reset to exactly 'password'.
 */

$users_to_fix = [
    'selam_dating' => 'selam@demo.com',
    'dawit_dating' => 'dawit@demo.com'
];

$password = password_hash('password', PASSWORD_DEFAULT);

echo "<h2>🔧 Fixing Dating Login Accounts</h2><hr>";

try {
    foreach ($users_to_fix as $username => $email) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user) {
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$password, $user['id']]);
            echo "✅ Password for <strong>$username</strong> has been reset to: <code>password</code><br>";
        } else {
            // Create the user if it doesn't exist
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
            $stmt->execute([$username, $email, $password, ucfirst(str_replace('_dating', '', $username)) . " (Dating)"]);
            echo "✨ Account for <strong>$username</strong> did not exist; created now with password: <code>password</code><br>";
        }
    }

    echo "<hr><p style='color:green; font-weight:bold;'>All done! You can now log in with these accounts using the password 'password'.</p>";
    echo "<a href='login.php' style='padding:10px 20px; background:#1B5E20; color:white; text-decoration:none; border-radius:5px;'>Return to Login</a>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . $e->getMessage() . "</p>";
}
?>