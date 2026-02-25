<?php
require_once 'includes/db.php';

try {
    // Add missing columns to app_signaling
    $pdo->exec("ALTER TABLE app_signaling ADD COLUMN IF NOT EXISTS room_id VARCHAR(255) AFTER receiver_id");
    $pdo->exec("ALTER TABLE app_signaling ADD COLUMN IF NOT EXISTS call_type VARCHAR(50) DEFAULT 'telemed' AFTER room_id");
    $pdo->exec("ALTER TABLE app_signaling ADD COLUMN IF NOT EXISTS is_video TINYINT(1) DEFAULT 1 AFTER call_type");
    $pdo->exec("ALTER TABLE app_signaling ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER is_video");

    echo "Table app_signaling updated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
