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
