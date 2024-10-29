<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');
?>

<!DOCTYPE html>
<html>
<head>
    <title>DeliveryTrack - Home</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#"><i class="bi bi-truck"></i> DeliveryTrack</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarContent">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Home</a>
                    </li>
                    <?php if($_SESSION['user_role'] == 'driver'): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="driver-dashboard.php">Driver Dashboard</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="customer-dashboard.php">My Deliveries</a>
                        </li>
                    <?php endif; ?>
                </ul>
                
                <div class="d-flex align-items-center">
                    <span class="text-white me-3">
                        <i class="bi bi-person-circle"></i>
                        <?php echo htmlspecialchars($_SESSION['user_name']); ?> 
                        (<?php echo ucfirst($_SESSION['user_role']); ?>)
                    </span>
                    <a href="logout.php" class="btn btn-light btn-sm">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if($_SESSION['user_role'] == 'customer'): ?>
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-calendar-plus"></i> Schedule a Delivery</h5>
                        </div>
                        <div class="card-body">
                            <form id="createDelivery">
                                <div class="mb-3">
                                    <label class="form-label">Item Type</label>
                                    <select name="item_type" class="form-select" required>
                                        <option value="">Select Type</option>
                                        <option value="food">Food</option>
                                        <option value="hardware">Hardware</option>
                                        <option value="documents">Documents</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                                <div class="mb-3">
    <label class="form-label">Pickup Address</label>
    <input type="text" id="pickup_address" name="pickup_address" class="form-control" required>
    <div id="pickup_suggestions" class="list-group mt-1"></div>
</div>

<div class="mb-3">
    <label class="form-label">Dropoff Address</label>
    <input type="text" id="dropoff_address" name="dropoff_address" class="form-control" required>
    <div id="dropoff_suggestions" class="list-group mt-1"></div>
</div>
                                <div class="mb-3">
                                    <label class="form-label">Special Instructions</label>
                                    <textarea name="special_instructions" class="form-control" rows="3"></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-send"></i> Schedule Delivery
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-6 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0"><i class="bi bi-geo-alt"></i> Track Your Delivery</h5>
                        </div>
                        <div class="card-body">
                            <div class="input-group mb-3">
                                <input type="text" id="tracking_id" class="form-control" placeholder="Enter Tracking ID">
                                <button class="btn btn-primary" type="button" onclick="trackDelivery()">
                                    <i class="bi bi-search"></i> Track
                                </button>
                            </div>
                            <div id="delivery-status" class="mb-3"></div>
                            <div id="map" style="height: 300px; border-radius: 8px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif($_SESSION['user_role'] == 'driver'): ?>
            <div class="alert alert-info">
                <h4><i class="bi bi-info-circle"></i> Welcome, Driver!</h4>
                <p>Head to your <a href="driver-dashboard.php" class="alert-link">Driver Dashboard</a> to manage deliveries.</p>
            </div>
        <?php elseif($_SESSION['user_role'] == 'admin'): 
            header("location: admin-dashboard.php"); 
        endif; ?>
    

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

        document.getElementById('createDelivery')?.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('process_delivery.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if(data.success) {
                    alert(`Delivery scheduled successfully!\nYour tracking ID is: ${data.tracking_id}`);
                    this.reset();
                    window.location.href = 'customer-dashboard.php';
                }
            });
        });

        function trackDelivery() {
            const trackingId = document.getElementById('tracking_id').value;
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

        function updateStatus(status) {
            const statusMessages = {
                'assigned': '<div class="alert alert-info">Driver Assigned - Your delivery will be picked up soon!</div>',
                'picked_up': '<div class="alert alert-primary">Driver has picked up your Delivery</div>',
                'in_transit': '<div class="alert alert-success">Driver is on their way with your delivery</div>'
            };
            document.getElementById('delivery-status').innerHTML = statusMessages[status] || '';
        }

        if (document.getElementById('map')) {
            window.onload = initMap;
        }




        function initAddressAutocomplete() {
    const pickupInput = document.getElementById('pickup_address');
    const dropoffInput = document.getElementById('dropoff_address');
    
    setupAutocomplete(pickupInput, 'pickup_suggestions');
    setupAutocomplete(dropoffInput, 'dropoff_suggestions');
}

function setupAutocomplete(input, suggestionsDivId) {
    let timeout = null;
    
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(() => {
            const query = this.value;
            if (query.length < 3) return;
            
            fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    const suggestionsDiv = document.getElementById(suggestionsDivId);
                    suggestionsDiv.innerHTML = '';
                    
                    data.slice(0, 5).forEach(place => {
                        const div = document.createElement('a');
                        div.className = 'list-group-item list-group-item-action';
                        div.textContent = place.display_name;
                        div.href = '#';
                        div.onclick = (e) => {
                            e.preventDefault();
                            input.value = place.display_name;
                            suggestionsDiv.innerHTML = '';
                        };
                        suggestionsDiv.appendChild(div);
                    });
                });
        }, 300);
    });
}

window.onload = () => {
    initMap();
    initAddressAutocomplete();
};
    </script>
</body>
</html>
