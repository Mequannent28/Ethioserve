<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
require_once '../includes/recycle_bin_helper.php';

requireRole('employer');
$user_id = getCurrentUserId();

// Get company id
$stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();
if (!$company) die("Company record not found.");
$company_id = $company['id'];

// Handle Restore
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['bin_id'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        redirectWithMessage('recycle_bin.php', 'error', 'Invalid security token.');
    }
    $bin_id = (int)$_GET['bin_id'];

    $stmt = $pdo->prepare("SELECT * FROM recycle_bin WHERE id = ? AND user_id = ? AND actor_type = 'employer'");
    $stmt->execute([$bin_id, $user_id]);
    $bin_item = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($bin_item) {
        $data = json_decode($bin_item['data_json'], true);
        $table = $bin_item['original_table'];
        unset($data['id']); // Remove old primary key

        // Re-link to company if table has company_id
        if (isset($data['company_id'])) $data['company_id'] = $company_id;
        // Re-link to user if table has posted_by
        if (isset($data['posted_by'])) $data['posted_by'] = $user_id;

        // Build INSERT
        $columns = implode(', ', array_map(fn($c) => "`$c`", array_keys($data)));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));

        try {
            $insertStmt = $pdo->prepare("INSERT INTO `$table` ($columns) VALUES ($placeholders)");
            $insertStmt->execute(array_values($data));

            // Remove from recycle bin
            $pdo->prepare("DELETE FROM recycle_bin WHERE id = ?")->execute([$bin_id]);

            redirectWithMessage('recycle_bin.php', 'success', 'Item restored successfully!');
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
    $pdo->prepare("DELETE FROM recycle_bin WHERE id = ? AND user_id = ? AND actor_type = 'employer'")->execute([$bin_id, $user_id]);
    redirectWithMessage('recycle_bin.php', 'success', 'Item permanently deleted.');
}

// Fetch all items in the recycle bin for this employer
$stmt = $pdo->prepare("SELECT * FROM recycle_bin WHERE user_id = ? AND actor_type = 'employer' ORDER BY deleted_at DESC");
$stmt->execute([$user_id]);
$bin_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$flash = getFlashMessage();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recycle Bin - Employer Panel</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { overflow-x: hidden; font-family: 'Poppins', sans-serif; background-color: #f0f2f5; }
        .dashboard-wrapper { display: flex; width: 100%; align-items: stretch; }
        .main-content { flex: 1; padding: 30px; min-height: 100vh; }
        .bin-card {
            background: #fff;
            border-radius: 16px;
            border: 1px solid #e8e8e8;
            transition: box-shadow 0.2s ease;
        }
        .bin-card:hover { box-shadow: 0 8px 24px rgba(0,0,0,0.08); }
        .table-badge {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 4px 10px;
            border-radius: 20px;
        }
        .bin-header {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            border-radius: 16px;
            color: #fff;
            padding: 30px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
<div class="dashboard-wrapper">
    <?php include('../includes/sidebar_employer.php'); ?>

    <div class="main-content text-left">
        <?php if ($flash): ?>
            <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-3 mb-4">
                <?php echo htmlspecialchars($flash['message']); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="bin-header d-flex align-items-center gap-3">
            <div style="background:rgba(255,255,255,0.15); border-radius:14px; padding:16px; font-size:2rem;">
                <i class="fas fa-trash-restore text-white"></i>
            </div>
            <div>
                <h2 class="fw-bold mb-1 text-white">Recycle Bin</h2>
                <p class="mb-0 opacity-75 text-white">Recover deleted jobs and exams here.</p>
            </div>
            <span class="badge bg-warning text-dark ms-auto fs-6 px-3 py-2 rounded-pill">
                <?php echo count($bin_items); ?> item(s)
            </span>
        </div>

        <?php if (empty($bin_items)): ?>
            <div class="card border-0 shadow-sm p-5 text-center rounded-4">
                <i class="fas fa-trash-alt text-muted mb-3" style="font-size: 4rem; opacity: 0.3;"></i>
                <h5 class="text-muted fw-bold text-center w-100">Your recycle bin is empty</h5>
                <p class="text-muted small text-center w-100">When you delete jobs or exams, they'll appear here for recovery.</p>
            </div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($bin_items as $item): ?>
                    <?php $data = json_decode($item['data_json'], true); ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="bin-card p-4 h-100 d-flex flex-column">
                            <div class="d-flex align-items-start gap-3 flex-grow-1">
                                <div class="preview-img d-flex align-items-center justify-content-center bg-light text-muted rounded-3" style="width:50px; height:50px; flex-shrink:0;">
                                    <i class="fas fa-<?php echo $item['original_table'] === 'job_listings' ? 'briefcase' : 'file-alt'; ?> fa-lg"></i>
                                </div>
                                <div class="flex-grow-1 overflow-hidden">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge table-badge <?php echo $item['original_table'] === 'job_listings' ? 'bg-primary' : 'bg-info text-dark'; ?>">
                                            <?php echo $item['original_table'] === 'job_listings' ? 'Job Posting' : 'Exam'; ?>
                                        </span>
                                        <small class="text-muted">#<?php echo $item['original_id']; ?></small>
                                    </div>
                                    <h6 class="fw-bold mb-0 text-truncate">
                                        <?php echo htmlspecialchars($data['title'] ?? 'Unnamed Item'); ?>
                                    </h6>
                                    <small class="text-muted d-block mt-1">
                                        <i class="fas fa-calendar-times me-1"></i>
                                        Deleted <?php echo date('M d, Y H:i', strtotime($item['deleted_at'])); ?>
                                    </small>
                                    <?php if (!empty($item['reason'])): ?>
                                        <div class="mt-2">
                                            <span class="badge rounded-pill px-3 py-1" style="background:#fff3e0; color:#e65100; font-size:0.7rem;">
                                                <i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($item['reason']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="d-flex gap-2 mt-4 pt-3 border-top">
                                <a href="recycle_bin.php?action=restore&bin_id=<?php echo $item['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                                   class="btn btn-sm btn-success rounded-pill flex-grow-1"
                                   onclick="return confirm('Restore this item back to your panel?')">
                                    <i class="fas fa-undo me-1"></i> Restore
                                </a>
                                <a href="recycle_bin.php?action=permanent_delete&bin_id=<?php echo $item['id']; ?>&csrf_token=<?php echo generateCSRFToken(); ?>"
                                   class="btn btn-sm btn-outline-danger rounded-pill perm-delete-btn"
                                   data-name="<?php echo htmlspecialchars($data['title'] ?? 'Unnamed Item', ENT_QUOTES); ?>">
                                    <i class="fas fa-trash"></i>
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
            const url = btn.getAttribute('href');
            const name = btn.getAttribute('data-name');
            Swal.fire({
                title: 'Permanently Delete?',
                html: `<p class="text-muted">This will permanently remove <strong>${name}</strong> and it cannot be recovered.</p>`,
                icon: 'error',
                showCancelButton: true,
                confirmButtonText: '<i class="fas fa-trash me-1"></i> Delete Forever',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#d32f2f',
                cancelButtonColor: '#6c757d',
            }).then((result) => {
                if (result.isConfirmed) window.location.href = url;
            });
        });
    });
</script>
</body>
</html>
