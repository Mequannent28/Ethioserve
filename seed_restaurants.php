<?php
/**
 * Ethiopian Restaurant Seeder - Full Menu Edition
 * Seeds 15 famous Ethiopian restaurants with 6-8 menu items each (prices in ETB/Birr)
 * Run: http://localhost/ethioserve/seed_restaurants.php
 */
require_once 'includes/db.php';

try {
    // Ensure we have a hotel-role user to own these restaurants
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = 'eth_rest_owner'");
    $stmt->execute();
    $hotel_user_id = $stmt->fetchColumn();

    if (!$hotel_user_id) {
        $stmt = $pdo->prepare("INSERT INTO users (username, password, email, full_name, role) VALUES (?, ?, ?, ?, 'hotel')");
        $stmt->execute([
            'eth_rest_owner',
            password_hash('password123', PASSWORD_DEFAULT),
            'restaurants@ethioserve.com',
            'Ethiopian Restaurant Services'
        ]);
        $hotel_user_id = $pdo->lastInsertId();
        echo "✅ Created restaurant owner user (ID: $hotel_user_id)<br>";
    } else {
        echo "ℹ️ Restaurant owner user already exists (ID: $hotel_user_id)<br>";
    }

    // Ensure categories exist
    $needed_categories = ['Breakfast', 'Lunch', 'Dinner', 'Drinks', 'Desserts'];
    foreach ($needed_categories as $cat) {
        $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt->execute([$cat]);
        if (!$stmt->fetch()) {
            $pdo->prepare("INSERT INTO categories (name) VALUES (?)")->execute([$cat]);
        }
    }

    // Fetch category IDs
    $stmt = $pdo->query("SELECT id, name FROM categories");
    $categories = [];
    while ($row = $stmt->fetch()) {
        $categories[$row['name']] = $row['id'];
    }

    // Food-specific image URLs for menu items
    $food_images = [
        'kitfo' => 'https://images.unsplash.com/photo-1604329760661-e71dc83f8f26?auto=format&fit=crop&w=400&q=80',
        'injera' => 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80',
        'tibs' => 'https://images.unsplash.com/photo-1544025162-d76694265947?auto=format&fit=crop&w=400&q=80',
        'doro_wot' => 'https://images.unsplash.com/photo-1585937421612-70a008356fbe?auto=format&fit=crop&w=400&q=80',
        'shiro' => 'https://images.unsplash.com/photo-1455619452474-d2be8b1e70cd?auto=format&fit=crop&w=400&q=80',
        'coffee' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=400&q=80',
        'juice' => 'https://images.unsplash.com/photo-1534353473418-4cfa6c56fd38?auto=format&fit=crop&w=400&q=80',
        'meat' => 'https://images.unsplash.com/photo-1529694157872-4e0c0f3b238b?auto=format&fit=crop&w=400&q=80',
        'stew' => 'https://images.unsplash.com/photo-1547592180-85f173990554?auto=format&fit=crop&w=400&q=80',
        'bread' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?auto=format&fit=crop&w=400&q=80',
        'salad' => 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&w=400&q=80',
        'fish' => 'https://images.unsplash.com/photo-1467003909585-2f8a72700288?auto=format&fit=crop&w=400&q=80',
        'egg' => 'https://images.unsplash.com/photo-1525351484163-7529414344d8?auto=format&fit=crop&w=400&q=80',
        'pasta' => 'https://images.unsplash.com/photo-1551183053-bf91a1d81141?auto=format&fit=crop&w=400&q=80',
        'dessert' => 'https://images.unsplash.com/photo-1551024601-bec78aea704b?auto=format&fit=crop&w=400&q=80',
        'tea' => 'https://images.unsplash.com/photo-1556679343-c7306c1976bc?auto=format&fit=crop&w=400&q=80',
        'lamb' => 'https://images.unsplash.com/photo-1514516345957-556ca7d90a29?auto=format&fit=crop&w=400&q=80',
        'vegan' => 'https://images.unsplash.com/photo-1543352634-a1c51d9f1fa7?auto=format&fit=crop&w=400&q=80',
        'honey_wine' => 'https://images.unsplash.com/photo-1474722883778-792e7990302f?auto=format&fit=crop&w=400&q=80',
        'beans' => 'https://images.unsplash.com/photo-1536304929831-ee1ca9d44726?auto=format&fit=crop&w=400&q=80',
        'cabbage' => 'https://images.unsplash.com/photo-1556909114-44e3e70034e2?auto=format&fit=crop&w=400&q=80',
        'cheese' => 'https://images.unsplash.com/photo-1486297678162-eb2a19b0a32d?auto=format&fit=crop&w=400&q=80',
    ];

    // ==================== 15 FAMOUS ETHIOPIAN RESTAURANTS ====================
    $restaurants = [
        // 1. Yod Abyssinia
        [
            'name' => 'Yod Abyssinia Traditional Restaurant',
            'desc' => 'Iconic cultural restaurant offering an unforgettable Ethiopian dining experience with traditional music, dance performances, and authentic cuisine since 1997.',
            'addr' => 'Bole Medhanialem, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1511690656952-34342bb7c2f2?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Traditional Ethiopian',
            'hours' => '11:00 AM - 11:00 PM',
            'rating' => 4.8,
            'min_order' => 300,
            'delivery_time' => '40-55 min',
            'items' => [
                ['name' => 'Kitfo - L', 'desc' => 'Minced beef top side with seasoned butter, caper served with local cottage cheese and collard greens on injera.', 'price' => 1304.35, 'cat' => 'Lunch', 'img' => $food_images['kitfo']],
                ['name' => 'Gomen Kitfo - L', 'desc' => 'Local cabbage cooked with seasoned butter & caper served with cottage cheese and injera.', 'price' => 790.51, 'cat' => 'Lunch', 'img' => $food_images['cabbage']],
                ['name' => 'Zemamujet - L', 'desc' => 'Local cabbage with seasoned butter, caper served with false banana bread and cottage cheese.', 'price' => 830.04, 'cat' => 'Lunch', 'img' => $food_images['vegan']],
                ['name' => 'Doro Wott - L', 'desc' => 'Local chicken cooked with onion, red pepper, seasoned butter served with injera and boiled egg.', 'price' => 1304.35, 'cat' => 'Dinner', 'img' => $food_images['doro_wot']],
                ['name' => 'Tegabino - L', 'desc' => 'Ground beans, oil served with injera — a creamy chickpea-based fasting dish.', 'price' => 434.78, 'cat' => 'Lunch', 'img' => $food_images['beans']],
                ['name' => 'Bozena Shero - L', 'desc' => 'Ground beans mixed with meat, seasoned butter served with injera — hearty and flavorful.', 'price' => 869.57, 'cat' => 'Lunch', 'img' => $food_images['shiro']],
                ['name' => 'Yod Special Beyaynetu', 'desc' => 'Grand vegan platter with 7 types of wot (stews) — misir, gomen, atkilt, shiro, and more on fresh injera.', 'price' => 650.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Buna Ceremony Coffee', 'desc' => 'Full traditional Ethiopian coffee ceremony — roasted, ground, and brewed fresh at your table with popcorn.', 'price' => 180.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
            ]
        ],
        // 2. 2000 Habesha Cultural Restaurant
        [
            'name' => '2000 Habesha Cultural Restaurant',
            'desc' => 'A vibrant cultural hub serving authentic Ethiopian dishes with live traditional performances, known for its colorful Mesob dining experience.',
            'addr' => 'Bole Road, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1541510965749-fdd893598387?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Traditional Ethiopian',
            'hours' => '10:00 AM - 12:00 AM',
            'rating' => 4.7,
            'min_order' => 350,
            'delivery_time' => '35-50 min',
            'items' => [
                ['name' => 'Kitfo Special', 'desc' => 'Freshly minced premium beef seasoned with mitmita spice and Ethiopian herbed butter (kibbeh). Served raw, medium, or well done.', 'price' => 750.00, 'cat' => 'Dinner', 'img' => $food_images['kitfo']],
                ['name' => 'Tibs Derek (Dry-Fried Beef)', 'desc' => 'Cubed beef sautéed with rosemary, hot peppers, onions, and tomatoes — served sizzling on a clay plate.', 'price' => 680.00, 'cat' => 'Lunch', 'img' => $food_images['tibs']],
                ['name' => 'Doro Wot', 'desc' => 'Slow-cooked chicken drumstick in rich berbere sauce with hard-boiled egg — Ethiopia\'s national dish.', 'price' => 950.00, 'cat' => 'Dinner', 'img' => $food_images['doro_wot']],
                ['name' => 'Beyaynetu (Fasting Platter)', 'desc' => 'Colorful vegan platter with 8 different wots including misir, shiro, gomen, tikil gomen, atkilt, and azifa.', 'price' => 580.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Gored Gored', 'desc' => 'Cubed raw beef tossed in mitmita spice and awaze pepper paste — for the adventurous palate.', 'price' => 820.00, 'cat' => 'Dinner', 'img' => $food_images['meat']],
                ['name' => 'Shiro Wot', 'desc' => 'Creamy chickpea powder stew simmered with garlic, onions, tomato, and Ethiopian spice blend.', 'price' => 380.00, 'cat' => 'Lunch', 'img' => $food_images['shiro']],
                ['name' => 'Tej (Honey Wine)', 'desc' => 'Traditional Ethiopian honey wine, delicately sweet and refreshing — served in a classic berele glass.', 'price' => 220.00, 'cat' => 'Drinks', 'img' => $food_images['honey_wine']],
                ['name' => 'Spriss (Layered Juice)', 'desc' => 'Beautiful three-layer fresh fruit juice — mango, avocado, and papaya blended separately.', 'price' => 150.00, 'cat' => 'Drinks', 'img' => $food_images['juice']],
            ]
        ],
        // 3. Lucy Restaurant
        [
            'name' => 'Lucy Restaurant & Lounge',
            'desc' => 'Named after the famous fossil, Lucy offers a sophisticated blend of Ethiopian and international cuisine in an elegant upscale setting near Bole.',
            'addr' => 'Atlas Area, Bole, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Ethiopian & International',
            'hours' => '07:00 AM - 11:00 PM',
            'rating' => 4.6,
            'min_order' => 400,
            'delivery_time' => '30-45 min',
            'items' => [
                ['name' => 'Lucy Special Combination', 'desc' => 'A luxurious platter with kitfo, tibs, gomen, and ayib served together on a large injera — chef\'s recommendation.', 'price' => 980.00, 'cat' => 'Dinner', 'img' => $food_images['injera']],
                ['name' => 'Shiro Wot', 'desc' => 'Creamy chickpea flour stew simmered with garlic, tomatoes, and Ethiopian spice blend — a beloved comfort food.', 'price' => 350.00, 'cat' => 'Lunch', 'img' => $food_images['shiro']],
                ['name' => 'Lamb Tibs Firfir', 'desc' => 'Tender lamb pieces sautéed with butter, onion, rosemary and served on torn injera soaked in juices.', 'price' => 880.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Misir Wot (Red Lentil)', 'desc' => 'Split red lentils slow-cooked in berbere sauce — rich, spicy, and deeply satisfying vegan option.', 'price' => 320.00, 'cat' => 'Lunch', 'img' => $food_images['stew']],
                ['name' => 'Derek Tibs - Beef', 'desc' => 'Dry-fried cubed beef seasoned with rosemary, jalapeño, garlic, and onions on a sizzling pan.', 'price' => 750.00, 'cat' => 'Lunch', 'img' => $food_images['tibs']],
                ['name' => 'Enkulal Firfir', 'desc' => 'Ethiopian-style scrambled eggs with tomatoes, onions, green peppers, and shredded injera.', 'price' => 350.00, 'cat' => 'Breakfast', 'img' => $food_images['egg']],
                ['name' => 'Fresh Avocado Juice', 'desc' => 'Thick, creamy layered avocado and mango juice topped with a drizzle of lime — an Ethiopian café classic.', 'price' => 150.00, 'cat' => 'Drinks', 'img' => $food_images['juice']],
            ]
        ],
        // 4. Kategna Restaurant
        [
            'name' => 'Kategna Traditional Food',
            'desc' => 'Famous for the best kategna (toasted injera with kibbeh and berbere) and traditional breakfast in the city. A beloved local favorite.',
            'addr' => 'Multiple Locations, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1547928576-96541f94f997?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Traditional Ethiopian',
            'hours' => '06:30 AM - 10:00 PM',
            'rating' => 4.9,
            'min_order' => 200,
            'delivery_time' => '25-35 min',
            'items' => [
                ['name' => 'Kategna Special', 'desc' => 'Toasted injera generously topped with Ethiopian spiced butter (kibbeh) and berbere — crunchy, warm, and addictive.', 'price' => 280.00, 'cat' => 'Breakfast', 'img' => $food_images['bread']],
                ['name' => 'Chechebsa (Kita Firfir)', 'desc' => 'Shredded pan-fried flatbread mixed with Ethiopian spiced butter and berbere — a hearty traditional breakfast.', 'price' => 320.00, 'cat' => 'Breakfast', 'img' => $food_images['bread']],
                ['name' => 'Enkulal Firfir (Egg Scramble)', 'desc' => 'Ethiopian-style scrambled eggs with tomatoes, onions, green peppers and a touch of berbere with bread.', 'price' => 280.00, 'cat' => 'Breakfast', 'img' => $food_images['egg']],
                ['name' => 'Firfir Be Siga', 'desc' => 'Shredded injera soaked in spicy meat sauce with tender beef chunks — perfect morning energy.', 'price' => 420.00, 'cat' => 'Breakfast', 'img' => $food_images['injera']],
                ['name' => 'Ful Medames', 'desc' => 'Mashed fava beans topped with cumin, olive oil, fresh tomato, egg, jalapeño, and warm bread.', 'price' => 350.00, 'cat' => 'Breakfast', 'img' => $food_images['beans']],
                ['name' => 'Kinche (Cracked Wheat)', 'desc' => 'Ethiopian porridge made with cracked wheat or oats cooked in spiced butter — simple and nutritious.', 'price' => 220.00, 'cat' => 'Breakfast', 'img' => $food_images['stew']],
                ['name' => 'Genfo', 'desc' => 'Thick barley flour porridge with a well of spiced butter and berbere in the center — an ancient Ethiopian breakfast.', 'price' => 250.00, 'cat' => 'Breakfast', 'img' => $food_images['stew']],
                ['name' => 'Buna (Coffee)', 'desc' => 'Freshly roasted and brewed Ethiopian coffee served with popcorn — the morning ritual.', 'price' => 80.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
            ]
        ],
        // 5. Totot Cultural Restaurant
        [
            'name' => 'Totot Cultural Restaurant',
            'desc' => 'Specializing in Southern Ethiopian flavors and cultural shows. Famous for its premium Kitfo and Gurage specialties.',
            'addr' => 'Haya Hulet, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1589302168068-964664d93dc0?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Southern Ethiopian / Gurage',
            'hours' => '10:00 AM - 11:00 PM',
            'rating' => 4.7,
            'min_order' => 350,
            'delivery_time' => '35-50 min',
            'items' => [
                ['name' => 'Totot Special Kitfo', 'desc' => 'Premium-grade minced raw beef with freshly ground mitmita and warm kibbeh — the house specialty.', 'price' => 850.00, 'cat' => 'Dinner', 'img' => $food_images['kitfo']],
                ['name' => 'Gomen Be Siga', 'desc' => 'Tender beef slow-cooked with collard greens, garlic, and ginger — a Southern Ethiopian classic.', 'price' => 580.00, 'cat' => 'Lunch', 'img' => $food_images['cabbage']],
                ['name' => 'Ayib Be Gomen', 'desc' => 'Fresh homemade Ethiopian cottage cheese mixed with finely chopped collard greens and spices.', 'price' => 320.00, 'cat' => 'Lunch', 'img' => $food_images['cheese']],
                ['name' => 'Kurt (Raw Beef Cubes)', 'desc' => 'Fresh cubed raw beef dipped in mitmita and awaze — a delicacy for meat lovers.', 'price' => 920.00, 'cat' => 'Dinner', 'img' => $food_images['meat']],
                ['name' => 'Kitfo Leb Leb', 'desc' => 'Lightly cooked kitfo (medium rare) with cottage cheese and gomen — the perfect balance.', 'price' => 780.00, 'cat' => 'Dinner', 'img' => $food_images['kitfo']],
                ['name' => 'Kocho Be Kitfo', 'desc' => 'False banana bread served alongside special kitfo, ayib, and gomen — authentic Gurage meal.', 'price' => 950.00, 'cat' => 'Lunch', 'img' => $food_images['bread']],
                ['name' => 'Bula (Porridge)', 'desc' => 'Smooth porridge made from false banana starch, served with kibbeh butter — a Gurage staple.', 'price' => 280.00, 'cat' => 'Breakfast', 'img' => $food_images['stew']],
            ]
        ],
        // 6. Four Sisters Restaurant (Gondar)
        [
            'name' => 'Four Sisters Restaurant',
            'desc' => 'Located in historic Gondar, this legendary restaurant is run by four real sisters serving family recipes passed down for generations.',
            'addr' => 'Piazza Area, Gondar',
            'img' => 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Northern Ethiopian / Amhara',
            'hours' => '08:00 AM - 9:30 PM',
            'rating' => 4.9,
            'min_order' => 250,
            'delivery_time' => '30-40 min',
            'items' => [
                ['name' => 'Four Sisters Combo Plate', 'desc' => 'Generous beyaynetu with 9 different wots including misir, shiro, gomen, and special alicha — feeds two.', 'price' => 550.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Tere Siga (Raw Beef)', 'desc' => 'Fresh-cut raw beef served with awaze paste, senafich (mustard), and traditional accompaniments.', 'price' => 720.00, 'cat' => 'Dinner', 'img' => $food_images['meat']],
                ['name' => 'Doro Wot Special', 'desc' => 'Slowly simmered chicken in rich berbere sauce with boiled eggs — the queens favorite recipe.', 'price' => 880.00, 'cat' => 'Dinner', 'img' => $food_images['doro_wot']],
                ['name' => 'Yebeg Alicha', 'desc' => 'Mild lamb stew cooked with turmeric, garlic, and ginger — gentle spiced Northern style.', 'price' => 750.00, 'cat' => 'Lunch', 'img' => $food_images['lamb']],
                ['name' => 'Asa Tibs (Fish Tibs)', 'desc' => 'Fresh Tana Lake fish sautéed with onions, tomatoes, peppers, and rosemary herbs.', 'price' => 620.00, 'cat' => 'Lunch', 'img' => $food_images['fish']],
                ['name' => 'Enjera Be Wot Sampler', 'desc' => 'A sampler plate of key wot, yebeg wot, and misir wot on a large injera — taste everything!', 'price' => 680.00, 'cat' => 'Dinner', 'img' => $food_images['injera']],
                ['name' => 'Tella (Traditional Beer)', 'desc' => 'Homemade traditional Ethiopian barley beer — mildly fermented, smoky, and refreshing.', 'price' => 80.00, 'cat' => 'Drinks', 'img' => $food_images['honey_wine']],
            ]
        ],
        // 7. Makush Art Gallery & Restaurant
        [
            'name' => 'Makush Art Gallery & Restaurant',
            'desc' => 'A stunning restaurant surrounded by Ethiopia\'s largest private art collection. Fine dining meets culture with curated Ethiopian fusion cuisine.',
            'addr' => 'Bole Atlas, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1514362545857-3bc16c4c7d1b?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Ethiopian Fusion & Fine Dining',
            'hours' => '11:30 AM - 10:30 PM',
            'rating' => 4.8,
            'min_order' => 500,
            'delivery_time' => '45-60 min',
            'items' => [
                ['name' => 'Makush Signature Lamb Tibs', 'desc' => 'Tender lamb cubes sautéed with rosemary, jalapeño, and caramelized onions on a sizzling clay pot.', 'price' => 920.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Injera Lasagna', 'desc' => 'Creative fusion: layers of injera with spiced minced beef, tomato sauce, and Ethiopian cheese — a Makush original.', 'price' => 780.00, 'cat' => 'Lunch', 'img' => $food_images['pasta']],
                ['name' => 'Avocado Tartare', 'desc' => 'Fresh avocado layered with spiced kitfo, cherry tomatoes, and microgreens — a modern Ethiopian starter.', 'price' => 520.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Grilled Nile Perch', 'desc' => 'Pan-seared Nile perch fillet with lemon-berbere butter, roasted vegetables, and injera crisps.', 'price' => 1100.00, 'cat' => 'Dinner', 'img' => $food_images['fish']],
                ['name' => 'Mushroom Shiro', 'desc' => 'Elevated version of classic shiro with sautéed wild mushrooms, truffle oil, and fresh herbs.', 'price' => 480.00, 'cat' => 'Lunch', 'img' => $food_images['shiro']],
                ['name' => 'Tibs Salad Bowl', 'desc' => 'Warm beef tibs over mixed greens, cherry tomatoes, avocado, and house vinaigrette dressing.', 'price' => 620.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Ethiopian Honey Wine Cocktail', 'desc' => 'Signature cocktail mixing tej (honey wine) with fresh lime, ginger, and sparkling water.', 'price' => 350.00, 'cat' => 'Drinks', 'img' => $food_images['honey_wine']],
                ['name' => 'Bunna Brulee', 'desc' => 'Ethiopian coffee-infused crème brûlée with caramelized sugar top — a fusion dessert masterpiece.', 'price' => 420.00, 'cat' => 'Desserts', 'img' => $food_images['dessert']],
            ]
        ],
        // 8. Ben Abeba Restaurant (Lalibela)
        [
            'name' => 'Ben Abeba Restaurant',
            'desc' => 'An architectural marvel perched on a cliff in Lalibela, offering panoramic views and an eclectic mix of Ethiopian and Scottish cuisine.',
            'addr' => 'Hilltop, Lalibela',
            'img' => 'https://images.unsplash.com/photo-1466978913421-dad2ebd01d17?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Ethiopian & Scottish Fusion',
            'hours' => '07:30 AM - 9:00 PM',
            'rating' => 4.8,
            'min_order' => 300,
            'delivery_time' => '30-45 min',
            'items' => [
                ['name' => 'Mixed Wot Platter', 'desc' => 'A colorful spread of 6 traditional stews on injera — includes yebeg wot, misir, gomen, and more.', 'price' => 480.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Lamb Shank in Berbere', 'desc' => 'Slow-roasted lamb shank glazed with berbere-infused sauce, served with roasted vegetables and injera.', 'price' => 750.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Highland Shepherd Pie', 'desc' => 'Scottish classic made with spiced Ethiopian minced beef, topped with creamy mashed potatoes.', 'price' => 550.00, 'cat' => 'Lunch', 'img' => $food_images['stew']],
                ['name' => 'Ben Abeba Breakfast', 'desc' => 'Full Ethiopian-Scottish breakfast with ful, eggs, toast, sausage, fresh juice, and coffee.', 'price' => 420.00, 'cat' => 'Breakfast', 'img' => $food_images['egg']],
                ['name' => 'Roasted Beet Salad', 'desc' => 'Roasted beetroot with goat cheese crumble, walnuts, and honey-lemon dressing.', 'price' => 380.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Vegetarian Beyaynetu', 'desc' => 'Full fasting platter with shiro, misir, azifa, gomen, atkilt, and tikil gomen on injera.', 'price' => 350.00, 'cat' => 'Lunch', 'img' => $food_images['vegan']],
                ['name' => 'Spris (Layered Juice)', 'desc' => 'Three layers of fresh fruit juices — mango, avocado, and papaya, beautifully layered.', 'price' => 120.00, 'cat' => 'Drinks', 'img' => $food_images['juice']],
            ]
        ],
        // 9. Dashen Traditional Restaurant
        [
            'name' => 'Dashen Traditional Restaurant',
            'desc' => 'Named after Ethiopia\'s highest mountain, Dashen serves hearty traditional Ethiopian food in a warm, family-style mesob dining setup.',
            'addr' => 'Kazanchis, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Traditional Ethiopian',
            'hours' => '09:00 AM - 10:30 PM',
            'rating' => 4.5,
            'min_order' => 250,
            'delivery_time' => '30-45 min',
            'items' => [
                ['name' => 'Zilzil Tibs', 'desc' => 'Tender strips of beef sautéed with onions, green peppers, and rosemary — served on a hot mitad.', 'price' => 620.00, 'cat' => 'Lunch', 'img' => $food_images['tibs']],
                ['name' => 'Misir Wot (Red Lentil)', 'desc' => 'Split red lentils simmered in rich berbere sauce — Ethiopia\'s most popular vegan dish.', 'price' => 280.00, 'cat' => 'Lunch', 'img' => $food_images['stew']],
                ['name' => 'Key Wot (Spicy Beef Stew)', 'desc' => 'Cubed beef slow-cooked in a thick berbere sauce with onions and kibbeh butter.', 'price' => 720.00, 'cat' => 'Dinner', 'img' => $food_images['stew']],
                ['name' => 'Alicha Yebeg (Mild Lamb)', 'desc' => 'Tender lamb cubes in a mild, turmeric-based sauce with potatoes and carrots.', 'price' => 680.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Azifa (Lentil Salad)', 'desc' => 'Cool green lentil salad with mustard, jalapeño, onion, and lemon juice — refreshing side.', 'price' => 180.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Timatim Salata', 'desc' => 'Fresh tomato salad with onion, green peppers, lemon juice, and olive oil — Ethiopian classic.', 'price' => 150.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Macchiato (Ethiopian Style)', 'desc' => 'Strong Ethiopian espresso topped with steamed milk foam — the daily ritual of every Ethiopian.', 'price' => 60.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
            ]
        ],
        // 10. Habesha Restaurant
        [
            'name' => 'Habesha Restaurant',
            'desc' => 'One of Addis Ababa\'s most popular dining spots for both locals and visitors, offering generous portions and an authentic atmosphere.',
            'addr' => 'Bole Rwanda, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Traditional Ethiopian',
            'hours' => '10:00 AM - 11:00 PM',
            'rating' => 4.6,
            'min_order' => 280,
            'delivery_time' => '30-45 min',
            'items' => [
                ['name' => 'Yebeg Tibs (Lamb Tibs)', 'desc' => 'Juicy cubes of lamb sautéed with jalapeños, onions, and tomatoes, seasoned with Ethiopian herbs.', 'price' => 780.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Firfir Be Siga', 'desc' => 'Shredded injera soaked in spicy berbere-based meat sauce with tender beef — hearty breakfast.', 'price' => 350.00, 'cat' => 'Breakfast', 'img' => $food_images['injera']],
                ['name' => 'Dulet', 'desc' => 'Minced tripe, liver, and lean beef sautéed with jalapeño, onion, and Ethiopian spices — a delicacy.', 'price' => 650.00, 'cat' => 'Dinner', 'img' => $food_images['meat']],
                ['name' => 'Quanta Firfir', 'desc' => 'Dried beef jerky (quanta) rehydrated and sautéed with injera, berbere sauce, and kibbeh butter.', 'price' => 520.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Shekla Tibs', 'desc' => 'Hot stone-grilled beef or lamb tibs served sizzling on a clay plate with onions and peppers.', 'price' => 850.00, 'cat' => 'Dinner', 'img' => $food_images['tibs']],
                ['name' => 'Fossolia (Green Beans)', 'desc' => 'Fresh green beans and carrots sautéed with garlic, onion, and turmeric — light fasting dish.', 'price' => 220.00, 'cat' => 'Lunch', 'img' => $food_images['vegan']],
                ['name' => 'Tena Adam Tea (Rue Tea)', 'desc' => 'Traditional Ethiopian herbal tea brewed with rue herb, known for health benefits and aromatic flavor.', 'price' => 50.00, 'cat' => 'Drinks', 'img' => $food_images['tea']],
            ]
        ],
        // 11. Saro-Maria Hotel Restaurant
        [
            'name' => 'Saro-Maria Hotel Restaurant',
            'desc' => 'An upscale hotel restaurant offering a refined Ethiopian dining experience with international flair, located in the heart of Bole.',
            'addr' => 'Bole Sub-City, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1559339352-11d035aa65de?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Ethiopian & Continental',
            'hours' => '06:00 AM - 11:30 PM',
            'rating' => 4.7,
            'min_order' => 450,
            'delivery_time' => '40-55 min',
            'items' => [
                ['name' => 'Saro-Maria Doro Wot', 'desc' => 'Premium chicken stew slow-simmered for 6 hours in berbere sauce, served with 2 hardboiled eggs and injera.', 'price' => 950.00, 'cat' => 'Dinner', 'img' => $food_images['doro_wot']],
                ['name' => 'Ful Medames', 'desc' => 'Mashed fava beans with cumin, olive oil, fresh tomatoes, green chili, egg, and warm bread.', 'price' => 320.00, 'cat' => 'Breakfast', 'img' => $food_images['beans']],
                ['name' => 'Continental Breakfast', 'desc' => 'Toast, butter, jam, fresh fruits, scrambled eggs, sausage, and freshly brewed coffee.', 'price' => 450.00, 'cat' => 'Breakfast', 'img' => $food_images['egg']],
                ['name' => 'Goulash (Ethiopian Style)', 'desc' => 'Slow-cooked beef and vegetable stew with Ethiopian spices, served with fresh bread.', 'price' => 680.00, 'cat' => 'Lunch', 'img' => $food_images['stew']],
                ['name' => 'Tibs Special Platter', 'desc' => 'A combination of beef, lamb, and chicken tibs served on a sizzling platter with vegetables.', 'price' => 1200.00, 'cat' => 'Dinner', 'img' => $food_images['tibs']],
                ['name' => 'Caesar Salad', 'desc' => 'Crispy romaine lettuce, parmesan, croutons with house-made dressing — Continental classic.', 'price' => 380.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Fresh Mango Smoothie', 'desc' => 'Thick and creamy blended mango smoothie with a hint of honey — refreshing and naturally sweet.', 'price' => 130.00, 'cat' => 'Drinks', 'img' => $food_images['juice']],
            ]
        ],
        // 12. Tomoca Coffee
        [
            'name' => 'Tomoca Coffee',
            'desc' => 'Ethiopia\'s most legendary coffee house since 1953. The birthplace of premium Ethiopian coffee culture, serving the finest Arabica beans.',
            'addr' => 'Wavel Street, Piazza, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Coffee & Light Bites',
            'hours' => '06:00 AM - 8:00 PM',
            'rating' => 4.9,
            'min_order' => 50,
            'delivery_time' => '15-25 min',
            'items' => [
                ['name' => 'Tomoca Special Roast', 'desc' => 'Single-origin, dark-roasted Ethiopian Arabica coffee — the legendary Tomoca blend since 1953.', 'price' => 80.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
                ['name' => 'Macchiato Doppio', 'desc' => 'Double-shot Ethiopian espresso with a touch of steamed milk — rich, bold, and aromatic.', 'price' => 70.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
                ['name' => 'Kolo & Popcorn Platter', 'desc' => 'Traditional roasted barley and peanut mix served with popcorn — the classic Ethiopian coffee companion.', 'price' => 100.00, 'cat' => 'Breakfast', 'img' => $food_images['bread']],
                ['name' => 'Cappuccino', 'desc' => 'Perfectly frothed Ethiopian Arabica cappuccino with velvety milk foam and cocoa dust.', 'price' => 90.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
                ['name' => 'Espresso Con Panna', 'desc' => 'Rich Ethiopian espresso shot topped with fresh whipped cream — a classic indulgence.', 'price' => 100.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
                ['name' => 'Bunna Spris', 'desc' => 'Layered iced coffee drink with milk, espresso, and chocolate — Tomoca\'s refreshing take.', 'price' => 120.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
                ['name' => 'Himbasha (Sweet Bread)', 'desc' => 'Traditional Ethiopian celebration bread with cardamom — lightly sweet and perfect with coffee.', 'price' => 150.00, 'cat' => 'Desserts', 'img' => $food_images['bread']],
            ]
        ],
        // 13. Addis in Dar Restaurant
        [
            'name' => 'Addis in Dar Restaurant',
            'desc' => 'A modern Ethiopian restaurant bringing Addis Ababa flavors with a contemporary twist. Popular among young professionals and food enthusiasts.',
            'addr' => 'Sarbet, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1590846406792-0adc7f938f1d?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Modern Ethiopian',
            'hours' => '11:00 AM - 11:00 PM',
            'rating' => 4.5,
            'min_order' => 300,
            'delivery_time' => '30-40 min',
            'items' => [
                ['name' => 'Derek Tibs Special', 'desc' => 'Generously seasoned dry-fried beef cubes with rosemary, garlic, and jalapeño on a sizzling clay mitad.', 'price' => 720.00, 'cat' => 'Lunch', 'img' => $food_images['tibs']],
                ['name' => 'Atkilt Wot (Cabbage & Potato)', 'desc' => 'Mild-spiced cabbage, carrots, and potatoes cooked with turmeric and garlic — a fasting favorite.', 'price' => 250.00, 'cat' => 'Lunch', 'img' => $food_images['cabbage']],
                ['name' => 'Awaze Tibs', 'desc' => 'Beef cubes marinated in awaze (chili paste), then pan-fried with onions and green peppers.', 'price' => 680.00, 'cat' => 'Dinner', 'img' => $food_images['tibs']],
                ['name' => 'Yatakilt Kilkil', 'desc' => 'Mixed vegetables (potato, carrot, green beans) cooked mild with turmeric and garlic.', 'price' => 280.00, 'cat' => 'Lunch', 'img' => $food_images['vegan']],
                ['name' => 'Special Kitfo Combo', 'desc' => 'Kitfo served with kocho (false banana bread), ayib, gomen, and awaze on the side.', 'price' => 880.00, 'cat' => 'Dinner', 'img' => $food_images['kitfo']],
                ['name' => 'Suf Fitfit', 'desc' => 'Shredded injera tossed in sunflower seed sauce — a unique fasting dish from Eastern Ethiopia.', 'price' => 350.00, 'cat' => 'Lunch', 'img' => $food_images['injera']],
                ['name' => 'Kenetto', 'desc' => 'Fermented honey-based drink with herbs — a milder, refreshing version of Tej.', 'price' => 160.00, 'cat' => 'Drinks', 'img' => $food_images['honey_wine']],
            ]
        ],
        // 14. Lime Tree Restaurant
        [
            'name' => 'Lime Tree Café & Restaurant',
            'desc' => 'A beloved Addis Ababa gem known for its garden seating, healthy options, and a perfect fusion of Ethiopian and European cuisines.',
            'addr' => 'Bole Medhanialem, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1537047902294-62a40c20a6ae?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Ethiopian-European Fusion',
            'hours' => '07:00 AM - 10:00 PM',
            'rating' => 4.6,
            'min_order' => 350,
            'delivery_time' => '35-50 min',
            'items' => [
                ['name' => 'Lime Tree Breakfast Platter', 'desc' => 'Mix of ful, scrambled eggs, fresh bread, avocado, and seasonal fruits — the ultimate brunch.', 'price' => 420.00, 'cat' => 'Breakfast', 'img' => $food_images['egg']],
                ['name' => 'Grilled Fish with Awaze', 'desc' => 'Fresh Nile tilapia grilled to perfection, topped with awaze (chili paste) and served with salad.', 'price' => 650.00, 'cat' => 'Lunch', 'img' => $food_images['fish']],
                ['name' => 'Garden Pasta', 'desc' => 'Penne pasta with roasted vegetables, fresh basil pesto, and parmesan cheese — garden fresh.', 'price' => 480.00, 'cat' => 'Lunch', 'img' => $food_images['pasta']],
                ['name' => 'Chicken Wrap', 'desc' => 'Grilled chicken breast with fresh vegetables, avocado, and garlic sauce in a warm tortilla wrap.', 'price' => 420.00, 'cat' => 'Lunch', 'img' => $food_images['bread']],
                ['name' => 'Quinoa Bowl', 'desc' => 'Nutritious quinoa with roasted sweet potato, avocado, chickpeas, greens, and tahini dressing.', 'price' => 520.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Carrot Cake', 'desc' => 'Moist carrot cake with cream cheese frosting, walnuts, and a sprinkle of cinnamon.', 'price' => 280.00, 'cat' => 'Desserts', 'img' => $food_images['dessert']],
                ['name' => 'Fresh Strawberry Juice', 'desc' => 'Cold-pressed fresh strawberry juice blended with a squeeze of lime — seasonal and refreshing.', 'price' => 140.00, 'cat' => 'Drinks', 'img' => $food_images['juice']],
            ]
        ],
        // 15. Castelli's Restaurant
        [
            'name' => "Castelli's Italian-Ethiopian Restaurant",
            'desc' => 'An Addis Ababa institution since 1948, Castelli\'s blends Italian and Ethiopian gastronomy in a vintage, elegantly decorated dining hall.',
            'addr' => 'Piazza, Churchill Avenue, Addis Ababa',
            'img' => 'https://images.unsplash.com/photo-1550966871-3ed3cdb51f3a?auto=format&fit=crop&w=800&q=80',
            'cuisine' => 'Italian-Ethiopian',
            'hours' => '12:00 PM - 10:00 PM',
            'rating' => 4.7,
            'min_order' => 600,
            'delivery_time' => '45-60 min',
            'items' => [
                ['name' => 'Spaghetti alla Castelli', 'desc' => 'Classic Italian spaghetti with a unique Ethiopian twist — house-made sauce with berbere-infused meatballs.', 'price' => 580.00, 'cat' => 'Lunch', 'img' => $food_images['pasta']],
                ['name' => 'Veal Scaloppine', 'desc' => 'Thin-sliced veal in white wine and lemon butter sauce, served with sautéed vegetables.', 'price' => 1100.00, 'cat' => 'Dinner', 'img' => $food_images['meat']],
                ['name' => 'Lasagna al Forno', 'desc' => 'Classic layered lasagna with bolognese, béchamel, and parmesan — baked to golden perfection.', 'price' => 750.00, 'cat' => 'Lunch', 'img' => $food_images['pasta']],
                ['name' => 'Grilled Lamb Chops', 'desc' => 'Premium lamb chops grilled medium-rare with rosemary and garlic, served with mint sauce.', 'price' => 1350.00, 'cat' => 'Dinner', 'img' => $food_images['lamb']],
                ['name' => 'Minestrone Soup', 'desc' => 'Hearty Italian vegetable soup with pasta, beans, and fresh herbs — a perfect starter.', 'price' => 350.00, 'cat' => 'Lunch', 'img' => $food_images['stew']],
                ['name' => 'Caprese Salad', 'desc' => 'Fresh mozzarella, ripe tomatoes, and basil drizzled with extra virgin olive oil and balsamic.', 'price' => 420.00, 'cat' => 'Lunch', 'img' => $food_images['salad']],
                ['name' => 'Tiramisu Habesha Style', 'desc' => 'Classic Italian tiramisu infused with Ethiopian coffee — a perfect fusion dessert.', 'price' => 380.00, 'cat' => 'Desserts', 'img' => $food_images['dessert']],
                ['name' => 'Espresso Italiano', 'desc' => 'Authentic Italian espresso made with premium Ethiopian Arabica beans — bold and smooth.', 'price' => 90.00, 'cat' => 'Drinks', 'img' => $food_images['coffee']],
            ]
        ],
    ];

    $inserted_restaurants = 0;
    $inserted_items = 0;

    foreach ($restaurants as $r) {
        // Check if restaurant already exists
        $stmt = $pdo->prepare("SELECT id FROM hotels WHERE name = ?");
        $stmt->execute([$r['name']]);
        $existing = $stmt->fetch();

        if ($existing) {
            $hotel_id = $existing['id'];
            echo "ℹ️ Restaurant '<strong>{$r['name']}</strong>' already exists (ID: $hotel_id)<br>";
        } else {
            // Insert the restaurant
            $stmt = $pdo->prepare("INSERT INTO hotels (user_id, name, description, location, image_url, cuisine_type, opening_hours, status, rating, min_order, delivery_time) VALUES (?, ?, ?, ?, ?, ?, ?, 'approved', ?, ?, ?)");
            $stmt->execute([
                $hotel_user_id,
                $r['name'],
                $r['desc'],
                $r['addr'],
                $r['img'],
                $r['cuisine'],
                $r['hours'],
                $r['rating'],
                $r['min_order'],
                $r['delivery_time']
            ]);
            $hotel_id = $pdo->lastInsertId();
            $inserted_restaurants++;
            echo "✅ Added restaurant: <strong>{$r['name']}</strong> (ID: $hotel_id)<br>";
        }

        // Insert menu items
        foreach ($r['items'] as $item) {
            // Check if menu item already exists for this hotel
            $stmt = $pdo->prepare("SELECT id FROM menu_items WHERE hotel_id = ? AND name = ?");
            $stmt->execute([$hotel_id, $item['name']]);

            if (!$stmt->fetch()) {
                $cat_id = $categories[$item['cat']] ?? null;
                $item_img = $item['img'] ?? $r['img'];
                $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, image_url, is_available) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $stmt->execute([
                    $hotel_id,
                    $cat_id,
                    $item['name'],
                    $item['desc'],
                    $item['price'],
                    $item_img
                ]);
                $inserted_items++;
                echo "   🍽️ {$item['name']} — <strong>" . number_format($item['price'], 2) . " Birr</strong><br>";
            } else {
                echo "   ℹ️ '{$item['name']}' already exists — skipped.<br>";
            }
        }
        echo "<hr>";
    }

    echo "<br><div style='background:#1a1a2e;color:#e94560;padding:20px;border-radius:10px;font-size:18px;text-align:center;'>";
    echo "🎉 <strong>Seeding Complete!</strong><br><br>";
    echo "🏪 Restaurants added: <strong>$inserted_restaurants</strong><br>";
    echo "🍽️ Menu items added: <strong>$inserted_items</strong><br>";
    echo "📍 Total items per restaurant: <strong>6-8</strong><br>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background:#ff4444;color:white;padding:20px;border-radius:10px;'>";
    echo "❌ <strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>