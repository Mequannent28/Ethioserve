<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS video_calls (
        id INT AUTO_INCREMENT PRIMARY KEY,
        caller_id INT NOT NULL,
        receiver_id INT NOT NULL,
        provider_id INT NOT NULL,
        room_id VARCHAR(255) NOT NULL,
        status ENUM('pending', 'accepted', 'rejected', 'ended') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (caller_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;");

    echo "✅ Created video_calls table.<br>";
    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
