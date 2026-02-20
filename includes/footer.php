<?php require_once __DIR__ . '/config.php';
$base_url = BASE_URL; ?>
<footer class="bg-white pt-5 mt-5 border-top">
    <div class="container pb-4">
        <div class="row g-4">
            <div class="col-lg-4">
                <h5 class="fw-bold text-primary-green mb-3">EthioServe</h5>
                <p class="text-muted">Connecting people with the best food, reliable brokers, and seamless booking
                    services across Ethiopia. Modern solutions for modern needs.</p>
                <div class="d-flex gap-3 mt-3">
                    <a href="#" class="btn btn-outline-primary-green rounded-circle p-2"
                        style="width: 40px; height: 40px;"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" class="btn btn-outline-primary-green rounded-circle p-2"
                        style="width: 40px; height: 40px;"><i class="fab fa-instagram"></i></a>
                    <a href="#" class="btn btn-outline-primary-green rounded-circle p-2"
                        style="width: 40px; height: 40px;"><i class="fab fa-whatsapp"></i></a>
                </div>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">Company</h6>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><a href="#" class="text-decoration-none text-reset">About Us</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Contact</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Careers</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Blogs</a></li>
                </ul>
            </div>
            <div class="col-lg-2 col-md-4">
                <h6 class="fw-bold mb-3">Services</h6>
                <ul class="list-unstyled text-muted lh-lg">
                    <li><a href="#" class="text-decoration-none text-reset">Food Delivery</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Broker Portal</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Halls & Rooms</a></li>
                    <li><a href="#" class="text-decoration-none text-reset">Table Booking</a></li>
                </ul>
            </div>
            <div class="col-lg-4">
                <h6 class="fw-bold mb-3">Download App</h6>
                <p class="text-muted small">Access EthioServe from your mobile device. Available on iOS and Android.</p>
                <div class="d-flex gap-2">
                    <a href="#" class="btn btn-dark btn-sm rounded-pill px-3 py-2"><i class="fab fa-apple me-2"></i> App
                        Store</a>
                    <a href="#" class="btn btn-dark btn-sm rounded-pill px-3 py-2"><i
                            class="fab fa-google-play me-2"></i> Play Store</a>
                </div>
            </div>
        </div>
    </div>

    <style>
        .footer-bottom-bar {
            background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
            padding: 14px 0;
        }

        .footer-bottom-bar p {
            margin: 0;
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.82rem;
        }
    </style>

    <div class="footer-bottom-bar">
        <div class="footer-bottom-bar">
            <div class="container">
                <div class="row align-items-center g-3">
                    <div class="col-md-6 text-center text-md-start">
                        <p class="mb-0">&copy; 2026 EthioServe Platform. All rights reserved. Designed with ❤️ in
                            Ethiopia.
                        </p>
                    </div>
                    <div class="col-md-6 text-center text-md-end">
                        <div class="developer-badge d-inline-flex flex-column align-items-end">
                            <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank"
                                class="d-flex align-items-center bg-white bg-opacity-10 rounded-pill px-3 py-1 border border-white border-opacity-10 shadow-sm text-decoration-none transition-all hover-scale"
                                title="View LinkedIn Profile">
                                <span class="text-white-50 small me-2">Developed by</span>
                                <div class="d-flex align-items-center">
                                    <img src="<?php echo $base_url; ?>/assets/img/AA.jpg"
                                        alt="Mequannent Gashaw Asinake"
                                        class="rounded-circle border border-2 border-white shadow-sm me-2"
                                        style="width: 32px; height: 32px; object-fit: cover;"
                                        onerror="this.src='https://ui-avatars.com/api/?name=Mequannent+Gashaw+Asinake&background=FFD600&color=1B5E20&bold=true'">
                                    <span class="text-white fw-bold small">Mequannent Gashaw Asinake</span>
                                    <i class="fab fa-linkedin ms-2 text-info"></i>
                                </div>
                            </a>
                            <div class="mt-1 d-flex gap-2">
                                <a href="https://t.me/+251918592028" target="_blank"
                                    class="text-white-50 small text-decoration-none hover-white"><i
                                        class="fab fa-telegram me-1"></i>0918592028</a>
                                <a href="https://wa.me/251918592028" target="_blank"
                                    class="text-white-50 small text-decoration-none hover-white"><i
                                        class="fab fa-whatsapp me-1"></i>0918592028</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
</footer>

