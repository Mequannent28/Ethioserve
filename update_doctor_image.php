<?php
require_once 'includes/db.php';

try {
    // Update Dr. Yalemwork's image to a graduation/professional photo
    $stmt = $pdo->prepare("UPDATE health_providers SET image_url = ? WHERE name LIKE '%Yalemwork%'");
    $stmt->execute(['https://images.unsplash.com/photo-1559839734-2b71ef197ec2?w=800']); // Keeping it professional and high quality

    echo "Dr. Yalemwork's profile has been updated with the professional image.";
} catch (Exception $e) {
    echo "Update failed: " . $e->getMessage();
}
