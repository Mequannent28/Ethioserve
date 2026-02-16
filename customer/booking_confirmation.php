<?php
require_once '../includes/functions.php';
requireLogin();
require_once '../includes/db.php';

$booking_id = (int) ($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    redirectWithMessage('buses.php', 'error', 'Invalid booking ID');
}

// Fetch booking details
$stmt = $pdo->prepare("
    SELECT bb.*, 
           s.departure_time, s.arrival_time, s.price,
           r.origin, r.destination, r.distance_km, r.estimated_hours,
           b.bus_number, bt.name as bus_type, b.total_seats, b.amenities,
           tc.company_name, tc.phone as company_phone, tc.email as company_email,
           u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_types bt ON b.bus_type_id = bt.id
    JOIN transport_companies tc ON b.company_id = tc.id
    JOIN users u ON bb.customer_id = u.id
    WHERE bb.id = ? AND bb.customer_id = ?
");
$stmt->execute([$booking_id, getCurrentUserId()]);
$booking = $stmt->fetch();

if (!$booking) {
    redirectWithMessage('buses.php', 'error', 'Booking not found');
}

// Parse passenger details
$passenger_names = explode('|', $booking['passenger_names']);
$passenger_phones = explode('|', $booking['passenger_phones']);

$pageTitle = 'Booking Confirmation';
include('../includes/header.php');
?>

<style>
    .confirmation-hero {
        background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
        color: white;
        padding: 60px 0 40px;
        position: relative;
        overflow: hidden;
    }

    .confirmation-hero::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="rgba(255,255,255,0.1)" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,122.7C672,117,768,139,864,138.7C960,139,1056,117,1152,106.7C1248,96,1344,96,1392,96L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>') bottom;
        background-size: cover;
        opacity: 0.3;
    }

    .success-icon {
        width: 100px;
        height: 100px;
        background: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 20px;
        animation: scaleIn 0.5s ease-out;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    }

    .success-icon i {
        font-size: 50px;
        color: #4CAF50;
    }

    @keyframes scaleIn {
        from {
            transform: scale(0);
        }

        to {
            transform: scale(1);
        }
    }

    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(30px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .confirmation-card {
        animation: fadeInUp 0.6s ease-out;
        animation-delay: 0.2s;
        animation-fill-mode: both;
    }

    .status-badge {
        display: inline-block;
        padding: 10px 25px;
        border-radius: 25px;
        font-weight: bold;
        font-size: 14px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .status-pending {
        background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%);
        color: white;
    }

    .status-confirmed {
        background: linear-gradient(135deg, #4CAF50 0%, #388E3C 100%);
        color: white;
    }

    .info-section {
        background: white;
        border-radius: 15px;
        padding: 25px;
        margin-bottom: 20px;
        box-shadow: 0 2px 15px rgba(0, 0, 0, 0.08);
        transition: transform 0.3s, box-shadow 0.3s;
    }

    .info-section:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .info-section h5 {
        color: #1B5E20;
        font-weight: bold;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #1B5E20;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        flex: 0 0 180px;
        font-weight: 600;
        color: #666;
    }

    .info-value {
        flex: 1;
        color: #333;
    }

    .action-buttons .btn {
        padding: 15px 40px;
        font-size: 16px;
        font-weight: bold;
        border-radius: 50px;
        transition: all 0.3s;
    }

    .action-buttons .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .timeline {
        position: relative;
        padding: 20px 0;
    }

    .timeline-item {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }

    .timeline-icon {
        flex: 0 0 60px;
        height: 60px;
        background: #1B5E20;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 24px;
    }

    .timeline-content {
        flex: 1;
        padding-top: 5px;
    }

    .qr-code-container {
        text-align: center;
        padding: 20px;
        background: #f9f9f9;
        border-radius: 10px;
        margin: 20px 0;
    }

    .pulse {
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% {
            box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
        }

        70% {
            box-shadow: 0 0 0 15px rgba(76, 175, 80, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(76, 175, 80, 0);
        }
    }
</style>

<!-- Success Hero Section -->
<div class="confirmation-hero">
    <div class="container text-center position-relative">
        <!-- Big Amazing Bus SVG -->
        <div class="mb-4">
            <svg width="200" height="200" viewBox="0 0 200 200" xmlns="http://www.w3.org/2000/svg"
                style="filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));">
                <defs>
                    <linearGradient id="heroGradient" x1="0%" y1="0%" x2="0%" y2="100%">
                        <stop offset="0%" style="stop-color:#FFD700;stop-opacity:1" />
                        <stop offset="100%" style="stop-color:#FFA500;stop-opacity:1" />
                    </linearGradient>
                    <linearGradient id="heroGlass" x1="0%" y1="0%" x2="100%" y2="0%">
                        <stop offset="0%" style="stop-color:#87CEEB;stop-opacity:0.9" />
                        <stop offset="100%" style="stop-color:#4682B4;stop-opacity:0.9" />
                    </linearGradient>
                </defs>

                <!-- Bus Body -->
                <rect x="30" y="60" width="140" height="80" rx="15" fill="url(#heroGradient)" />

                <!-- Windows Row 1 -->
                <rect x="40" y="70" width="22" height="18" rx="3" fill="url(#heroGlass)" />
                <rect x="68" y="70" width="22" height="18" rx="3" fill="url(#heroGlass)" />
                <rect x="96" y="70" width="22" height="18" rx="3" fill="url(#heroGlass)" />
                <rect x="124" y="70" width="22" height="18" rx="3" fill="url(#heroGlass)" />
                <rect x="152" y="70" width="13" height="18" rx="3" fill="url(#heroGlass)" />

                <!-- Front Panel -->
                <rect x="155" y="95" width="15" height="35" rx="5" fill="#FFA500" />

                <!-- Headlights -->
                <circle cx="162" cy="105" r="4" fill="#FFFF00" opacity="0.9">
                    <animate attributeName="opacity" values="0.5;1;0.5" dur="2s" repeatCount="indefinite" />
                </circle>
                <circle cx="162" cy="125" r="4" fill="#FFFF00" opacity="0.9">
                    <animate attributeName="opacity" values="1;0.5;1" dur="2s" repeatCount="indefinite" />
                </circle>

                <!-- Door -->
                <rect x="40" y="100" width="18" height="35" rx="3" fill="rgba(255,255,255,0.8)" />
                <line x1="49" y1="105" x2="49" y2="130" stroke="#FFA500" stroke-width="1" />

                <!-- Wheels -->
                <circle cx="60" cy="148" r="14" fill="#333" />
                <circle cx="60" cy="148" r="8" fill="#666" />
                <circle cx="60" cy="148" r="4" fill="#999" />

                <circle cx="150" cy="148" r="14" fill="#333" />
                <circle cx="150" cy="148" r="8" fill="#666" />
                <circle cx="150" cy="148" r="4" fill="#999" />

                <!-- Wheel Animation -->
                <animateTransform attributeName="transform" attributeType="XML" type="translate" values="0,0; -200,0"
                    dur="5s" repeatCount="indefinite" />

                <!-- Confetti Effect -->
                <circle cx="100" cy="40" r="3" fill="#FF1493">
                    <animate attributeName="cy" values="40;180" dur="3s" repeatCount="indefinite" />
                    <animate attributeName="opacity" values="1;0" dur="3s" repeatCount="indefinite" />
                </circle>
                <circle cx="120" cy="30" r="4" fill="#00FF00">
                    <animate attributeName="cy" values="30;180" dur="2.5s" repeatCount="indefinite" />
                    <animate attributeName="opacity" values="1;0" dur="2.5s" repeatCount="indefinite" />
                </circle>
                <circle cx="80" cy="35" r="3" fill="#1E90FF">
                    <animate attributeName="cy" values="35;180" dur="2.8s" repeatCount="indefinite" />
                    <animate attributeName="opacity" values="1;0" dur="2.8s" repeatCount="indefinite" />
                </circle>
            </svg>
        </div>

        <div class="success-icon pulse">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1 class="fw-bold mb-3 animate__animated animate__fadeIn">🎉 Registration Successful!</h1>
        <p class="lead mb-4 animate__animated animate__fadeIn animate__delay-1s">
            Your bus ticket has been booked successfully
        </p>
        <h3 class="fw-bold mb-2">Booking Reference</h3>
        <h2 class="display-4 fw-bold" style="letter-spacing: 3px;">
            <?php echo htmlspecialchars($booking['booking_reference']); ?>
        </h2>
        <div class="mt-4">
            <span class="status-badge status-<?php echo $booking['status']; ?>">
                <?php echo $booking['status'] === 'confirmed' ? '✓ Confirmed' : '⏳ Pending Approval'; ?>
            </span>
        </div>
    </div>
</div>

<main class="container py-5">
    <!-- Alert Messages -->
    <?php if ($booking['status'] === 'pending'): ?>
        <div class="alert alert-info rounded-4 shadow-sm mb-4 animate__animated animate__fadeIn">
            <div class="d-flex align-items-center">
                <i class="fas fa-info-circle fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">⏳ Awaiting Seat Assignment</h5>
                    <p class="mb-0">Your booking has been received! The bus company will review and assign your seat
                        numbers shortly. You'll receive a notification once confirmed.</p>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="alert alert-success rounded-4 shadow-sm mb-4 animate__animated animate__fadeIn">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle fs-3 me-3"></i>
                <div>
                    <h5 class="alert-heading mb-1">✅ Booking Confirmed!</h5>
                    <p class="mb-0">Your seats have been assigned. See details below.</p>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Journey Timeline -->
            <div class="info-section confirmation-card">
                <h5><i class="fas fa-route"></i> Journey Timeline</h5>
                <div class="timeline">
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['origin']); ?></h6>
                            <p class="text-muted mb-0">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('h:i A', strtotime($booking['departure_time'])); ?> •
                                <?php echo date('M d, Y', strtotime($booking['travel_date'])); ?>
                            </p>
                            <p class="small text-muted mb-0"><i class="fas fa-map-pin me-1"></i> Pickup:
                                <?php echo htmlspecialchars($booking['pickup_point']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-bus"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['company_name']); ?></h6>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($booking['bus_number']); ?> •
                                <?php echo htmlspecialchars($booking['bus_type']); ?>
                            </p>
                            <p class="small text-muted mb-0"><i class="fas fa-road me-1"></i>
                                <?php echo $booking['distance_km']; ?> km •
                                ~<?php echo $booking['estimated_hours']; ?> hours</p>
                        </div>
                    </div>
                    <div class="timeline-item">
                        <div class="timeline-icon">
                            <i class="fas fa-flag-checkered"></i>
                        </div>
                        <div class="timeline-content">
                            <h6 class="fw-bold mb-1"><?php echo htmlspecialchars($booking['destination']); ?></h6>
                            <p class="text-muted mb-0">
                                <i class="fas fa-clock me-1"></i>
                                <?php echo date('h:i A', strtotime($booking['arrival_time'])); ?>
                            </p>
                            <p class="small text-muted mb-0"><i class="fas fa-map-pin me-1"></i> Drop-off:
                                <?php echo htmlspecialchars($booking['dropoff_point']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Passenger Information -->
            <div class="info-section confirmation-card" style="animation-delay: 0.3s;">
                <h5><i class="fas fa-users"></i> Passenger Information</h5>
                <?php for ($i = 0; $i < count($passenger_names); $i++): ?>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-user-circle me-2"></i>Passenger <?php echo $i + 1; ?>
                        </div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($passenger_names[$i]); ?></strong><br>
                            <small class="text-muted"><i class="fas fa-phone me-1"></i>
                                <?php echo htmlspecialchars($passenger_phones[$i]); ?></small>
                        </div>
                    </div>
                <?php endfor; ?>

                <?php if ($booking['status'] === 'confirmed' && !empty($booking['seat_numbers'])): ?>
                    <div class="info-row">
                        <div class="info-label">
                            <i class="fas fa-chair me-2"></i>Seat Numbers
                        </div>
                        <div class="info-value">
                            <h4 class="mb-0" style="color: #1B5E20;">
                                <strong><?php echo htmlspecialchars($booking['seat_numbers']); ?></strong>
                            </h4>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Payment Information -->
            <div class="info-section confirmation-card" style="animation-delay: 0.4s;">
                <h5><i class="fas fa-credit-card"></i> Payment Information</h5>
                <div class="info-row">
                    <div class="info-label">Payment Method</div>
                    <div class="info-value">
                        <i class="fas fa-wallet me-2"></i>
                        <?php echo strtoupper(htmlspecialchars($booking['payment_method'])); ?>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Number of Passengers</div>
                    <div class="info-value"><?php echo $booking['num_passengers']; ?>
                        passenger<?php echo $booking['num_passengers'] > 1 ? 's' : ''; ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Price per Seat</div>
                    <div class="info-value"><?php echo number_format($booking['price']); ?> ETB</div>
                </div>
                <div class="info-row">
                    <div class="info-label">Total Amount</div>
                    <div class="info-value">
                        <h4 class="mb-0" style="color: #1B5E20;">
                            <strong><?php echo number_format($booking['total_amount']); ?> ETB</strong>
                        </h4>
                    </div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Status</div>
                    <div class="info-value">
                        <span class="badge bg-success px-3 py-2">
                            <i class="fas fa-check-circle me-1"></i> PAID
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-lg-4">
            <!-- Action Buttons -->
            <div class="info-section confirmation-card text-center" style="animation-delay: 0.5s;">
                <h5><i class="fas fa-download"></i> Download Ticket</h5>
                <p class="small text-muted">Get your PDF ticket for boarding</p>
                <div class="action-buttons">
                    <a href="download_ticket.php?id=<?php echo $booking_id; ?>"
                        class="btn btn-primary-green w-100 mb-3">
                        <i class="fas fa-file-pdf me-2"></i> Download PDF Ticket
                    </a>
                    <a href="buses.php" class="btn btn-outline-secondary w-100 mb-3">
                        <i class="fas fa-search me-2"></i> Book Another Ticket
                    </a>
                    <button onclick="window.print()" class="btn btn-outline-primary w-100">
                        <i class="fas fa-print me-2"></i> Print This Page
                    </button>
                </div>
            </div>

            <!-- Important Information -->
            <div class="info-section confirmation-card" style="animation-delay: 0.6s; background: #FFF9C4;">
                <h6 class="fw-bold mb-3"><i class="fas fa-exclamation-triangle text-warning me-2"></i> Important
                    Information</h6>
                <ul class="small mb-0" style="line-height: 1.8;">
                    <li>Arrive at pickup point 30 minutes before departure</li>
                    <li>Carry a valid ID for verification</li>
                    <li>Show this ticket at boarding</li>
                    <li>Ticket is non-refundable and non-transferable</li>
                </ul>
            </div>

            <!-- Contact Support -->
            <div class="info-section confirmation-card" style="animation-delay: 0.7s;">
                <h6 class="fw-bold mb-3"><i class="fas fa-headset me-2"></i> Need Help?</h6>
                <p class="small mb-2"><i class="fas fa-building me-2 text-primary"></i>
                    <strong><?php echo htmlspecialchars($booking['company_name']); ?></strong>
                </p>
                <p class="small mb-2"><i class="fas fa-phone me-2 text-success"></i>
                    <?php echo htmlspecialchars($booking['company_phone']); ?></p>
                <p class="small mb-0"><i class="fas fa-envelope me-2 text-danger"></i>
                    <a href="mailto:<?php echo htmlspecialchars($booking['company_email']); ?>">Email Support</a>
                </p>
            </div>
        </div>
    </div>
</main>

<?php include('../includes/footer.php'); ?>