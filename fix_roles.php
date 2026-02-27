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
foreach ($fixes as $fix) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE username = ? AND (role = '' OR role IS NULL)");
        $stmt->execute([$fix['role'], $fix['username']]);
        $affected = $stmt->rowCount();
        $results[] = "✅ {$fix['username']} → {$fix['role']} ($affected row updated)";
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

echo "<pre style='font-family:monospace;font-size:16px;padding:20px;'>";
echo "<strong>Role Migration Results:</strong>\n\n";
foreach ($results as $r) {
    echo $r . "\n";
}
echo "\n<strong style='color:red'>⚠️  IMPORTANT: Delete this file (fix_roles.php) after migration is complete!</strong>";
echo "</pre>";
