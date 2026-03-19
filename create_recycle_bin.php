<?php
require_once 'includes/db.php';
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS recycle_bin (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            actor_type VARCHAR(50) NOT NULL,
            original_table VARCHAR(100) NOT NULL,
            original_id INT NOT NULL,
            data_json LONGTEXT NOT NULL,
            deleted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "Recycle bin table created.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
