<?php
require_once 'includes/db.php';

$queries = [
    // Job Chat Messages
    "CREATE TABLE IF NOT EXISTS job_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        application_id INT NOT NULL,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (application_id) REFERENCES job_applications(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

echo "<h2>🚀 Running Job Chat Migration...</h2><hr>";
$success = true;
foreach ($queries as $i => $sql) {
    try {
        $pdo->exec($sql);
        echo "✅ Query " . ($i + 1) . " executed successfully<br>";
    } catch (Exception $e) {
        echo "❌ Query " . ($i + 1) . " failed: " . $e->getMessage() . "<br>";
        $success = false;
    }
}

if ($success) {
    echo "<h3 style='color:green'>✅ Job Chat migration complete!</h3>";
} else {
    echo "<h3 style='color:red'>⚠️ Migration completed with some errors.</h3>";
}
