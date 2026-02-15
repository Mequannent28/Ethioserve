<?php
/**
 * Seed Ride, Feres, and Yango taxi companies with owner accounts and vehicles
 * Run this file once: http://localhost/ethioserve/seed_taxi_providers.php
 */

require_once 'includes/config.php';
require_once 'includes/db.php';

echo "<pre style='font-family:Courier;background:#1a1a2e;color:#eee;padding:30px;border-radius:20px;max-width:800px;margin:40px auto;'>";
echo "🚕 <strong style='color:#FFD600;'>SEEDING TAXI PROVIDERS: Ride, Feres & Yango</strong>\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

try {
    $hashed = password_hash('password', PASSWORD_DEFAULT);

    // ===============================
    // 1. RIDE (Ride Ethiopia)
    // ===============================
    echo "🔵 <strong>1. RIDE (Ride Ethiopia)</strong>\n";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['ride_ethiopia']);
    if (!$stmt->fetch()) {
        // Create owner account
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
        $stmt->execute(['ride_ethiopia', 'ride@ethioserve.com', $hashed, 'Ride Ethiopia', '+251911100100']);
        $ride_user_id = $pdo->lastInsertId();

        // Create taxi company
        $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, address, logo_url, rating, total_vehicles, license_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([
            $ride_user_id,
            'Ride',
            'Ethiopia\'s leading ride-hailing platform. Safe, reliable, and affordable rides across Addis Ababa. Available 24/7 with Economy, Premium, and Van options.',
            '+251911100100',
            'ride@ethioserve.com',
            'Bole Road, Addis Ababa',
            '',
            4.8,
            120,
            'TAXI-RIDE-2024'
        ]);
        $ride_company_id = $pdo->lastInsertId();

        // Add vehicles (drivers)
        $pdo->exec("INSERT INTO taxi_vehicles (company_id, driver_name, driver_phone, vehicle_type, plate_number, model, color, is_available) VALUES 
            ($ride_company_id, 'Yared Tadesse', '+251922100101', 'Sedan', 'AA-1-45678', 'Toyota Corolla', 'White', 1),
            ($ride_company_id, 'Biniam Hailu', '+251922100102', 'Sedan', 'AA-1-45679', 'Hyundai Elantra', 'Silver', 1),
            ($ride_company_id, 'Dawit Gebremedhin', '+251922100103', 'SUV', 'AA-1-45680', 'Toyota RAV4', 'Black', 1),
            ($ride_company_id, 'Getachew Mekonnen', '+251922100104', 'Sedan', 'AA-1-45681', 'Suzuki Dzire', 'White', 1),
            ($ride_company_id, 'Kibrom Asfaw', '+251922100105', 'Van', 'AA-1-45682', 'Toyota HiAce', 'Grey', 1),
            ($ride_company_id, 'Henok Tadesse', '+251922100106', 'SUV', 'AA-1-45683', 'Hyundai Tucson', 'Blue', 1),
            ($ride_company_id, 'Abel Wondwosen', '+251922100107', 'Sedan', 'AA-1-45684', 'Toyota Yaris', 'Red', 1),
            ($ride_company_id, 'Samuel Kebede', '+251922100108', 'Van', 'AA-1-45685', 'Mitsubishi L300', 'White', 1)
        ");

        echo "   ✅ Owner account: <strong style='color:#4CAF50;'>ride_ethiopia</strong> / password\n";
        echo "   ✅ Company: Ride (8 vehicles)\n";
        echo "   📧 Email: ride@ethioserve.com\n\n";
    } else {
        echo "   ⏩ Ride already exists, skipping...\n\n";
    }

    // ===============================
    // 2. FERES
    // ===============================
    echo "🟢 <strong>2. FERES</strong>\n";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['feres']);
    if (!$stmt->fetch()) {
        // Create owner account
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
        $stmt->execute(['feres', 'feres@ethioserve.com', $hashed, 'Feres Transport', '+251911200200']);
        $feres_user_id = $pdo->lastInsertId();

        // Create taxi company
        $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, address, logo_url, rating, total_vehicles, license_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([
            $feres_user_id,
            'Feres',
            'Ethiopia\'s most affordable ride service. Feres connects passengers with nearby drivers at budget-friendly prices. Available as Feres Mini, Feres Plus, and Feres XL.',
            '+251911200200',
            'feres@ethioserve.com',
            'Kazanchis, Addis Ababa',
            '',
            4.5,
            85,
            'TAXI-FERES-2024'
        ]);
        $feres_company_id = $pdo->lastInsertId();

        // Add vehicles
        $pdo->exec("INSERT INTO taxi_vehicles (company_id, driver_name, driver_phone, vehicle_type, plate_number, model, color, is_available) VALUES 
            ($feres_company_id, 'Teshome Bekele', '+251922200201', 'Sedan', 'AA-2-56781', 'Lifan 520', 'White', 1),
            ($feres_company_id, 'Mulugeta Alemayehu', '+251922200202', 'Sedan', 'AA-2-56782', 'Chery A3', 'Silver', 1),
            ($feres_company_id, 'Abebaw Yilma', '+251922200203', 'Sedan', 'AA-2-56783', 'Toyota Vitz', 'Blue', 1),
            ($feres_company_id, 'Frezer Amanuel', '+251922200204', 'SUV', 'AA-2-56784', 'Suzuki Vitara', 'Black', 1),
            ($feres_company_id, 'Girma Tesfaye', '+251922200205', 'Van', 'AA-2-56785', 'Toyota HiAce', 'White', 1),
            ($feres_company_id, 'Yonas Sisay', '+251922200206', 'Sedan', 'AA-2-56786', 'Hyundai Accent', 'Grey', 1),
            ($feres_company_id, 'Tekle Hailu', '+251922200207', 'Sedan', 'AA-2-56787', 'Suzuki Dzire', 'White', 1)
        ");

        echo "   ✅ Owner account: <strong style='color:#4CAF50;'>feres</strong> / password\n";
        echo "   ✅ Company: Feres (7 vehicles)\n";
        echo "   📧 Email: feres@ethioserve.com\n\n";
    } else {
        echo "   ⏩ Feres already exists, skipping...\n\n";
    }

    // ===============================
    // 3. YANGO
    // ===============================
    echo "🔴 <strong>3. YANGO</strong>\n";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['yango']);
    if (!$stmt->fetch()) {
        // Create owner account
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
        $stmt->execute(['yango', 'yango@ethioserve.com', $hashed, 'Yango Ethiopia', '+251911300300']);
        $yango_user_id = $pdo->lastInsertId();

        // Create taxi company
        $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, address, logo_url, rating, total_vehicles, license_number, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([
            $yango_user_id,
            'Yango',
            'International ride-hailing service now in Ethiopia! Yango offers competitive prices and a seamless booking experience. Start, Comfort, and XL rides available.',
            '+251911300300',
            'yango@ethioserve.com',
            'Meskel Square, Addis Ababa',
            '',
            4.6,
            95,
            'TAXI-YANGO-2024'
        ]);
        $yango_company_id = $pdo->lastInsertId();

        // Add vehicles
        $pdo->exec("INSERT INTO taxi_vehicles (company_id, driver_name, driver_phone, vehicle_type, plate_number, model, color, is_available) VALUES 
            ($yango_company_id, 'Mohammed Ali', '+251922300301', 'Sedan', 'AA-3-67891', 'Toyota Corolla', 'White', 1),
            ($yango_company_id, 'Bereket Desta', '+251922300302', 'Sedan', 'AA-3-67892', 'Hyundai Elantra', 'Grey', 1),
            ($yango_company_id, 'Kaleb Solomon', '+251922300303', 'SUV', 'AA-3-67893', 'Kia Sportage', 'Black', 1),
            ($yango_company_id, 'Natnael Worku', '+251922300304', 'Sedan', 'AA-3-67894', 'Suzuki Ciaz', 'Silver', 1),
            ($yango_company_id, 'Eyob Tesfai', '+251922300305', 'Van', 'AA-3-67895', 'Toyota HiAce', 'White', 1),
            ($yango_company_id, 'Mesfin Gudeta', '+251922300306', 'SUV', 'AA-3-67896', 'Toyota Fortuner', 'White', 1),
            ($yango_company_id, 'Daniel Fikru', '+251922300307', 'Sedan', 'AA-3-67897', 'Hyundai Accent', 'Blue', 1),
            ($yango_company_id, 'Amanuel Haile', '+251922300308', 'Sedan', 'AA-3-67898', 'Toyota Yaris', 'Red', 1),
            ($yango_company_id, 'Robel Mengistu', '+251922300309', 'Van', 'AA-3-67899', 'Mitsubishi L300', 'Grey', 1)
        ");

        echo "   ✅ Owner account: <strong style='color:#4CAF50;'>yango</strong> / password\n";
        echo "   ✅ Company: Yango (9 vehicles)\n";
        echo "   📧 Email: yango@ethioserve.com\n\n";
    } else {
        echo "   ⏩ Yango already exists, skipping...\n\n";
    }

    // ===============================
    // 4. SEED DEMO RIDES FOR ALL 3
    // ===============================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 <strong>4. SEEDING DEMO RIDES</strong>\n\n";

    // Find a customer
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE role = 'customer' LIMIT 1");
    $stmt->execute();
    $customer = $stmt->fetch();

    if ($customer) {
        // Get the 3 companies
        $companies = [];
        foreach (['Ride', 'Feres', 'Yango'] as $name) {
            $stmt = $pdo->prepare("SELECT id FROM taxi_companies WHERE company_name = ?");
            $stmt->execute([$name]);
            $co = $stmt->fetch();
            if ($co)
                $companies[$name] = $co['id'];
        }

        if (!empty($companies)) {
            $cust_id = $customer['id'];
            $cust_name = $customer['full_name'];

            // Check if demo rides exist already
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_rides WHERE ride_reference = 'RIDE-R001'");
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                $rides_data = [];

                // Ride demos
                if (isset($companies['Ride'])) {
                    $co_id = $companies['Ride'];
                    $rides_data[] = "('RIDE-R001', $cust_id, $co_id, 'Bole International Airport', 'Sheraton Addis', 320.00, 'completed', 'paid', 'cash', '$cust_name', '+251911000001', 'Yared Tadesse', 'AA-1-45678')";
                    $rides_data[] = "('RIDE-R002', $cust_id, $co_id, 'Meskel Square', 'Edna Mall, Bole', 180.00, 'completed', 'paid', 'cash', '$cust_name', '+251911000001', 'Biniam Hailu', 'AA-1-45679')";
                    $rides_data[] = "('RIDE-R003', $cust_id, $co_id, 'Megenagna', 'CMC', 220.00, 'accepted', 'pending', 'cash', '$cust_name', '+251911000001', 'Dawit Gebremedhin', 'AA-1-45680')";
                }

                // Feres demos
                if (isset($companies['Feres'])) {
                    $co_id = $companies['Feres'];
                    $rides_data[] = "('RIDE-F001', $cust_id, $co_id, 'Piazza', 'Mexico Square', 100.00, 'completed', 'paid', 'cash', '$cust_name', '+251911000001', 'Teshome Bekele', 'AA-2-56781')";
                    $rides_data[] = "('RIDE-F002', $cust_id, $co_id, 'Kazanchis', 'Bole Medhanialem', 150.00, 'in_progress', 'pending', 'cash', '$cust_name', '+251911000001', 'Mulugeta Alemayehu', 'AA-2-56782')";
                    $rides_data[] = "('RIDE-F003', $cust_id, $co_id, 'Sarbet', 'Jemo', 180.00, 'requested', 'pending', 'cash', '$cust_name', '+251911000001', '', '')";
                }

                // Yango demos
                if (isset($companies['Yango'])) {
                    $co_id = $companies['Yango'];
                    $rides_data[] = "('RIDE-Y001', $cust_id, $co_id, 'Hilton Hotel', 'Bole International Airport', 280.00, 'completed', 'paid', 'cash', '$cust_name', '+251911000001', 'Mohammed Ali', 'AA-3-67891')";
                    $rides_data[] = "('RIDE-Y002', $cust_id, $co_id, 'Arat Kilo', 'Megenagna', 160.00, 'completed', 'paid', 'cash', '$cust_name', '+251911000001', 'Bereket Desta', 'AA-3-67892')";
                    $rides_data[] = "('RIDE-Y003', $cust_id, $co_id, 'Gotera', 'Summit', 200.00, 'accepted', 'pending', 'cash', '$cust_name', '+251911000001', 'Kaleb Solomon', 'AA-3-67893')";
                }

                if (!empty($rides_data)) {
                    $sql = "INSERT INTO taxi_rides (ride_reference, customer_id, taxi_company_id, pickup_location, dropoff_location, fare, status, payment_status, payment_method, passenger_name, passenger_phone, driver_name, vehicle_plate) VALUES " . implode(",\n", $rides_data);
                    $pdo->exec($sql);
                    echo "   ✅ Seeded " . count($rides_data) . " demo rides across all providers\n\n";
                }
            } else {
                echo "   ⏩ Demo rides already exist\n\n";
            }
        }
    } else {
        echo "   ⚠️ No customer account found. Create a customer first.\n\n";
    }

    // ===============================
    // SUMMARY
    // ===============================
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🎉 <strong style='color:#4CAF50;'>ALL TAXI PROVIDERS SEEDED SUCCESSFULLY!</strong>\n\n";

    echo "📋 <strong style='color:#FFD600;'>LOGIN CREDENTIALS:</strong>\n\n";

    echo "   🔵 <strong>Ride</strong>\n";
    echo "      Username: <strong style='color:#4FC3F7;'>ride_ethiopia</strong>\n";
    echo "      Password: <strong style='color:#4FC3F7;'>password</strong>\n";
    echo "      Dashboard: /ethioserve/taxi/dashboard.php\n\n";

    echo "   🟢 <strong>Feres</strong>\n";
    echo "      Username: <strong style='color:#4FC3F7;'>feres</strong>\n";
    echo "      Password: <strong style='color:#4FC3F7;'>password</strong>\n";
    echo "      Dashboard: /ethioserve/taxi/dashboard.php\n\n";

    echo "   🔴 <strong>Yango</strong>\n";
    echo "      Username: <strong style='color:#4FC3F7;'>yango</strong>\n";
    echo "      Password: <strong style='color:#4FC3F7;'>password</strong>\n";
    echo "      Dashboard: /ethioserve/taxi/dashboard.php\n\n";

    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📌 <strong>HOW IT WORKS:</strong>\n";
    echo "   1. Customer goes to /customer/taxi.php\n";
    echo "   2. Selects Ride, Feres, or Yango as provider\n";
    echo "   3. Enters destination → Books a ride\n";
    echo "   4. The owner logs in → Sees the booking\n";
    echo "   5. Owner can Accept → Start → Complete the ride\n\n";

} catch (Exception $e) {
    echo "\n❌ <strong style='color:#ef5350;'>Error:</strong> " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<div style='text-align:center;margin:20px;'>";
echo "<a href='login.php' style='display:inline-block;padding:14px 40px;background:#1B5E20;color:white;text-decoration:none;border-radius:30px;font-weight:bold;font-size:1.1rem;margin:5px;'>🔑 Login</a>";
echo "<a href='customer/taxi.php' style='display:inline-block;padding:14px 40px;background:#FFD600;color:#212121;text-decoration:none;border-radius:30px;font-weight:bold;font-size:1.1rem;margin:5px;'>🚕 Book a Ride</a>";
echo "</div>";
?>