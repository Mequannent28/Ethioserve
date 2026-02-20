<?php
require_once 'includes/db.php';

try {
    // Add columns to dating_messages for image support
    $pdo->exec("ALTER TABLE dating_messages ADD COLUMN IF NOT EXISTS message_type ENUM('text', 'image', 'location', 'video_call') DEFAULT 'text' AFTER message");
    $pdo->exec("ALTER TABLE dating_messages ADD COLUMN IF NOT EXISTS attachment_url VARCHAR(255) DEFAULT NULL AFTER message_type");
    $pdo->exec("ALTER TABLE dating_messages ADD COLUMN IF NOT EXISTS is_read TINYINT(1) DEFAULT 0 AFTER attachment_url");

    echo "Dating messages table updated successfully!\n";
} catch (Exception $e) {
    // Try individually if IF NOT EXISTS isn't supported
    try {
        $pdo->exec("ALTER TABLE dating_messages ADD COLUMN message_type ENUM('text', 'image', 'location', 'video_call') DEFAULT 'text' AFTER message");
    } catch (Exception $ex) {
    }
    try {
        $pdo->exec("ALTER TABLE dating_messages ADD COLUMN attachment_url VARCHAR(255) DEFAULT NULL AFTER message_type");
    } catch (Exception $ex) {
    }
    try {
        $pdo->exec("ALTER TABLE dating_messages ADD COLUMN is_read TINYINT(1) DEFAULT 0 AFTER attachment_url");
    } catch (Exception $ex) {
    }
    echo "Dating messages table update attempted.\n";
}
