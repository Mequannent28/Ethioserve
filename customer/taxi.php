<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$is_logged_in = isLoggedIn();
$flash = getFlashMessage();
$user_id = getCurrentUserId();

// Fetch taxi companies from DB
$taxi_companies = [];
try {
    $stmt = $pdo->query("SELECT * FROM taxi_companies WHERE status = 'approved' ORDER BY rating DESC");
    $taxi_companies = $stmt->fetchAll();
} catch (Exception $e) {
    // ignore
}

// AJAX: Get available vehicles for a provider
if (isset($_GET['get_vehicles']) && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $provider = sanitize($_GET['provider'] ?? 'ride');
    $company_id = null;

    if (!empty($taxi_companies)) {
        foreach ($taxi_companies as $tc) {
            if (stripos($tc['company_name'], $provider) !== false) {
                $company_id = $tc['id'];
                break;
            }
        }
        if (!$company_id)
            $company_id = $taxi_companies[0]['id'];
    }

    $vehicles = [];
    if ($company_id) {
        $stmt = $pdo->prepare("SELECT id, driver_name, driver_phone, vehicle_type, plate_number, model, color, is_available FROM taxi_vehicles WHERE company_id = ? ORDER BY is_available DESC, RAND() LIMIT 10");
        $stmt->execute([$company_id]);
        $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Pad to at least 5 with simulated vehicles if needed
    $ethiopian_names = ['Abebe Tadesse', 'Dawit Mekonnen', 'Kidus Haile', 'Yonas Gebru', 'Solomon Tekle', 'Bereket Fikre', 'Tewodros Alemu', 'Henok Girma'];
    $models = ['Toyota Corolla', 'Hyundai Accent', 'Toyota Yaris', 'Suzuki Dzire', 'Toyota Vitz', 'Lifan X50', 'Geely EC7'];
    $colors = ['White', 'Silver', 'Blue', 'Grey', 'Black', 'Red'];
    $count = count($vehicles);
    $used_ids = array_column($vehicles, 'id');

    while ($count < 5) {
        $sim_id = 900 + $count;
        $vehicles[] = [
            'id' => $sim_id,
            'driver_name' => $ethiopian_names[array_rand($ethiopian_names)],
            'driver_phone' => '+25192' . rand(1000000, 9999999),
            'vehicle_type' => 'Sedan',
            'plate_number' => 'AA-3-' . rand(10000, 99999),
            'model' => $models[array_rand($models)],
            'color' => $colors[array_rand($colors)],
            'is_available' => 1
        ];
        $count++;
    }

    // Add random ratings and trip counts
    foreach ($vehicles as &$v) {
        $v['rating'] = round(3.8 + (mt_rand(0, 12) / 10), 1);
        $v['trips'] = rand(40, 350);
        $v['eta_min'] = rand(2, 12);
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'vehicles' => $vehicles]);
    exit;
}

