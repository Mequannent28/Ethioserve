<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Fetch Labs
$stmt = $pdo->query("SELECT * FROM health_providers WHERE type = 'laboratory' ORDER BY rating DESC");
$labs = $stmt->fetchAll();

// Common Tests List
$tests = [
    ['name' => 'Complete Blood Count (CBC)', 'price' => 250],
    ['name' => 'Blood Sugar (Glucose)', 'price' => 120],
    ['name' => 'Lipid Profile', 'price' => 450],
    ['name' => 'Kidney Function Test', 'price' => 600],
    ['name' => 'Liver Function Test', 'price' => 550],
    ['name' => 'COVID-19 RT-PCR', 'price' => 1200],
    ['name' => 'Thyroid Panel (T3, T4, TSH)', 'price' => 800],
];

// Handle Booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_lab'])) {
    if (!isset($_SESSION['id'])) {
        redirectWithMessage('../login.php', 'warning', 'Authentication required.');
    }

    $user_id = $_SESSION['id'];
    $provider_id = intval($_POST['provider_id']);
    $tests_selected = json_encode($_POST['selected_tests']);
    $scheduled_at = $_POST['scheduled_at'];
    $total = floatval($_POST['total_price']);

    try {
        // We reuse the appointments table for lab bookings with type = 'lab_test'
        $stmt = $pdo->prepare("INSERT INTO health_appointments (user_id, provider_id, appointment_type, scheduled_at, reason, status) VALUES (?, ?, 'lab_test', ?, ?, 'pending')");
        $stmt->execute([$user_id, $provider_id, $scheduled_at, "Lab Reservation: " . $tests_selected]);

        redirectWithMessage('medical_records.php', 'success', 'Lab test booked! You will receive confirmation shortly.');
    } catch (Exception $e) {
        $error = "Booking failed: " . $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="row g-4 mb-5">
        <div class="col-lg-6">
            <h2 class="fw-bold mb-1">Laboratory Booking</h2>
            <p class="text-muted">Schedule diagnostic tests and receive digital reports securely</p>
        </div>
        <div class="col-lg-6 text-lg-end">
            <a href="medical_records.php" class="btn btn-outline-warning rounded-pill px-4">Test History</a>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <h5 class="fw-bold mb-4">Select Lab Tests</h5>
            <div class="row g-3">
                <?php foreach ($tests as $index => $t): ?>
                    <div class="col-12">
                        <div class="card border-0 shadow-sm rounded-4 p-3 test-item transition-all"
                            onclick="toggleTest(<?php echo $index; ?>, '<?php echo $t['name']; ?>', <?php echo $t['price']; ?>)">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="d-flex align-items-center">
                                    <div class="form-check mb-0">
                                        <input class="form-check-input" type="checkbox" id="test-<?php echo $index; ?>">
                                    </div>
                                    <div class="ms-3">
                                        <h6 class="fw-bold mb-0">
                                            <?php echo $t['name']; ?>
                                        </h6>
                                        <span class="small text-muted">Preparation may be required</span>
                                    </div>
                                </div>
                                <div class="fw-bold text-primary">
                                    <?php echo $t['price']; ?> ETB
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden sticky-top" style="top: 100px;">
                <div class="bg-warning text-dark p-4">
                    <h4 class="fw-bold mb-0">Booking Summary</h4>
                </div>
                <div class="card-body p-4">
                    <form method="POST">
                        <?php echo csrfField(); ?>
                        <input type="hidden" name="book_lab" value="1">
                        <input type="hidden" name="total_price" id="totalPriceInput" value="0">

                        <div id="selectedTests" class="mb-4">
                            <p class="text-muted text-center py-3 border rounded-4 border-dashed">No tests selected</p>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-bold">Select Laboratory</label>
                            <select name="provider_id" class="form-select rounded-pill border-0 bg-light px-3" required>
                                <?php foreach ($labs as $l): ?>
                                    <option value="<?php echo $l['id']; ?>">
                                        <?php echo htmlspecialchars($l['name']); ?> (
                                        <?php echo $l['location']; ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold">Preferred Schedule</label>
                            <input type="datetime-local" name="scheduled_at"
                                class="form-control rounded-pill border-0 bg-light px-3" required
                                min="<?php echo date('Y-m-d\TH:i'); ?>">
                        </div>

                        <div class="d-flex justify-content-between h5 fw-bold mb-4">
                            <span>Total (ETB):</span>
                            <span id="displayTotal">0</span>
                        </div>

                        <button type="submit" id="submitBtn"
                            class="btn btn-warning btn-lg w-100 rounded-pill py-3 fw-bold shadow-sm" disabled>
                            Confirm Lab Booking
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .test-item {
        cursor: pointer;
        border: 2px solid transparent !important;
    }

    .test-item:hover {
        background-color: #fff9f0;
    }

    .test-item.selected {
        border-color: #ffc107 !important;
        background-color: #fff9f0;
    }

    .border-dashed {
        border-style: dashed !important;
    }
</style>

<script>
    let selectedTests = [];

    function toggleTest(index, name, price) {
        const card = document.querySelectorAll('.test-item')[index];
        const checkbox = document.getElementById('test-' + index);

        const existsIndex = selectedTests.findIndex(t => t.name === name);

        if (existsIndex > -1) {
            selectedTests.splice(existsIndex, 1);
            card.classList.remove('selected');
            checkbox.checked = false;
        } else {
            selectedTests.push({ name, price, index });
            card.classList.add('selected');
            checkbox.checked = true;
        }
        updateUI();
    }

    function updateUI() {
        const container = document.getElementById('selectedTests');
        const totalDisplay = document.getElementById('displayTotal');
        const totalInput = document.getElementById('totalPriceInput');
        const submitBtn = document.getElementById('submitBtn');

        if (selectedTests.length === 0) {
            container.innerHTML = '<p class="text-muted text-center py-3 border rounded-4 border-dashed">No tests selected</p>';
            totalDisplay.innerText = '0';
            totalInput.value = '0';
            submitBtn.disabled = true;
            return;
        }

        let html = '';
        let total = 0;
        selectedTests.forEach((test) => {
            total += test.price;
            html += `
            <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded-3">
                <div class="small fw-bold">${test.name}</div>
                <div class="small">${test.price} ETB</div>
                <input type="hidden" name="selected_tests[]" value="${test.name}">
            </div>
        `;
        });

        container.innerHTML = html;
        totalDisplay.innerText = total.toLocaleString();
        totalInput.value = total;
        submitBtn.disabled = false;
    }
</script>

<?php include '../includes/footer.php'; ?>