<style>
    /* Premium Mobile Bottom Navigation */
    .mobile-bottom-nav {
        display: none;
        position: fixed;
        bottom: 0px;
        left: 0;
        right: 0;
        background: rgba(255, 255, 255, 0.82);
        backdrop-filter: blur(18px);
        -webkit-backdrop-filter: blur(18px);
        border-top: 1px solid rgba(0, 0, 0, 0.05);
        padding: 10px 10px calc(10px + env(safe-area-inset-bottom));
        z-index: 10001;
        box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.1);
        border-radius: 24px 24px 0 0;
    }

    @media (max-width: 767.98px) {
        .mobile-bottom-nav {
            display: block;
        }

        body {
            padding-bottom: 85px !important;
        }
    }

    .nav-container-f {
        display: flex;
        justify-content: space-around;
        align-items: center;
        max-width: 100%;
        margin: 0 auto;
    }

    .nav-item-f {
        display: flex;
        flex-direction: column;
        align-items: center;
        text-decoration: none;
        color: #8e8e93;
        font-size: 0.7rem;
        font-weight: 600;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        flex: 1;
        gap: 4px;
    }

    .nav-item-f i {
        font-size: 1.4rem;
        transition: transform 0.3s ease;
    }

    .nav-item-f.active {
        color: #1B5E20;
    }

    .nav-item-f.active i {
        transform: scale(1.15) translateY(-2px);
    }

    .nav-center-f {
        position: relative;
        top: -18px;
    }

    .nav-center-f .icon-box {
        width: 58px;
        height: 52px;
        background: linear-gradient(135deg, #FFB300, #FFD600);
        border-radius: 18px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 10px 25px rgba(255, 179, 0, 0.35);
        color: #1B5E20;
        transition: all 0.3s ease;
        border: 4px solid #fff;
    }

    .nav-center-f i {
        font-size: 1.6rem !important;
        color: #1B5E20;
    }

    .nav-center-f:active .icon-box {
        transform: scale(0.9) rotate(5deg);
    }

    .nav-item-f:active:not(.nav-center-f) {
        transform: scale(0.85);
    }
</style>

<!-- Mobile Bottom Navigation -->
<nav class="mobile-bottom-nav">
    <div class="nav-container-f">
        <a href="<?php echo $base_url; ?>/index.php"
            class="nav-item-f <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>Home</span>
        </a>
        <a href="<?php echo $base_url; ?>/customer/index.php#services" class="nav-item-f">
            <i class="fas fa-th-large"></i>
            <span>Services</span>
        </a>
        <a href="<?php echo $base_url; ?>/customer/index.php" class="nav-item-f nav-center-f">
            <div class="icon-box">
                <i class="fas fa-plus"></i>
            </div>
            <span style="font-size: 0.65rem; color: #1B5E20; font-weight: 800; margin-top: 5px;">APPS</span>
        </a>
        <a href="<?php echo $base_url; ?>/customer/orders.php"
            class="nav-item-f <?php echo basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : ''; ?>">
            <i class="fas fa-heart"></i>
            <span>Saved</span>
        </a>
        <a href="<?php echo $is_logged_in ? ($user_role == 'doctor' ? $base_url . '/doctor/dashboard.php' : $base_url . '/customer/profile.php') : $base_url . '/login.php'; ?>"
            class="nav-item-f <?php echo (basename($_SERVER['PHP_SELF']) == 'profile.php' || basename($_SERVER['PHP_SELF']) == 'dashboard.php') ? 'active' : ''; ?>">
            <i class="fas fa-user"></i>
            <span>Profile</span>
        </a>
    </div>
</nav>

<!-- Chatbot Widget -->
<?php include __DIR__ . '/chatbot.php'; ?>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- Custom JS -->
<script src="<?php echo $base_url; ?>/assets/js/main.js"></script>

<!-- Mobile Install Guide Banner -->
<style>
    #mobile-install-banner {
        display: none;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        animation: slideUp 0.5s ease-out;
    }

    @keyframes slideUp {
        from {
            transform: translateY(100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .install-banner-inner {
        background: linear-gradient(135deg, #1B5E20, #2E7D32);
        color: white;
        padding: 16px 20px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.2);
        border-radius: 20px 20px 0 0;
    }

    .install-icon-box {
        width: 50px;
        height: 50px;
        background: rgba(255, 255, 255, 0.15);
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 24px;
    }

    .install-btn {
        background: #FFD600;
        color: #1B5E20;
        border: none;
        border-radius: 50px;
        padding: 10px 22px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        white-space: nowrap;
        transition: transform 0.2s;
    }

    .install-btn:active {
        transform: scale(0.95);
    }

    .install-close {
        background: none;
        border: none;
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.4rem;
        cursor: pointer;
        padding: 0 4px;
    }

    /* Install Modal */
    .install-modal-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 10000;
        background: rgba(0, 0, 0, 0.6);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .install-modal-overlay.active {
        display: flex;
    }

    .install-modal {
        background: white;
        border-radius: 24px;
        padding: 35px 25px;
        max-width: 360px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.3);
        animation: modalPop 0.3s ease-out;
    }

    @keyframes modalPop {
        from {
            transform: scale(0.85);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .install-step {
        display: flex;
        align-items: flex-start;
        gap: 14px;
        text-align: left;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .install-step:last-of-type {
        border-bottom: none;
    }

    .step-num {
        width: 32px;
        height: 32px;
        background: linear-gradient(135deg, #1B5E20, #43A047);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .step-text {
        font-size: 0.9rem;
        color: #444;
        line-height: 1.5;
    }

    .step-text strong {
        color: #1B5E20;
    }
</style>

<!-- Bottom Install Banner (shows on mobile) -->
<div id="mobile-install-banner">
    <div class="install-banner-inner">
        <div class="install-icon-box">📱</div>
        <div style="flex:1;">
            <div style="font-weight:700; font-size:1rem; margin-bottom:2px;">Install EthioServe</div>
            <div style="font-size:0.8rem; opacity:0.85;">Add to home screen for app experience!</div>
        </div>
        <button class="install-btn" onclick="showInstallGuide()">Install</button>
        <button class="install-close" onclick="dismissInstall()">×</button>
    </div>
</div>

<!-- Install Instructions Modal -->
<div class="install-modal-overlay" id="install-modal">
    <div class="install-modal">
        <div style="font-size:56px; margin-bottom:10px;">📲</div>
        <h3 style="font-weight:700; color:#1B5E20; margin-bottom:5px; font-size:1.4rem;">Install EthioServe</h3>
        <p style="color:#888; font-size:0.85rem; margin-bottom:20px;">Follow these steps to add the app to your home
            screen</p>

        <!-- Android Steps -->
        <div id="android-steps">
            <div class="install-step">
                <div class="step-num">1</div>
                <div class="step-text">Tap the <strong>three dots ⋮</strong> menu at the top-right corner of Chrome
                </div>
            </div>
            <div class="install-step">
                <div class="step-num">2</div>
                <div class="step-text">Tap <strong>"Add to Home Screen"</strong> or <strong>"Install App"</strong></div>
            </div>
            <div class="install-step">
                <div class="step-num">3</div>
                <div class="step-text">Tap <strong>"Add"</strong> to confirm. EthioServe will appear on your home
                    screen! 🎉</div>
            </div>
        </div>

        <!-- iOS Steps -->
        <div id="ios-steps" style="display:none;">
            <div class="install-step">
                <div class="step-num">1</div>
                <div class="step-text">Tap the <strong>Share button ⬆️</strong> at the bottom center of Safari</div>
            </div>
            <div class="install-step">
                <div class="step-num">2</div>
                <div class="step-text">Scroll down and tap <strong>"Add to Home Screen"</strong></div>
            </div>
            <div class="install-step">
                <div class="step-num">3</div>
                <div class="step-text">Tap <strong>"Add"</strong> in the top right. EthioServe is now on your home
                    screen! 🎉</div>
            </div>
        </div>

        <div style="margin-top:20px; display:flex; gap:10px; justify-content:center;">
            <button onclick="closeInstallModal()"
                style="background:#f5f5f5; color:#666; border:none; border-radius:50px; padding:12px 28px; font-weight:600; cursor:pointer; font-size:0.9rem;">Later</button>
            <button onclick="closeInstallModal(); dismissInstall();"
                style="background:linear-gradient(135deg, #1B5E20, #2E7D32); color:white; border:none; border-radius:50px; padding:12px 28px; font-weight:600; cursor:pointer; font-size:0.9rem;">Got
                it!</button>
        </div>
    </div>
</div>

<!-- Service Worker Registration & Install Logic -->
<script>
    // Register Service Worker (works on HTTPS and localhost)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
            navigator.serviceWorker.register('<?php echo $base_url; ?>/service-worker.js')
                .then((reg) => console.log('✅ SW registered:', reg.scope))
                .catch((err) => console.log('ℹ️ SW not available (requires HTTPS):', err.message));
        });
    }

    // Detect mobile device
    function isMobile() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    function isIOS() {
        return /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
    }
    function isStandalone() {
        return window.matchMedia('(display-mode: standalone)').matches ||
            window.navigator.standalone === true;
    }

    // Show install banner on mobile (if not already installed and not dismissed)
    const banner = document.getElementById('mobile-install-banner');
    const modal = document.getElementById('install-modal');

    if (isMobile() && !isStandalone() && !localStorage.getItem('ethioserve-install-dismissed')) {
        setTimeout(() => {
            banner.style.display = 'block';
        }, 2000);
    }

    // Handle native PWA prompt if available (HTTPS)
    let deferredPrompt = null;
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
    });

    function showInstallGuide() {
        // If native prompt is available, use it directly
        if (deferredPrompt) {
            deferredPrompt.prompt();
            deferredPrompt.userChoice.then((result) => {
                if (result.outcome === 'accepted') {
                    console.log('🎉 App installed!');
                    banner.style.display = 'none';
                }
                deferredPrompt = null;
            });
            return;
        }

        // Otherwise show manual instructions
        if (isIOS()) {
            document.getElementById('android-steps').style.display = 'none';
            document.getElementById('ios-steps').style.display = 'block';
        } else {
            document.getElementById('android-steps').style.display = 'block';
            document.getElementById('ios-steps').style.display = 'none';
        }
        modal.classList.add('active');
        banner.style.display = 'none';
    }

    function closeInstallModal() {
        modal.classList.remove('active');
    }

    function dismissInstall() {
        banner.style.display = 'none';
        localStorage.setItem('ethioserve-install-dismissed', Date.now());
    }

    // Auto-hide banner once app is installed
    window.addEventListener('appinstalled', () => {
        banner.style.display = 'none';
        console.log('🎉 EthioServe installed!');
    });
</script>

</body>

</html>