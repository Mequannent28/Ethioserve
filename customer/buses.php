<?php
require_once '../includes/functions.php';
require_once '../includes/db.php';

// Handle search
$origin = sanitize($_GET['origin'] ?? '');
$destination = sanitize($_GET['destination'] ?? '');
$travel_date = sanitize($_GET['date'] ?? '');

// Get all approved transport companies
$stmt = $pdo->query("SELECT * FROM transport_companies WHERE status = 'approved' ORDER BY rating DESC");
$companies = $stmt->fetchAll();

// Search for available schedules
$schedules = [];
if (!empty($origin) && !empty($destination) && !empty($travel_date)) {
    $stmt = $pdo->prepare("
        SELECT s.*, r.origin, r.destination, r.estimated_hours,
               b.bus_number, b.total_seats, b.amenities, bt.name as bus_type,
               tc.company_name, tc.logo_url, tc.rating, tc.phone,
               (b.total_seats - COALESCE((
                   SELECT SUM(bb.num_passengers) 
                   FROM bus_bookings bb 
                   WHERE bb.schedule_id = s.id 
                   AND bb.travel_date = ? 
                   AND bb.status != 'cancelled'
               ), 0)) as available_seats
        FROM schedules s
        JOIN routes r ON s.route_id = r.id
        JOIN buses b ON s.bus_id = b.id
        JOIN bus_types bt ON b.bus_type_id = bt.id
        JOIN transport_companies tc ON b.company_id = tc.id
        WHERE r.origin LIKE ? 
        AND r.destination LIKE ?
        AND s.is_active = TRUE
        AND b.is_active = TRUE
        AND tc.status = 'approved'
        ORDER BY s.departure_time ASC
    ");
    $stmt->execute([$travel_date, "%$origin%", "%$destination%"]);
    $schedules = $stmt->fetchAll();
}

include('../includes/header.php');
?>

<style>
    .bus-hero {
        background: linear-gradient(rgba(27, 94, 32, 0.85), rgba(27, 94, 32, 0.95)), url('https://images.unsplash.com/photo-1544620347-c4fd4a3d5957?auto=format&fit=crop&w=1200&q=80');
        background-size: cover;
        background-position: center;
        border-radius: 0 0 40px 40px;
        padding: 60px 0 100px;
        color: white;
    }

    .search-box {
        background: white;
        border-radius: 30px;
        padding: 40px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
        margin-top: -60px;
    }

    .company-pill {
        transition: 0.3s;
        border: 2px solid transparent;
        cursor: pointer;
    }

    .company-pill:hover {
        transform: translateY(-5px);
        border-color: #1B5E20;
    }

    .logo-container {
        width: 80px;
        height: 80px;
        background: #f8f9fa;
        border-radius: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 15px;
        overflow: hidden;
    }

    .logo-container img {
        width: 100%;
        height: 100%;
        object-fit: contain;
    }
</style>

<div class="bus-hero">
    <div class="container text-center">
        <h1 class="display-4 fw-bold mb-3">Ethiopian City Bus Services</h1>
        <p class="lead opacity-75 mb-0">Official partner for Sheger, Anbessa, Alliance and premium inter-city buses.</p>
    </div>
</div>

<div class="container mb-5">
    <div class="search-box">
        <div class="row g-4">
            <div class="col-lg-12">
                <h5 class="fw-bold mb-4 text-dark"><i class="fas fa-search-location text-primary-green me-2"></i>Find
                    Your Route</h5>
                <form method="GET">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="origin" class="form-control border-0 bg-light rounded-4"
                                    id="originField" placeholder="From" value="<?php echo htmlspecialchars($origin); ?>"
                                    list="ethiopianCities" required>
                                <label for="originField">Origin City (e.g. Addis Ababa)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="text" name="destination" class="form-control border-0 bg-light rounded-4"
                                    id="destField" placeholder="To"
                                    value="<?php echo htmlspecialchars($destination); ?>" list="ethiopianCities"
                                    required>
                                <label for="destField">Destination City (e.g. Hawassa)</label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-floating">
                                <input type="date" name="date" class="form-control border-0 bg-light rounded-4"
                                    id="dateField" min="<?php echo date('Y-m-d'); ?>"
                                    value="<?php echo htmlspecialchars($travel_date ?: date('Y-m-d')); ?>" required>
                                <label for="dateField">Travel Date</label>
                            </div>
                        </div>
                        <div class="col-12 mt-4">
                            <button type="submit" class="btn btn-primary-green btn-lg w-100 rounded-pill py-3 fw-bold">
                                <i class="fas fa-search me-2"></i> Search Available Buses
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Ethiopian Cities Datalist for Autocomplete -->
                <datalist id="ethiopianCities">
                    <option value="Addis Ababa">Capital City</option>
                    <option value="Adama (Nazret)">Oromia Region</option>
                    <option value="Adwa">Tigray Region</option>
                    <option value="Arba Minch">Southern Nations</option>
                    <option value="Asosa">Benishangul-Gumuz</option>
                    <option value="Awasa (Hawassa)">Southern Nations</option>
                    <option value="Axum">Tigray Region</option>
                    <option value="Bahir Dar">Amhara Region</option>
                    <option value="Debre Birhan">Amhara Region</option>
                    <option value="Debre Markos">Amhara Region</option>
                    <option value="Debre Tabor">Amhara Region</option>
                    <option value="Dessie">Amhara Region</option>
                    <option value="Dire Dawa">Dire Dawa City</option>
                    <option value="Dilla">Southern Nations</option>
                    <option value="Gambela">Gambela Region</option>
                    <option value="Gondar">Amhara Region</option>
                    <option value="Harar">Harari Region</option>
                    <option value="Hawassa">Southern Nations</option>
                    <option value="Hosaena">Southern Nations</option>
                    <option value="Jijiga">Somali Region</option>
                    <option value="Jimma">Oromia Region</option>
                    <option value="Kombolcha">Amhara Region</option>
                    <option value="Lalibela">Amhara Region</option>
                    <option value="Mekelle">Tigray Region</option>
                    <option value="Nekemte">Oromia Region</option>
                    <option value="Semera">Afar Region</option>
                    <option value="Shashamane">Oromia Region</option>
                    <option value="Wolaita Sodo">Southern Nations</option>
                    <option value="Wolkite">Southern Nations</option>
                    <option value="Ziway">Oromia Region</option>
                </datalist>
            </div>
        </div>
    </div>
</div>

<main class="container py-5">
    <!-- Featured Companies Section -->
    <div class="mb-5 text-center">
        <h3 class="fw-bold mb-4">Top Bus Companies</h3>
        <div class="row g-4 justify-content-center">
            <?php foreach ($companies as $comp): ?>
                <div class="col-lg-2 col-md-4 col-6">
                    <div class="card border-0 shadow-sm rounded-4 p-3 h-100 hover-lift company-pill text-center"
                        onclick="document.getElementById('originField').value='Addis Ababa'; document.getElementById('destField').focus();">
                        <div class="logo-container mb-2" style="width: 60px; height: 60px; margin: 0 auto;">
                            <?php if ($comp['logo_url']): ?>
                                <img src="<?php echo $comp['logo_url']; ?>"
                                    alt="<?php echo htmlspecialchars($comp['company_name']); ?>"
                                    style="width: 100%; height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <i class="fas fa-bus text-primary-green fs-3"></i>
                            <?php endif; ?>
                        </div>
                        <h6 class="fw-bold mb-1 small"><?php echo htmlspecialchars($comp['company_name']); ?></h6>
                        <div class="small text-warning">
                            <i class="fas fa-star" style="font-size: 10px;"></i>
                            <span class="text-muted"
                                style="font-size: 10px;"><?php echo number_format($comp['rating'], 1); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <hr class="my-5 opacity-25">
    <?php if (empty($schedules) && (empty($origin) || empty($destination))): ?>
        <!-- Welcome Screen when no search -->
        <div class="row align-items-center py-5">
            <div class="col-lg-6">
                <h2 class="fw-bold mb-4">Connect to Every Corner of Ethiopia</h2>
                <p class="text-muted lead mb-4">Choose from over 500+ daily departures across our network of trusted
                    partners. Whether it's the premium Golden Bus or the reliable Sheger City service, we've got you
                    covered.</p>
                <div class="d-flex gap-3">
                    <div class="bg-light p-3 rounded-4">
                        <h4 class="fw-bold mb-0">98%</h4>
                        <small class="text-muted">On-time</small>
                    </div>
                    <div class="bg-light p-3 rounded-4">
                        <h4 class="fw-bold mb-0">1M+</h4>
                        <small class="text-muted">Users</small>
                    </div>
                    <div class="bg-light p-3 rounded-4">
                        <h4 class="fw-bold mb-0">4.8</h4>
                        <small class="text-muted">Rating</small>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <img src="https://images.unsplash.com/photo-1570125909232-eb263c188f7e?auto=format&fit=crop&w=800&q=80"
                    class="img-fluid rounded-4 shadow-lg" alt="EthioServe Bus">
            </div>
        </div>
    <?php elseif (!empty($schedules)): ?>
        <!-- Search Results -->
        <h4 class="fw-bold mb-4">
            <i class="fas fa-route text-primary-green me-2"></i>
            Routes for <?php echo htmlspecialchars($origin); ?> → <?php echo htmlspecialchars($destination); ?>
        </h4>
        <div class="row g-4">
            <?php foreach ($schedules as $schedule): ?>
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="row g-0">
                            <div class="col-md-4 position-relative"
                                style="background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);">
                                <div class="text-center p-4 position-relative">
                                    <!-- Bus Type Icon/Image -->
                                    <div class="bus-icon-container mb-3">
                                        <?php
                                        // Dynamic bus image based on bus type
                                        $bus_type_lower = strtolower($schedule['bus_type']);
                                        $bus_icon_color = '#1B5E20';

                                        if (strpos($bus_type_lower, 'luxury') !== false || strpos($bus_type_lower, 'vip') !== false) {
                                            $bus_icon_color = '#FFD700'; // Gold for luxury
                                        } elseif (strpos($bus_type_lower, 'standard') !== false) {
                                            $bus_icon_color = '#2196F3'; // Blue for standard
                                        } elseif (strpos($bus_type_lower, 'economy') !== false) {
                                            $bus_icon_color = '#4CAF50'; // Green for economy
                                        }
                                        ?>

                                        <!-- Beautiful SVG Bus Icon -->
                                        <svg width="80" height="80" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                                            <defs>
                                                <linearGradient id="busGradient<?php echo $schedule['id']; ?>" x1="0%" y1="0%"
                                                    x2="0%" y2="100%">
                                                    <stop offset="0%"
                                                        style="stop-color:<?php echo $bus_icon_color; ?>;stop-opacity:1" />
                                                    <stop offset="100%"
                                                        style="stop-color:<?php echo $bus_icon_color; ?>CC;stop-opacity:1" />
                                                </linearGradient>
                                            </defs>
                                            <!-- Bus Body -->
                                            <rect x="10" y="30" width="80" height="45" rx="8"
                                                fill="url(#busGradient<?php echo $schedule['id']; ?>)" />
                                            <!-- Windows -->
                                            <rect x="15" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
                                            <rect x="35" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
                                            <rect x="55" y="35" width="15" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
                                            <rect x="75" y="35" width="10" height="12" rx="2" fill="rgba(255,255,255,0.9)" />
                                            <!-- Wheels -->
                                            <circle cx="25" cy="75" r="8" fill="#333" />
                                            <circle cx="25" cy="75" r="4" fill="#666" />
                                            <circle cx="75" cy="75" r="8" fill="#333" />
                                            <circle cx="75" cy="75" r="4" fill="#666" />
                                            <!-- Front Light -->
                                            <circle cx="85" cy="60" r="3" fill="#FFD700" opacity="0.8" />
                                            <!-- Door -->
                                            <rect x="15" y="52" width="12" height="18" rx="2" fill="rgba(255,255,255,0.7)" />
                                        </svg>
                                    </div>

                                    <!-- Company Logo (if available) -->
                                    <?php if (!empty($schedule['logo_url'])): ?>
                                        <img src="<?php echo $schedule['logo_url']; ?>"
                                            class="img-fluid rounded-circle mb-2 shadow-sm"
                                            style="max-height: 50px; max-width: 50px; background: white; padding: 5px;">
                                    <?php endif; ?>

                                    <h5 class="fw-bold mb-1 text-dark">
                                        <?php echo htmlspecialchars($schedule['company_name']); ?>
                                    </h5>

                                    <!-- Enhanced Bus Type Badge -->
                                    <span class="badge px-3 py-2 fw-bold"
                                        style="background: <?php echo $bus_icon_color; ?>; color: white;">
                                        <i class="fas fa-bus me-1"></i>
                                        <?php echo htmlspecialchars($schedule['bus_type']); ?>
                                    </span>

                                    <!-- Bus Number -->
                                    <div class="mt-2">
                                        <small class="text-muted">
                                            <i class="fas fa-hashtag"></i>
                                            <?php echo htmlspecialchars($schedule['bus_number']); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-8 p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        <h3 class="fw-bold text-primary-green mb-0 d-flex align-items-center">
                                            <i class="fas fa-clock me-2"></i>
                                            <?php echo date('H:i', strtotime($schedule['departure_time'])); ?>
                                        </h3>
                                        <small class="text-muted">Departure Time</small>
                                    </div>
                                    <div class="text-end">
                                        <h4 class="fw-bold mb-0 text-success">
                                            <i class="fas fa-tag me-1"></i>
                                            <?php echo number_format($schedule['price']); ?> ETB
                                        </h4>
                                        <small class="text-muted">Per Passenger</small>
                                    </div>
                                </div>

                                <!-- Route Visualization -->
                                <div class="route-visual d-flex align-items-center gap-2 mb-3 p-3"
                                    style="background: #f8f9fa; border-radius: 12px;">
                                    <div class="flex-shrink-0">
                                        <div style="width: 12px; height: 12px; background: #4CAF50; border-radius: 50%;"></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <strong><?php echo htmlspecialchars($schedule['origin']); ?></strong>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-bus text-primary-green fa-lg"></i>
                                    </div>
                                    <div class="flex-shrink-0" style="flex-basis: 40px;">
                                        <div style="border-top: 2px dashed #ccc; width: 100%;"></div>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <i class="fas fa-flag-checkered text-danger fa-lg"></i>
                                    </div>
                                    <div class="flex-grow-1 text-end">
                                        <strong><?php echo htmlspecialchars($schedule['destination']); ?></strong>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <div style="width: 12px; height: 12px; background: #f44336; border-radius: 50%;"></div>
                                    </div>
                                </div>

                                <!-- Amenities (if available) -->
                                <?php if (!empty($schedule['amenities'])): ?>
                                    <div class="mb-3">
                                        <small class="text-muted d-flex flex-wrap gap-2">
                                            <?php
                                            $amenities = explode(',', $schedule['amenities']);
                                            foreach (array_slice($amenities, 0, 4) as $amenity):
                                                $amenity = trim($amenity);
                                                $icon = match (strtolower($amenity)) {
                                                    'wifi', 'wi-fi' => 'wifi',
                                                    'ac', 'air conditioning' => 'snowflake',
                                                    'tv', 'entertainment' => 'tv',
                                                    'usb charging' => 'charging-station',
                                                    'reclining seats' => 'couch',
                                                    default => 'check-circle'
                                                };
                                                ?>
                                                <span class="badge bg-light text-dark border">
                                                    <i class="fas fa-<?php echo $icon; ?> me-1"></i>
                                                    <?php echo htmlspecialchars($amenity); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex gap-3 align-items-center">
                                        <span class="text-muted small">
                                            <i class="fas fa-chair me-1 text-primary-green"></i>
                                            <strong class="text-success"><?php echo $schedule['available_seats']; ?></strong>
                                            Seats
                                        </span>
                                        <?php if (!empty($schedule['estimated_hours'])): ?>
                                            <span class="text-muted small">
                                                <i class="fas fa-hourglass-half me-1 text-warning"></i>
                                                ~<?php echo $schedule['estimated_hours']; ?>h
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="book_bus.php?schedule=<?php echo $schedule['id']; ?>&date=<?php echo $travel_date; ?>"
                                        class="btn btn-primary-green rounded-pill px-4 shadow-sm">
                                        <i class="fas fa-ticket-alt me-2"></i>Book Ticket
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-5">
            <i class="fas fa-bus-alt fa-4x text-light mb-4"></i>
            <h4 class="text-muted">No buses found for this route on this date.</h4>
            <p>Try searching for a different date or city like "Addis Ababa" to "Hawassa".</p>
            <a href="buses.php" class="btn btn-outline-primary-green rounded-pill px-4 mt-3">Reset Search</a>
        </div>
    <?php endif; ?>
</main>

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<?php include('../includes/footer.php'); ?>