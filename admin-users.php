<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

$db = new PDO('mysql:host=localhost;dbname=delivery_service', 'root', 'root');

// Get all users with their stats
$stmt = $db->query("
    SELECT u.*, 
           COUNT(DISTINCT d.id) as total_deliveries,
           AVG(r.rating) as avg_rating
    FROM users u 
    LEFT JOIN deliveries d ON (u.role = 'customer' AND u.id = d.customer_id) 
                          OR (u.role = 'driver' AND u.id = d.driver_id)
    LEFT JOIN delivery_ratings r ON u.role = 'driver' AND u.id = r.driver_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = 'User Management';
$currentPage = 'users';
include 'admin/layout/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">User Management</h5>
            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="bi bi-person-plus"></i> Add User
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Deliveries</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><span class="badge bg-info"><?php echo ucfirst($user['role']); ?></span></td>
                            <td><?php echo $user['total_deliveries']; ?></td>
                            <td>
                                <?php if($user['role'] == 'driver' && $user['avg_rating']): ?>
                                    <?php echo number_format($user['avg_rating'], 1); ?> ⭐
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-<?php echo $user['active'] ? 'success' : 'danger'; ?>">
                                    <?php echo $user['active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="editUser(<?php echo $user['id']; ?>)">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button class="btn btn-sm btn-<?php echo $user['active'] ? 'danger' : 'success'; ?>" 
                                        onclick="toggleUserStatus(<?php echo $user['id']; ?>)">
                                    <i class="bi bi-<?php echo $user['active'] ? 'person-x' : 'person-check'; ?>"></i>
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

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addUserForm">
                    <div class="mb-3">
                        <label class="form-label">Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Role</label>
                        <select class="form-select" name="role" required>
                            <option value="customer">Customer</option>
                            <option value="driver">Driver</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" class="form-control" name="password" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="submitAddUser()">Add User</button>
            </div>
        </div>
    </div>
</div>

<script>
function editUser(id) {
    // Implementation for editing user
    fetch(`get_user.php?id=${id}`)
        .then(response => response.json())
        .then(user => {
            // Show edit modal with user data
        });
}

function toggleUserStatus(id) {
    if(confirm('Are you sure you want to change this user\'s status?')) {
        fetch('admin-actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'toggle_user_status',
                user_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if(data.success) {
                location.reload();
            }
        });
    }
}

function submitAddUser() {
    const formData = new FormData(document.getElementById('addUserForm'));
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

<?php include 'admin/layout/footer.php'; ?>
