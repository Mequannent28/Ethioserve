<?php
// sidebar_broker.php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<style>
    .sidebar-broker {
        width: 260px;
        min-height: 100vh;
        position: fixed;
        left: 0;
        top: 0;
        background: linear-gradient(180deg, #1B5E20 0%, #2E7D32 60%, #1a4d1c 100%);
        z-index: 100;
        display: flex;
        flex-direction: column;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.15);
        overflow-y: auto;
        overflow-x: hidden;
    }

    .sidebar-broker .sidebar-brand {
        padding: 28px 24px 20px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        text-decoration: none;
    }

    .sidebar-broker .sidebar-brand h4 {
        font-size: 1.5rem;
        font-weight: 800;
        margin: 0;
        color: #fff;
        letter-spacing: -0.5px;
    }

    .sidebar-broker .user-profile {
        padding: 20px 24px;
        display: flex;
        align-items: center;
        gap: 12px;
        background: rgba(255, 255, 255, 0.07);
        margin: 12px 12px 8px;
        border-radius: 14px;
    }

    .sidebar-broker .user-avatar {
        width: 44px;
        height: 44px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.2);
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        color: #fff;
        flex-shrink: 0;
        border: 2px solid rgba(255, 255, 255, 0.3);
    }

    .sidebar-broker .user-info .user-name {
        font-size: 0.85rem;
        font-weight: 700;
        color: #fff;
        margin: 0;
        line-height: 1.2;
        max-width: 160px;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
    }

    .sidebar-broker .user-info .user-role {
        font-size: 0.68rem;
        background: rgba(255, 193, 7, 0.25);
        color: #FFD54F;
        border: 1px solid rgba(255, 193, 7, 0.3);
        border-radius: 20px;
        padding: 2px 10px;
        display: inline-block;
        font-weight: 600;
        margin-top: 3px;
    }

    .sidebar-broker .nav-section-label {
        font-size: 0.63rem;
        font-weight: 700;
        letter-spacing: 1.8px;
        text-transform: uppercase;
        color: rgba(255, 255, 255, 0.35);
        padding: 14px 24px 6px;
    }

    .sidebar-broker .nav-item-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 11px 20px;
        margin: 2px 12px;
        border-radius: 12px;
        color: rgba(255, 255, 255, 0.75);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 500;
        transition: all 0.25s ease;
        position: relative;
    }

    .sidebar-broker .nav-item-link i {
        width: 22px;
        text-align: center;
        font-size: 0.95rem;
        flex-shrink: 0;
    }

    .sidebar-broker .nav-item-link:hover {
        background: rgba(255, 255, 255, 0.12);
        color: #fff;
        transform: translateX(3px);
    }

    .sidebar-broker .nav-item-link.active {
        background: rgba(255, 255, 255, 0.95);
        color: #1B5E20;
        font-weight: 700;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.15);
    }

    .sidebar-broker .nav-item-link .badge-count {
        margin-left: auto;
        background: #EF5350;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
        min-width: 20px;
        text-align: center;
    }

    .sidebar-broker .sidebar-divider {
        border: none;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin: 10px 20px;
    }

    .sidebar-broker .sidebar-footer {
        margin-top: auto;
        padding: 16px 12px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
    }

    .sidebar-broker .logout-link {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 10px 20px;
        border-radius: 12px;
        color: rgba(255, 120, 100, 0.9);
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        transition: all 0.25s;
    }

    .sidebar-broker .logout-link:hover {
        background: rgba(239, 83, 80, 0.15);
        color: #ff8a80;
    }

    .sidebar-broker .dev-credit {
        margin: 8px 12px 0;
        background: rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 10px 14px;
        text-decoration: none;
        border: 1px solid rgba(255, 255, 255, 0.1);
        display: flex;
        align-items: center;
        gap: 10px;
        transition: background 0.2s;
    }

    .sidebar-broker .dev-credit:hover {
        background: rgba(255, 255, 255, 0.13);
    }

    /* Main content offset */
    body.broker-page .main-content {
        margin-left: 260px;
    }

    @media (max-width: 991px) {
        .sidebar-broker {
            transform: translateX(-100%);
            transition: transform 0.3s;
        }

        .sidebar-broker.show {
            transform: translateX(0);
        }

        body.broker-page .main-content {
            margin-left: 0;
        }
    }
