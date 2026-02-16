<?php
/**
 * Enhanced Email Templates with Bus Images and QR Codes
 */

/**
 * Generate QR Code for ticket
 * Using Google Charts API (simple, no library needed)
 */
function generateQRCode($booking_reference)
{
    $qr_data = urlencode($booking_reference);
    $qr_size = '200x200';
    $qr_url = "https://chart.googleapis.com/chart?cht=qr&chs={$qr_size}&chl={$qr_data}";
    return $qr_url;
}

/**
 * Get bus type image/icon as base64 for email embedding
 */
function getBusImageForEmail($bus_type)
{
    $bus_type_lower = strtolower($bus_type);

    // Color based on bus type
    if (strpos($bus_type_lower, 'luxury') !== false || strpos($bus_type_lower, 'vip') !== false) {
        $color = '#FFD700'; // Gold
    } elseif (strpos($bus_type_lower, 'standard') !== false) {
        $color = '#2196F3'; // Blue
    } else {
        $color = '#4CAF50'; // Green
    }

    // Generate SVG bus image
    $svg = <<<SVG
<svg width="120" height="120" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
    <defs>
        <linearGradient id="busGrad" x1="0%" y1="0%" x2="0%" y2="100%">
            <stop offset="0%" style="stop-color:{$color};stop-opacity:1" />
            <stop offset="100%" style="stop-color:{$color}CC;stop-opacity:1" />
        </linearGradient>
    </defs>
    <rect x="10" y="30" width="80" height="45" rx="8" fill="url(#busGrad)" />
    <rect x="15" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
    <rect x="35" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
    <rect x="55" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
    <rect x="75" y="35" width="10" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
    <circle cx="25" cy="75" r="8" fill="#333" />
    <circle cx="25" cy="75" r="4" fill="#666" />
    <circle cx="75" cy="75" r="8" fill="#333" />
    <circle cx="75" cy="75" r="4" fill="#666" />
    <circle cx="85" cy="60" r="3" fill="#FFD700" opacity="0.8" />
    <rect x="15" y="52" width="12" height="18" rx="2" fill="rgba(255,255,255,0.7)" />
</svg>
SVG;

    return 'data:image/svg+xml;base64,' . base64_encode($svg);
}

/**
 * EMAIL #1: Booking Confirmation (Sent immediately after booking)
 */
