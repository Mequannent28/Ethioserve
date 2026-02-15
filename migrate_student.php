<?php
require_once 'includes/db.php';

echo "<h2>🔄 EthioServe — Student Role Migration</h2>";
echo "<pre style='background:#f5f5f5;padding:20px;border-radius:10px;font-family:monospace;'>";

try {
    // Step 1: Alter users role ENUM to include 'student'
    echo "➡️ Step 1: Updating users.role ENUM...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'hotel', 'broker', 'transport', 'customer', 'restaurant', 'taxi', 'student') DEFAULT 'customer'");
    echo "   ✅ users.role ENUM updated\n\n";

    // Step 2: Seed demo student account
    echo "➡️ Step 2: Seeding demo student account...\n";
    $hashed = password_hash('password', PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['student1']);
    if (!$stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'student')");
        $stmt->execute(['student1', 'student@ethioserve.com', $hashed, 'Abebe Student']);
        echo "   ✅ student1 account created\n";
    } else {
        echo "   ⏩ student1 account already exists\n";
    }

    echo "\n\n🎉 <strong>Migration completed successfully!</strong>\n";
    echo "   🎓 Student: <strong>student1</strong> / password\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='login.php' style='display:inline-block;padding:12px 30px;background:#1565C0;color:white;text-decoration:none;border-radius:30px;font-weight:bold;'>→ Go to Login</a>";
?>