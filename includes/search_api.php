<?php
/**
 * EthioServe — Live Search API
 * Returns JSON results from multiple services: restaurants, hotels, taxis, buses, 
 * listings, menu items, health providers, dating profiles, jobs, real estate, 
 * education, home services, exchange materials, and users
 */

header('Content-Type: application/json');
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$query = trim($_GET['q'] ?? '');

if (strlen($query) < 1) {
    echo json_encode(['results' => []]);
    exit;
}

$search = "%{$query}%";
$results = [];

try {
    // 1. Search Restaurants
    $stmt = $pdo->prepare("
        SELECT id, name, address AS description, cuisine_type AS extra, 'restaurant' AS category 
        FROM restaurants 
        WHERE status = 'approved' AND (name LIKE ? OR address LIKE ? OR cuisine_type LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Restaurant',
            'icon' => 'fas fa-utensils',
            'color' => '#E65100',
            'link' => BASE_URL . '/customer/restaurants.php'
        ];
    }

    // 2. Search Hotels
    $stmt = $pdo->prepare("
        SELECT id, name, location AS description, cuisine_type AS extra, 'hotel' AS category 
        FROM hotels 
        WHERE status = 'approved' AND (name LIKE ? OR location LIKE ? OR cuisine_type LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Hotel',
            'icon' => 'fas fa-hotel',
            'color' => '#1565C0',
            'link' => BASE_URL . '/customer/menu.php?hotel_id=' . $row['id']
        ];
    }

    // 3. Search Taxi Companies
    $stmt = $pdo->prepare("
        SELECT id, company_name AS name, address AS description, rating AS extra, 'taxi' AS category 
        FROM taxi_companies 
        WHERE status = 'approved' AND (company_name LIKE ? OR address LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ? '⭐ ' . $row['extra'] : '',
            'category' => 'Taxi',
            'icon' => 'fas fa-taxi',
            'color' => '#F9A825',
            'link' => BASE_URL . '/customer/taxi.php'
        ];
    }

    // 4. Search Bus / Transport Companies
    $stmt = $pdo->prepare("
        SELECT id, company_name AS name, address AS description, rating AS extra, 'bus' AS category 
        FROM transport_companies 
        WHERE status = 'approved' AND (company_name LIKE ? OR address LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ? '⭐ ' . $row['extra'] : '',
            'category' => 'Bus',
            'icon' => 'fas fa-bus',
            'color' => '#2E7D32',
            'link' => BASE_URL . '/customer/buses.php'
        ];
    }

    // 5. Search Bus Routes
    $stmt = $pdo->prepare("
        SELECT r.id, CONCAT(r.origin, ' → ', r.destination) AS name, 
               tc.company_name AS description, 
               CONCAT(r.base_price, ' ETB') AS extra,
               'route' AS category
        FROM routes r 
        JOIN transport_companies tc ON r.company_id = tc.id
        WHERE r.is_active = 1 AND (r.origin LIKE ? OR r.destination LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Bus Route',
            'icon' => 'fas fa-route',
            'color' => '#00695C',
            'link' => BASE_URL . '/customer/buses.php'
        ];
    }

    // 6. Search Menu Items (restaurant menus)
    $stmt = $pdo->prepare("
        SELECT m.id, m.name, r.name AS description, 
               CONCAT(m.price, ' ETB') AS extra,
               'menu' AS category
        FROM restaurant_menu m 
        JOIN restaurants r ON m.restaurant_id = r.id
        WHERE m.is_available = 1 AND (m.name LIKE ? OR m.description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => 'at ' . ($row['description'] ?? ''),
            'extra' => $row['extra'] ?? '',
            'category' => 'Food Menu',
            'icon' => 'fas fa-hamburger',
            'color' => '#AD1457',
            'link' => BASE_URL . '/customer/restaurants.php'
        ];
    }

    // 7. Search Hotel Menu Items
    $stmt = $pdo->prepare("
        SELECT mi.id, mi.name, h.name AS description, 
               CONCAT(mi.price, ' ETB') AS extra,
               'hotel_menu' AS category
        FROM menu_items mi 
        JOIN hotels h ON mi.hotel_id = h.id
        WHERE mi.is_available = 1 AND (mi.name LIKE ? OR mi.description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => 'at ' . ($row['description'] ?? ''),
            'extra' => $row['extra'] ?? '',
            'category' => 'Hotel Menu',
            'icon' => 'fas fa-concierge-bell',
            'color' => '#6A1B9A',
            'link' => BASE_URL . '/customer/menu.php'
        ];
    }

    // 8. Search Listings (house rent, car rent, etc.)
    $stmt = $pdo->prepare("
        SELECT id, title AS name, location AS description, 
               CONCAT(price, ' ETB') AS extra, type AS category
        FROM listings 
        WHERE status = 'available' AND (title LIKE ? OR location LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $cat_label = str_replace('_', ' ', ucfirst($row['category']));
        $icon = 'fas fa-list';
        $color = '#455A64';
        if (strpos($row['category'], 'house') !== false) {
            $icon = 'fas fa-home';
            $color = '#4E342E';
        }
        if (strpos($row['category'], 'car') !== false) {
            $icon = 'fas fa-car';
            $color = '#283593';
        }

        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => $cat_label,
            'icon' => $icon,
            'color' => $color,
            'link' => BASE_URL . '/customer/listings.php'
        ];
    }

    // 9. Search Health Providers (Doctors, Clinics, Hospitals)
    $stmt = $pdo->prepare("
        SELECT id, name, location AS description, 
               CONCAT(specialization, ' - ', type) AS extra,
               'health' AS category
        FROM health_providers 
        WHERE is_available = 1 AND (name LIKE ? OR location LIKE ? OR specialization LIKE ? OR bio LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Healthcare',
            'icon' => 'fas fa-user-md',
            'color' => '#2E7D32',
            'link' => BASE_URL . '/customer/doctors.php'
        ];
    }

    // 10. Search Dating Profiles
    $stmt = $pdo->prepare("
        SELECT p.id, u.full_name AS name, p.location_name AS description, 
               CONCAT(p.age, ' years') AS extra,
               'dating' AS category
        FROM dating_profiles p 
        JOIN users u ON p.user_id = u.id
        WHERE p.is_active = 1 AND (u.full_name LIKE ? OR p.bio LIKE ? OR p.location_name LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Dating',
            'icon' => 'fas fa-heart',
            'color' => '#E91E63',
            'link' => BASE_URL . '/customer/dating.php'
        ];
    }

    // 11. Search Jobs
    $stmt = $pdo->prepare("
        SELECT id, title AS name, company AS description, 
               CONCAT(salary_range, ' - ', location) AS extra,
               'job' AS category
        FROM job_listings 
        WHERE status = 'active' AND (title LIKE ? OR company LIKE ? OR description LIKE ? OR location LIKE ? OR skills_required LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Job',
            'icon' => 'fas fa-briefcase',
            'color' => '#1565C0',
            'link' => BASE_URL . '/customer/jobs.php'
        ];
    }

    // 12. Search Real Estate Properties
    $stmt = $pdo->prepare("
        SELECT id, title AS name, location AS description, 
               CONCAT(property_type, ' - ', price, ' ETB') AS extra,
        'realestate' AS category
        FROM real_estate_properties 
        WHERE status = 'available' AND (title LIKE ? OR location LIKE ? OR description LIKE ? OR property_type LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Real Estate',
            'icon' => 'fas fa-building',
            'color' => '#5D4037',
            'link' => BASE_URL . '/realestate/index.php'
        ];
    }

    // 13. Search Education Resources
    $stmt = $pdo->prepare("
        SELECT id, title AS name, subject AS description, 
               CONCAT(grade, ' - ', resource_type) AS extra,
               'education' AS category
        FROM education_resources 
        WHERE status = 'active' AND (title LIKE ? OR subject LIKE ? OR description LIKE ? OR grade LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Education',
            'icon' => 'fas fa-graduation-cap',
            'color' => '#1565C0',
            'link' => BASE_URL . '/customer/education.php'
        ];
    }

    // 14. Search Home Services
    $stmt = $pdo->prepare("
        SELECT id, name, description, 
               '' AS extra,
               'homeservice' AS category
        FROM home_service_categories 
        WHERE is_active = 1 AND (name LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Home Service',
            'icon' => 'fas fa-wrench',
            'color' => '#4527A0',
            'link' => BASE_URL . '/customer/home_services.php'
        ];
    }

    // 15. Search Home Service Providers (Professionals)
    $stmt = $pdo->prepare("
        SELECT p.id, p.business_name AS name, 
               c.name AS description, 
               CONCAT(p.rating, ' ⭐') AS extra,
               'homepro' AS category
        FROM home_service_providers p 
        LEFT JOIN home_service_categories c ON p.category_id = c.id
        WHERE p.is_available = 1 AND (p.business_name LIKE ? OR p.description LIKE ? OR p.location LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Home Pro',
            'icon' => 'fas fa-user-cog',
            'color' => '#7B1FA2',
            'link' => BASE_URL . '/customer/home_services.php'
        ];
    }

    // 16. Search Exchange Materials (Textbooks, educational materials)
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               CONCAT(subject, ' - ', grade_level) AS description, 
               CONCAT(price, ' ETB') AS extra,
               'exchange' AS category
        FROM exchange_materials 
        WHERE status = 'available' AND (title LIKE ? OR subject LIKE ? OR description LIKE ? OR grade_level LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Exchange',
            'icon' => 'fas fa-exchange-alt',
            'color' => '#5C6BC0',
            'link' => BASE_URL . '/customer/exchange_material.php'
        ];
    }

    // 17. Search Textbooks
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               CONCAT(author, ' - ', subject) AS description, 
               CONCAT(grade, ' Grade') AS extra,
               'textbook' AS category
        FROM textbooks 
        WHERE status = 'available' AND (title LIKE ? OR author LIKE ? OR subject LIKE ? OR grade LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Textbook',
            'icon' => 'fas fa-book',
            'color' => '#3F51B5',
            'link' => BASE_URL . '/customer/education.php'
        ];
    }

    // 18. Search Community News
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               LEFT(content, 100) AS description, 
               'News' AS extra,
               'news' AS category
        FROM comm_news 
        WHERE status = 'published' AND (title LIKE ? OR content LIKE ?)
        LIMIT 3
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Community News',
            'icon' => 'fas fa-newspaper',
            'color' => '#0288D1',
            'link' => BASE_URL . '/customer/community.php'
        ];
    }

    // 19. Search Community Events
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               location AS description, 
               DATE_FORMAT(event_date, '%M %d, %Y') AS extra,
               'event' AS category
        FROM comm_events 
        WHERE status = 'published' AND (title LIKE ? OR location LIKE ? OR description LIKE ?)
        LIMIT 3
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Community Event',
            'icon' => 'fas fa-calendar-alt',
            'color' => '#00897B',
            'link' => BASE_URL . '/customer/community.php'
        ];
    }

    // 20. Search Community Marketplace
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               description, 
               CONCAT(price, ' ETB') AS extra,
               'marketplace' AS category
        FROM comm_marketplace 
        WHERE status = 'available' AND (title LIKE ? OR description LIKE ? OR category LIKE ?)
        LIMIT 3
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Marketplace',
            'icon' => 'fas fa-store',
            'color' => '#FF5722',
            'link' => BASE_URL . '/customer/community.php'
        ];
    }

    // 21. Search Brokers
    $stmt = $pdo->prepare("
        SELECT b.id, b.business_name AS name, 
               b.location AS description, 
               CONCAT(b.rating, ' ⭐') AS extra,
               'broker' AS category
        FROM brokers b 
        JOIN users u ON b.user_id = u.id
        WHERE u.status = 'active' AND (b.business_name LIKE ? OR b.location LIKE ? OR b.description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Broker',
            'icon' => 'fas fa-user-tie',
            'color' => '#F9A825',
            'link' => BASE_URL . '/broker/dashboard.php'
        ];
    }

    // 22. Search Users (by name - limited for privacy)
    $stmt = $pdo->prepare("
        SELECT id, full_name AS name, 
               email AS description, 
               role AS extra,
               'user' AS category
        FROM users 
        WHERE status = 'active' AND (full_name LIKE ? OR email LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        // Mask email for privacy
        $email_parts = explode('@', $row['description']);
        if (count($email_parts) == 2) {
            $masked_email = substr($email_parts[0], 0, 2) . '***@' . $email_parts[1];
        } else {
            $masked_email = '***';
        }

        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $masked_email,
            'extra' => ucfirst($row['extra']),
            'category' => 'User',
            'icon' => 'fas fa-user',
            'color' => '#607D8B',
            'link' => BASE_URL . '/customer/profile.php'
        ];
    }

    // 23. Search Flights (if available)
    $stmt = $pdo->prepare("
        SELECT id, CONCAT(airline, ' - ', flight_number) AS name, 
               CONCAT(departure_city, ' → ', arrival_city) AS description, 
               CONCAT(base_price, ' ETB') AS extra,
               'flight' AS category
        FROM flights 
        WHERE status = 'active' AND (airline LIKE ? OR flight_number LIKE ? OR departure_city LIKE ? OR arrival_city LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Flight',
            'icon' => 'fas fa-plane',
            'color' => '#6A1B9A',
            'link' => BASE_URL . '/customer/flights.php'
        ];
    }

    // 24. Search Courses (LMS)
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               description, 
               CONCAT(duration, ' hours') AS extra,
               'course' AS category
        FROM courses 
        WHERE status = 'published' AND (title LIKE ? OR description LIKE ? OR instructor LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Course',
            'icon' => 'fas fa-play-circle',
            'color' => '#00BCD4',
            'link' => BASE_URL . '/customer/lms.php'
        ];
    }

    // 25. Search Exams
    $stmt = $pdo->prepare("
        SELECT id, title AS name, 
               subject AS description, 
               CONCAT(duration, ' mins') AS extra,
               'exam' AS category
        FROM exams 
        WHERE status = 'active' AND (title LIKE ? OR subject LIKE ? OR description LIKE ?)
        LIMIT 5
    ");
    $stmt->execute([$search, $search, $search]);
    foreach ($stmt->fetchAll() as $row) {
        $results[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'description' => $row['description'] ?? '',
            'extra' => $row['extra'] ?? '',
            'category' => 'Exam',
            'icon' => 'fas fa-file-alt',
            'color' => '#FF9800',
            'link' => BASE_URL . '/customer/education.php'
        ];
    }

} catch (Exception $e) {
    // Log error but don't expose details to users
    error_log('Search API Error: ' . $e->getMessage());
    $results[] = [
        'id' => 0,
        'name' => 'Search temporarily unavailable',
        'description' => 'Please try again',
        'extra' => '',
        'category' => 'System',
        'icon' => 'fas fa-exclamation-triangle',
        'color' => '#9E9E9E',
        'link' => '#'
    ];
}

// Sort results by relevance (exact match first, then partial match)
usort($results, function ($a, $b) use ($query) {
    $queryLower = strtolower($query);
    $aNameLower = strtolower($a['name']);
    $bNameLower = strtolower($b['name']);

    // Exact match comes first
    if ($aNameLower === $queryLower && $bNameLower !== $queryLower)
        return -1;
    if ($bNameLower === $queryLower && $aNameLower !== $queryLower)
        return 1;

    // Starts with query comes next
    $aStarts = strpos($aNameLower, $queryLower) === 0;
    $bStarts = strpos($bNameLower, $queryLower) === 0;
    if ($aStarts && !$bStarts)
        return -1;
    if ($bStarts && !$aStarts)
        return 1;

    // Otherwise maintain original order
    return 0;
});

// Limit total results to prevent overwhelming response
$results = array_slice($results, 0, 30);

echo json_encode(['results' => $results, 'query' => $query, 'total' => count($results)]);
?>