// Handle ride booking POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_ride'])) {
    $pickup = sanitize($_POST['pickup_location'] ?? '');
    $destination = sanitize($_POST['destination'] ?? '');
    $ride_type = sanitize($_POST['ride_type'] ?? 'economy');
    $provider = sanitize($_POST['provider'] ?? 'ride');
    $fare = floatval($_POST['fare'] ?? 0);
    $passenger_name = sanitize($_POST['passenger_name'] ?? '');
    $passenger_phone = sanitize($_POST['passenger_phone'] ?? '');
    $selected_vehicle_id = intval($_POST['vehicle_id'] ?? 0);

    // Guest booking - auto-register if not logged in
    $booking_user_id = $user_id;
    $auto_registered = false;
    $auto_password = '';

    if (!$is_logged_in) {
        if (empty($passenger_name) || empty($passenger_phone)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => 'Name and phone number are required.']);
                exit;
            }
            redirectWithMessage('taxi.php', 'error', 'Name and phone are required.');
        }

        // Check if user with this phone already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR username = ?");
        $clean_phone = preg_replace('/[^0-9+]/', '', $passenger_phone);
        $auto_username = 'rider_' . preg_replace('/[^0-9]/', '', $clean_phone);
        $stmt->execute([$clean_phone, $auto_username]);
        $existing = $stmt->fetch();

        if ($existing) {
            $booking_user_id = $existing['id'];
        } else {
            // Auto-register as customer
            $auto_password = substr(str_shuffle('abcdefghjkmnpqrstuvwxyz23456789'), 0, 6);
            $hashed = password_hash($auto_password, PASSWORD_DEFAULT);
            $auto_email = $auto_username . '@ethioserve.temp';

            try {
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'customer', NOW())");
                $stmt->execute([$auto_username, $auto_email, $hashed, $passenger_name, $clean_phone]);
                $booking_user_id = $pdo->lastInsertId();
                $auto_registered = true;
            } catch (Exception $e) {
                // Try with a more unique username if duplicate
                $auto_username .= '_' . substr(uniqid(), -4);
                $auto_email = $auto_username . '@ethioserve.temp';
                $stmt = $pdo->prepare("INSERT INTO users (username, email, password, full_name, phone, role, created_at) VALUES (?, ?, ?, ?, ?, 'customer', NOW())");
                $stmt->execute([$auto_username, $auto_email, $hashed, $passenger_name, $clean_phone]);
                $booking_user_id = $pdo->lastInsertId();
                $auto_registered = true;
            }
        }
    }

    if (empty($pickup) || empty($destination)) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Pickup and destination are required.']);
            exit;
        }
        redirectWithMessage('taxi.php', 'error', 'Pickup and destination are required.');
    }

    if ($fare <= 0)
        $fare = 120;

    try {
        // Find taxi company for provider
        $company_id = null;
        if (!empty($taxi_companies)) {
            foreach ($taxi_companies as $tc) {
                if (stripos($tc['company_name'], $provider) !== false) {
                    $company_id = $tc['id'];
                    break;
                }
            }
            if (!$company_id)
                $company_id = $taxi_companies[0]['id'];
        }

        // Use the selected vehicle if provided, otherwise pick random
        $driver_name = '';
        $vehicle_plate = '';
        $vehicle_model = '';
        $vehicle_color = '';
        if ($selected_vehicle_id > 0 && $selected_vehicle_id < 900) {
            $stmt = $pdo->prepare("SELECT driver_name, plate_number, model, color FROM taxi_vehicles WHERE id = ? AND is_available = 1");
            $stmt->execute([$selected_vehicle_id]);
            $vehicle = $stmt->fetch();
            if ($vehicle) {
                $driver_name = $vehicle['driver_name'];
                $vehicle_plate = $vehicle['plate_number'];
                $vehicle_model = $vehicle['model'];
                $vehicle_color = $vehicle['color'];
            }
        }

        // Fallback: pick random if no specific vehicle
        if (empty($driver_name) && $company_id) {
            $stmt = $pdo->prepare("SELECT driver_name, plate_number, model, color FROM taxi_vehicles WHERE company_id = ? AND is_available = 1 ORDER BY RAND() LIMIT 1");
            $stmt->execute([$company_id]);
            $vehicle = $stmt->fetch();
            if ($vehicle) {
                $driver_name = $vehicle['driver_name'];
                $vehicle_plate = $vehicle['plate_number'];
                $vehicle_model = $vehicle['model'];
                $vehicle_color = $vehicle['color'];
            }
        }

        $ride_ref = 'RIDE-' . strtoupper(substr(uniqid(), -6));

        $stmt = $pdo->prepare("INSERT INTO taxi_rides (ride_reference, customer_id, taxi_company_id, pickup_location, dropoff_location, fare, status, payment_status, payment_method, passenger_name, passenger_phone, driver_name, vehicle_plate) VALUES (?, ?, ?, ?, ?, ?, 'requested', 'pending', 'cash', ?, ?, ?, ?)");
        $stmt->execute([
            $ride_ref,
            $booking_user_id,
            $company_id,
            $pickup,
            $destination,
            $fare,
            $passenger_name,
            $passenger_phone,
            $driver_name,
            $vehicle_plate
        ]);

        $ride_id = $pdo->lastInsertId();

        // Return JSON for AJAX booking
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            $response = [
                'success' => true,
                'ride_ref' => $ride_ref,
                'driver_name' => $driver_name ?: 'Assigned Driver',
                'vehicle_plate' => $vehicle_plate ?: 'AA-XXXX',
                'fare' => $fare,
                'ride_type' => $ride_type,
                'provider' => $provider,
                'auto_registered' => $auto_registered
            ];
            if ($auto_registered) {
                $response['login_username'] = $auto_username;
                $response['login_password'] = $auto_password;
            }
            echo json_encode($response);
            exit;
        }

        redirectWithMessage('taxi.php', 'success', "Ride booked! Reference: $ride_ref");
    } catch (Exception $e) {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Failed to book ride. Please try again.']);
            exit;
        }
        redirectWithMessage('taxi.php', 'error', 'Failed to book ride. Please try again.');
    }
}

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
    :root {
        --taxi-yellow: #FFD600;
        --taxi-dark: #212121;
        --ride-blue: #1565C0;
        --feres-green: #1B5E20;
        --yango-red: #D32F2F;
        --bg-color: #f4f6f3;
    }

    .taxi-page {
        font-family: 'Outfit', sans-serif;
        background-color: var(--bg-color);
    }

    .taxi-container {
        max-width: 520px;
        margin: 0 auto;
        padding: 20px 15px 0;
    }

    /* Map area */
    .map-area {
        height: 300px;
        background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        border-radius: 28px;
        position: relative;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
        margin-bottom: -30px;
        z-index: 1;
        overflow: hidden;
    }

    .map-grid {
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255, 255, 255, 0.03) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255, 255, 255, 0.03) 1px, transparent 1px);
        background-size: 30px 30px;
    }

    .map-roads {
        position: absolute;
        inset: 0;
    }

    .map-road {
        position: absolute;
        background: rgba(255, 214, 0, 0.15);
        border-radius: 4px;
    }

    .map-pin {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -100%);
        z-index: 5;
    }

    .map-pin-icon {
        font-size: 2.2rem;
        color: var(--taxi-yellow);
        filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.4));
        animation: pinBounce 2s ease-in-out infinite;
    }

    .map-pin-pulse {
        width: 20px;
        height: 20px;
        background: rgba(255, 214, 0, 0.3);
        border-radius: 50%;
        position: absolute;
        bottom: -8px;
        left: 50%;
        transform: translateX(-50%);
        animation: pulse 2s ease-in-out infinite;
    }

    @keyframes pinBounce {

        0%,
        100% {
            transform: translate(-50%, -100%);
        }

        50% {
            transform: translate(-50%, calc(-100% - 8px));
        }
    }

    @keyframes pulse {

        0%,
        100% {
            transform: translateX(-50%) scale(1);
            opacity: 0.6;
        }

        50% {
            transform: translateX(-50%) scale(2.5);
            opacity: 0;
        }
    }

    .map-car {
        position: absolute;
        font-size: 1.1rem;
        color: var(--taxi-yellow);
        opacity: 0.8;
        animation: drift 6s ease-in-out infinite alternate;
    }

    @keyframes drift {
        0% {
            transform: translateX(0) translateY(0);
        }

        100% {
            transform: translateX(15px) translateY(-10px);
        }
    }

    .map-location-badge {
        position: absolute;
        bottom: 16px;
        left: 50%;
        transform: translateX(-50%);
        background: rgba(0, 0, 0, 0.6);
        backdrop-filter: blur(10px);
        color: #fff;
        padding: 8px 18px;
        border-radius: 30px;
        font-size: 0.78rem;
        font-weight: 500;
        z-index: 6;
        white-space: nowrap;
    }

    .map-location-badge i {
        color: var(--taxi-yellow);
        margin-right: 6px;
    }

    .detecting-spinner {
        display: inline-block;
        width: 12px;
        height: 12px;
        border: 2px solid rgba(255, 214, 0, 0.3);
        border-top-color: var(--taxi-yellow);
        border-radius: 50%;
        animation: spin 0.8s linear infinite;
        margin-right: 8px;
        vertical-align: middle;
    }

    @keyframes spin {
        to {
            transform: rotate(360deg);
        }
    }

    /* Booking Panel */
    .booking-panel {
        background: white;
        border-radius: 28px 28px 0 0;
        padding: 35px 24px 40px;
        box-shadow: 0 -8px 30px rgba(0, 0, 0, 0.06);
        position: relative;
        z-index: 2;
    }

    /* Provider Tabs */
    .provider-tabs {
        display: flex;
        gap: 10px;
        margin-bottom: 24px;
        overflow-x: auto;
        padding-bottom: 4px;
    }

    .provider-tab {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 16px;
        border: 2px solid #eee;
        cursor: pointer;
        transition: all 0.3s ease;
        white-space: nowrap;
        font-weight: 600;
        font-size: 0.85rem;
        background: #fff;
        flex-shrink: 0;
    }

    .provider-tab:hover {
        border-color: #bbb;
        transform: translateY(-2px);
    }

    .provider-tab.active-provider {
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transform: translateY(-2px);
    }

    .provider-tab[data-provider="ride"].active-provider {
        border-color: var(--ride-blue);
        background: #e3f2fd;
    }

    .provider-tab[data-provider="feres"].active-provider {
        border-color: var(--feres-green);
        background: #e8f5e9;
    }

    .provider-tab[data-provider="yango"].active-provider {
        border-color: var(--yango-red);
        background: #ffebee;
    }

    .provider-logo {
        width: 26px;
        height: 26px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
    }

    /* Location Card */
    .location-card {
        background: #f8f9fa;
        border-radius: 20px;
        padding: 18px 20px;
        margin-bottom: 22px;
        border: 2px solid transparent;
        transition: border-color 0.3s;
    }

    .location-card:focus-within {
        border-color: var(--taxi-yellow);
    }

    .loc-row {
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .loc-indicator {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0;
    }

    .loc-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        border: 3px solid;
        flex-shrink: 0;
    }

    .loc-dot-pickup {
        border-color: #43A047;
        background: #e8f5e9;
    }

    .loc-dot-dest {
        border-color: #e53935;
        background: #ffebee;
    }

    .loc-line-v {
        width: 2px;
        height: 28px;
        background: #ddd;
    }

    .loc-input {
        border: none;
        background: transparent;
        font-weight: 600;
        font-size: 0.92rem;
        color: #333;
        width: 100%;
        padding: 8px 0;
        outline: none;
    }

    .loc-input::placeholder {
        color: #aaa;
        font-weight: 500;
    }

    .loc-input:focus {
        color: #000;
    }

    .loc-swap {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid #eee;
        background: #fff;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.8rem;
        color: #888;
        flex-shrink: 0;
    }

    .loc-swap:hover {
        border-color: var(--taxi-yellow);
        color: var(--taxi-dark);
        transform: rotate(180deg);
    }

    /* Destination Suggestions */
    .dest-suggestions {
        display: none;
        background: #fff;
        border-radius: 16px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
        position: absolute;
        left: 0;
        right: 0;
        z-index: 100;
        max-height: 280px;
        overflow-y: auto;
        margin-top: 4px;
    }

    .dest-suggestions.show {
        display: block;
    }

    .dest-item {
        display: flex;
        align-items: center;
        gap: 14px;
        padding: 12px 18px;
        cursor: pointer;
        transition: background 0.15s;
        border-bottom: 1px solid #f5f5f5;
    }

    .dest-item:last-child {
        border-bottom: none;
    }

    .dest-item:hover {
        background: #fffde7;
    }

    .dest-item-icon {
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: #f5f5f5;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #888;
        font-size: 0.85rem;
        flex-shrink: 0;
    }

    .dest-item-name {
        font-weight: 600;
        font-size: 0.85rem;
        color: #333;
    }

    .dest-item-area {
        font-size: 0.72rem;
        color: #aaa;
    }

    /* Ride Options */
    .ride-option {
        border: 2px solid #f1f1f1;
        border-radius: 18px;
        padding: 14px 16px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.25s ease;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .ride-option:hover {
        border-color: #ddd;
        background: #fafafa;
    }

    .ride-option.active {
        border-color: var(--taxi-yellow);
        background: #fffde7;
        box-shadow: 0 2px 12px rgba(255, 214, 0, 0.15);
    }

    .ride-icon {
        width: 48px;
        height: 48px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .ride-info {
        flex: 1;
    }

    .ride-name {
        font-weight: 700;
        font-size: 0.92rem;
        margin-bottom: 2px;
    }

    .ride-meta {
        font-size: 0.75rem;
        color: #999;
    }

    .ride-price {
        font-weight: 800;
        font-size: 1rem;
        white-space: nowrap;
    }

    .ride-price-orig {
        font-size: 0.72rem;
        color: #bbb;
        text-decoration: line-through;
        margin-right: 4px;
    }

    /* Book Button */
    .btn-ride {
        background: var(--taxi-dark);
        color: var(--taxi-yellow);
        border: none;
        border-radius: 18px;
        padding: 18px;
        width: 100%;
        font-weight: 800;
        font-size: 1.15rem;
        transition: all 0.3s;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
        position: relative;
        overflow: hidden;
    }

    .btn-ride:hover:not(:disabled) {
        transform: scale(1.02);
        background: #000;
    }

    .btn-ride:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .btn-ride .btn-text {
        transition: all 0.3s;
    }

    /* Fare breakdown */
    .fare-breakdown {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 16px 18px;
        margin: 16px 0;
        display: none;
    }

    .fare-breakdown.show {
        display: block;
    }

    .fare-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 4px 0;
        font-size: 0.82rem;
        color: #666;
    }

    .fare-row.total {
        font-weight: 800;
        font-size: 0.95rem;
        color: #333;
        border-top: 1px solid #eee;
        padding-top: 10px;
        margin-top: 6px;
    }

    /* Ride Booking Modal */
    .ride-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10000;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(4px);
        align-items: center;
        justify-content: center;
        padding: 20px;
    }

    .ride-modal-overlay.active {
        display: flex;
    }

    .ride-modal {
        background: #fff;
        border-radius: 28px;
        padding: 40px 30px;
        max-width: 400px;
        width: 100%;
        text-align: center;
        box-shadow: 0 25px 60px rgba(0, 0, 0, 0.3);
        animation: modalPop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }

    @keyframes modalPop {
        from {
            transform: scale(0.8);
            opacity: 0;
        }

        to {
            transform: scale(1);
            opacity: 1;
        }
    }

    .driver-card {
        background: #f8f9fa;
        border-radius: 18px;
        padding: 20px;
        margin: 20px 0;
        text-align: left;
    }

    .driver-avatar {
        width: 56px;
        height: 56px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--taxi-yellow), #FFA000);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--taxi-dark);
    }

    /* Login prompt */
    .login-prompt {
        background: linear-gradient(135deg, #fff3e0, #fff8e1);
        border: 2px solid #ffe082;
        border-radius: 16px;
        padding: 16px 20px;
        margin-bottom: 20px;
        text-align: center;
    }

    /* Guest Booking Modal */
    .guest-modal-overlay {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 9999;
        background: rgba(0, 0, 0, 0.7);
        backdrop-filter: blur(6px);
        align-items: flex-end;
        justify-content: center;
        padding: 0;
    }

    .guest-modal-overlay.active {
        display: flex;
    }

    .guest-modal {
        background: #fff;
        border-radius: 28px 28px 0 0;
        padding: 30px 24px 40px;
        max-width: 520px;
        width: 100%;
        box-shadow: 0 -10px 40px rgba(0, 0, 0, 0.25);
        animation: slideUpModal 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        max-height: 90vh;
        overflow-y: auto;
    }

    @keyframes slideUpModal {
        from {
            transform: translateY(100%);
            opacity: 0;
        }

        to {
            transform: translateY(0);
            opacity: 1;
        }
    }

    .guest-modal-handle {
        width: 40px;
        height: 4px;
        background: #ddd;
        border-radius: 4px;
        margin: 0 auto 18px;
    }

    .guest-modal h4 {
        font-weight: 800;
        font-size: 1.2rem;
        margin-bottom: 4px;
    }

    .guest-modal .subtitle {
        color: #999;
        font-size: 0.82rem;
        margin-bottom: 18px;
    }

    .guest-field {
        margin-bottom: 14px;
    }

    .guest-field label {
        display: block;
        font-weight: 700;
        font-size: 0.78rem;
        color: #555;
        margin-bottom: 6px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .guest-field input {
        width: 100%;
        border: 2px solid #eee;
        border-radius: 14px;
        padding: 14px 16px;
        font-size: 0.92rem;
        font-weight: 600;
        font-family: 'Outfit', sans-serif;
        outline: none;
        transition: border-color 0.3s;
        background: #f8f9fa;
    }

    .guest-field input:focus {
        border-color: var(--taxi-yellow);
        background: #fff;
    }

    .guest-field input::placeholder {
        color: #bbb;
        font-weight: 400;
    }

    .guest-ride-summary {
        background: #f8f9fa;
        border-radius: 16px;
        padding: 14px 16px;
        margin-bottom: 18px;
        display: flex;
        align-items: center;
        gap: 14px;
    }

    .guest-ride-summary .ride-icon-sm {
        width: 44px;
        height: 44px;
        border-radius: 12px;
        background: #fffde7;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        flex-shrink: 0;
    }

    .guest-ride-summary .ride-detail {
        flex: 1;
    }

    .guest-ride-summary .ride-detail .route {
        font-weight: 700;
        font-size: 0.85rem;
        color: #333;
    }

    .guest-ride-summary .ride-detail .meta {
        font-size: 0.72rem;
        color: #999;
    }

    .guest-ride-summary .ride-fare {
        font-weight: 800;
        font-size: 1.05rem;
        color: var(--taxi-dark);
    }

    .btn-guest-book {
        background: var(--taxi-dark);
        color: var(--taxi-yellow);
        border: none;
        border-radius: 16px;
        padding: 16px;
        width: 100%;
        font-weight: 800;
        font-size: 1.05rem;
        cursor: pointer;
        transition: all 0.3s;
        font-family: 'Outfit', sans-serif;
    }

    .btn-guest-book:hover:not(:disabled) {
        background: #000;
        transform: scale(1.02);
    }

    .btn-guest-book:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    .guest-login-info {
        background: linear-gradient(135deg, #e8f5e9, #f1f8e9);
        border: 2px solid #a5d6a7;
        border-radius: 16px;
        padding: 16px 18px;
        margin-top: 16px;
    }

    .guest-login-info h6 {
        font-weight: 800;
        color: #1B5E20;
        margin-bottom: 8px;
        font-size: 0.88rem;
    }

    .guest-login-info .cred-box {
        background: #fff;
        border-radius: 10px;
        padding: 10px 14px;
        margin-bottom: 6px;
        font-size: 0.82rem;
        display: flex;
        justify-content: space-between;
    }

    .guest-login-info .cred-box strong {
        color: #1B5E20;
    }

    .guest-modal-close {
        position: absolute;
        top: 16px;
        right: 16px;
        background: #f5f5f5;
        border: none;
        width: 32px;
        height: 32px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: #888;
        font-size: 0.85rem;
        transition: all 0.2s;
    }

    .guest-modal-close:hover {
        background: #eee;
        color: #333;
    }

    /* Status bar */
    .status-bar {
        display: none;
        background: linear-gradient(135deg, #1B5E20, #2E7D32);
        color: #fff;
        border-radius: 16px;
        padding: 14px 18px;
        margin-bottom: 18px;
        font-size: 0.85rem;
        align-items: center;
        gap: 10px;
    }

    .status-bar.show {
        display: flex;
    }

    @media (max-width: 576px) {
        .taxi-container {
            padding: 10px 10px 0;
        }

        .booking-panel {
            padding: 28px 16px 30px;
        }

        .provider-tabs {
            gap: 6px;
        }

        .provider-tab {
            padding: 8px 12px;
            font-size: 0.78rem;
        }
    }
</style>

<div class="taxi-page">
    <div class="taxi-container">
        <!-- Map Area -->
        <div class="map-area" id="map-area">
            <div class="map-grid"></div>
            <div class="map-roads">
                <div class="map-road" style="top:40%;left:10%;width:80%;height:3px;"></div>
                <div class="map-road" style="top:20%;left:45%;width:3px;height:60%;"></div>
                <div class="map-road" style="top:60%;left:20%;width:60%;height:3px;transform:rotate(-15deg);"></div>
                <div class="map-road" style="top:30%;left:25%;width:3px;height:40%;transform:rotate(20deg);"></div>
            </div>
            <div class="map-car" style="top:25%;left:15%;animation-delay:0s;">🚕</div>
            <div class="map-car" style="top:55%;right:20%;animation-delay:2s;animation-direction:reverse;">🚕</div>
            <div class="map-car" style="top:35%;left:60%;animation-delay:4s;">🚗</div>
            <div class="map-pin">
                <i class="fas fa-map-marker-alt map-pin-icon"></i>
                <div class="map-pin-pulse"></div>
            </div>
            <div class="map-location-badge" id="map-location-text">
                <span class="detecting-spinner" id="loc-spinner"></span>
                <i class="fas fa-crosshairs" id="loc-icon" style="display:none;"></i>
                <span id="map-loc-label">Detecting location...</span>
            </div>
        </div>

        <!-- Booking Panel -->
        <div class="booking-panel">

            <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type'] === 'error' ? 'danger' : $flash['type']; ?> alert-dismissible fade show mb-3"
                    role="alert" style="border-radius:14px;font-size:0.85rem;">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (!$is_logged_in): ?>
                <div class="login-prompt">
                    <i class="fas fa-taxi" style="font-size:1.5rem;color:#F9A825;margin-bottom:8px;"></i>
                    <div style="font-weight:700;margin-bottom:4px;">Book Without Login!</div>
                    <div style="font-size:0.8rem;color:#888;margin-bottom:10px;">Select provider, destination & ride — then
                        enter your name & phone to book. We'll create your account automatically so you can track your ride
                        status!
                    </div>
                    <div style="font-size:0.72rem;color:#aaa;">Already have an account? <a
                            href="<?php echo BASE_URL; ?>/login.php" style="color:#1B5E20;font-weight:700;">Login here</a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Provider Selection -->
            <h6 class="fw-bold mb-2" style="font-size:0.78rem;color:#aaa;letter-spacing:1px;text-transform:uppercase;">
                Choose Provider</h6>
            <div class="provider-tabs" id="provider-tabs">
                <div class="provider-tab active-provider" data-provider="ride" data-base-rate="15" data-min-fare="80">
                    <div class="provider-logo" style="background:var(--ride-blue);">R</div>
                    <span>Ride</span>
                </div>
                <div class="provider-tab" data-provider="feres" data-base-rate="12" data-min-fare="60">
                    <div class="provider-logo" style="background:var(--feres-green);">F</div>
                    <span>Feres</span>
                </div>
                <div class="provider-tab" data-provider="yango" data-base-rate="14" data-min-fare="70">
                    <div class="provider-logo" style="background:var(--yango-red);">Y</div>
                    <span>Yango</span>
                </div>
                <?php foreach ($taxi_companies as $tc): ?>
                    <div class="provider-tab"
                        data-provider="<?php echo strtolower(str_replace(' ', '_', $tc['company_name'])); ?>"
                        data-base-rate="13" data-min-fare="70" data-company-id="<?php echo $tc['id']; ?>">
                        <div class="provider-logo" style="background:#555;">
                            <?php echo strtoupper(substr($tc['company_name'], 0, 1)); ?>
                        </div>
                        <span><?php echo htmlspecialchars($tc['company_name']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Location Input -->
            <h4 class="fw-bold mb-3" style="font-size:1.2rem;">Where to?</h4>
            <div class="location-card" style="position:relative;">
                <div class="d-flex align-items-stretch gap-2">
                    <div class="loc-indicator">
                        <div class="loc-dot loc-dot-pickup"></div>
                        <div class="loc-line-v"></div>
                        <div class="loc-dot loc-dot-dest"></div>
                    </div>
                    <div class="flex-grow-1">
                        <div class="loc-row">
                            <input type="text" class="loc-input" id="pickup-input" placeholder="Pickup location..."
                                value="" readonly style="cursor:pointer;">
                            <button class="loc-swap" id="swap-btn" title="Swap"><i
                                    class="fas fa-exchange-alt fa-rotate-90"></i></button>
                        </div>
                        <div style="height:1px;background:#eee;margin:2px 0;"></div>
                        <div class="loc-row" style="position:relative;">
                            <input type="text" class="loc-input" id="dest-input" placeholder="Where are you going?">
                        </div>
                    </div>
                </div>
                <div class="dest-suggestions" id="dest-suggestions"></div>
            </div>

            <!-- Status Bar -->
            <div class="status-bar" id="status-bar">
                <div class="detecting-spinner" style="border-top-color:#fff;border-color:rgba(255,255,255,0.3);"></div>
                <span id="status-text">Calculating fare...</span>
            </div>

            <!-- Ride Options -->
            <h6 class="fw-bold mb-3" id="ride-options-title" style="display:none;">Choose a ride</h6>
            <div id="ride-options-container" style="display:none;">
                <!-- Dynamically filled -->
            </div>

            <!-- Fare Breakdown -->
            <div class="fare-breakdown" id="fare-breakdown">
                <div class="fare-row">
                    <span>Base fare</span>
                    <span id="fb-base">—</span>
                </div>
                <div class="fare-row">
                    <span>Distance (<span id="fb-distance">0</span> km)</span>
                    <span id="fb-dist-cost">—</span>
                </div>
                <div class="fare-row">
                    <span>Service fee</span>
                    <span id="fb-service">—</span>
                </div>
                <div class="fare-row total">
                    <span>Total</span>
                    <span id="fb-total">—</span>
                </div>
            </div>

            <!-- Book Button -->
            <button class="btn-ride mt-3" id="btn-book" disabled>
                <span class="btn-text">Enter destination to continue</span>
            </button>
        </div>
    </div>
</div>

<!-- Guest Booking Modal (Bottom Sheet) -->
<div class="guest-modal-overlay" id="guest-modal">
    <div class="guest-modal" style="position:relative;">
        <button class="guest-modal-close" onclick="closeGuestModal()"><i class="fas fa-times"></i></button>
        <div class="guest-modal-handle"></div>

        <!-- Phase 1: Collect Info -->
        <div id="guest-form-phase">
            <h4><i class="fas fa-taxi" style="color:var(--taxi-yellow);margin-right:8px;"></i>Book Your Ride</h4>
            <p class="subtitle">Enter your details to confirm the booking</p>

            <div class="guest-ride-summary" id="guest-ride-summary">
                <div class="ride-icon-sm">🚕</div>
                <div class="ride-detail">
                    <div class="route" id="guest-route">Pickup → Destination</div>
                    <div class="meta" id="guest-ride-meta">Economy · Ride</div>
                </div>
                <div class="ride-fare" id="guest-fare">— ETB</div>
            </div>

            <div class="guest-field">
                <label><i class="fas fa-user me-1"></i> Full Name</label>
                <input type="text" id="guest-name" placeholder="e.g. Abebe Tadesse" <?php echo $is_logged_in ? 'value="' . htmlspecialchars(getCurrentUserName()) . '"' : ''; ?>>
            </div>
            <div class="guest-field">
                <label><i class="fas fa-phone me-1"></i> Phone Number</label>
                <input type="tel" id="guest-phone" placeholder="e.g. 0911 22 33 44">
            </div>

            <?php if (!$is_logged_in): ?>
                <div style="font-size:0.72rem;color:#999;margin-bottom:14px;text-align:center;">
                    <i class="fas fa-info-circle me-1"></i>
                    An account will be created for you automatically so you can track your ride status (accepted/rejected).
                </div>
            <?php endif; ?>

            <button class="btn-guest-book" id="btn-guest-confirm" onclick="confirmGuestBooking()">
                <i class="fas fa-check-circle me-2"></i>Confirm Booking
            </button>
        </div>

        <!-- Phase 2: Searching -->
        <div id="guest-searching-phase" style="display:none;text-align:center;padding:30px 0;">
            <div style="font-size:3.5rem;margin-bottom:12px;">🚕</div>
            <h4 class="fw-bold mb-2">Finding your driver...</h4>
            <p class="text-muted" style="font-size:0.85rem;">Matching you with nearby drivers</p>
            <div class="d-flex justify-content-center gap-2 my-4">
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite;">
                </div>
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite 0.2s;">
                </div>
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite 0.4s;">
                </div>
            </div>
        </div>

        <!-- Phase 3: Booked Successfully -->
        <div id="guest-success-phase" style="display:none;text-align:center;">
            <div style="font-size:3.5rem;margin-bottom:8px;">✅</div>
            <h4 class="fw-bold mb-1" style="color:#1B5E20;">Ride Booked!</h4>
            <p class="text-muted mb-0" style="font-size:0.82rem;">Your ride request has been sent to the driver</p>

            <div class="driver-card" style="text-align:left;">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="driver-avatar" id="driver-avatar">D</div>
                    <div>
                        <div class="fw-bold" id="driver-name-display">Driver Name</div>
                        <div style="font-size:0.78rem;color:#888;"><i class="fas fa-star text-warning"></i> 4.8 · 120+
                            trips</div>
                    </div>
                </div>
                <div class="d-flex gap-3" style="font-size:0.82rem;">
                    <div><i class="fas fa-car text-muted me-1"></i> <span id="driver-plate">AA-XXXX</span></div>
                    <div><i class="fas fa-clock text-muted me-1"></i> <span id="driver-eta">3 min</span></div>
                </div>
            </div>

            <div class="d-flex gap-3 mb-3" style="font-size:0.82rem;">
                <div class="flex-grow-1 text-start">
                    <div style="color:#999;font-size:0.72rem;">Pickup</div>
                    <div class="fw-bold" id="modal-pickup">—</div>
                </div>
                <div style="color:#ddd;align-self:center;"><i class="fas fa-arrow-right"></i></div>
                <div class="flex-grow-1 text-end">
                    <div style="color:#999;font-size:0.72rem;">Destination</div>
                    <div class="fw-bold" id="modal-dest">—</div>
                </div>
            </div>

            <div style="background:#fffde7;border-radius:14px;padding:14px;margin-bottom:16px;">
                <div style="font-size:0.72rem;color:#999;">Estimated Fare</div>
                <div class="fw-bold" style="font-size:1.5rem;color:var(--taxi-dark);" id="modal-fare">— ETB</div>
                <div style="font-size:0.72rem;color:#999;" id="modal-ride-label">Economy · Ride</div>
            </div>

            <div style="background:#f8f9fa;border-radius:14px;padding:12px;margin-bottom:12px;">
                <div style="font-size:0.72rem;color:#999;">Ride Reference</div>
                <div class="fw-bold" style="font-size:1.1rem;color:#1B5E20;letter-spacing:2px;" id="modal-ref">—</div>
                <div style="font-size:0.68rem;color:#aaa;margin-top:4px;">Status: <span
                        style="color:#FF8F00;font-weight:700;">Pending Driver Approval</span></div>
            </div>

            <!-- Auto-created account info -->
            <div id="guest-account-info" class="guest-login-info" style="display:none;text-align:left;">
                <h6><i class="fas fa-user-check me-2"></i>Your Account Created!</h6>
                <p style="font-size:0.75rem;color:#555;margin-bottom:10px;">Login to track if your ride is
                    <strong>accepted</strong> or <strong>rejected</strong>:
                </p>
                <div class="cred-box">
                    <span>Username</span>
                    <strong id="auto-username">—</strong>
                </div>
                <div class="cred-box">
                    <span>Password</span>
                    <strong id="auto-password">—</strong>
                </div>
                <p style="font-size:0.68rem;color:#888;margin-top:8px;margin-bottom:0;"><i
                        class="fas fa-exclamation-triangle me-1"></i>Please save these credentials. You can change your
                    password after login.</p>
            </div>

            <div class="d-flex gap-2 mt-3">
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn-guest-book"
                    style="background:#1B5E20;color:#fff;text-decoration:none;display:flex;align-items:center;justify-content:center;"
                    id="btn-login-track">
                    <i class="fas fa-sign-in-alt me-2"></i>Login & Track
                </a>
                <button class="btn-guest-book" onclick="closeGuestModal()" style="background:#f5f5f5;color:#333;">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Ride Booking Modal (for logged in users) -->
<div class="ride-modal-overlay" id="ride-modal">
    <div class="ride-modal">
        <div id="modal-searching">
            <div style="font-size:3.5rem;margin-bottom:12px;">🚕</div>
            <h4 class="fw-bold mb-2">Finding your driver...</h4>
            <p class="text-muted" style="font-size:0.85rem;">Matching you with nearby drivers</p>
            <div class="d-flex justify-content-center gap-2 my-4">
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite;">
                </div>
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite 0.2s;">
                </div>
                <div
                    style="width:12px;height:12px;border-radius:50%;background:var(--taxi-yellow);animation:dotPulse 1.4s ease-in-out infinite 0.4s;">
                </div>
            </div>
        </div>
        <div id="modal-found-loggedin" style="display:none;">
            <div style="font-size:3.5rem;margin-bottom:8px;">✅</div>
            <h4 class="fw-bold mb-1" style="color:#1B5E20;">Ride Booked!</h4>
            <p class="text-muted mb-0" style="font-size:0.82rem;">Your ride request has been sent</p>
            <div style="background:#f8f9fa;border-radius:14px;padding:12px;margin:16px 0;">
                <div style="font-size:0.72rem;color:#999;">Reference</div>
                <div class="fw-bold" style="font-size:1.1rem;color:#1B5E20;letter-spacing:2px;" id="modal-ref-loggedin">
                    —</div>
                <div style="font-size:0.68rem;color:#aaa;margin-top:4px;">Status: <span
                        style="color:#FF8F00;font-weight:700;">Pending Driver Approval</span></div>
            </div>
            <div class="d-flex gap-2">
                <a href="track_order.php" class="btn-ride"
                    style="font-size:0.95rem;padding:14px;text-decoration:none;display:flex;align-items:center;justify-content:center;background:#1B5E20;">
                    <i class="fas fa-map-marker-alt me-2"></i>Track Ride
                </a>
                <button class="btn-ride" onclick="closeRideModal()"
                    style="font-size:0.95rem;padding:14px;background:#f5f5f5;color:#333;">
                    Done
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes dotPulse {

        0%,
        100% {
            transform: scale(0.6);
            opacity: 0.4;
        }

        50% {
            transform: scale(1.2);
            opacity: 1;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const baseUrl = '<?php echo BASE_URL; ?>';
        const isLoggedIn = <?php echo $is_logged_in ? 'true' : 'false'; ?>;

        // === Addis Ababa All Known Locations ===
        const popularDestinations = [
            // ===== SUB-CITIES & GENERAL AREAS (First for quick selection) =====
            { name: 'Bole Sub-City', area: 'Bole', icon: 'fas fa-map-marker-alt', km: 7 },
            { name: 'Piazza (Arada Sub-City)', area: 'Arada', icon: 'fas fa-map-marker-alt', km: 5 },
            { name: 'Mercato (Addis Ketema)', area: 'Addis Ketema', icon: 'fas fa-store', km: 6 },
            { name: 'Kirkos Sub-City', area: 'Kirkos', icon: 'fas fa-building', km: 4 },
            { name: 'Yeka Sub-City (Megenagna)', area: 'Yeka', icon: 'fas fa-map-pin', km: 9 },
            { name: 'Lideta Sub-City', area: 'Lideta', icon: 'fas fa-landmark', km: 5 },
            { name: 'Gulele Sub-City', area: 'Gulele', icon: 'fas fa-mountain', km: 7 },
            { name: 'Kolfe Keranio Sub-City', area: 'Kolfe', icon: 'fas fa-home', km: 10 },
            { name: 'Nifas Silk Lafto Sub-City', area: 'Nifas Silk', icon: 'fas fa-city', km: 8 },
            { name: 'Akaki Kality Sub-City', area: 'Akaki', icon: 'fas fa-industry', km: 14 },

            // ===== AIRPORTS & TRANSPORT =====
            { name: 'Bole International Airport (Terminal 2)', area: 'Bole', icon: 'fas fa-plane', km: 8 },
            { name: 'Bole International Airport (Terminal 1)', area: 'Bole', icon: 'fas fa-plane', km: 7 },
            { name: 'Lamberet Bus Station', area: 'Yeka', icon: 'fas fa-bus', km: 9 },
            { name: 'Autobus Tera', area: 'Mercato', icon: 'fas fa-bus', km: 7 },
            { name: 'Meskel Square LRT Station', area: 'Kirkos', icon: 'fas fa-train', km: 5 },
            { name: 'Ayat LRT Station', area: 'Yeka', icon: 'fas fa-train', km: 14 },
            { name: 'Torhailoch LRT Station', area: 'Kolfe', icon: 'fas fa-train', km: 9 },
            { name: 'Menelik II Square LRT', area: 'Arada', icon: 'fas fa-train', km: 5 },
            { name: 'Stadium LRT Station', area: 'Kirkos', icon: 'fas fa-train', km: 4 },
            { name: 'Kality LRT Station', area: 'Akaki Kality', icon: 'fas fa-train', km: 13 },

            // ===== LANDMARKS & SQUARES =====
            { name: 'Meskel Square', area: 'Kirkos', icon: 'fas fa-landmark', km: 5 },
            { name: 'Mexico Square', area: 'Lideta', icon: 'fas fa-landmark', km: 5 },
            { name: 'Arat Kilo', area: 'Arada', icon: 'fas fa-landmark', km: 4 },
            { name: 'Sidist Kilo', area: 'Arada', icon: 'fas fa-landmark', km: 5 },
            { name: 'Sar Bet (Piazza)', area: 'Arada', icon: 'fas fa-landmark', km: 6 },
            { name: 'Leghar', area: 'Kirkos', icon: 'fas fa-landmark', km: 4 },
            { name: 'De Gaulle Square', area: 'Piazza', icon: 'fas fa-landmark', km: 5 },
            { name: 'Tewodros Square', area: 'Arada', icon: 'fas fa-landmark', km: 5 },
            { name: 'Menelik II Square', area: 'Arada', icon: 'fas fa-landmark', km: 5 },
            { name: 'Churchill Avenue', area: 'Arada', icon: 'fas fa-road', km: 5 },
            { name: 'Bole Road', area: 'Bole', icon: 'fas fa-road', km: 7 },
            { name: 'Africa Avenue (Bole Road)', area: 'Bole', icon: 'fas fa-road', km: 6 },

            // ===== HOTELS =====
            { name: 'Sheraton Addis', area: 'Taitu St., Kirkos', icon: 'fas fa-hotel', km: 4 },
            { name: 'Hilton Hotel', area: 'Kirkos', icon: 'fas fa-hotel', km: 5 },
            { name: 'Hyatt Regency', area: 'Meskel Square', icon: 'fas fa-hotel', km: 5 },
            { name: 'Radisson Blu', area: 'Kazanchis', icon: 'fas fa-hotel', km: 4 },
            { name: 'Capital Hotel & Spa', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Eliana Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Golden Tulip Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Getfam Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 6 },
            { name: 'Atlas Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 6 },
            { name: 'Jupiter Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Harmony Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 8 },
            { name: 'Best Western Plus', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Intercontinental Hotel', area: 'Kazanchis', icon: 'fas fa-hotel', km: 4 },
            { name: 'Moka Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 7 },
            { name: 'Desalegn Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 8 },
            { name: 'Monarch Hotel', area: 'Kazanchis', icon: 'fas fa-hotel', km: 4 },
            { name: 'Saro Maria Hotel', area: 'Bole', icon: 'fas fa-hotel', km: 8 },

            // ===== SHOPPING & MALLS =====
            { name: 'Mercato', area: 'Addis Ketema', icon: 'fas fa-store', km: 7 },
            { name: 'Edna Mall', area: 'Bole', icon: 'fas fa-shopping-bag', km: 6 },
            { name: 'Dembel City Center', area: 'Kazanchis', icon: 'fas fa-shopping-bag', km: 4 },
            { name: 'Friendship Mall', area: 'Bole', icon: 'fas fa-shopping-bag', km: 7 },
            { name: 'Zefmesh Grand Mall', area: 'Lebu', icon: 'fas fa-shopping-bag', km: 12 },
            { name: 'Shoa Shopping Center', area: 'Piazza', icon: 'fas fa-shopping-bag', km: 5 },
            { name: 'Bambis Supermarket', area: 'Bole', icon: 'fas fa-shopping-cart', km: 7 },
            { name: 'SafeWay Supermarket', area: 'Megenagna', icon: 'fas fa-shopping-cart', km: 9 },
            { name: 'Gotera Condominium Market', area: 'Nifas Silk', icon: 'fas fa-shopping-cart', km: 7 },
            { name: 'Sheger Mall', area: 'Bole Bulbula', icon: 'fas fa-shopping-bag', km: 10 },

            // ===== CHURCHES & MOSQUES =====
            { name: 'Bole Medhanialem Church', area: 'Bole', icon: 'fas fa-church', km: 7 },
            { name: 'Holy Trinity Cathedral', area: 'Arat Kilo', icon: 'fas fa-church', km: 4 },
            { name: 'St. George Cathedral', area: 'Piazza', icon: 'fas fa-church', km: 5 },
            { name: 'Medhane Alem Cathedral', area: 'Bole', icon: 'fas fa-church', km: 7 },
            { name: 'Kidist Mariam Church', area: 'Entoto', icon: 'fas fa-church', km: 10 },
            { name: 'Entoto Maryam Church', area: 'Entoto', icon: 'fas fa-church', km: 11 },
            { name: 'Urael Church', area: 'Kazanchis', icon: 'fas fa-church', km: 4 },
            { name: 'Anwar Mosque (Grand Mosque)', area: 'Mercato', icon: 'fas fa-mosque', km: 6 },
            { name: 'Mesjid Al-Negashi', area: 'Arada', icon: 'fas fa-mosque', km: 5 },
            { name: 'Raguel Church', area: 'Entoto', icon: 'fas fa-church', km: 10 },
            { name: 'Yeka Mikael Church', area: 'Yeka', icon: 'fas fa-church', km: 9 },
            { name: 'Lideta Mariam Church', area: 'Lideta', icon: 'fas fa-church', km: 5 },
            { name: 'Kechene Medhane Alem', area: 'Gulele', icon: 'fas fa-church', km: 6 },

            // ===== UNIVERSITIES & SCHOOLS =====
            { name: 'Addis Ababa University (4 Kilo)', area: '4 Kilo', icon: 'fas fa-graduation-cap', km: 4 },
            { name: 'AAU - Sidist Kilo Campus', area: 'Sidist Kilo', icon: 'fas fa-graduation-cap', km: 5 },
            { name: 'AAU - Technology (AAiT)', area: 'Arat Kilo', icon: 'fas fa-graduation-cap', km: 4 },
            { name: 'AAU - Commerce Campus', area: 'Lideta', icon: 'fas fa-graduation-cap', km: 5 },
            { name: 'St. Mary\'s University', area: 'Bole', icon: 'fas fa-graduation-cap', km: 8 },
            { name: 'Unity University', area: 'Bole', icon: 'fas fa-graduation-cap', km: 7 },
            { name: 'Addis Ababa Science & Tech', area: 'Kality', icon: 'fas fa-graduation-cap', km: 13 },
            { name: 'Kotebe Metropolitan Univ.', area: 'Kotebe', icon: 'fas fa-graduation-cap', km: 10 },
            { name: 'Ethiopian Civil Service Univ.', area: 'Sidist Kilo', icon: 'fas fa-graduation-cap', km: 5 },
            { name: 'Rift Valley University', area: 'Bole', icon: 'fas fa-graduation-cap', km: 7 },
            { name: 'International Leadership Academy', area: 'CMC', icon: 'fas fa-school', km: 11 },
            { name: 'Sandford International School', area: 'Old Airport', icon: 'fas fa-school', km: 5 },
            { name: 'Lycee Franco-Ethiopien', area: 'Kazanchis', icon: 'fas fa-school', km: 4 },
            { name: 'British International School', area: 'Bole', icon: 'fas fa-school', km: 8 },

            // ===== HOSPITALS & HEALTH =====
            { name: 'Tikur Anbessa (Black Lion) Hospital', area: 'Sidist Kilo', icon: 'fas fa-hospital', km: 5 },
            { name: 'St. Paul\'s Hospital', area: 'Gulele', icon: 'fas fa-hospital', km: 7 },
            { name: 'Zewditu Memorial Hospital', area: 'Kirkos', icon: 'fas fa-hospital', km: 4 },
            { name: 'Yekatit 12 Hospital', area: 'Piazza', icon: 'fas fa-hospital', km: 5 },
            { name: 'Alert Hospital', area: 'Kolfe', icon: 'fas fa-hospital', km: 8 },
            { name: 'Bethel Teaching Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 7 },
            { name: 'Korean Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 8 },
            { name: 'Hayat Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 7 },
            { name: 'Lancet General Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 8 },
            { name: 'St. Gabriel Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 7 },
            { name: 'Girum General Hospital', area: 'Megenagna', icon: 'fas fa-hospital', km: 9 },
            { name: 'Myungsung Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 8 },
            { name: 'MCM Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 7 },
            { name: 'Kadisco General Hospital', area: 'Bole', icon: 'fas fa-hospital', km: 9 },

            // ===== GOVERNMENT & EMBASSIES =====
            { name: 'National Palace (Jubilee)', area: 'Arat Kilo', icon: 'fas fa-building-columns', km: 4 },
            { name: 'City Hall (Addis Ababa Municipality)', area: 'Piazza', icon: 'fas fa-city', km: 5 },
            { name: 'Parliament (Menelik Palace)', area: 'Arat Kilo', icon: 'fas fa-building-columns', km: 4 },
            { name: 'African Union HQ', area: 'Kirkos', icon: 'fas fa-globe-africa', km: 5 },
            { name: 'UN Economic Commission (UNECA)', area: 'Arat Kilo', icon: 'fas fa-globe', km: 4 },
            { name: 'US Embassy', area: 'Entoto Road', icon: 'fas fa-flag', km: 5 },
            { name: 'British Embassy', area: 'Kirkos', icon: 'fas fa-flag', km: 4 },
            { name: 'Chinese Embassy', area: 'Bole', icon: 'fas fa-flag', km: 7 },
            { name: 'Kenyan Embassy', area: 'Kirkos', icon: 'fas fa-flag', km: 4 },
            { name: 'Immigration Office', area: 'Kirkos', icon: 'fas fa-passport', km: 4 },
            { name: 'Federal Police Commission', area: 'Sidist Kilo', icon: 'fas fa-shield-alt', km: 5 },
            { name: 'Ministry of Foreign Affairs', area: 'Arat Kilo', icon: 'fas fa-building', km: 4 },

            // ===== PARKS & RECREATION =====
            { name: 'Unity Park', area: 'Arat Kilo', icon: 'fas fa-tree', km: 4 },
            { name: 'Entoto Park', area: 'Gulele', icon: 'fas fa-tree', km: 10 },
            { name: 'Friendship Park', area: 'Kazanchis', icon: 'fas fa-tree', km: 3 },
            { name: 'Sheger Park', area: 'Bole', icon: 'fas fa-tree', km: 6 },
            { name: 'Bihere Tsige Park', area: 'Mexico', icon: 'fas fa-tree', km: 5 },
            { name: 'Jan Meda Race Ground', area: 'Sidist Kilo', icon: 'fas fa-horse', km: 5 },
            { name: 'Ghion Swimming Pool', area: 'Arat Kilo', icon: 'fas fa-swimming-pool', km: 4 },
            { name: 'Yaya Village', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Village Addis', area: 'Bole', icon: 'fas fa-utensils', km: 7 },

            // ===== MUSEUMS & CULTURE =====
            { name: 'National Museum', area: 'Arada', icon: 'fas fa-university', km: 4 },
            { name: 'Ethnological Museum', area: 'Sidist Kilo', icon: 'fas fa-university', km: 5 },
            { name: 'Red Terror Museum', area: 'Meskel Square', icon: 'fas fa-university', km: 5 },
            { name: 'Addis Ababa Museum', area: 'Arada', icon: 'fas fa-university', km: 5 },
            { name: 'National Theatre', area: 'Piazza', icon: 'fas fa-theater-masks', km: 5 },
            { name: 'Hager Fikir Theatre', area: 'Piazza', icon: 'fas fa-theater-masks', km: 5 },
            { name: 'Institute of Ethiopian Studies', area: 'Sidist Kilo', icon: 'fas fa-book', km: 5 },

            // ===== STADIUMS =====
            { name: 'Addis Ababa Stadium', area: 'Kirkos', icon: 'fas fa-futbol', km: 4 },
            { name: 'Yidnekachew Tessema Stadium', area: 'Kirkos', icon: 'fas fa-futbol', km: 4 },

            // ===== POPULAR AREAS / NEIGHBORHOODS =====
            { name: 'Piazza', area: 'Arada', icon: 'fas fa-map-marker-alt', km: 6 },
            { name: 'Kazanchis', area: 'Kirkos', icon: 'fas fa-building', km: 3 },
            { name: 'Bole', area: 'Bole', icon: 'fas fa-map-marker-alt', km: 7 },
            { name: 'Megenagna', area: 'Yeka', icon: 'fas fa-map-pin', km: 9 },
            { name: 'CMC', area: 'Yeka', icon: 'fas fa-map-pin', km: 11 },
            { name: 'Ayat', area: 'Yeka', icon: 'fas fa-home', km: 14 },
            { name: 'Summit', area: 'Bole', icon: 'fas fa-mountain', km: 10 },
            { name: 'Sarbet', area: 'Nifas Silk', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Gotera', area: 'Nifas Silk', icon: 'fas fa-map-pin', km: 7 },
            { name: 'Lebu', area: 'Nifas Silk', icon: 'fas fa-map-pin', km: 12 },
            { name: 'Jemo', area: 'Kolfe', icon: 'fas fa-home', km: 11 },
            { name: 'Torhailoch', area: 'Kolfe', icon: 'fas fa-map-pin', km: 9 },
            { name: 'Kotebe', area: 'Yeka', icon: 'fas fa-map-pin', km: 10 },
            { name: 'Bambis', area: 'Bole', icon: 'fas fa-map-pin', km: 7 },
            { name: '22 Mazoria', area: 'Yeka', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Kera', area: 'Kirkos', icon: 'fas fa-map-pin', km: 4 },
            { name: 'Saris', area: 'Akaki Kality', icon: 'fas fa-map-pin', km: 10 },
            { name: 'Saris Abo', area: 'Akaki Kality', icon: 'fas fa-map-pin', km: 11 },
            { name: 'Akaki', area: 'Akaki Kality', icon: 'fas fa-map-pin', km: 15 },
            { name: 'Kality', area: 'Akaki Kality', icon: 'fas fa-map-pin', km: 13 },
            { name: 'Bole Bulbula', area: 'Bole', icon: 'fas fa-map-pin', km: 10 },
            { name: 'Bole Arabsa', area: 'Bole', icon: 'fas fa-map-pin', km: 12 },
            { name: 'Bole Rufael', area: 'Bole', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Bole Atlas', area: 'Bole', icon: 'fas fa-map-pin', km: 6 },
            { name: 'Old Airport Area', area: 'Kirkos', icon: 'fas fa-map-pin', km: 5 },
            { name: 'Gerji', area: 'Bole', icon: 'fas fa-home', km: 9 },
            { name: 'Gerji Mebrat Hail', area: 'Bole', icon: 'fas fa-home', km: 10 },
            { name: 'Gerji Imperial', area: 'Bole', icon: 'fas fa-home', km: 9 },
            { name: 'Wolo Sefer', area: 'Bole', icon: 'fas fa-map-pin', km: 6 },
            { name: 'Olympia', area: 'Bole', icon: 'fas fa-map-pin', km: 6 },
            { name: 'Urael', area: 'Kirkos', icon: 'fas fa-map-pin', km: 4 },
            { name: 'Congo', area: 'Bole', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Aware', area: 'Yeka', icon: 'fas fa-map-pin', km: 9 },
            { name: 'Ferensay Legasion', area: 'Kirkos', icon: 'fas fa-map-pin', km: 4 },
            { name: 'Filwuha', area: 'Kirkos', icon: 'fas fa-map-pin', km: 4 },
            { name: 'Asko', area: 'Kolfe Keranio', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Addisu Gebeya', area: 'Gulele', icon: 'fas fa-store', km: 7 },
            { name: 'Shiro Meda', area: 'Gulele', icon: 'fas fa-store', km: 7 },
            { name: 'Shola', area: 'Gulele', icon: 'fas fa-map-pin', km: 6 },
            { name: 'Teklehaimanot', area: 'Addis Ketema', icon: 'fas fa-map-pin', km: 5 },
            { name: 'Abnet', area: 'Arada', icon: 'fas fa-map-pin', km: 5 },
            { name: 'Kebena', area: 'Arada', icon: 'fas fa-map-pin', km: 4 },
            { name: 'Gulele', area: 'Gulele', icon: 'fas fa-map-pin', km: 7 },
            { name: 'Kolfe', area: 'Kolfe Keranio', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Lideta', area: 'Lideta', icon: 'fas fa-map-pin', km: 5 },
            { name: 'Nifas Silk', area: 'Nifas Silk Lafto', icon: 'fas fa-map-pin', km: 7 },
            { name: 'Lafto', area: 'Nifas Silk Lafto', icon: 'fas fa-map-pin', km: 8 },
            { name: 'Gelan', area: 'Akaki Kality', icon: 'fas fa-map-pin', km: 16 },
            { name: 'Dukem', area: 'Oromia', icon: 'fas fa-map-pin', km: 25 },
            { name: 'Bishoftu (Debre Zeit)', area: 'Oromia', icon: 'fas fa-map-pin', km: 45 },
            { name: 'Sendafa', area: 'Oromia', icon: 'fas fa-map-pin', km: 30 },
            { name: 'Sululta', area: 'Oromia', icon: 'fas fa-map-pin', km: 22 },
            { name: 'Burayu', area: 'Oromia', icon: 'fas fa-map-pin', km: 15 },
            { name: 'Lege Tafo', area: 'Oromia', icon: 'fas fa-map-pin', km: 18 },
            { name: 'Sebeta', area: 'Oromia', icon: 'fas fa-map-pin', km: 20 },

            // ===== MARKETS =====
            { name: 'Shola Market', area: 'Gulele', icon: 'fas fa-store', km: 6 },
            { name: 'Piazza Market', area: 'Arada', icon: 'fas fa-store', km: 6 },
            { name: 'Atiklt Tera (Vegetable Market)', area: 'Mercato', icon: 'fas fa-leaf', km: 7 },
            { name: 'Addis Merkato (Big Market)', area: 'Addis Ketema', icon: 'fas fa-store', km: 7 },
            { name: 'Bole Mini Market', area: 'Bole', icon: 'fas fa-store', km: 7 },

            // ===== INDUSTRIAL & BUSINESS =====
            { name: 'Bole Lemi Industrial Park', area: 'Bole', icon: 'fas fa-industry', km: 12 },
            { name: 'Kirkos Sub-City Office', area: 'Kirkos', icon: 'fas fa-building', km: 4 },
            { name: 'ECA (Economic Commission for Africa)', area: 'Arat Kilo', icon: 'fas fa-globe', km: 4 },
            { name: 'Commercial Bank of Ethiopia HQ', area: 'Arat Kilo', icon: 'fas fa-university', km: 4 },
            { name: 'Ethiopian Airlines HQ', area: 'Bole', icon: 'fas fa-plane', km: 8 },
            { name: 'Ethio Telecom HQ', area: 'Kirkos', icon: 'fas fa-phone', km: 4 },
            { name: 'Century Mall', area: 'Bole', icon: 'fas fa-shopping-bag', km: 7 },

            // ===== CONDOMINIUM SITES =====
            { name: 'Jemo 1 Condominium', area: 'Kolfe', icon: 'fas fa-building', km: 11 },
            { name: 'Jemo 2 Condominium', area: 'Kolfe', icon: 'fas fa-building', km: 12 },
            { name: 'Jemo 3 Condominium', area: 'Kolfe', icon: 'fas fa-building', km: 13 },
            { name: 'Ayat Condominium', area: 'Yeka', icon: 'fas fa-building', km: 14 },
            { name: 'CMC Condominium', area: 'Yeka', icon: 'fas fa-building', km: 11 },
            { name: 'Gotera Condominium', area: 'Nifas Silk', icon: 'fas fa-building', km: 7 },
            { name: 'Bole Bulbula Condominium', area: 'Bole', icon: 'fas fa-building', km: 10 },
            { name: 'Summit Condominium', area: 'Bole', icon: 'fas fa-building', km: 10 },
            { name: 'Koye Feche Condominium', area: 'Nifas Silk', icon: 'fas fa-building', km: 12 },
            { name: 'Kilinto Condominium', area: 'Akaki Kality', icon: 'fas fa-building', km: 15 },
            { name: 'Tuludimtu Condominium', area: 'Bole', icon: 'fas fa-building', km: 11 },

            // ===== RESTAURANTS & DINING =====
            { name: 'Yod Abyssinia', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Kategna Restaurant', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Lucy Restaurant', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Totot Restaurant', area: 'Kazanchis', icon: 'fas fa-utensils', km: 4 },
            { name: 'Four Seasons Restaurant', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Gusto Restaurant', area: 'Bole', icon: 'fas fa-utensils', km: 7 },
            { name: 'Lime Tree Cafe', area: 'Bole', icon: 'fas fa-coffee', km: 7 },
            { name: 'Tomoca Coffee', area: 'Piazza', icon: 'fas fa-coffee', km: 5 },
            { name: 'Kaldi\'s Coffee', area: 'Bole', icon: 'fas fa-coffee', km: 7 },

            // ===== BANKS & FINANCIAL =====
            { name: 'CBE Main Branch', area: 'Arat Kilo', icon: 'fas fa-university', km: 4 },
            { name: 'Dashen Bank HQ', area: 'Bole', icon: 'fas fa-university', km: 7 },
            { name: 'Awash Bank HQ', area: 'Bole', icon: 'fas fa-university', km: 7 },
            { name: 'Bank of Abyssinia HQ', area: 'Bole', icon: 'fas fa-university', km: 7 },

            // ===== OTHER NOTABLE =====
            { name: 'Entoto Mountain', area: 'Gulele', icon: 'fas fa-mountain', km: 12 },
            { name: 'Yeka Hill', area: 'Yeka', icon: 'fas fa-mountain', km: 10 },
            { name: 'Taitu Hotel (Historical)', area: 'Piazza', icon: 'fas fa-hotel', km: 5 },
            { name: 'Addis Ababa Post Office', area: 'Piazza', icon: 'fas fa-envelope', km: 5 },
            { name: 'Telecommunications Museum', area: 'Kirkos', icon: 'fas fa-phone', km: 4 },
            { name: 'Lion Zoo (near Sidist Kilo)', area: 'Sidist Kilo', icon: 'fas fa-paw', km: 5 },
        ];

        // Ride types per provider
        const rideTypes = {
            ride: [
                { type: 'economy', name: 'Economy', icon: 'fas fa-taxi', color: '#FFD600', bgColor: '#fffde7', seats: 4, multiplier: 1.0 },
                { type: 'premium', name: 'Premium', icon: 'fas fa-car-side', color: '#1565C0', bgColor: '#e3f2fd', seats: 4, multiplier: 1.8 },
                { type: 'van', name: 'Van', icon: 'fas fa-shuttle-van', color: '#e53935', bgColor: '#ffebee', seats: 7, multiplier: 2.5 },
            ],
            feres: [
                { type: 'feres_mini', name: 'Feres Mini', icon: 'fas fa-car', color: '#1B5E20', bgColor: '#e8f5e9', seats: 4, multiplier: 0.9 },
                { type: 'feres_plus', name: 'Feres Plus', icon: 'fas fa-car-side', color: '#2E7D32', bgColor: '#e8f5e9', seats: 4, multiplier: 1.5 },
                { type: 'feres_xl', name: 'Feres XL', icon: 'fas fa-shuttle-van', color: '#43A047', bgColor: '#e8f5e9', seats: 7, multiplier: 2.2 },
            ],
            yango: [
                { type: 'yango_start', name: 'Start', icon: 'fas fa-car', color: '#D32F2F', bgColor: '#ffebee', seats: 4, multiplier: 0.85 },
                { type: 'yango_comfort', name: 'Comfort', icon: 'fas fa-car-side', color: '#C62828', bgColor: '#ffebee', seats: 4, multiplier: 1.6 },
                { type: 'yango_xl', name: 'XL', icon: 'fas fa-shuttle-van', color: '#B71C1C', bgColor: '#ffebee', seats: 6, multiplier: 2.3 },
            ],
        };

        // State
        let currentProvider = 'ride';
        let currentRideType = null;
        let pickupLocation = '';
        let destLocation = '';
        let distanceKm = 0;
        let currentFare = 0;

        // Elements
        const pickupInput = document.getElementById('pickup-input');
        const destInput = document.getElementById('dest-input');
        const destSuggestions = document.getElementById('dest-suggestions');
        const rideOptionsContainer = document.getElementById('ride-options-container');
        const rideOptionsTitle = document.getElementById('ride-options-title');
        const btnBook = document.getElementById('btn-book');
        const fareBreakdown = document.getElementById('fare-breakdown');
        const statusBar = document.getElementById('status-bar');
        const mapLocLabel = document.getElementById('map-loc-label');
        const locSpinner = document.getElementById('loc-spinner');
        const locIcon = document.getElementById('loc-icon');

        // === 1. Geolocation Detection ===
        function detectLocation() {
            if ('geolocation' in navigator) {
                // Show detecting state
                locSpinner.style.display = 'inline-block';
                locIcon.style.display = 'none';
                mapLocLabel.textContent = 'Seeking current location...';

                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        const lat = pos.coords.latitude;
                        const lon = pos.coords.longitude;
                        // Reverse geocode using coordinate to guess area name
                        const areaName = guessAreaFromCoords(lat, lon);
                        pickupLocation = areaName;
                        pickupInput.value = areaName;
                        mapLocLabel.textContent = areaName;
                        locSpinner.style.display = 'none';
                        locIcon.style.display = 'inline-block';

                        // Small visual bounce effect on map pin when found
                        const pin = document.querySelector('.map-pin-icon');
                        if (pin) {
                            pin.style.animation = 'none';
                            void pin.offsetWidth;
                            pin.style.animation = 'pinBounce 0.5s ease-in-out 3';
                        }
                    },
                    (err) => {
                        console.error('Geo error:', err);
                        // Fallback using IP or center
                        const fallback = 'Piazza, Addis Ababa';
                        pickupLocation = fallback;
                        pickupInput.value = fallback;
                        mapLocLabel.textContent = fallback;
                        locSpinner.style.display = 'none';
                        locIcon.style.display = 'inline-block';
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 60000 }
                );
            } else {
                pickupLocation = 'Addis Ababa Center';
                pickupInput.value = 'Addis Ababa Center';
                mapLocLabel.textContent = 'Addis Ababa Center';
                locSpinner.style.display = 'none';
                locIcon.style.display = 'inline-block';
            }
        }

        function guessAreaFromCoords(lat, lon) {
            // Addis Ababa rough area guess based on lat/lon
            const areas = [
                { name: 'Bole', lat: 8.9806, lon: 38.7578, r: 0.02 },
                { name: 'Kazanchis', lat: 9.0166, lon: 38.7611, r: 0.015 },
                { name: 'Meskel Square Area', lat: 9.0107, lon: 38.7612, r: 0.012 },
                { name: 'Piazza', lat: 9.0268, lon: 38.7465, r: 0.015 },
                { name: 'Megenagna', lat: 9.0203, lon: 38.7989, r: 0.015 },
                { name: 'Merkato Area', lat: 9.0369, lon: 38.7333, r: 0.018 },
                { name: '4 Kilo', lat: 9.0337, lon: 38.7636, r: 0.012 },
                { name: 'CMC Area', lat: 9.0412, lon: 38.8201, r: 0.02 },
                { name: 'Sarbet', lat: 8.9903, lon: 38.7349, r: 0.015 },
            ];
            let closest = 'Addis Ababa';
            let minDist = Infinity;
            areas.forEach(a => {
                const d = Math.sqrt(Math.pow(lat - a.lat, 2) + Math.pow(lon - a.lon, 2));
                if (d < a.r && d < minDist) { minDist = d; closest = a.name; }
            });
            return closest + ', Addis Ababa';
        }

        detectLocation();

        // === 2. Provider Tabs ===
        document.querySelectorAll('.provider-tab').forEach(tab => {
            tab.addEventListener('click', function () {
                document.querySelectorAll('.provider-tab').forEach(t => t.classList.remove('active-provider'));
                this.classList.add('active-provider');
                currentProvider = this.dataset.provider;
                if (destLocation) {
                    showRideOptions();
                }
            });
        });

        // === 3. Destination Input with Suggestions ===
        destInput.addEventListener('focus', function () {
            showSuggestions(this.value);
        });

        destInput.addEventListener('input', function () {
            showSuggestions(this.value);
        });

        function showSuggestions(query) {
            query = query.toLowerCase().trim();
            let filtered = popularDestinations;
            if (query.length > 0) {
                filtered = popularDestinations.filter(d =>
                    d.name.toLowerCase().includes(query) ||
                    d.area.toLowerCase().includes(query)
                );
            }

            if (filtered.length === 0) {
                destSuggestions.innerHTML = `
                <div style="padding:20px;text-align:center;color:#aaa;font-size:0.85rem;">
                    <i class="fas fa-search" style="font-size:1.2rem;display:block;margin-bottom:6px;"></i>
                    No matching places found. Try typing a sub-city like "Bole" or "Piazza".
                </div>`;
            } else {
                // If query is empty, show a "Popular in Addis" header
                const headerHtml = query ? '' : '<div style="padding:10px 18px; font-size:0.7rem; font-weight:800; color:#aaa; text-transform:uppercase; letter-spacing:1px; background:#fcfcfc; border-bottom:1px solid #eee;">Popuar in Addis Ababa</div>';

                destSuggestions.innerHTML = headerHtml + filtered.slice(0, 12).map(d => `
                <div class="dest-item" data-name="${d.name}" data-km="${d.km}" data-area="${d.area}">
                    <div class="dest-item-icon"><i class="${d.icon}"></i></div>
                    <div class="flex-grow-1">
                        <div class="dest-item-name">${highlightText(d.name, query)}</div>
                        <div class="dest-item-area">${d.area} · ~${d.km} km</div>
                    </div>
                    <div style="font-size:0.6rem; color:#bbb; font-weight:700;">${d.km}km</div>
                </div>
            `).join('');
            }

            destSuggestions.classList.add('show');

            // Attach click handlers
            destSuggestions.querySelectorAll('.dest-item').forEach(item => {
                item.addEventListener('click', function () {
                    destInput.value = this.dataset.name;
                    destLocation = this.dataset.name;
                    distanceKm = parseFloat(this.dataset.km) || 5;
                    destSuggestions.classList.remove('show');
                    showRideOptions();
                });
            });
        }

        function highlightText(text, query) {
            if (!query) return text;
            const regex = new RegExp(`(${query.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            return text.replace(regex, '<strong style="color:#1B5E20;">$1</strong>');
        }

        // Close suggestions on outside click
        document.addEventListener('click', function (e) {
            if (!destInput.contains(e.target) && !destSuggestions.contains(e.target)) {
                destSuggestions.classList.remove('show');
            }
        });

        // Allow custom destinations
        destInput.addEventListener('keydown', function (e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                if (this.value.trim()) {
                    destLocation = this.value.trim();
                    distanceKm = 5 + Math.random() * 10; // random estimate
                    destSuggestions.classList.remove('show');
                    showRideOptions();
                }
            }
        });

        // === 4. Swap locations ===
        document.getElementById('swap-btn').addEventListener('click', function () {
            const temp = pickupInput.value;
            pickupInput.value = destInput.value;
            destInput.value = temp;
            const tempLoc = pickupLocation;
            pickupLocation = destLocation;
            destLocation = tempLoc;
            if (destLocation) showRideOptions();
        });

        // === 5. Show Ride Options ===
        function showRideOptions() {
            pickupLocation = pickupInput.value || 'Addis Ababa';
            const provider = currentProvider;
            // Get provider config
            const activeTab = document.querySelector('.provider-tab.active-provider');
            const baseRate = parseFloat(activeTab?.dataset.baseRate || 15);
            const minFare = parseFloat(activeTab?.dataset.minFare || 80);

            // Determine ride types — use known providers or default
            let types = rideTypes[provider] || rideTypes['ride'];

            let html = '';
            types.forEach((rt, i) => {
                const baseFare = 30;
                const distCost = distanceKm * baseRate * rt.multiplier;
                const serviceFee = 15;
                let fare = Math.round(baseFare + distCost + serviceFee);
                if (fare < minFare) fare = minFare;

                const etaMin = Math.floor(2 + Math.random() * 8);

                html += `
                <div class="ride-option ${i === 0 ? 'active' : ''}" 
                     data-type="${rt.type}" data-fare="${fare}" data-name="${rt.name}"
                     data-base-fare="30" data-dist-cost="${Math.round(distCost)}" data-service-fee="15">
                    <div class="ride-icon" style="background:${rt.bgColor};color:${rt.color};">
                        <i class="${rt.icon}"></i>
                    </div>
                    <div class="ride-info">
                        <div class="ride-name">${rt.name}</div>
                        <div class="ride-meta">${etaMin} min away · ${rt.seats} seats</div>
                    </div>
                    <div class="text-end">
                        <div class="ride-price">${fare} ETB</div>
                    </div>
                </div>`;
            });

            rideOptionsContainer.innerHTML = html;
            rideOptionsContainer.style.display = 'block';
            rideOptionsTitle.style.display = 'block';

            // Select first by default
            selectRide(rideOptionsContainer.querySelector('.ride-option'));

            // Click handlers
            rideOptionsContainer.querySelectorAll('.ride-option').forEach(opt => {
                opt.addEventListener('click', function () {
                    rideOptionsContainer.querySelectorAll('.ride-option').forEach(o => o.classList.remove('active'));
                    this.classList.add('active');
                    selectRide(this);
                });
            });
        }

        function selectRide(el) {
            if (!el) return;
            currentRideType = el.dataset.type;
            currentFare = parseFloat(el.dataset.fare);
            const rideName = el.dataset.name;

            // Update fare breakdown
            document.getElementById('fb-base').textContent = el.dataset.baseFare + ' ETB';
            document.getElementById('fb-distance').textContent = distanceKm.toFixed(1);
            document.getElementById('fb-dist-cost').textContent = el.dataset.distCost + ' ETB';
            document.getElementById('fb-service').textContent = el.dataset.serviceFee + ' ETB';
            document.getElementById('fb-total').textContent = currentFare + ' ETB';
            fareBreakdown.classList.add('show');

            // Update button
            btnBook.disabled = false;
            btnBook.querySelector('.btn-text').innerHTML = `<i class="fas fa-taxi me-2"></i> Book ${rideName} · ${currentFare} ETB`;
        }

        // === 6. Book Ride ===
        btnBook.addEventListener('click', function () {
            if (!destLocation) {
                alert('Please enter a destination.');
                return;
            }

            if (!isLoggedIn) {
                // Show guest booking modal
                showGuestModal();
                return;
            }

            // Logged-in user - book directly
            bookRideLoggedIn();
        });

        // === Guest Modal Functions ===
        function showGuestModal() {
            const modal = document.getElementById('guest-modal');
            document.getElementById('guest-form-phase').style.display = 'block';
            document.getElementById('guest-searching-phase').style.display = 'none';
            document.getElementById('guest-success-phase').style.display = 'none';

            // Fill summary
            document.getElementById('guest-route').textContent =
                (pickupLocation || pickupInput.value || 'Pickup') + ' → ' + destLocation;
            document.getElementById('guest-ride-meta').textContent =
                capitalize(currentRideType || 'economy') + ' · ' + capitalize(currentProvider);
            document.getElementById('guest-fare').textContent = currentFare + ' ETB';

            modal.classList.add('active');
        }
        window.showGuestModal = showGuestModal;

        function confirmGuestBooking() {
            const name = document.getElementById('guest-name').value.trim();
            const phone = document.getElementById('guest-phone').value.trim();

            if (!name) {
                document.getElementById('guest-name').style.borderColor = '#e53935';
                document.getElementById('guest-name').focus();
                return;
            }
            if (!phone || phone.length < 9) {
                document.getElementById('guest-phone').style.borderColor = '#e53935';
                document.getElementById('guest-phone').focus();
                return;
            }

            // Reset borders
            document.getElementById('guest-name').style.borderColor = '#eee';
            document.getElementById('guest-phone').style.borderColor = '#eee';

            // Show searching
            document.getElementById('guest-form-phase').style.display = 'none';
            document.getElementById('guest-searching-phase').style.display = 'block';
            document.getElementById('btn-guest-confirm').disabled = true;

            // Send booking
            const formData = new FormData();
            formData.append('book_ride', '1');
            formData.append('pickup_location', pickupLocation || pickupInput.value);
            formData.append('destination', destLocation);
            formData.append('ride_type', currentRideType || 'economy');
            formData.append('provider', currentProvider);
            formData.append('fare', currentFare);
            formData.append('passenger_name', name);
            formData.append('passenger_phone', phone);

            fetch('taxi.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    setTimeout(() => {
                        document.getElementById('guest-searching-phase').style.display = 'none';
                        document.getElementById('guest-success-phase').style.display = 'block';

                        if (data.success) {
                            document.getElementById('driver-name-display').textContent = data.driver_name || 'Assigned Driver';
                            document.getElementById('driver-plate').textContent = data.vehicle_plate || 'AA-XXXX';
                            document.getElementById('driver-avatar').textContent = (data.driver_name || 'D')[0];
                            document.getElementById('driver-eta').textContent = Math.floor(2 + Math.random() * 6) + ' min';
                            document.getElementById('modal-pickup').textContent = pickupLocation || pickupInput.value;
                            document.getElementById('modal-dest').textContent = destLocation;
                            document.getElementById('modal-fare').textContent = data.fare + ' ETB';
                            document.getElementById('modal-ride-label').textContent =
                                capitalize(data.ride_type || 'economy') + ' · ' + capitalize(data.provider || currentProvider);
                            document.getElementById('modal-ref').textContent = data.ride_ref || '—';

                            // Show auto-registered account info
                            if (data.auto_registered && data.login_username && data.login_password) {
                                document.getElementById('guest-account-info').style.display = 'block';
                                document.getElementById('auto-username').textContent = data.login_username;
                                document.getElementById('auto-password').textContent = data.login_password;
                            }
                        }

                        document.getElementById('btn-guest-confirm').disabled = false;
                    }, 2500);
                })
                .catch(err => {
                    document.getElementById('guest-searching-phase').style.display = 'none';
                    document.getElementById('guest-form-phase').style.display = 'block';
                    document.getElementById('btn-guest-confirm').disabled = false;
                    alert('Failed to book ride. Please try again.');
                });
        }
        window.confirmGuestBooking = confirmGuestBooking;

        // Logged-in user booking
        function bookRideLoggedIn() {
            btnBook.disabled = true;
            btnBook.querySelector('.btn-text').innerHTML = '<div class="detecting-spinner" style="border-top-color:var(--taxi-yellow);border-color:rgba(255,214,0,0.3);"></div> Booking...';

            const modal = document.getElementById('ride-modal');
            document.getElementById('modal-searching').style.display = 'block';
            document.getElementById('modal-found-loggedin').style.display = 'none';
            modal.classList.add('active');

            const formData = new FormData();
            formData.append('book_ride', '1');
            formData.append('pickup_location', pickupLocation || pickupInput.value);
            formData.append('destination', destLocation);
            formData.append('ride_type', currentRideType || 'economy');
            formData.append('provider', currentProvider);
            formData.append('fare', currentFare);
            formData.append('passenger_name', '<?php echo $is_logged_in ? addslashes(getCurrentUserName()) : ''; ?>');
            formData.append('passenger_phone', '');

            fetch('taxi.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            })
                .then(res => res.json())
                .then(data => {
                    setTimeout(() => {
                        document.getElementById('modal-searching').style.display = 'none';
                        document.getElementById('modal-found-loggedin').style.display = 'block';

                        if (data.success) {
                            document.getElementById('modal-ref-loggedin').textContent = data.ride_ref || '—';
                        }

                        btnBook.disabled = false;
                        btnBook.querySelector('.btn-text').innerHTML = `<i class="fas fa-taxi me-2"></i> Book Ride`;
                    }, 2500);
                })
                .catch(err => {
                    modal.classList.remove('active');
                    btnBook.disabled = false;
                    btnBook.querySelector('.btn-text').innerHTML = `<i class="fas fa-taxi me-2"></i> Book Ride`;
                    alert('Failed to book ride. Please try again.');
                });
        }

        function capitalize(str) {
            return str.charAt(0).toUpperCase() + str.slice(1);
        }
    });

    function closeRideModal() {
        document.getElementById('ride-modal').classList.remove('active');
    }

    function closeGuestModal() {
        document.getElementById('guest-modal').classList.remove('active');
    }


</script>

<?php include('../includes/footer.php'); ?>