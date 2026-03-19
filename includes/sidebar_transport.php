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
                <a class="nav-link text-warning mb-3" href="../logout.php">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
                <!-- Developer Credit -->
                <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
                    class="text-decoration-none transition-all hover-scale d-block mx-3">
                    <div
                        class="py-2 px-3 bg-white bg-opacity-10 rounded-3 border border-white border-opacity-10 shadow-sm mt-2">
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

<!-- Transport Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const transportApiUrl = '<?php echo BASE_URL; ?>/api.php';
    let lastSeenBusBookingId = 0;

    document.addEventListener('DOMContentLoaded', () => {
        // Initial setup for existing bookings
        const findMaxIds = () => {
            const bookingRefs = document.querySelectorAll('.booking-ref, strong');
            bookingRefs.forEach(el => {
                const match = el.innerText.match(/BUS-(\w+)/);
                if (match) {
                    // Similar to taxi, we'll rely on the API for numeric ID tracking.
                }
            });
        };
        findMaxIds();

        // Start polling
        setInterval(checkTransportNotifications, 10000);
    });

    async function checkTransportNotifications() {
        try {
            const response = await fetch(`${transportApiUrl}?action=get_transport_notifications&last_booking_id=${lastSeenBusBookingId}`);
            const data = await response.json();

            if (data.success && data.new_bookings && data.new_bookings.length > 0) {
                data.new_bookings.forEach(booking => {
                    showTransportNotification('New Bus Booking!', `${booking.customer_name} booked seats for ${booking.travel_date}.`, 'info', `<?php echo BASE_URL; ?>/transport/bookings.php`);
                });
                const maxId = Math.max(...data.new_bookings.map(b => parseInt(b.id)));
                if (maxId > lastSeenBusBookingId) lastSeenBusBookingId = maxId;
            }
        } catch (error) { console.error('Transport Notification Error:', error); }
    }

    function showTransportNotification(title, message, icon, url) {
        // Distinct alert sound for transport bookings
        const audio = new Audio('https://assets.mixkit.co/active_storage/sfx/2568/2568-preview.mp3');
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
