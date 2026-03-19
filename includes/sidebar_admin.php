<?php 
$current_page = basename($_SERVER['PHP_SELF']); 
$user_role = $_SESSION['role'] ?? '';
?>
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
            <?php if ($user_role === 'school_admin'): ?>
                <span class="badge bg-success mt-1">School Admin</span>
            <?php else: ?>
                <span class="badge bg-danger mt-1">Super Admin</span>
            <?php endif; ?>
        </div>

        <!-- Navigation -->
        <div class="sidebar-nav-wrapper">
            <ul class="nav flex-column sidebar-nav">
                <!-- Dashboard -->
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'dashboard.php' || ($user_role === 'school_admin' && $current_page == 'manage_school.php' && !isset($_GET['view']))) ? 'active' : ''; ?>"
                        href="<?php echo $user_role === 'school_admin' ? 'manage_school.php' : 'dashboard.php'; ?>">
                        <i class="fas fa-home"></i>
                        <span>Dashboard</span>
                    </a>
                </li>

                <?php if ($user_role !== 'school_admin'): ?>
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
                <?php endif; ?>

                <!-- EDUCATION & ACADEMICS Section -->
                <li class="nav-section-title">
                    <span>ACADEMICS & LMS</span>
                </li>

                <!-- School Management -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_school.php' ? 'active' : ''; ?>" 
                       data-bs-toggle="collapse" href="#schoolSubmenu" role="button" 
                       aria-expanded="<?php echo $current_page == 'manage_school.php' ? 'true' : 'false'; ?>">
                        <i class="fas fa-school"></i>
                        <span>School Management</span>
                        <i class="fas fa-chevron-down ms-auto small opacity-50 transition-transform <?php echo $current_page == 'manage_school.php' ? 'rotate-180' : ''; ?>"></i>
                    </a>
                    <div class="collapse <?php echo $current_page == 'manage_school.php' ? 'show' : ''; ?>" id="schoolSubmenu">
                        <ul class="nav flex-column ps-4 mb-2">
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_school.php' && ($_GET['view'] ?? '') == 'classes') ? 'active-sub' : ''; ?>" 
                                   href="manage_school.php?view=classes">
                                    <i class="fas fa-chalkboard small"></i> Classes
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_school.php' && ($_GET['view'] ?? '') == 'teachers') ? 'active-sub' : ''; ?>" 
                                   href="manage_school.php?view=teachers">
                                    <i class="fas fa-user-tie small"></i> Teachers
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_school.php' && ($_GET['view'] ?? '') == 'students') ? 'active-sub' : ''; ?>" 
                                   href="manage_school.php?view=students">
                                    <i class="fas fa-user-graduate small"></i> Students
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_school.php' && ($_GET['view'] ?? '') == 'finance') ? 'active-sub' : ''; ?>" 
                                   href="manage_school.php?view=finance">
                                    <i class="fas fa-file-invoice-dollar small"></i> Finance
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Manage Education (Admin Resources) -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_education.php' ? 'active' : ''; ?>"
                       data-bs-toggle="collapse" href="#eduSubmenu" role="button" 
                       aria-expanded="<?php echo $current_page == 'manage_education.php' ? 'true' : 'false'; ?>">
                        <i class="fas fa-graduation-cap"></i>
                        <span>Admin Resources</span>
                        <i class="fas fa-chevron-down ms-auto small opacity-50 transition-transform <?php echo $current_page == 'manage_education.php' ? 'rotate-180' : ''; ?>"></i>
                    </a>
                    <div class="collapse <?php echo $current_page == 'manage_education.php' ? 'show' : ''; ?>" id="eduSubmenu">
                        <ul class="nav flex-column ps-4 mb-2">
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_education.php' && (($_GET['type'] ?? '') == 'textbook' || !isset($_GET['type']))) ? 'active-sub' : ''; ?>" 
                                   href="manage_education.php?type=textbook">
                                    <i class="fas fa-book small"></i> Textbooks
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_education.php' && ($_GET['type'] ?? '') == 'video') ? 'active-sub' : ''; ?>" 
                                   href="manage_education.php?type=video">
                                    <i class="fas fa-play-circle small"></i> Video Lessons
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- Manage LMS -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_lms.php' ? 'active' : ''; ?>"
                       data-bs-toggle="collapse" href="#lmsSubmenu" role="button" 
                       aria-expanded="<?php echo $current_page == 'manage_lms.php' ? 'true' : 'false'; ?>">
                        <i class="fas fa-brain"></i>
                        <span>LMS & Exams</span>
                        <i class="fas fa-chevron-down ms-auto small opacity-50 transition-transform <?php echo $current_page == 'manage_lms.php' ? 'rotate-180' : ''; ?>"></i>
                    </a>
                    <div class="collapse <?php echo $current_page == 'manage_lms.php' ? 'show' : ''; ?>" id="lmsSubmenu">
                        <ul class="nav flex-column ps-4 mb-2">
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_lms.php' && (($_GET['view'] ?? '') == 'list' || !isset($_GET['view']))) ? 'active-sub' : ''; ?>" 
                                   href="manage_lms.php?view=list">
                                    <i class="fas fa-file-alt small"></i> Manage Exams
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo ($current_page == 'manage_lms.php' && ($_GET['view'] ?? '') == 'chapters') ? 'active-sub' : ''; ?>" 
                                   href="manage_lms.php?view=chapters">
                                    <i class="fas fa-book-open small"></i> Chapters
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link text-info fw-bold" href="../customer/lms.php" target="_blank">
                                    <i class="fas fa-external-link-alt small"></i> View Public LMS Portal
                                </a>
                            </li>
                        </ul>
                    </div>
                </li>

                <!-- School Portal Login (Functional Link) -->
                <li class="nav-item">
                    <a class="nav-link portal-link" href="../customer/school_portal.php" target="_blank">
                        <i class="fas fa-sign-in-alt" style="color:#F9A825;"></i>
                        <span style="color:#F9A825; font-weight:700;">School Portal Login</span>
                        <div class="ms-auto badge bg-warning text-dark" style="font-size: 0.55rem; padding: 2px 6px;">PORTAL</div>
                    </a>
                </li>

                <?php if ($user_role !== 'school_admin'): ?>
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
                <?php endif; ?>

                <?php if ($user_role !== 'school_admin'): ?>
                <!-- MARKETPLACE Section -->
                <li class="nav-section-title">
                    <span>MARKETPLACE</span>
                </li>

                <!-- Manage Brokers -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'manage_brokers.php' || $current_page == 'manage_referrals.php' ? 'active' : ''; ?>"
                        data-bs-toggle="collapse" href="#brokerSubmenu" role="button" 
                        aria-expanded="<?php echo $current_page == 'manage_brokers.php' || $current_page == 'manage_referrals.php' ? 'true' : 'false'; ?>">
                        <i class="fas fa-user-tie"></i>
                        <span>Manage Agents</span>
                        <i class="fas fa-chevron-down ms-auto small opacity-50 transition-transform <?php echo ($current_page == 'manage_brokers.php' || $current_page == 'manage_referrals.php') ? 'rotate-180' : ''; ?>"></i>
                    </a>
                    <div class="collapse <?php echo ($current_page == 'manage_brokers.php' || $current_page == 'manage_referrals.php') ? 'show' : ''; ?>" id="brokerSubmenu">
                        <ul class="nav flex-column ps-4 mb-2">
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo $current_page == 'manage_brokers.php' ? 'active-sub' : ''; ?>" href="manage_brokers.php">
                                    <i class="fas fa-users-cog small"></i> Broker Accounts
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link sub-nav-link <?php echo $current_page == 'manage_referrals.php' ? 'active-sub' : ''; ?>" href="manage_referrals.php">
                                    <i class="fas fa-comment-dollar small"></i> Referrals & Payouts
                                </a>
                            </li>
                        </ul>
                    </div>
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
                <?php endif; ?>


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

                <?php if ($user_role !== 'school_admin'): ?>
                <!-- Activity Log -->
                <li class="nav-item">
                    <a class="nav-link <?php echo $current_page == 'activity_log.php' ? 'active' : ''; ?>"
                        href="activity_log.php">
                        <i class="fas fa-list-ul"></i>
                        <span>Activity Log</span>
                    </a>
                </li>
                <?php endif; ?>

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
        background: <?php echo $user_role === 'school_admin' ? '#1B5E20' : 'linear-gradient(180deg, #1B5E20 0%, #2E7D32 50%, #1B5E20 100%)'; ?>;
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

    /* Sub-navigation Styling */
    .sub-nav-link {
        padding: 5px 15px !important;
        margin: 1px 0 1px 12px !important;
        font-size: 0.8rem !important;
        opacity: 0.7;
        background: transparent !important;
        box-shadow: none !important;
    }
    
    .sub-nav-link:hover {
        opacity: 1;
        background: rgba(255,255,255,0.05) !important;
        transform: translateX(3px) !important;
    }

    .sub-nav-link.active-sub {
        opacity: 1;
        color: #F9A825 !important;
        font-weight: 600;
    }

    .sub-nav-link::before {
        display: none !important;
    }

    .rotate-180 {
        transform: rotate(180deg);
    }

    .transition-transform {
        transition: transform 0.3s ease;
    }

    .portal-link {
        background: rgba(249,168,37,0.12); 
        border-left: 3px solid #F9A825; 
        margin: 8px 12px !important; 
        border-radius: 8px;
    }

    .portal-link:hover {
        background: rgba(249,168,37,0.2) !important;
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
        padding: 2.5rem;
        min-height: 100vh;
        width: calc(100% - 260px);
        transition: all 0.3s;
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

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    let lastHotelId = 0;
    let lastRestId = 0;

    async function checkAdminNotifications() {
        try {
            const res = await fetch(`../api.php?action=get_admin_notifications&last_hotel_id=${lastHotelId}&last_rest_id=${lastRestId}`);
            const data = await res.json();

            if (data.success) {
                if (data.new_hotels && data.new_hotels.length > 0) {
                    data.new_hotels.forEach(h => {
                        showAdminToast('New Hotel Registration', `${h.name} is awaiting approval.`);
                    });
                    lastHotelId = Math.max(...data.new_hotels.map(h => h.id));
                }
                if (data.new_restaurants && data.new_restaurants.length > 0) {
                    data.new_restaurants.forEach(r => {
                        showAdminToast('New Restaurant registration', `${r.name} is awaiting approval.`);
                    });
                    lastRestId = Math.max(...data.new_restaurants.map(r => r.id));
                }
            }
        } catch (e) { console.error('Admin Check Error:', e); }
    }

    function showAdminToast(title, msg) {
        new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3').play().catch(() => {});
        Swal.fire({
            title: title, text: msg, icon: 'info', toast: true, position: 'top-end', 
            showConfirmButton: true, confirmButtonText: 'Review', showCancelButton: true,
            timer: 10000, timerProgressBar: true
        }).then(r => { if (r.isConfirmed) window.location.href = 'dashboard.php'; });
    }

    // Initial delay so it doesn't fire on page load for EXISTING pendings
    setTimeout(() => {
        // Set initial IDs to avoid notifying for existing items
        fetch(`../api.php?action=get_admin_notifications`).then(r => r.json()).then(d => {
            if (d.success) {
                const hIds = d.new_hotels.map(h => h.id);
                if (hIds.length > 0) lastHotelId = Math.max(...hIds);
                const rIds = d.new_restaurants.map(r => r.id);
                if (rIds.length > 0) lastRestId = Math.max(...rIds);
            }
            setInterval(checkAdminNotifications, 15000);
        });
    }, 2000);
</script>