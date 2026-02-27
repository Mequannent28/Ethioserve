<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Handle deletions or clears if needed (optional)
if (isset($_POST['clear_logs']) && verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $pdo->query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 30 DAY)");
    redirectWithMessage('activity_log.php', 'success', 'Logs older than 30 days cleared.');
}

// Filters
$where = "1=1";
$params = [];

if (!empty($_GET['search'])) {
    $where .= " AND (description LIKE ? OR activity_type LIKE ?)";
    $params[] = "%" . $_GET['search'] . "%";
    $params[] = "%" . $_GET['search'] . "%";
}

if (!empty($_GET['platform']) && $_GET['platform'] !== 'All Platforms') {
    $where .= " AND platform = ?";
    $params[] = $_GET['platform'];
}

if (!empty($_GET['activity_type']) && $_GET['activity_type'] !== 'All Activities') {
    $where .= " AND activity_type = ?";
    $params[] = $_GET['activity_type'];
}

if (!empty($_GET['date'])) {
    $where .= " AND DATE(created_at) = ?";
    $params[] = $_GET['date'];
}

// Fetch Activity Logs
$query = "
    SELECT al.*, u.full_name, u.role, u.username
    FROM activity_logs al
    LEFT JOIN users u ON al.user_id = u.id
    WHERE $where
    ORDER BY al.created_at DESC
    LIMIT 100
