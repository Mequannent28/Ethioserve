<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

if (!isset($_SESSION['id'])) {
    header("Location: ../login.php");
    exit();
}

$user_id = $_SESSION['id'];

// Handle Cancellation
if (isset($_GET['cancel'])) {
    $booking_id = intval($_GET['cancel']);
    $stmt = $pdo->prepare("UPDATE home_service_bookings SET status = 'cancelled' WHERE id = ? AND customer_id = ? AND status = 'pending'");
    $stmt->execute([$booking_id, $user_id]);
    redirectWithMessage('my_home_bookings.php', 'success', 'Booking cancelled successfully.');
}

// Handle Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_review'])) {
    $booking_id = intval($_POST['booking_id']);
    $rating = intval($_POST['rating']);
    $comment = sanitize($_POST['comment']);
    $provider_id = intval($_POST['provider_id']);

    try {
        $stmt = $pdo->prepare("INSERT INTO home_service_reviews (booking_id, customer_id, provider_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$booking_id, $user_id, $provider_id, $rating, $comment]);
        redirectWithMessage('my_home_bookings.php', 'success', 'Thank you for your review!');
    } catch (Exception $e) {
        $error = "Review failed: " . $e->getMessage();
    }
}

// Fetch Bookings
$stmt = $pdo->prepare("SELECT b.*, c.name as category_name, o.name as service_name, p_u.full_name as provider_name, r.id as review_id 
                       FROM home_service_bookings b
                       JOIN home_service_categories c ON b.category_id = c.id
                       JOIN home_service_options o ON b.option_id = o.id
                       LEFT JOIN home_service_providers p ON b.provider_id = p.id
                       LEFT JOIN users p_u ON p.user_id = p_u.id
                       LEFT JOIN home_service_reviews r ON b.id = r.booking_id
                       WHERE b.customer_id = ?
                       ORDER BY b.created_at DESC");
$stmt->execute([$user_id]);
$bookings = $stmt->fetchAll();

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h2 class="fw-bold mb-1">My Home Service Bookings</h2>
            <p class="text-muted">Track your service requests and appointments</p>
        </div>
        <a href="home_services.php" class="btn btn-primary-green rounded-pill px-4">
            <i class="fas fa-plus me-2"></i>Book New Service
        </a>
    </div>

    <?php if (empty($bookings)): ?>
        <div class="text-center py-5 card border-0 shadow-sm rounded-4">
            <div class="py-5">
                <div class="mb-4"><i class="fas fa-calendar-times text-muted" style="font-size: 5rem;"></i></div>
                <h4 class="text-muted">No bookings found</h4>
                <p class="text-muted mb-4">You haven't requested any home services yet.</p>
                <a href="home_services.php" class="btn btn-warning rounded-pill px-5 fw-bold">Browse Services</a>
            </div>
        </div>
    <?php else: ?>
        <div class="row g-4">
            <?php foreach ($bookings as $b): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4 overflow-hidden">
                        <div class="row align-items-center g-4">
                            <div class="col-md-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-light p-3 rounded-4 me-3">
                                        <i class="fas fa-tools text-warning fs-3"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">
                                            <?php echo htmlspecialchars($b['service_name']); ?>
                                        </h6>
                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill small">
                                            <?php echo htmlspecialchars($b['category_name']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="d-flex align-items-center text-muted small mb-1">
                                    <i class="far fa-calendar-alt me-2"></i>
                                    <span>Scheduled for:</span>
                                </div>
                                <div class="fw-bold">
                                    <?php echo date('M d, Y - h:i A', strtotime($b['scheduled_at'])); ?>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="text-muted small mb-1">Status:</div>
                                <?php
                                $status_class = [
                                    'pending' => 'bg-warning text-dark',
                                    'confirmed' => 'bg-info text-white',
                                    'in_progress' => 'bg-primary text-white',
                                    'completed' => 'bg-success text-white',
                                    'cancelled' => 'bg-danger text-white'
                                ][$b['status']];
                                ?>
                                <span class="badge <?php echo $status_class; ?> rounded-pill px-3 py-2">
                                    <?php echo ucfirst($b['status']); ?>
                                </span>
                            </div>
                            <div class="col-md-2">
                                <div class="text-muted small mb-1">Est. Price:</div>
                                <div class="fw-extrabold text-primary-green h5 mb-0">
                                    <?php echo number_format($b['total_price']); ?> ETB
                                </div>
                            </div>
                            <div class="col-md-2 text-md-end">
                                <button class="btn btn-light rounded-pill btn-sm mb-2 w-100" data-bs-toggle="modal"
                                    data-bs-target="#modal<?php echo $b['id']; ?>">Details</button>
                                <?php if ($b['status'] === 'pending'): ?>
                                    <a href="?cancel=<?php echo $b['id']; ?>"
                                        class="btn btn-outline-danger rounded-pill btn-sm w-100"
                                        onclick="return confirm('Are you sure you want to cancel this booking?')">Cancel</a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Details Modal -->
                <div class="modal fade" id="modal<?php echo $b['id']; ?>" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content border-0 rounded-4">
                            <div class="modal-header border-0 p-4">
                                <h5 class="modal-title fw-bold text-primary">Booking Details #
                                    <?php echo $b['id']; ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body p-4 pt-0">
                                <div class="p-3 bg-light rounded-4 mb-4">
                                    <h6 class="fw-bold small text-uppercase text-muted mb-3">Service Info</h6>
                                    <h5 class="fw-bold">
                                        <?php echo htmlspecialchars($b['service_name']); ?>
                                    </h5>
                                    <p class="mb-0 text-muted">
                                        <?php echo htmlspecialchars($b['category_name']); ?>
                                    </p>
                                </div>

                                <div class="row g-4">
                                    <div class="col-6">
                                        <label class="small text-muted d-block">Schedule</label>
                                        <strong>
                                            <?php echo date('M d, Y, h:i A', strtotime($b['scheduled_at'])); ?>
                                        </strong>
                                    </div>
                                    <div class="col-6">
                                        <label class="small text-muted d-block">Status</label>
                                        <strong class="text-primary">
                                            <?php echo ucfirst($b['status']); ?>
                                        </strong>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-muted d-block">Address</label>
                                        <strong>
                                            <?php echo htmlspecialchars($b['service_address']); ?>
                                        </strong>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-muted d-block">Provider Assigned</label>
                                        <strong>
                                            <?php echo $b['provider_name'] ? htmlspecialchars($b['provider_name']) : 'Assigning soon...'; ?>
                                        </strong>
                                    </div>
                                    <div class="col-12">
                                        <label class="small text-muted d-block">Notes</label>
                                        <div class="p-2 border rounded-3 bg-white mt-1 small">
                                            <?php echo $b['notes'] ? htmlspecialchars($b['notes']) : 'No special instructions provided.'; ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 text-center">
                                    <hr>
                                    <div class="h4 fw-bold text-primary-green">Total:
                                        <?php echo number_format($b['total_price']); ?> ETB
                                    </div>
                                    <p class="small text-muted mb-0">Pay via cash or transfer after service</p>
                                </div>

                                <?php if ($b['status'] === 'completed' && !$b['review_id'] && $b['provider_id']): ?>
                                <div class="mt-4 p-4 bg-light rounded-4">
                                    <h6 class="fw-bold mb-3"><i class="fas fa-star text-warning me-2"></i>Rate your experience</h6>
                                    <form method="POST">
                                        <?php echo csrfField(); ?>
                                        <input type="hidden" name="booking_id" value="<?php echo $b['id']; ?>">
                                        <input type="hidden" name="provider_id" value="<?php echo $b['provider_id']; ?>">
                                        <div class="mb-3">
                                            <select name="rating" class="form-select border-0 shadow-sm rounded-pill px-3" required>
                                                <option value="5">⭐⭐⭐⭐⭐ Excellent</option>
                                                <option value="4">⭐⭐⭐⭐ Great</option>
                                                <option value="3">⭐⭐⭐ Good</option>
                                                <option value="2">⭐⭐ Fair</option>
                                                <option value="1">⭐ Poor</option>
                                            </select>
                                        </div>
                                        <div class="mb-3">
                                            <textarea name="comment" class="form-control border-0 shadow-sm rounded-4" rows="2" placeholder="Tell us more about the service..."></textarea>
                                        </div>
                                        <button type="submit" name="submit_review" class="btn btn-warning w-100 rounded-pill fw-bold">Submit Review</button>
                                    </form>
                                </div>
                                <?php elseif ($b['review_id']): ?>
                                <div class="mt-4 p-3 bg-success bg-opacity-10 text-success rounded-4 text-center">
                                    <i class="fas fa-check-circle me-2"></i>Review Submitted
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>