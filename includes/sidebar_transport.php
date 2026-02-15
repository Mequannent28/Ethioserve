<!-- Sidebar -->
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-primary-green sidebar collapse shadow">
    <div class="position-sticky pt-3">
        <div class="px-4 mb-4 text-center">
            <a href="../customer/index.php" class="text-decoration-none">
                <h4 class="text-white fw-bold">Ethio<span class="text-warning">Serve</span></h4>
            </a>
            <p class="text-white-50 small mb-0">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Transport Owner'); ?>
            </p>
            <span class="badge bg-info">Transport</span>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"
                    href="bookings.php">
                    <i class="fas fa-ticket-alt"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'buses.php' ? 'active' : ''; ?>"
                    href="buses.php">
                    <i class="fas fa-bus"></i> Manage Buses
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'routes.php' ? 'active' : ''; ?>"
                    href="routes.php">
                    <i class="fas fa-route"></i> Routes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'schedules.php' ? 'active' : ''; ?>"
                    href="schedules.php">
                    <i class="fas fa-calendar-alt"></i> Schedules
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../customer/buses.php">
                    <i class="fas fa-store"></i> View Storefront
                </a>
            </li>
            <li class="nav-item pt-5 mt-3 border-top border-white border-opacity-25">
                <a class="nav-link text-warning" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
    .sidebar {
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        z-index: 100;
    }

    .sidebar .nav-link {
        color: rgba(255, 255, 255, 0.8);
        padding: 12px 20px;
        margin: 2px 10px;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .sidebar .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.1);
    }

    .sidebar .nav-link.active {
        color: #1B5E20;
        background: #fff;
        font-weight: 600;
    }

    .sidebar .nav-link i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }
</style>