</style>

<?php
// Get pending requests count for badge
try {
    $pending_req_count = 0;
    if (isset($pdo)) {
        $uid = getCurrentUserId();
        $stmt_badge = $pdo->prepare("SELECT COUNT(*) FROM rental_requests rr JOIN listings l ON rr.listing_id = l.id WHERE l.user_id = ? AND rr.status = 'pending'");
        $stmt_badge->execute([$uid]);
        $pending_req_count = $stmt_badge->fetchColumn();
    }
} catch (Exception $e) {
    $pending_req_count = 0;
}

$user_name_sidebar = htmlspecialchars($_SESSION['full_name'] ?? 'Owner');
$user_initials = strtoupper(substr($user_name_sidebar, 0, 1));
$profile_photo = $_SESSION['profile_photo'] ?? null;
?>

<nav class="sidebar-broker" id="brokerSidebar">
    <!-- Brand -->
    <a href="../customer/index.php" class="sidebar-brand d-flex align-items-center gap-2">
        <div style="width:38px;height:38px;background:rgba(255,255,255,0.15);border-radius:10px;display:flex;align-items:center;justify-content:center;">
            <i class="fas fa-building text-warning"></i>
        </div>
        <h4 class="mb-0">Ethio<span style="color:#FFD54F;">Serve</span></h4>
    </a>

    <!-- User Profile -->
    <div class="user-profile">
        <div class="user-avatar">
            <?php if ($profile_photo && file_exists('../' . $profile_photo)): ?>
                <img src="<?php echo BASE_URL . '/' . $profile_photo; ?>" width="44" height="44"
                     style="width:44px;height:44px;border-radius:50%;object-fit:cover;" alt="">
            <?php else: ?>
                <?php echo $user_initials; ?>
            <?php endif; ?>
        </div>
        <div class="user-info">
            <p class="user-name"><?php echo $user_name_sidebar; ?></p>
            <span class="user-role">Property Owner</span>
        </div>
    </div>

    <!-- Nav Items -->
    <div style="flex:1;">
        <p class="nav-section-label">Main Menu</p>

        <a href="dashboard.php" class="nav-item-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-th-large"></i>
            <span>Dashboard</span>
        </a>

        <a href="post_listing.php" class="nav-item-link <?php echo $current_page === 'post_listing.php' ? 'active' : ''; ?>">
            <i class="fas fa-plus-circle"></i>
            <span>Add New Listing</span>
        </a>

        <a href="my_listings.php" class="nav-item-link <?php echo $current_page === 'my_listings.php' ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>
            <span>My Listings</span>
        </a>

        <a href="recycle_bin.php" class="nav-item-link <?php echo $current_page === 'recycle_bin.php' ? 'active' : ''; ?>">
            <i class="fas fa-trash-alt"></i>
            <span>Recycle Bin</span>
        </a>

        <a href="requests.php" class="nav-item-link <?php echo $current_page === 'requests.php' || $current_page === 'chat.php' ? 'active' : ''; ?>">
            <i class="fas fa-envelope-open-text"></i>
            <span>Rental Requests</span>
            <?php if ($pending_req_count > 0): ?>
                <span class="badge-count"><?php echo $pending_req_count; ?></span>
            <?php endif; ?>
        </a>

        <a href="messages.php" class="nav-item-link <?php echo $current_page === 'messages.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments"></i>
            <span>Messages</span>
        </a>

        <hr class="sidebar-divider">

        <p class="nav-section-label">Settings & Payments</p>

        <a href="payment_settings.php" class="nav-item-link <?php echo $current_page === 'payment_settings.php' ? 'active' : ''; ?>">
            <i class="fas fa-qrcode"></i>
            <span>Payment Settings</span>
        </a>

        <a href="profile.php" class="nav-item-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
            <i class="fas fa-user-cog"></i>
            <span>My Profile</span>
        </a>

        <p class="nav-section-label">Earnings</p>

        <a href="referrals.php" class="nav-item-link <?php echo $current_page === 'referrals.php' ? 'active' : ''; ?>">
            <i class="fas fa-comments-dollar"></i>
            <span>Referrals & Commissions</span>
        </a>

        <hr class="sidebar-divider">

        <a href="../customer/index.php" class="nav-item-link">
            <i class="fas fa-globe"></i>
            <span>View Platform</span>
        </a>
    </div>

    <!-- Footer -->
    <div class="sidebar-footer">
        <a href="../logout.php" class="logout-link">
            <i class="fas fa-sign-out-alt"></i>
            <span>Logout</span>
        </a>

        <a href="https://et.linkedin.com/in/mequannent-gashaw-asinake-48056b247" target="_blank" class="dev-credit">
            <img src="https://ui-avatars.com/api/?name=Mequannent+Gashaw&background=FFB300&color=000&bold=true"
                 class="rounded-circle" width="28" height="28">
            <div style="line-height:1.2;">
                <div style="font-size:0.6rem;color:rgba(255,255,255,0.45);">Developed by</div>
                <div style="font-size:0.72rem;font-weight:700;color:rgba(255,255,255,0.85);">Mequannent Gashaw</div>
            </div>
            <i class="fab fa-linkedin ms-auto" style="color:#FFD54F;font-size:0.8rem;"></i>
        </a>
    </div>
