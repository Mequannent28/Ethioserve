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

// Fix Account: cloud_company (Employer)
try {
    $username = 'cloud_company';
    $password = 'password123'; // The password we want to set
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        // Update existing user: ensure role is employer AND reset password just in case
        $stmt = $pdo->prepare("UPDATE users SET role = 'employer', password = ? WHERE username = ?");
        $stmt->execute([$hashed_password, $username]);
        $results[] = "✅ User '{$username}' updated: role=employer, password set to 'password123'";
    } else {
        // Create the user if missing
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, phone, role) VALUES (?, ?, 'redcloud@demo.com', 'Red Cloud ICT Solutions', '+251 911 22 33 44', 'employer')");
        $stmt->execute([$username, $hashed_password]);
        $results[] = "✅ User '{$username}' CREATED: role=employer, password set to 'password123'";
    }
} catch (Exception $e) {
    $results[] = "❌ Account fix: " . $e->getMessage();
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
