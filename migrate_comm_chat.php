<?php
require_once 'includes/db.php';

try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS comm_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        receiver_id INT NOT NULL,
        item_id INT DEFAULT NULL,
        item_type ENUM('marketplace', 'lostfound') DEFAULT 'marketplace',
        message TEXT NOT NULL,
        is_read TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (sender_id) REFERENCES users(id),
        FOREIGN KEY (receiver_id) REFERENCES users(id)
    ) ENGINE=InnoDB;");
    echo "Community Chat (comm_messages) table created successfully.";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage();
}
