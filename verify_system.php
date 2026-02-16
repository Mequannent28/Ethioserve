<?php
/**
 * Quick Database Verification Script
 * Checks if all necessary data exists for the booking system
 */

require_once 'includes/db.php';

echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    h2 { color: #1B5E20; border-bottom: 3px solid #1B5E20; padding-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
    th { background: #1B5E20; color: white; }
    tr:hover { background: #f5f5f5; }
</style>";

echo "<h1>🔍 Ethiopian Bus Booking System - Database Verification</h1>";

try {
    // Check Routes
    echo "<div class='section'>";
    echo "<h2>📍 Routes Check</h2>";
    $route_count = $pdo->query("SELECT COUNT(*) FROM routes")->fetchColumn();
    echo "<p class='success'>✅ Total Routes: $route_count</p>";

    if ($route_count > 0) {
        echo "<h3>Sample Routes from Addis Ababa:</h3>";
        echo "<table>";
        echo "<tr><th>Origin</th><th>Destination</th><th>Distance (km)</th><th>Duration (hrs)</th></tr>";

        $routes = $pdo->query("SELECT * FROM routes WHERE origin = 'Addis Ababa' LIMIT 10")->fetchAll();
        foreach ($routes as $route) {
            echo "<tr>";
            echo "<td>{$route['origin']}</td>";
            echo "<td>{$route['destination']}</td>";
            echo "<td>{$route['distance_km']}</td>";
            echo "<td>{$route['estimated_hours']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Check Schedules
    echo "<div class='section'>";
    echo "<h2>🚌 Schedules Check</h2>";
    $schedule_count = $pdo->query("SELECT COUNT(*) FROM schedules WHERE is_active = TRUE")->fetchColumn();
    echo "<p class='success'>✅ Total Active Schedules: $schedule_count</p>";

    if ($schedule_count > 0) {
        echo "<h3>Sample Schedules:</h3>";
        echo "<table>";
        echo "<tr><th>Route</th><th>Departure</th><th>Arrival</th><th>Price (ETB)</th><th>Bus Company</th></tr>";

        $schedules = $pdo->query("
            SELECT s.*, r.origin, r.destination, tc.company_name
            FROM schedules s
            JOIN routes r ON s.route_id = r.id
            JOIN buses b ON s.bus_id = b.id
            JOIN transport_companies tc ON b.company_id = tc.id
            WHERE s.is_active = TRUE
            LIMIT 10
        ")->fetchAll();

        foreach ($schedules as $sched) {
            echo "<tr>";
            echo "<td>{$sched['origin']} → {$sched['destination']}</td>";
            echo "<td>" . date('H:i', strtotime($sched['departure_time'])) . "</td>";
            echo "<td>" . date('H:i', strtotime($sched['arrival_time'])) . "</td>";
            echo "<td>" . number_format($sched['price']) . "</td>";
            echo "<td>{$sched['company_name']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Check Transport Companies
    echo "<div class='section'>";
    echo "<h2>🏢 Transport Companies Check</h2>";
    $company_count = $pdo->query("SELECT COUNT(*) FROM transport_companies WHERE status = 'approved'")->fetchColumn();
    echo "<p class='success'>✅ Total Approved Companies: $company_count</p>";

    if ($company_count > 0) {
        echo "<h3>Registered Companies:</h3>";
        echo "<table>";
        echo "<tr><th>Company Name</th><th>Rating</th><th>Phone</th><th>Status</th></tr>";

        $companies = $pdo->query("SELECT * FROM transport_companies WHERE status = 'approved'")->fetchAll();
        foreach ($companies as $company) {
            echo "<tr>";
            echo "<td>{$company['company_name']}</td>";
            echo "<td>⭐ " . ($company['rating'] ?? 'N/A') . "</td>";
            echo "<td>{$company['phone']}</td>";
            echo "<td><span class='success'>{$company['status']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // Check Buses
    echo "<div class='section'>";
    echo "<h2>🚍 Buses Check</h2>";
    $bus_count = $pdo->query("SELECT COUNT(*) FROM buses WHERE is_active = TRUE")->fetchColumn();
    echo "<p class='success'>✅ Total Active Buses: $bus_count</p>";

    if ($bus_count > 0) {
        echo "<h3>Sample Buses:</h3>";
        echo "<table>";
        echo "<tr><th>Bus Number</th><th>Company</th><th>Type</th><th>Seats</th><th>Amenities</th></tr>";

        $buses = $pdo->query("
            SELECT b.*, tc.company_name, bt.name as bus_type
            FROM buses b
            JOIN transport_companies tc ON b.company_id = tc.id
            JOIN bus_types bt ON b.bus_type_id = bt.id
            WHERE b.is_active = TRUE
            LIMIT 10
        ")->fetchAll();

        foreach ($buses as $bus) {
            echo "<tr>";
            echo "<td>{$bus['bus_number']}</td>";
            echo "<td>{$bus['company_name']}</td>";
            echo "<td>{$bus['bus_type']}</td>";
            echo "<td>{$bus['total_seats']}</td>";
            echo "<td>{$bus['amenities']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    echo "</div>";

    // System Readiness
    echo "<div class='section' style='background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white;'>";
    echo "<h2 style='color: white; border-color: white;'>✨ System Readiness Report</h2>";

    $all_good = true;

    if ($route_count < 10) {
        echo "<p class='error'>❌ Warning: Only $route_count routes found. Run seed script!</p>";
        $all_good = false;
    }

    if ($schedule_count < 10) {
        echo "<p class='error'>❌ Warning: Only $schedule_count schedules found. Run seed script!</p>";
        $all_good = false;
    }

    if ($company_count < 1) {
        echo "<p class='error'>❌ Error: No transport companies found. Please seed companies!</p>";
        $all_good = false;
    }

    if ($bus_count < 1) {
        echo "<p class='error'>❌ Error: No buses found. Please seed buses!</p>";
        $all_good = false;
    }

    if ($all_good) {
        echo "<h3 style='color: white;'>🎉 All Systems GO!</h3>";
        echo "<p style='font-size: 1.2em;'>✅ Routes: $route_count</p>";
        echo "<p style='font-size: 1.2em;'>✅ Schedules: $schedule_count</p>";
        echo "<p style='font-size: 1.2em;'>✅ Companies: $company_count</p>";
        echo "<p style='font-size: 1.2em;'>✅ Buses: $bus_count</p>";
        echo "<hr style='border-color: white; margin: 20px 0;'>";
        echo "<h2 style='color: white;'>🚀 Ready for Testing!</h2>";
        echo "<p style='font-size: 1.3em;'><a href='customer/buses.php' style='color: white; text-decoration: underline;'>👉 Click here to start booking</a></p>";
    }

    echo "</div>";

} catch (Exception $e) {
    echo "<div class='section'>";
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<div class='section' style='text-align: center; background: #2d3748; color: white;'>";
echo "<p>Database verification complete at " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";
?>