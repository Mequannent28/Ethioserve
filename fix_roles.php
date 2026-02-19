<?php
require_once 'includes/db.php';

try {
    // Update users table role enum to include doctor, employer, and dating
    // We also include existing ones to be safe
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','hotel','broker','transport','customer','restaurant','taxi','student','doctor','employer','dating') DEFAULT 'customer'");
    echo "✅ Successfully updated users role enum.<br>";

    // Refresh Dr. Dawit's role to ensure it's correct
    $pdo->exec("UPDATE users SET role = 'doctor' WHERE username = 'dr_dawit'");
    echo "✅ Refreshed Dr. Dawit's role.<br>";

    echo "Migration completed successfully!";
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
