<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/recycle_bin_helper.php';

requireRole(['broker', 'property_owner']);
$user_id = getCurrentUserId();

// Handle Restore
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['bin_id'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        redirectWithMessage('recycle_bin.php', 'error', 'Invalid security token.');
    }
    $bin_id = (int)$_GET['bin_id'];

    $stmt = $pdo->prepare("SELECT * FROM recycle_bin WHERE id = ? AND user_id = ? AND actor_type = 'broker'");
    $stmt->execute([$bin_id, $user_id]);
    $bin_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bin_item) {
        $data = json_decode($bin_item['data_json'], true);
        $table = $bin_item['original_table'];
        unset($data['id']); // Remove old primary key

        // Build INSERT
        $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        try {
            $insertStmt = $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
            $insertStmt->execute(array_values($data));

            // Remove from recycle bin
            $pdo->prepare("DELETE FROM recycle_bin WHERE id = ?")->execute([$bin_id]);

            redirectWithMessage('recycle_bin.php', 'success', 'Listing restored successfully!');
        } catch (Exception $e) {
            redirectWithMessage('recycle_bin.php', 'error', 'Could not restore: ' . $e->getMessage());
        }
    } else {
        redirectWithMessage('recycle_bin.php', 'error', 'Item not found.');
    }
}

// Handle Permanent Delete
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete' && isset($_GET['bin_id'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        redirectWithMessage('recycle_bin.php', 'error', 'Invalid security token.');
    }
    $bin_id = (int)$_GET['bin_id'];
    $pdo->prepare("DELETE FROM recycle_bin WHERE id = ? AND user_id = ? AND actor_type = 'broker'")->execute([$bin_id, $user_id]);
    redirectWithMessage('recycle_bin.php', 'success', 'Listing permanently deleted.');
}

// Fetch all items in the recycle bin for this broker
$stmt = $pdo->prepare("SELECT * FROM recycle_bin WHERE user_id = ? AND actor_type = 'broker' ORDER BY deleted_at DESC");
$stmt->execute([$user_id]);
$bin_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin - Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body { overflow-x: hidden; font-family: 'Poppins', sans-serif; background-color: #f0f2f5; }
        .dashboard-wrapper { display: flex; width: 100%; align-items: stretch; }
        .main-content { flex: 1; padding: 30px; min-height: 100vh; margin-left: 260px; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
        .bin-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8e8e8;
            transition: box-shadow 0.2s ease;
        }
        .bin-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .preview-img {
            width: 80px; height: 80px;
            object-fit: cover;
            border-radius: 12px;
            border: 2px solid #eee;
        }
        .bin-header {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            border-radius: 20px;
            color: #fff;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(27, 94, 32, 0.2);
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('../includes/sidebar_broker.php'); ?>

    <div class="main-content">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 border-0 shadow-sm mb-4">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="bin-header d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center gap-4">
                <div style="background:rgba(255,255,255,0.2); border-radius:18px; padding:20px; font-size:2.5rem; backdrop-filter: blur(10px);">
                    <i class="fas fa-trash-restore-alt"></i>
                </div>
                <div>
                    <h2 class="fw-bold mb-1">Recycle Bin</h2>
                    <p class="mb-0 opacity-75">Recover your deleted listings or remove them forever.</p>
                </div>
            </div>
            <a href="my_listings.php" class="btn btn-light rounded-pill px-4 fw-bold">
                <i class="fas fa-arrow-left me-2"></i> Back to Listings
            </a>
        </div>

        <?php if (empty($bin_items)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                <div class="mb-4">
                    <i class="fas fa-trash-alt text-muted" style="font-size: 5rem; opacity: 0.1;"></i>
                </div>
                <h4 class="text-muted fw-bold">Recycle Bin is Empty</h4>
                <p class="text-muted">Listings you delete will stay here for a while before they disappear.</p>
                <div class="mt-3">
                    <a href="my_listings.php" class="btn btn-primary-green rounded-pill px-5">Go to My Listings</a>
                </div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($bin_items as $item): ?>
                    <?php $data = json_decode($item['data_json'], true); ?>
                    <div class="col-md-6">
                        <div class="bin-card p-4 h-100 shadow-sm">
                            <div class="d-flex align-items-start gap-3">
                                <img src="<?php echo $data['image_url'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200'; ?>" class="preview-img shadow-sm" onerror="this.src='https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=200'">
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between">
                                        <span class="badge bg-light text-success border border-success border-opacity-10 text-uppercase px-2 py-1 mb-2" style="font-size: 0.65rem;">
                                            <?php echo str_replace('_', ' ', $data['type'] ?? 'Listing'); ?>
                                        </span>
                                        <small class="text-muted">ID: #<?php echo $item['original_id']; ?></small>
                                    </div>
                                    <h5 class="fw-bold mb-1"><?php echo htmlspecialchars($data['title'] ?? 'Untitled Listing'); ?></h5>
                                    <p class="text-muted small mb-2"><i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($data['location'] ?? 'No location'); ?></p>
                                    
                                    <div class="d-flex align-items-center gap-3 text-muted small mt-2">
                                        <span><i class="fas fa-calendar-times me-1"></i> Deleted: <?php echo date('M d, Y', strtotime($item['deleted_at'])); ?></span>
                                        <?php if (isset($data['price'])): ?>
                                            <span class="fw-bold text-success"><?php echo number_format($data['price']); ?> ETB</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                                <a href="recycle_bin.php?action=restore&bin_id=<?php echo $item['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                                   class="btn btn-success rounded-pill flex-grow-1 fw-bold"
                                   onclick="return confirm('Restore this listing?')">
                                    <i class="fas fa-undo-alt me-2"></i> Restore Listing
                                </a>
                                <a href="recycle_bin.php?action=permanent_delete&bin_id=<?php echo $item['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                                   class="btn btn-outline-danger rounded-circle perm-delete-btn"
                                   style="width: 42px; height: 42px; display: flex; align-items: center; justify-content: center;"
                                   data-name="<?php echo htmlspecialchars($data['title'] ?? 'this listing'); ?>">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.querySelectorAll('.perm-delete-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            const name = this.getAttribute('data-name');
            Swal.fire({
                title: 'Permanently Delete?',
                text: `Are you sure you want to delete "${name}" forever? This cannot be undone.`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#1B5E20',
                confirmButtonText: 'Yes, Delete Permanently'
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });
</script>
</body>
</html>
