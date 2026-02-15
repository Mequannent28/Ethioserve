<?php
/**
 * EthioServe - Seed Demo Accounts
 * Run this once to create demo accounts for all roles
 * URL: http://localhost/ethioserve/seed_accounts.php
 */
require_once 'includes/db.php';

$password = password_hash('password', PASSWORD_DEFAULT);
$results = [];

// ==================== CUSTOMER ACCOUNTS ====================
$customers = [
    ['customer1', 'customer1@ethioserve.com', 'Abeba Tadesse', '+251911111111'],
    ['customer2', 'customer2@ethioserve.com', 'Dawit Mekonnen', '+251922222222'],
    ['customer3', 'customer3@ethioserve.com', 'Selam Hailu', '+251933333333'],
];

foreach ($customers as $c) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$c[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'customer')");
            $stmt->execute([$c[0], $c[1], $password, $c[2], $c[3]]);
            $results[] = "✅ Customer '{$c[0]}' created";
        } else {
            $results[] = "ℹ️ Customer '{$c[0]}' already exists";
        }
    } catch (Exception $e) {
        $results[] = "❌ Failed to create '{$c[0]}': " . $e->getMessage();
    }
}

// ==================== HOTEL OWNER ACCOUNTS ====================
$hotels = [
    ['hotel_lucy', 'lucy@ethioserve.com', 'Lucy Hotel', '+251944444444', 'Lucy International Hotel', 'Bole Road, Addis Ababa', 'Ethiopian & Continental', '06:00 AM - 11:00 PM', 4.6],
    ['hotel_getfam', 'getfam@ethioserve.com', 'Getfam Hotel', '+251955555555', 'Getfam Hotel', 'Kazanchis, Addis Ababa', 'Ethiopian & International', '24/7', 4.4],
    ['hotel_eliana', 'eliana@ethioserve.com', 'Eliana Hotel', '+251966666666', 'Eliana Hotel', 'Piazza, Addis Ababa', 'Traditional Ethiopian', '07:00 AM - 10:00 PM', 4.3],
];

foreach ($hotels as $h) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$h[0]]);
        if (!$stmt->fetch()) {
            // Create user
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'hotel')");
            $stmt->execute([$h[0], $h[1], $password, $h[2], $h[3]]);
            $user_id = $pdo->lastInsertId();

            // Create hotel
            $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, description, location, cuisine_type, opening_hours, rating, min_order, delivery_time, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved')");
            $stmt->execute([
                $user_id,
                $h[4],
                "Premium hospitality and dining experience in the heart of Ethiopia.",
                $h[5],
                $h[6],
                $h[7],
                $h[8],
                200.00,
                '25-40 min',
                'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80'
            ]);
            $hotel_id = $pdo->lastInsertId();

            // Add sample menu items
            $menu_items = [
                [1, 'Special Injera Firfir', 'Spicy beef firfir with fresh injera and awaze.', 280.00],
                [2, 'Doro Wot', 'Classic Ethiopian chicken stew with hard-boiled eggs.', 450.00],
                [2, 'Tibs Special', 'Sauteed beef tips with onions and peppers.', 380.00],
                [3, 'Kitfo', 'Fresh minced beef with mitmita and kibbeh.', 520.00],
                [4, 'Fresh Juice Combo', 'Mango, avocado, and papaya layered juice.', 120.00],
                [5, 'Baklava', 'Sweet pastry with honey and pistachios.', 150.00],
            ];
            foreach ($menu_items as $mi) {
                $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, is_available) VALUES (?, ?, ?, ?, ?, TRUE)");
                $stmt->execute([$hotel_id, $mi[0], $mi[1], $mi[2], $mi[3]]);
            }

            $results[] = "✅ Hotel Owner '{$h[0]}' created with hotel '{$h[4]}' and 6 menu items";
        } else {
            $results[] = "ℹ️ Hotel Owner '{$h[0]}' already exists";
        }
    } catch (Exception $e) {
        $results[] = "❌ Failed to create '{$h[0]}': " . $e->getMessage();
    }
}

// ==================== BROKER ACCOUNTS ====================
$brokers = [
    ['broker_abel', 'abel@ethioserve.com', 'Abel Kebede', '+251977777777', 'ABEL2026', 'Top-rated broker connecting you to premium services.'],
    ['broker_marta', 'marta@ethioserve.com', 'Marta Solomon', '+251988888888', 'MART2026', 'Your trusted partner for real estate and rentals.'],
];

foreach ($brokers as $b) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$b[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'broker')");
            $stmt->execute([$b[0], $b[1], $password, $b[2], $b[3]]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO brokers (user_id, referral_code, bio) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $b[4], $b[5]]);

            $results[] = "✅ Broker '{$b[0]}' created with code '{$b[4]}'";
        } else {
            $results[] = "ℹ️ Broker '{$b[0]}' already exists";
        }
    } catch (Exception $e) {
        $results[] = "❌ Failed to create '{$b[0]}': " . $e->getMessage();
    }
}

