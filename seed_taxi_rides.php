<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

echo "Seeding Taxi Rides...\n";

try {
    $customer_id = $pdo->query("SELECT id FROM users WHERE role = 'customer' LIMIT 1")->fetchColumn();
    $companies = $pdo->query("SELECT id FROM taxi_companies")->fetchAll(PDO::FETCH_COLUMN);

    if ($customer_id && !empty($companies)) {
        foreach ($companies as $company_id) {
            // Add 3 rides per company
            for ($i = 1; $i <= 3; $i++) {
                $stmt = $pdo->prepare("INSERT INTO taxi_rides (customer_id, taxi_company_id, pickup_location, dropoff_location, fare, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, 'paid')");
                $statuses = ['completed', 'in_progress', 'requested'];
                $status = $statuses[($i - 1) % 3];
                $stmt->execute([
                    $customer_id,
                    $company_id,
                    "Location A" . $i,
                    "Location B" . $i,
                    250 + ($i * 50),
                    $status
                ]);
            }
        }
        echo "✅ Added taxi rides for " . count($companies) . " companies.\n";
    } else {
        echo "❌ No customers or taxi companies found.\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
