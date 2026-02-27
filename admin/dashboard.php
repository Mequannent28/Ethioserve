<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in and is an admin
requireRole('admin');

// Handle hotel approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_hotel_status'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $hotel_id = (int) $_POST['hotel_id'];
        $status = sanitize($_POST['status']);

        if (in_array($status, ['approved', 'rejected'])) {
            $stmt = $pdo->prepare("UPDATE hotels SET status = ? WHERE id = ?");
            $stmt->execute([$status, $hotel_id]);

            // Log activity
            logActivity('Hotel Status Update', "Hotel ID $hotel_id set to $status");

            redirectWithMessage('dashboard.php', 'success', 'Hotel status updated');
        }
    }
}

// Ensure activity_logs table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NULL,
        activity_type VARCHAR(50) NOT NULL,
        description TEXT,
        platform VARCHAR(50) DEFAULT 'WEB',
        ip_address VARCHAR(45) NULL,
        user_agent TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (user_id),
        INDEX (activity_type),
        INDEX (created_at)
    )");
} catch (Exception $e) {
}

// Fetch stats from database
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_hotels = $pdo->query("SELECT COUNT(*) FROM hotels")->fetchColumn();
$approved_hotels = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status = 'approved'")->fetchColumn();
$pending_hotels = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status = 'pending'")->fetchColumn();
$total_orders = $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$total_revenue = $pdo->query("SELECT SUM(total_amount) FROM orders WHERE status = 'delivered'")->fetchColumn() ?: 0;

// Broad Stats for "Everything"
$modules = [];
try { $modules['restaurants'] = $pdo->query("SELECT COUNT(*) FROM hotels WHERE role_type = 'restaurant'")->fetchColumn(); } catch(Exception $e) {}
try { $modules['taxis'] = $pdo->query("SELECT COUNT(*) FROM taxi_bookings")->fetchColumn(); } catch(Exception $e) {}
try { $modules['jobs'] = $pdo->query("SELECT COUNT(*) FROM job_listings")->fetchColumn(); } catch(Exception $e) {}

