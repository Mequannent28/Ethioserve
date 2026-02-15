<?php
/**
 * Dynamic manifest.json generator
 * Serves the correct paths based on environment (local vs production)
 */
require_once __DIR__ . '/includes/config.php';

header('Content-Type: application/manifest+json');

$base = BASE_URL;

$manifest = [
    "name" => "EthioServe - Premium Platform",
    "short_name" => "EthioServe",
    "description" => "Food delivery, hotel booking, transport & broker services across Ethiopia.",
    "start_url" => "$base/customer/index.php",
    "scope" => "$base/",
    "display" => "standalone",
    "orientation" => "portrait",
    "background_color" => "#ffffff",
    "theme_color" => "#1B5E20",
    "categories" => ["food", "travel", "shopping", "lifestyle"],
    "icons" => [],
    "shortcuts" => [
        [
            "name" => "Restaurants",
            "short_name" => "Food",
            "description" => "Browse Restaurants",
            "url" => "$base/customer/restaurants.php",
            "icons" => [["src" => "$base/assets/icons/icon-96x96.png", "sizes" => "96x96"]]
        ],
        [
            "name" => "Hotels",
            "short_name" => "Hotels",
            "description" => "Book Hotels",
            "url" => "$base/customer/listings.php",
            "icons" => [["src" => "$base/assets/icons/icon-96x96.png", "sizes" => "96x96"]]
        ],
        [
            "name" => "Taxi",
            "short_name" => "Taxi",
            "description" => "Book a Ride",
            "url" => "$base/customer/taxi.php",
            "icons" => [["src" => "$base/assets/icons/icon-96x96.png", "sizes" => "96x96"]]
        ]
    ]
];

$sizes = [72, 96, 128, 144, 152, 192, 384, 512];
foreach ($sizes as $size) {
    $manifest["icons"][] = [
        "src" => "$base/assets/icons/icon-{$size}x{$size}.png",
        "sizes" => "{$size}x{$size}",
        "type" => "image/png",
        "purpose" => "any maskable"
    ];
}

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>