// ==================== TRANSPORT ACCOUNTS ====================
$transports = [
    ['transport_selam', 'selam_bus@ethioserve.com', 'Selam Bus', '+251999999999', 'Selam Bus Express', 'Fast and comfortable intercity travel.', 'Autobus Tera, Addis Ababa'],
];

foreach ($transports as $t) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$t[0]]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role) VALUES (?, ?, ?, ?, ?, 'transport')");
            $stmt->execute([$t[0], $t[1], $password, $t[2], $t[3]]);
            $user_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO transport_companies (user_id, company_name, description, phone, email, address, rating, total_buses, status) VALUES (?, ?, ?, ?, ?, ?, 4.5, 15, 'approved')");
            $stmt->execute([$user_id, $t[4], $t[5], $t[3], $t[1], $t[6]]);

            $results[] = "✅ Transport '{$t[0]}' created with company '{$t[4]}'";
        } else {
            $results[] = "ℹ️ Transport '{$t[0]}' already exists";
        }
    } catch (Exception $e) {
        $results[] = "❌ Failed to create '{$t[0]}': " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seed Accounts - EthioServe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f5f5;
        }

        .result-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .account-table th {
            background: #1B5E20;
            color: white;
        }

        .badge-role {
            padding: 5px 14px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.8rem;
        }
    </style>
</head>

<body>
    <div class="container py-5">
        <div class="result-card p-4 mb-4">
            <h2 class="fw-bold text-center" style="color:#1B5E20;">🌱 EthioServe Account Seeder</h2>
            <p class="text-center text-muted">Creating demo accounts for testing...</p>
            <hr>
            <?php foreach ($results as $r): ?>
                <div class="mb-1"><code><?php echo $r; ?></code></div>
            <?php endforeach; ?>
        </div>

        <div class="result-card p-4">
            <h4 class="fw-bold mb-3" style="color:#1B5E20;">📋 All Demo Accounts</h4>
            <p class="text-muted small">All accounts use password: <strong>password</strong></p>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr class="account-table">
                            <th>Role</th>
                            <th>Username</th>
                            <th>Name</th>
                            <th>Password</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><span class="badge-role bg-danger text-white">Admin</span></td>
                            <td><code>admin</code></td>
                            <td>System Admin</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="fw-bold pt-3" style="color:#1B5E20;">👤 Customers</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-primary text-white">Customer</span></td>
                            <td><code>customer1</code></td>
                            <td>Abeba Tadesse</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-primary text-white">Customer</span></td>
                            <td><code>customer2</code></td>
                            <td>Dawit Mekonnen</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-primary text-white">Customer</span></td>
                            <td><code>customer3</code></td>
                            <td>Selam Hailu</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="fw-bold pt-3" style="color:#1B5E20;">🏨 Hotel Owners</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-success text-white">Hotel</span></td>
                            <td><code>hilton_owner</code></td>
                            <td>Hilton Addis</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-success text-white">Hotel</span></td>
                            <td><code>sheraton_owner</code></td>
                            <td>Sheraton Addis</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-success text-white">Hotel</span></td>
                            <td><code>hotel_lucy</code></td>
                            <td>Lucy International Hotel</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-success text-white">Hotel</span></td>
                            <td><code>hotel_getfam</code></td>
                            <td>Getfam Hotel</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-success text-white">Hotel</span></td>
                            <td><code>hotel_eliana</code></td>
                            <td>Eliana Hotel</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="fw-bold pt-3" style="color:#1B5E20;">💼 Brokers</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-warning text-dark">Broker</span></td>
                            <td><code>broker1</code></td>
                            <td>Abebe Bikila</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-warning text-dark">Broker</span></td>
                            <td><code>broker_abel</code></td>
                            <td>Abel Kebede</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-warning text-dark">Broker</span></td>
                            <td><code>broker_marta</code></td>
                            <td>Marta Solomon</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="fw-bold pt-3" style="color:#1B5E20;">🚌 Transport</td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-info text-white">Transport</span></td>
                            <td><code>golden_bus</code></td>
                            <td>Golden Bus</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-info text-white">Transport</span></td>
                            <td><code>walya_bus</code></td>
                            <td>Walya Bus</td>
                            <td><code>password</code></td>
                        </tr>
                        <tr>
                            <td><span class="badge-role bg-info text-white">Transport</span></td>
                            <td><code>transport_selam</code></td>
                            <td>Selam Bus Express</td>
                            <td><code>password</code></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="text-center mt-4">
                <a href="login.php" class="btn btn-lg rounded-pill px-5 fw-bold text-white" style="background:#1B5E20;">
                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                </a>
            </div>
        </div>
    </div>
</body>

</html>