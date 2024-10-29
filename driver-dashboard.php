<?php
session_start();

// Ensure only drivers can access this page
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'driver') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

// Get available deliveries
$stmt = $db->prepare("
    SELECT * FROM deliveries 
    WHERE (status = 'pending' OR (status NOT IN ('delivered', 'cancelled') AND driver_id = ?))
    ORDER BY created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Driver Dashboard - DeliveryTrack</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
</head>
<body>
    <!-- Include the same navbar as index.php -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">DeliveryTrack</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="driver-dashboard.php">Driver Dashboard</a>
                    </li>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                        (Driver)
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
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Available Deliveries</h5>
                        <button class="btn btn-light btn-sm" onclick="updateLocation()">
                            <i class="bi bi-geo-alt"></i> Update Location
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Tracking ID</th>
                                        <th>Pickup</th>
                                        <th>Dropoff</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($deliveries as $delivery): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($delivery['tracking_id']); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['pickup_address']); ?></td>
                                        <td><?php echo htmlspecialchars($delivery['dropoff_address']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo getStatusColor($delivery['status']); ?>">
                                                <?php echo ucfirst($delivery['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($delivery['status'] === 'pending'): ?>
                                            <button class="btn btn-primary btn-sm" onclick="acceptDelivery('<?php echo $delivery['tracking_id']; ?>')">
                                                Accept
                                            </button>
                                            <?php else: ?>
                                            <button class="btn btn-success btn-sm" onclick="updateStatus('<?php echo $delivery['tracking_id']; ?>')">
                                                Update Status
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
<?php
// Add this before the statistics card HTML
$stats = [
    'completed' => $db->prepare("
        SELECT COUNT(*) FROM deliveries 
        WHERE driver_id = ? AND status = 'delivered'
    "),
    'active' => $db->prepare("
        SELECT COUNT(*) FROM deliveries 
        WHERE driver_id = ? AND status IN ('assigned', 'picked_up', 'in_transit')
    "),
    'rating' => $db->prepare("
        SELECT AVG(rating) FROM delivery_ratings 
        WHERE driver_id = ?
    ")
];

$stats['completed']->execute([$_SESSION['user_id']]);
$completedCount = $stats['completed']->fetchColumn();

$stats['active']->execute([$_SESSION['user_id']]);
$activeCount = $stats['active']->fetchColumn();

$stats['rating']->execute([$_SESSION['user_id']]);
$ratingValue = $stats['rating']->fetchColumn();
$rating = $ratingValue ? number_format($ratingValue, 1) : 0.0;?>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-3">
                            <span>Completed Deliveries</span>
                            <span class="badge bg-success"><?php echo $completedCount; ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-3">
                            <span>Active Deliveries</span>
                            <span class="badge bg-primary"><?php echo $activeCount; ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Rating</span>
                            <span class="text-warning">
                                <?php
                                $fullStars = floor($rating);
                                $halfStar = $rating - $fullStars >= 0.5;
                                
                                for ($i = 1; $i <= 5; $i++) {
                                    if ($i <= $fullStars) {
                                        echo '<i class="bi bi-star-fill"></i>';
                                    } elseif ($halfStar && $i == $fullStars + 1) {
                                        echo '<i class="bi bi-star-half"></i>';
                                    } else {
                                        echo '<i class="bi bi-star"></i>';
                                    }
                                }
                                ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function acceptDelivery(trackingId) {
            fetch('update_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tracking_id: trackingId,
                    action: 'accept'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
        }

        function updateStatus(trackingId) {
            const modalHtml = `
                <div class="modal fade" id="statusModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Update Delivery Status</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <select class="form-select" id="newStatus">
                                    <option value="picked_up">Picked Up</option>
                                    <option value="in_transit">In Transit</option>
                                    <option value="delivered">Delivered</option>
                                </select>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-primary" onclick="submitStatus('${trackingId}')">Update</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);
            const modal = new bootstrap.Modal(document.getElementById('statusModal'));
            modal.show();

            document.getElementById('statusModal').addEventListener('hidden.bs.modal', function () {
                this.remove();
            });
        }

        function submitStatus(trackingId) {
            const newStatus = document.getElementById('newStatus').value;
            fetch('update_delivery.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    tracking_id: trackingId,
                    action: 'update_status',
                    status: newStatus
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('statusModal')).hide();
                    location.reload();
                }
            });
        }
        function updateLocation() {
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(position => {
                    fetch('update_delivery.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            action: 'update_location',
                            lat: position.coords.latitude,
                            lng: position.coords.longitude
                        })
                    });
                });
            }
        }
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
?>
