<?php
// Include functions file
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/config.php';

// Define base URL for links
$base_url = BASE_URL;
$is_logged_in = isLoggedIn();
$user_name = $is_logged_in ? getCurrentUserName() : 'Guest';
$user_role = $is_logged_in ? getCurrentUserRole() : '';
$cart_count = $is_logged_in ? getCartCount() : 0;
$unread_count = $is_logged_in ? getUnreadMessageCount() : 0;
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>EthioServe - Premium Platform</title>

    <!-- PWA Meta Tags -->
    <meta name="theme-color" content="#1B5E20">
    <meta name="description"
        content="EthioServe - Food delivery, hotel booking, transport & broker services across Ethiopia.">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="EthioServe">
    <meta name="application-name" content="EthioServe">
    <meta name="msapplication-TileColor" content="#1B5E20">

    <!-- PWA Manifest -->
    <link rel="manifest" href="<?php echo $base_url; ?>/manifest.php">

    <!-- App Icons -->
    <link rel="icon" type="image/svg+xml" href="<?php echo $base_url; ?>/assets/icons/icon.svg">
    <link rel="apple-touch-icon" sizes="152x152" href="<?php echo $base_url; ?>/assets/icons/icon-152x152.png">
    <link rel="apple-touch-icon" sizes="192x192" href="<?php echo $base_url; ?>/assets/icons/icon-192x192.png">
    <link rel="apple-touch-icon" sizes="512x512" href="<?php echo $base_url; ?>/assets/icons/icon-512x512.png">

    <!-- Splash screen for iOS -->
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/assets/css/style.css">
</head>

