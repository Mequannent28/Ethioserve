<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

$hotel_id = isset($_GET['id']) ? (int) $_GET['id'] : 1;

// Fetch hotel details
$stmt = $pdo->prepare("SELECT * FROM hotels WHERE id = ?");
$stmt->execute([$hotel_id]);
$hotel = $stmt->fetch();

if (!$hotel) {
    // If hotel not found, redirect to home
    header("Location: index.php");
    exit();
}

// Fetch menu items for this hotel
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name FROM menu_items m LEFT JOIN categories c ON m.category_id = c.id WHERE m.hotel_id = ? AND m.is_available = 1 ORDER BY c.id, m.name");
$stmt->execute([$hotel_id]);
$menu_items = $stmt->fetchAll();

// Group items by category
$grouped_items = [];
foreach ($menu_items as $item) {
    $cat = $item['category_name'] ?? 'Popular';
    $grouped_items[$cat][] = $item;
}

include('../includes/header.php');
?>

<!-- Restaurant Banner -->
<div class="restaurant-banner position-relative overflow-hidden" style="height: 300px;">
    <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?auto=format&fit=crop&w=1500&q=80'); ?>"
        class="w-100 h-100" style="object-fit: cover; filter: brightness(0.6);">
    <div class="position-absolute bottom-0 start-0 p-5 text-white w-100 d-flex justify-content-between align-items-end">
        <div>
            <h1 class="display-5 fw-bold mb-1"><?php echo htmlspecialchars($hotel['name']); ?></h1>
            <p class="mb-0 fs-5">
                <i
                    class="fas fa-map-marker-alt me-2 text-warning"></i><?php echo htmlspecialchars($hotel['location']); ?>
                |
                <i class="fas fa-star text-warning"></i> <?php echo number_format($hotel['rating'], 1); ?>
                <?php if ($hotel['cuisine_type']): ?> |
                    <?php echo htmlspecialchars($hotel['cuisine_type']); ?><?php endif; ?>
            </p>
            <p class="mb-0 mt-2">
                <i class="fas fa-clock me-2"></i><?php echo htmlspecialchars($hotel['opening_hours'] ?? 'Open Now'); ?>
                |
                <i
                    class="fas fa-motorcycle me-2"></i><?php echo htmlspecialchars($hotel['delivery_time'] ?? '30-45 min'); ?>
                |
                <i class="fas fa-shopping-bag me-2"></i>Min: <?php echo number_format($hotel['min_order']); ?> ETB
                <?php if (!empty($hotel['phone'])): ?>
                    |
                    <a href="tel:<?php echo htmlspecialchars($hotel['phone']); ?>" class="text-white text-decoration-none">
                        <i class="fas fa-phone-alt me-2"></i><?php echo htmlspecialchars($hotel['phone']); ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <div class="mb-2 d-flex flex-column gap-2">
            <a href="booking.php?hotel_id=<?php echo $hotel_id; ?>"
                class="btn btn-warning btn-lg rounded-pill px-5 fw-bold shadow">
                <i class="fas fa-calendar-check me-2"></i>Book Room / Table
            </a>
            <?php if (!empty($hotel['phone'])): ?>
                <a href="tel:<?php echo htmlspecialchars($hotel['phone']); ?>"
                    class="btn btn-success btn-lg rounded-pill px-5 fw-bold shadow">
                    <i class="fas fa-phone-alt me-2"></i>Call Restaurant
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<main class="container py-5">
    <div class="row">
        <!-- Menu Categories Sidebar (for desktop) -->
        <div class="col-lg-3 d-none d-lg-block">
            <div class="sticky-top" style="top: 100px;">
                <h5 class="fw-bold mb-4">Categories</h5>
                <div class="list-group list-group-flush rounded-4 overflow-hidden border shadow-sm">
                    <?php foreach (array_keys($grouped_items) as $index => $category): ?>
                        <a href="#cat-<?php echo strtolower(str_replace(' ', '-', $category)); ?>"
                            class="list-group-item list-group-item-action py-3 <?php echo $index === 0 ? 'active' : ''; ?>">
                            <?php echo htmlspecialchars($category); ?>
                        </a>
                    <?php endforeach; ?>
                </div>

                <div class="card bg-primary-green text-white mt-4 p-4 rounded-4 border-0">
                    <h6 class="fw-bold"><i class="fas fa-user-tag me-2"></i>Broker Offer!</h6>
                    <p class="small mb-3">Share this restaurant with your referral code and earn 5% commission on every
                        order.</p>
                    <button class="btn btn-warning btn-sm rounded-pill w-100 fw-bold" onclick="copyLink()">
                        <i class="fas fa-share-alt me-2"></i>Share Restaurant
                    </button>
                </div>
            </div>
        </div>

        <!-- Food Items Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0">Menu</h3>
                <div class="input-group w-50 shadow-sm rounded-pill overflow-hidden">
                    <span class="input-group-text bg-white border-0 ps-3">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input type="text" class="form-control border-0 px-2" id="menuSearch"
                        placeholder="Search for food...">
                    <button class="btn btn-primary-green px-4 fw-bold" id="searchBtn">
                        Search
                    </button>
                </div>
            </div>

            <?php if (empty($menu_items)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-utensils text-muted mb-3" style="font-size: 4rem;"></i>
                    <h4 class="text-muted">No menu items available</h4>
                    <p class="text-muted">This restaurant hasn't added any items yet.</p>
                </div>
            <?php else: ?>
                <?php foreach ($grouped_items as $category => $items): ?>
                    <div class="category-section mb-5" id="cat-<?php echo strtolower(str_replace(' ', '-', $category)); ?>">
                        <h4 class="fw-bold mb-4 text-primary-green d-flex align-items-center">
                            <i class="fas fa-utensils me-2"></i><?php echo htmlspecialchars($category); ?>
                            <span class="badge bg-light text-muted ms-3 fs-6 fw-normal"><?php echo count($items); ?> items</span>
                        </h4>
                        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-4 g-4">
                            <?php foreach ($items as $item): ?>
                                <div class="col menu-item" 
                                     data-name="<?php echo strtolower($item['name']); ?>"
                                     data-desc="<?php echo strtolower($item['description'] ?? ''); ?>">
                                    <div class="card border-0 shadow-sm h-100 hover-lift overflow-hidden rounded-4">
                                        <div class="position-relative">
                                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1548943487-a2e4e43b4853?auto=format&fit=crop&w=400&q=80'); ?>"
                                                class="card-img-top" style="height: 160px; object-fit: cover;"
                                                alt="<?php echo htmlspecialchars($item['name']); ?>">
                                            <div class="position-absolute top-0 end-0 p-2">
                                                <span class="badge bg-white text-primary-green shadow-sm rounded-pill">
                                                    <?php echo number_format($item['price']); ?> ETB
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-body p-3 d-flex flex-column">
                                            <h6 class="fw-bold mb-1 text-truncate" title="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php echo htmlspecialchars($item['name']); ?>
                                            </h6>
                                            <p class="text-muted smaller mb-3 flex-grow-1" style="font-size: 0.75rem; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; min-height: 2.2rem;">
                                                <?php echo htmlspecialchars($item['description'] ?? 'Delicious authentic dish freshly prepared.'); ?>
                                            </p>
                                            <button class="btn btn-primary-green btn-sm rounded-pill w-100 fw-bold"
                                                onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo htmlspecialchars(addslashes($item['name'])); ?>', <?php echo $item['price']; ?>, '<?php echo htmlspecialchars($hotel_id); ?>', '<?php echo htmlspecialchars(addslashes($hotel['name'])); ?>', '<?php echo htmlspecialchars($item['image_url'] ?? ''); ?>')">
                                                <i class="fas fa-plus me-1"></i> Add to Cart
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

<!-- Floating Cart Button -->
<a href="cart.php" id="cartButton"
    class="position-fixed bottom-0 end-0 m-4 btn btn-primary-green btn-lg rounded-pill shadow-lg p-3 z-3 d-flex align-items-center gap-3"
    style="display: none;">
    <div class="bg-white text-primary-green rounded-circle px-2 py-1 small fw-bold" id="cartCount">0</div>
    <span>View Cart</span>
    <i class="fas fa-shopping-basket"></i>
</a>

<!-- Toast Notification -->
<div class="toast-container position-fixed bottom-0 start-50 translate-middle-x mb-5">
    <div id="cartToast" class="toast align-items-center text-white bg-success border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body">
                <i class="fas fa-check-circle me-2"></i><span id="toastMessage">Item added to cart!</span>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
</div>

<script>
    const csrfToken = '<?php echo generateCSRFToken(); ?>';
    const hotelId = <?php echo $hotel_id; ?>;
    const apiUrl = '<?php echo BASE_URL; ?>/api.php';

    // Update cart button on page load
    updateCartButton();

    function addToCart(itemId, name, price, hotelId, hotelName, imageUrl) {
        fetch(apiUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=add_to_cart&item_id=${itemId}&quantity=1&csrf_token=${csrfToken}`
        })
            .then(res => {
                if (!res.ok) throw new Error('Server error: ' + res.status);
                return res.json();
            })
            .then(data => {
                if (data.success) {
                    showToast(`${name} added to cart!`);
                    updateCartButton();
                } else {
                    showToast(data.message || 'Could not add item', 'error');
                }
            })
            .catch(err => {
                console.error('Add to cart error:', err);
                showToast('Failed to add item. Please try again.', 'error');
            });
    }

    function updateCartButton() {
        fetch(apiUrl + '?action=get_cart')
            .then(res => {
                if (!res.ok) throw new Error('Server error: ' + res.status);
                return res.json();
            })
            .then(data => {
                const cartBtn = document.getElementById('cartButton');
                const cartCount = document.getElementById('cartCount');

                if (data.count > 0) {
                    cartBtn.style.display = 'flex';
                    cartCount.textContent = data.count;
                } else {
                    cartBtn.style.display = 'none';
                }
            })
            .catch(err => {
                console.error('Cart update error:', err);
            });
    }

    function showToast(message, type = 'success') {
        const toast = document.getElementById('cartToast');
        const toastMsg = document.getElementById('toastMessage');

        toast.classList.remove('bg-success', 'bg-danger');
        toast.classList.add(type === 'success' ? 'bg-success' : 'bg-danger');
        toastMsg.textContent = message;

        const bsToast = new bootstrap.Toast(toast);
        bsToast.show();
    }

    function copyLink() {
        const url = window.location.href;
        navigator.clipboard.writeText(url).then(() => {
            showToast('Restaurant link copied!');
        });
    }

    // Menu search
    const searchInput = document.getElementById('menuSearch');
    const searchBtn = document.getElementById('searchBtn');

    function performSearch() {
        const search = searchInput.value.toLowerCase().trim();
        let foundAny = false;

        document.querySelectorAll('.category-section').forEach(section => {
            let sectionHasMatch = false;
            section.querySelectorAll('.menu-item').forEach(item => {
                const name = item.dataset.name;
                const desc = item.dataset.desc || '';
                const match = name.includes(search) || desc.includes(search);
                
                if (match) {
                    item.style.display = 'block';
                    sectionHasMatch = true;
                    foundAny = true;
                } else {
                    item.style.display = 'none';
                }
            });
            section.style.display = sectionHasMatch ? 'block' : 'none';
        });

        // Toggle no results message (add it if it doesn't exist)
        let noResults = document.getElementById('noMenuResults');
        if (!foundAny) {
            if (!noResults) {
                noResults = document.createElement('div');
                noResults.id = 'noMenuResults';
                noResults.className = 'text-center py-5';
                noResults.innerHTML = `
                    <i class="fas fa-search text-muted mb-3" style="font-size: 3rem;"></i>
                    <h5 class="text-muted">No items match your search "${search}"</h5>
                    <button class="btn btn-link text-primary-green" onclick="resetSearch()">View all items</button>
                `;
                document.querySelector('.col-lg-9').appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }

    function resetSearch() {
        searchInput.value = '';
        performSearch();
    }

    searchInput.addEventListener('input', performSearch);
    searchBtn.addEventListener('click', performSearch);
</script>

<?php include('../includes/footer.php'); ?>