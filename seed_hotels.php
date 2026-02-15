<?php
require_once 'includes/db.php';

try {
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'hotel' LIMIT 1");
    $hotel_user_id = $stmt->fetchColumn();

    if (!$hotel_user_id) {
        $pdo->exec("INSERT INTO users (username, password, email, full_name, role) VALUES ('hotel_owner', '" . password_hash('password123', PASSWORD_DEFAULT) . "', 'hotels@ethioserve.com', 'Ethiopia Hotel Services', 'hotel')");
        $hotel_user_id = $pdo->lastInsertId();
    }

    $hotels = [
        [
            'name' => 'Sheraton Addis, a Luxury Collection Hotel',
            'desc' => 'Nestled on a hilltop overlooking the city, Sheraton Addis is a landmark of luxury and elegance in the heart of Ethiopia.',
            'loc' => 'Taitu Street, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1542314831-068cd1dbfeeb?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'International & Ethiopian Fusion'
        ],
        [
            'name' => 'Hilton Addis Ababa',
            'desc' => 'Experience amazing views of the city from this central hotel, located across from the Prime Minister\'s office and near several UN buildings.',
            'loc' => 'Menelik II Avenue, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1566073771259-6a8506099945?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Global Cuisine'
        ],
        [
            'name' => 'Ethiopian Skylight Hotel',
            'desc' => 'The largest hotel in Ethiopia, located just minutes from Bole International Airport, offering world-class luxury and comfort.',
            'loc' => 'Bole Airport Area, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1445019980597-93fa8acb246c?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Continental & Traditional'
        ],
        [
            'name' => 'Hyatt Regency Addis Ababa',
            'desc' => 'Modern and stylish hotel located on Meskel Square, offering sophisticated dining and the highest service standards.',
            'loc' => 'Meskel Square, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1551882547-ff40c63fe5fa?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'International'
        ],
        [
            'name' => 'Radisson Blu Hotel, Addis Ababa',
            'desc' => 'A premier upscale hotel located in the city center, perfect for business travelers and luxury seekers.',
            'loc' => 'Kazanchis, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1517840901100-8179e982acb7?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Mediterranean & Local'
        ]
    ];

    foreach ($hotels as $h) {
        $stmt = $pdo->prepare("SELECT id FROM hotels WHERE name = ?");
        $stmt->execute([$h['name']]);
        if (!$stmt->fetch()) {
            $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, description, location, image_url, cuisine_type, status, rating) VALUES (?, ?, ?, ?, ?, ?, 'approved', ?)");
            $stmt->execute([$hotel_user_id, $h['name'], $h['desc'], $h['loc'], $h['img'], $h['cuisine'], rand(47, 50) / 10]);
        }
    }

    echo "Premium Ethiopian Hotels registered successfully!";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>