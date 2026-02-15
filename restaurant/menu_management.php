<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('restaurant');

$user_id = getCurrentUserId();

$stmt = $pdo->prepare("SELECT * FROM restaurants WHERE user_id = ?");
$stmt->execute([$user_id]);
$restaurant = $stmt->fetch();

if (!$restaurant) {
    header("Location: dashboard.php");
    exit();
}

$restaurant_id = $restaurant['id'];

// Handle add/edit/delete menu item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    if (isset($_POST['add_item'])) {
        $name = sanitize($_POST['name']);
        $description = sanitize($_POST['description'] ?? '');
        $price = (float) $_POST['price'];
        $category = sanitize($_POST['category']);

        $stmt = $pdo->prepare("INSERT INTO restaurant_menu (restaurant_id, name, description, price, category) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$restaurant_id, $name, $description, $price, $category]);
        redirectWithMessage('menu_management.php', 'success', 'Menu item added!');
    }

    if (isset($_POST['delete_item'])) {
        $item_id = (int) $_POST['item_id'];
        $stmt = $pdo->prepare("DELETE FROM restaurant_menu WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$item_id, $restaurant_id]);
        redirectWithMessage('menu_management.php', 'success', 'Menu item deleted!');
    }

    if (isset($_POST['toggle_item'])) {
        $item_id = (int) $_POST['item_id'];
        $stmt = $pdo->prepare("UPDATE restaurant_menu SET is_available = NOT is_available WHERE id = ? AND restaurant_id = ?");
        $stmt->execute([$item_id, $restaurant_id]);
        redirectWithMessage('menu_management.php', 'success', 'Availability toggled!');
    }

    if (isset($_POST['import_demo_menu'])) {
        $demo_items = [
            ['Injera with Firfir', 'Spicy beef firfir served with fresh injera and boiled egg.', 350.00, 'Breakfast'],
            ['Special Kitfo', 'Premium minced beef seasoned with mitmita and niter kibbeh.', 1200.00, 'Main Course'],
            ['Doro Wot', 'Traditional Ethiopian spicy chicken stew with egg.', 950.00, 'Main Course'],
            ['Beyaynetu', 'Large assortment of vegan stews and salads on injera.', 450.00, 'Main Course'],
            ['Shiro Tegabino', 'Thick chickpea stew served in a traditional clay pot.', 320.00, 'Lunch'],
            ['Ethio Coffee', 'Traditional roasted coffee with aromatic herbs.', 60.00, 'Drinks'],
            ['Habesha Beer', 'Cold premium Ethiopian beer.', 120.00, 'Drinks']
        ];

        $stmt = $pdo->prepare("INSERT INTO restaurant_menu (restaurant_id, name, description, price, category) VALUES (?, ?, ?, ?, ?)");
        foreach ($demo_items as $item) {
            $stmt->execute([$restaurant_id, $item[0], $item[1], $item[2], $item[3]]);
        }
        redirectWithMessage('menu_management.php', 'success', 'Demo menu imported successfully!');
    }

    if (isset($_FILES['menu_csv']) && $_FILES['menu_csv']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['menu_csv']['tmp_name'];
        if (($handle = fopen($file, "r")) !== FALSE) {
            // Skip header if exists
            $header = fgetcsv($handle, 1000, ",");

            $stmt = $pdo->prepare("INSERT INTO restaurant_menu (restaurant_id, name, category, price, description) VALUES (?, ?, ?, ?, ?)");
            $count = 0;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Map based on: Item, Category, Price, Status (Actions is skipped as it is UI)
                // data[0]=Item, data[1]=Category, data[2]=Price, data[3]=Description or Status
                $name = sanitize($data[0] ?? '');
                $category = sanitize($data[1] ?? 'Main Course');
                $price = (float) ($data[2] ?? 0);
                $desc = sanitize($data[3] ?? '');

                if (!empty($name)) {
                    $stmt->execute([$restaurant_id, $name, $category, $price, $desc]);
                    $count++;
                }
            }
            fclose($handle);
            redirectWithMessage('menu_management.php', 'success', "Successfully imported $count items from file!");
        } else {
            redirectWithMessage('menu_management.php', 'error', 'Error opening the file.');
        }
    }
}

// Get menu items
$stmt = $pdo->prepare("SELECT * FROM restaurant_menu WHERE restaurant_id = ? ORDER BY category, name");
$stmt->execute([$restaurant_id]);
$menu_items = $stmt->fetchAll();