</nav>

<script>
    document.body.classList.add('broker-page');
</script>

<!-- Broker Notifications -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const brokerApiUrl = '<?php echo BASE_URL; ?>/api.php';
    let lastSeenRefId = 0;
    let lastSeenReqId = 0;

    document.addEventListener('DOMContentLoaded', () => {
        setInterval(checkBrokerNotifications, 15000);
    });

    async function checkBrokerNotifications() {
        try {
            const response = await fetch(`${brokerApiUrl}?action=get_broker_notifications&last_ref_id=${lastSeenRefId}&last_request_id=${lastSeenReqId}`);
            const data = await response.json();

            if (data.success) {
                if (data.new_referrals && data.new_referrals.length > 0) {
                    data.new_referrals.forEach(ref => {
                        showBrokerToast('💰 New Referral!', `Commission earned from ${ref.customer_name}'s order!`, 'success', `<?php echo BASE_URL; ?>/broker/referrals.php`);
                    });
                    const maxRefId = Math.max(...data.new_referrals.map(r => parseInt(r.id)));
                    if (maxRefId > lastSeenRefId) lastSeenRefId = maxRefId;
                }

                if (data.new_requests && data.new_requests.length > 0) {
                    data.new_requests.forEach(req => {
                        showBrokerToast('🏠 New Request!', `Someone is interested in "${req.listing_title}"`, 'info', `<?php echo BASE_URL; ?>/broker/requests.php`);
                    });
                    const maxReqId = Math.max(...data.new_requests.map(r => parseInt(r.id)));
                    if (maxReqId > lastSeenReqId) lastSeenReqId = maxReqId;
                }
            }
        } catch (error) {
            console.error('Broker Notification Error:', error);
        }
    }

    function showBrokerToast(title, message, icon, url) {
        new Audio('https://assets.mixkit.co/active_storage/sfx/2358/2358-preview.mp3').play().catch(() => {});
        Swal.fire({
            title, text: message, icon, toast: true, position: 'top-end',
            showConfirmButton: true, confirmButtonText: 'View',
            showCancelButton: true, timer: 15000, timerProgressBar: true
        }).then(result => { if (result.isConfirmed) window.location.href = url; });
    }
</script>