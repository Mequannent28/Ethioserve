<?php
require_once 'includes/db.php';

$doctor = [
    'name' => 'Dr. Yalemwork Belay',
    'type' => 'doctor',
    'specialty_id' => 1, // General Medicine
    'bio' => 'Experienced General Practitioner dedicated to providing compassionate and comprehensive healthcare in Addis Ababa.',
    'location' => 'Bole, Addis Ababa',
    'phone' => '0911000000',
    'rating' => 4.9,
    'image_url' => 'https://images.unsplash.com/photo-1594824476967-48c8b964273f?w=600', // Professional female doctor placeholder
    'availability_hours' => 'Mon-Fri: 8:00 AM - 5:00 PM'
];

try {
    $stmt = $pdo->prepare("INSERT INTO health_providers (name, type, specialty_id, bio, location, phone, rating, image_url, availability_hours) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $doctor['name'],
        $doctor['type'],
        $doctor['specialty_id'],
        $doctor['bio'],
        $doctor['location'],
        $doctor['phone'],
        $doctor['rating'],
        $doctor['image_url'],
        $doctor['availability_hours']
    ]);
    echo "Dr. Yalemwork Belay has been added to the health system.";
} catch (Exception $e) {
    echo "Addition failed: " . $e->getMessage();
}