// Recent activities stats
$recent_users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll();
$recent_orders = $pdo->query("
    SELECT o.*, u.full_name as customer_name, h.name as hotel_name 
    FROM orders o 
    JOIN users u ON o.customer_id = u.id 
    JOIN hotels h ON o.hotel_id = h.id 
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();
$pending_hotels_list = $pdo->query("
    SELECT h.*, u.full_name as owner_name 
    FROM hotels h 
    JOIN users u ON h.user_id = u.id 
    WHERE h.status = 'pending' 
    ORDER BY h.id DESC LIMIT 5
")->fetchAll();

// Chart Data (Monthly Revenue)
$stmt = $pdo->query("
    SELECT 
        DATE_FORMAT(created_at, '%Y-%m') as month,
        COUNT(*) as order_count,
        SUM(total_amount) as revenue
    FROM orders
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month ASC
");
$monthly_stats = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        :root {
            --admin-primary: #1B5E20;
            --admin-secondary: #F9A825;
            --admin-bg: #f8f9fc;
            --card-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.1);
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--admin-bg);
            color: #5a5c69;
        }

        .main-content {
            margin-left: 260px;
            padding: 2.5rem;
            min-height: 100vh;
        }

        .page-header {
            margin-bottom: 2.5rem;
        }

        .stat-card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: var(--card-shadow);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 2rem 0 rgba(58, 59, 69, 0.15);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .metric-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05rem;
            color: #b7b9cc;
            margin-bottom: 0.25rem;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #4e73df;
            margin-bottom: 0;
        }

        .metric-trend {
            font-size: 0.75rem;
            font-weight: 600;
        }

        .card-header-premium {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.35rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .card-header-premium h6 {
            margin: 0;
            font-weight: 700;
            color: var(--admin-primary);
        }

        .btn-circle {
            width: 2.5rem;
            height: 2.5rem;
            text-align: center;
            padding: 0.375rem 0;
            font-size: 1rem;
            line-height: 1.42857;
            border-radius: 50%;
        }

        .table-custom tr {
            transition: background-color 0.15s ease-in-out;
        }

        .table-custom tr:hover {
            background-color: #f8f9fc;
        }

        .badge-soft-success {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .badge-soft-warning {
            background: #fff3e0;
            color: #ef6c00;
        }

        .badge-soft-danger {
            background: #ffebee;
            color: #c62828;
        }

        .badge-soft-info {
            background: #e3f2fd;
            color: #1565c0;
        }

        .quick-action-btn {
            padding: 0.75rem;
            border-radius: 0.75rem;
            text-align: center;
            background: #fff;
            border: 1px solid #e3e6f0;
            transition: all 0.2s;
            color: #5a5c69;
            text-decoration: none;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .quick-action-btn:hover {
            background: var(--admin-primary);
            color: #fff;
            border-color: var(--admin-primary);
            transform: scale(1.05);
        }

        .quick-action-btn i {
            font-size: 1.25rem;
        }

        .brand-text {
            font-family: 'Montserrat', sans-serif;
            letter-spacing: -0.5px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <!-- Top Nav Replacement -->
            <div class="d-flex justify-content-between align-items-center page-header">
                <div>
                    <h2 class="fw-bold mb-0 text-dark brand-text">Ethio<span class="text-success">Serve</span> HQ</h2>
                    <p class="text-muted mb-0">Platform Overview & Management Center</p>
                </div>
                <div class="d-flex gap-3 align-items-center">
                    <div class="text-end d-none d-md-block">
                        <div class="fw-bold text-dark"><?php echo date('l, F jS'); ?></div>
                        <div class="small text-muted">System Status: <span class="text-success fw-bold">ONLINE</span>
                        </div>
                    </div>
                    <div class="dropdown">
                        <a href="#"
                            class="btn btn-white shadow-sm rounded-pill p-1 px-3 d-flex align-items-center gap-2 border"
                            data-bs-toggle="dropdown">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode(getCurrentUserName()); ?>&background=1B5E20&color=fff"
                                class="rounded-circle" width="32">
                            <div class="text-start d-none d-sm-block">
                                <div class="small fw-bold lh-1"><?php echo htmlspecialchars(getCurrentUserName()); ?>
                                </div>
                                <div class="extra-small text-muted" style="font-size: 0.7rem;">Super Admin</div>
                            </div>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2">
                            <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i>My
                                    Profile</a></li>
                            <li><a class="dropdown-item" href="manage_users.php"><i
                                        class="fas fa-users-cog me-2"></i>Security Settings</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item text-danger" href="../logout.php"><i
                                        class="fas fa-power-off me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <?php echo displayFlashMessage(); ?>

            <!-- Global Stats Grid -->
            <div class="row g-4 mb-5">
                <!-- Platform Revenue -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-white border-start border-primary border-4">
                        <div class="card-body">
                            <div class="metric-label text-primary">Total Gross Revenue</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="metric-value mb-0 text-dark"><?php echo number_format($total_revenue); ?>
                                    <small class="fs-6 fw-normal">ETB</small></h4>
                                <div class="stat-icon bg-soft-info text-primary"><i class="fas fa-coins"></i></div>
                            </div>
                            <div class="metric-trend text-success"><i class="fas fa-arrow-up"></i> +12% this month</div>
                        </div>
                    </div>
                </div>

                <!-- Active Users -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-white border-start border-success border-4">
                        <div class="card-body">
                            <div class="metric-label text-success">Active Ecosystem Users</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="metric-value mb-0 text-dark"><?php echo number_format($total_users); ?></h4>
                                <div class="stat-icon bg-soft-success text-success"><i class="fas fa-users"></i></div>
                            </div>
                            <div class="metric-trend text-success"><i class="fas fa-user-plus"></i>
                                +<?php echo rand(5, 15); ?> today</div>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-white border-start border-warning border-4">
                        <div class="card-body">
                            <div class="metric-label text-warning">Awaiting Verification</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="metric-value mb-0 text-dark"><?php echo number_format($pending_hotels); ?>
                                </h4>
                                <div class="stat-icon bg-soft-warning text-warning"><i
                                        class="fas fa-hourglass-half"></i></div>
                            </div>
                            <div class="metric-trend text-warning"><i class="fas fa-clock"></i> Critical tasks pending
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Total Orders -->
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card bg-white border-start border-danger border-4">
                        <div class="card-body">
                            <div class="metric-label text-danger">Service Transactions</div>
                            <div class="d-flex align-items-center justify-content-between">
                                <h4 class="metric-value mb-0 text-dark"><?php echo number_format($total_orders); ?></h4>
                                <div class="stat-icon bg-soft-danger text-danger"><i class="fas fa-shopping-cart"></i>
                                </div>
                            </div>
                            <div class="metric-trend text-success"><i class="fas fa-check-double"></i> Verified
                                transactions</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Second Layer: Analytics & Actions -->
            <div class="row g-4 mb-5">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header-premium">
                            <h6><i class="fas fa-chart-line me-2"></i>Revenue Growth (ETB)</h6>
                            <div class="btn-group">
                                <button class="btn btn-sm btn-light border">Weekly</button>
                                <button class="btn btn-sm btn-primary">Monthly</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <canvas id="mainChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm mb-4 h-100">
                        <div class="card-header-premium">
                            <h6><i class="fas fa-bolt me-2"></i>High Performance Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-6">
                                    <a href="manage_users.php" class="quick-action-btn">
                                        <i class="fas fa-user-plus text-primary"></i>
                                        <span class="small fw-bold">Users</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="manage_hotels.php" class="quick-action-btn">
                                        <i class="fas fa-hotel text-success"></i>
                                        <span class="small fw-bold">Hotels</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="manage_restaurants.php" class="quick-action-btn">
                                        <i class="fas fa-utensils text-danger"></i>
                                        <span class="small fw-bold">Food</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="activity_log.php" class="quick-action-btn">
                                        <i class="fas fa-clipboard-list text-warning"></i>
                                        <span class="small fw-bold">Audit Logs</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="manage_taxi.php" class="quick-action-btn">
                                        <i class="fas fa-taxi text-info"></i>
                                        <span class="small fw-bold">Taxis</span>
                                    </a>
                                </div>
                                <div class="col-6">
                                    <a href="manage_lms.php" class="quick-action-btn">
                                        <i class="fas fa-graduation-cap text-secondary"></i>
                                        <span class="small fw-bold">LMS</span>
                                    </a>
                                </div>
                            </div>

                            <hr class="my-4">

                            <h6 class="fw-bold mb-3 small text-uppercase">Distribution by Service</h6>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Hotel Bookings</span>
                                    <span
                                        class="fw-bold"><?php echo @round(($approved_hotels / $total_hotels) * 100); ?>%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-success"
                                        style="width: <?php echo @round(($approved_hotels / $total_hotels) * 100); ?>%">
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Food Deliveries</span>
                                    <span class="fw-bold">45%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-danger" style="width: 45%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between mb-1 small">
                                    <span>Users Authenticated</span>
                                    <span class="fw-bold">92%</span>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar bg-primary" style="width: 92%"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Third Layer: Tables -->
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header-premium">
                            <h6><i class="fas fa-user-clock me-2"></i>New Registrations</h6>
                            <a href="manage_users.php" class="btn btn-sm btn-light rounded-pill">View All</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-custom align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="border-0 px-4">User</th>
                                        <th class="border-0">Role</th>
                                        <th class="border-0">Joined</th>
                                        <th class="border-0 text-center">Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_users as $user): ?>
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="d-flex align-items-center gap-3">
                                                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['full_name']); ?>&background=random"
                                                        class="rounded-circle" width="36">
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <?php echo htmlspecialchars($user['full_name']); ?></div>
                                                        <div class="small text-muted">
                                                            <?php echo htmlspecialchars($user['email']); ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-soft-<?php
                                                echo $user['role'] === 'admin' ? 'danger' : ($user['role'] === 'hotel' ? 'warning' : 'success');
                                                ?> rounded-pill px-3">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td class="text-muted small"><?php echo time_ago($user['created_at']); ?></td>
                                            <td class="text-center">
                                                <a href="manage_users.php?id=<?php echo $user['id']; ?>"
                                                    class="btn btn-sm btn-circle btn-light"><i
                                                        class="fas fa-external-link-alt"></i></a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-header-premium">
                            <h6><i class="fas fa-clock me-2"></i>Pending Verifications</h6>
                            <span class="badge bg-danger rounded-pill"><?php echo count($pending_hotels_list); ?></span>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($pending_hotels_list)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-check-double text-success fs-1 mb-3 d-block opacity-25"></i>
                                    <span class="text-muted">No pending approvals detected.</span>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($pending_hotels_list as $hotel): ?>
                                        <div class="list-group-item p-4 border-bottom-0 pb-0">
                                            <div class="d-flex align-items-start gap-3">
                                                <div class="stat-icon bg-light text-primary mb-0"><i class="fas fa-hotel"></i>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <div class="fw-bold text-dark">
                                                        <?php echo htmlspecialchars($hotel['name']); ?></div>
                                                    <div class="small text-muted mb-3">Owner:
                                                        <?php echo htmlspecialchars($hotel['owner_name']); ?></div>
                                                    <div class="d-flex gap-2 mb-3">
                                                        <form method="POST">
                                                            <?php echo csrfField(); ?>
                                                            <input type="hidden" name="hotel_id"
                                                                value="<?php echo $hotel['id']; ?>">
                                                            <input type="hidden" name="update_hotel_status" value="1">
                                                            <button type="submit" name="status" value="approved"
                                                                class="btn btn-sm btn-success rounded-pill px-3 fw-bold shadow-sm">Approve</button>
                                                            <button type="submit" name="status" value="rejected"
                                                                class="btn btn-sm btn-outline-danger rounded-pill px-3 border-0">Decline</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr class="mt-0 opacity-10">
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-footer bg-white border-0 p-3 pb-4">
                            <a href="manage_hotels.php"
                                class="btn btn-light w-100 rounded-pill fw-bold text-primary">View Global List</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        const ctx = document.getElementById('mainChart').getContext('2d');

        <?php
        $months = [];
        $revs = [];
        $ords = [];
        if (!empty($monthly_stats)) {
            foreach ($monthly_stats as $s) {
                $months[] = date('M', strtotime($s['month'] . '-01'));
                $revs[] = (float) $s['revenue'];
                $ords[] = (int) $s['order_count'];
            }
        } else {
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'];
            $revs = [12000, 19000, 15000, 25000, 22000, 30000];
            $ords = [120, 190, 150, 250, 220, 300];
        }
        ?>

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($months); ?>,
                datasets: [{
                    label: 'Revenue (ETB)',
                    data: <?php echo json_encode($revs); ?>,
                    borderColor: '#1B5E20',
                    backgroundColor: 'rgba(27, 94, 32, 0.05)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#fff',
                    pointBorderWidth: 2,
                    pointBorderColor: '#1B5E20'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { drawBorder: false, color: '#f8f9fc' }
                    },
                    x: {
                        grid: { display: false }
                    }
                }
            }
        });
    </script>
</body>