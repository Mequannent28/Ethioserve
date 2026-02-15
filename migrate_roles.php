<?php
/**
 * Migration: Add Restaurant and Taxi roles
 * Creates restaurants table, taxi_companies table
 * Adds demo accounts for restaurant owners and taxi operators
 */

require_once 'includes/db.php';

echo "<h2>🔄 EthioServe — Role Migration</h2>";
echo "<pre style='background:#f5f5f5;padding:20px;border-radius:10px;font-family:monospace;'>";

try {
    // Step 1: Alter users role ENUM to include 'restaurant' and 'taxi'
    echo "➡️ Step 1: Updating users.role ENUM...\n";
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin', 'hotel', 'broker', 'transport', 'customer', 'restaurant', 'taxi') DEFAULT 'customer'");
    echo "   ✅ users.role ENUM updated\n\n";

    // Step 2: Create restaurants table
    echo "➡️ Step 2: Creating restaurants table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurants (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            address VARCHAR(255),
            cuisine_type VARCHAR(100),
            opening_hours VARCHAR(100),
            phone VARCHAR(20),
            email VARCHAR(100),
            rating DECIMAL(2,1) DEFAULT 0.0,
            min_order DECIMAL(10,2) DEFAULT 0.0,
            delivery_time VARCHAR(50),
            image_url VARCHAR(255),
            logo_url VARCHAR(255),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ restaurants table created\n\n";

    // Step 3: Create restaurant_orders table (orders placed at restaurants)
    echo "➡️ Step 3: Creating restaurant_orders table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurant_orders (
            id INT AUTO_INCREMENT PRIMARY KEY,
            customer_id INT,
            restaurant_id INT,
            order_reference VARCHAR(30) UNIQUE,
            total_amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending', 'preparing', 'ready', 'on_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            delivery_address TEXT,
            special_instructions TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ restaurant_orders table created\n\n";

    // Step 4: Create restaurant_order_items table
    echo "➡️ Step 4: Creating restaurant_order_items table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurant_order_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            order_id INT,
            item_name VARCHAR(100) NOT NULL,
            quantity INT NOT NULL DEFAULT 1,
            price DECIMAL(10,2) NOT NULL,
            FOREIGN KEY (order_id) REFERENCES restaurant_orders(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ restaurant_order_items table created\n\n";

    // Step 5: Create restaurant_menu table
    echo "➡️ Step 5: Creating restaurant_menu table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS restaurant_menu (
            id INT AUTO_INCREMENT PRIMARY KEY,
            restaurant_id INT,
            category VARCHAR(50),
            name VARCHAR(100) NOT NULL,
            description TEXT,
            price DECIMAL(10,2) NOT NULL,
            image_url VARCHAR(255),
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (restaurant_id) REFERENCES restaurants(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ restaurant_menu table created\n\n";

    // Step 6: Create taxi_companies table
    echo "➡️ Step 6: Creating taxi_companies table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS taxi_companies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            company_name VARCHAR(100) NOT NULL,
            description TEXT,
            phone VARCHAR(20),
            email VARCHAR(100),
            address VARCHAR(255),
            logo_url VARCHAR(255),
            rating DECIMAL(2,1) DEFAULT 0.0,
            total_vehicles INT DEFAULT 0,
            license_number VARCHAR(50),
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ taxi_companies table created\n\n";

    // Step 7: Create taxi_rides table
    echo "➡️ Step 7: Creating taxi_rides table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS taxi_rides (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ride_reference VARCHAR(30) UNIQUE,
            customer_id INT,
            taxi_company_id INT,
            pickup_location VARCHAR(255) NOT NULL,
            dropoff_location VARCHAR(255) NOT NULL,
            pickup_time DATETIME,
            dropoff_time DATETIME,
            distance_km DECIMAL(10,2),
            fare DECIMAL(10,2) NOT NULL,
            status ENUM('requested', 'accepted', 'in_progress', 'completed', 'cancelled') DEFAULT 'requested',
            payment_method VARCHAR(50),
            payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
            passenger_name VARCHAR(100),
            passenger_phone VARCHAR(20),
            driver_name VARCHAR(100),
            vehicle_plate VARCHAR(20),
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (taxi_company_id) REFERENCES taxi_companies(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ taxi_rides table created\n\n";

    // Step 8: Create taxi_vehicles table
    echo "➡️ Step 8: Creating taxi_vehicles table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS taxi_vehicles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            company_id INT,
            driver_name VARCHAR(100) NOT NULL,
            driver_phone VARCHAR(20),
            vehicle_type VARCHAR(50),
            plate_number VARCHAR(20) NOT NULL,
            model VARCHAR(50),
            color VARCHAR(30),
            is_available BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (company_id) REFERENCES taxi_companies(id) ON DELETE CASCADE
        )
    ");
    echo "   ✅ taxi_vehicles table created\n\n";

    // Step 9: Seed demo restaurant accounts
    echo "➡️ Step 9: Seeding demo restaurant accounts...\n";
    $hashed = password_hash('password', PASSWORD_DEFAULT);

    // Check if restaurant demo users already exist
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['lucy_restaurant']);
    if (!$stmt->fetch()) {
        // Restaurant Owner 1 - Lucy Restaurant
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'restaurant')");
        $stmt->execute(['lucy_restaurant', 'lucy@ethioserve.com', $hashed, 'Lucy Gebremedhin', '+251911223344']);
        $user1_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO restaurants (user_id, name, description, address, cuisine_type, opening_hours, phone, email, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$user1_id, "Lucy's Kitchen", 'Authentic Ethiopian cuisine with traditional recipes.', 'Bole Road, Addis Ababa', 'Ethiopian', '07:00 AM - 10:00 PM', '+251911223344', 'lucy@ethioserve.com', 4.6]);

        $rest1_id = $pdo->lastInsertId();

        // Add menu items for Lucy's Kitchen
        $pdo->exec("INSERT INTO restaurant_menu (restaurant_id, category, name, description, price) VALUES 
            ($rest1_id, 'Main Course', 'Doro Wot', 'Spicy chicken stew with boiled eggs and injera', 450.00),
            ($rest1_id, 'Main Course', 'Kitfo', 'Minced raw beef seasoned with mitmita and kibbeh', 550.00),
            ($rest1_id, 'Main Course', 'Tibs', 'Sauteed beef with onions and peppers', 400.00),
            ($rest1_id, 'Breakfast', 'Firfir', 'Shredded injera in spicy sauce', 250.00),
            ($rest1_id, 'Drinks', 'Ethiopian Coffee', 'Traditional buna ceremony style', 100.00),
            ($rest1_id, 'Desserts', 'Baklava', 'Sweet pastry with nuts and honey', 150.00)
        ");

        echo "   ✅ Lucy's Kitchen restaurant created with menu\n";

        // Restaurant Owner 2 - Abyssinia Restaurant
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'restaurant')");
        $stmt->execute(['abyssinia_rest', 'abyssinia@ethioserve.com', $hashed, 'Abyssinia Foods', '+251911556677']);
        $user2_id = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO restaurants (user_id, name, description, address, cuisine_type, opening_hours, phone, email, rating, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$user2_id, 'Abyssinia Restaurant', 'Premium dining experience with international and Ethiopian fusion.', 'Kazanchis, Addis Ababa', 'Ethiopian & International', '08:00 AM - 11:00 PM', '+251911556677', 'abyssinia@ethioserve.com', 4.8]);

        $rest2_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO restaurant_menu (restaurant_id, category, name, description, price) VALUES 
            ($rest2_id, 'Main Course', 'Special Beyaynetu', 'Fasting platter with 8 varieties', 600.00),
            ($rest2_id, 'Main Course', 'Lamb Tibs', 'Tender lamb sauteed with vegetables', 700.00),
            ($rest2_id, 'Appetizer', 'Sambusa', 'Crispy pastry filled with lentils', 120.00),
            ($rest2_id, 'Drinks', 'Fresh Mango Juice', 'Freshly squeezed mango', 80.00),
            ($rest2_id, 'Drinks', 'Tej', 'Traditional Ethiopian honey wine', 200.00)
        ");

        echo "   ✅ Abyssinia Restaurant created with menu\n";
    } else {
        echo "   ⏩ Restaurant demo accounts already exist\n";
    }

    // Step 10: Seed demo taxi accounts
    echo "\n➡️ Step 10: Seeding demo taxi accounts...\n";

    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute(['ride_addis']);
    if (!$stmt->fetch()) {
        // Taxi Company 1 - Ride Addis
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
        $stmt->execute(['ride_addis', 'rideaddis@ethioserve.com', $hashed, 'Ride Addis', '+251911001122']);
        $taxi1_user = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, address, rating, total_vehicles, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$taxi1_user, 'Ride Addis', 'Premium ride-hailing service in Addis Ababa. Comfortable, safe, and reliable.', '+251911001122', 'rideaddis@ethioserve.com', 'Bole, Addis Ababa', 4.7, 50]);

        $taxi1_id = $pdo->lastInsertId();

        // Add vehicles
        $pdo->exec("INSERT INTO taxi_vehicles (company_id, driver_name, driver_phone, vehicle_type, plate_number, model, color) VALUES 
            ($taxi1_id, 'Abebe Tadesse', '+251922001122', 'Sedan', 'AA-3-12345', 'Toyota Corolla', 'White'),
            ($taxi1_id, 'Dawit Mekonnen', '+251922001133', 'SUV', 'AA-3-12346', 'Toyota RAV4', 'Silver'),
            ($taxi1_id, 'Kidus Haile', '+251922001144', 'Sedan', 'AA-3-12347', 'Hyundai Accent', 'Blue')
        ");

        echo "   ✅ Ride Addis taxi company created with vehicles\n";

        // Taxi Company 2 - ZayRide
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'taxi')");
        $stmt->execute(['zayride', 'zayride@ethioserve.com', $hashed, 'ZayRide Ethiopia', '+251911334455']);
        $taxi2_user = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO taxi_companies (user_id, company_name, description, phone, email, address, rating, total_vehicles, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
        $stmt->execute([$taxi2_user, 'ZayRide Ethiopia', 'Affordable taxi service across Ethiopian cities. Fast and friendly drivers.', '+251911334455', 'zayride@ethioserve.com', 'Meskel Square, Addis Ababa', 4.4, 35]);

        $taxi2_id = $pdo->lastInsertId();

        $pdo->exec("INSERT INTO taxi_vehicles (company_id, driver_name, driver_phone, vehicle_type, plate_number, model, color) VALUES 
            ($taxi2_id, 'Yonas Gebru', '+251922334455', 'Sedan', 'AA-3-22345', 'Suzuki Dzire', 'White'),
            ($taxi2_id, 'Solomon Tekle', '+251922334466', 'Minivan', 'AA-3-22346', 'Toyota HiAce', 'Grey')
        ");

        echo "   ✅ ZayRide Ethiopia taxi company created with vehicles\n";
    } else {
        echo "   ⏩ Taxi demo accounts already exist\n";
    }

    // Seed some demo rides
    echo "\n➡️ Step 11: Seeding demo taxi rides...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_rides");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        // Find customer
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
        $stmt->execute();
        $cust = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT id FROM taxi_companies LIMIT 1");
        $stmt->execute();
        $taxi_co = $stmt->fetch();

        if ($cust && $taxi_co) {
            $pdo->exec("INSERT INTO taxi_rides (ride_reference, customer_id, taxi_company_id, pickup_location, dropoff_location, fare, status, payment_status, passenger_name, passenger_phone, driver_name, vehicle_plate) VALUES 
                ('RIDE-001', {$cust['id']}, {$taxi_co['id']}, 'Bole International Airport', 'Hilton Hotel, Addis Ababa', 350.00, 'completed', 'paid', 'Abebe Customer', '+251911000001', 'Abebe Tadesse', 'AA-3-12345'),
                ('RIDE-002', {$cust['id']}, {$taxi_co['id']}, 'Meskel Square', 'Addis Ababa University', 150.00, 'completed', 'paid', 'Abebe Customer', '+251911000001', 'Dawit Mekonnen', 'AA-3-12346'),
                ('RIDE-003', {$cust['id']}, {$taxi_co['id']}, 'Kazanchis', 'Bole Medhanialem', 200.00, 'requested', 'pending', 'Abebe Customer', '+251911000001', '', '')
            ");
            echo "   ✅ Demo taxi rides seeded\n";
        }
    } else {
        echo "   ⏩ Taxi rides already exist\n";
    }

    // Seed some demo restaurant orders
    echo "\n➡️ Step 12: Seeding demo restaurant orders...\n";
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurant_orders");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'customer' LIMIT 1");
        $stmt->execute();
        $cust = $stmt->fetch();

        $stmt = $pdo->prepare("SELECT id FROM restaurants LIMIT 1");
        $stmt->execute();
        $rest = $stmt->fetch();

        if ($cust && $rest) {
            $pdo->exec("INSERT INTO restaurant_orders (customer_id, restaurant_id, order_reference, total_amount, status, payment_method, payment_status) VALUES 
                ({$cust['id']}, {$rest['id']}, 'REST-001', 900.00, 'delivered', 'cash', 'paid'),
                ({$cust['id']}, {$rest['id']}, 'REST-002', 550.00, 'preparing', 'chapa', 'paid'),
                ({$cust['id']}, {$rest['id']}, 'REST-003', 350.00, 'pending', 'cash', 'pending')
            ");

            // Add order items
            $pdo->exec("INSERT INTO restaurant_order_items (order_id, item_name, quantity, price) VALUES 
                (1, 'Doro Wot', 2, 450.00),
                (2, 'Kitfo', 1, 550.00),
                (3, 'Firfir', 1, 250.00),
                (3, 'Ethiopian Coffee', 1, 100.00)
            ");
            echo "   ✅ Demo restaurant orders seeded\n";
        }
    } else {
        echo "   ⏩ Restaurant orders already exist\n";
    }

    echo "\n\n🎉 <strong>Migration completed successfully!</strong>\n";
    echo "\n📋 <strong>New Demo Accounts:</strong>\n";
    echo "   🍽️ Restaurant: <strong>lucy_restaurant</strong> / password\n";
    echo "   🍽️ Restaurant: <strong>abyssinia_rest</strong> / password\n";
    echo "   🚕 Taxi: <strong>ride_addis</strong> / password\n";
    echo "   🚕 Taxi: <strong>zayride</strong> / password\n";

} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<br><a href='login.php' style='display:inline-block;padding:12px 30px;background:#1B5E20;color:white;text-decoration:none;border-radius:30px;font-weight:bold;'>→ Go to Login</a>";
echo " <a href='register.php' style='display:inline-block;padding:12px 30px;background:#F9A825;color:#333;text-decoration:none;border-radius:30px;font-weight:bold;'>→ Register New Account</a>";
?>