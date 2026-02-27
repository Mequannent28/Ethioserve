<?php $current_page = basename($_SERVER['PHP_SELF']); ?>
<!-- Sidebar -->
<nav id="sidebarMenu" class="admin-sidebar">
    <div class="sidebar-inner">
        <!-- Logo / Brand -->
        <div class="sidebar-brand">
            <a href="../customer/index.php" class="text-decoration-none d-flex align-items-center gap-2">
                <img src="../assets/img/AA.jpg" class="rounded-circle border border-warning"
                    style="width: 35px; height: 35px; object-fit: cover;" onerror="this.style.display='none'">
                <h4 class="text-white fw-bold mb-0">Ethio<span class="text-warning">Serve</span></h4>
            </a>
            <p class="text-white-50 small mb-0 mt-1">
                <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?>
            </p>
            <span class="badge bg-danger mt-1">Super Admin</span>
        </div>

        <!-- Navigation -->
        <div class="sidebar-nav-wrapper">
            <ul class="nav flex-column sidebar-nav">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>"
                        href="dashboard.php">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <!-- SERVICES Section -->
                <li class="nav-section-title">
                    <span>SERVICES</span>
                </li>

                <!-- Manage Restaurants -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_restaurants.php' ? 'active' : ''; ?>"
                        href="manage_restaurants.php">
                        <i class="fas fa-utensils"></i>
                        <span>Manage Restaurants</span>
                    </a>
                </li>

                <!-- Manage Hotels -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_hotels.php' ? 'active' : ''; ?>"
                        href="manage_hotels.php">
                        <i class="fas fa-hotel"></i>
                        <span>Manage Hotels</span>
                    </a>
                </li>

                <!-- Manage Taxi -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_taxi.php' ? 'active' : ''; ?>"
                        href="manage_taxi.php">
                        <i class="fas fa-taxi"></i>
                        <span>Manage Taxi</span>
                    </a>
                </li>

                <!-- Manage Bus -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_bus.php' ? 'active' : ''; ?>"
                        href="manage_bus.php">
                        <i class="fas fa-bus"></i>
                        <span>Manage Bus</span>
                    </a>
                </li>

                <!-- Manage Transport -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_transport.php' ? 'active' : ''; ?>"
                        href="manage_transport.php">
                        <i class="fas fa-bus-alt"></i>
                        <span>Manage Transport</span>
                    </a>
                </li>

                <!-- Manage Flights -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_flights.php' ? 'active' : ''; ?>"
                        href="manage_flights.php">
                        <i class="fas fa-plane"></i>
                        <span>Manage Flights</span>
                    </a>
                </li>

                <!-- ENTERTAINMENT Section -->
                <li class="nav-section-title">
                    <span>ENTERTAINMENT</span>
                </li>

                <!-- Manage Movies -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_movies.php' ? 'active' : ''; ?>"
                        href="manage_movies.php">
                        <i class="fas fa-film"></i>
                        <span>Manage Movies</span>
                    </a>
                </li>

                <!-- Manage Experiences -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_experiences.php' ? 'active' : ''; ?>"
                        href="manage_experiences.php">
                        <i class="fas fa-skating"></i>
                        <span>Manage Experiences</span>
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_education.php' ? 'active' : ''; ?>"
                        href="manage_education.php">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Manage Education</span>
                    </a>
                </li>

                <!-- Manage LMS -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_lms.php' ? 'active' : ''; ?>"
                        href="manage_lms.php">
                        <i class="fas fa-brain"></i>
                        <span>Manage LMS</span>
                    </a>
                </li>

                <!-- MARKETPLACE Section -->
                <li class="nav-section-title">
                    <span>MARKETPLACE</span>
                </li>

                <!-- Manage Brokers -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_brokers.php' ? 'active' : ''; ?>"
                        href="manage_brokers.php">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Brokers</span>
                    </a>
                </li>

                <!-- Manage Real Estate -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_realestate.php' ? 'active' : ''; ?>"
                        href="manage_realestate.php">
                        <i class="fas fa-city"></i>
                        <span>Manage Real Estate</span>
                    </a>
                </li>

                <!-- Manage Rent -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_rent.php' ? 'active' : ''; ?>"
                        href="manage_rent.php">
                        <i class="fas fa-home"></i>
                        <span>Manage Rent</span>
                    </a>
                </li>

                <!-- Manage Home Services -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_home.php' ? 'active' : ''; ?>"
                        href="manage_home.php">
                        <i class="fas fa-wrench"></i>
                        <span>Manage Home</span>
                    </a>
                </li>

                <!-- Manage Exchange -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_exchange.php' ? 'active' : ''; ?>"
                        href="manage_exchange.php">
                        <i class="fas fa-exchange-alt"></i>
                        <span>Manage Exchange</span>
                    </a>
                </li>

                <!-- Manage Health -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_health.php' ? 'active' : ''; ?>"
                        href="manage_health.php">
                        <i class="fas fa-heartbeat"></i>
                        <span>Manage Health</span>
                    </a>
                </li>


                <!-- SYSTEM Section -->
                <li class="nav-section-title">
                    <span>SYSTEM</span>
                </li>

                <!-- Manage Users -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_users.php' ? 'active' : ''; ?>"
                        href="manage_users.php">
                        <i class="fas fa-users"></i>
                        <span>Manage Users</span>
                    </a>
                </li>

                <!-- Activity Log -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'activity_log.php' ? 'active' : ''; ?>"
                        href="activity_log.php">
                        <i class="fas fa-list-ul"></i>
                        <span>Activity Log</span>
                    </a>
                </li>

                <!-- View Platform -->
                <li class="nav-item">
                    <a class="nav-link" href="../customer/index.php">
                        <i class="fas fa-store"></i>
                        <span>View Platform</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- Logout -->
        <div class="sidebar-footer">
            <a class="nav-link text-danger mb-3" href="../logout.php">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>

            <!-- Developer Credit -->
            <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
                class="text-decoration-none transition-all hover-scale">
                <div class="px-3 py-2 bg-white bg-opacity-5 rounded-3 border border-white border-opacity-10 shadow-sm">
                    <div class="d-flex align-items-center gap-2">
                        <img src="../assets/img/AA.jpg"
                            onerror="this.src='https://ui-avatars.com/api/?name=Mequannent+Gashaw+Asinake&background=F9A825&color=fff&bold=true'"
                            class="rounded-circle shadow-sm" width="28" height="28">
                        <div class="lh-1">
                            <div class="text-white-50" style="font-size: 0.6rem;">Developed by</div>
                            <div class="text-white fw-bold" style="font-size: 0.7rem;">Mequannent Gashaw</div>
                        </div>
                        <i class="fab fa-linkedin ms-auto text-primary" style="font-size: 0.7rem;"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</nav>

