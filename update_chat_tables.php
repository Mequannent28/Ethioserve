<?php
require_once 'includes/db.php';

echo "<h2>🚀 Updating Chat Tables for Rich Features...</h2><hr>";

$tasks = [
    "job_messages" => [
        "ALTER TABLE job_messages ADD COLUMN IF NOT EXISTS message_type ENUM('text','image','file','voice') DEFAULT 'text' AFTER message",
        "ALTER TABLE job_messages ADD COLUMN IF NOT EXISTS attachment_url TEXT AFTER message_type",
        "ALTER TABLE job_messages ADD COLUMN IF NOT EXISTS reply_to_id INT DEFAULT NULL AFTER attachment_url",
    ],
    "doctor_messages" => [
        "ALTER TABLE doctor_messages ADD COLUMN IF NOT EXISTS message_type ENUM('text','image','file','voice') DEFAULT 'text' AFTER message",
        "ALTER TABLE doctor_messages ADD COLUMN IF NOT EXISTS attachment_url TEXT AFTER message_type",
        "ALTER TABLE doctor_messages ADD COLUMN IF NOT EXISTS reply_to_id INT DEFAULT NULL AFTER attachment_url",
    ]
];
foreach ($tasks as $table => $queries) {
    echo "<h3>Updating $table...</h3>";
    foreach ($queries as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Executed: $sql<br>";
        }
        catch (Exception $e) {
            echo "❌ Failed: $sql - " . $e->getMessage() . "<br>";
        }
    }
}
echo "<hr><h3 style='color:green'>✅ Chat tables updated successfully!</h3>";
