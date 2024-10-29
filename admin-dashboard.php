<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

// Get statistics
$stats = [
    'total_deliveries' => $db->query("SELECT COUNT(*) FROM deliveries")->fetchColumn(),
    'active_deliveries' => $db->query("SELECT COUNT(*) FROM deliveries WHERE status NOT IN ('delivered', 'cancelled')")->fetchColumn(),
    'total_drivers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'driver'")->fetchColumn(),
    'total_customers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'customer'")->fetchColumn(),
    'today_deliveries' => $db->query("SELECT COUNT(*) FROM deliveries WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
    'active_drivers' => $db->query("SELECT COUNT(*) FROM users WHERE role = 'driver' AND active = 1")->fetchColumn(),
    'pending_deliveries' => $db->query("SELECT COUNT(*) FROM deliveries WHERE status = 'pending'")->fetchColumn()
];

// Get recent deliveries
$stmt = $db->query("
    SELECT d.*, c.name as customer_name, dr.name as driver_name 
    FROM deliveries d 
    LEFT JOIN users c ON d.customer_id = c.id 
    LEFT JOIN users dr ON d.driver_id = dr.id 
    ORDER BY d.created_at DESC 
    LIMIT 10
");
$recent_deliveries = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';
include 'admin/layout/header.php';
?>

<div class="container mt-4">
    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5>Today's Deliveries</h5>
                    <h2><?php echo $stats['today_deliveries']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5>Active Drivers</h5>
                    <h2><?php echo $stats['active_drivers']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5>Pending Deliveries</h5>
                    <h2><?php echo $stats['pending_deliveries']; ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5>Total Customers</h5>
                    <h2><?php echo $stats['total_customers']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <!-- System Status -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-shield-check"></i> System Status</h5>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Server Status
                            <span class="badge bg-success rounded-pill">Online</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            Active Sessions
                            <span class="badge bg-primary rounded-pill">23</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            System Load
                            <span class="badge bg-info rounded-pill">45%</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-lightning"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="admin-users.php" class="btn btn-lg btn-outline-primary">
                            <i class="bi bi-people"></i> Manage Users
                        </a>
                        <a href="admin-deliveries.php" class="btn btn-lg btn-outline-success">
                            <i class="bi bi-truck"></i> Manage Deliveries
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Deliveries -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Recent Deliveries</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Tracking ID</th>
                            <th>Customer</th>
                            <th>Driver</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_deliveries as $delivery): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($delivery['tracking_id']); ?></td>
                            <td><?php echo htmlspecialchars($delivery['customer_name']); ?></td>
                            <td><?php echo $delivery['driver_name'] ? htmlspecialchars($delivery['driver_name']) : 'Not assigned'; ?></td>
                            <td>
                                <span class="badge bg-<?php echo getStatusColor($delivery['status']); ?>">
                                    <?php echo ucfirst($delivery['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y H:i', strtotime($delivery['created_at'])); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewDelivery('<?php echo $delivery['id']; ?>')">
                                    View
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include 'admin/layout/footer.php'; ?>
