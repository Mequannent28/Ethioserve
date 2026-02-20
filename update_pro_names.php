<?php
require_once 'includes/db.php';

function getOrCreateUser($pdo, $username, $fullName, $role)
{
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user) {
        $pdo->prepare("UPDATE users SET full_name = ?, role = ? WHERE id = ?")->execute([$fullName, $role, $user['id']]);
        return $user['id'];
    } else {
        $password = password_hash('pro123', PASSWORD_DEFAULT);
        $email = $username . "@ethioserve.com";
        $pdo->prepare("INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$username, $password, $fullName, $email, $role]);
        return $pdo->lastInsertId();
    }
}

function setupProvider($pdo, $userId, $bio, $exp, $degree, $cert, $loc, $cats)
{
    $stmt = $pdo->prepare("SELECT id FROM home_service_providers WHERE user_id = ?");
    $stmt->execute([$userId]);
    $pro = $stmt->fetch();

    if ($pro) {
        $pdo->prepare("UPDATE home_service_providers SET bio = ?, experience_years = ?, degree_type = ?, certification = ?, location = ?, availability_status = 'available' WHERE id = ?")
            ->execute([$bio, $exp, $degree, $cert, $loc, $pro['id']]);
        $proId = $pro['id'];
    } else {
        $pdo->prepare("INSERT INTO home_service_providers (user_id, bio, experience_years, degree_type, certification, location, availability_status) VALUES (?, ?, ?, ?, ?, ?, 'available')")
            ->execute([$userId, $bio, $exp, $degree, $cert, $loc]);
        $proId = $pdo->lastInsertId();
    }

    // Links cats
    $pdo->prepare("DELETE FROM provider_services WHERE provider_id = ?")->execute([$proId]);
    foreach ($cats as $catId) {
        $pdo->prepare("INSERT INTO provider_services (provider_id, category_id) VALUES (?, ?)")->execute([$proId, $catId]);
    }
}

try {
    // 1. Mequannent - Electrician (Cat 2)
    $u1 = getOrCreateUser($pdo, 'mequannent', 'Mequannent G.', 'home_pro');
    SetupProvider($pdo, $u1, 'Expert electrician with certification in industrial wiring and home safety systems.', 10, 'B.Sc. in Electrical Engineering', 'Certified Senior Electrician', 'Bole, Addis Ababa', [2, 6]);

    // 2. Chala - Plumber (Cat 1)
    $u2 = getOrCreateUser($pdo, 'chala', 'Chala K.', 'home_pro');
    setupProvider($pdo, $u2, 'Specialized in modern plumbing, leak detection, and sanitary installations.', 7, 'Diploma in Sanitary Engineering', 'Grade A Plumbing License', 'Arada, Addis Ababa', [1]);

    // 3. Bekele - Carpenter (Cat 3)
    $u3 = getOrCreateUser($pdo, 'bekele', 'Bekele T.', 'home_pro');
    setupProvider($pdo, $u3, 'Master carpenter for custom furniture, cabinet installations, and wood repairs.', 15, 'TVET Level 4 Carpentry', 'Master Artisan Certificate', 'Kolfe Keranio, Addis Ababa', [3]);

    // 4. Muluken - Appliance Repair (Cat 6)
    $u4 = getOrCreateUser($pdo, 'muluken', 'Muluken S.', 'home_pro');
    setupProvider($pdo, $u4, 'Professional technician for refrigerators, washing machines, and electronics.', 6, 'Electronics Technology Certificate', 'Certified Appliance Specialist', 'Yeka, Addis Ababa', [6, 2]);

    echo "Demo professionals updated successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
