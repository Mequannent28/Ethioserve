<?php
// One-time emergency migration/reset script - DELETE after use
require_once 'includes/db.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'ethioserve_fix_2026') {
    die('Unauthorized. Pass ?key=ethioserve_fix_2026');
}

$results = [];

// 1. Ensure profile_photo column exists 
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    $results[] = "✅ profile_photo column ensured";
} catch (Exception $e) {
    $results[] = "❌ Column check: " . $e->getMessage();
}

// 2. FORCE CREATE/FIX MAIN ACCOUNT (mequalimaz2015@gmail.com)
try {
    $email = 'mequalimaz2015@gmail.com';
    $password = 'password123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        // Just fix role and password
        $stmt = $pdo->prepare("UPDATE users SET role = 'employer', password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        $results[] = "✅ Main account updated successfully (Password: password123)";
    } else {
        // Create it fresh
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES ('Walelgn', ?, ?, 'Mequannent G.', 'employer')");
        $stmt->execute([$hashed_password, $email]);
        $results[] = "✅ Main account CREATED fresh (Username: Walelgn, Password: password123)";
    }
} catch (Exception $e) {
    $results[] = "❌ Account Fix: " . $e->getMessage();
}

// 3. Fix other roles
$fixes = [
    ['username' => 'cloud_company', 'role' => 'employer'],
    ['username' => 'ride_ethiopia', 'role' => 'taxi'],
    ['username' => 'feres', 'role' => 'taxi']
];

foreach ($fixes as $fix) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
        $stmt->execute([$fix['role'], $fix['username']]);
    } catch (Exception $e) {
    }
}

echo "<pre style='font-family:monospace;font-size:16px;padding:20px;background:#f8f9fa;'>";
echo "<h2 style='color:#1B5E20'>Render Repair - EthioServe</h2>\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<strong style='color:red'>⚠️ Security: Delete this file after running it once!</strong>";
echo "</pre>";
