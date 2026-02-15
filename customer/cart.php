<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Check if user is logged in for checkout
$is_logged_in = isLoggedIn();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $action = $_POST['action'];

    if ($action === 'update_quantity') {
        $item_id = (int) $_POST['item_id'];
        $quantity = max(1, (int) $_POST['quantity']);
        $result = updateCartQuantity($item_id, $quantity);
        $result['subtotal'] = getCartSubtotal();
        $result['cart_count'] = getCartCount();
        echo json_encode($result);
        exit;
    }

    if ($action === 'remove_item') {
        $item_id = (int) $_POST['item_id'];
        $result = removeFromCart($item_id);
        $result['subtotal'] = getCartSubtotal();
        $result['cart_count'] = getCartCount();
        echo json_encode($result);
        exit;
    }

    if ($action === 'place_order') {
        if (!$is_logged_in) {
            echo json_encode(['success' => false, 'message' => 'Please login to place an order', 'require_login' => true]);
            exit;
        }

        $items = getCartItems();
        if (empty($items)) {
            echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
            exit;
        }

        $hotel = getCartHotel();
        $payment_method = sanitize($_POST['payment_method'] ?? 'cash');
        $delivery_address = sanitize($_POST['delivery_address'] ?? '');
        $broker_code = sanitize($_POST['broker_code'] ?? '');

        if (empty($delivery_address)) {
            echo json_encode(['success' => false, 'message' => 'Please enter delivery address']);
            exit;
        }

        try {
            $pdo->beginTransaction();

            // Calculate total
            $total_amount = getCartSubtotal();
            $delivery_fee = 150;
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

            $redirect_url = ($payment_method === 'cash')
                ? "track_order.php?id=$order_id"
                : "payment.php?order_id=$order_id&method=$payment_method";

            echo json_encode([
                'success' => true,
                'message' => 'Order placed successfully! Confirmation email sent.',
                'order_id' => $order_id,
                'redirect' => $redirect_url
            ]);

        } catch (Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Failed to place order. Please try again.']);
        }
        exit;
    }
}

// Get cart items for display
$cart_items = getCartItems();
$cart_hotel = getCartHotel();
$subtotal = getCartSubtotal();
$delivery_fee = $subtotal > 0 ? 150 : 0;
$total = $subtotal + $delivery_fee;

include('../includes/header.php');
?>

