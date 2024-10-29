<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'customer') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

// Get customer's deliveries
$stmt = $db->prepare("
    SELECT d.*, u.name as driver_name 
    FROM deliveries d 
    LEFT JOIN users u ON d.driver_id = u.id
    WHERE d.customer_id = ? 
    ORDER BY d.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Dashboard - DeliveryTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-truck"></i> DeliveryTrack</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="customer-dashboard.php">My Deliveries</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </span>
                    <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">My Deliveries</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tracking ID</th>
                                        <th>Driver</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($delivery['tracking_id']); ?></td>
                                        <td><?php echo $delivery['driver_name'] ? htmlspecialchars($delivery['driver_name']) : 'Not assigned'; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusColor($delivery['status']); ?>">
                                                <?php echo ucfirst($delivery['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></td>
                                        <td>
                                            <button class="btn btn-primary btn-sm" onclick="trackDelivery('<?php echo $delivery['tracking_id']; ?>')">
                                                Track
                                            </button>
                                            <?php if ($delivery['status'] === 'delivered' && !hasRating($delivery['id'])): ?>
                                            <button class="btn btn-success btn-sm" onclick="rateDriver('<?php echo $delivery['id']; ?>', '<?php echo $delivery['driver_id']; ?>')">
                                                Rate Driver
                                            </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Live Tracking</h5>
                    </div>
                    <div class="card-body">
                        <div id="tracking-status" class="mb-3"></div>
                        <div id="map" style="height: 300px; border-radius: 8px;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        let map;
        let marker;

        function initMap() {
            map = L.map('map').setView([0, 0], 13);
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '© OpenStreetMap contributors'
            }).addTo(map);
        }

        function trackDelivery(trackingId) {
            fetch(`track.php?tracking_id=${trackingId}`)
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        updateMap(data.lat, data.lng);
                        updateStatus(data.status);
                    }
                });
        }

        function updateMap(lat, lng) {
            if (marker) {
                marker.setLatLng([lat, lng]);
            } else {
                marker = L.marker([lat, lng]).addTo(map);
            }
            map.setView([lat, lng], 13);
        }

        function rateDriver(deliveryId, driverId) {
            const modalHtml = `
                <div class="modal fade" id="ratingModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Rate Driver</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Rating</label>
                                    <div class="rating">
                                        <i class="bi bi-star fs-4" data-rating="1"></i>
                                        <i class="bi bi-star fs-4" data-rating="2"></i>
                                        <i class="bi bi-star fs-4" data-rating="3"></i>
                                        <i class="bi bi-star fs-4" data-rating="4"></i>
                                        <i class="bi bi-star fs-4" data-rating="5"></i>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Comment</label>
                                    <textarea class="form-control" id="ratingComment" rows="3"></textarea>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="submitRating(${deliveryId}, ${driverId})">Submit Rating</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
            modal.show();

            // Star rating functionality
            let selectedRating = 0;
            document.querySelectorAll('.rating i').forEach(star => {
                star.addEventListener('click', function() {
                    selectedRating = this.dataset.rating;
                    updateStars(selectedRating);
                });
            });
        }

        function updateStars(rating) {
            document.querySelectorAll('.rating i').forEach(star => {
                star.classList.remove('bi-star-fill', 'bi-star');
                if (star.dataset.rating <= rating) {
                    star.classList.add('bi-star-fill');
                } else {
                    star.classList.add('bi-star');
                }
            });
        }

        function submitRating(deliveryId, driverId) {
            const rating = document.querySelector('.rating i.bi-star-fill:last-of-type').dataset.rating;
            const comment = document.getElementById('ratingComment').value;

            fetch('submit_rating.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    delivery_id: deliveryId,
                    driver_id: driverId,
                    rating: rating,
                    comment: comment
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                    location.reload();
                }
            });
        }

        window.onload = initMap;
    </script>
</body>
</html>

<?php
function getStatusColor($status) {
    $colors = [
        'pending' => 'warning',
        'assigned' => 'info',
        'picked_up' => 'primary',
        'in_transit' => 'primary',
        'delivered' => 'success',
        'cancelled' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

function hasRating($deliveryId) {
    global $db;
    $stmt = $db->prepare("SELECT id FROM delivery_ratings WHERE delivery_id = ?");
    $stmt->execute([$deliveryId]);
    return $stmt->fetch() !== false;
}
?>