<style>
    .admin-sidebar {
        position: fixed;
        left: 0;
        top: 0;
        bottom: 0;
        width: 260px;
        background: linear-gradient(180deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        z-index: 100;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 24px rgba(0, 0, 0, 0.15);
        overflow: hidden;
    }

    .sidebar-inner {
        display: flex;
        flex-direction: column;
        height: 100%;
        overflow: hidden;
    }

    .sidebar-brand {
        padding: 24px 20px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }

    .sidebar-brand h4 {
        font-size: 1.4rem;
        letter-spacing: 0.5px;
    }

    .sidebar-nav-wrapper {
        flex: 1;
        overflow-y: auto;
        overflow-x: hidden;
        padding: 10px 0;
        scrollbar-width: thin;
        scrollbar-color: rgba(255, 255, 255, 0.15) transparent;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar {
        width: 5px;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar-track {
        background: transparent;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar-thumb {
        background: rgba(255, 255, 255, 0.15);
        border-radius: 10px;
    }

    .sidebar-nav-wrapper::-webkit-scrollbar-thumb:hover {
        background: rgba(255, 255, 255, 0.25);
    }

    .sidebar-nav .nav-section-title {
        padding: 18px 20px 8px;
        list-style: none;
    }

    .sidebar-nav .nav-section-title span {
        font-size: 0.65rem;
        font-weight: 700;
        letter-spacing: 1.5px;
        color: rgba(255, 255, 255, 0.35);
        text-transform: uppercase;
    }

    .sidebar-nav .nav-link {
        color: rgba(255, 255, 255, 0.65);
        padding: 10px 20px;
        margin: 1px 12px;
        border-radius: 10px;
        transition: all 0.25s ease;
        font-size: 0.88rem;
        font-weight: 400;
        display: flex;
        align-items: center;
        gap: 12px;
        position: relative;
        overflow: hidden;
    }

    .sidebar-nav .nav-link::before {
        content: '';
        position: absolute;
        left: 0;
        top: 50%;
        transform: translateY(-50%);
        width: 3px;
        height: 0;
        background: #F9A825;
        border-radius: 0 3px 3px 0;
        transition: height 0.25s ease;
    }

    .sidebar-nav .nav-link:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(2px);
    }

    .sidebar-nav .nav-link.active {
        color: #fff;
        background: linear-gradient(90deg, rgba(27, 94, 32, 0.9), rgba(27, 94, 32, 0.6));
        font-weight: 600;
        box-shadow: 0 4px 15px rgba(27, 94, 32, 0.3);
    }

    .sidebar-nav .nav-link.active::before {
        height: 60%;
    }

    .sidebar-nav .nav-link i {
        width: 20px;
        text-align: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .sidebar-footer {
        padding: 12px 12px 20px;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        flex-shrink: 0;
    }

    .sidebar-footer .nav-link {
        padding: 12px 20px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 0.88rem;
        font-weight: 500;
        transition: all 0.25s ease;
        text-decoration: none;
    }

    .sidebar-footer .nav-link:hover {
        background: rgba(220, 53, 69, 0.15);
    }

    .sidebar-footer .nav-link i {
        width: 20px;
        text-align: center;
    }

    /* Main content offset */
    .main-content {
        margin-left: 260px;
        width: calc(100% - 260px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .admin-sidebar {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .admin-sidebar.show {
            transform: translateX(0);
        }

        .main-content {
            margin-left: 0;
            width: 100%;
        }
    }
</style>