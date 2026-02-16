<?php
/**
 * HTML Ticket Generator
 * Generates beautiful HTML tickets that can be attached to emails
 */

require_once __DIR__ . '/functions.php'; // Ensure functions are available if needed

/**
 * Generate HTML Ticket File
 * Saves the ticket as an HTML file in the temp directory
 */
function generateHTMLTicketFile($booking)
{
    // Generate the HTML content
    $html = generateTicketHTML($booking);
    
    // Create temp directory if not exists
    $tempDir = __DIR__ . '/../temp';
    if (!file_exists($tempDir)) {
        mkdir($tempDir, 0777, true);
    }
    
    // Filename
    $filename = 'Ticket_' . $booking['booking_reference'] . '.html';
    $filepath = $tempDir . '/' . $filename;
    
    // Save to file
    file_put_contents($filepath, $html);
    
    return [
        'filename' => $filename,
        'filepath' => $filepath
    ];
}

/**
 * Generate beautiful HTML ticket content
 */
function generateTicketHTML($booking)
{
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
                color: #333;
            }
            .ticket-container {
                max-width: 800px;
                margin: 0 auto;
                background: white;
                border: 1px solid #ddd;
                border-radius: 15px;
                overflow: hidden;
                box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            }
            .ticket-header {
                background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%);
                color: white;
                padding: 40px;
                text-align: center;
            }
            .ticket-header h1 {
                font-size: 28px;
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
                font-size: 24px;
                font-weight: bold;
                letter-spacing: 2px;
                border-bottom: 2px dashed #e0e0e0;
            }
            .status-badge {
                background: <?php echo $status_color; ?>;
                color: white;
                padding: 5px 15px;
                border-radius: 15px;
                display: inline-block;
                font-size: 12px;
                font-weight: bold;
                margin-top: 5px;
                text-transform: uppercase;
            }
            .ticket-body {
                padding: 40px;
            }
            .section {
                margin-bottom: 30px;
                padding-bottom: 20px;
                border-bottom: 1px solid #f0f0f0;
            }
            .section:last-child {
                border-bottom: none;
            }
            .section-title {
                color: #1B5E20;
                font-size: 16px;
                font-weight: bold;
                margin-bottom: 15px;
                text-transform: uppercase;
                letter-spacing: 1px;
            }
            .info-grid {
                display: table;
                width: 100%;
            }
            .info-row {
                display: table-row;
            }
            .info-label {
                display: table-cell;
                padding: 5px 10px 5px 0;
                font-weight: bold;
                color: #666;
                width: 180px;
            }
            .info-value {
                display: table-cell;
                padding: 5px 0;
                color: #333;
            }
            .journey-visual {
                background: #f9f9f9;
                padding: 20px;
                border-radius: 10px;
                margin: 20px 0;
                text-align: center;
            }
            .journey-route {
                font-size: 18px;
                font-weight: bold;
                color: #1B5E20;
                margin-bottom: 5px;
            }
            .journey-time {
                color: #666;
                font-size: 14px;
            }
            .important-box {
                background: #FFF9C4;
                border-left: 4px solid #FBC02D;
                padding: 15px;
                margin-top: 20px;
                font-size: 13px;
                line-height: 1.6;
            }
            .footer {
                background: #f9f9f9;
                padding: 20px;
                text-align: center;
                color: #999;
                font-size: 11px;
                border-top: 1px solid #eee;
            }
            @media print {
                body { padding: 0; background: white; }
                .ticket-container { border: none; box-shadow: none; }
            }
        </style>
    </head>
    <body>
        <div class="ticket-container">
            <div class="ticket-header">
                <h1>EthioServe Bus Ticket</h1>
                <p class="tagline">Safe Journey with <?php echo htmlspecialchars($booking['company_name']); ?></p>
            </div>
            
            <div class="booking-ref">
                <?php echo $booking['booking_reference']; ?>
                <br>
                <span class="status-badge"><?php echo $status_text; ?></span>
            </div>
            
            <div class="ticket-body">
                <!-- Journey Route Visual -->
                <div class="journey-visual">
                    <div class="journey-route">
                        <?php echo htmlspecialchars($booking['origin']); ?> 
                        <span style="margin: 0 10px; color: #999;">✈</span> 
                        <?php echo htmlspecialchars($booking['destination']); ?>
                    </div>
                    <div class="journey-time">
                        <?php echo date('h:i A', strtotime($booking['departure_time'])); ?> 
                        - 
                        <?php echo date('h:i A', strtotime($booking['arrival_time'])); ?>
                    </div>
                </div>
                
                <!-- Journey Details -->
                <div class="section">
                    <div class="section-title">Journey Details</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Travel Date:</div>
                            <div class="info-value"><?php echo date('l, F d, Y', strtotime($booking['travel_date'])); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Bus Company:</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['company_name']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Bus Number:</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['bus_number']); ?> (<?php echo htmlspecialchars($booking['bus_type']); ?>)</div>
                        </div>
                        <?php if (!empty($booking['seat_numbers'])): ?>
                        <div class="info-row">
                            <div class="info-label">Seat Number(s):</div>
                            <div class="info-value"><strong style="color: #1B5E20;"><?php echo htmlspecialchars($booking['seat_numbers']); ?></strong></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Passenger Info -->
                <div class="section">
                    <div class="section-title">Passengers</div>
                    <div class="info-grid">
                        <?php 
                        $names = explode('|', $booking['passenger_names']);
                        $phones = explode('|', $booking['passenger_phones']);
                        for ($i = 0; $i < count($names); $i++): 
                        ?>
                        <div class="info-row">
                            <div class="info-label">Passenger <?php echo $i + 1; ?>:</div>
                            <div class="info-value">
                                <?php echo htmlspecialchars($names[$i]); ?><br>
                                <small style="color: #666;"><?php echo htmlspecialchars($phones[$i] ?? ''); ?></small>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                
                <!-- Pickup & Dropoff -->
                <div class="section">
                    <div class="section-title">Locations</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Pickup Point:</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['pickup_point']); ?></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Drop-off Point:</div>
                            <div class="info-value"><?php echo htmlspecialchars($booking['dropoff_point']); ?></div>
                        </div>
                    </div>
                </div>
                
                <!-- Payment -->
                <div class="section">
                    <div class="section-title">Payment</div>
                    <div class="info-grid">
                        <div class="info-row">
                            <div class="info-label">Total Amount:</div>
                            <div class="info-value"><strong><?php echo number_format($booking['total_amount']); ?> ETB</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Method:</div>
                            <div class="info-value"><?php echo strtoupper(htmlspecialchars($booking['payment_method'])); ?> <span style="color: green; margin-left: 5px;">(PAID)</span></div>
                        </div>
                    </div>
                </div>
                
                <!-- Important -->
                <div class="important-box">
                    <strong>⚠️ Important Information:</strong><br>
                    • Arrive at pickup point 30 minutes before departure.<br>
                    • Carry a valid ID for verification.<br>
                    • This digital ticket is valid for boarding. You can print it or show it on your phone.
                </div>
            </div>
            
            <div class="footer">
                <p>Thank you for choosing EthioServe.</p>
                <p>Ticket Generated: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    return ob_get_clean();
}
?>