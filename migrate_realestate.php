<?php
require_once 'includes/db.php';

echo "<h2>🏗️ Setting up Real Estate Module...</h2>";

try {
    // 1. Create Real Estate Properties Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS real_estate_properties (
        id INT AUTO_INCREMENT PRIMARY KEY,
        agent_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(15, 2) NOT NULL,
        type ENUM('sale', 'rent', 'lease') NOT NULL DEFAULT 'sale',
        category ENUM('apartment', 'house', 'villa', 'condominium', 'office', 'land', 'warehouse', 'commercial') NOT NULL,
        location VARCHAR(255) NOT NULL,
        city VARCHAR(100) DEFAULT 'Addis Ababa',
        bedrooms INT DEFAULT 0,
        bathrooms INT DEFAULT 0,
        area_sqm DECIMAL(10, 2),
        image_url VARCHAR(500),
        gallery_images TEXT COMMENT 'JSON array of additional images',
        amenities TEXT COMMENT 'JSON array of amenities',
        status ENUM('available', 'sold', 'rented', 'pending') DEFAULT 'available',
        is_featured BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (agent_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Created 'real_estate_properties' table.<br>";

    // 2. Create Real Estate Inquiries Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS real_estate_inquiries (
        id INT AUTO_INCREMENT PRIMARY KEY,
        property_id INT NOT NULL,
        user_id INT DEFAULT NULL COMMENT 'Nullable for guest inquiries',
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        message TEXT NOT NULL,
        status ENUM('new', 'read', 'responded', 'closed') DEFAULT 'new',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (property_id) REFERENCES real_estate_properties(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    echo "✅ Created 'real_estate_inquiries' table.<br>";

    // 3. Seed some sample data
    $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'broker' LIMIT 1");
    $stmt->execute();
    $agent = $stmt->fetch();
    $agent_id = $agent ? $agent['id'] : 1; // Fallback to admin if no broker

    // Sample Properties
    $properties = [
        [
            $agent_id,
            'Luxury Villa in Bole',
            'Stunning 5-bedroom villa with a large garden and modern amenities.',
            25000000.00,
            'sale',
            'villa',
            'Bole, Addis Ababa',
            5,
            4,
            500.00,
            'https://images.unsplash.com/photo-1613977257363-707ba9348227?auto=format&fit=crop&w=800&q=80',
            1
        ],
        [
            $agent_id,
            'Modern Apartment near Kazanchis',
            '3-bedroom apartment with city view, fully furnished.',
            45000.00,
            'rent',
            'apartment',
            'Kazanchis, Addis Ababa',
            3,
            2,
            120.00,
            'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&w=800&q=80',
            1
        ],
        [
            $agent_id,
            'Commercial Space in Piassa',
            'Prime office location in the historic center.',
            80000.00,
            'rent',
            'office',
            'Piassa, Addis Ababa',
            0,
            2,
            200.00,
            'https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&w=800&q=80',
            0
        ],
        [
            $agent_id,
            'G+2 House in CMC',
            'Spacious family home in a quiet neighborhood.',
            18500000.00,
            'sale',
            'house',
            'CMC, Addis Ababa',
            6,
            5,
            350.00,
            'https://images.unsplash.com/photo-1600596542815-2250657d2fc5?auto=format&fit=crop&w=800&q=80',
            0
        ],
        [
            $agent_id,
            'Furnished Condo in Sarbet',
            'Cozy 2-bedroom condo, perfect for expats.',
            30000.00,
            'rent',
            'condominium',
            'Sarbet, Addis Ababa',
            2,
            1,
            90.00,
            'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?auto=format&fit=crop&w=800&q=80',
            1
        ],
        [
            $agent_id,
            'Plot of Land in Ayat',
            '500sqm residential land ready for construction.',
            6000000.00,
            'sale',
            'land',
            'Ayat, Addis Ababa',
            0,
            0,
            500.00,
            'https://images.unsplash.com/photo-1500382017468-9049fed747ef?auto=format&fit=crop&w=800&q=80',
            0
        ]
    ];

    $insert = $pdo->prepare("INSERT INTO real_estate_properties 
        (agent_id, title, description, price, type, category, location, bedrooms, bathrooms, area_sqm, image_url, is_featured) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($properties as $prop) {
        // Check if exists first to avoid duplicates on re-run
        $check = $pdo->prepare("SELECT id FROM real_estate_properties WHERE title = ?");
        $check->execute([$prop[1]]);
        if (!$check->fetch()) {
            $insert->execute($prop);
            echo "🏠 Seeded property: {$prop[1]}<br>";
        }
    }

    echo "<h3>✨ Real Estate System Ready!</h3>";
    echo "<a href='realestate/index.php'>Go to Real Estate Home</a>";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>