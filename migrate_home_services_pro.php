<?php
require_once 'includes/db.php';

try {
    // Add fields to home_service_providers
    $pdo->exec("ALTER TABLE home_service_providers ADD COLUMN degree_type VARCHAR(255) AFTER experience_years");
    $pdo->exec("ALTER TABLE home_service_providers ADD COLUMN certification VARCHAR(255) AFTER degree_type");
    $pdo->exec("ALTER TABLE home_service_providers ADD COLUMN profile_image VARCHAR(255) AFTER id");

    // Add provider_id to home_service_bookings
    $pdo->exec("ALTER TABLE home_service_bookings ADD COLUMN provider_id INT NULL AFTER option_id");

    echo "Migration successful!\n";

    // Update existing provider (System Admin) with some dummy data
    $stmt = $pdo->prepare("UPDATE home_service_providers SET degree_type = ?, certification = ? WHERE id = 1");
    $stmt->execute(['B.Sc. in Electrical Engineering', 'Certified Master Electrician']);

    // Add another dummy provider for variety (if user_id 2 exists)
    $stmt = $pdo->query("SELECT id FROM users WHERE id = 2");
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare("INSERT IGNORE INTO home_service_providers (user_id, bio, experience_years, degree_type, certification, location, service_areas, availability_status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            2,
            'Expert plumber specialized in modern bathroom fittings and leak repairs.',
            8,
            'Advanced Diploma in Civil Engineering',
            'National Plumbing Certificate Tier 1',
            'Arada, Addis Ababa',
            '["Piazza", "Arat Kilo", "Meskel Square"]',
            'available'
        ]);
        $provider_id = $pdo->lastInsertId();
        if ($provider_id) {
            $pdo->exec("INSERT IGNORE INTO provider_services (provider_id, category_id) VALUES ($provider_id, 1)"); // Plumber
        }
    }

} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
