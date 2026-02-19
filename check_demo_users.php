<?php
require_once 'includes/db.php';

$usernames = ['cloud_company', 'dr_dawit', 'admin', 'customer1', 'hilton_owner'];
echo "Checking demo users...\n";

foreach ($usernames as $username) {
    echo "Checking: $username -> ";
    try {
        $stmt = $pdo->prepare("SELECT role FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        if ($user) {
            echo "✅ FOUND (" . $user['role'] . ")\n";
        } else {
            echo "❌ MISSING\n";
        }
    } catch (Exception $e) {
        echo "❌ ERROR: " . $e->getMessage() . "\n";
    }
}
