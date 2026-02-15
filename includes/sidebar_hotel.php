<!-- Sidebar -->
<<<<<<< HEAD
<nav id="sidebarMenu" class="sidebar-hotel shadow-lg">
    <div class="sidebar-header">
        <div class="brand-box mx-auto">
            <i class="fas fa-hotel"></i>
        </div>
        <h5 class="brand-name">Ethio<span class="text-warning">Serve</span></h5>
        <div class="user-badge mt-2">
            <span class="small text-white-50">Hotel Panel</span>
        </div>
    </div>

    <div class="sidebar-nav-container">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/dashboard.php">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/orders.php">
                    <i class="fas fa-shopping-bag"></i> <span>All Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/bookings.php">
                    <i class="fas fa-calendar-alt"></i> <span>Bookings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'menu_management.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/menu_management.php">
                    <i class="fas fa-utensils"></i> <span>Menu & Rooms</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel" href="<?php echo BASE_URL; ?>/customer/index.php">
                    <i class="fas fa-store"></i> <span>View Site</span>
=======
<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-primary-green sidebar collapse shadow">
    <div class="position-sticky pt-3">
        <div class="px-4 mb-4 text-center">
            <a href="../customer/index.php" class="text-decoration-none">
                <h4 class="text-white fw-bold">Ethio<span class="text-warning">Serve</span></h4>
            </a>
            <p class="text-white-50 small mb-0">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Hotel Owner'); ?>
            </p>
            <small class="text-white-50">Hotel Panel</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="fas fa-home"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"
                    href="orders.php">
                    <i class="fas fa-shopping-bag"></i> Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"
                    href="bookings.php">
                    <i class="fas fa-calendar-alt"></i> Bookings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'menu_management.php' ? 'active' : ''; ?>"
                    href="menu_management.php">
                    <i class="fas fa-utensils"></i> Menu Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="../customer/index.php">
                    <i class="fas fa-store"></i> View Storefront
                </a>
            </li>
            <li class="nav-item pt-5 mt-3 border-top border-white border-opacity-25">
                <a class="nav-link text-warning" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
                </a>
            </li>
        </ul>
    </div>
<<<<<<< HEAD

    <div class="sidebar-footer">
        <div class="user-profile-summary mb-3 p-2 rounded-3" style="background: rgba(255,255,255,0.05);">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'H'); ?>&background=FFB300&color=000"
                        class="rounded-circle" width="32">
                </div>
                <div class="overflow-hidden">
                    <p class="mb-0 small fw-bold text-truncate text-white">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Owner'); ?>
                    </p>
                </div>
            </div>
        </div>
        <a class="nav-link-hotel logout-btn text-warning mb-3" href="<?php echo BASE_URL; ?>/logout.php">
            <i class="fas fa-sign-out-alt"></i> <span>Logout Portal</span>
        </a>

        <!-- Developer Credit -->
        <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
            class="text-decoration-none transition-all hover-scale">
            <div class="px-3 py-2 bg-white bg-opacity-5 rounded-3 border border-white border-opacity-10 mt-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <img src="https://ui-avatars.com/api/?name=Mequannent+Gashaw+Asinake&background=FFB300&color=000&bold=true"
                        class="rounded-circle shadow-sm" width="28" height="28">
                    <div class="lh-1">
                        <div class="text-white-50" style="font-size: 0.6rem;">Developed by</div>
                        <div class="text-white fw-bold" style="font-size: 0.7rem;">Mequannent Gashaw</div>
                    </div>
                    <i class="fab fa-linkedin ms-auto text-warning" style="font-size: 0.7rem;"></i>
                </div>
            </div>
        </a>
    </div>
</nav>

<style>
    .sidebar-hotel {
        width: 280px;
        height: 100vh;
        background: #1B5E20;
        /* Deep EthioServe Green */
        position: fixed;
        left: 0;
        top: 0;
        z-index: 1000;
        display: flex;
        flex-direction: column;
        color: #fff;
        border-right: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-header {
        padding: 40px 20px;
        text-align: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .brand-box {
        width: 60px;
        height: 60px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        margin-bottom: 15px;
    }

    .brand-name {
        font-weight: 800;
        letter-spacing: -0.5px;
        margin-bottom: 0;
    }

    .sidebar-nav-container {
        flex: 1;
        padding: 30px 15px;
        overflow-y: auto;
    }

    .nav-link-hotel {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 14px 20px;
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 12px;
        margin-bottom: 5px;
        transition: all 0.25s ease;
    }

    .nav-link-hotel i {
        width: 20px;
        font-size: 1.1rem;
        text-align: center;
    }

    .nav-link-hotel:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(5px);
    }

    .nav-link-hotel.active {
        background: #FFB300;
        /* Gold Accent */
        color: #000;
        box-shadow: 0 4px 15px rgba(255, 179, 0, 0.3);
    }

    .sidebar-footer {
        padding: 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
    }

    .logout-btn {
        margin-top: 10px;
        background: rgba(255, 179, 0, 0.1);
        color: #FFB300 !important;
    }

    .logout-btn:hover {
        background: #FFB300 !important;
        color: #000 !important;
    }

    /* Fixed Layout Fix for Pages */
    @media (min-width: 992px) {
        body {
            padding-left: 280px;
        }
    }

    @media (max-width: 991px) {
        .sidebar-hotel {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar-hotel.active {
            transform: translateX(0);
        }
    }
=======
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
>>>>>>> 6e436db773e71c6388afebebeb3d1102776a1fd1
</style>