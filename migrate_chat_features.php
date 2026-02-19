<?php
require_once 'includes/db.php';

try {
    // Enhance doctor_messages
    $pdo->exec("ALTER TABLE doctor_messages ADD COLUMN message_type ENUM('text', 'image', 'file', 'location') DEFAULT 'text' AFTER message");
    $pdo->exec("ALTER TABLE doctor_messages ADD COLUMN attachment_url VARCHAR(255) NULL AFTER message_type");
    echo "✅ Enhanced doctor_messages table.<br>";

    // Enhance job_messages
    $pdo->exec("ALTER TABLE job_messages ADD COLUMN message_type ENUM('text', 'image', 'file', 'location') DEFAULT 'text' AFTER message");
    $pdo->exec("ALTER TABLE job_messages ADD COLUMN attachment_url VARCHAR(255) NULL AFTER message_type");
    echo "✅ Enhanced job_messages table.<br>";

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "❌ Error (likely columns exist): " . $e->getMessage();
}
