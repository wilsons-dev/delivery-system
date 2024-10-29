<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

$delivery_id = $_GET['id'] ?? 0;

// Get delivery details with related information
$stmt = $db->prepare("
    SELECT d.*, 
           c.name as customer_name, c.email as customer_email,
           dr.name as driver_name, dr.email as driver_email,
           r.rating, r.comment as rating_comment,
           r.created_at as rating_date
    FROM deliveries d
    LEFT JOIN users c ON d.customer_id = c.id
    LEFT JOIN users dr ON d.driver_id = dr.id
    LEFT JOIN delivery_ratings r ON d.id = r.delivery_id
    WHERE d.id = ?
");
$stmt->execute([$delivery_id]);
$delivery = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Delivery Details';
$currentPage = 'deliveries';
include 'admin/layout/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Delivery Details</h5>
                    <span class="badge bg-light text-dark">
                        Tracking ID: <?php echo htmlspecialchars($delivery['tracking_id']); ?>
                    </span>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <h6>Pickup Address</h6>
                            <p><?php echo htmlspecialchars($delivery['pickup_address']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <h6>Dropoff Address</h6>
                            <p><?php echo htmlspecialchars($delivery['dropoff_address']); ?></p>
                        </div>
                    </div>
                    
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <h6>Status</h6>
                            <span class="badge bg-<?php echo getStatusColor($delivery['status']); ?>">
                                <?php echo ucfirst($delivery['status']); ?>
                            </span>
                        </div>
                        <div class="col-md-4">
                            <h6>Created At</h6>
                            <p><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></p>
                        </div>
                        <div class="col-md-4">
                            <h6>Last Updated</h6>
                            <p><?php echo date('M d, Y H:i', strtotime($delivery['updated_at'])); ?></p>
                        </div>
                    </div>

                    <?php if($delivery['special_instructions']): ?>
                    <div class="mb-3">
                        <h6>Special Instructions</h6>
                        <p><?php echo htmlspecialchars($delivery['special_instructions']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Delivery Timeline</h5>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <i class="bi bi-circle-fill text-success"></i>
                            <span>Created</span>
                            <small><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></small>
                        </div>
                        <?php if($delivery['driver_id']): ?>
                        <div class="timeline-item">
                            <i class="bi bi-circle-fill text-info"></i>
                            <span>Driver Assigned</span>
                            <small><?php echo date('M d, Y H:i', strtotime($delivery['updated_at'])); ?></small>
                        </div>
                        <?php endif; ?>
                        <!-- Add more timeline items based on status changes -->
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Customer Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($delivery['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($delivery['customer_email']); ?></p>
                </div>
            </div>

            <?php if($delivery['driver_id']): ?>
            <div class="card mb-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">Driver Details</h5>
                </div>
                <div class="card-body">
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($delivery['driver_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($delivery['driver_email']); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <?php if($delivery['rating']): ?>
            <div class="card">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">Delivery Rating</h5>
                </div>
                <div class="card-body">
                    <p><strong>Rating:</strong> <?php echo $delivery['rating']; ?> ⭐</p>
                    <?php if($delivery['rating_comment']): ?>
                    <p><strong>Comment:</strong> <?php echo htmlspecialchars($delivery['rating_comment']); ?></p>
                    <?php endif; ?>
                    <small class="text-muted">Rated on <?php echo date('M d, Y', strtotime($delivery['rating_date'])); ?></small>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding: 20px 0;
}

.timeline-item {
    padding: 10px 0;
    position: relative;
    padding-left: 30px;
}

.timeline-item i {
    position: absolute;
    left: 0;
    top: 15px;
}

.timeline-item small {
    display: block;
    color: #6c757d;
}
</style>

<?php include 'admin/layout/footer.php'; ?>
