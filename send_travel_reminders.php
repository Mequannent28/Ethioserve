<?php
/**
 * Automated Email Reminder System
 * This script should be run every hour via cron job or Windows Task Scheduler
 * 
 * SETUP:
 * Windows: Task Scheduler -> Run: php C:\xampp1\htdocs\Ethioserve-main\send_travel_reminders.php
 * Linux: Crontab: 0 * * * * php /path/to/send_travel_reminders.php
 */

require_once 'includes/db.php';
require_once 'includes/email_templates.php';

echo "=== EthioServe Travel Reminder System ===\n";
echo "Started at: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Find all confirmed bookings that depart in the next 3-4 hours
    // and haven't received a reminder yet
    $stmt = $pdo->prepare("
        SELECT 
            bb.*,
            s.departure_time,
            s.arrival_time,
            r.origin,
            r.destination,
            b.bus_number,
            bt.name as bus_type,
            tc.company_name,
            tc.phone as company_phone,
            tc.email as company_email,
            u.email as customer_email,
            u.full_name as customer_name
        FROM bus_bookings bb
        JOIN schedules s ON bb.schedule_id = s.id
        JOIN routes r ON s.route_id = r.id
        JOIN buses b ON s.bus_id = b.id
        JOIN bus_types bt ON b.bus_type_id = bt.id
        JOIN transport_companies tc ON b.company_id = tc.id
        JOIN users u ON bb.customer_id = u.id
        WHERE bb.status = 'confirmed'
        AND bb.travel_date = CURDATE()
        AND TIMESTAMPDIFF(HOUR, NOW(), CONCAT(bb.travel_date, ' ', s.departure_time)) BETWEEN 3 AND 4
        AND (bb.reminder_sent IS NULL OR bb.reminder_sent = 0)
    ");

    $stmt->execute();
    $bookings = $stmt->fetchAll();

    echo "Found " . count($bookings) . " booking(s) requiring reminder emails\n\n";

    $sent_count = 0;
    $failed_count = 0;

    foreach ($bookings as $booking) {
        echo "Processing Booking: {$booking['booking_reference']}\n";
        echo "  Customer: {$booking['customer_name']} ({$booking['customer_email']})\n";
        echo "  Route: {$booking['origin']} → {$booking['destination']}\n";
        echo "  Departure: {$booking['departure_time']}\n";

        // Prepare booking data
        $booking_data = [
            'booking_reference' => $booking['booking_reference'],
            'company_name' => $booking['company_name'],
            'company_phone' => $booking['company_phone'],
            'company_email' => $booking['company_email'],
            'bus_type' => $booking['bus_type'],
            'bus_number' => $booking['bus_number'],
            'origin' => $booking['origin'],
            'destination' => $booking['destination'],
            'travel_date_formatted' => date('l, F d, Y', strtotime($booking['travel_date'])),
            'departure_time_formatted' => date('h:i A', strtotime($booking['departure_time'])),
            'arrival_time_formatted' => date('h:i A', strtotime($booking['arrival_time'])),
            'pickup_point' => $booking['pickup_point'],
            'dropoff_point' => $booking['dropoff_point'],
            'num_passengers' => $booking['num_passengers'],
            'total_amount' => number_format($booking['total_amount']),
            'payment_method' => strtoupper($booking['payment_method']),
            'seat_numbers_html' => !empty($booking['seat_numbers'])
                ? '<div class="info-row"><div class="info-label">💺 Seat Numbers:</div><div class="info-value"><strong style="font-size: 20px; color: #FF9800;">' . htmlspecialchars($booking['seat_numbers']) . '</strong></div></div>'
                : ''
        ];

        // Send reminder email
        $result = sendTravelReminderEmail($booking_data, $booking['customer_email'], $booking['customer_name']);

        if ($result['success']) {
            echo "  ✅ Reminder email sent successfully!\n";
            $sent_count++;

            // Mark reminder as sent
            $update_stmt = $pdo->prepare("
                UPDATE bus_bookings 
                SET reminder_sent = 1, reminder_sent_at = NOW() 
                WHERE id = ?
            ");
            $update_stmt->execute([$booking['id']]);

        } else {
            echo "  ❌ Failed to send reminder email: {$result['message']}\n";
            $failed_count++;
        }

        echo "\n";
    }

    echo "=== Summary ===\n";
    echo "Total bookings processed: " . count($bookings) . "\n";
    echo "Successfully sent: $sent_count\n";
    echo "Failed: $failed_count\n";
    echo "\nCompleted at: " . date('Y-m-d H:i:s') . "\n";

    // Log the execution
    $log_file = __DIR__ . '/logs/reminder_emails_' . date('Y-m-d') . '.log';
    $log_dir = dirname($log_file);

    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0777, true);
    }

    $log_entry = sprintf(
        "[%s] Processed: %d | Sent: %d | Failed: %d\n",
        date('Y-m-d H:i:s'),
        count($bookings),
        $sent_count,
        $failed_count
    );

    file_put_contents($log_file, $log_entry, FILE_APPEND);

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>