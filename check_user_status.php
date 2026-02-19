<?php
require_once 'includes/db.php';

$username = 'cloud_company';
echo "Checking user: $username\n";

try {
    $stmt = $pdo->prepare("SELECT id, username, email, password, role FROM users WHERE username = ? OR email = ?");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

    if ($user) {
        echo "✅ User found!\n";
        echo "ID: " . $user['id'] . "\n";
        echo "Username: " . $user['username'] . "\n";
        echo "Role: " . $user['role'] . "\n";

        $password_to_test = 'password';
        if (password_verify($password_to_test, $user['password'])) {
            echo "✅ Password 'password' is CORRECT for this user.\n";
        } else {
            echo "❌ Password 'password' is INCORRECT for this user.\n";
        }
    } else {
        echo "❌ User '$username' NOT found in the database.\n";

        // Let's check how many users are in the table
        $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "Total users in database: $count\n";

        // Show last 5 users
        echo "\nLast 5 users:\n";
        $stmt = $pdo->query("SELECT username, email, role FROM users ORDER BY id DESC LIMIT 5");
        while ($row = $stmt->fetch()) {
            echo "- {$row['username']} ({$row['role']})\n";
        }
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
