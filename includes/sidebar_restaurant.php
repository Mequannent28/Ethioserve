<!-- Sidebar -->
<nav id="sidebarMenu" class="sidebar-portal shadow-lg">
    <div class="sidebar-header">
        <div class="brand-box mx-auto mb-2">
            <i class="fas fa-utensils"></i>
        </div>
        <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
            <img src="../assets/img/AA.jpg" class="rounded-circle border border-warning"
                style="width: 30px; height: 30px; object-fit: cover;" onerror="this.style.display='none'">
            <h5 class="brand-name mb-0">Ethio<span class="text-warning">Serve</span></h5>
        </div>
        <div class="user-badge mt-2">
            <span class="small text-white-50">Restaurant Panel</span>
        </div>
    </div>

    <div class="sidebar-nav-container">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link-portal <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="dashboard.php">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-portal <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>"
                    href="orders.php">
                    <i class="fas fa-shopping-bag"></i> <span>Orders</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-portal <?php echo basename($_SERVER['PHP_SELF']) == 'customers.php' ? 'active' : ''; ?>"
                    href="customers.php">
                    <i class="fas fa-users"></i> <span>Customers</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-portal <?php echo basename($_SERVER['PHP_SELF']) == 'menu_management.php' ? 'active' : ''; ?>"
                    href="menu_management.php">
                    <i class="fas fa-book-open"></i> <span>Menu Management</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-portal" href="../customer/index.php">
                    <i class="fas fa-store"></i> <span>View Storefront</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-summary mb-3 p-2 rounded-3" style="background: rgba(255,255,255,0.05);">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'R'); ?>&background=FFB300&color=000"
                        class="rounded-circle" width="32">
                </div>
                <div class="overflow-hidden">
                    <p class="mb-0 small fw-bold text-truncate text-white">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Owner'); ?>
                    </p>
                </div>
            </div>
        </div>
        <a class="nav-link-portal logout-btn text-warning mb-3" href="../logout.php">
            <i class="fas fa-sign-out-alt"></i> <span>Logout Portal</span>
        </a>

        <!-- Developer Credit -->
        <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
            class="text-decoration-none transition-all hover-scale">
            <div class="px-3 py-2 bg-white bg-opacity-5 rounded-3 border border-white border-opacity-10 mt-2 shadow-sm">
                <div class="d-flex align-items-center gap-2">
                    <img src="../assets/img/AA.jpg"
                        onerror="this.src='https://ui-avatars.com/api/?name=Mequannent+Gashaw+Asinake&background=FFB300&color=000&bold=true'"
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
    .sidebar-portal {
        width: 280px;
        height: 100vh;
        background: #1B5E20;
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

    .nav-link-portal {
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

    .nav-link-portal i {
        width: 20px;
        font-size: 1.1rem;
        text-align: center;
    }

    .nav-link-portal:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(5px);
    }

    .nav-link-portal.active {
        background: #FFB300;
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
</style>