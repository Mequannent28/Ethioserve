<?php
/**
 * Database Migration: Add Email Tracking Columns
 * Run this once to add columns for tracking reminder emails
 */

require_once 'includes/db.php';

echo "=== Adding Email Tracking Columns to bus_bookings ===\n\n";

try {
    // Add reminder_sent column
    echo "Adding 'reminder_sent' column...\n";
    $pdo->exec("
        ALTER TABLE bus_bookings 
        ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0
    ");
    echo "✅ Done\n\n";

    // Add reminder_sent_at column
    echo "Adding 'reminder_sent_at' column...\n";
    $pdo->exec("
        ALTER TABLE bus_bookings 
        ADD COLUMN IF NOT EXISTS reminder_sent_at DATETIME NULL
    ");
    echo "✅ Done\n\n";

    // Add confirmation_email_sent column
    echo "Adding 'confirmation_email_sent' column...\n";
    $pdo->exec("
        ALTER TABLE bus_bookings 
        ADD COLUMN IF NOT EXISTS confirmation_email_sent TINYINT(1) DEFAULT 0
    ");
    echo "✅ Done\n\n";

    // Add confirmation_email_sent_at column
    echo "Adding 'confirmation_email_sent_at' column...\n";
    $pdo->exec("
        ALTER TABLE bus_bookings 
        ADD COLUMN IF NOT EXISTS confirmation_email_sent_at DATETIME NULL
    ");
    echo "✅ Done\n\n";

    echo "=== Migration Completed Successfully! ===\n";
    echo "New columns added:\n";
    echo "  - reminder_sent (tracks if 3-hour reminder was sent)\n";
    echo "  - reminder_sent_at (timestamp of reminder email)\n";
    echo "  - confirmation_email_sent (tracks if booking confirmation was sent)\n";
    echo "  - confirmation_email_sent_at (timestamp of confirmation email)\n";

} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nIf columns already exist, you can ignore this error.\n";
}
?>