<?php
/**
 * EthioServe — Live Search API
 * Returns JSON results from restaurants, hotels, taxis, buses, listings, and menu items
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

} catch (Exception $e) {
    // Silently return empty on error
}

echo json_encode(['results' => $results, 'query' => $query]);
?>