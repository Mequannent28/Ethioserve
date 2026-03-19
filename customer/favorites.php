<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('customer');

$user_id = getCurrentUserId();
$flash = getFlashMessage();

// Handle Remove Favorite via AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'remove_favorite') {
    header('Content-Type: application/json');
    $listing_id = (int) $_POST['listing_id'];
    $stmt = $pdo->prepare("DELETE FROM rental_favorites WHERE user_id = ? AND listing_id = ?");
    $stmt->execute([$user_id, $listing_id]);
    echo json_encode(['status' => 'removed']);
    exit;
}

// Fetch all favorite listings
$stmt = $pdo->prepare("
    SELECT l.*, rf.created_at as favorited_at
    FROM rental_favorites rf
    JOIN listings l ON rf.listing_id = l.id
    WHERE rf.user_id = ?
    ORDER BY rf.created_at DESC
");
$stmt->execute([$user_id]);
$favorites = $stmt->fetchAll();

include('../includes/header.php');
?>

<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
<style>
    :root {
        --fav-primary: #1B5E20;
        --fav-gold: #F9A825;
        --fav-bg: #f5f7f5;
    }

    body {
        font-family: 'Outfit', sans-serif;
        background-color: var(--fav-bg);
    }

    .fav-hero {
        background: linear-gradient(135deg, #1B5E20, #2E7D32);
        padding: 50px 0 80px;
        color: white;
        position: relative;
        overflow: hidden;
    }

    .fav-hero::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 60px;
        background: var(--fav-bg);
        border-radius: 50px 50px 0 0;
    }

    .fav-hero::before {
        content: '';
        position: absolute;
        top: -100px; right: -100px;
        width: 400px; height: 400px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.04);
        pointer-events: none;
    }

    .stat-pill {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        border-radius: 30px;
        padding: 10px 24px;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: 600;
    }

    .fav-card {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        border: none;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
    }

    .fav-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.1);
    }

    .fav-card-img {
        height: 220px;
        width: 100%;
        object-fit: cover;
        transition: transform 0.5s ease;
    }

    .fav-card:hover .fav-card-img {
        transform: scale(1.05);
    }

    .fav-card-img-wrap {
        overflow: hidden;
        position: relative;
    }

    .type-badge {
        position: absolute;
        top: 14px;
        left: 14px;
        background: rgba(27, 94, 32, 0.85);
        color: white;
        padding: 5px 14px;
        border-radius: 25px;
        font-size: 0.72rem;
        font-weight: 700;
        backdrop-filter: blur(8px);
    }

    .price-badge {
        position: absolute;
        bottom: 14px;
        right: 14px;
        background: white;
        color: var(--fav-primary);
        padding: 8px 16px;
        border-radius: 14px;
        font-weight: 800;
        font-size: 0.95rem;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
    }

    .remove-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 36px;
        height: 36px;
        background: white;
        border: none;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.12);
        cursor: pointer;
        transition: all 0.3s;
        color: #e53935;
    }

    .remove-btn:hover {
        transform: scale(1.15);
        background: #e53935;
        color: white;
    }

    .fav-card-body {
        padding: 20px;
    }

    .fav-card-title {
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 6px;
        color: #1a1a1a;
        text-overflow: ellipsis;
        overflow: hidden;
        white-space: nowrap;
    }

    .fav-card-location {
        color: #888;
        font-size: 0.85rem;
        margin-bottom: 14px;
    }

    .fav-meta-row {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
        padding-top: 12px;
        border-top: 1px solid #f0f0f0;
        margin-bottom: 16px;
    }

    .fav-meta-item {
        font-size: 0.78rem;
        color: #666;
        display: flex;
        align-items: center;
        gap: 5px;
        background: #f8f9fa;
        padding: 4px 10px;
        border-radius: 20px;
    }

    .fav-meta-item i {
        color: var(--fav-primary);
    }

    .btn-view-listing {
        background: linear-gradient(135deg, var(--fav-primary), #2e7d32);
        color: white;
        border: none;
        border-radius: 12px;
        padding: 10px 20px;
        font-weight: 700;
        font-size: 0.9rem;
        text-decoration: none;
        display: block;
        text-align: center;
        transition: all 0.3s;
    }

    .btn-view-listing:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 18px rgba(27, 94, 32, 0.3);
        color: white;
    }

    .search-bar {
        background: white;
        border-radius: 50px;
        padding: 6px 6px 6px 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.07);
        max-width: 500px;
        margin: 0 auto 40px;
    }

    .search-bar input {
        border: none;
        outline: none;
        flex: 1;
        font-size: 0.95rem;
        background: transparent;
    }

    .search-bar button {
        background: var(--fav-primary);
        color: white;
        border: none;
        border-radius: 40px;
        padding: 10px 22px;
        font-weight: 600;
        font-size: 0.85rem;
    }

    .empty-state {
        text-align: center;
        padding: 80px 20px;
    }

    .empty-icon {
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #f8f9fa, #e8f5e9);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
        font-size: 3rem;
        color: #ccc;
    }

    .card-removing {
        animation: fadeOutCard 0.45s ease forwards;
    }

    @keyframes fadeOutCard {
        to {
            opacity: 0;
            transform: scale(0.85);
        }
    }

    @media (max-width: 768px) {
        .fav-hero { padding: 40px 0 70px; }
    }