function sendBookingConfirmationEmail($booking, $customer_email, $customer_name)
{
    $qr_code_url = generateQRCode($booking['booking_reference']);
    $bus_image = getBusImageForEmail($booking['bus_type']);

    $subject = "✅ Booking Confirmed - {$booking['booking_reference']} | EthioServe";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 650px; margin: 0 auto; background: white; }
        .header { background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%); color: white; padding: 30px 20px; text-align: center; }
        .logo { font-size: 28px; font-weight: bold; margin-bottom: 10px; }
        .ticket-card { background: white; margin: 20px; border: 2px solid #1B5E20; border-radius: 15px; overflow: hidden; }
        .ticket-header { background: #1B5E20; color: white; padding: 20px; text-align: center; }
        .booking-ref { font-size: 32px; font-weight: bold; letter-spacing: 3px; margin: 10px 0; }
        .status-badge { background: #4CAF50; color: white; padding: 8px 20px; border-radius: 20px; display: inline-block; font-weight: bold; }
        .ticket-body { padding: 30px; }
        .section { margin-bottom: 25px; }
        .section-title { background: #f8f8f8; padding: 10px 15px; font-weight: bold; color: #1B5E20; border-left: 4px solid #1B5E20; margin-bottom: 15px; }
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { flex: 0 0 180px; font-weight: 600; color: #666; }
        .info-value { flex: 1; color: #333; }
        .route-visual { background: #f8f9fa; padding: 20px; border-radius: 10px; text-align: center; margin: 20px 0; }
        .route-line { display: flex; align-items: center; justify-content: center; gap: 15px; }
        .route-point { font-size: 18px; font-weight: bold; color: #1B5E20; }
        .route-arrow { color: #999; font-size: 24px; }
        .qr-section { text-align: center; padding: 20px; background: #f9f9f9; border-radius: 10px; margin: 20px 0; }
        .bus-image { margin: 20px auto; text-align: center; }
        .important-box { background: #FFF9C4; border-left: 4px solid #FBC02D; padding: 15px; margin: 20px 0; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #888; font-size: 12px; }
        .btn { display: inline-block; background: #1B5E20; color: white; padding: 15px 30px; text-decoration: none; border-radius: 25px; font-weight: bold; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="logo">🚌 EthioServe</div>
            <div style="font-size: 18px; opacity: 0.9;">Ethiopian Bus Booking System</div>
        </div>
        
        <!-- Success Message -->
        <div style="text-align: center; padding: 30px 20px; background: #E8F5E9;">
            <h1 style="color: #1B5E20; margin: 0 0 10px 0;">✅ Booking Confirmed!</h1>
            <p style="color: #666; font-size: 16px; margin: 0;">Your ticket has been successfully booked</p>
        </div>
        
        <!-- Ticket Card -->
        <div class="ticket-card">
            <div class="ticket-header">
                <div style="font-size: 14px; opacity: 0.9;">BOOKING REFERENCE</div>
                <div class="booking-ref">{$booking['booking_reference']}</div>
                <div class="status-badge">CONFIRMED</div>
            </div>
            
            <div class="ticket-body">
                <!-- Bus Information -->
                <div class="bus-image">
                    <img src="{$bus_image}" alt="Bus" width="120" height="120" />
                    <h3 style="color: #1B5E20; margin: 10px 0;">{$booking['company_name']}</h3>
                    <div style="color: #666;">{$booking['bus_type']} • Bus #{$booking['bus_number']}</div>
                </div>
                
                <!-- Route Information -->
                <div class="section">
                    <div class="section-title">🗺️ JOURNEY DETAILS</div>
                    <div class="route-visual">
                        <div class="route-line">
                            <div class="route-point">{$booking['origin']}</div>
                            <div class="route-arrow">✈️ →</div>
                            <div class="route-point">{$booking['destination']}</div>
                        </div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">📅 Travel Date:</div>
                        <div class="info-value"><strong>{$booking['travel_date_formatted']}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">🕐 Departure Time:</div>
                        <div class="info-value"><strong>{$booking['departure_time_formatted']}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">🕐 Arrival Time:</div>
                        <div class="info-value"><strong>{$booking['arrival_time_formatted']}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">📍 Pickup Point:</div>
                        <div class="info-value">{$booking['pickup_point']}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">📍 Drop-off Point:</div>
                        <div class="info-value">{$booking['dropoff_point']}</div>
                    </div>
                </div>
                
                <!-- Passenger Information -->
                <div class="section">
                    <div class="section-title">👤 PASSENGER DETAILS</div>
                    <div class="info-row">
                        <div class="info-label">Passenger Name:</div>
                        <div class="info-value"><strong>{$customer_name}</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Number of Passengers:</div>
                        <div class="info-value">{$booking['num_passengers']}</div>
                    </div>
                    {$booking['seat_numbers_html']}
                </div>
                
                <!-- Payment Information -->
                <div class="section">
                    <div class="section-title">💰 PAYMENT DETAILS</div>
                    <div class="info-row">
                        <div class="info-label">Payment Method:</div>
                        <div class="info-value">{$booking['payment_method']}</div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Total Amount:</div>
                        <div class="info-value"><strong style="font-size: 20px; color: #1B5E20;">{$booking['total_amount']} ETB</strong></div>
                    </div>
                    <div class="info-row">
                        <div class="info-label">Payment Status:</div>
                        <div class="info-value"><span style="color: green; font-weight: bold;">✓ PAID</span></div>
                    </div>
                </div>
                
                <!-- QR Code -->
                <div class="qr-section">
                    <h4 style="margin-top: 0;">📱 Your Ticket QR Code</h4>
                    <img src="{$qr_code_url}" alt="QR Code" width="200" height="200" />
                    <p style="color: #666; font-size: 12px; margin: 10px 0 0 0;">Show this QR code at boarding</p>
                </div>
                
                <!-- Important Information -->
                <div class="important-box">
                    <h4 style="margin-top: 0;">⚠️ IMPORTANT INFORMATION</h4>
                    <ul style="margin: 10px 0; line-height: 1.8;">
                        <li>Arrive at pickup point 30 minutes before departure</li>
                        <li>Carry a valid ID for verification</li>
                        <li>Show this email or QR code at boarding</li>
                        <li>You will receive a reminder email 3 hours before departure</li>
                    </ul>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Need Help?</strong></p>
            <p>Company: {$booking['company_name']}<br>
            Phone: {$booking['company_phone']}<br>
            Email: {$booking['company_email']}</p>
            <p style="margin-top: 20px;">Thank you for choosing EthioServe!<br>
            &copy; 2026 EthioServe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendEmail($customer_email, $customer_name, $subject, $html);
}

/**
 * EMAIL #2: Travel Reminder (Sent 3 hours before departure)
 */
function sendTravelReminderEmail($booking, $customer_email, $customer_name)
{
    $qr_code_url = generateQRCode($booking['booking_reference']);
    $bus_image = getBusImageForEmail($booking['bus_type']);

    $subject = "🔔 Reminder: Your Bus Departs in 3 Hours - {$booking['booking_reference']}";

    $html = <<<HTML
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { margin: 0; padding: 0; font-family: 'Segoe UI', Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 650px; margin: 0 auto; background: white; }
        .header { background: linear-gradient(135deg, #FF9800 0%, #F57C00 100%); color: white; padding: 30px 20px; text-align: center; }
        .alert-banner { background: #FFF9C4; border-left: 4px solid #FF9800; padding: 20px; margin: 20px; }
        .alert-banner h2 { color: #F57C00; margin: 0 0 10px 0; }
        .ticket-card { background: white; margin: 20px; border: 2px solid #FF9800; border-radius: 15px; overflow: hidden; }
        .ticket-header { background: #FF9800; color: white; padding: 20px; text-align: center; }
        .booking-ref { font-size: 28px; font-weight: bold; letter-spacing: 2px; }
        .ticket-body { padding: 30px; }
        .countdown { background: #FF9800; color: white; padding: 20px; text-align: center; border-radius: 10px; margin: 20px; }
        .countdown-time { font-size: 48px; font-weight: bold; }
        .section-title { background: #f8f8f8; padding: 10px 15px; font-weight: bold; color: #FF9800; border-left: 4px solid #FF9800; margin: 20px 0 15px 0; }
        .info-row { display: flex; padding: 10px 0; border-bottom: 1px solid #eee; }
        .info-label { flex: 0 0 180px; font-weight: 600; color: #666; }
        .info-value { flex: 1; color: #333; }
        .qr-section { text-align: center; padding: 30px; background: #FFF3E0; border-radius: 10px; margin: 20px 0; border: 3px dashed #FF9800; }
        .checklist { background: #E8F5E9; padding: 20px; border-radius: 10px; margin: 20px 0; }
        .checklist li { padding: 10px; font-size: 16px; }
        .btn-primary { background: #FF9800; color: white; padding: 15px 40px; text-decoration: none; border-radius: 25px; font-weight: bold; display: inline-block; margin: 10px; }
        .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #888; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1 style="margin: 0; font-size: 32px;">⏰ TRAVEL REMINDER</h1>
            <p style="font-size: 18px; margin: 10px 0 0 0;">Your bus departs soon!</p>
        </div>
        
        <!-- Countdown -->
        <div class="countdown">
            <div style="font-size: 18px; margin-bottom: 10px;">DEPARTURE IN</div>
            <div class="countdown-time">3 HOURS</div>
        </div>
        
        <!-- Alert Banner -->
        <div class="alert-banner">
            <h2>🔔 Get Ready to Travel!</h2>
            <p style="margin: 0; font-size: 16px;">Dear {$customer_name}, your bus from <strong>{$booking['origin']}</strong> to <strong>{$booking['destination']}</strong> departs at <strong>{$booking['departure_time_formatted']}</strong> today.</p>
        </div>
        
        <!-- Ticket Card -->
        <div class="ticket-card">
            <div class="ticket-header">
                <div>BOOKING REFERENCE</div>
                <div class="booking-ref">{$booking['booking_reference']}</div>
            </div>
            
            <div class="ticket-body">
                <!-- Bus Info -->
                <div style="text-align: center; margin-bottom: 30px;">
                    <img src="{$bus_image}" alt="Bus" width="120" height="120" />
                    <h3 style="color: #FF9800; margin: 10px 0;">{$booking['company_name']}</h3>
                    <div>{$booking['bus_type']} • Bus #{$booking['bus_number']}</div>
                </div>
                
                <!-- Key Information -->
                <div class="section-title">🚌 DEPARTURE DETAILS</div>
                <div class="info-row">
                    <div class="info-label">📅 Today's Date:</div>
                    <div class="info-value"><strong>{$booking['travel_date_formatted']}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">🕐 Departure Time:</div>
                    <div class="info-value"><strong style="font-size: 20px; color: #FF9800;">{$booking['departure_time_formatted']}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">📍 Pickup Location:</div>
                    <div class="info-value"><strong>{$booking['pickup_point']}</strong></div>
                </div>
                <div class="info-row">
                    <div class="info-label">📍 Destination:</div>
                    <div class="info-value"><strong>{$booking['destination']}</strong></div>
                </div>
                {$booking['seat_numbers_html']}
                
                <!-- QR Code -->
                <div class="qr-section">
                    <h3 style="color: #FF9800; margin-top: 0;">📱 YOUR BOARDING PASS</h3>
                    <img src="{$qr_code_url}" alt="QR Code" width="250" height="250" />
                    <p style="font-size: 18px; font-weight: bold; margin: 15px 0 5px 0;">Show this QR code at boarding</p>
                    <p style="color: #666; font-size: 14px; margin: 0;">Reference: {$booking['booking_reference']}</p>
                </div>
                
                <!-- Pre-Travel Checklist -->
                <div class="checklist">
                    <h3 style="color: #2E7D32; margin-top: 0;">✅ PRE-TRAVEL CHECKLIST</h3>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li>✅ Valid ID (National ID, Passport, or Driver's License)</li>
                        <li>✅ This email with QR code (printed or on phone)</li>
                        <li>✅ Arrive 30 minutes early at: <strong>{$booking['pickup_point']}</strong></li>
                        <li>✅ Contact number saved: <strong>{$booking['company_phone']}</strong></li>
                        <li>✅ Light luggage (check company policy)</li>
                    </ul>
                </div>
                
                <!-- Emergency Contact -->
                <div style="background: #FFEBEE; padding: 20px; border-radius: 10px; border-left: 4px solid #f44336; margin: 20px 0;">
                    <h4 style="color: #c62828; margin-top: 0;">📞 EMERGENCY CONTACT</h4>
                    <p style="margin: 5px 0;"><strong>{$booking['company_name']}</strong></p>
                    <p style="margin: 5px 0;">Phone: <strong>{$booking['company_phone']}</strong></p>
                    <p style="margin: 5px 0;">Email: {$booking['company_email']}</p>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p style="font-size: 14px;"><strong>Have a safe journey!</strong></p>
            <p>&copy; 2026 EthioServe. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
HTML;

    return sendEmail($customer_email, $customer_name, $subject, $html);
}

/**
 * Helper function to send email
 */
function sendEmail($to_email, $to_name, $subject, $html_body)
{
    // Using PHP mail() function
    // For production, use PHPMailer or similar

    $headers = "From: EthioServe <noreply@ethioserve.com>\r\n";
    $headers .= "Reply-To: support@ethioserve.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    $success = mail($to_email, $subject, $html_body, $headers);

    return [
        'success' => $success,
        'message' => $success ? 'Email sent successfully' : 'Failed to send email'
    ];
}
?>