<body>

    <!-- Customer Navbar -->
    <nav class="navbar navbar-expand-lg sticky-top bg-white shadow-sm">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo $base_url; ?>/customer/index.php">
                <img src="<?php echo $base_url; ?>/assets/img/AA.jpg" alt="Dev"
                    class="rounded-circle shadow-sm border border-2 border-warning"
                    style="width: 45px; height: 45px; object-fit: cover;"
                    onerror="this.src='https://ui-avatars.com/api/?name=Mequannent+Gashaw&background=1B5E20&color=fff&bold=true'">
                <span class="fw-bold fs-3 text-primary-green">Ethio<span class="text-warning">Serve</span></span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <div class="mx-auto w-50 d-none d-lg-block position-relative" id="search-wrapper">
                    <form class="input-group" action="<?php echo $base_url; ?>/customer/index.php" method="GET"
                        id="search-form" autocomplete="off">
                        <input type="text" name="search" id="live-search-input"
                            class="form-control rounded-pill-start border-end-0"
                            placeholder="Search restaurants, hotels, taxis, buses..."
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        <button class="btn btn-outline-secondary border-start-0 ps-0 pe-3 rounded-pill-end"
                            type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <!-- Live Search Dropdown -->
                    <div id="live-search-results" class="live-search-dropdown"></div>
                </div>

                <!-- Live Search Styles & Script -->
                <style>
                    .live-search-dropdown {
                        display: none;
                        position: absolute;
                        top: calc(100% + 6px);
                        left: 0;
                        right: 0;
                        background: #fff;
                        border-radius: 16px;
                        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18), 0 0 0 1px rgba(0, 0, 0, 0.04);
                        z-index: 9999;
                        max-height: 440px;
                        overflow-y: auto;
                        animation: searchDropIn 0.25s ease-out;
                    }

                    .live-search-dropdown.active {
                        display: block;
                    }

                    @keyframes searchDropIn {
                        from {
                            opacity: 0;
                            transform: translateY(-8px);
                        }

                        to {
                            opacity: 1;
                            transform: translateY(0);
                        }
                    }

                    .live-search-dropdown::-webkit-scrollbar {
                        width: 6px;
                    }

                    .live-search-dropdown::-webkit-scrollbar-thumb {
                        background: #ccc;
                        border-radius: 3px;
                    }

                    .search-category-label {
                        font-size: 0.68rem;
                        font-weight: 700;
                        letter-spacing: 1.5px;
                        text-transform: uppercase;
                        color: #9e9e9e;
                        padding: 12px 18px 6px;
                        margin: 0;
                    }

                    .search-item {
                        display: flex;
                        align-items: center;
                        gap: 14px;
                        padding: 10px 18px;
                        text-decoration: none;
                        color: #333;
                        transition: background 0.15s ease;
                        cursor: pointer;
                        border-bottom: 1px solid #f5f5f5;
                    }

                    .search-item:last-child {
                        border-bottom: none;
                    }

                    .search-item:hover,
                    .search-item.active-item {
                        background: linear-gradient(90deg, #e8f5e9, #f1f8e9);
                        color: #1B5E20;
                    }

                    .search-item-icon {
                        width: 40px;
                        height: 40px;
                        border-radius: 12px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        font-size: 0.95rem;
                        color: #fff;
                        flex-shrink: 0;
                    }

                    .search-item-info {
                        flex: 1;
                        min-width: 0;
                    }

                    .search-item-name {
                        font-weight: 600;
                        font-size: 0.88rem;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .search-item-desc {
                        font-size: 0.75rem;
                        color: #888;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    }

                    .search-item-badge {
                        font-size: 0.65rem;
                        font-weight: 700;
                        padding: 3px 8px;
                        border-radius: 6px;
                        white-space: nowrap;
                        flex-shrink: 0;
                    }

                    .search-no-results {
                        padding: 30px 20px;
                        text-align: center;
                        color: #aaa;
                    }

                    .search-no-results i {
                        font-size: 2rem;
                        margin-bottom: 8px;
                        display: block;
                        color: #ddd;
                    }

                    .search-loading {
                        padding: 20px;
                        text-align: center;
                        color: #aaa;
                    }

                    .search-loading .spinner-border {
                        width: 1.2rem;
                        height: 1.2rem;
                        border-width: 2px;
                    }

                    .search-footer {
                        padding: 10px 18px;
                        text-align: center;
                        font-size: 0.75rem;
                        color: #bbb;
                        background: #fafafa;
                        border-radius: 0 0 16px 16px;
                        border-top: 1px solid #f0f0f0;
                    }

                    .search-footer kbd {
                        background: #eee;
                        border: 1px solid #ddd;
                        border-radius: 4px;
                        padding: 1px 6px;
                        font-size: 0.7rem;
                        color: #666;
                    }
                </style>

                <script>
                    document.addEventListener('DOMContentLoaded', function () {
                        const input = document.getElementById('live-search-input');
                        const dropdown = document.getElementById('live-search-results');
                        const wrapper = document.getElementById('search-wrapper');
                        const baseUrl = '<?php echo $base_url; ?>';
                        let debounceTimer = null;
                        let activeIndex = -1;
                        let currentResults = [];

                        if (!input || !dropdown) return;

                        // Debounced search
                        input.addEventListener('input', function () {
                            const q = this.value.trim();
                            clearTimeout(debounceTimer);
                            activeIndex = -1;

                            if (q.length < 1) {
                                hideDropdown();
                                return;
                            }

                            // Show loading
                            dropdown.innerHTML = '<div class="search-loading"><div class="spinner-border text-success" role="status"></div><div class="mt-2">Searching...</div></div>';
                            showDropdown();

                            debounceTimer = setTimeout(() => fetchResults(q), 300);
                        });

                        // Show dropdown on focus if has value
                        input.addEventListener('focus', function () {
                            const q = this.value.trim();
                            if (q.length >= 1) {
                                if (dropdown.innerHTML.trim() !== '') {
                                    showDropdown();
                                } else {
                                    fetchResults(q);
                                }
                            }
                        });

                        // Keyboard navigation
                        input.addEventListener('keydown', function (e) {
                            const items = dropdown.querySelectorAll('.search-item');
                            if (!items.length) return;

                            if (e.key === 'ArrowDown') {
                                e.preventDefault();
                                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                                updateActive(items);
                            } else if (e.key === 'ArrowUp') {
                                e.preventDefault();
                                activeIndex = Math.max(activeIndex - 1, 0);
                                updateActive(items);
                            } else if (e.key === 'Enter' && activeIndex >= 0) {
                                e.preventDefault();
                                items[activeIndex].click();
                            } else if (e.key === 'Escape') {
                                hideDropdown();
                            }
                        });

                        function updateActive(items) {
                            items.forEach((el, i) => {
                                el.classList.toggle('active-item', i === activeIndex);
                                if (i === activeIndex) el.scrollIntoView({ block: 'nearest' });
                            });
                        }

                        // Close dropdown on outside click
                        document.addEventListener('click', function (e) {
                            if (!wrapper.contains(e.target)) {
                                hideDropdown();
                            }
                        });

                        function showDropdown() { dropdown.classList.add('active'); }
                        function hideDropdown() { dropdown.classList.remove('active'); activeIndex = -1; }

                        function fetchResults(query) {
                            fetch(baseUrl + '/includes/search_api.php?q=' + encodeURIComponent(query))
                                .then(res => res.json())
                                .then(data => {
                                    currentResults = data.results || [];
                                    renderResults(currentResults, query);
                                })
                                .catch(() => {
                                    dropdown.innerHTML = '<div class="search-no-results"><i class="fas fa-exclamation-circle"></i>Search unavailable</div>';
                                });
                        }

                        function renderResults(results, query) {
                            if (!results.length) {
                                dropdown.innerHTML = `
                                <div class="search-no-results">
                                    <i class="fas fa-search"></i>
                                    <div>No results for "<strong>${escapeHtml(query)}</strong>"</div>
                                    <div style="font-size:0.75rem;margin-top:4px;">Try searching for restaurants, hotels, or cities</div>
                                </div>`;
                                showDropdown();
                                return;
                            }

                            // Group by category
                            const grouped = {};
                            results.forEach(r => {
                                if (!grouped[r.category]) grouped[r.category] = [];
                                grouped[r.category].push(r);
                            });

                            let html = '';
                            for (const [category, items] of Object.entries(grouped)) {
                                html += `<div class="search-category-label">${escapeHtml(category)}</div>`;
                                items.forEach(item => {
                                    const desc = item.description ? escapeHtml(item.description) : '';
                                    const extra = item.extra ? escapeHtml(item.extra) : '';
                                    html += `
                                    <a href="${item.link}" class="search-item" data-name="${escapeHtml(item.name)}">
                                        <div class="search-item-icon" style="background:${item.color}">
                                            <i class="${item.icon}"></i>
                                        </div>
                                        <div class="search-item-info">
                                            <div class="search-item-name">${highlightMatch(item.name, query)}</div>
                                            <div class="search-item-desc">${desc}${desc && extra ? ' · ' : ''}${extra}</div>
                                        </div>
                                        <span class="search-item-badge" style="background:${item.color}15;color:${item.color}">${escapeHtml(category)}</span>
                                    </a>`;
                                });
                            }
                            html += `<div class="search-footer"><kbd>↑↓</kbd> Navigate · <kbd>Enter</kbd> Select · <kbd>Esc</kbd> Close</div>`;

                            dropdown.innerHTML = html;
                            showDropdown();
                        }

                        function highlightMatch(text, query) {
                            const escaped = escapeHtml(text);
                            const regex = new RegExp(`(${escapeRegex(query)})`, 'gi');
                            return escaped.replace(regex, '<strong style="color:#1B5E20">$1</strong>');
                        }

                        function escapeHtml(str) {
                            const div = document.createElement('div');
                            div.appendChild(document.createTextNode(str));
                            return div.innerHTML;
                        }

                        function escapeRegex(str) {
                            return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                        }
                    });
                </script>

                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $base_url; ?>/realestate/index.php">
                            <i class="fas fa-building fs-5 text-primary-green"></i>
                            <span class="d-none d-sm-inline ms-1">Real Estate</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo $base_url; ?>/customer/booking.php">
                            <i class="fas fa-calendar-check fs-5 text-primary-green"></i>
                            <span class="d-none d-sm-inline ms-1">Book</span>
                        </a>
                    </li>

                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link px-3 position-relative" href="<?php echo $base_url; ?>/customer/cart.php">
                                <i class="fas fa-shopping-cart fs-5 text-primary-green"></i>
                                <?php if ($cart_count > 0): ?>
                                    <span
                                        class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger cart-badge">
                                        <?php echo $cart_count; ?>
                                    </span>
                                <?php endif; ?>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3" href="<?php echo $base_url; ?>/customer/track_order.php">
                                <i class="fas fa-truck fs-5 text-primary-green"></i>
                                <span class="d-none d-sm-inline ms-1">Orders</span>
                            </a>
                        </li>
                        <li class="nav-item dropdown ms-lg-2">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" id="userDropdown"
                                role="button" data-bs-toggle="dropdown">
                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=1B5E20&color=fff"
                                    class="rounded-circle" width="32" height="32" alt="Profile">
                                <span class="d-none d-sm-inline position-relative">
                                    <?php echo htmlspecialchars($user_name); ?>
                                    <?php if ($unread_count > 0): ?>
                                        <span
                                            class="position-absolute top-0 start-100 translate-middle p-1 bg-danger border border-light rounded-circle"
                                            style="left: 105% !important; top: 0 !important;">
                                            <span class="visually-hidden">New messages</span>
                                        </span>
                                    <?php endif; ?>
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                                <li>
                                    <span class="dropdown-header text-muted">
                                        <small>Logged in as <strong><?php echo ucfirst($user_role); ?></strong></small>
                                    </span>
                                </li>
                                <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/profile.php">
                                        <i class="fas fa-user-circle me-2"></i> My Profile</a>
                                </li>
                                <li><a class="dropdown-item py-2 d-flex justify-content-between align-items-center"
                                        href="<?php echo $base_url; ?>/customer/messages.php">
                                        <span><i class="fas fa-envelope me-2"></i> Messages</span>
                                        <?php if ($unread_count > 0): ?>
                                            <span class="badge rounded-pill bg-danger"><?php echo $unread_count; ?></span>
                                        <?php endif; ?>
                                    </a>
                                </li>

                                <?php if ($user_role == 'customer'): ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header text-uppercase small" style="font-size: 0.65rem;">My Activity
                                        </h6>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/track_order.php">
                                            <i class="fas fa-shopping-bag me-2 text-primary-green"></i> My Orders & Bus
                                            Tickets</a>
                                    </li>
                                    <li><a class="dropdown-item py-2"
                                            href="<?php echo $base_url; ?>/customer/my_home_bookings.php">
                                            <i class="fas fa-tools me-2 text-primary-green"></i> Home Service Bookings</a>
                                    </li>
                                    <li><a class="dropdown-item py-2"
                                            href="<?php echo $base_url; ?>/customer/medical_records.php">
                                            <i class="fas fa-file-medical me-2 text-primary-green"></i> Medical Records</a>
                                    </li>

                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <h6 class="dropdown-header text-uppercase small" style="font-size: 0.65rem;">Explore
                                            Services</h6>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/restaurants.php">
                                            <i class="fas fa-utensils me-2 text-primary-green"></i> Restaurants & Food</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/rent.php">
                                            <i class="fas fa-home me-2 text-primary-green"></i> Rent & Real Estate</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/taxi.php">
                                            <i class="fas fa-taxi me-2 text-primary-green"></i> Taxi & Rides</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/jobs.php">
                                            <i class="fas fa-briefcase me-2 text-primary-green"></i> Jobs & Freelance</a>
                                    </li>
                                    <li><a class="dropdown-item py-2"
                                            href="<?php echo $base_url; ?>/customer/health_services.php">
                                            <i class="fas fa-heartbeat me-2 text-primary-green"></i> Health & Doctors</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/community.php">
                                            <i class="fas fa-users me-2 text-primary-green"></i> Community & Social</a>
                                    </li>
                                    <li><a class="dropdown-item py-2"
                                            href="<?php echo $base_url; ?>/customer/dating_matches.php">
                                            <i class="fas fa-heart me-2 text-primary-green"></i> Dating & Matches</a>
                                    </li>
                                    <li><a class="dropdown-item py-2"
                                            href="<?php echo $base_url; ?>/customer/coming_soon.php?service=Movies">
                                            <i class="fas fa-film me-2 text-primary-green"></i> Cinema & Movies</a>
                                    </li>
                                <?php else: ?>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/track_order.php">
                                            <i class="fas fa-receipt me-2"></i> My Orders</a>
                                    </li>
                                    <li><a class="dropdown-item py-2" href="<?php echo $base_url; ?>/customer/booking.php">
                                            <i class="fas fa-calendar me-2"></i> My Bookings</a>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>

                                <?php if ($user_role == 'hotel'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/hotel/dashboard.php">
                                            <i class="fas fa-hotel me-2 text-primary"></i> My Dashboard</a></li>
                                <?php elseif ($user_role == 'broker'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/broker/dashboard.php">
                                            <i class="fas fa-handshake me-2 text-success"></i> My Dashboard</a></li>
                                <?php elseif ($user_role == 'admin'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/admin/dashboard.php">
                                            <i class="fas fa-user-shield me-2 text-danger"></i> My Dashboard</a></li>
                                <?php elseif ($user_role == 'doctor'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/doctor/dashboard.php">
                                            <i class="fas fa-stethoscope me-2 text-info"></i> My Dashboard</a></li>
                                    <li><a class="dropdown-item py-1"
                                            href="<?php echo $base_url; ?>/doctor/dashboard.php#appointments">
                                            <i class="fas fa-calendar-check me-2 text-muted" style="width: 20px;"></i>
                                            Appointments</a></li>
                                    <li><a class="dropdown-item py-1"
                                            href="<?php echo $base_url; ?>/doctor/dashboard.php#chats">
                                            <i class="fas fa-comments me-2 text-muted" style="width: 20px;"></i> Patient
                                            Chats</a></li>
                                <?php elseif ($user_role == 'employer'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/customer/employer_dashboard.php">
                                            <i class="fas fa-user-tie me-2 text-warning"></i> My Dashboard</a></li>
                                    <li><a class="dropdown-item py-1"
                                            href="<?php echo $base_url; ?>/customer/employer_dashboard.php?view=applications">
                                            <i class="fas fa-users me-2 text-muted" style="width: 20px;"></i> All Applicants</a>
                                    </li>
                                    <li><a class="dropdown-item py-1"
                                            href="<?php echo $base_url; ?>/customer/employer_dashboard.php">
                                            <i class="fas fa-comments me-2 text-muted" style="width: 20px;"></i> Candidate
                                            Chats</a></li>
                                <?php elseif ($user_role == 'restaurant'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/restaurant/dashboard.php">
                                            <i class="fas fa-utensils me-2 text-danger"></i> My Dashboard</a></li>
                                <?php elseif ($user_role == 'taxi'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/taxi/dashboard.php">
                                            <i class="fas fa-taxi me-2 text-warning"></i> My Dashboard</a></li>
                                <?php elseif ($user_role == 'transport'): ?>
                                    <li><a class="dropdown-item py-2 fw-bold"
                                            href="<?php echo $base_url; ?>/transport/dashboard.php">
                                            <i class="fas fa-bus me-2 text-success"></i> My Dashboard</a></li>
                                <?php endif; ?>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item py-2 text-danger" href="<?php echo $base_url; ?>/logout.php">
                                        <i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-primary-green rounded-pill px-4 me-2"
                                href="<?php echo $base_url; ?>/login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-primary-green rounded-pill px-4"
                                href="<?php echo $base_url; ?>/register.php">Sign Up</a>
                        </li>
                        <!-- DEMO CREDENTIALS TOOLTIP -->
                        <li class="nav-item ms-2">
                            <div class="dropdown">
                                <button class="btn btn-sm btn-warning rounded-circle shadow-sm" type="button"
                                    data-bs-toggle="dropdown" aria-expanded="false" title="Demo Credentials">
                                    <i class="fas fa-key text-white"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-3 shadow border-0" style="min-width: 250px;">
                                    <li>
                                        <h6 class="dropdown-header text-primary-green fw-bold">Demo Accounts</h6>
                                    </li>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <!-- Admin -->
                                    <li class="mb-2">
                                        <small class="d-block text-muted fw-bold">Admin</small>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                            <small class="text-dark">admin@ethioserve.com</small>
                                            <button onclick="copyToClipboard('admin@ethioserve.com')"
                                                class="btn btn-xs text-primary"><i class="far fa-copy"></i></button>
                                        </div>
                                        <small class="text-muted fst-italic ms-1" style="font-size:0.75rem">Pass:
                                            admin123</small>
                                    </li>
                                    <!-- Transport Owner -->
                                    <li class="mb-2">
                                        <small class="d-block text-muted fw-bold">Transport Owner</small>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                            <small class="text-dark">transport@ethioserve.com</small>
                                            <button onclick="copyToClipboard('transport@ethioserve.com')"
                                                class="btn btn-xs text-primary"><i class="far fa-copy"></i></button>
                                        </div>
                                        <small class="text-muted fst-italic ms-1" style="font-size:0.75rem">Pass:
                                            transport123</small>
                                    </li>
                                    <!-- Customer -->
                                    <li class="mb-2">
                                        <small class="d-block text-muted fw-bold">Customer</small>
                                        <div class="d-flex justify-content-between align-items-center bg-light p-2 rounded">
                                            <small class="text-dark">customer1@ethioserve.com</small>
                                            <button onclick="copyToClipboard('customer1@ethioserve.com')"
                                                class="btn btn-xs text-primary"><i class="far fa-copy"></i></button>
                                        </div>
                                        <small class="text-muted fst-italic ms-1" style="font-size:0.75rem">Pass:
                                            customer123</small>
                                    </li>
                                </ul>
                            </div>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    </script>

    <!-- Incoming Call Modal -->
    <div class="modal fade" id="incomingCallModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        style="z-index: 9999;">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-5 shadow-lg bg-dark text-white text-center p-4">
                <div class="modal-body">
                    <div class="position-relative d-inline-block mb-4">
                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center mx-auto shadow-lg"
                            style="width:100px;height:100px;">
                            <i class="fas fa-video display-4 text-white"></i>
                        </div>
                        <div class="position-absolute top-0 start-0 w-100 h-100 rounded-circle border border-4 border-primary animate-ping"
                            style="animation: ping 1.5s cubic-bezier(0, 0, 0.2, 1) infinite;"></div>
                    </div>
                    <h4 class="fw-bold mb-1" id="callerNameDisp">Incoming Call...</h4>
                    <p class="text-white-50 mb-4" id="callerDetailDisp">Someone is calling you for a video consultation.
                    </p>

                    <div class="d-flex justify-content-center gap-4">
                        <button id="declineCallBtn" class="btn btn-danger rounded-circle p-3 shadow-lg"
                            style="width:70px;height:70px;" title="Decline">
                            <i class="fas fa-phone-slash fs-3"></i>
                        </button>
                        <button id="acceptCallBtn" class="btn btn-success rounded-circle p-3 shadow-lg"
                            style="width:70px;height:70px;" title="Accept">
                            <i class="fas fa-phone fs-3"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <audio id="ringtoneIncoming" loop preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-marimba-ringtone-1359.mp3" type="audio/mpeg">
    </audio>
    <audio id="ringtoneOutgoing" loop preload="auto">
        <source src="https://assets.mixkit.co/sfx/preview/mixkit-outgoing-call-waiting-ringtone-1353.mp3"
            type="audio/mpeg">
    </audio>

    <style>
        @keyframes ping {

            75%,
            100% {
                transform: scale(1.6);
                opacity: 0;
            }
        }

        #incomingCallModal .modal-content {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%) !important;
        }
    </style>

    <?php if ($is_logged_in): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let activeIncomingCall = null;
                const ringtoneIn = document.getElementById('ringtoneIncoming');
                const ringtoneOut = document.getElementById('ringtoneOutgoing');
                const incModalEl = document.getElementById('incomingCallModal');
                let audioEnabled = false;

                console.log("EthioServe Call System: Initialized for <?php echo $user_role; ?>");

                const getModal = () => {
                    if (typeof bootstrap !== 'undefined') {
                        return bootstrap.Modal.getOrCreateInstance(incModalEl);
                    }
                    return null;
                };

                // Unlock audio on first user interaction
                const unlockAudio = () => {
                    if (ringtoneIn) ringtoneIn.play().then(() => { ringtoneIn.pause(); ringtoneIn.currentTime = 0; }).catch(() => { });
                    if (ringtoneOut) ringtoneOut.play().then(() => { ringtoneOut.pause(); ringtoneOut.currentTime = 0; }).catch(() => { });
                    audioEnabled = true;
                    console.log("EthioServe Call System: Audio Unlocked");
                    document.removeEventListener('click', unlockAudio);
                    document.removeEventListener('touchstart', unlockAudio);
                };
                document.addEventListener('click', unlockAudio);
                document.addEventListener('touchstart', unlockAudio);

                function checkIncomingCalls() {
                    const formData = new FormData();
                    formData.append('action', 'check_incoming');

                    fetch('<?php echo $base_url; ?>/includes/signaling.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(r => r.json())
                        .then(data => {
                            if (data.call && (!activeIncomingCall || activeIncomingCall.id != data.call.id)) {
                                console.log("%c[CALL SYSTEM] Incoming call detected", "color: white; background: #2E7D32; padding: 5px; border-radius: 5px; font-weight: bold;");
                                activeIncomingCall = data.call;
                                document.getElementById('callerNameDisp').textContent = data.call.caller_name;
                                document.getElementById('callerDetailDisp').textContent = "Incoming video call...";

                                const modal = getModal();
                                if (modal) modal.show();

                                if (audioEnabled && ringtoneIn) {
                                    setTimeout(() => {
                                        ringtoneIn.play().catch(e => console.warn("Audio play deferred", e));
                                    }, 200);
                                }
                            }
                        })
                        .catch(err => {
                            // Silently ignore or log connection drops
                        });
                }

                document.getElementById('acceptCallBtn').onclick = () => {
                    if (!activeIncomingCall) return;
                    const fd = new FormData();
                    fd.append('action', 'respond_call');
                    fd.append('call_id', activeIncomingCall.id);
                    fd.append('status', 'accepted');

                    fetch('<?php echo $base_url; ?>/includes/signaling.php', { method: 'POST', body: fd })
                        .then(() => {
                            if (ringtoneIn) ringtoneIn.pause();
                            let redirectUrl = '';
                            if ('<?php echo $user_role; ?>' === 'doctor') {
                                redirectUrl = '<?php echo $base_url; ?>/doctor/video_call.php?customer_id=' + activeIncomingCall.caller_id;
                            } else {
                                redirectUrl = '<?php echo $base_url; ?>/customer/doctor_video_call.php?doctor_id=' + activeIncomingCall.provider_id;
                            }
                            window.location.href = redirectUrl;
                        });
                };

                document.getElementById('declineCallBtn').onclick = () => {
                    if (!activeIncomingCall) return;
                    const fd = new FormData();
                    fd.append('action', 'respond_call');
                    fd.append('call_id', activeIncomingCall.id);
                    fd.append('status', 'rejected');

                    fetch('<?php echo $base_url; ?>/includes/signaling.php', { method: 'POST', body: fd })
                        .then(() => {
                            if (ringtoneIn) ringtoneIn.pause();
                            const modal = getModal();
                            if (modal) modal.hide();
                            activeIncomingCall = null;
                        });
                };

                // Check every 3 seconds for better responsiveness
                setInterval(checkIncomingCalls, 3000);
                setTimeout(checkIncomingCalls, 1000); // Initial check
            });
        </script>
    <?php endif; ?>