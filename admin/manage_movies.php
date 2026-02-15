<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

requireRole('admin');

// Handle add movie
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_movie'])) {
    if (verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $title = sanitize($_POST['title']);
        $genre = sanitize($_POST['genre']);
        $cinema = sanitize($_POST['cinema']);
        $showtime = $_POST['showtime'];
        $price = (float) $_POST['price'];
        $poster_url = sanitize($_POST['poster_url'] ?? '');
        $description = sanitize($_POST['description'] ?? '');

        try {
            // Create movies table if not exists
            $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
                id INT AUTO_INCREMENT PRIMARY KEY,
                title VARCHAR(200) NOT NULL,
                genre VARCHAR(100),
                cinema VARCHAR(200),
                showtime DATETIME,
                price DECIMAL(10,2) DEFAULT 0,
                poster_url VARCHAR(500),
                description TEXT,
                status ENUM('showing','upcoming','ended') DEFAULT 'showing',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");

            $stmt = $pdo->prepare("INSERT INTO movies (title, genre, cinema, showtime, price, poster_url, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $genre, $cinema, $showtime, $price, $poster_url, $description]);
            redirectWithMessage('manage_movies.php', 'success', 'Movie added successfully!');
        } catch (Exception $e) {
            redirectWithMessage('manage_movies.php', 'error', 'Failed to add movie: ' . $e->getMessage());
        }
    }
}

// Handle delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM movies WHERE id = ?");
        $stmt->execute([$id]);
        redirectWithMessage('manage_movies.php', 'success', 'Movie deleted');
    } catch (Exception $e) {
    }
}

// Ensure table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS movies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        genre VARCHAR(100),
        cinema VARCHAR(200),
        showtime DATETIME,
        price DECIMAL(10,2) DEFAULT 0,
        poster_url VARCHAR(500),
        description TEXT,
        status ENUM('showing','upcoming','ended') DEFAULT 'showing',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
} catch (Exception $e) {
}

// Fetch all movies
$items = [];
try {
    $stmt = $pdo->query("SELECT * FROM movies ORDER BY showtime DESC");
    $items = $stmt->fetchAll();
} catch (Exception $e) {
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Movies - Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            overflow-x: hidden;
            font-family: 'Poppins', sans-serif;
        }

        .main-content {
            margin-left: 260px;
            width: calc(100% - 260px);
            padding: 30px;
            background-color: #f4f6f9;
            min-height: 100vh;
        }

        .movie-poster {
            width: 60px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="dashboard-wrapper">
        <?php include('../includes/sidebar_admin.php'); ?>

        <div class="main-content">
            <?php echo displayFlashMessage(); ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h2 class="fw-bold mb-1"><i class="fas fa-film text-danger me-2"></i>Manage Movies</h2>
                    <p class="text-muted mb-0">Add and manage movie listings for cinemas</p>
                </div>
                <button class="btn btn-primary-green rounded-pill px-4" data-bs-toggle="modal"
                    data-bs-target="#addMovieModal">
                    <i class="fas fa-plus me-2"></i>Add Movie
                </button>
            </div>

            <!-- Table -->
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Movie</th>
                                <th>Genre</th>
                                <th>Cinema</th>
                                <th>Showtime</th>
                                <th>Price</th>
                                <th>Status</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($items)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="fas fa-film fs-1 mb-3 d-block"></i>
                                        No movies added yet. Click "Add Movie" to get started.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td class="px-4">
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if ($item['poster_url']): ?>
                                                    <img src="<?php echo htmlspecialchars($item['poster_url']); ?>"
                                                        class="movie-poster">
                                                <?php else: ?>
                                                    <div class="bg-danger bg-opacity-10 p-2 rounded-3"
                                                        style="width:60px;height:80px;display:flex;align-items:center;justify-content:center;">
                                                        <i class="fas fa-film text-danger fs-4"></i>
                                                    </div>
                                                <?php endif; ?>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        <?php echo htmlspecialchars($item['title']); ?>
                                                    </h6>
                                                    <span class="text-muted small">
                                                        <?php echo mb_strimwidth(htmlspecialchars($item['description'] ?? ''), 0, 50, '...'); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($item['genre'] ?? 'N/A'); ?>
                                            </span></td>
                                        <td>
                                            <?php echo htmlspecialchars($item['cinema'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="small">
                                            <?php echo $item['showtime'] ? date('M d, H:i', strtotime($item['showtime'])) : 'TBA'; ?>
                                        </td>
                                        <td class="fw-bold">
                                            <?php echo number_format($item['price']); ?> ETB
                                        </td>
                                        <td>
                                            <?php echo getStatusBadge($item['status'] ?? 'showing'); ?>
                                        </td>
                                        <td class="text-end px-4">
                                            <a href="?delete=<?php echo $item['id']; ?>"
                                                class="btn btn-sm btn-outline-danger rounded-pill"
                                                onclick="return confirm('Delete this movie?')"><i class="fas fa-trash"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Movie Modal -->
    <div class="modal fade" id="addMovieModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 shadow">
                <div class="modal-header border-0 p-4">
                    <h5 class="modal-title fw-bold"><i class="fas fa-film text-danger me-2"></i>Add New Movie</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" class="modal-body p-4 pt-0">
                    <?php echo csrfField(); ?>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Movie Title</label>
                            <input type="text" name="title" class="form-control rounded-pill bg-light border-0 px-4"
                                required placeholder="e.g. Black Panther">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Genre</label>
                            <input type="text" name="genre" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="e.g. Action, Drama">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Cinema</label>
                            <input type="text" name="cinema" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="e.g. Edna Mall Cinema">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Showtime</label>
                            <input type="datetime-local" name="showtime"
                                class="form-control rounded-pill bg-light border-0 px-4">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Ticket Price (ETB)</label>
                            <input type="number" name="price" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="250">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Poster URL</label>
                            <input type="url" name="poster_url" class="form-control rounded-pill bg-light border-0 px-4"
                                placeholder="https://...">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Description</label>
                            <textarea name="description" class="form-control bg-light border-0 px-4" rows="3"
                                style="border-radius:15px;" placeholder="Short movie description..."></textarea>
                        </div>
                    </div>
                    <button type="submit" name="add_movie"
                        class="btn btn-primary-green w-100 rounded-pill py-3 fw-bold mt-4 shadow">
                        <i class="fas fa-check-circle me-2"></i>Add Movie
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>