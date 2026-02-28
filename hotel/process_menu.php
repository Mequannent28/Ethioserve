<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('hotel');
$user_id = getCurrentUserId();

// Get hotel for this user
$stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
if (!$hotel)
    die("Hotel record not found.");
$hotel_id = $hotel['id'];

// CSRF Check for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        die("CSRF token validation failed. Please try again.");
    }
}

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $tax_rate = $_POST['tax_rate'] ?? 15;
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];

        // Handle File Upload
        $uploaded_image = handleImageUpload('image_file');
        if ($uploaded_image) {
            $image_url = $uploaded_image;
        }

        $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, tax_rate, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hotel_id, $category_id, $name, $description, $price, $tax_rate, $image_url]);

        redirectWithMessage('menu_management.php', 'success', 'Menu item added successfully!');

    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $tax_rate = $_POST['tax_rate'] ?? 15;
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // Handle File Upload
        $uploaded_image = handleImageUpload('image_file');
        if ($uploaded_image) {
            $image_url = $uploaded_image;
        }

        // Ensure the item belongs to this hotel
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, category_id = ?, price = ?, tax_rate = ?, description = ?, image_url = ?, is_available = ? WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$name, $category_id, $price, $tax_rate, $description, $image_url, $is_available, $id, $hotel_id]);

        redirectWithMessage('menu_management.php', 'success', 'Menu item updated successfully!');
    } elseif ($action === 'import_demo') {
        // Ensure standard categories exist
        $std_categories = ['Breakfast', 'Lunch', 'Main Course', 'Appetizer', 'Drinks', 'Dessert'];
        $cat_map = [];

        foreach ($std_categories as $cat_name) {
            $stmt = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
            $stmt->execute([$cat_name]);
            $cat = $stmt->fetch();
            if (!$cat) {
                $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                $stmt->execute([$cat_name]);
                $cat_map[$cat_name] = $pdo->lastInsertId();
            } else {
                $cat_map[$cat_name] = $cat['id'];
            }
        }

        $demo_items = [
            ['Injera with Firfir', 'Spicy beef firfir served with fresh injera.', 350.00, 'Breakfast', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500'],
            ['Special Kitfo', 'Premium minced beef seasoned with mitmita.', 1200.00, 'Main Course', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500'],
            ['Doro Wot', 'Traditional Ethiopian spicy chicken stew.', 950.00, 'Main Course', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500'],
            ['Beyaynetu', 'Assortment of vegan stews on injera.', 450.00, 'Main Course', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500'],
            ['Shiro Tegabino', 'Thick chickpea stew in a traditional clay pot.', 320.00, 'Lunch', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500'],
            ['Ethio Coffee', 'Traditional roasted coffee.', 60.00, 'Drinks', 'https://images.unsplash.com/photo-1541518763669-27fef04b14ea?w=500']
        ];

        $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        foreach ($demo_items as $item) {
            $cat_id = $cat_map[$item[3]] ?? $cat_map['Main Course'];
            $stmt->execute([$hotel_id, $cat_id, $item[0], $item[1], $item[2], $item[4]]);
        }

        redirectWithMessage('menu_management.php', 'success', 'Demo menu imported successfully!');
    } elseif ($action === 'import_csv') {
        if (isset($_FILES['menu_csv']) && $_FILES['menu_csv']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['menu_csv']['tmp_name'];
            if (($handle = fopen($file, "r")) !== FALSE) {
                // Skip header
                fgetcsv($handle, 1000, ",");

                // Get categories map
                $stmt = $pdo->query("SELECT id, name FROM categories");
                $cat_rows = $stmt->fetchAll();
                $cat_map = [];
                foreach ($cat_rows as $row) {
                    $cat_map[strtolower($row['name'])] = $row['id'];
                }

                $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, tax_rate, is_available) VALUES (?, ?, ?, ?, ?, ?, 1)");
                $count = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Item, Category, Price, Description, TaxCode
                    $name = $data[0] ?? '';
                    $cat_name = $data[1] ?? 'Main Course';
                    $price = (float) ($data[2] ?? 0);
                    $desc = $data[3] ?? '';
                    $tax_code = $data[4] ?? 0;

                    $tax_rate = ($tax_code == 1) ? 15.00 : 0.00;

                    if (!empty($name)) {
                        $cat_id = $cat_map[strtolower($cat_name)] ?? null;
                        if (!$cat_id) {
                            // Create category if not exists
                            $c_stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                            $c_stmt->execute([$cat_name]);
                            $cat_id = $pdo->lastInsertId();
                            $cat_map[strtolower($cat_name)] = $cat_id;
                        }

                        $stmt->execute([$hotel_id, $cat_id, $name, $desc, $price, $tax_rate]);
                        $count++;
                    }
                }
                fclose($handle);
                redirectWithMessage('menu_management.php', 'success', "Successfully imported $count items from CSV!");
            }
        }
        redirectWithMessage('menu_management.php', 'error', 'Error uploading the CSV file.');
    }
}

if ($action === 'delete') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);

    redirectWithMessage('menu_management.php', 'success', 'Menu item deleted successfully!');
}
?>