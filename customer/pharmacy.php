<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch Pharmacies
$stmt = $pdo->query("SELECT * FROM health_providers WHERE type = 'pharmacy' ORDER BY rating DESC");
$pharmacies = $stmt->fetchAll();

// Simplified Medicine List
$medicines = [
    ['id' => 1, 'name' => 'Paracetamol', 'type' => 'OTC', 'price' => 50, 'image' => 'https://images.unsplash.com/photo-1584308666744-24d5c474f2ae?w=200'],
    ['id' => 2, 'name' => 'Amoxicillin', 'type' => 'Rx', 'price' => 320, 'image' => 'https://images.unsplash.com/photo-1550572017-ed200f5190d2?w=200'],
    ['id' => 3, 'name' => 'Vitamin C', 'type' => 'OTC', 'price' => 150, 'image' => 'https://images.unsplash.com/photo-1616671285442-87002b4122bc?w=200'],
    ['id' => 4, 'name' => 'Cough Syrup', 'type' => 'OTC', 'price' => 200, 'image' => 'https://images.unsplash.com/photo-1547089120-21b21d5b912c?w=200'],
    ['id' => 5, 'name' => 'Insulin', 'type' => 'Rx', 'price' => 2500, 'image' => 'https://images.unsplash.com/photo-1594411116127-d64e12e8731d?w=200'],
];

// Handle Order Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!isset($_SESSION['id'])) {
        redirectWithMessage('../login.php', 'warning', 'Please login to order medicine.');
    }

    $user_id = $_SESSION['id'];
    $pharmacy_id = intval($_POST['pharmacy_id']);
    $items = json_encode($_POST['selected_items']);
    $total = floatval($_POST['total_price']);
    $address = sanitize($_POST['address']);

    // In a real app, handle file upload for Rx
    $prescription_url = "uploads/prescriptions/dummy.jpg";

    try {
        $stmt = $pdo->prepare("INSERT INTO health_pharmacy_orders (user_id, pharmacy_id, items, total_price, status, delivery_address, prescription_url) VALUES (?, ?, ?, ?, 'pending', ?, ?)");
        $stmt->execute([$user_id, $pharmacy_id, $items, $total, $address, $prescription_url]);
        redirectWithMessage('medical_records.php', 'success', 'Your order has been placed successfully! Follow progress in your records.');
    } catch (Exception $e) {
        $error = "Order failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-1">Pharmacy Delivery</h2>
            <p class="text-muted">Order prescription and OTC medicines from local pharmacies</p>
        </div>
        <div class="col-lg-6 text-lg-end">
            <a href="medical_records.php" class="btn btn-outline-success rounded-pill px-4">My Orders</a>
        </div>
    </div>

    <div class="row g-4">
        <!-- Medicine Catalog -->
        <div class="col-lg-8">
            <div class="row g-3">
                <?php foreach ($medicines as $med): ?>
                    <div class="col-6 col-md-4">
                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden med-card transition-all">
                            <img src="<?php echo $med['image']; ?>" class="card-img-top"
                                style="height: 150px; object-fit: cover;">
                            <div class="card-body p-3">
                                <span
                                    class="badge <?php echo $med['type'] == 'Rx' ? 'bg-danger' : 'bg-primary'; ?> rounded-pill mb-2">
                                    <?php echo $med['type']; ?>
                                </span>
                                <h6 class="fw-bold mb-1">
                                    <?php echo $med['name']; ?>
                                </h6>
                                <div class="text-success fw-bold">
                                    <?php echo $med['price']; ?> ETB
                                </div>
                                <button class="btn btn-sm btn-outline-primary w-100 rounded-pill mt-3"
                                    onclick="addToCart(<?php echo htmlspecialchars(json_encode($med)); ?>)">Add to
                                    Order</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Checkout Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden sticky-top" style="top: 100px;">
                <div class="bg-success text-white p-4">
                    <h4 class="fw-bold mb-0">Delivery Details</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST" id="orderForm">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="place_order" value="1">
                        <input type="hidden" name="total_price" id="totalPriceInput" value="0">

                        <div id="cartItems" class="mb-4">
                            <p class="text-muted text-center py-3 border rounded-4 border-dashed">No items added yet</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Pharmacy</label>
                            <select name="pharmacy_id" class="form-select rounded-pill border-0 bg-light px-3" required>
                                <?php foreach ($pharmacies as $ph): ?>
                                    <option value="<?php echo $ph['id']; ?>">
                                        <?php echo htmlspecialchars($ph['name']); ?> (
                                        <?php echo $ph['location']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Delivery Address</label>
                            <textarea name="address" class="form-control border-0 bg-light px-3 py-3" rows="2"
                                placeholder="House No, Street..." style="border-radius:15px;" required></textarea>
                        </div>

                        <div id="rxUpload" class="mb-4 d-none">
                            <label class="form-label small fw-bold text-danger"><i
                                    class="fas fa-prescription me-1"></i>Upload Prescription</label>
                            <input type="file" class="form-control rounded-pill border-0 bg-light px-3">
                            <div class="small text-muted mt-1">Required for Rx items.</div>
                        </div>

                        <div class="d-flex justify-content-between h5 fw-bold mb-4">
                            <span>Total (ETB):</span>
                            <span id="displayTotal">0</span>
                        </div>

                        <button type="submit" id="submitOrder"
                            class="btn btn-success btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm" disabled>
                            Confirm & Place Order
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .med-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }

    .border-dashed {
        border-style: dashed !important;
    }
</style>

<script>
    let cart = [];

    function addToCart(item) {
        cart.push(item);
        updateUI();
    }

    function removeFromCart(index) {
        cart.splice(index, 1);
        updateUI();
    }

    function updateUI() {
        const container = document.getElementById('cartItems');
        const totalDisplay = document.getElementById('displayTotal');
        const totalInput = document.getElementById('totalPriceInput');
        const submitBtn = document.getElementById('submitOrder');
        const rxBox = document.getElementById('rxUpload');

        if (cart.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3 border rounded-4 border-dashed">No items added yet</p>';
            totalDisplay.innerText = '0';
            totalInput.value = '0';
            submitBtn.disabled = true;
            rxBox.classList.add('d-none');
            return;
        }

        let html = '';
        let total = 0;
        let hasRx = false;

        cart.forEach((item, index) => {
            total += item.price;
            if (item.type === 'Rx') hasRx = true;
            html += `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-3">
                <div class="small fw-bold">${item.name}</div>
                <div class="d-flex align-items-center">
                    <span class="small me-3">${item.price} ETB</span>
                    <button type="button" class="btn btn-sm text-danger" onclick="removeFromCart(${index})"><i class="fas fa-times"></i></button>
                    <input type="hidden" name="selected_items[]" value="${item.name}">
                </div>
            </div>
        `;
        });

        container.innerHTML = html;
        totalDisplay.innerText = total.toLocaleString();
        totalInput.value = total;
        submitBtn.disabled = false;

        if (hasRx) rxBox.classList.remove('d-none');
        else rxBox.classList.add('d-none');
    }
</script>

<?php include '../includes/footer.php'; ?>