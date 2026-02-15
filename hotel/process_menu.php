<?php
session_start();
require_once '../includes/db.php';

// Auth Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hotel') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
$hotel_id = $hotel['id'];

$action = $_GET['action'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];

        $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, image_url) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$hotel_id, $category_id, $name, $description, $price, $image_url]);

        header("Location: menu_management.php?success=added");
        exit();

    } elseif ($action === 'edit') {
        $id = $_POST['id'];
        $name = $_POST['name'];
        $category_id = $_POST['category_id'];
        $price = $_POST['price'];
        $description = $_POST['description'];
        $image_url = $_POST['image_url'];
        $is_available = isset($_POST['is_available']) ? 1 : 0;

        // Ensure the item belongs to this hotel
        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, category_id = ?, price = ?, description = ?, image_url = ?, is_available = ? WHERE id = ? AND hotel_id = ?");
        $stmt->execute([$name, $category_id, $price, $description, $image_url, $is_available, $id, $hotel_id]);

        header("Location: menu_management.php?success=updated");
        exit();
<<<<<<< HEAD
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

        header("Location: menu_management.php?success=imported");
        exit();
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

                $stmt = $pdo->prepare("INSERT INTO menu_items (hotel_id, category_id, name, description, price, is_available) VALUES (?, ?, ?, ?, ?, 1)");
                $count = 0;

                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    // Item, Category, Price, Description
                    $name = $data[0] ?? '';
                    $cat_name = $data[1] ?? 'Main Course';
                    $price = (float) ($data[2] ?? 0);
                    $desc = $data[3] ?? '';

                    if (!empty($name)) {
                        $cat_id = $cat_map[strtolower($cat_name)] ?? null;
                        if (!$cat_id) {
                            // Create category if not exists
                            $c_stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
                            $c_stmt->execute([$cat_name]);
                            $cat_id = $pdo->lastInsertId();
                            $cat_map[strtolower($cat_name)] = $cat_id;
                        }

                        $stmt->execute([$hotel_id, $cat_id, $name, $desc, $price]);
                        $count++;
                    }
                }
                fclose($handle);
                header("Location: menu_management.php?success=imported&count=$count");
                exit();
            }
        }
        header("Location: menu_management.php?error=upload_failed");
        exit();
=======
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
    }
}

if ($action === 'delete') {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ? AND hotel_id = ?");
    $stmt->execute([$id, $hotel_id]);

    header("Location: menu_management.php?success=deleted");
    exit();
}
?>