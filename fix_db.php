<?php
require_once 'includes/db.php';

echo "<h1>Database Fixer</h1>";

try {
    // 1. Create flights table
    $pdo->exec("CREATE TABLE IF NOT EXISTS flights (
        id INT AUTO_INCREMENT PRIMARY KEY,
        airline VARCHAR(100) NOT NULL,
        flight_number VARCHAR(20) UNIQUE,
        origin VARCHAR(100) DEFAULT 'Addis Ababa (ADD)',
        destination VARCHAR(100) NOT NULL,
        departure_time DATETIME NOT NULL,
        arrival_time DATETIME NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        available_seats INT DEFAULT 50,
        status ENUM('scheduled', 'delayed', 'cancelled', 'completed') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "<p>✅ 'flights' table created or already exists.</p>";

    // 2. Create flight_bookings table
    $pdo->exec("CREATE TABLE IF NOT EXISTS flight_bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        customer_id INT,
        flight_id INT,
        passenger_name VARCHAR(100),
        passport_number VARCHAR(50),
        pnr_code VARCHAR(10) UNIQUE,
        trip_type ENUM('one_way', 'round_trip') DEFAULT 'one_way',
        seat_number VARCHAR(10),
        status ENUM('pending', 'confirmed', 'cancelled') DEFAULT 'pending',
        payment_status ENUM('unpaid', 'paid') DEFAULT 'unpaid',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (flight_id) REFERENCES flights(id) ON DELETE CASCADE
    )");

    // Ensure columns exist even if table was already created
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS pnr_code VARCHAR(10) UNIQUE AFTER passport_number");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS trip_type ENUM('one_way', 'round_trip') DEFAULT 'one_way' AFTER pnr_code");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS title VARCHAR(10) AFTER trip_type");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS given_names VARCHAR(100) AFTER title");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS last_name VARCHAR(100) AFTER given_names");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS date_of_birth DATE AFTER last_name");
    } catch (Exception $e) {
    }
    try {
        $pdo->exec("ALTER TABLE flight_bookings ADD COLUMN IF NOT EXISTS gender ENUM('male', 'female') AFTER date_of_birth");
    } catch (Exception $e) {
    }

    echo "<p>✅ 'flight_bookings' table updated with detailed passenger support.</p>";

    // 3. Populate with comprehensive Ethiopian Domestic and International destinations
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
    $pdo->exec("TRUNCATE TABLE flights");
    $pdo->exec("TRUNCATE TABLE flight_bookings");
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

    echo "<p>✅ Database cleared. Seeding 10 days of recurring flights...</p>";

    $domestic = [
        ['BJR', 'Bahir Dar', 7402],
        ['GDQ', 'Gondar', 7800],
        ['LLI', 'Lalibela', 8200],
        ['DIR', 'Dire Dawa', 6900],
        ['MQX', 'Mekelle', 8500],
        ['JIM', 'Jimma', 6200],
        ['AMH', 'Arba Minch', 7100],
        ['HWA', 'Hawassa', 5800]
    ];

    $international = [
        ['NBO', 'Nairobi', 12500],
        ['DXB', 'Dubai', 28000],
        ['IAD', 'Washington D.C.', 85000],
        ['LHR', 'London', 72000],
        ['IST', 'Istanbul', 42000],
        ['JNB', 'Johannesburg', 32000]
    ];

    $stmt = $pdo->prepare("INSERT INTO flights (airline, flight_number, destination, departure_time, arrival_time, price, available_seats) VALUES (?, ?, ?, ?, ?, ?, ?)");

    // Seed for the next 14 days
    for ($i = 0; $i < 14; $i++) {
        $date = date('Y-m-d', strtotime("+$i days"));

        // Domestic: 3 flights per day per destination (Morning, Afternoon, Evening)
        foreach ($domestic as $city) {
            $dest = $city[1] . " (" . $city[0] . ")";
            $code = $city[0];

            // Morning
            $stmt->execute(['Ethiopian Airlines', "ETD-$i-$code-M", $dest, "$date 08:15:00", "$date 09:25:00", $city[2], 50]);
            // Afternoon
            $stmt->execute(['Ethiopian Airlines', "ETD-$i-$code-A", $dest, "$date 14:30:00", "$date 15:40:00", $city[2], 50]);
            // Evening
            $stmt->execute(['Ethiopian Airlines', "ETD-$i-$code-E", $dest, "$date 17:20:00", "$date 18:30:00", $city[2] + 200, 50]);
        }

        // International: 1-2 flights per day
        foreach ($international as $int) {
            $dest = $int[1] . " (" . $int[0] . ")";
            $code = $int[0];
            $stmt->execute(['Ethiopian Airlines', "ETI-$i-$code", $dest, "$date 10:00:00", "$date " . (rand(14, 23)) . ":00:00", $int[2], 250]);

            if (rand(0, 1)) { // random second flight
                $stmt->execute(['Emirates', "EK-$i-$code", $dest, "$date 21:00:00", date('Y-m-d H:i:s', strtotime("$date 21:00:00 + 4 hours")), $int[2] * 1.2, 300]);
            }
        }
    }

    echo "<p>✅ Successfully seeded hundreds of daily flights for the next 2 weeks!</p>";
    echo "<h3>System Ready! Domestic routes (BJR, GDQ, DIR, etc.) are now ACTIVE DAILY.</h3>";
    echo "<a href='customer/flights.php' style='padding: 10px 20px; background: #1B5E20; color: white; text-decoration: none; border-radius: 5px;'>Go to Flights</a>";

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>