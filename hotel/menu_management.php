<?php
session_start();
require_once '../includes/db.php';

// Check if logged in and is a hotel owner
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'hotel') {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get hotel for this user
$stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    die("Hotel record not found. Please contact admin.");
}

$hotel_id = $hotel['id'];

// Fetch menu items for this hotel
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name 
                       FROM menu_items m 
                       LEFT JOIN categories c ON m.category_id = c.id 
                       WHERE m.hotel_id = ? 
                       ORDER BY c.name, m.name");
$stmt->execute([$hotel_id]);
$menu_items = $stmt->fetchAll();

// Fetch categories for the add/edit form
$stmt = $pdo->query("SELECT * FROM categories ORDER BY name");
$categories = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Hotel Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="/ethioserve/assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            background-color: #f8fafc;
        }

        .main-content {
            padding: 40px;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include '../includes/sidebar_hotel.php'; ?>
        <main class="main-content">
            <div
                class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Menu Management</h1>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-primary-green rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#importExcelModal">
                        <i class="fas fa-file-excel me-2"></i> Import Excel
                    </button>
                    <form action="process_menu.php?action=import_demo" method="POST">
                        <button type="submit" class="btn btn-outline-info rounded-pill px-4">
                            <i class="fas fa-magic me-2"></i> Demo Menu
                        </button>
                    </form>
                    <button type="button" class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                        data-bs-target="#addItemModal">
                        <i class="fas fa-plus me-2"></i> Add New Item
                    </button>
                </div>
            </div>

            <!-- Menu Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Item</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($menu_items)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">No menu items found. Start by adding
                                        one!</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($menu_items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo $item['image_url'] ?: '../assets/images/placeholder-food.jpg'; ?>"
                                                    class="rounded-3" width="50" height="50" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['name']); ?>
                                                    </h6>
                                                    <p class="text-muted small mb-0 d-inline-block text-truncate"
                                                        style="max-width: 200px;">
                                                        <?php echo htmlspecialchars($item['description']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark border">
                                                <?php echo htmlspecialchars($item['category_name']); ?>
                                            </span></td>
                                        <td class="fw-bold text-primary-green">
                                            <?php echo number_format($item['price'], 2); ?> ETB
                                        </td>
                                        <td>
                                            <?php if ($item['is_available']): ?>
                                                <span class="badge bg-success-subtle text-success">Available</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger-subtle text-danger">Sold Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <div class="btn-group">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill me-2 edit-btn"
                                                    data-item='<?php echo json_encode($item); ?>' data-bs-toggle="modal"
                                                    data-bs-target="#editItemModal">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <a href="process_menu.php?action=delete&id=<?php echo $item['id']; ?>"
                                                    class="btn btn-sm btn-outline-danger rounded-pill"
                                                    onclick="return confirm('Are you sure you want to delete this item?')">
                                                    <i class="fas fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Import Excel Modal -->
    <div class="modal fade" id="importExcelModal" tabindex="-1">
        <div class="modal-dialog">
            <form action="process_menu.php?action=import_csv" method="POST" enctype="multipart/form-data"
                class="modal-content">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Import Menu via Excel/CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">Prepare your menu in an Excel file and save it as <strong>CSV (Comma
                            Delimited)</strong>. Use these columns:</p>
                    <div class="bg-light p-3 rounded-3 mb-3">
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

    <!-- Add Item Modal -->
    <div class="modal fade" id="addItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="process_menu.php?action=add" method="POST">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Add New Food Item</h5>
                        <button type="button" class="btn-close" data-bs-close="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item Name</label>
                            <input type="text" name="name" class="form-control rounded-3" required
                                placeholder="e.g. Special Kitfo">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" class="form-select rounded-3" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Price (ETB)</label>
                                <input type="number" name="price" step="0.01" class="form-control rounded-3" required
                                    placeholder="0.00">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control rounded-3" rows="3"
                                placeholder="Describe the ingredients or taste..."></textarea>
                        </div>
                        <div class="mb-0">
                            <label class="form-label small fw-bold">Image URL (Optional)</label>
                            <input type="url" name="image_url" class="form-control rounded-3"
                                placeholder="https://image-link.com/photo.jpg">
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-green rounded-pill px-4">Save Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow rounded-4">
                <form action="process_menu.php?action=edit" method="POST">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="modal-header border-0 pb-0">
                        <h5 class="modal-title fw-bold">Edit Food Item</h5>
                        <button type="button" class="btn-close" data-bs-close="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Item Name</label>
                            <input type="text" name="name" id="edit_name" class="form-control rounded-3" required>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Category</label>
                                <select name="category_id" id="edit_category_id" class="form-select rounded-3" required>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo $cat['id']; ?>">
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Price (ETB)</label>
                                <input type="number" name="price" id="edit_price" step="0.01"
                                    class="form-control rounded-3" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" id="edit_description" class="form-control rounded-3"
                                rows="3"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold">Image URL</label>
                            <input type="url" name="image_url" id="edit_image_url" class="form-control rounded-3">
                        </div>
                        <div class="form-check form-switch mt-3">
                            <input class="form-check-input" type="checkbox" name="is_available" id="edit_is_available"
                                value="1">
                            <label class="form-check-label fw-bold small">Is Available</label>
                        </div>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary-green rounded-pill px-4">Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const editBtns = document.querySelectorAll('.edit-btn');
            editBtns.forEach(btn => {
                btn.addEventListener('click', function () {
                    const data = JSON.parse(this.getAttribute('data-item'));
                    document.getElementById('edit_id').value = data.id;
                    document.getElementById('edit_name').value = data.name;
                    document.getElementById('edit_category_id').value = data.category_id;
                    document.getElementById('edit_price').value = data.price;
                    document.getElementById('edit_description').value = data.description;
                    document.getElementById('edit_image_url').value = data.image_url;
                    document.getElementById('edit_is_available').checked = data.is_available == 1;
                });
            });
        });
    </script>

    <?php include '../includes/footer.php'; ?>