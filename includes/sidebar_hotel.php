<!-- Sidebar -->
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
                    <span class="badge bg-danger rounded-pill ms-auto" id="hotel-order-count" style="display: none;">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'bookings.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/bookings.php">
                    <i class="fas fa-calendar-alt"></i> <span>Bookings</span>
                    <span class="badge bg-danger rounded-pill ms-auto" id="hotel-booking-count" style="display: none;">0</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'menu_management.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/menu_management.php">
                    <i class="fas fa-utensils"></i> <span>Food Menu</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'rooms_management.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/rooms_management.php">
                    <i class="fas fa-bed"></i> <span>Room Types</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'transactions.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/transactions.php">
                    <i class="fas fa-file-invoice-dollar"></i> <span>Transactions</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/reports.php">
                    <i class="fas fa-chart-bar"></i> <span>Analytics Reports</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel <?php echo basename($_SERVER['PHP_SELF']) == 'recycle_bin.php' ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>/hotel/recycle_bin.php">
                    <i class="fas fa-trash-restore"></i> <span>Recycle Bin</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link-hotel" href="<?php echo BASE_URL; ?>/customer/index.php">
                    <i class="fas fa-store"></i> <span>View Site</span>
                </a>
            </li>
        </ul>
    </div>

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
</style>
<!-- SweetAlert2 for Premium Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    // Real-time Notification Polling
    let hotelApiUrl = '<?php echo BASE_URL; ?>/api.php';
    let lastSeenOrderId = 0;
    let lastSeenBookingId = 0;
    let isInitialized = false;

    document.addEventListener('DOMContentLoaded', () => {
        // Find existing maximum IDs from the page
        const findMaxIds = () => {
            const elements = document.querySelectorAll('strong, .order-id, .booking-id');
            elements.forEach(el => {
                const text = el.innerText;
                const oMatch = text.match(/#(\d+)/);
                if (oMatch) {
                    const id = parseInt(oMatch[1]);
                    if (id > lastSeenOrderId) lastSeenOrderId = id;
                }
            });
        };

        findMaxIds();
        
        // Start polling - reduced to 4 seconds for better responsiveness
        setInterval(checkNotifications, 4000);
        
        // Immediate first check
        setTimeout(checkNotifications, 1000);
    });

    async function checkNotifications() {
        try {
            const response = await fetch(`${hotelApiUrl}?action=get_hotel_notifications&last_order_id=${lastSeenOrderId}&last_booking_id=${lastSeenBookingId}`);
            const data = await response.json();

            if (data.success) {
                // New Orders
                if (data.new_orders && data.new_orders.length > 0) {
                    data.new_orders.forEach(order => {
                        const orderIdNum = parseInt(order.id);
                        if (orderIdNum > lastSeenOrderId) {
                            if (isInitialized) {
                                showNotification('New Order Recevied!', `Order #${order.id.toString().padStart(5, '0')} by ${order.customer_name}`, 'success', 'orders.php');
                            }
                            lastSeenOrderId = orderIdNum;
                        }
                    });
                }

                // New Bookings
                if (data.new_bookings && data.new_bookings.length > 0) {
                    data.new_bookings.forEach(booking => {
                        const bookingIdNum = parseInt(booking.id);
                        if (bookingIdNum > lastSeenBookingId) {
                            if (isInitialized) {
                                showNotification('New Room Booking!', `${booking.customer_name} booked a room (#${booking.id})`, 'info', 'bookings.php');
                            }
                            lastSeenBookingId = bookingIdNum;
                        }
                    });
                }

                // Update Sidebar Badges
                if (data.total_pending_orders !== undefined) updateHotelBadge('hotel-order-count', data.total_pending_orders);
                if (data.total_pending_bookings !== undefined) updateHotelBadge('hotel-booking-count', data.total_pending_bookings);
                
                isInitialized = true; // Mark as initialized after first successful poll
            }
        } catch (err) {
            console.warn('Notification poll failed:', err);
        }
    }

    function updateHotelBadge(id, count) {
        const badge = document.getElementById(id);
        if (badge) {
            if (count > 0) {
                if (badge.innerText != count) {
                    badge.innerText = count;
                    badge.classList.add('animate__animated', 'animate__bounceIn');
                    setTimeout(() => badge.classList.remove('animate__bounceIn'), 1000);
                }
                badge.style.display = 'inline-block';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    function showNotification(title, message, icon, redirectUrl) {
        // Sound for notification
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/951/951-preview.mp3');
        audio.play().catch(e => console.log('Audio blocked by browser, click anywhere on page to enable sounds.'));

        Swal.fire({
            title: title,
            text: message,
            icon: icon,
            toast: true,
            position: 'top-end',
            showConfirmButton: true,
            confirmButtonText: 'View',
            confirmButtonColor: '#1B5E20',
            timer: 10000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.onclick = () => { window.location.href = redirectUrl; };
            }
        });
    }
</script>

<!-- Required Libraries -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>

