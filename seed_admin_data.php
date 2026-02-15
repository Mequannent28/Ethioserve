<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<pre>";
echo "Seeding Admin Dashboard Data...\n";

try {
    $hashed = password_hash('password', PASSWORD_DEFAULT);

    // 1. Add Taxi Owners
    $taxi_providers = [
        ['ride_ethiopia', 'ride@ethioserve.com', 'Ride Ethiopia', '+251115170001'],
        ['feres', 'feres@ethioserve.com', 'Feres Transport', '+251115170002'],
        ['yango', 'yango@ethioserve.com', 'Yango Ethiopia', '+251115170003']
    ];

    foreach ($taxi_providers as $provider) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$provider[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
            $stmt->execute([$provider[0], $provider[1], $hashed, $provider[2], $provider[3]]);
            $user_id = $pdo->lastInsertId();

            // Create Company
            $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, status) VALUES (?, ?, ?, ?, ?, 'approved')");
            $stmt->execute([$user_id, $provider[2], "Leading taxi service in Ethiopia.", $provider[3], $provider[1]]);
            echo "Added Taxi Owner: {$provider[0]}\n";
        }
    }

    // 2. Add some Bus Data (if not exists)
    $transport_owners = [
        ['golden_bus', 'golden@ethioserve.com', 'Golden Bus'],
        ['walya_bus', 'walya@ethioserve.com', 'Walya Bus']
    ];

    foreach ($transport_owners as $owner) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$owner[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'transport')");
            $stmt->execute([$owner[0], $owner[1], $hashed, $owner[2]]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO transport_companies (user_id, company_name, phone, status) VALUES (?, ?, ?, 'approved')");
            $stmt->execute([$user_id, $owner[2], '+251116111111']);
            echo "Added Transport Owner: {$owner[0]}\n";
        }
    }

    // 3. Add some Sample Orders for Admin Panel
    // Get a customer and a hotel
    $customer_id = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetchColumn();
    $hotel_id = $pdo->query("SELECT id FROM hotels LIMIT 1")->fetchColumn();

    if ($customer_id && $hotel_id) {
        for ($i = 0; $i < 5; $i++) {
            $stmt = $pdo->prepare("INSERT INTO orders (customer_id, hotel_id, total_amount, status, payment_status) VALUES (?, ?, ?, 'pending', 'pending')");
            $stmt->execute([$customer_id, $hotel_id, 500 + ($i * 100)]);
        }
        echo "Added 5 Sample Orders.\n";
    }

    // 4. Update Hotel Phone/Email to fix the 404/Not Found in payment
    $pdo->exec("UPDATE hotels SET phone = '+251115170000', email = 'info@ethioserve.com' WHERE phone IS NULL OR phone = ''");
    echo "Fixed missing hotel phone numbers.\n";

    echo "Seeding completed successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
