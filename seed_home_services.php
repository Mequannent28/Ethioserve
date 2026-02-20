<?php
require_once 'includes/db.php';

function addColumnIfNotExist($pdo, $table, $column, $definition)
{
    try {
        $pdo->query("SELECT $column FROM $table LIMIT 1");
    } catch (Exception $e) {
        $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
        echo "Column $column added to $table.\n";
    }
}

try {
    addColumnIfNotExist($pdo, 'home_service_providers', 'degree_type', 'VARCHAR(255) AFTER experience_years');
    addColumnIfNotExist($pdo, 'home_service_providers', 'certification', 'VARCHAR(255) AFTER degree_type');
    addColumnIfNotExist($pdo, 'home_service_providers', 'profile_image', 'VARCHAR(255) AFTER id');
    addColumnIfNotExist($pdo, 'home_service_bookings', 'provider_id', 'INT NULL AFTER option_id');

    // Update existing provider (System Admin)
    $stmt = $pdo->prepare("UPDATE home_service_providers SET degree_type = ?, certification = ?, bio = ? WHERE id = 1");
    $stmt->execute([
        'B.Sc. in Electrical Engineering',
        'Certified Master Electrician',
        'Expert professional with over 5 years of experience in electrical systems, plumbing, and home repairs. Dedicated to providing high-quality service and customer satisfaction.'
    ]);

    // Check if user_id 2 exists
    $stmt = $pdo->query("SELECT id FROM users WHERE id = 2");
    $user2 = $stmt->fetch();
    if ($user2) {
        $stmt = $pdo->prepare("SELECT id FROM home_service_providers WHERE user_id = 2");
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO home_service_providers (user_id, bio, experience_years, degree_type, certification, location, service_areas, availability_status) 
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
            $pdo->exec("INSERT IGNORE INTO provider_services (provider_id, category_id) VALUES ($provider_id, 1)"); // Plumber
            $pdo->exec("INSERT IGNORE INTO provider_services (provider_id, category_id) VALUES ($provider_id, 2)"); // Electrical
        }
    }

    echo "Migration and data seeding completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
