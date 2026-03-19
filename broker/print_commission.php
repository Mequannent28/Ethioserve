<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';
requireRole(['broker', 'property_owner']);

$ref_id = (int)($_GET['id'] ?? 0);
$user_id = getCurrentUserId();

// Fetch referral details and ensure visibility (must be broker or owner)
$stmt = $pdo->prepare("
    SELECT r.*, 
           COALESCE(o.total_amount, rp.amount, 0) as total_amount,
           COALESCE(u_order.full_name, u_rent.full_name, 'Guest') as customer_name,
           b_user.full_name as agent_name,
           b_user.phone as agent_phone,
           CASE WHEN r.request_id IS NOT NULL THEN 'House/Car Rental' ELSE 'Marketplace Order' END as ref_type,
           COALESCE(l.title, 'Market Purchase') as item_name,
           owner_u.full_name as owner_name,
           owner_u.phone as owner_phone
    FROM referrals r
    JOIN brokers b ON r.broker_id = b.id
    JOIN users b_user ON b.user_id = b_user.id
    LEFT JOIN rental_requests rr ON r.request_id = rr.id
    LEFT JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users owner_u ON l.user_id = owner_u.id
    LEFT JOIN orders o ON r.order_id = o.id
    LEFT JOIN users u_order ON o.customer_id = u_order.id
    LEFT JOIN users u_rent ON rr.customer_id = u_rent.id
    LEFT JOIN rental_payment_proofs rp ON r.request_id = rp.request_id AND rp.status = 'confirmed'
    WHERE r.id = ? AND (r.broker_id = (SELECT id FROM brokers WHERE user_id = ?) OR l.user_id = ?)
");
$stmt->execute([$ref_id, $user_id, $user_id]);
$ref = $stmt->fetch();

if (!$ref || $ref['status'] !== 'paid') {
    die("Commission not found or not yet approved.");
}

// Get total paid earnings for this broker
$stmt = $pdo->prepare("SELECT SUM(commission_amount) FROM referrals WHERE broker_id = ? AND status = 'paid'");
$stmt->execute([$ref['broker_id']]);
$total_paid = $stmt->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Commission Voucher - <?php echo $ref['id']; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Poppins', sans-serif; background: #fff; padding: 20px; margin: 0; }
        .voucher-container {
            width: 100%; max-width: 900px; margin: 0 auto; background: #fff; padding: 40px;
            box-shadow: none; border-radius: 8px;
            position: relative; overflow: hidden;
            border: 2px solid #1B5E20;
        }
        .watermark {
            position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 8rem; color: rgba(27, 94, 32, 0.05); font-weight: 900;
            pointer-events: none; white-space: nowrap; z-index: 0;
        }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid #1B5E20; padding-bottom: 20px; margin-bottom: 30px; }
        .logo h2 { margin: 0; color: #1B5E20; font-size: 2rem; font-weight: 800; }
        .logo span { color: #F9A825; }
        .doc-title { text-align: right; }
        .doc-title h1 { margin: 0; font-size: 1.5rem; text-transform: uppercase; letter-spacing: 2px; }
        
        .content-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 40px; position: relative; z-index: 1; }
        .info-box h4 { margin: 0 0 10px; color: #666; text-transform: uppercase; font-size: 0.8rem; letter-spacing: 1px; }
        .info-box p { margin: 0; font-weight: 600; font-size: 1.1rem; color: #333; }

        .payment-summary {
            background: #f9f9f9; padding: 30px; border-radius: 12px;
            border-left: 10px solid #F9A825; margin-bottom: 40px;
        }
        .amount-row { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .amount-row.total { border-top: 1px solid #ddd; padding-top: 10px; margin-top: 10px; font-weight: 700; font-size: 1.4rem; color: #1B5E20; }

        .stamps { display: flex; justify-content: space-between; margin-top: 60px; }
        .stamp-box { width: 200px; text-align: center; border-top: 1px solid #333; padding-top: 10px; font-size: 0.8rem; }
        
        .official-seal {
            position: absolute; bottom: 80px; right: 50px;
            width: 120px; height: 120px; border: 4px double #EF5350;
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            color: #EF5350; font-weight: 800; font-size: 0.9rem; text-align: center;
            transform: rotate(-15deg); opacity: 0.8;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            body { background: #fff; padding: 0; margin: 0; }
            .voucher-container { 
                box-shadow: none; 
                border-radius: 0; 
                border: none;
                width: 210mm; 
                height: 297mm; 
                padding: 20mm;
                margin: 0;
            }
            .no-print { display: none; }
        }
        .no-print-btn { background: #1B5E20; color: #fff; border: none; padding: 12px 30px; border-radius: 40px; font-weight: 600; cursor: pointer; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div style="text-align: center;" class="no-print">
        <button class="no-print-btn" onclick="window.print()"><i class="fas fa-print"></i> Print Voucher</button>
    </div>

    <div class="voucher-container">
        <div class="watermark">ETHIOSERVE APPROVED</div>
        
        <div class="header">
            <div class="logo">
                <h2>Ethio<span>Serve</span></h2>
                <p style="margin: 0; font-size: 0.7rem; color: #888;">Modern Real Estate & Marketplace Solutions</p>
            </div>
            <div class="doc-title">
                <h1>Commission Voucher</h1>
                <p style="margin: 0; font-size: 0.9rem; color: #555;">No: #VO-<?php echo str_pad($ref['id'], 6, '0', STR_PAD_LEFT); ?></p>
                <p style="margin: 0; font-size: 0.8rem; color: #888;">Date: <?php echo date('M d, Y'); ?></p>
            </div>
        </div>

        <div class="content-grid">
            <div class="info-box">
                <h4>Recipient (Agent)</h4>
                <p><?php echo htmlspecialchars($ref['agent_name']); ?></p>
                <p style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($ref['agent_phone']); ?></p>
            </div>
            <div class="info-box">
                <h4>Referred Transaction</h4>
                <p><?php echo htmlspecialchars($ref['item_name']); ?></p>
                <p style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($ref['customer_name']); ?> (Customer)</p>
            </div>
            <div class="info-box">
                <h4>Property/Listing Owner</h4>
                <p><?php echo htmlspecialchars($ref['owner_name'] ?? 'EthioServe Marketplace'); ?></p>
                <p style="font-size: 0.85rem; color: #777;"><?php echo htmlspecialchars($ref['owner_phone'] ?? ''); ?></p>
            </div>
            <div class="info-box">
                <h4>Transaction Type</h4>
                <p><?php echo $ref['ref_type']; ?></p>
            </div>
        </div>

        <div class="payment-summary">
            <div class="amount-row">
                <span>Transaction Amount:</span>
                <span><?php echo number_format($ref['total_amount']); ?> ETB</span>
            </div>
            <div class="amount-row">
                <span>Commission Rate:</span>
                <span><?php echo ($ref['ref_type'] === 'House/Car Rental' ? '15%' : 'Market Standard'); ?></span>
            </div>
            <div class="amount-row total">
                <span>Voucher Amount:</span>
                <span><?php echo number_format($ref['commission_amount']); ?> ETB</span>
            </div>
            <div style="margin-top: 15px; font-size: 0.8rem; color: #444;">
                <strong>History:</strong> This agent has earned a total of <strong><?php echo number_format($total_paid); ?> ETB</strong> in verified commissions from our platform.
            </div>
        </div>

        <p style="font-size: 0.8rem; color: #666; margin-bottom: 60px;">
            This document serves as an official confirmation of the referral service provided. The listing owner acknowledges that the above-mentioned agent has successfully connected a verified customer to the service and is entitled to the commission amount specified.
        </p>

        <div class="official-seal">
            AUTHORIZED<br>COMMISSION<br>PAYMENT
        </div>

        <div class="stamps">
            <div class="stamp-box">
                Property Owner Signature
            </div>
            <div class="stamp-box">
                Agent's Acknowledgement
            </div>
            <div class="stamp-box">
                Admin Approval Seal
            </div>
        </div>

        <div style="margin-top: 40px; text-align: center; border-top: 1px dashed #eee; padding-top: 20px; font-size: 0.72rem; color: #aaa;">
            EthioServe Inc. | Addis Ababa, Ethiopia | Verified Electronic Receipt
        </div>
    </div>
</body>
</html>
