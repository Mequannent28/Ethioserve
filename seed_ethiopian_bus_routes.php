<?php
require_once 'includes/db.php';
require_once 'includes/functions.php';

echo "<h2>🚌 Seeding Ethiopian Inter-City Bus Routes & Schedules</h2>\n";
echo "<pre>\n";

try {
    // Define major Ethiopian cities with distances from Addis Ababa (in km) and estimated hours
    $cities = [
        // Amhara Region
        ['name' => 'Bahir Dar', 'distance' => 565, 'hours' => 8],
        ['name' => 'Gondar', 'distance' => 727, 'hours' => 10],
        ['name' => 'Dessie', 'distance' => 401, 'hours' => 6],
        ['name' => 'Debre Birhan', 'distance' => 130, 'hours' => 2],
        ['name' => 'Debre Markos', 'distance' => 300, 'hours' => 5],
        ['name' => 'Lalibela', 'distance' => 642, 'hours' => 11],
        ['name' => 'Kombolcha', 'distance' => 376, 'hours' => 6],
        ['name' => 'Debre Tabor', 'distance' => 623, 'hours' => 9],

        // Oromia Region
        ['name' => 'Adama (Nazret)', 'distance' => 99, 'hours' => 1.5],
        ['name' => 'Jimma', 'distance' => 346, 'hours' => 5],
        ['name' => 'Nekemte', 'distance' => 331, 'hours' => 5],
        ['name' => 'Shashamane', 'distance' => 250, 'hours' => 4],
        ['name' => 'Ziway', 'distance' => 163, 'hours' => 2.5],

        // Southern Nations
        ['name' => 'Hawassa', 'distance' => 275, 'hours' => 4],
        ['name' => 'Awasa (Hawassa)', 'distance' => 275, 'hours' => 4],
        ['name' => 'Arba Minch', 'distance' => 505, 'hours' => 7],
        ['name' => 'Wolaita Sodo', 'distance' => 327, 'hours' => 5],
        ['name' => 'Dilla', 'distance' => 360, 'hours' => 5.5],
        ['name' => 'Hosaena', 'distance' => 232, 'hours' => 3.5],
        ['name' => 'Wolkite', 'distance' => 157, 'hours' => 2.5],

        // Dire Dawa & Harari
        ['name' => 'Dire Dawa', 'distance' => 515, 'hours' => 8],
        ['name' => 'Harar', 'distance' => 526, 'hours' => 8.5],

        // Tigray Region
        ['name' => 'Mekelle', 'distance' => 783, 'hours' => 11],
        ['name' => 'Axum', 'distance' => 1023, 'hours' => 14],
        ['name' => 'Adwa', 'distance' => 1001, 'hours' => 13.5],

        // Other Regions
        ['name' => 'Jijiga', 'distance' => 628, 'hours' => 10],
        ['name' => 'Gambela', 'distance' => 768, 'hours' => 12],
        ['name' => 'Semera', 'distance' => 602, 'hours' => 9],
        ['name' => 'Asosa', 'distance' => 687, 'hours' => 11],
    ];

    $pdo->beginTransaction();

    echo "📍 Creating routes from Addis Ababa to all cities and reverse routes...\n\n";

    // Create routes: Addis Ababa → City AND City → Addis Ababa
    $route_count = 0;
    foreach ($cities as $city) {
        // Route: Addis Ababa → City
        $stmt = $pdo->prepare("
            INSERT INTO routes (origin, destination, distance_km, estimated_hours)
            VALUES (?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE distance_km = VALUES(distance_km), estimated_hours = VALUES(estimated_hours)
        ");
        $stmt->execute(['Addis Ababa', $city['name'], $city['distance'], $city['hours']]);
        $route_count++;
        echo "✅ Route created: Addis Ababa → {$city['name']} ({$city['distance']} km, ~{$city['hours']} hrs)\n";

        // Reverse Route: City → Addis Ababa
        $stmt->execute([$city['name'], 'Addis Ababa', $city['distance'], $city['hours']]);
        $route_count++;
        echo "✅ Route created: {$city['name']} → Addis Ababa ({$city['distance']} km, ~{$city['hours']} hrs)\n";
    }

    echo "\n📊 Total Routes Created: $route_count\n\n";

    // Get all bus companies
    $companies = $pdo->query("SELECT id, company_name FROM transport_companies WHERE status = 'approved'")->fetchAll();

    if (empty($companies)) {
        echo "⚠️  No approved transport companies found. Please seed companies first.\n";
        $pdo->rollBack();
        exit;
    }

    echo "🚍 Creating schedules for routes...\n\n";

    // Get all routes
    $routes = $pdo->query("SELECT * FROM routes")->fetchAll();

    // Time slots for departures (morning, afternoon, evening, night)
    $time_slots = [
        '06:00:00',
        '07:30:00',
        '09:00:00', // Morning
        '12:00:00',
        '14:00:00',
        '15:30:00', // Afternoon
        '18:00:00',
        '20:00:00',
        '22:00:00'  // Evening/Night
    ];

    // Pricing based on distance (base price + per km)
    function calculatePrice($distance_km)
    {
        $base_price = 100;
        $price_per_km = 3.5;
        return round($base_price + ($distance_km * $price_per_km), -1); // Round to nearest 10
    }

    $schedule_count = 0;
    foreach ($routes as $route) {
        // Get buses for random companies
        $num_schedules = rand(2, 4); // 2-4 schedules per route

        for ($i = 0; $i < $num_schedules; $i++) {
            // Pick a random company
            $company = $companies[array_rand($companies)];

            // Get a random bus from this company
            $bus = $pdo->prepare("
                SELECT * FROM buses 
                WHERE company_id = ? AND is_active = TRUE 
                ORDER BY RAND() LIMIT 1
            ");
            $bus->execute([$company['id']]);
            $bus_data = $bus->fetch();

            if (!$bus_data)
                continue;

            // Pick a random departure time
            $departure_time = $time_slots[array_rand($time_slots)];

            // Calculate arrival time
            $departure = new DateTime($departure_time);
            $arrival = clone $departure;
            $hours = floor($route['estimated_hours']);
            $minutes = ($route['estimated_hours'] - $hours) * 60;
            $arrival->add(new DateInterval("PT{$hours}H" . round($minutes) . "M"));
            $arrival_time = $arrival->format('H:i:s');

            // Calculate price
            $price = calculatePrice($route['distance_km']);

            // Insert schedule
            $stmt = $pdo->prepare("
                INSERT INTO schedules (route_id, bus_id, departure_time, arrival_time, price, is_active)
                VALUES (?, ?, ?, ?, ?, TRUE)
                ON DUPLICATE KEY UPDATE price = VALUES(price)
            ");
            $stmt->execute([
                $route['id'],
                $bus_data['id'],
                $departure_time,
                $arrival_time,
                $price
            ]);

            $schedule_count++;
        }

        echo "✅ Created schedules for: {$route['origin']} → {$route['destination']}\n";
    }

    $pdo->commit();

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✨ SEEDING COMPLETED SUCCESSFULLY!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📍 Routes Created: $route_count\n";
    echo "🚍 Schedules Created: $schedule_count\n";
    echo "🏢 Transport Companies: " . count($companies) . "\n";
    echo "\n";
    echo "🌍 Coverage: Routes from Addis Ababa to " . count($cities) . " major Ethiopian cities (and reverse)\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='customer/buses.php'>🚌 Go to Bus Booking Page</a></p>\n";
?>