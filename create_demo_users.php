<?php
require_once 'includes/db.php';

try {
    // Add home_pro to role enum if missing
    $pdo->exec("ALTER TABLE users MODIFY COLUMN role ENUM('admin','hotel','broker','transport','customer','restaurant','taxi','student','doctor','employer','dating','home_pro') DEFAULT 'customer'");
} catch (Exception $e) {
    // Already added or error
}

function createDemoUser($pdo, $username, $password, $role, $fullName)
{
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        echo "User '$username' already exists. Skipping.\n";
        return;
    }

    $stmt = $pdo->prepare("INSERT INTO users (username, password, role, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $email = $username . "@ethioserve.com";
    $phone = "09" . rand(10000000, 99999999);
    $stmt->execute([$username, $hashedPassword, $role, $fullName, $email, $phone]);
    echo "User '$username' created with role '$role'. Password: $password\n";
}

echo "CREATING DEMO ACCOUNTS...\n";
createDemoUser($pdo, 'taxi_demo', 'taxi123', 'taxi', 'Taxi Demo Manager');
createDemoUser($pdo, 'hotel_demo', 'hotel123', 'hotel', 'Hotel Demo Manager');
createDemoUser($pdo, 'restaurant_demo', 'rest123', 'restaurant', 'Restaurant Demo Owner');
createDemoUser($pdo, 'transport_demo', 'trans123', 'transport', 'Transport Demo Owner');
createDemoUser($pdo, 'broker_demo', 'broker123', 'broker', 'Real Estate Broker Demo');
createDemoUser($pdo, 'doctor_demo', 'doc123', 'doctor', 'Doctor Demo Account');
createDemoUser($pdo, 'student_demo', 'edu123', 'student', 'Education Demo Student');
createDemoUser($pdo, 'customer_demo', 'cust123', 'customer', 'General Customer Demo');
createDemoUser($pdo, 'employer_demo', 'job123', 'employer', 'Job Employer Demo');
createDemoUser($pdo, 'dating_demo', 'date123', 'dating', 'Dating Demo User');
createDemoUser($pdo, 'pro_demo', 'pro123', 'home_pro', 'Home Service Pro Demo');

echo "\nDEMO ACCOUNTS CREATION COMPLETED.\n";
echo "SUMMARY:\n";
echo "---------------------------------\n";
echo "Taxi: taxi_demo / taxi123\n";
echo "Hotel: hotel_demo / hotel123\n";
echo "Restaurant: restaurant_demo / rest123\n";
echo "Transport: transport_demo / trans123\n";
echo "Real Estate: broker_demo / broker123\n";
echo "Health/Doctor: doctor_demo / doc123\n";
echo "Education: student_demo / edu123\n";
echo "Customer: customer_demo / cust123\n";
echo "Jobs: employer_demo / job123\n";
echo "Dating: dating_demo / date123\n";
echo "Home Pro: pro_demo / pro123\n";
echo "---------------------------------\n";
