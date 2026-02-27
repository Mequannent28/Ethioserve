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
];

$results = [];

// Fix Account: mequalimaz2015@gmail.com (Main Account)
try {
    $email = 'mequalimaz2015@gmail.com';
    $password = 'password123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id, username FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'employer', password = ? WHERE email = ?");
        $stmt->execute([$hashed_password, $email]);
        $results[] = "✅ User '{$user['username']}' ({$email}) updated: role=employer, password set to 'password123'";
    } else {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES ('admin_user', ?, ?, 'Mequannent G.', 'employer')");
        $stmt->execute([$hashed_password, $email]);
        $results[] = "✅ Account created for {$email}: role=employer, password set to 'password123'";
    }
} catch (Exception $e) {
    $results[] = "❌ Email fix: " . $e->getMessage();
}

// Fix Account: cloud_company (Demo)
try {
    $username = 'cloud_company';
    $password = 'password123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'employer', password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        $results[] = "✅ User '{$username}' updated to employer and password reset";
    }
} catch (Exception $e) {
    $results[] = "❌ Demo fix: " . $e->getMessage();
}

foreach ($fixes as $fix) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ? AND (role = '' OR role IS NULL OR role != ?)");
        $stmt->execute([$fix['role'], $fix['username'], $fix['role']]);
        $affected = $stmt->rowCount();
        if ($affected > 0) {
            $results[] = "✅ {$fix['username']} → {$fix['role']} ({$affected} row updated)";
        }
    } catch (Exception $e) {
        $results[] = "❌ {$fix['username']}: " . $e->getMessage();
    }
}

// Ensure activity_logs table exists
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

// Add profile_photo column if missing
try {
    $pdo->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS profile_photo VARCHAR(255) DEFAULT NULL");
    $results[] = "✅ profile_photo column ensured in users table";
} catch (Exception $e) {
    // Try without IF NOT EXISTS (older MariaDB)
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

echo "<pre style='font-family:monospace;font-size:16px;padding:20px;'>";
echo "<strong>Role Migration Results:</strong>\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<strong style='color:red'>⚠️  IMPORTANT: Delete this file (fix_roles.php) after migration is complete!</strong>";
echo "</pre>";