<main class="container py-5">
    <?php echo displayFlashMessage(); ?>

    <div class="row g-5">
        <!-- Order Items -->
        <div class="col-lg-8">
            <h3 class="fw-bold mb-4">Your Shopping Cart</h3>

            <?php if (empty($cart_items)): ?>
                <div class="card border-0 shadow-sm p-5 text-center">
                    <i class="fas fa-shopping-cart text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">Your cart is empty</h4>
                    <p class="text-muted mb-4">Add some delicious items from our restaurants!</p>
                    <a href="index.php" class="btn btn-primary-green rounded-pill px-4">
                        <i class="fas fa-utensils me-2"></i> Browse Restaurants
                    </a>
                </div>
            <?php else: ?>
                <div class="card border-0 shadow-sm p-4 overflow-hidden">
                    <!-- Hotel Info -->
                    <?php if ($cart_hotel['hotel_name']): ?>
                        <div class="mb-4 pb-3 border-bottom">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-primary-green text-white rounded-circle p-2" style="width: 40px; height: 40px;">
                                    <i class="fas fa-store"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($cart_hotel['hotel_name']); ?></h6>
                                    <small class="text-muted">All items from this restaurant</small>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="text-muted small text-uppercase">
                                <tr>
                                    <th class="border-0">Item</th>
                                    <th class="border-0 text-center">Quantity</th>
                                    <th class="border-0 text-end">Price</th>
                                    <th class="border-0"></th>
                                </tr>
                            </thead>
                            <tbody id="cartItems">
                                <?php foreach ($cart_items as $item): ?>
                                    <tr data-item-id="<?php echo $item['id']; ?>">
                                        <td class="py-3 border-0">
                                            <div class="d-flex align-items-center gap-3">
                                                <img src="<?php echo htmlspecialchars($item['image_url'] ?? 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=100&q=80'); ?>"
                                                    class="rounded-3" width="60" height="60" style="object-fit: cover;">
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($item['name']); ?></h6>
                                                    <p class="text-muted small mb-0">
                                                        <?php echo number_format($item['price']); ?> ETB each
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 border-0 text-center">
                                            <div class="d-inline-flex border rounded-pill overflow-hidden">
                                                <button class="btn btn-sm btn-light px-3 border-0 qty-btn"
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                                <span class="px-3 py-1 qty-display"><?php echo $item['quantity']; ?></span>
                                                <button class="btn btn-sm btn-light px-3 border-0 qty-btn"
                                                    onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                            </div>
                                        </td>
                                        <td class="py-3 border-0 text-end fw-bold item-total">
                                            <?php echo number_format($item['price'] * $item['quantity']); ?> ETB
                                        </td>
                                        <td class="py-3 border-0 text-end">
                                            <button class="btn btn-link text-danger p-0"
                                                onclick="removeItem(<?php echo $item['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 pt-4 border-top">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold" id="subtotal"><?php echo number_format($subtotal); ?> ETB</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Delivery Fee</span>
                            <span class="fw-bold" id="deliveryFee"><?php echo number_format($delivery_fee); ?> ETB</span>
                        </div>
                        <div class="d-flex justify-content-between mt-3">
                            <h5 class="fw-bold">Total</h5>
                            <h5 class="fw-bold text-primary-green" id="total"><?php echo number_format($total); ?> ETB</h5>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <a href="index.php" class="btn btn-link text-primary-green text-decoration-none mt-4 p-0">
                <i class="fas fa-arrow-left me-2"></i> Continue Shopping
            </a>
        </div>

        <!-- Checkout Sidebar -->
        <div class="col-lg-4">
            <h3 class="fw-bold mb-4">Checkout</h3>

            <?php if (!$is_logged_in): ?>
                <div class="card border-0 shadow-sm p-4 mb-4">
                    <div class="text-center">
                        <i class="fas fa-user-lock text-muted mb-3" style="font-size: 2rem;"></i>
                        <h6 class="fw-bold">Login Required</h6>
                        <p class="text-muted small mb-3">Please login to place your order</p>
                        <a href="../login.php?redirect=cart" class="btn btn-primary-green rounded-pill w-100">
                            <i class="fas fa-sign-in-alt me-2"></i> Login
                        </a>
                    </div>
                </div>
            <?php elseif (empty($cart_items)): ?>
                <div class="card border-0 shadow-sm p-4">
                    <p class="text-muted text-center mb-0">Add items to your cart to checkout</p>
                </div>
            <?php else: ?>
                <form id="checkoutForm">
                    <?php echo csrfField(); ?>

                    <div class="card border-0 shadow-sm p-4">
                        <h6 class="fw-bold mb-3">Payment Method</h6>
                        <div class="d-flex flex-column gap-2 mb-4">
                            <div class="form-check card-radio p-3 border rounded-3 d-flex align-items-center gap-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="chapa"
                                    value="chapa" checked>
                                <label
                                    class="form-check-label w-100 d-flex justify-content-between align-items-center cursor-pointer"
                                    for="chapa">
                                    <span><strong>Chapa</strong> <small class="text-muted">(Recommended)</small></span>
                                    <span style="background:linear-gradient(135deg,#7B61FF,#00D4AA);color:#fff;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:bold;">CHAPA</span>
                                </label>
                            </div>
                            <div class="form-check card-radio p-3 border rounded-3 d-flex align-items-center gap-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="telebirr"
                                    value="telebirr">
                                <label
                                    class="form-check-label w-100 d-flex justify-content-between align-items-center cursor-pointer"
                                    for="telebirr">
                                    <span>Telebirr</span>
                                    <img src="https://img.icons8.com/color/48/000000/smartphone.png" width="30">
                                </label>
                            </div>
                            <div class="form-check card-radio p-3 border rounded-3 d-flex align-items-center gap-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="cbe"
                                    value="cbe_birr">
                                <label class="form-check-label w-100 d-flex justify-content-between align-items-center"
                                    for="cbe">
                                    <span>CBE Birr</span>
                                    <img src="https://img.icons8.com/color/48/000000/bank.png" width="30">
                                </label>
                            </div>
                            <div class="form-check card-radio p-3 border rounded-3 d-flex align-items-center gap-3">
                                <input class="form-check-input" type="radio" name="payment_method" id="cash" value="cash">
                                <label class="form-check-label w-100 d-flex justify-content-between align-items-center"
                                    for="cash">
                                    <span>Cash on Delivery</span>
                                    <i class="fas fa-money-bill-wave text-success fs-5"></i>
                                </label>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3">Delivery Address</h6>
                        <textarea class="form-control rounded-3 bg-light border-0 mb-4" name="delivery_address" rows="3"
                            placeholder="Enter your detailed address..." required></textarea>

                        <h6 class="fw-bold mb-3">Broker Code (Optional)</h6>
                        <div class="input-group mb-4">
                            <span class="input-group-text bg-light border-0"><i
                                    class="fas fa-user-tag text-muted"></i></span>
                            <input type="text" name="broker_code" class="form-control bg-light border-0"
                                placeholder="Enter referral code">
                        </div>

                        <button type="submit" class="btn btn-primary-green btn-lg rounded-pill w-100 py-3 shadow"
                            id="placeOrderBtn">
                            <i class="fas fa-check-circle me-2"></i> Confirm Order
                        </button>
                        <p class="text-center text-muted small mt-3">By confirming, you agree to EthioServe's terms and
                            conditions.</p>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    const csrfToken = '<?php echo generateCSRFToken(); ?>';

    function updateQuantity(itemId, newQty) {
        if (newQty < 1) {
            removeItem(itemId);
            return;
        }

        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=update_quantity&item_id=${itemId}&quantity=${newQty}&csrf_token=${csrfToken}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }

    function removeItem(itemId) {
        if (!confirm('Remove this item from cart?')) return;

        fetch('cart.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=remove_item&item_id=${itemId}&csrf_token=${csrfToken}`
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }

    document.getElementById('checkoutForm')?.addEventListener('submit', function (e) {
        e.preventDefault();

        const btn = document.getElementById('placeOrderBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Processing...';

        const formData = new FormData(this);
        formData.append('action', 'place_order');
        formData.append('csrf_token', csrfToken);

        fetch('cart.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.href = data.redirect;
                } else {
                    alert(data.message);
                    if (data.require_login) {
                        window.location.href = '../login.php?redirect=cart';
                    }
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Confirm Order';
                }
            })
            .catch(err => {
                alert('An error occurred. Please try again.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check-circle me-2"></i> Confirm Order';
            });
    });
</script>

<?php include('../includes/footer.php'); ?>