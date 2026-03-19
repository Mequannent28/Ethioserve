<?php
require_once 'includes/db.php';
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS rental_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        listing_id INT NOT NULL,
        reporter_id INT DEFAULT NULL,
        reason_type ENUM('fraud', 'incorrect_price', 'unavailable', 'duplicate', 'wrong_location', 'other') NOT NULL,
        description TEXT,
        status ENUM('pending', 'reviewed', 'resolved') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
        FOREIGN KEY (reporter_id) REFERENCES users(id) ON DELETE SET NULL
    )");
    echo "Table rental_reports created/verified.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
