<?php
// One-time role migration script - DELETE after use
require_once 'includes/db.php';

$secret = $_GET['key'] ?? '';
if ($secret !== 'ethioserve_fix_2026') {
    die('Unauthorized. Pass ?key=ethioserve_fix_2026');
}

$fixes = [
    ['username' => 'cloud_company', 'role' => 'employer'],
    ['username' => 'ride_ethiopia', 'role' => 'taxi'],
    ['username' => 'feres', 'role' => 'taxi'],
    ['username' => 'yango', 'role' => 'taxi'],
    ['username' => 'dr_abebe', 'role' => 'doctor'],
    ['email' => 'mequalimaz2015@gmail.com', 'role' => 'employer']
];

$results = [];

// 1. Ensure profile_photo column exists 
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    $results[] = "✅ profile_photo column ensured";
} catch (Exception $e) {
    // Try without IF NOT EXISTS if server is old
    try {
        $check = $pdo->query("SHOW COLUMNS FROM users LIKE 'profile_photo'")->fetch();
        if (!$check) {
            $pdo->exec("ALTER TABLE users ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL");
            $results[] = "✅ profile_photo column added";
        } else {
            $results[] = "✅ profile_photo column already exists";
        }
    } catch (Exception $e2) {
        $results[] = "❌ profile_photo: " . $e2->getMessage();
    }
}

// 2. Fix roles only (NO password reset)
foreach ($fixes as $fix) {
    try {
        if (isset($fix['username'])) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ? AND (role = '' OR role IS NULL OR role != ?)");
            $stmt->execute([$fix['role'], $fix['username'], $fix['role']]);
        } else {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE email = ? AND (role = '' OR role IS NULL OR role != ?)");
            $stmt->execute([$fix['role'], $fix['email'], $fix['role']]);
        }
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            $results[] = "✅ Updated role for " . ($fix['username'] ?? $fix['email']);
        }
    } catch (Exception $e) {
        $results[] = "❌ Role fix failed for " . ($fix['username'] ?? $fix['email']) . ": " . $e->getMessage();
    }
}

// 3. Ensure activity_logs table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        platform VARCHAR(50) DEFAULT 'WEB',
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (activity_type),
        INDEX (created_at)
    )");
    $results[] = "✅ activity_logs table ensured";
} catch (Exception $e) {
    $results[] = "❌ activity_logs: " . $e->getMessage();
}

echo "<pre style='font-family:monospace;font-size:16px;padding:20px;background:#f8f9fa;'>";
echo "<h2 style='color:#1B5E20'>System Account Migration - EthioServe</h2>\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<strong style='color:red'>⚠️ Security: Delete this file after running it once!</strong>";
echo "</pre>";
