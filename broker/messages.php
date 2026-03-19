<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole(['broker', 'property_owner']);

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Fetch all requests where this owner's listings are involved, grouped by latest message or request
$stmt = $pdo->prepare("
    SELECT rr.*, l.title as listing_title, l.type as listing_type, l.image_url as listing_img,
           u.full_name as customer_name_db, (SELECT COUNT(*) FROM rental_chat_messages WHERE request_id = rr.id AND receiver_id = ? AND is_read = 0) as unread_count,
           (SELECT message FROM rental_chat_messages WHERE request_id = rr.id ORDER BY created_at DESC LIMIT 1) as last_msg,
           (SELECT created_at FROM rental_chat_messages WHERE request_id = rr.id ORDER BY created_at DESC LIMIT 1) as last_msg_time
    FROM rental_requests rr
    JOIN listings l ON rr.listing_id = l.id
    LEFT JOIN users u ON rr.customer_id = u.id
    WHERE l.user_id = ?
    ORDER BY COALESCE(last_msg_time, rr.created_at) DESC
");
$stmt->execute([$user_id, $user_id]);
$conversations = $stmt->fetchAll();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Owner Portal</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body { background: #f0f2f5; font-family: 'Poppins', sans-serif; }
        .dashboard-wrapper { display: flex; width: 100%; }
        .main-content { margin-left: 260px; padding: 32px; width: 100%; min-height: 100vh; }
        .msg-card { 
            background: #fff; border-radius: 16px; border: none; transition: 0.3s; 
            cursor: pointer; text-decoration: none; color: inherit; display: block;
            border: 1px solid transparent;
        }
        .msg-card:hover { border-color: #1B5E20; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0,0,0,0.05); }
        .msg-card.unread { background: #E8F5E9; border-left: 4px solid #1B5E20; }
        .unread-badge { background: #1B5E20; color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; }
        @media (max-width: 991px) { .main-content { margin-left: 0; } }
    </style>
</head>
<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_broker.php'); ?>
        <div class="main-content">
            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-1">Messages</h2>
                    <p class="text-muted">Direct communication with potential customers.</p>
                </div>
            </div>

            <?php if (empty($conversations)): ?>
                <div class="text-center py-5 bg-white rounded-4 shadow-sm">
                    <i class="fas fa-comments fs-1 opacity-25 mb-3"></i>
                    <p>No messages yet. When customers inquire about your listings, they will appear here.</p>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($conversations as $conv): ?>
                        <div class="col-12">
                            <a href="chat.php?request_id=<?php echo $conv['id']; ?>" class="msg-card p-4 shadow-sm <?php echo $conv['unread_count'] > 0 ? 'unread' : ''; ?>">
                                <div class="row align-items-center g-3">
                                    <div class="col-auto">
                                        <img src="<?php echo $conv['listing_img'] ?: 'https://images.unsplash.com/photo-1560518883-ce09059eeffa?w=80'; ?>" 
                                             class="rounded-3 shadow-sm" style="width:60px; height:60px; object-fit: cover;">
                                    </div>
                                    <div class="col">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="fw-bold mb-0 text-dark"><?php echo htmlspecialchars($conv['customer_name_db'] ?: $conv['customer_name']); ?></h6>
                                            <small class="text-muted"><?php echo time_ago($conv['last_msg_time'] ?: $conv['created_at']); ?></small>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <p class="text-muted small mb-0 text-truncate" style="max-width: 80%;">
                                                <i class="fas fa-home me-1"></i> <?php echo htmlspecialchars($conv['listing_title']); ?>:
                                                <?php echo htmlspecialchars($conv['last_msg'] ?: 'Opened an inquiry. Click to start chatting.'); ?>
                                            </p>
                                            <?php if ($conv['unread_count'] > 0): ?>
                                                <span class="unread-badge"><?php echo $conv['unread_count']; ?> New</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
