<?php
/**
 * Database Migration: Enhanced Passenger Information
 * Adds detailed fields for passenger data collection
 */

require_once 'includes/db.php';

echo "<h2>🔄 Migrating Database for Enhanced Passenger Information</h2>\n";
echo "<pre>\n";

try {
    $pdo->beginTransaction();

    // 1. Add columns to bus_bookings table for enhanced passenger info
    echo "📝 Updating bus_bookings table...\n";

    $migrations = [
        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_first_names TEXT DEFAULT NULL COMMENT 'JSON array of first names'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_middle_names TEXT DEFAULT NULL COMMENT 'JSON array of middle names'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_last_names TEXT DEFAULT NULL COMMENT 'JSON array of last names'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_dobs TEXT DEFAULT NULL COMMENT 'JSON array of dates of birth'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_genders TEXT DEFAULT NULL COMMENT 'JSON array of genders'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS passenger_emails TEXT DEFAULT NULL COMMENT 'JSON array of emails'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS emergency_contact_name VARCHAR(255) DEFAULT NULL",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS emergency_contact_phone VARCHAR(20) DEFAULT NULL",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS special_requirements TEXT DEFAULT NULL COMMENT 'Wheelchair, dietary, etc.'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS owner_response TEXT DEFAULT NULL COMMENT 'Owner response message'",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS confirmed_at TIMESTAMP NULL DEFAULT NULL",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS cancelled_at TIMESTAMP NULL DEFAULT NULL",

        "ALTER TABLE bus_bookings 
         ADD COLUMN IF NOT EXISTS cancellation_reason TEXT DEFAULT NULL"
    ];

    foreach ($migrations as $sql) {
        try {
            $pdo->exec($sql);
            echo "✅ Migration executed successfully\n";
        } catch (PDOException $e) {
            // Skip if column already exists
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "⏭️  Column already exists, skipping...\n";
            } else {
                throw $e;
            }
        }
    }

    // 2. Create notifications table if not exists
    echo "\n📬 Creating notifications table...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS booking_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            type ENUM('booking_created', 'booking_confirmed', 'booking_cancelled', 'payment_received', 'seats_assigned') NOT NULL,
            title VARCHAR(255) NOT NULL,
            message TEXT NOT NULL,
            is_read BOOLEAN DEFAULT FALSE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (booking_id) REFERENCES bus_bookings(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            INDEX idx_user_read (user_id, is_read),
            INDEX idx_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Notifications table created/verified\n";

    // 3. Create payment history table
    echo "\n💰 Creating payment history table...\n";

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS payment_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            payment_method VARCHAR(50) NOT NULL,
            transaction_reference VARCHAR(100) DEFAULT NULL,
            payment_status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
            payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            notes TEXT DEFAULT NULL,
            FOREIGN KEY (booking_id) REFERENCES bus_bookings(id) ON DELETE CASCADE,
            INDEX idx_booking (booking_id),
            INDEX idx_status (payment_status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    echo "✅ Payment history table created/verified\n";

    $pdo->commit();

    echo "\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✨ MIGRATION COMPLETED SUCCESSFULLY!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ bus_bookings table enhanced with detailed passenger fields\n";
    echo "✅ booking_notifications table created\n";
    echo "✅ payment_history table created\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

} catch (Exception $e) {
    $pdo->rollBack();
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "</pre>\n";
echo "<p><a href='customer/buses.php'>🚌 Go to Bus Booking</a></p>\n";
?>