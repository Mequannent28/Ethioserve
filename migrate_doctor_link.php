<?php
require_once 'includes/db.php';

try {
    // Add user_id column to health_providers if it doesn't exist
    $pdo->exec("ALTER TABLE health_providers ADD COLUMN IF NOT EXISTS user_id INT NULL AFTER id");

    // Check if constraint exists, if not add it (simple way)
    try {
        $pdo->exec("ALTER TABLE health_providers ADD CONSTRAINT fk_hp_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL");
    } catch (Exception $e) {
        // Likely already exists
    }

    // Link Dr. Dawit (ID 7 in health_providers) to 'dr_dawit' user
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'dr_dawit'");
    $stmt->execute();
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare("UPDATE health_providers SET user_id = ? WHERE id = 7")->execute([$user['id']]);
        echo "Linked Dr. Dawit (Provider 7) to User ID " . $user['id'] . "<br>";
    }

    // Also link Dr. Abebe (ID 1) if we want
    // But we don't have a demo user for him yet. Let's create one.
    $demo_abebe = [
        'username' => 'dr_abebe',
        'full_name' => 'Dr. Abebe Molla',
        'email' => 'abebe@demo.com',
        'password' => password_hash('password', PASSWORD_DEFAULT),
        'role' => 'doctor'
    ];

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$demo_abebe['username']]);
    $existing = $stmt->fetch();

    if (!$existing) {
        $pdo->prepare("INSERT INTO users (username, full_name, email, password, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$demo_abebe['username'], $demo_abebe['full_name'], $demo_abebe['email'], $demo_abebe['password'], $demo_abebe['role']]);
        $new_user_id = $pdo->lastInsertId();
        $pdo->prepare("UPDATE health_providers SET user_id = ? WHERE id = 1")->execute([$new_user_id]);
        echo "Created user 'dr_abebe' and linked to Provider 1<br>";
    } else {
        $pdo->prepare("UPDATE health_providers SET user_id = ? WHERE id = 1")->execute([$existing['id']]);
        echo "Linked 'dr_abebe' to Provider 1<br>";
    }

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
