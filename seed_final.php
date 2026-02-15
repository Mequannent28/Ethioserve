<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<pre>";
echo "🚀 Starting Master Seeder for EthioServe...\n";

try {
    $hashed = password_hash('password', PASSWORD_DEFAULT);

    // 1. ADD CUSTOMERS
    $customers = [
        ['cust1', 'cust1@test.com', 'Abebe Kabede'],
        ['cust2', 'cust2@test.com', 'Tadesse Lemma'],
        ['cust3', 'cust3@test.com', 'Mulugeta Tesfaye'],
        ['cust4', 'cust4@test.com', 'Sara Hagos'],
        ['cust5', 'cust5@test.com', 'Daniel Girma'],
        ['cust6', 'cust6@test.com', 'Lidya Solomon']
    ];

    foreach ($customers as $c) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$c[0], $c[1]]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'customer')");
                $stmt->execute([$c[0], $c[1], $hashed, $c[2]]);
                echo "✅ Added Customer: {$c[2]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Skip Customer {$c[2]}: " . $e->getMessage() . "\n";
        }
    }

    // 2. ADD HOTELS & RESTAURANTS
    $hotels_data = [
        ['Haile Resort', 'Luxury resort by the lake.', 'Hawassa', 'International', '24/7', 4.8, 1000, '45 min', '+251462200000', 'haile@hailehotels.com'],
        ['Kuriftu Resort', 'Premium spa and resort.', 'Bishoftu', 'Traditional & European', '06:00-22:00', 4.9, 1500, '60 min', '+251114331000', 'info@kurifturesort.com'],
        ['Skylight Hotel', 'Modern luxury near airport.', 'Bole, Addis Ababa', 'Chinese, Ethio, International', '24/7', 4.7, 2000, '30 min', '+251116618060', 'info@ethiopianskylighthotel.com'],
        ['Elilly International', 'Centrally located luxury.', 'Kazanchis, Addis Ababa', 'Buffet', '07:00-23:00', 4.5, 800, '40 min', '+251115587777', 'info@elillyhotel.com'],
        ['Jupiter International', 'Business hotel in Kazanchis.', 'Kazanchis', 'Grill & Bar', '24/7', 4.3, 500, '35 min', '+251115527333', 'info@jupiterhotel.com'],
        ['Golden Tulip', 'International brand in Bole.', 'Bole', 'Fine Dining', '24/7', 4.6, 1200, '25 min', '+251116170740', 'info@goldentuliptana.com']
    ];

    foreach ($hotels_data as $h) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM hotels WHERE name = ?");
            $stmt->execute([$h[0]]);
            if (!$stmt->fetch()) {
                $username = strtolower(str_replace(' ', '_', $h[0])) . "_owner";
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $h[9]]);
                $user = $stmt->fetch();

                if (!$user) {
                    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES (?, ?, ?, ?, 'hotel')");
                    $stmt->execute([$username, $h[9], $hashed, $h[0] . " Admin"]);
                    $user_id = $pdo->lastInsertId();
                } else {
                    $user_id = $user['id'];
                }

                $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, description, location, cuisine_type, opening_hours, rating, min_order, delivery_time, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
                $stmt->execute([$user_id, $h[0], $h[1], $h[2], $h[3], $h[4], $h[5], $h[6], $h[7], $h[8], $h[9]]);
                echo "✅ Added Hotel/Restaurant: {$h[0]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Skip Hotel {$h[0]}: " . $e->getMessage() . "\n";
        }
    }

    // 3. ADD TAXI COMPANIES (6 items)
    $taxi_data = [
        ['Ride Ethiopia', 'ride@ethioserve.com', '+2518291'],
        ['Feres Transport', 'feres@ethioserve.com', '+2516090'],
        ['Yango Ethiopia', 'yango@ethioserve.com', '+251115170003'],
        ['Safe Ride', 'safe@ethioserve.com', '+2518210'],
        ['Little Ride', 'little@ethioserve.com', '+2516000'],
        ['ZayRide', 'zay@ethioserve.com', '+2518199']
    ];

    foreach ($taxi_data as $t) {
        try {
            $username = strtolower(str_replace(' ', '_', $t[0]));
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $t[1]]);
            $user = $stmt->fetch();

            if (!$user) {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
                $stmt->execute([$username, $t[1], $hashed, $t[0], $t[2]]);
                $user_id = $pdo->lastInsertId();
            } else {
                $user_id = $user['id'];
            }

            $stmt = $pdo->prepare("SELECT id FROM taxi_companies WHERE company_name = ?");
            $stmt->execute([$t[0]]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, status) VALUES (?, ?, ?, ?, ?, 'approved')");
                $stmt->execute([$user_id, $t[0], "Taxi service provider", $t[2], $t[1]]);
                echo "✅ Added Taxi Company: {$t[0]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Skip Taxi {$t[0]}: " . $e->getMessage() . "\n";
        }
    }

    // 4. ADD LISTINGS (Houses and Cars - 6 each)
    $listings_data = [
        ['house_rent', 'Luxury Villa in Bole', '4 BR Villa with private pool.', 55000, 'Bole', 'Garden, Pool, 24/7 Security'],
        ['house_rent', 'Modern Apt CMC', '2 BR Furnished Apartment.', 30000, 'CMC', 'Elevator, Parking, WiFi'],
        ['house_rent', 'Cosy Studio Piassa', 'Studio for young professionals.', 15000, 'Piassa', 'Near Metro, Safe Area'],
        ['house_rent', 'Big Mansion Ayat', '6 BR with large compound.', 85000, 'Ayat', 'Compound, Guard House'],
        ['house_rent', 'Office Space Kazanchis', '150 SQM premium office.', 45000, 'Kazanchis', 'AC, Power Backup'],
        ['house_rent', 'G+1 Home Lebu', '3 BR family home.', 40000, 'Lebu', 'Quiet Area'],

        ['car_rent', 'Toyota Land Cruiser V8', 'Full options, perfect for field trips.', 5000, 'Addis Ababa', 'GPS, Leather, 4WD'],
        ['car_rent', 'Toyota Corolla 2023', 'City car, fuel efficient.', 1800, 'Addis Ababa', 'Automatic, AC'],
        ['car_rent', 'Hyundai Santa Fe', '7 seater SUV.', 2500, 'Addis Ababa', 'Spacious, Bluetooth'],
        ['car_rent', 'Mercedes E-Class', 'Luxury for weddings/VIP.', 6000, 'Addis Ababa', 'Chauffeur, Black'],
        ['car_rent', 'Nissan Patrol', 'Off road beast.', 4500, 'Addis Ababa', 'Tough, 4WD'],
        ['car_rent', 'Suzuki Dzire', 'Budget city transport.', 1200, 'Addis Ababa', 'Manual, Efficiency']
    ];

    $broker_id = $pdo->query("SELECT id FROM users WHERE role = 'broker' LIMIT 1")->fetchColumn();
    if (!$broker_id) {
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, role) VALUES ('broker_test', 'broker@test.com', ?, 'Test Broker', 'broker')");
        $stmt->execute([$hashed]);
        $broker_id = $pdo->lastInsertId();
    }

    foreach ($listings_data as $l) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM listings WHERE title = ?");
            $stmt->execute([$l[1]]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO listings (user_id, type, title, description, price, location, features, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'available')");
                $stmt->execute([$broker_id, $l[0], $l[1], $l[2], $l[3], $l[4], $l[5]]);
                echo "✅ Added Listing: {$l[1]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Skip Listing {$l[1]}: " . $e->getMessage() . "\n";
        }
    }

    // 5. ADD FLIGHTS (6 items)
    $flights = [
        ['Ethiopian Airlines', 'ET302', 'Nairobi (NBO)', 8500],
        ['Ethiopian Airlines', 'ET500', 'Washington (IAD)', 45000],
        ['Emirates', 'EK723', 'Dubai (DXB)', 22000],
        ['Lufthansa', 'LH591', 'Frankfurt (FRA)', 35000],
        ['Turkish Airlines', 'TK606', 'Istanbul (IST)', 28000],
        ['Qatar Airways', 'QR1428', 'Doha (DOH)', 25000]
    ];

    foreach ($flights as $f) {
        try {
            $stmt = $pdo->prepare("SELECT id FROM flights WHERE flight_number = ?");
            $stmt->execute([$f[1]]);
            if (!$stmt->fetch()) {
                $stmt = $pdo->prepare("INSERT INTO flights (airline, flight_number, destination, departure_time, arrival_time, price) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 2 DAY), DATE_ADD(NOW(), INTERVAL '2 3' DAY_HOUR), ?)");
                $stmt->execute([$f[0], $f[1], $f[2], $f[3]]);
                echo "✅ Added Flight: {$f[1]} to {$f[2]}\n";
            }
        } catch (Exception $e) {
            echo "⚠️ Skip Flight {$f[1]}: " . $e->getMessage() . "\n";
        }
    }

    // 6. RUN EDUCATION SEEDER Logic implicitly (Commented out due to path issues in CLI, run manually)
    /*
    echo "📚 Running Education Resource Seeder...\n";
    $_GET['force'] = 1; // Simulate force re-seed
    require_once 'admin/seed_education.php';
    echo "✅ Education resources seeded.\n";
    */

    echo "\n🎉 MASTER SEEDING COMPLETED SUCCESSFULLY!\n";
    echo "Note: Please run admin/seed_education.php manually to populate education resources.\n";
} catch (Exception $e) {
    echo "❌ Error during seeding: " . $e->getMessage() . "\n";
}
echo "</pre>";
