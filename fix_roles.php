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

// 2. RESET EVERY USER TO 'password123'
// This fulfills the request "possible to login all existing user"
try {
    $default_pass = 'password123';
    $hashed_pass = password_hash($default_pass, PASSWORD_DEFAULT);

    // Get all usernames
    $stmt = $pdo->query("SELECT id, username FROM users");
    $users = $stmt->fetchAll();

    foreach ($users as $u) {
        $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $upd->execute([$hashed_pass, $u['id']]);
    }

    $results[] = "✅ SUCCESS! All " . count($users) . " existing accounts now have password: '" . $default_pass . "'";
} catch (Exception $e) {
    $results[] = "❌ Global Reset Failed: " . $e->getMessage();
}

// 3. Ensure role fixing for demo accounts
$fixes = [
    ['username' => 'cloud_company', 'role' => 'employer'],
    ['username' => 'ride_ethiopia', 'role' => 'taxi'],
    ['username' => 'feres', 'role' => 'taxi'],
    ['username' => 'yango', 'role' => 'taxi'],
    ['username' => 'dr_abebe', 'role' => 'doctor'],
    ['email' => 'mequalimaz2015@gmail.com', 'role' => 'employer']
];

foreach ($fixes as $fix) {
    try {
        if (isset($fix['username'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ?");
            $stmt->execute([$fix['role'], $fix['username']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE email = ?");
            $stmt->execute([$fix['role'], $fix['email']]);
        }
    } catch (Exception $e) {
    }
}

echo "<pre style='font-family:monospace;font-size:16px;padding:20px;background:#f8f9fa;'>";
echo "<h2 style='color:#1B5E20'>System Account Sync - EthioServe</h2>\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<strong style='color:red'>⚠️ Security: Delete this file after running it once!</strong>";
echo "</pre>";
