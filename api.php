<?php
/**
 * API Endpoint for EthioServe
 * Handles AJAX requests for cart, orders, bookings, and other operations
 */

require_once 'includes/functions.php';
require_once 'includes/db.php';

// Set JSON header
header('Content-Type: application/json');

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Verify CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $csrf_token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($csrf_token)) {
        jsonResponse(['success' => false, 'message' => 'Invalid security token'], 403);
    }
}

switch ($action) {
    // ==================== CART OPERATIONS ====================

    case 'add_to_cart':
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));

        // Fetch item details from database
        $stmt = $pdo->prepare("
            SELECT m.*, h.id as hotel_id, h.name as hotel_name 
            FROM menu_items m 
            JOIN hotels h ON m.hotel_id = h.id 
            WHERE m.id = ? AND m.is_available = 1
        ");
        $stmt->execute([$item_id]);
        $item = $stmt->fetch();

        if (!$item) {
            jsonResponse(['success' => false, 'message' => 'Item not found or unavailable']);
        }

        $result = addToCart(
            $item['id'],
            $item['name'],
            $item['price'],
            $quantity,
            $item['hotel_id'],
            $item['hotel_name'],
            $item['image_url']
        );

        jsonResponse($result);
        break;

    case 'remove_from_cart':
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $result = removeFromCart($item_id);
        $result['cart_count'] = getCartCount();
        $result['subtotal'] = getCartSubtotal();
        jsonResponse($result);
        break;

    case 'update_cart_quantity':
        $item_id = (int) ($_POST['item_id'] ?? 0);
        $quantity = max(1, (int) ($_POST['quantity'] ?? 1));
        $result = updateCartQuantity($item_id, $quantity);
        $result['cart_count'] = getCartCount();
        $result['subtotal'] = getCartSubtotal();
        jsonResponse($result);
        break;

    case 'get_cart':
        $items = getCartItems();
        $hotel = getCartHotel();
        jsonResponse([
            'success' => true,
            'items' => $items,
            'hotel' => $hotel,
            'count' => getCartCount(),
            'subtotal' => getCartSubtotal()
        ]);
        break;

    case 'clear_cart':
        clearCart();
        jsonResponse(['success' => true, 'message' => 'Cart cleared']);
        break;

    // ==================== ORDER OPERATIONS ====================

    case 'place_order':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login to place an order', 'require_login' => true]);
        }

        $items = getCartItems();
        if (empty($items)) {
            jsonResponse(['success' => false, 'message' => 'Your cart is empty']);
        }

        $hotel = getCartHotel();
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $delivery_address = sanitize($_POST['delivery_address'] ?? '');
        $broker_code = sanitize($_POST['broker_code'] ?? '');

        if (empty($delivery_address)) {
            jsonResponse(['success' => false, 'message' => 'Please enter delivery address']);
        }

        try {
            $pdo->beginTransaction();

            // Calculate total
            $total_amount = getCartSubtotal();
            $delivery_fee = 150; // Fixed delivery fee
            $grand_total = $total_amount + $delivery_fee;

            // Create order
            $stmt = $pdo->prepare("
                INSERT INTO orders (customer_id, hotel_id, total_amount, status, payment_method, payment_status, created_at)
                VALUES (?, ?, ?, 'pending', ?, 'pending', NOW())
            ");
            $stmt->execute([
                getCurrentUserId(),
                $hotel['hotel_id'],
                $grand_total,
                $payment_method
            ]);
            $order_id = $pdo->lastInsertId();

            // Add order items
            $stmt = $pdo->prepare("
                INSERT INTO order_items (order_id, menu_item_id, quantity, price)
                VALUES (?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $stmt->execute([
                    $order_id,
                    $item['id'],
                    $item['quantity'],
                    $item['price']
                ]);
            }

            // Handle broker referral
            if (!empty($broker_code)) {
                $stmt = $pdo->prepare("SELECT id FROM brokers WHERE referral_code = ?");
                $stmt->execute([$broker_code]);
                $broker = $stmt->fetch();

                if ($broker) {
                    $commission = calculateCommission($total_amount);
                    $stmt = $pdo->prepare("
                        INSERT INTO referrals (broker_id, order_id, commission_amount, status, created_at)
                        VALUES (?, ?, ?, 'pending', NOW())
                    ");
                    $stmt->execute([$broker['id'], $order_id, $commission]);
                }
            }

            $pdo->commit();
            clearCart();

            // Send email notification to customer and restaurant
            sendOrderEmail($pdo, $order_id);

            jsonResponse([
                'success' => true,
                'message' => 'Order placed successfully! Confirmation email sent.',
                'order_id' => $order_id
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            jsonResponse(['success' => false, 'message' => 'Failed to place order. Please try again.']);
        }
        break;

    case 'get_order_status':
        $order_id = (int) ($_GET['order_id'] ?? 0);

        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $stmt = $pdo->prepare("
            SELECT o.*, h.name as hotel_name, h.location as hotel_location
            FROM orders o
            JOIN hotels h ON o.hotel_id = h.id
            WHERE o.id = ? AND o.customer_id = ?
        ");
        $stmt->execute([$order_id, getCurrentUserId()]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order not found']);
        }

        // Get order items
        $stmt = $pdo->prepare("
            SELECT oi.*, mi.name as item_name
            FROM order_items oi
            JOIN menu_items mi ON oi.menu_item_id = mi.id
            WHERE oi.order_id = ?
        ");
        $stmt->execute([$order_id]);
        $items = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'order' => $order,
            'items' => $items
        ]);
        break;

    case 'cancel_order':
        $order_id = (int) ($_POST['order_id'] ?? 0);

        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        // Check if order belongs to user and is cancellable
        $stmt = $pdo->prepare("
            SELECT * FROM orders 
            WHERE id = ? AND customer_id = ? AND status IN ('pending', 'preparing')
        ");
        $stmt->execute([$order_id, getCurrentUserId()]);
        $order = $stmt->fetch();

        if (!$order) {
            jsonResponse(['success' => false, 'message' => 'Order cannot be cancelled']);
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $stmt->execute([$order_id]);

        jsonResponse(['success' => true, 'message' => 'Order cancelled successfully']);
        break;

    case 'update_payment_status':
        $order_id = (int) ($_POST['order_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'pending');

        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $stmt = $pdo->prepare("UPDATE orders SET payment_status = ? WHERE id = ? AND customer_id = ?");
        $stmt->execute([$status, $order_id, getCurrentUserId()]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Payment status updated']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to update payment status']);
        }
        break;

    case 'update_flight_payment':
        $booking_id = (int) ($_POST['booking_id'] ?? 0);
        $status = sanitize($_POST['status'] ?? 'paid');

        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $stmt = $pdo->prepare("UPDATE flight_bookings SET payment_status = ?, status = 'confirmed' WHERE id = ? AND customer_id = ?");
        $stmt->execute([$status, $booking_id, getCurrentUserId()]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Flight payment confirmed']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to update flight payment']);
        }
        break;

    // ==================== BOOKING OPERATIONS ====================

    case 'place_booking':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login to make a booking', 'require_login' => true]);
        }

        $hotel_id = (int) ($_POST['hotel_id'] ?? 0);
        $booking_type = sanitize($_POST['booking_type'] ?? 'table');
        $booking_date = sanitize($_POST['booking_date'] ?? '');
        $booking_time = sanitize($_POST['booking_time'] ?? '');
        $num_guests = (int) ($_POST['num_guests'] ?? 1);

        // Validate inputs
        if (!$hotel_id || !validateDate($booking_date) || !validateTime($booking_time)) {
            jsonResponse(['success' => false, 'message' => 'Please fill all fields correctly']);
        }

        // Check if hotel exists and is approved
        $stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ? AND status = 'approved'");
        $stmt->execute([$hotel_id]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Invalid hotel selected']);
        }

        try {
            $stmt = $pdo->prepare("
                INSERT INTO bookings (customer_id, hotel_id, booking_date, booking_time, booking_type, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())
            ");
            $stmt->execute([
                getCurrentUserId(),
                $hotel_id,
                $booking_date,
                $booking_time,
                $booking_type
            ]);
            $booking_id = $pdo->lastInsertId();

            jsonResponse([
                'success' => true,
                'message' => 'Booking request submitted successfully!',
                'booking_id' => $booking_id
            ]);

        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => 'Failed to create booking. Please try again.']);
        }
        break;

    case 'get_user_bookings':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $stmt = $pdo->prepare("
            SELECT b.*, h.name as hotel_name, h.location as hotel_location
            FROM bookings b
            JOIN hotels h ON b.hotel_id = h.id
            WHERE b.customer_id = ?
            ORDER BY b.created_at DESC
        ");
        $stmt->execute([getCurrentUserId()]);
        $bookings = $stmt->fetchAll();

        jsonResponse(['success' => true, 'bookings' => $bookings]);
        break;

    // ==================== USER OPERATIONS ====================

    case 'get_user_orders':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $stmt = $pdo->prepare("
            SELECT o.*, h.name as hotel_name
            FROM orders o
            JOIN hotels h ON o.hotel_id = h.id
            WHERE o.customer_id = ?
            ORDER BY o.created_at DESC
            LIMIT 20
        ");
        $stmt->execute([getCurrentUserId()]);
        $orders = $stmt->fetchAll();

        jsonResponse(['success' => true, 'orders' => $orders]);
        break;

    // ==================== HOTEL OPERATIONS ====================

    case 'update_order_status':
        if (!isLoggedIn() || !hasRole('hotel')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $order_id = (int) ($_POST['order_id'] ?? 0);
        $new_status = sanitize($_POST['status'] ?? '');

        $valid_statuses = ['pending', 'preparing', 'on_delivery', 'delivered', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            jsonResponse(['success' => false, 'message' => 'Invalid status']);
        }

        // Verify order belongs to hotel
        $stmt = $pdo->prepare("
            SELECT o.id FROM orders o
            JOIN hotels h ON o.hotel_id = h.id
            WHERE o.id = ? AND h.user_id = ?
        ");
        $stmt->execute([$order_id, getCurrentUserId()]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Order not found']);
        }

        $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $order_id]);

        jsonResponse(['success' => true, 'message' => 'Order status updated']);
        break;

    case 'update_booking_status':
        if (!isLoggedIn() || !hasRole('hotel')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $booking_id = (int) ($_POST['booking_id'] ?? 0);
        $new_status = sanitize($_POST['status'] ?? '');

        $valid_statuses = ['pending', 'approved', 'cancelled'];
        if (!in_array($new_status, $valid_statuses)) {
            jsonResponse(['success' => false, 'message' => 'Invalid status']);
        }

        // Verify booking belongs to hotel
        $stmt = $pdo->prepare("
            SELECT b.id FROM bookings b
            JOIN hotels h ON b.hotel_id = h.id
            WHERE b.id = ? AND h.user_id = ?
        ");
        $stmt->execute([$booking_id, getCurrentUserId()]);
        if (!$stmt->fetch()) {
            jsonResponse(['success' => false, 'message' => 'Booking not found']);
        }

        $stmt = $pdo->prepare("UPDATE bookings SET status = ? WHERE id = ?");
        $stmt->execute([$new_status, $booking_id]);

        jsonResponse(['success' => true, 'message' => 'Booking status updated']);
        break;

    case 'get_hotel_notifications':
        if (!isLoggedIn() || !hasRole('hotel')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get hotel ID
        $stmt = $pdo->prepare("SELECT id FROM hotels WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $hotel = $stmt->fetch();

        if (!$hotel) {
            jsonResponse(['success' => false, 'message' => 'Hotel not found']);
        }

        $hotel_id = $hotel['id'];
        $last_order_id = (int) ($_GET['last_order_id'] ?? 0);
        $last_booking_id = (int) ($_GET['last_booking_id'] ?? 0);

        // Check for new pending orders
        $stmt = $pdo->prepare("
            SELECT o.*, u.full_name as customer_name 
            FROM orders o
            JOIN users u ON o.customer_id = u.id
            WHERE o.hotel_id = ? AND o.status = 'pending' AND o.id > ?
            ORDER BY o.id DESC
        ");
        $stmt->execute([$hotel_id, $last_order_id]);
        $new_orders = $stmt->fetchAll();

        // Check for new pending bookings
        $stmt = $pdo->prepare("
            SELECT b.*, u.full_name as customer_name 
            FROM bookings b
            JOIN users u ON b.customer_id = u.id
            WHERE b.hotel_id = ? AND b.status = 'pending' AND b.id > ?
            ORDER BY b.id DESC
        ");
        $stmt->execute([$hotel_id, $last_booking_id]);
        $new_bookings = $stmt->fetchAll();

        // Total pending counts for badges
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE hotel_id = ? AND status = 'pending'");
        $stmt->execute([$hotel_id]);
        $all_pending_orders = (int) $stmt->fetchColumn();

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE hotel_id = ? AND status = 'pending'");
        $stmt->execute([$hotel_id]);
        $all_pending_bookings = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_orders' => $new_orders,
            'new_bookings' => $new_bookings,
            'total_pending_orders' => $all_pending_orders,
            'total_pending_bookings' => $all_pending_bookings
        ]);
        break;

    case 'get_employer_notifications':
        if (!isLoggedIn() || !hasRole('employer')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get company ID
        $stmt = $pdo->prepare("SELECT id FROM job_companies WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $company = $stmt->fetch();

        if (!$company) {
            jsonResponse(['success' => false, 'message' => 'Company not found']);
        }

        $company_id = $company['id'];
        $last_app_id = (int) ($_GET['last_app_id'] ?? 0);

        // Check for new pending applications
        $stmt = $pdo->prepare("
            SELECT ja.*, u.full_name as applicant_name, jl.title as job_title 
            FROM job_applications ja
            JOIN users u ON ja.applicant_id = u.id
            JOIN job_listings jl ON ja.job_id = jl.id
            WHERE jl.company_id = ? AND ja.status = 'pending' AND ja.id > ?
            ORDER BY ja.id DESC
        ");
        $stmt->execute([$company_id, $last_app_id]);
        $new_apps = $stmt->fetchAll();

        // Check for unread messages
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([getCurrentUserId()]);
        $unread_messages = (int) $stmt->fetchColumn();

        // Total pending counts for badges
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM job_applications ja JOIN job_listings jl ON ja.job_id = jl.id WHERE jl.company_id = ? AND ja.status = 'pending'");
        $stmt->execute([$company_id]);
        $all_pending_apps = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_applications' => $new_apps,
            'unread_messages' => $unread_messages,
            'total_pending_apps' => $all_pending_apps
        ]);
        break;

    case 'get_restaurant_notifications':
        if (!isLoggedIn() || !hasRole('restaurant')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get restaurant ID
        $stmt = $pdo->prepare("SELECT id FROM restaurants WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $restaurant = $stmt->fetch();

        if (!$restaurant) {
            jsonResponse(['success' => false, 'message' => 'Restaurant not found']);
        }

        $restaurant_id = $restaurant['id'];
        $last_order_id = (int) ($_GET['last_order_id'] ?? 0);

        // Check for new pending orders
        $stmt = $pdo->prepare("
            SELECT ro.*, u.full_name as customer_name 
            FROM restaurant_orders ro
            JOIN users u ON ro.customer_id = u.id
            WHERE ro.restaurant_id = ? AND ro.status = 'pending' AND ro.id > ?
            ORDER BY ro.id DESC
        ");
        $stmt->execute([$restaurant_id, $last_order_id]);
        $new_orders = $stmt->fetchAll();

        // Total pending counts for badges
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM restaurant_orders WHERE restaurant_id = ? AND status = 'pending'");
        $stmt->execute([$restaurant_id]);
        $all_pending_orders = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_orders' => $new_orders,
            'total_pending_orders' => $all_pending_orders
        ]);
        break;

    case 'get_taxi_notifications':
        if (!isLoggedIn() || !hasRole('taxi')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get company ID
        $stmt = $pdo->prepare("SELECT id FROM taxi_companies WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $company = $stmt->fetch();

        if (!$company) {
            jsonResponse(['success' => false, 'message' => 'Taxi company not found']);
        }

        $company_id = $company['id'];
        $last_ride_id = (int) ($_GET['last_ride_id'] ?? 0);

        // Check for new ride requests
        $stmt = $pdo->prepare("
            SELECT tr.*, u.full_name as customer_name 
            FROM taxi_rides tr
            JOIN users u ON tr.customer_id = u.id
            WHERE tr.taxi_company_id = ? AND tr.status = 'requested' AND tr.id > ?
            ORDER BY tr.id DESC
        ");
        $stmt->execute([$company_id, $last_ride_id]);
        $new_rides = $stmt->fetchAll();

        // Total pending counts for badges
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM taxi_rides WHERE taxi_company_id = ? AND status = 'requested'");
        $stmt->execute([$company_id]);
        $all_pending_rides = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_rides' => $new_rides,
            'total_pending_rides' => $all_pending_rides
        ]);
        break;

    case 'get_transport_notifications':
        if (!isLoggedIn() || !hasRole('transport')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get company ID
        $stmt = $pdo->prepare("SELECT id FROM transport_companies WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $company = $stmt->fetch();

        if (!$company) {
            jsonResponse(['success' => false, 'message' => 'Transport company not found']);
        }

        $company_id = $company['id'];
        $last_booking_id = (int) ($_GET['last_booking_id'] ?? 0);

        // Check for new bus bookings
        $stmt = $pdo->prepare("
            SELECT bb.*, u.full_name as customer_name 
            FROM bus_bookings bb
            JOIN users u ON bb.customer_id = u.id
            JOIN schedules s ON bb.schedule_id = s.id
            JOIN buses b ON s.bus_id = b.id
            WHERE b.company_id = ? AND bb.status = 'pending' AND bb.id > ?
            ORDER BY bb.id DESC
        ");
        $stmt->execute([$company_id, $last_booking_id]);
        $new_bookings = $stmt->fetchAll();

        // Total pending counts for badges
        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM bus_bookings bb
            JOIN schedules s ON bb.schedule_id = s.id
            JOIN buses b ON s.bus_id = b.id
            WHERE b.company_id = ? AND bb.status = 'pending'
        ");
        $stmt->execute([$company_id]);
        $all_pending_bookings = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_bookings' => $new_bookings,
            'total_pending_bookings' => $all_pending_bookings
        ]);
        break;

    case 'get_broker_notifications':
        if (!isLoggedIn() || !hasRole('broker')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get broker ID
        $stmt = $pdo->prepare("SELECT id FROM brokers WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $broker = $stmt->fetch();

        $broker_id = $broker['id'];
        $user_id = getCurrentUserId();
        $last_ref_id = (int) ($_GET['last_ref_id'] ?? 0);
        $last_request_id = (int) ($_GET['last_request_id'] ?? 0);

        // Check for new referrals (traditional broker role)
        $stmt = $pdo->prepare("
            SELECT r.*, u.full_name as customer_name, o.total_amount 
            FROM referrals r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON o.customer_id = u.id
            WHERE r.broker_id = ? AND r.id > ?
            ORDER BY r.id DESC
        ");
        $stmt->execute([$broker_id, $last_ref_id]);
        $new_refs = $stmt->fetchAll();

        // Check for new rental requests (new Owner role)
        $stmt = $pdo->prepare("
            SELECT rr.*, l.title as listing_title, u.full_name as customer_name_db
            FROM rental_requests rr
            JOIN listings l ON rr.listing_id = l.id
            LEFT JOIN users u ON rr.customer_id = u.id
            WHERE l.user_id = ? AND rr.status = 'pending' AND rr.id > ?
            ORDER BY rr.id DESC
        ");
        $stmt->execute([$user_id, $last_request_id]);
        $new_requests = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'new_referrals' => $new_refs,
            'new_requests' => $new_requests,
            'total_new_refs' => count($new_refs),
            'total_new_requests' => count($new_requests)
        ]);
        break;

    case 'get_doctor_notifications':
        if (!isLoggedIn() || !hasRole('doctor')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get provider ID
        $stmt = $pdo->prepare("SELECT id FROM health_providers WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $doctor = $stmt->fetch();

        if (!$doctor) {
            jsonResponse(['success' => false, 'message' => 'Doctor not found']);
        }

        $provider_id = $doctor['id'];
        $last_appt_id = (int) ($_GET['last_appt_id'] ?? 0);

        // Check for new pending appointments
        $stmt = $pdo->prepare("
            SELECT a.*, u.full_name as patient_name 
            FROM health_appointments a
            JOIN users u ON a.user_id = u.id
            WHERE a.provider_id = ? AND a.status = 'pending' AND a.id > ?
            ORDER BY a.id DESC
        ");
        $stmt->execute([$provider_id, $last_appt_id]);
        $new_appts = $stmt->fetchAll();

        // Check for unread messages
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM doctor_messages WHERE provider_id = ? AND is_read = 0 AND sender_type = 'customer'");
        $stmt->execute([$provider_id]);
        $unread_messages = (int) $stmt->fetchColumn();

        // Total pending apps
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM health_appointments WHERE provider_id = ? AND status = 'pending'");
        $stmt->execute([$provider_id]);
        $total_pending_appts = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_appointments' => $new_appts,
            'unread_messages' => $unread_messages,
            'total_pending_appts' => $total_pending_appts
        ]);
        break;

    case 'get_home_pro_notifications':
        if (!isLoggedIn() || !hasRole('home_pro')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        // Get provider ID
        $stmt = $pdo->prepare("SELECT id FROM home_service_providers WHERE user_id = ?");
        $stmt->execute([getCurrentUserId()]);
        $pro = $stmt->fetch();

        if (!$pro) {
            jsonResponse(['success' => false, 'message' => 'Home pro not found']);
        }

        $provider_id = $pro['id'];
        $last_booking_id = (int) ($_GET['last_booking_id'] ?? 0);

        // Check for new pending bookings
        $stmt = $pdo->prepare("
            SELECT b.*, u.full_name as customer_name 
            FROM home_service_bookings b
            JOIN users u ON b.customer_id = u.id
            WHERE b.provider_id = ? AND b.status = 'pending' AND b.id > ?
            ORDER BY b.id DESC
        ");
        $stmt->execute([$provider_id, $last_booking_id]);
        $new_bookings = $stmt->fetchAll();

        jsonResponse([
            'success' => true,
            'new_bookings' => $new_bookings,
            'total_pending_bookings' => count($new_bookings)
        ]);
        break;

    case 'get_admin_notifications':
        if (!isLoggedIn() || !hasRole('admin')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $last_hotel_id = (int) ($_GET['last_hotel_id'] ?? 0);
        $last_rest_id = (int) ($_GET['last_rest_id'] ?? 0);

        // Check for new pending hotels
        $stmt = $pdo->prepare("SELECT id, name FROM hotels WHERE status = 'pending' AND id > ? ORDER BY id DESC");
        $stmt->execute([$last_hotel_id]);
        $hotels = $stmt->fetchAll();

        // Check for new pending restaurants
        $stmt = $pdo->prepare("SELECT id, name FROM restaurants WHERE status = 'pending' AND id > ? ORDER BY id DESC");
        $stmt->execute([$last_rest_id]);
        $rests = $stmt->fetchAll();

        // Total counts for badges
        $stmt = $pdo->query("SELECT COUNT(*) FROM hotels WHERE status = 'pending'");
        $total_hotels = (int) $stmt->fetchColumn();
        $stmt = $pdo->query("SELECT COUNT(*) FROM restaurants WHERE status = 'pending'");
        $total_rests = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'new_hotels' => $hotels,
            'new_restaurants' => $rests,
            'total_pending_hotels' => $total_hotels,
            'total_pending_rests' => $total_rests
        ]);
        break;

    case 'get_customer_notifications':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $user_id = getCurrentUserId();
        $last_notif_id = (int) ($_GET['last_notif_id'] ?? 0);

        // Check for new bus booking notifications
        $stmt = $pdo->prepare("
            SELECT * FROM booking_notifications 
            WHERE user_id = ? AND id > ? 
            ORDER BY id DESC
        ");
        $stmt->execute([$user_id, $last_notif_id]);
        $notifs = $stmt->fetchAll();

        // Check for unread personal messages (general)
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $unread_msgs = (int) $stmt->fetchColumn();

        jsonResponse([
            'success' => true,
            'notifications' => $notifs,
            'unread_messages' => $unread_msgs,
            'total' => count($notifs)
        ]);
        break;

    // ==================== ADMIN OPERATIONS ====================

    case 'approve_hotel':
        if (!isLoggedIn() || !hasRole('admin')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $hotel_id = (int) ($_POST['hotel_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE hotels SET status = 'approved' WHERE id = ?");
        $stmt->execute([$hotel_id]);

        jsonResponse(['success' => true, 'message' => 'Hotel approved']);
        break;

    case 'reject_hotel':
        if (!isLoggedIn() || !hasRole('admin')) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $hotel_id = (int) ($_POST['hotel_id'] ?? 0);
        $stmt = $pdo->prepare("UPDATE hotels SET status = 'rejected' WHERE id = ?");
        $stmt->execute([$hotel_id]);

        jsonResponse(['success' => true, 'message' => 'Hotel rejected']);
        break;

    // ==================== CHAPA PAYMENT ====================

    case 'initiate_chapa_payment':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Please login']);
        }

        $order_id = (int) ($_POST['order_id'] ?? 0);
        $payment_type = sanitize($_POST['payment_type'] ?? 'order');

        // Get user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([getCurrentUserId()]);
        $user = $stmt->fetch();

        if ($payment_type === 'order') {
            $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = ? AND customer_id = ?");
            $stmt->execute([$order_id, getCurrentUserId()]);
            $order = $stmt->fetch();

            if (!$order) {
                jsonResponse(['success' => false, 'message' => 'Order not found']);
            }

            $amount = $order['total_amount'];
        } else {
            $amount = (float) ($_POST['amount'] ?? 0);
        }

        $base_url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $callback_url = $base_url . BASE_URL . '/api.php?action=chapa_callback';
        $return_url = $base_url . BASE_URL . '/customer/track_order.php?id=' . $order_id;

        $name_parts = explode(' ', $user['full_name'] ?? 'Customer User', 2);
        $first_name = $name_parts[0];
        $last_name = $name_parts[1] ?? '';

        $type_prefix = ($payment_type === 'booking') ? 'BOK' : 'ORD';
        $result = initiateChapaPayment(
            $order_id,
            $amount,
            $user['email'],
            $first_name,
            $last_name,
            $user['phone'] ?? '',
            $callback_url,
            $return_url,
            $type_prefix
        );

        jsonResponse($result);
        break;

    case 'get_chat_messages':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $app_id = (int) ($_GET['application_id'] ?? 0);
        $last_id = (int) ($_GET['last_id'] ?? 0);
        $user_id = getCurrentUserId();

        if (!$app_id) {
            jsonResponse(['success' => false, 'message' => 'Invalid application ID']);
        }

        // Security check
        $stmt = $pdo->prepare("
            SELECT applicant_id, c.user_id as employer_id 
            FROM job_applications ja
            JOIN job_listings jl ON ja.job_id = jl.id
            JOIN job_companies c ON jl.company_id = c.id
            WHERE ja.id = ?
        ");
        $stmt->execute([$app_id]);
        $app = $stmt->fetch();

        if (!$app || ($user_id != $app['applicant_id'] && $user_id != $app['employer_id'])) {
            jsonResponse(['success' => false, 'message' => 'Access denied']);
        }

        // Fetch new messages with replied-to content
        $stmt = $pdo->prepare("
            SELECT m.*, rm.message as replied_message 
            FROM job_messages m
            LEFT JOIN job_messages rm ON m.reply_to_id = rm.id
            WHERE m.application_id = ? AND m.id > ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$app_id, $last_id]);
        $messages = $stmt->fetchAll();

        // Mark as read in background
        if (!empty($messages)) {
            $pdo->prepare("UPDATE job_messages SET is_read = 1 WHERE application_id = ? AND receiver_id = ? AND is_read = 0")
                ->execute([$app_id, $user_id]);
        }

        jsonResponse(['success' => true, 'messages' => $messages]);
        break;

    case 'delete_chat_message':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $msg_id = (int) ($_POST['message_id'] ?? 0);
        $user_id = getCurrentUserId();

        if (!$msg_id) {
            jsonResponse(['success' => false, 'message' => 'Invalid message ID']);
        }

        // Security: only sender can delete
        $stmt = $pdo->prepare("DELETE FROM job_messages WHERE id = ? AND sender_id = ?");
        $stmt->execute([$msg_id, $user_id]);

        if ($stmt->rowCount() > 0) {
            jsonResponse(['success' => true, 'message' => 'Message deleted']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Failed to delete message or access denied']);
        }
        break;

    case 'get_doctor_messages':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $provider_id = (int) ($_GET['provider_id'] ?? 0);
        $customer_id = (int) ($_GET['customer_id'] ?? 0);
        $last_id = (int) ($_GET['last_id'] ?? 0);
        $user_id = getCurrentUserId();

        if (!$provider_id || !$customer_id) {
            jsonResponse(['success' => false, 'message' => 'Invalid parameters']);
        }

        // Security check: Either you are the customer or the user_id belongs to the provider
        $stmt = $pdo->prepare("SELECT user_id FROM health_providers WHERE id = ?");
        $stmt->execute([$provider_id]);
        $provider = $stmt->fetch();
        $provider_user_id = $provider['user_id'] ?? 0;

        if ($user_id != $customer_id && $user_id != $provider_user_id) {
            jsonResponse(['success' => false, 'message' => 'Access denied']);
        }

        // Fetch new messages with replied-to content
        $stmt = $pdo->prepare("
            SELECT m.*, rm.message as replied_message 
            FROM doctor_messages m
            LEFT JOIN doctor_messages rm ON m.reply_to_id = rm.id
            WHERE m.provider_id = ? AND m.customer_id = ? AND m.id > ? 
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$provider_id, $customer_id, $last_id]);
        $messages = $stmt->fetchAll();

        // Mark as read in background if needed
        foreach ($messages as $m) {
            // If I am the receiver, mark as read
            $is_me_customer = ($user_id == $customer_id);
            if (($is_me_customer && $m['sender_type'] == 'doctor') || (!$is_me_customer && $m['sender_type'] == 'customer')) {
                $pdo->prepare("UPDATE doctor_messages SET is_read = 1 WHERE id = ?")->execute([$m['id']]);
            }
        }

        jsonResponse(['success' => true, 'messages' => $messages]);
        break;

    case 'delete_doctor_chat_message':
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => 'Unauthorized']);
        }

        $msg_id = (int) ($_POST['message_id'] ?? 0);
        $user_id = getCurrentUserId();

        if (!$msg_id) {
            jsonResponse(['success' => false, 'message' => 'Invalid message ID']);
        }

        // Check if I am the sender of this message
        // Needs provider_id check too if sender_type is doctor...
        $stmt = $pdo->prepare("SELECT * FROM doctor_messages WHERE id = ?");
        $stmt->execute([$msg_id]);
        $msg = $stmt->fetch();

        if (!$msg) {
            jsonResponse(['success' => false, 'message' => 'Message not found']);
        }

        $is_allowed = false;
        if ($msg['sender_type'] == 'customer' && $user_id == $msg['sender_id']) {
            $is_allowed = true;
        } else if ($msg['sender_type'] == 'doctor') {
            $stmt = $pdo->prepare("SELECT user_id FROM health_providers WHERE id = ?");
            $stmt->execute([$msg['provider_id']]);
            $p_user = $stmt->fetch();
            if ($p_user && $p_user['user_id'] == $user_id) {
                $is_allowed = true;
            }
        }

        if ($is_allowed) {
            $pdo->prepare("DELETE FROM doctor_messages WHERE id = ?")->execute([$msg_id]);
            jsonResponse(['success' => true, 'message' => 'Message deleted']);
        } else {
            jsonResponse(['success' => false, 'message' => 'Access denied']);
        }
        break;

    case 'chapa_callback':
        // Handle Chapa webhook callback
        $tx_ref = sanitize($_GET['trx_ref'] ?? $_POST['trx_ref'] ?? '');
        if (!empty($tx_ref)) {
            // Extract type and ID from tx_ref (ETHIOSERVE-{TYPE}-{ID}-{timestamp})
            $parts = explode('-', $tx_ref);
            if (count($parts) >= 3) {
                $type = $parts[1]; // ORD or BOK
                $id = (int) $parts[2];

                if ($type === 'ORD') {
                    $stmt = $pdo->prepare("UPDATE orders SET payment_status = 'paid', payment_method = 'chapa' WHERE id = ?");
                    $stmt->execute([$id]);
                    sendPaymentConfirmationEmail($pdo, $id, 'order');
                } elseif ($type === 'BOK') {
                    $stmt = $pdo->prepare("UPDATE bookings SET status = 'approved' WHERE id = ?");
                    $stmt->execute([$id]);
                    sendPaymentConfirmationEmail($pdo, $id, 'booking');
                }
            }
        }
        jsonResponse(['success' => true, 'message' => 'Callback received']);
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid action']);
}
