<?php
require_once '../includes/functions.php';
requireLogin();
require_once '../includes/db.php';
require_once '../includes/pdf_generator.php'; // We'll create a simpler version
require_once '../includes/email_service.php';

$booking_id = (int) ($_GET['id'] ?? 0);

if ($booking_id <= 0) {
    die('Invalid booking ID');
}

// Fetch complete booking details
$stmt = $pdo->prepare("
    SELECT bb.*, 
           s.departure_time, s.arrival_time,
           r.origin, r.destination,
           b.bus_number, bt.name as bus_type,
           tc.company_name
    FROM bus_bookings bb
    JOIN schedules s ON bb.schedule_id = s.id
    JOIN routes r ON s.route_id = r.id
    JOIN buses b ON s.bus_id = b.id
    JOIN bus_types bt ON b.bus_type_id = bt.id
    JOIN transport_companies tc ON b.company_id = tc.id
    WHERE bb.id = ? AND bb.customer_id = ?
");
$stmt->execute([$booking_id, getCurrentUserId()]);
$booking = $stmt->fetch();

if (!$booking) {
    die('Booking not found');
}

// Generate simple HTML ticket
$html = generateTicketHTML($booking);

// Output as downloadable HTML (can be converted to PDF later)
header('Content-Type: text/html; charset=utf-8');
header('Content-Disposition: attachment; filename="ticket_' . $booking['booking_reference'] . '.html"');

echo $html;

/**
 * Generate beautiful HTML ticket
 */
function generateTicketHTML($booking) {
    $status_color = $booking['status'] === 'confirmed' ? '#4CAF50' : '#FF9800';
    $status_text = $booking['status'] === 'confirmed' ? 'CONFIRMED' : 'PENDING';
    
    ob_start();
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Bus Ticket - <?php echo $booking['booking_reference']; ?></title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { 
                font-family: 'Arial', sans-serif; 
                background: #f5f5f5; 
                padding: 40px 20px;
            }
            .ticket-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            }
            .ticket-header {
                background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
                color: white;
                padding: 40px;
                text-align: center;
            }
            .ticket-header h1 {
                font-size: 32px;
                margin-bottom: 10px;
            }
            .ticket-header .tagline {
                opacity: 0.9;
                font-size: 14px;
            }
            .booking-ref {
                background: white;
                color: #1B5E20;
                padding: 20px;
                text-align: center;
                font-size: 28px;
                font-weight: bold;
                letter-spacing: 3px;
                border-bottom: 3px dashed #e0e0e0;
            }
            .status-badge {
                background: <?php echo $status_color; ?>;
                color: white;
                padding: 8px 20px;
                border-radius: 20px;
                display: inline-block;
                font-weight: bold;
                margin-top: 10px;
            }
            .ticket-body {
                padding: 40px;
            }
            .section {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #e0e0e0;
            }
            .section:last-child {
                border-bottom: none;
            }
            .section-title {
                color: #1B5E20;
                font-size: 18px;
                font-weight: bold;
                margin-bottom: 15px;
                padding-bottom: 10px;
                border-bottom: 2px solid #1B5E20;
            }
            .info-grid {
                display: grid;
                grid-template-columns: 200px 1fr;
                gap: 15px;
            }
            .info-label {
                font-weight: 600;
                color: #666;
            }
            .info-value {
                color: #333;
            }
            .journey-visual {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px;
                background: #f9f9f9;
                border-radius: 10px;
                margin: 20px 0;
            }
            .journey-point {
                text-align: center;
                flex: 1;
            }
            .journey-point h3 {
                color: #1B5E20;
                margin-bottom: 5px;
            }
            .journey-arrow {
                flex: 0 0 100px;
                text-align: center;
                font-size: 30px;
                color: #1B5E20;
            }
            .important-box {
                background: #FFF9C4;
                border-left: 4px solid #FBC02D;
                padding: 20px;
                margin: 20px 0;
            }
            .important-box h4 {
                color: #F57F17;
                margin-bottom: 10px;
            }
            .qr-placeholder {
                width: 150px;
                height: 150px;
                background: #f0f0f0;
                border: 2px dashed #999;
                border-radius: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                margin: 20px auto;
                font-size: 12px;
                color: #999;
                text-align: center;
            }
            .footer {
                background: #f5f5f5;
                padding: 20px;
                text-align: center;
                color: #888;
                font-size: 12px;
            }
            @media print {
                body { background: white; padding: 0; }
                .ticket-container { box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="ticket-container">
            <div class="ticket-header">
                <h1>🚌 EthioServe Bus Ticket</h1>
                <p class="tagline">Your Journey Begins Here</p>
            </div>
            
            <div class="booking-ref">
                <?php echo $booking['booking_reference']; ?>
                <br>
                <span class="status-badge"><?php echo $status_text; ?></span>
            </div>
            
            <div class="ticket-body">
                <!-- Journey Route Visual -->
                <div class="journey-visual">
                    <div class="journey-point">
                        <h3><?php echo htmlspecialchars($booking['origin']); ?></h3>
                        <p><?php echo date('h:i A', strtotime($booking['departure_time'])); ?></p>
                    </div>
                    <div class="journey-arrow">
                        ✈️ →
                    </div>
                    <div class="journey-point">
                        <h3><?php echo htmlspecialchars($booking['destination']); ?></h3>
                        <p><?php echo date('h:i A', strtotime($booking['arrival_time'])); ?></p>
                    </div>
                </div>
                
                <!-- Journey Details Section -->
                <div class="section">
                    <div class="section-title">📋 Journey Details</div>
                    <div class="info-grid">
                        <div class="info-label">Travel Date:</div>
                        <div class="info-value"><strong><?php echo date('l, F d, Y', strtotime($booking['travel_date'])); ?></strong></div>
                        
                        <div class="info-label">Bus Company:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['company_name']); ?></div>
                        
                        <div class="info-label">Bus Number:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['bus_number']); ?> (<?php echo htmlspecialchars($booking['bus_type']); ?>)</div>
                        
                        <?php if (!empty($booking['seat_numbers'])): ?>
                        <div class="info-label">Seat Number(s):</div>
                        <div class="info-value"><strong style="color: #1B5E20; font-size: 20px;"><?php echo htmlspecialchars($booking['seat_numbers']); ?></strong></div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Passenger Information -->
                <div class="section">
                    <div class="section-title">👤 Passenger Information</div>
                    <div class="info-grid">
                        <?php 
                        $names = explode('|', $booking['passenger_names']);
                        $phones = explode('|', $booking['passenger_phones']);
                        for ($i = 0; $i < count($names); $i++): 
                        ?>
                        <div class="info-label">Passenger <?php echo $i + 1; ?>:</div>
                        <div class="info-value">
                            <strong><?php echo htmlspecialchars($names[$i]); ?></strong><br>
                            <small>📞 <?php echo htmlspecialchars($phones[$i]); ?></small>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Pickup & Drop-off -->
                <div class="section">
                    <div class="section-title">📍 Pickup & Drop-off</div>
                    <div class="info-grid">
                        <div class="info-label">Pickup Point:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['pickup_point']); ?></div>
                        
                        <div class="info-label">Drop-off Point:</div>
                        <div class="info-value"><?php echo htmlspecialchars($booking['dropoff_point']); ?></div>
                    </div>
                </div>
                
                <!-- Payment Information -->
                <div class="section">
                    <div class="section-title">💰 Payment Information</div>
                    <div class="info-grid">
                        <div class="info-label">Payment Method:</div>
                        <div class="info-value"><?php echo strtoupper(htmlspecialchars($booking['payment_method'])); ?></div>
                        
                        <div class="info-label">Total Amount:</div>
                        <div class="info-value"><strong style="color: #1B5E20; font-size: 24px;"><?php echo number_format($booking['total_amount']); ?> ETB</strong></div>
                        
                        <div class="info-label">Payment Status:</div>
                        <div class="info-value"><span style="color: green; font-weight: bold;">✓ PAID</span></div>
                    </div>
                </div>
                
                <!-- QR Code Placeholder -->
                <div class="qr-placeholder">
                    QR CODE<br>
                    <?php echo $booking['booking_reference']; ?>
                </div>
                
                <!-- Important Information -->
                <div class="important-box">
                    <h4>⚠️ Important Information</h4>
                    <ul style="margin-left: 20px; line-height: 1.8;">
                        <li>Please arrive at the pickup point at least 30 minutes before departure time</li>
                        <li>Carry a valid ID for verification at boarding</li>
                        <li>This ticket is non-transferable and non-refundable</li>
                        <li>Show this ticket (printed or digital) at boarding</li>
                        <li>Contact the bus company for any changes or inquiries</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing EthioServe</p>
                <p>For support, visit www.ethioserve.com or call our helpline</p>
                <p>Ticket generated on: <?php echo date('F d, Y h:i A'); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>