";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Get unique activity types for filter
$activity_types = $pdo->query("SELECT DISTINCT activity_type FROM activity_logs ORDER BY activity_type")->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log - EthioServe Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --primary-blue: #3b71ca;
            --bg-light: #f8f9fa;
            --border-color: #e9ecef;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            color: #333;
        }

        .main-content {
            margin-left: 260px;
            padding: 20px;
            min-height: 100vh;
        }

        .log-container {
            display: flex;
            gap: 20px;
            margin-top: 20px;
        }

        .log-sidebar-left {
            width: 280px;
            flex-shrink: 0;
        }

        .log-main-content {
            flex-grow: 1;
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .log-sidebar-right {
            width: 300px;
            flex-shrink: 0;
        }

        .sidebar-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid var(--border-color);
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.02);
        }

        .report-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 15px;
            border-radius: 8px;
            color: #555;
            text-decoration: none;
            transition: all 0.2s;
            margin-bottom: 5px;
        }

        .report-item:hover {
            background: #f0f4f8;
            color: var(--primary-blue);
        }

        .report-item.active {
            background: var(--primary-blue);
            color: #fff;
        }

        .report-item i {
            width: 20px;
            text-align: center;
        }

        .table-activity thead th {
            background: #fcfcfd;
            font-weight: 600;
            color: #666;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 1px solid var(--border-color);
            padding: 15px 20px;
        }

        .table-activity td {
            padding: 15px 20px;
            vertical-align: middle;
            border-bottom: 1px solid #f8f9fa;
            font-size: 0.9rem;
        }

        .platform-badge {
            background: #e1f0ff;
            color: #0052cc;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
        }

        .search-wrapper {
            position: relative;
        }

        .search-wrapper i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
        }

        .search-wrapper input {
            padding-left: 40px;
            border-radius: 10px;
            border: 1px solid #ddd;
        }

        .filter-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #999;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .btn-apply {
            background: var(--primary-blue);
            color: #fff;
            font-weight: 600;
            border-radius: 8px;
            width: 100%;
            padding: 10px;
            margin-top: 20px;
            border: none;
            transition: opacity 0.2s;
        }

        .btn-apply:hover {
            opacity: 0.9;
            color: #fff;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #eee;
        }

        @media (max-width: 1200px) {
            .log-container {
                flex-direction: column;
            }

            .log-sidebar-left,
            .log-sidebar-right {
                width: 100%;
            }
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">System Activity Reports</h3>
                <div class="text-muted small">Monitor all system activities in real-time</div>
            </div>

            <?php echo displayFlashMessage(); ?>

            <div class="log-container">
                <!-- Left Sidebar: Report Types -->
                <div class="log-sidebar-left">
                    <div class="sidebar-card">
                        <h6 class="fw-bold mb-3">Activity Reports</h6>
                        <p class="text-muted small mb-4">Monitor system activities</p>

                        <div class="filter-label">Report Types</div>
                        <nav class="report-nav">
                            <a href="#" class="report-item active">
                                <i class="fas fa-users-viewfinder"></i>
                                <span>User Activity</span>
                            </a>
                            <a href="#" class="report-item">
                                <i class="fas fa-user-shield"></i>
                                <span>Admin Logs</span>
                            </a>
                            <a href="#" class="report-item">
                                <i class="fas fa-microchip"></i>
                                <span>System Audit</span>
                            </a>
                        </nav>

                        <button class="btn btn-outline-secondary w-100 mt-5 rounded-pill btn-sm"
                            onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh Data
                        </button>
                    </div>
                </div>

                <!-- Main Content: Log List -->
                <div class="log-main-content">
                    <div
                        class="p-4 border-bottom d-flex justify-content-between align-items-center bg-white sticky-top">
                        <div>
                            <h5 class="fw-bold mb-0">User Activity</h5>
                            <span class="text-muted small">
                                <?php echo count($logs); ?> records found
                            </span>
                        </div>
                        <div class="search-wrapper">
                            <form method="GET" class="d-flex gap-2">
                                <i class="fas fa-search"></i>
                                <input type="text" name="search" class="form-control" placeholder="Search activities..."
                                    value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                                <?php if (!empty($_GET['search'])): ?>
                                    <a href="activity_log.php" class="btn btn-light"><i class="fas fa-times"></i></a>
                                <?php endif; ?>
                            </form>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-activity mb-0">
                            <thead>
                                <tr>
                                    <th>Activity Definition</th>
                                    <th>Time</th>
                                    <th>Platform</th>
                                    <th>IP Address</th>
                                    <th>User</th>
                                    <th>Location</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" width="80"
                                                class="opacity-25 mb-3">
                                            <p class="text-muted">No activity logs found matching your filters.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-bold">
                                                    <?php echo htmlspecialchars($log['activity_type']); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo htmlspecialchars($log['description']); ?>
                                                </div>
                                            </td>
                                            <td class="text-nowrap">
                                                <div class="small fw-medium">
                                                    <?php echo date('m/d/Y', strtotime($log['created_at'])); ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <?php echo date('g:i A', strtotime($log['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="platform-badge">
                                                    <?php echo htmlspecialchars($log['platform']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small">
                                                <?php echo htmlspecialchars($log['ip_address'] ?: '-'); ?>
                                            </td>
                                            <td>
                                                <div class="user-info">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($log['full_name'] ?: ($log['username'] ?: 'Guest')); ?>&background=random"
                                                        class="user-avatar" alt="">
                                                    <div class="lh-1">
                                                        <div class="small fw-bold">
                                                            <?php echo htmlspecialchars($log['full_name'] ?: ($log['username'] ?: 'Guest')); ?>
                                                        </div>
                                                        <div class="extra-small text-muted" style="font-size: 0.7rem;">
                                                            <?php echo strtoupper($log['role'] ?? 'VISITOR'); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-muted small">-</td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right Sidebar: Filters -->
                <div class="log-sidebar-right">
                    <form method="GET">
                        <div class="sidebar-card">
                            <div class="filter-label">Date</div>
                            <input type="date" name="date" class="form-control mb-4"
                                value="<?php echo htmlspecialchars($_GET['date'] ?? ''); ?>">

                            <h6 class="fw-bold mb-3">Filters</h6>

                            <div class="filter-label">Platform</div>
                            <select name="platform" class="form-select mb-3">
                                <option>All Platforms</option>
                                <option value="WEB" <?php echo ($_GET['platform'] ?? '') === 'WEB' ? 'selected' : ''; ?>
                                    >Web Dashboard</option>
                                <option value="MOBILE" <?php echo ($_GET['platform'] ?? '') === 'MOBILE' ? 'selected' : ''; ?>>Mobile App</option>
                                <option value="API" <?php echo ($_GET['platform'] ?? '') === 'API' ? 'selected' : ''; ?>
                                    >API Integration</option>
                            </select>

                            <div class="filter-label">Activity Type</div>
                            <select name="activity_type" class="form-select mb-3">
                                <option>All Activities</option>
                                <?php foreach ($activity_types as $type): ?>
                                    <option value="<?php echo htmlspecialchars($type); ?>" <?php echo ($_GET['activity_type'] ?? '') === $type ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($type); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>

                            <button type="submit" class="btn btn-apply">
                                <i class="fas fa-filter me-2"></i>Apply Filters
                            </button>

                            <a href="activity_log.php" class="btn btn-light w-100 mt-2 rounded-pill btn-sm">Reset</a>
                        </div>
                    </form>

                    <div class="sidebar-card bg-primary-green text-white">
                        <h6 class="fw-bold mb-1">Export Logs</h6>
                        <p class="small opacity-75 mb-3">Download log records for audit</p>
                        <button class="btn btn-sm btn-white w-100"><i class="fas fa-file-export me-2"></i>Download
                            CSV</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>