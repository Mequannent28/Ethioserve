<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];
$user_role = $_SESSION['role'] ?? 'customer';

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-900 mb-2">Message Center</h2>
        <p class="text-muted">Stay connected with everyone across the platform</p>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Dating Messages Card -->
        <div class="col-md-4">
            <a href="dating_matches.php" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center transition-all message-cat-card">
                    <div class="rounded-circle bg-danger bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width:70px;height:70px;">
                        <i class="fas fa-heart text-danger fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Dating & Matches</h5>
                    <p class="text-muted small">Chat with your matches and see who liked your profile.</p>
                    <?php
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM dating_messages WHERE receiver_id = ? AND is_read = 0");
                    $stmt->execute([$user_id]);
                    $unread = $stmt->fetchColumn();
                    if ($unread > 0):
                        ?>
                        <span class="badge bg-danger rounded-pill">
                            <?php echo $unread; ?> New Messages
                        </span>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <!-- Doctor Chats Card -->
        <div class="col-md-4">
            <?php
            $chat_url = ($user_role === 'doctor') ? '../doctor/dashboard.php#chats' : 'telemedicine.php';
            ?>
            <a href="<?php echo $chat_url; ?>" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center transition-all message-cat-card">
                    <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width:70px;height:70px;">
                        <i class="fas fa-stethoscope text-primary fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Medical Consultations</h5>
                    <p class="text-muted small">Talk to your doctors or manage patient inquiries.</p>
                </div>
            </a>
        </div>

        <!-- Job/Freelance Card -->
        <div class="col-md-4">
            <a href="jobs.php" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center transition-all message-cat-card">
                    <div class="rounded-circle bg-warning bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width:70px;height:70px;">
                        <i class="fas fa-briefcase text-warning fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Job & Freelance</h5>
                    <p class="text-muted small">Communicate with employers or freelance service providers.</p>
                    <?php
                    $job_unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM job_messages WHERE receiver_id = ? AND is_read = 0");
                    $job_unread_stmt->execute([$user_id]);
                    $job_unread = $job_unread_stmt->fetchColumn();
                    if ($job_unread > 0):
                        ?>
                        <span class="badge bg-warning text-dark rounded-pill">
                            <?php echo $job_unread; ?> New Replies
                        </span>
                    <?php endif; ?>
                </div>
            </a>
        </div>

        <!-- Community Card -->
        <div class="col-md-4 mt-4">
            <a href="community.php" class="text-decoration-none">
                <div class="card border-0 shadow-sm rounded-4 h-100 p-4 text-center transition-all message-cat-card">
                    <div class="rounded-circle bg-success bg-opacity-10 d-flex align-items-center justify-content-center mx-auto mb-3"
                        style="width:70px;height:70px;">
                        <i class="fas fa-users text-success fs-2"></i>
                    </div>
                    <h5 class="fw-bold text-dark">Community News</h5>
                    <p class="text-muted small">Official platform announcements and community discussions.</p>
                </div>
            </a>
        </div>
    </div>
</div>

<style>
    .fw-900 {
        font-weight: 900;
    }

    .message-cat-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1) !important;
        background: #f8f9fa;
    }

    .transition-all {
        transition: all 0.3s ease;
    }
</style>

<?php include '../includes/footer.php'; ?>