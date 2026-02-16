<?php
/**
 * Email Service Helper
 * Sends emails with PDF attachments
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// If using PHPMailer (recommended)
// require 'vendor/autoload.php';

/**
 * Send email with PDF ticket attachment
 */
function sendTicketEmail($to_email, $to_name, $booking, $pdf_filepath)
{
    // Option 1: Using PHPMailer (RECOMMENDED)
    try {
        $mail = new PHPMailer(true);

        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com'; // Change to your SMTP server
        $mail->SMTPAuth = true;
        $mail->Username = 'your-email@gmail.com'; // Your email
        $mail->Password = 'your-app-password'; // Your app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        // Recipients
        $mail->setFrom('noreply@ethioserve.com', 'EthioServe');
        $mail->addAddress($to_email, $to_name);

        // Attach PDF
        $mail->addAttachment($pdf_filepath, 'bus_ticket.pdf');

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your Bus Ticket - Booking #' . $booking['booking_reference'];
        $mail->Body = getEmailTemplate($booking);

        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Email could not be sent. Error: {$mail->ErrorInfo}"];
    }
}

require_once __DIR__ . '/SimpleSMTP.php';

/**
 * Send email with attachment (PDF, HTML, etc.)
 * Supports PHPMailer (if available), SimpleSMTP (if configured), or mail() fallback
 */
function sendEmailWithAttachment($to_email, $to_name, $subject, $html_body, $file_path, $file_name, $mime_type = 'application/pdf')
{
    // 1. Try SimpleSMTP if configured
    if (defined('SMTP_HOST') && defined('SMTP_USER') && SMTP_USER !== 'your-email@gmail.com') {
        try {
            $mail = new SimpleSMTP(SMTP_HOST, SMTP_PORT, SMTP_USER, SMTP_PASS);
            $fromEmail = defined('SMTP_FROM_EMAIL') ? SMTP_FROM_EMAIL : 'noreply@ethioserve.com';
            $fromName = defined('SMTP_FROM_NAME') ? SMTP_FROM_NAME : 'EthioServe';

            // Prepare attachments
            $attachments = [];
            if (file_exists($file_path)) {
                $attachments[] = [
                    'path' => $file_path,
                    'name' => $file_name,
                    'type' => $mime_type
                ];
            }

            // Send
            if ($mail->send($to_email, $subject, $html_body, $fromName, $fromEmail, $attachments)) {
                return ['success' => true, 'message' => 'Email sent via SMTP'];
            }
        } catch (Exception $e) {
            error_log("SimpleSMTP Failed: " . $e->getMessage());
        }
    }

    // 2. Fallback to PHP mail()
    // Read file content
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'Attachment file not found'];
    }

    $file_content = chunk_split(base64_encode(file_get_contents($file_path)));

    // Email headers
    $boundary = md5(time());
    $headers = "From: EthioServe <noreply@ethioserve.com>\r\n";
    $headers .= "Reply-To: support@ethioserve.com\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";

    // Email body (HTML part)
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $html_body . "\r\n\r\n";

    // Attachment part
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: {$mime_type}; name=\"{$file_name}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$file_name}\"\r\n\r\n";
    $body .= $file_content . "\r\n\r\n";
    $body .= "--{$boundary}--\r\n";

    // Send email
    if (@mail($to_email, $subject, $body, $headers)) {
        return ['success' => true, 'message' => 'Email sent successfully via mail()'];
    } else {
        return ['success' => false, 'message' => 'Failed to send email'];
    }
}

/**
 * Email HTML template
 */
function getEmailTemplate($booking)
{
    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 0 auto; background: white; }
            .header { background: linear-gradient(135deg, #1B5E20 0%, #2E7D32 100%); color: white; padding: 40px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 28px; }
            .content { padding: 30px 20px; }
            .success-badge { background: #4CAF50; color: white; padding: 15px 30px; border-radius: 30px; display: inline-block; font-size: 18px; margin: 20px 0; }
            .info-box { background: #f9f9f9; border-left: 4px solid #1B5E20; padding: 15px; margin: 15px 0; }
            .info-row { display: flex; margin: 10px 0; }
            .info-label { font-weight: bold; width: 150px; color: #555; }
            .info-value { color: #333; }
            .cta-button { background: #1B5E20; color: white; padding: 15px 40px; text-decoration: none; border-radius: 5px; display: inline-block; margin: 20px 0; }
            .footer { background: #f5f5f5; padding: 20px; text-align: center; color: #888; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <h1>🚌 EthioServe Bus Ticket</h1>
                <p>Your journey begins here!</p>
            </div>
            
            <div class="content">
                <div style="text-align: center;">
                    <div class="success-badge">
                        ✅ Booking Confirmed!
                    </div>
                </div>
                
                <p>Dear ' . htmlspecialchars($booking['passenger_names']) . ',</p>
                
                <p>Thank you for booking with EthioServe! Your bus ticket has been successfully booked.</p>
                
                <div class="info-box">
                    <h3 style="margin-top: 0; color: #1B5E20;">📋 Booking Details</h3>
                    <div class="info-row">
                        <span class="info-label">Booking Reference:</span>
                        <span class="info-value"><strong>' . $booking['booking_reference'] . '</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Route:</span>
                        <span class="info-value">' . $booking['origin'] . ' → ' . $booking['destination'] . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Travel Date:</span>
                        <span class="info-value">' . date('l, F d, Y', strtotime($booking['travel_date'])) . '</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Departure Time:</span>
                        <span class="info-value">' . date('h:i A', strtotime($booking['departure_time'])) . '</span>
                    </div>
                    ' . (!empty($booking['seat_numbers']) ? '
                    <div class="info-row">
                        <span class="info-label">Seat Number(s):</span>
                        <span class="info-value"><strong style="color: #1B5E20; font-size: 18px;">' . $booking['seat_numbers'] . '</strong></span>
                    </div>
                    ' : '<div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value" style="color: #FF9800;">Pending seat assignment</span>
                    </div>') . '
                </div>
                
                <div class="info-box">
                    <h3 style="margin-top: 0; color: #1B5E20;">💰 Payment Information</h3>
                    <div class="info-row">
                        <span class="info-label">Total Amount:</span>
                        <span class="info-value"><strong>' . number_format($booking['total_amount']) . ' ETB</strong></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Payment Status:</span>
                        <span class="info-value" style="color: green;"><strong>PAID ✓</strong></span>
                    </div>
                </div>
                
                <div style="background: #FFF9C4; border-left: 4px solid #FBC02D; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0;"><strong>⚠️ Important:</strong></p>
                    <ul style="margin: 10px 0;">
                        <li>Please arrive at the pickup point 30 minutes before departure</li>
                        <li>Carry a valid ID for verification</li>
                        <li>Your PDF ticket is attached to this email</li>
                        <li>Show this ticket at boarding</li>
                    </ul>
                </div>
                
                <p style="text-align: center; margin-top: 30px;">
                    <strong>Need help?</strong> Contact us at support@ethioserve.com
                </p>
            </div>
            
            <div class="footer">
                <p>This is an automated email. Please do not reply.</p>
                <p>&copy; 2026 EthioServe. All rights reserved.</p>
            </div>
        </div>
    </body>
    </html>
    ';

    return $html;
}