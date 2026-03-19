<!-- Sidebar -->
<nav id="sidebarMenu" class="sidebar-employer shadow-lg">
    <div class="sidebar-header">
        <div class="brand-box mx-auto">
            <i class="fas fa-briefcase"></i>
        </div>
        <h5 class="brand-name">Ethio<span class="text-warning">Serve</span></h5>
        <div class="user-badge mt-2">
            <span class="small text-white-50">Employer Panel</span>
        </div>
    </div>

    <div class="sidebar-nav-container">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/dashboard.php">
                    <i class="fas fa-home"></i> <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'jobs_management.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/jobs_management.php">
                    <i class="fas fa-tasks"></i> <span>My Job Listings</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/applications.php">
                    <i class="fas fa-user-tie"></i> <span>Applications</span>
                    <span class="badge bg-danger rounded-pill ms-auto" id="emp-app-count" style="display: none;">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'lms.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/lms.php">
                    <i class="fas fa-graduation-cap"></i> <span>LMS & Exams</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'company_profile.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/company_profile.php">
                    <i class="fas fa-building"></i> <span>Company Info</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/reports.php">
                    <i class="fas fa-chart-pie"></i> <span>Hiring Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer <?php echo basename($_SERVER['PHP_SELF']) == 'recycle_bin.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/employer/recycle_bin.php">
                    <i class="fas fa-trash-alt"></i> <span>Recycle Bin</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-employer" href="<?php echo BASE_URL; ?>/customer/jobs.php">
                    <i class="fas fa-search"></i> <span>View All Jobs</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-summary mb-3 p-2 rounded-3" style="background: rgba(255,255,255,0.05);">
            <div class="d-flex align-items-center gap-2">
                <div class="avatar-sm">
                    <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['full_name'] ?? 'E'); ?>&background=FFB300&color=000"
                        class="rounded-circle" width="32">
                </div>
                <div class="overflow-hidden">
                    <p class="mb-0 small fw-bold text-truncate text-white">
                        <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Employer'); ?>
                    </p>
                </div>
            </div>
        </div>
        <a class="nav-link-employer logout-btn text-warning mb-3" href="<?php echo BASE_URL; ?>/logout.php">
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
    .sidebar-employer {
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

    .nav-link-employer {
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

    .nav-link-employer i {
        width: 20px;
        font-size: 1.1rem;
        text-align: center;
    }

    .nav-link-employer:hover {
        color: #fff;
        background: rgba(255, 255, 255, 0.08);
        transform: translateX(5px);
    }

    .nav-link-employer.active {
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
        .sidebar-employer {
            transform: translateX(-100%);
            transition: transform 0.3s ease;
        }

        .sidebar-employer.active {
            transform: translateX(0);
        }
    }
</style>

<!-- Notification System -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const employerApiUrl = '<?php echo BASE_URL; ?>/api.php';
    let lastSeenAppId = 0;
    let lastUnreadCount = -1;

    document.addEventListener('DOMContentLoaded', () => {
        // Find existing max application IDs
        const appRows = document.querySelectorAll('tr[onclick*="view_application.php?id="], a[href*="view_application.php?id="]');
        appRows.forEach(row => {
            const attr = row.getAttribute('onclick') || row.getAttribute('href');
            const match = attr.match(/id=(\d+)/);
            if (match) {
                const id = parseInt(match[1]);
                if (id > lastSeenAppId) lastSeenAppId = id;
            }
        });

        // Start polling
        setInterval(checkApplications, 10000);
    });

    async function checkApplications() {
        try {
            const response = await fetch(`${employerApiUrl}?action=get_employer_notifications&last_app_id=${lastSeenAppId}`);
            const data = await response.json();

            if (data.success) {
                // New App Notifications
                if (data.new_applications && data.new_applications.length > 0) {
                    data.new_applications.forEach(app => {
                        showEmployerToast('New Job Application!', `${app.applicant_name} applied for "${app.job_title}"`, 'info', `<?php echo BASE_URL; ?>/employer/applications.php`);
                    });
                    
                    const maxId = Math.max(...data.new_applications.map(a => parseInt(a.id)));
                    if (maxId > lastSeenAppId) lastSeenAppId = maxId;
                }

                // Unread Message Notifications
                if (data.unread_messages > 0 && data.unread_messages > lastUnreadCount) {
                    if (lastUnreadCount !== -1) {
                        showEmployerToast('New Candidate Message!', `You have ${data.unread_messages} unread message(s).`, 'question', `<?php echo BASE_URL; ?>/employer/applications.php`);
                    }
                    lastUnreadCount = data.unread_messages;
                }

                // Update badges
                if (data.total_pending_apps !== undefined) updateEmployerBadge('emp-app-count', data.total_pending_apps);
            }
        } catch (error) { console.error('Notification Error:', error); }
    }

    function updateEmployerBadge(id, count) {
        const badge = document.getElementById(id);
        if (badge) {
            if (count > 0) {
                badge.innerText = count;
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function showEmployerToast(title, message, icon, url) {
        // High-quality notification sound for employers
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3');
        audio.volume = 0.6;
        audio.play().catch(() => {});

        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'View',
            confirmButtonColor: '#1B5E20',
            showCancelButton: true,
            timer: 15000,
            timerProgressBar: true,
            background: '#fff',
            color: '#1B5E20',
            didOpen: (toast) => {
                toast.style.cursor = 'pointer';
                toast.onclick = () => window.location.href = url;
            }
        }).then((result) => { if (result.isConfirmed) window.location.href = url; });
    }
</script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
