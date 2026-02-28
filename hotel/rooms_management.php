<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Auth Check
requireRole('hotel');
$user_id = getCurrentUserId();

// Get hotel for this user
$stmt = $pdo->prepare("SELECT id, name FROM hotels WHERE user_id = ?");
$stmt->execute([$user_id]);
$hotel = $stmt->fetch();
if (!$hotel)
    die("Hotel record not found.");
$hotel_id = $hotel['id'];

// Get current rooms
$stmt = $pdo->prepare("
    SELECT * FROM hotel_rooms 
    WHERE hotel_id = ? 
    ORDER BY room_number
");
$stmt->execute([$hotel_id]);
$rooms = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Management - EthioServe</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background-color: #f0f2f5;
        }

        .main-content {
            padding: 30px;
            min-height: 100vh;
        }

        .room-card {
            transition: all 0.3s ease;
            border-radius: 15px;
            overflow: hidden;
        }

        .room-card:hover {
            transform: translateY(-5px);
        }

        .btn-premium {
            background: linear-gradient(135deg, #1B5E20, #2E7D32);
            color: white;
            border: none;
            transition: all 0.3s;
        }

        .btn-premium:hover {
            transform: scale(1.05);
            color: #FFB300;
        }

        .badge-available {
            background-color: #e8f5e9;
            color: #2e7d32;
        }

        .badge-occupied {
            background-color: #ffebee;
            color: #c62828;
        }

        .badge-maintenance {
            background-color: #fff3e0;
            color: #ef6c00;
        }
    </style>
</head>

<body>
    <div class="d-flex">
        <?php include('../includes/sidebar_hotel.php'); ?>

        <main class="main-content flex-grow-1">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Room Management</h2>
                    <p class="text-muted">Manage your hotel's rooms and availability</p>
                </div>
                <button class="btn btn-premium rounded-pill px-4 shadow-sm" data-bs-toggle="modal"
                    data-bs-target="#addRoomModal">
                    <i class="fas fa-plus me-2"></i>Add New Room
                </button>
            </div>

            <div class="row g-4">
                <?php if (empty($rooms)): ?>
                    <div class="col-12 text-center py-5">
                        <div class="bg-white p-5 rounded-4 shadow-sm">
                            <i class="fas fa-bed text-muted mb-3" style="font-size: 4rem;"></i>
                            <h4 class="text-muted">No rooms added yet</h4>
                            <p>Start by adding your first room to show on the platform.</p>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($rooms as $room): ?>
                        <div class="col-md-4 col-lg-3">
                            <div class="card h-100 border-0 shadow-sm room-card">
                                <?php if ($room['image_url']): ?>
                                    <img src="<?php echo htmlspecialchars($room['image_url']); ?>" class="card-img-top" alt="Room"
                                        style="height: 160px; object-fit: cover;">
                                <?php else: ?>
                                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                        <i class="fas fa-image text-muted fs-1"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h5 class="fw-bold mb-0">Room
                                            <?php echo htmlspecialchars($room['room_number']); ?>
                                        </h5>
                                        <span class="badge rounded-pill <?php
                                        echo match ($room['status']) {
                                            'available' => 'badge-available',
                                            'occupied' => 'badge-occupied',
                                            'maintenance' => 'badge-maintenance',
                                            default => 'bg-secondary'
                                        };
                                        ?>">
                                            <?php echo ucfirst($room['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-primary-green fw-bold mb-1">
                                        <?php echo number_format($room['price_per_night']); ?> ETB / night
                                    </p>
                                    <p class="text-muted small mb-3">
                                        <?php echo htmlspecialchars($room['room_type']); ?>
                                    </p>
                                    <div class="d-flex gap-2 mt-auto">
                                        <button class="btn btn-sm btn-outline-primary rounded-pill flex-grow-1 edit-btn"
                                            data-room='<?php echo json_encode($room); ?>' data-bs-toggle="modal"
                                            data-bs-target="#editRoomModal">
                                            <i class="fas fa-edit me-1"></i> Edit
                                        </button>
                                        <a href="process_rooms.php?action=delete&id=<?php echo $room['id']; ?>"
                                            class="btn btn-sm btn-outline-danger rounded-pill"
                                            onclick="return confirm('Delete this room?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Add Room Modal -->
    <div class="modal fade" id="addRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="process_rooms.php?action=add" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow-lg rounded-4">
                <?php echo csrfField(); ?>
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Add New Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Room Number</label>
                            <input type="text" name="room_number" class="form-control rounded-3" required
                                placeholder="e.g. 101">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Price per Night</label>
                            <input type="number" name="price_per_night" class="form-control rounded-3" required
                                placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Room Type</label>
                        <select name="room_type" class="form-select rounded-3" required>
                            <option value="Single Room">Single Room</option>
                            <option value="Double Room">Double Room</option>
                            <option value="Deluxe Suite">Deluxe Suite</option>
                            <option value="Presidential Suite">Presidential Suite</option>
                            <option value="Standard Room">Standard Room</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" class="form-control rounded-3" rows="3"
                            placeholder="Features, view, etc."></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Room Photo</label>
                        <input type="file" name="room_image" class="form-control rounded-3" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium rounded-pill px-4">Create Room</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div class="modal fade" id="editRoomModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <form action="process_rooms.php?action=edit" method="POST" enctype="multipart/form-data"
                class="modal-content border-0 shadow-lg rounded-4">
                <?php echo csrfField(); ?>
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">Edit Room</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Room Number</label>
                            <input type="text" name="room_number" id="edit_room_number" class="form-control rounded-3"
                                required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Price per Night</label>
                            <input type="number" name="price_per_night" id="edit_price" class="form-control rounded-3"
                                required>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Room Type</label>
                            <select name="room_type" id="edit_room_type" class="form-select rounded-3" required>
                                <option value="Single Room">Single Room</option>
                                <option value="Double Room">Double Room</option>
                                <option value="Deluxe Suite">Deluxe Suite</option>
                                <option value="Presidential Suite">Presidential Suite</option>
                                <option value="Standard Room">Standard Room</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Status</label>
                            <select name="status" id="edit_status" class="form-select rounded-3" required>
                                <option value="available">Available</option>
                                <option value="occupied">Occupied</option>
                                <option value="maintenance">Maintenance</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description</label>
                        <textarea name="description" id="edit_description" class="form-control rounded-3"
                            rows="3"></textarea>
                    </div>
                    <div class="mb-0">
                        <label class="form-label small fw-bold">Update Photo (Optional)</label>
                        <input type="file" name="room_image" class="form-control rounded-3" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light rounded-pill px-4"
                        data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-premium rounded-pill px-4">Update Room</button>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const room = JSON.parse(btn.getAttribute('data-room'));
                document.getElementById('edit_id').value = room.id;
                document.getElementById('edit_room_number').value = room.room_number;
                document.getElementById('edit_price').value = room.price_per_night;
                document.getElementById('edit_room_type').value = room.room_type;
                document.getElementById('edit_description').value = room.description;
                document.getElementById('edit_status').value = room.status;
            });
        });
    </script>
</body>

</html>