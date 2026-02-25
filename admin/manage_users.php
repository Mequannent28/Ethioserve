<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('admin');

// Handle user deletion
if (isset($_GET['delete'])) {
    $user_id = (int) $_GET['delete'];

    // Don't allow deleting yourself
    if ($user_id != getCurrentUserId()) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        redirectWithMessage('manage_users.php', 'success', 'User deleted successfully');
    } else {
        redirectWithMessage('manage_users.php', 'error', 'You cannot delete your own account');
    }
}

// Handle role update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $user_id = (int) $_POST['user_id'];
        $role = sanitize($_POST['role']);

        $valid_roles = ['admin', 'hotel', 'broker', 'customer'];
        if (in_array($role, $valid_roles)) {
            $stmt = $pdo->prepare("UPDATE users SET role = ? WHERE id = ?");
            $stmt->execute([$role, $user_id]);
            redirectWithMessage('manage_users.php', 'success', 'User role updated successfully');
        }
    }
}

// Filter by role
$role_filter = sanitize($_GET['role'] ?? '');
$where = "";
$params = [];

if (!empty($role_filter) && in_array($role_filter, ['admin', 'hotel', 'broker', 'customer'])) {
    $where = "WHERE role = ?";
    $params[] = $role_filter;
}

// Fetch all users
$stmt = $pdo->prepare("SELECT * FROM users $where ORDER BY created_at DESC");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Count by role
$counts = ['admin' => 0, 'hotel' => 0, 'broker' => 0, 'customer' => 0];
$stmt = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
while ($row = $stmt->fetch()) {
    $counts[$row['role']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
        }

        .dashboard-wrapper {
            width: 100%;
        }

        .main-content {
            margin-left: 240px;
            width: calc(100% - 240px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }
    </style>
</head>

<body>
    <div class=" dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Manage Users</h2>
                    <p class="text-muted">View and manage all platform users</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <a href="add_user.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-plus me-2"></i>Add User
                    </a>
                    <div class="d-flex gap-2">
                        <a href="?role=admin"
                            class="btn btn-outline-danger rounded-pill <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">Admins
                            (
                            <?php echo $counts['admin']; ?>)
                        </a>
                        <a href="?role=hotel"
                            class="btn btn-outline-warning rounded-pill <?php echo $role_filter === 'hotel' ? 'active' : ''; ?>">Hotels
                            (
                            <?php echo $counts['hotel']; ?>)
                        </a>
                        <a href="?role=broker"
                            class="btn btn-outline-info rounded-pill <?php echo $role_filter === 'broker' ? 'active' : ''; ?>">Brokers
                            (
                            <?php echo $counts['broker']; ?>)
                        </a>
                        <a href="?role=customer"
                            class="btn btn-outline-success rounded-pill <?php echo $role_filter === 'customer' ? 'active' : ''; ?>">Customers
                            (
                            <?php echo $counts['customer']; ?>)
                        </a>
                        <a href="manage_users.php" class="btn btn-outline-secondary rounded-pill">All</a>
                    </div>
                </div>
            </div>

                <!-- Users Table -->
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="px-4">User</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Joined</th>
                                    <th class="text-end px-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fas fa-users fs-1 mb-3 d-block"></i>
                                            No users found
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td class="px-4">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=1B5E20&color=fff"
                                                        class="rounded-circle" width="40" height="40">
                                                    <div>
                                                        <h6 class="mb-0 fw-bold">
                                                            <?php echo htmlspecialchars($user['full_name']); ?>
                                                        </h6>
                                                        <?php if ($user['phone']): ?>
                                                            <small class="text-muted"><i class="fas fa-phone me-1"></i>
                                                                <?php echo htmlspecialchars($user['phone']); ?>
                                                            </small>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php
                                                echo $user['role'] === 'admin' ? 'danger' :
                                                    ($user['role'] === 'hotel' ? 'warning text-dark' :
                                                        ($user['role'] === 'broker' ? 'info' : 'success'));
                                                ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </td>
                                            <td class="text-end px-4">
                                                <?php if ($user['id'] != getCurrentUserId()): ?>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#roleModal<?php echo $user['id']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <a href="?delete=<?php echo $user['id']; ?>"
                                                            class="btn btn-sm btn-outline-danger rounded-pill"
                                                            onclick="return confirm('Are you sure you want to delete this user?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Current user</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <!-- Role Change Modal -->
                                        <div class="modal fade" id="roleModal<?php echo $user['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog modal-dialog-centered">
                                                <div class="modal-content border-0 rounded-4">
                                                    <div class="modal-header border-0">
                                                        <h5 class="modal-title fw-bold">Change User Role</h5>
                                                        <button type="button" class="btn-close"
                                                            data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <?php echo csrfField(); ?>
                                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                        <input type="hidden" name="update_role" value="1">
                                                        <div class="modal-body">
                                                            <p class="text-muted">Change role for <strong>
                                                                    <?php echo htmlspecialchars($user['full_name']); ?>
                                                                </strong></p>
                                                            <select name="role" class="form-select rounded-pill">
                                                                <option value="customer" <?php echo $user['role'] === 'customer' ? 'selected' : ''; ?>>Customer</option>
                                                                <option value="hotel" <?php echo $user['role'] === 'hotel' ? 'selected' : ''; ?>>Hotel Owner</option>
                                                                <option value="broker" <?php echo $user['role'] === 'broker' ? 'selected' : ''; ?>>Broker</option>
                                                                <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                            </select>
                                                        </div>
                                                        <div class="modal-footer border-0">
                                                            <button type="button" class="btn btn-light rounded-pill px-4"
                                                                data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit"
                                                                class="btn btn-primary-green rounded-pill px-4">Update
                                                                Role</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>