// Group by category
$categories = [];
foreach ($menu_items as $item) {
    $cat = $item['category'] ?: 'Uncategorized';
    $categories[$cat][] = $item;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Restaurant Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            background-color: #f0f2f5;
            min-height: 100vh;
        }

        .menu-item-card {
            transition: all 0.3s;
        }

        .menu-item-card:hover {
            transform: translateY(-3px);
        }

        .unavailable {
            opacity: 0.5;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_restaurant.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0"><i class="fas fa-book-open me-2 text-primary-green"></i>Menu Management
                    </h2>
                    <p class="text-muted">Manage your restaurant menu items</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary-green rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#importExcelModal">
                        <i class="fas fa-file-excel me-2"></i>Import Excel
                    </button>
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <button type="submit" name="import_demo_menu" value="1"
                            class="btn btn-outline-info rounded-pill px-4">
                            <i class="fas fa-magic me-2"></i>Demo Menu
                        </button>
                    </form>
                    <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-2"></i>Add Item
                    </button>
                </div>
            </div>

            <!-- Menu Items by Category -->
            <?php if (empty($menu_items)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-utensils text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No menu items yet</h4>
                    <p class="text-muted">Click "Add Item" to start building your menu.</p>
                </div>
            <?php else: ?>
                <?php foreach ($categories as $category => $items): ?>
                    <div class="mb-4">
                        <h5 class="fw-bold text-primary-green mb-3">
                            <i class="fas fa-tag me-2"></i>
                            <?php echo htmlspecialchars($category); ?>
                            <span class="badge bg-light text-muted ms-2">
                                <?php echo count($items); ?>
                            </span>
                        </h5>
                        <div class="row g-3">
                            <?php foreach ($items as $item): ?>
                                <div class="col-md-4">
                                    <div
                                        class="card border-0 shadow-sm menu-item-card <?php echo !$item['is_available'] ? 'unavailable' : ''; ?>">
                                        <div class="card-body">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h6 class="fw-bold mb-0">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </h6>
                                                <span
                                                    class="badge <?php echo $item['is_available'] ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                                    <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                                </span>
                                            </div>
                                            <p class="text-muted small mb-2">
                                                <?php echo htmlspecialchars($item['description'] ?? ''); ?>
                                            </p>
                                            <h5 class="text-primary-green fw-bold">
                                                <?php echo number_format($item['price']); ?> ETB
                                            </h5>
                                            <div class="d-flex gap-2">
                                                <form method="POST" class="d-inline">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="toggle_item" value="1"
                                                        class="btn btn-sm btn-outline-warning rounded-pill">
                                                        <i
                                                            class="fas fa-<?php echo $item['is_available'] ? 'eye-slash' : 'eye'; ?>"></i>
                                                        <?php echo $item['is_available'] ? 'Disable' : 'Enable'; ?>
                                                    </button>
                                                </form>
                                                <form method="POST" class="d-inline"
                                                    onsubmit="return confirm('Delete this item?');">
                                                    <?php echo csrfField(); ?>
                                                    <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                                    <button type="submit" name="delete_item" value="1"
                                                        class="btn btn-sm btn-outline-danger rounded-pill">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" class="modal-content">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add Menu Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" name="name" class="form-control" required placeholder="e.g. Doro Wot">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category" class="form-select" required>
                            <option value="Main Course">Main Course</option>
                            <option value="Breakfast">Breakfast</option>
                            <option value="Lunch">Lunch</option>
                            <option value="Dinner">Dinner</option>
                            <option value="Appetizer">Appetizer</option>
                            <option value="Drinks">Drinks</option>
                            <option value="Desserts">Desserts</option>
                            <option value="Special">Special</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2"
                            placeholder="Optional description"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price (ETB)</label>
                        <input type="number" name="price" class="form-control" required step="0.01" min="1"
                            placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_item" value="1" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add Item
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1">
        <div class="modal-dialog">
            <form method="POST" enctype="multipart/form-data" class="modal-content">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Import Menu via Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Please upload a CSV file with the following columns:</p>
                    <div class="bg-light p-2 rounded-3 mb-3">
                        <code class="text-dark fw-bold">Item, Category, Price, Description</code>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Select File</label>
                        <input type="file" name="menu_csv" class="form-control" accept=".csv" required>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-upload me-2"></i>Start Import
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>