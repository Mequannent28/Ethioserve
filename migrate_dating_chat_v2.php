<?php
require_once 'includes/db.php';

echo "<h2>🔧 Dating Chat v2 Migration</h2><hr>";

$queries = [
    "ALTER TABLE `dating_messages` ADD COLUMN IF NOT EXISTS `reply_to_id` INT NULL DEFAULT NULL AFTER `message`",
    "ALTER TABLE `dating_messages` ADD COLUMN IF NOT EXISTS `is_pinned` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`",
    "ALTER TABLE `dating_messages` ADD COLUMN IF NOT EXISTS `is_edited` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_pinned`",
    "ALTER TABLE `dating_messages` ADD COLUMN IF NOT EXISTS `edited_at` TIMESTAMP NULL DEFAULT NULL AFTER `is_edited`",
    "ALTER TABLE `dating_messages` ADD COLUMN IF NOT EXISTS `forwarded_from` INT NULL DEFAULT NULL AFTER `edited_at`",
];

foreach ($queries as $q) {
    try {
        $pdo->exec($q);
        echo "✅ " . substr($q, 0, 80) . "...<br>";
    } catch (Exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ Column already exists, skipping.<br>";
        } else {
            echo "❌ Error: " . htmlspecialchars($e->getMessage()) . "<br>";
        }
    }
}

echo "<hr><p style='color:green;font-weight:bold'>Migration complete! <a href='customer/dating_chat.php'>Test Dating Chat</a></p>";
?>