</style>

<?php if ($flash): ?>
<div class="container mt-3">
    <div class="alert alert-<?php echo $flash['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show rounded-4 shadow-sm border-0">
        <?php echo htmlspecialchars($flash['message']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
</div>
<?php endif; ?>

<!-- Hero -->
<div class="fav-hero">
    <div class="container text-center position-relative" style="z-index: 2;">
        <h1 class="fw-bold display-5 mb-2">My Saved Listings</h1>
        <p class="opacity-75 mb-4">Properties you've hearted — all in one place.</p>
        <div class="stat-pill d-inline-flex">
            <i class="fas fa-heart text-danger"></i>
            <span><?php echo count($favorites); ?> saved listing<?php echo count($favorites) !== 1 ? 's' : ''; ?></span>
        </div>
    </div>
</div>

<div class="container py-5" style="max-width: 1200px;">
    <?php if (empty($favorites)): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <i class="far fa-heart"></i>
            </div>
            <h3 class="fw-bold mb-2">No saved listings yet</h3>
            <p class="text-muted mb-4">Browse listings and tap the <i class="fas fa-heart text-danger"></i> icon to save properties you're interested in.</p>
            <a href="rent.php" class="btn btn-success rounded-pill px-5 py-3 fw-bold shadow-sm">
                <i class="fas fa-search me-2"></i> Browse Listings
            </a>
        </div>
    <?php else: ?>

        <!-- Search -->
        <div class="search-bar">
            <i class="fas fa-search text-muted"></i>
            <input type="text" id="favSearch" placeholder="Search your saved listings...">
            <button onclick="filterFavorites()"><i class="fas fa-sliders-h me-1"></i> Filter</button>
        </div>

        <div class="row g-4" id="favGrid">
            <?php foreach ($favorites as $item): ?>
                <div class="col-lg-4 col-md-6 fav-item"
                     data-title="<?php echo strtolower($item['title']); ?>"
                     data-location="<?php echo strtolower($item['location']); ?>"
                     data-type="<?php echo $item['type']; ?>"
                     data-id="<?php echo $item['id']; ?>">
                    <div class="fav-card" id="fav-card-<?php echo $item['id']; ?>">
                        <div class="fav-card-img-wrap">
                            <img src="<?php echo htmlspecialchars($item['image_url'] ?: 'https://images.unsplash.com/photo-1582407947304-fd86f028f716?w=800&q=80'); ?>"
                                 class="fav-card-img" alt="<?php echo htmlspecialchars($item['title']); ?>">
                            
                            <span class="type-badge">
                                <i class="fas fa-<?php echo $item['type'] === 'car_rent' ? 'car' : 'home'; ?> me-1"></i>
                                <?php echo ucwords(str_replace('_', ' ', $item['type'])); ?>
                            </span>

                            <button class="remove-btn" onclick="removeFavorite(<?php echo $item['id']; ?>, this)"
                                    title="Remove from favorites">
                                <i class="fas fa-heart"></i>
                            </button>

                            <div class="price-badge">
                                <?php echo number_format($item['price']); ?> ETB
                            </div>
                        </div>

                        <div class="fav-card-body">
                            <h5 class="fav-card-title"><?php echo htmlspecialchars($item['title']); ?></h5>
                            <p class="fav-card-location">
                                <i class="fas fa-map-marker-alt text-danger me-1"></i>
                                <?php echo htmlspecialchars($item['location']); ?>
                            </p>

                            <?php if ($item['type'] === 'house_rent' && ($item['bedrooms'] || $item['bathrooms'])): ?>
                                <div class="fav-meta-row">
                                    <?php if ($item['bedrooms']): ?>
                                        <span class="fav-meta-item"><i class="fas fa-bed"></i> <?php echo $item['bedrooms']; ?> Bed</span>
                                    <?php endif; ?>
                                    <?php if ($item['bathrooms']): ?>
                                        <span class="fav-meta-item"><i class="fas fa-bath"></i> <?php echo $item['bathrooms']; ?> Bath</span>
                                    <?php endif; ?>
                                    <?php if ($item['area_sqm']): ?>
                                        <span class="fav-meta-item"><i class="fas fa-ruler-combined"></i> <?php echo $item['area_sqm']; ?> m²</span>
                                    <?php endif; ?>
                                </div>
                            <?php elseif (!empty($item['features'])): ?>
                                <div class="fav-meta-row">
                                    <?php
                                    $feats = explode(',', $item['features']);
                                    foreach (array_slice($feats, 0, 3) as $f): ?>
                                        <span class="fav-meta-item"><i class="fas fa-check"></i> <?php echo htmlspecialchars(trim($f)); ?></span>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div style="height: 16px;"></div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-bookmark-slash me-1"></i>
                                    Saved <?php echo date('M d, Y', strtotime($item['favorited_at'])); ?>
                                </small>
                                <span class="badge bg-<?php echo $item['status'] === 'available' ? 'success' : 'warning text-dark'; ?> rounded-pill small">
                                    <?php echo ucfirst($item['status']); ?>
                                </span>
                            </div>

                            <a href="rent.php?view=<?php echo $item['id']; ?>" class="btn-view-listing">
                                <i class="fas fa-eye me-2"></i> View Details & Request
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
    // Live search filter
    const favSearchInput = document.getElementById('favSearch');
    if (favSearchInput) {
        favSearchInput.addEventListener('input', filterFavorites);
    }

    function filterFavorites() {
        const query = (favSearchInput?.value || '').toLowerCase();
        document.querySelectorAll('.fav-item').forEach(item => {
            const title = item.getAttribute('data-title') || '';
            const loc = item.getAttribute('data-location') || '';
            item.style.display = (title.includes(query) || loc.includes(query)) ? 'block' : 'none';
        });
    }

    function removeFavorite(listingId, btn) {
        if (!confirm('Remove this listing from your favorites?')) return;

        fetch('favorites.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove_favorite&listing_id=' + listingId
        })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'removed') {
                const card = document.getElementById('fav-card-' + listingId);
                const wrapper = card ? card.closest('.fav-item') : null;
                if (wrapper) {
                    card.classList.add('card-removing');
                    setTimeout(() => {
                        wrapper.remove();
                        // Update count in hero
                        const remaining = document.querySelectorAll('.fav-item').length;
                        const pill = document.querySelector('.stat-pill span');
                        if (pill) {
                            pill.textContent = remaining + ' saved listing' + (remaining !== 1 ? 's' : '');
                        }
                        if (remaining === 0) {
                            location.reload();
                        }
                    }, 450);
                }
            }
        })
        .catch(err => console.error('Error removing favorite:', err));
    }
</script>

<?php include '../includes/footer.php'; ?>
