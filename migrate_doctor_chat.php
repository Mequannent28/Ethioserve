<?php
/**
 * Migration: Create doctor_messages table for doctor-patient chat
 */
require_once 'includes/db.php';

try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS doctor_messages (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sender_id INT NOT NULL,
            sender_type ENUM('customer', 'doctor') NOT NULL DEFAULT 'customer',
            provider_id INT NOT NULL COMMENT 'health_providers.id (doctor)',
            customer_id INT NOT NULL COMMENT 'users.id (patient)',
            message TEXT NOT NULL,
            is_read TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_provider_customer (provider_id, customer_id),
            INDEX idx_customer (customer_id),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    echo "<h2 style='color:green;'>✅ doctor_messages table created successfully!</h2>";

} catch (Exception $e) {
    echo "<h2 style='color:red;'>❌ Error: " . $e->getMessage() . "</h2>";
}
?>