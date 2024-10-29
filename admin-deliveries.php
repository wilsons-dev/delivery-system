<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

// Get all deliveries with related info
$stmt = $db->query("
    SELECT d.*, 
           c.name as customer_name,
           dr.name as driver_name,
           r.rating
    FROM deliveries d
    LEFT JOIN users c ON d.customer_id = c.id
    LEFT JOIN users dr ON d.driver_id = dr.id
    LEFT JOIN delivery_ratings r ON d.id = r.delivery_id
    ORDER BY d.created_at DESC
");
$deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available drivers for assignment
$drivers = $db->query("
    SELECT id, name 
    FROM users 
    WHERE role = 'driver' AND active = 1
    ORDER BY name
")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Delivery Management';
$currentPage = 'deliveries';
include 'admin/layout/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Delivery Management</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Customer</th>
                            <th>Driver</th>
                            <th>Pickup</th>
                            <th>Dropoff</th>
                            <th>Status</th>
                            <th>Rating</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($deliveries as $delivery): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($delivery['tracking_id']); ?></td>
                            <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                            <td><?php echo $delivery['driver_name'] ? htmlspecialchars($delivery['driver_name']) : 'Not assigned'; ?></td>
                            <td><?php echo htmlspecialchars($delivery['pickup_address']); ?></td>
                            <td><?php echo htmlspecialchars($delivery['dropoff_address']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($delivery['status']); ?>">
                                    <?php echo ucfirst($delivery['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if($delivery['rating']): ?>
                                    <?php echo $delivery['rating']; ?> ⭐
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewDelivery(<?php echo $delivery['id']; ?>)">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <?php if($delivery['status'] === 'pending'): ?>
                                <button class="btn btn-sm btn-success" onclick="assignDriver(<?php echo $delivery['id']; ?>)">
                                    <i class="bi bi-person-check"></i>
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

<!-- Assign Driver Modal -->
<div class="modal fade" id="assignDriverModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Driver</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="assignDriverForm">
                    <input type="hidden" id="delivery_id" name="delivery_id">
                    <div class="mb-3">
                        <label class="form-label">Select Driver</label>
                        <select class="form-select" name="driver_id" required>
                            <option value="">Choose a driver...</option>
                            <?php foreach ($drivers as $driver): ?>
                            <option value="<?php echo $driver['id']; ?>">
                                <?php echo htmlspecialchars($driver['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitAssignDriver()">Assign Driver</button>
            </div>
        </div>
    </div>
</div>

<script>
function viewDelivery(id) {
    window.location.href = `admin-delivery-details.php?id=${id}`;
}

function assignDriver(deliveryId) {
    document.getElementById('delivery_id').value = deliveryId;
    new bootstrap.Modal(document.getElementById('assignDriverModal')).show();
}

function submitAssignDriver() {
    const formData = new FormData(document.getElementById('assignDriverForm'));
    formData.append('action', 'assign_driver');
    
    fetch('admin-actions.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if(data.success) {
            location.reload();
        }
    });
}
</script>

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
}?>