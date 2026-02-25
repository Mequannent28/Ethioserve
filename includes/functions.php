<?php
/**
 * Core Functions for EthioServe Platform
 * Contains authentication, CSRF protection, sanitization, and helper functions
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure both session keys are set for compatibility across modules
if (isset($_SESSION['user_id']) && !isset($_SESSION['id'])) {
    $_SESSION['id'] = $_SESSION['user_id'];
} elseif (isset($_SESSION['id']) && !isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = $_SESSION['id'];
}


/**
 * Generate CSRF Token
 */
function generateCSRFToken()
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF Token
 */
function verifyCSRFToken($token)
{
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get CSRF Token Field (for forms)
 */
function csrfField()
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(generateCSRFToken()) . '">';
}

/**
 * Sanitize input string
 */
function sanitize($input)
{
    if (is_array($input)) {
        return array_map('sanitize', $input);
    }
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Check if user is logged in
 */
function isLoggedIn()
{
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user has specific role
 */
function hasRole($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

/**
 * Require login - redirect to login page if not logged in
 */
function requireLogin()
{
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        header("Location: ../login.php");
        exit();
    }
}

/**
 * Require specific role - redirect if not authorized
 */
function requireRole($role)
{
    requireLogin();
    if (!hasRole($role)) {
        header("Location: ../index.php");
        exit();
    }
}

/**
 * Get current user ID
 */
function getCurrentUserId()
{
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user role
 */
function getCurrentUserRole()
{
    return $_SESSION['role'] ?? null;
}

/**
 * Get current user name
 */
function getCurrentUserName()
{
    return $_SESSION['full_name'] ?? $_SESSION['username'] ?? 'Guest';
}

/**
 * Initialize cart in session
 */
function initCart()
{
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [
            'items' => [],
            'hotel_id' => null,
            'hotel_name' => null
        ];
    }
}

/**
 * Add item to cart
 */
function addToCart($item_id, $name, $price, $quantity = 1, $hotel_id = null, $hotel_name = null, $image_url = null)
{
    initCart();

    // Check if cart has items from different hotel
    if ($_SESSION['cart']['hotel_id'] !== null && $_SESSION['cart']['hotel_id'] != $hotel_id) {
        return ['success' => false, 'message' => 'Cannot mix items from different hotels. Clear cart first.'];
    }

    // Set hotel info if cart is empty
    if (empty($_SESSION['cart']['items'])) {
        $_SESSION['cart']['hotel_id'] = $hotel_id;
        $_SESSION['cart']['hotel_name'] = $hotel_name;
    }

    $item_key = (string) $item_id;

    if (isset($_SESSION['cart']['items'][$item_key])) {
        $_SESSION['cart']['items'][$item_key]['quantity'] += $quantity;
    } else {
        $_SESSION['cart']['items'][$item_key] = [
            'id' => $item_id,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'image_url' => $image_url
        ];
    }

    return ['success' => true, 'message' => 'Item added to cart', 'cart_count' => getCartCount()];
}

/**
 * Remove item from cart
 */
function removeFromCart($item_id)
{
    initCart();
    $item_key = (string) $item_id;

    if (isset($_SESSION['cart']['items'][$item_key])) {
        unset($_SESSION['cart']['items'][$item_key]);

        // Clear hotel info if cart is empty
        if (empty($_SESSION['cart']['items'])) {
            $_SESSION['cart']['hotel_id'] = null;
            $_SESSION['cart']['hotel_name'] = null;
        }
        return ['success' => true, 'message' => 'Item removed from cart'];
    }
    return ['success' => false, 'message' => 'Item not found in cart'];
}

/**
 * Update cart item quantity
 */
function updateCartQuantity($item_id, $quantity)
{
    initCart();
    $item_key = (string) $item_id;

    if ($quantity <= 0) {
        return removeFromCart($item_id);
    }

    if (isset($_SESSION['cart']['items'][$item_key])) {
        $_SESSION['cart']['items'][$item_key]['quantity'] = $quantity;
        return ['success' => true, 'message' => 'Quantity updated'];
    }
    return ['success' => false, 'message' => 'Item not found in cart'];
}

/**
 * Get cart items
 */
function getCartItems()
{
    initCart();
    return $_SESSION['cart']['items'];
}

/**
 * Get cart total count
 */
function getCartCount()
{
    initCart();
    return array_sum(array_column($_SESSION['cart']['items'], 'quantity'));
}

/**
 * Get cart subtotal
 */
function getCartSubtotal()
{
    initCart();
    $total = 0;
    foreach ($_SESSION['cart']['items'] as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

/**
 * Get cart hotel info
 */
function getCartHotel()
{
    initCart();
    return [
        'hotel_id' => $_SESSION['cart']['hotel_id'],
        'hotel_name' => $_SESSION['cart']['hotel_name']
    ];
}

/**
 * Clear cart
 */
function clearCart()
{
    $_SESSION['cart'] = [
        'items' => [],
        'hotel_id' => null,
        'hotel_name' => null
    ];
}

/**
 * Format price with currency
 */
function formatPrice($price)
{
    return number_format($price, 2) . ' ETB';
}

/**
 * Generate unique order reference
 */
function generateOrderRef()
{
    return 'ORD-' . strtoupper(uniqid());
}

/**
 * Generate referral code
 */
function generateReferralCode()
{
    return 'ETH' . strtoupper(substr(uniqid(), -6));
}

/**
 * Generate a random PNR Code (6 characters)
 */
function generatePNR($length = 6)
{
    $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $pnr = '';
    for ($i = 0; $i < $length; $i++) {
        $pnr .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $pnr;
}

/**
 * Calculate broker commission (5%)
 */
function calculateCommission($amount)
{
    return $amount * 0.05;
}

/**
 * Send JSON response
 */
function jsonResponse($data, $status_code = 200)
{
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit();
}

/**
 * Redirect with message
 */
function redirectWithMessage($url, $type, $message)
{
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
    header("Location: $url");
    exit();
}

/**
 * Get and clear flash message
 */
function getFlashMessage()
{
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Display flash message HTML
 */
function displayFlashMessage()
{
    $flash = getFlashMessage();
    if ($flash) {
        if (is_array($flash)) {
            $type = $flash['type'] ?? 'info';
            $message = $flash['message'] ?? '';
        } else {
            $message = $flash;
            $type = $_SESSION['flash_type'] ?? 'info';
            unset($_SESSION['flash_type']);
        }

        $alert_class = ($type === 'success') ? 'alert-success' :
            (($type === 'error' || $type === 'danger') ? 'alert-danger' : 'alert-warning');

        return "<div class=\"alert {$alert_class} alert-dismissible fade show\" role=\"alert\">
                    {$message}
                    <button type=\"button\" class=\"btn-close\" data-bs-dismiss=\"alert\"></button>
                </div>";
    }
    return '';
}

/**
 * Validate date format
 */
function validateDate($date, $format = 'Y-m-d')
{
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Validate time format
 */
function validateTime($time, $format = 'H:i')
{
    $d = DateTime::createFromFormat($format, $time);
    return $d && $d->format($format) === $time;
}

/**
 * Get status badge HTML
 */
function getStatusBadge($status)
{
    $badges = [
        'pending' => '<span class="badge bg-warning text-dark">Pending</span>',
        'approved' => '<span class="badge bg-success">Approved</span>',
        'rejected' => '<span class="badge bg-danger">Rejected</span>',
        'cancelled' => '<span class="badge bg-danger">Cancelled</span>',
        'preparing' => '<span class="badge bg-info">Preparing</span>',
        'ready' => '<span class="badge bg-primary">Ready</span>',
        'on_delivery' => '<span class="badge bg-primary">On Delivery</span>',
        'delivered' => '<span class="badge bg-success">Delivered</span>',
        'paid' => '<span class="badge bg-success">Paid</span>',
        'failed' => '<span class="badge bg-danger">Failed</span>',
        'requested' => '<span class="badge bg-warning text-dark">Requested</span>',
        'accepted' => '<span class="badge bg-info">Accepted</span>',
        'in_progress' => '<span class="badge bg-primary">In Progress</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        'available' => '<span class="badge bg-success">Available</span>',
        'taken' => '<span class="badge bg-secondary">Taken</span>',
        'active' => '<span class="badge bg-success">Active</span>',
        'inactive' => '<span class="badge bg-secondary">Inactive</span>',
        'showing' => '<span class="badge bg-info">Now Showing</span>',
        'upcoming' => '<span class="badge bg-warning text-dark">Upcoming</span>',
        'ended' => '<span class="badge bg-secondary">Ended</span>',
        'confirmed' => '<span class="badge bg-success">Confirmed</span>'
    ];
    return $badges[$status] ?? '<span class="badge bg-secondary">' . ucfirst($status) . '</span>';
}

/**
 * Send Order Notification Email (Temporary)
 * Sends email to customer and restaurant when an order is placed
 */
function sendOrderEmail($pdo, $order_id)
{
    try {
        // Get order details
        $stmt = $pdo->prepare("
            SELECT o.*, h.name as hotel_name, h.phone as hotel_phone, h.email as hotel_email,
                   u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
            FROM orders o
            JOIN hotels h ON o.hotel_id = h.id
            JOIN users u ON o.customer_id = u.id
            WHERE o.id = ?
        ");
        $stmt->execute([$order_id]);
        $order = $stmt->fetch();

        if (!$order)
            return false;

        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, mi.name as item_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();

        // Build items list
        $items_html = "";
        $items_text = "";
        foreach ($items as $item) {
            $line_total = number_format($item['price'] * $item['quantity']);
            $items_html .= "<tr>
                <td style='padding:8px;border-bottom:1px solid #eee;'>{$item['item_name']}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:center;'>{$item['quantity']}</td>
                <td style='padding:8px;border-bottom:1px solid #eee;text-align:right;'>{$line_total} ETB</td>
            </tr>";
            $items_text .= "  - {$item['item_name']} x{$item['quantity']} = {$line_total} ETB\n";
        }

        $order_ref = 'ORD-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
        $total = number_format($order['total_amount']);
        $date = date('M d, Y h:i A');

        // Beautiful HTML email template
        $html_body = "
        <div style='max-width:600px;margin:0 auto;font-family:Arial,sans-serif;'>
            <div style='background:linear-gradient(135deg,#1B5E20,#2E7D32);padding:30px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:#FFD700;margin:0;font-size:24px;'>🍽️ EthioServe</h1>
                <p style='color:#fff;margin:5px 0 0;'>Order Confirmation</p>
            </div>
            <div style='background:#fff;padding:30px;border:1px solid #e0e0e0;'>
                <h2 style='color:#1B5E20;'>New Order: {$order_ref}</h2>
                <p style='color:#666;'>Date: {$date}</p>
                <hr style='border:1px solid #eee;'>
                <h3 style='color:#333;'>Restaurant: {$order['hotel_name']}</h3>
                <p>📞 Restaurant Phone: <strong>{$order['hotel_phone']}</strong></p>
                <p>👤 Customer: <strong>{$order['customer_name']}</strong></p>
                <p>📱 Customer Phone: <strong>{$order['customer_phone']}</strong></p>
                <p>💳 Payment: <strong>" . ucfirst($order['payment_method']) . "</strong></p>
                <hr style='border:1px solid #eee;'>
                <h3 style='color:#333;'>Order Items:</h3>
                <table style='width:100%;border-collapse:collapse;'>
                    <thead>
                        <tr style='background:#f5f5f5;'>
                            <th style='padding:10px;text-align:left;'>Item</th>
                            <th style='padding:10px;text-align:center;'>Qty</th>
                            <th style='padding:10px;text-align:right;'>Price</th>
                        </tr>
                    </thead>
                    <tbody>{$items_html}</tbody>
                    <tfoot>
                        <tr>
                            <td colspan='2' style='padding:12px;font-weight:bold;font-size:16px;'>Total</td>
                            <td style='padding:12px;font-weight:bold;font-size:16px;text-align:right;color:#1B5E20;'>{$total} ETB</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div style='background:#f5f5f5;padding:20px;text-align:center;border-radius:0 0 10px 10px;border:1px solid #e0e0e0;border-top:0;'>
                <p style='color:#888;font-size:12px;margin:0;'>EthioServe — Taste of Ethiopia 🇪🇹</p>
            </div>
        </div>";

        // Email headers
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: EthioServe <noreply@ethioserve.com>\r\n";

        // Send to customer
        if (!empty($order['customer_email'])) {
            @mail($order['customer_email'], "EthioServe Order Confirmed — {$order_ref}", $html_body, $headers);
        }

        // Send to restaurant
        if (!empty($order['hotel_email'])) {
            @mail($order['hotel_email'], "New Order Received — {$order_ref}", $html_body, $headers);
        }

        // Also send to platform admin email (for temporary tracking)
        @mail('order@ethioserve.com', "New Order — {$order_ref} at {$order['hotel_name']}", $html_body, $headers);

        return true;
    } catch (Exception $e) {
        // Silently fail — email is not critical
        return false;
    }
}


/**
 * Send Job Application Status Update Email
 */
function sendJobApplicationEmail($pdo, $application_id)
{
    try {
        $stmt = $pdo->prepare("
            SELECT ja.*, jl.title as job_title, jc.company_name, u.full_name, u.email
            FROM job_applications ja
            JOIN job_listings jl ON ja.job_id = jl.id
            JOIN job_companies jc ON jl.company_id = jc.id
            JOIN users u ON ja.applicant_id = u.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$application_id]);
        $app = $stmt->fetch();

        if (!$app || empty($app['email']))
            return false;

        $status = ucfirst($app['status']);
        $company = $app['company_name'];
        $job = $app['job_title'];
        $name = $app['full_name'];

        $subject = "Application Update: {$job} at {$company}";

        $message_content = "";
        if ($app['status'] === 'shortlisted') {
            $message_content = "Great news! You have been <strong>shortlisted</strong> for the position. The team will review your profile further.";
        } elseif ($app['status'] === 'interviewed') {
            $date = $app['interview_date'] ? date('M d, Y \a\t h:i A', strtotime($app['interview_date'])) : 'to be decided';
            $message_content = "We would like to invite you for an <strong>interview</strong> for the {$job} position.<br><br><strong>Scheduled Time:</strong> {$date}";
        } elseif ($app['status'] === 'hired') {
            $message_content = "Congratulations! You have been <strong>hired</strong> for the position of {$job}. Our HR team will contact you soon with the next steps.";
        } elseif ($app['status'] === 'rejected') {
            $message_content = "Thank you for your interest in the {$job} position. After careful consideration, we will not be moving forward with your application at this time.";
        } else {
            $message_content = "The status of your application for {$job} has been updated to <strong>{$status}</strong>.";
        }

        $html_body = "
        <div style='max-width:600px;margin:20px auto;font-family:Arial,sans-serif;color:#333;line-height:1.6;'>
            <div style='background:#1B5E20;padding:30px;text-align:center;border-radius:10px 10px 0 0;'>
                <h1 style='color:#FFB300;margin:0;'>EthioServe Jobs</h1>
            </div>
            <div style='background:#fff;padding:40px;border:1px solid #eee;border-top:none;'>
                <h2>Hello {$name},</h2>
                <p>{$message_content}</p>
                <p style='margin-top:30px;'>Best regards,<br>The Recruitment Team at <strong>{$company}</strong></p>
                <hr style='border:none;border-top:1px solid #eee;margin:30px 0;'>
                <p style='font-size:12px;color:#888;'>This is an automated message from EthioServe Platform.</p>
            </div>
        </div>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: EthioServe Jobs <noreply@ethioserve.com>\r\n";

        return @mail($app['email'], $subject, $html_body, $headers);
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Send Payment Successful Notification Email
 * Sends a premium confirmation receipt to Customer, Provider (Hotel/Restaurant), and Admin
 */
function sendPaymentConfirmationEmail($pdo, $id, $type = 'order')
{
    try {
        if ($type === 'order') {
            // Get order details
            $stmt = $pdo->prepare("
                SELECT o.*, h.name as provider_name, h.email as provider_email, h.phone as provider_phone,
                       u.full_name as customer_name, u.email as customer_email, u.phone as customer_phone
                FROM orders o
                JOIN hotels h ON o.hotel_id = h.id
                JOIN users u ON o.customer_id = u.id
                WHERE o.id = ?
            ");
            $ref_prefix = "ORD";
        } else {
            // Get booking details
            $stmt = $pdo->prepare("
                SELECT b.id, b.status, b.guest_email as customer_email, b.guest_name as customer_name,
                       h.name as provider_name, h.email as provider_email, 
                       u.email as auth_email, u.full_name as auth_name
                FROM bookings b
                JOIN hotels h ON b.hotel_id = h.id
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE b.id = ?
            ");
            $ref_prefix = "BOK";
        }

        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (!$data)
            return false;

        $customer_email = $data['customer_email'] ?? $data['auth_email'] ?? '';
        $customer_name = $data['customer_name'] ?? $data['auth_name'] ?? 'Valued Customer';
        $provider_email = $data['provider_email'] ?? 'order@ethioserve.com';
        $provider_name = $data['provider_name'];
        $ref_no = $ref_prefix . '-' . str_pad($id, 5, '0', STR_PAD_LEFT);
        $date = date('M d, Y h:i A');

        $html_body = "
        <div style='max-width:600px;margin:20px auto;font-family:\"Segoe UI\",Tahoma,Geneva,Verdana,sans-serif;color:#333;line-height:1.6;'>
            <div style='background:#1B5E20;padding:40px;text-align:center;border-radius:15px 15px 0 0;'>
                <h1 style='color:#FFB300;margin:0;font-size:28px;letter-spacing:1px;'>ETHIOSERVE</h1>
                <p style='color:#fff;margin:10px 0 0;font-size:16px;opacity:0.9;'>Payment Confirmation Receipt</p>
            </div>
            <div style='background:#fff;padding:40px;border:1px solid #eee;border-top:none;'>
                <div style='text-align:center;margin-bottom:30px;'>
                    <div style='width:60px;height:60px;background:#4CAF50;color:#fff;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:30px;line-height:60px;margin-bottom:15px;'>✓</div>
                    <h2 style='margin:0;color:#1B5E20;'>Payment Successful!</h2>
                    <p style='color:#666;'>Thank you for your business, {$customer_name}.</p>
                </div>
                
                <table style='width:100%;margin-bottom:30px;border-collapse:collapse;'>
                    <tr>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;color:#888;'>Reference Number</td>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;text-align:right;font-weight:bold;'>{$ref_no}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;color:#888;'>Service Provider</td>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;text-align:right;font-weight:bold;'>{$provider_name}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;color:#888;'>Date & Time</td>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;text-align:right;'>{$date}</td>
                    </tr>
                    <tr>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;color:#888;'>Status</td>
                        <td style='padding:10px 0;border-bottom:1px solid #f5f5f5;text-align:right;'><span style='background:#E8F5E9;color:#2E7D32;padding:4px 12px;border-radius:20px;font-size:12px;font-weight:bold;'>PAID</span></td>
                    </tr>
                </table>

                <div style='background:#F9FAFB;padding:25px;border-radius:12px;text-align:center;'>
                    <p style='margin:0;color:#666;font-size:14px;'>Your transaction has been processed securely via Chapa.</p>
                    <p style='margin:5px 0 0;font-size:13px;color:#999;'>For support, please contact us at support@ethioserve.com</p>
                </div>
            </div>
            <div style='padding:20px;text-align:center;font-size:12px;color:#999;'>
                <p>© " . date('Y') . " EthioServe. All rights reserved.<br>Addis Ababa, Ethiopia</p>
                <div style='margin-top:10px;'>
                    <a href='#' style='color:#1B5E20;text-decoration:none;margin:0 10px;'>Privacy Policy</a>
                    <a href='#' style='color:#1B5E20;text-decoration:none;margin:0 10px;'>Terms of Service</a>
                </div>
            </div>
        </div>";

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-type: text/html; charset=UTF-8\r\n";
        $headers .= "From: EthioServe Payments <payments@ethioserve.com>\r\n";

        $subject = "Payment Successful: Receipt for {$ref_no}";

        // 1. Send to Customer
        if (!empty($customer_email)) {
            @mail($customer_email, $subject, $html_body, $headers);
        }

        // 2. Send to Provider (Restaurant/Hotel)
        if (!empty($provider_email)) {
            @mail($provider_email, "Funds Received: Payment Confirmed for {$ref_no}", $html_body, $headers);
        }

        // 3. Send to Admin
        @mail('admin@ethioserve.com', "System Alert: Payment Success {$ref_no}", $html_body, $headers);

        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Generate Chapa payment checkout URL
 * Uses Chapa.co API for Ethiopian online payments
 * Docs: https://developer.chapa.co
 */
function initiateChapaPayment($order_id, $amount, $email, $first_name, $last_name, $phone, $callback_url, $return_url, $type = 'ORD')
{
    // Chapa API configuration (REPLACE WITH YOUR REAL KEY)
    $chapa_secret_key = 'CHASECK_TEST-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // TODO: Replace with real Chapa secret key
    $tx_ref = 'ETHIOSERVE-' . $type . '-' . $order_id . '-' . time();

    $data = [
        'amount' => $amount,
        'currency' => 'ETB',
        'email' => $email,
        'first_name' => $first_name,
        'last_name' => $last_name,
        'phone_number' => $phone,
        'tx_ref' => $tx_ref,
        'callback_url' => $callback_url,
        'return_url' => $return_url,
        'customization' => [
            'title' => 'EthioServe Order #' . str_pad($order_id, 5, '0', STR_PAD_LEFT),
            'description' => 'Food order payment'
        ]
    ];

    $ch = curl_init('https://api.chapa.co/v1/transaction/initialize');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $chapa_secret_key,
            'Content-Type: application/json'
        ]
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $result = json_decode($response, true);

    if (isset($result['status']) && $result['status'] === 'success') {
        return [
            'success' => true,
            'checkout_url' => $result['data']['checkout_url'],
            'tx_ref' => $tx_ref
        ];
    }

    return [
        'success' => false,
        'message' => $result['message'] ?? 'Failed to initialize Chapa payment'
    ];
}

/**
 * Time ago helper function
 */
function time_ago($timestamp)
{
    $time_ago = strtotime($timestamp);
    $current_time = time();
    $time_difference = $current_time - $time_ago;
    $seconds = $time_difference;
    $minutes = round($seconds / 60);           // value 60 is seconds  
    $hours = round($seconds / 3600);           //value 3600 is 60 minutes * 60 sec  
    $days = round($seconds / 86400);          //86400 = 24 * 60 * 60;  
    $weeks = round($seconds / 604800);          // 7*24*60*60;  
    $months = round($seconds / 2629440);     //((365+365+365+365+366)/5/12)*24*60*60  
    $years = round($seconds / 31553280);     //(365+365+365+365+366)/5 * 24 * 60 * 60  
    if ($seconds <= 60) {
        return "Just Now";
    } else if ($minutes <= 60) {
        if ($minutes == 1) {
            return "one minute ago";
        } else {
            return "$minutes minutes ago";
        }
    } else if ($hours <= 24) {
        if ($hours == 1) {
            return "an hour ago";
        } else {
            return "$hours hrs ago";
        }
    } else if ($days <= 7) {
        if ($days == 1) {
            return "yesterday";
        } else {
            return "$days days ago";
        }
    } else if ($weeks <= 4.3) //4.3 == 52/12  
    {
        if ($weeks == 1) {
            return "a week ago";
        } else {
            return "$weeks weeks ago";
        }
    } else if ($months <= 12) {
        if ($months == 1) {
            return "a month ago";
        } else {
            return "$months months ago";
        }
    } else {
        if ($years == 1) {
            return "one year ago";
        } else {
            return "$years years ago";
        }
    }
}

/**
 * Get unread message count for current user
 */
function getUnreadMessageCount()
{
    global $pdo;
    if (!isLoggedIn())
        return 0;

    $user_id = getCurrentUserId();
    $role = getCurrentUserRole();
    $total = 0;

    // 1. Doctor Messages
    try {
        if ($role === 'doctor') {
            // Check for unread messages SENT BY customers TO this doctor
            $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $provider = $stmt->fetch();
            if ($provider) {
                $s = $pdo->prepare("SELECT COUNT(*) FROM doctor_messages WHERE provider_id = ? AND sender_type = 'customer' AND is_read = 0");
                $s->execute([$provider['id']]);
                $total += (int) $s->fetchColumn();
            }
        } else {
            // Check for unread messages SENT BY doctors TO this customer
            $s = $pdo->prepare("SELECT COUNT(*) FROM doctor_messages WHERE customer_id = ? AND sender_type = 'doctor' AND is_read = 0");
            $s->execute([$user_id]);
            $total += (int) $s->fetchColumn();
        }
    } catch (Exception $e) {
    }

    // 2. Job Messages
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM job_messages WHERE receiver_id = ? AND is_read = 0");
        $s->execute([$user_id]);
        $total += (int) $s->fetchColumn();
    } catch (Exception $e) {
    }

    // 3. Dating Messages
    try {
        $s = $pdo->prepare("SELECT COUNT(*) FROM dating_messages WHERE receiver_id = ? AND is_read = 0");
        $s->execute([$user_id]);
        $total += (int) $s->fetchColumn();
    } catch (Exception $e) {
    }

    return $total;
}

