<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $roleId = (int)$_POST['role_id'];
        $gender = $_POST['gender'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $dob = $_POST['date_of_birth'];
        $address = trim($_POST['address']);
        $emergencyContact = trim($_POST['emergency_contact']);
        $emergencyPhone = trim($_POST['emergency_phone']);
        
        if (!empty($username) && !empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO admin (username, password_hash, first_name, last_name, role_id, gender, phone, email, date_of_birth, address, emergency_contact, emergency_phone, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $hashed, $firstName, $lastName, $roleId, $gender, $phone, $email, $dob, $address, $emergencyContact, $emergencyPhone, $_SESSION['admin_id']]);
            $_SESSION['success'] = "User created successfully!";
        } else {
            $_SESSION['error'] = "Username and password are required";
        }
        header('Location: users.php');
        exit();
    }

    if (isset($_POST['update_user'])) {
        $userId = (int)$_POST['user_id'];
        $firstName = trim($_POST['first_name']);
        $lastName = trim($_POST['last_name']);
        $roleId = (int)$_POST['role_id'];
        $gender = $_POST['gender'];
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $dob = $_POST['date_of_birth'];
        $address = trim($_POST['address']);
        $emergencyContact = trim($_POST['emergency_contact']);
        $emergencyPhone = trim($_POST['emergency_phone']);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE admin SET first_name=?, last_name=?, role_id=?, gender=?, phone=?, email=?, date_of_birth=?, address=?, emergency_contact=?, emergency_phone=?, is_active=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$firstName, $lastName, $roleId, $gender, $phone, $email, $dob, $address, $emergencyContact, $emergencyPhone, $isActive, $userId]);
        
        $_SESSION['success'] = "User updated successfully!";
        header('Location: users.php?tab=users');
        exit();
    }

    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        // Add your deleteUser function or direct delete query here
        $stmt = $pdo->prepare("DELETE FROM admin WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success'] = 'User deleted successfully';
        header('Location: users.php?tab=users');
        exit();
    }
}

// Get user details for view/edit
$userDetails = null;
$action = '';
$userId = 0;

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $userId = (int)$_GET['view'];
    $action = 'view';
} elseif (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $userId = (int)$_GET['edit'];
    $action = 'edit';
}

if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT a.*, r.role_name FROM admin a LEFT JOIN roles r ON a.role_id = r.id WHERE a.id = ?");
    $stmt->execute([$userId]);
    $userDetails = $stmt->fetch();
    
    // If user not found, redirect to users list
    if (!$userDetails) {
        header('Location: users.php?tab=users');
        exit();
    }
}

// Get all users and roles
$users = $pdo->query("SELECT a.*, r.role_name FROM admin a LEFT JOIN roles r ON a.role_id = r.id ORDER BY a.created_at DESC")->fetchAll();
$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();

// Get current tab
$tab = $_GET['tab'] ?? 'users';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
    .tabs { display: flex; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
    .tab-btn { padding: 10px 20px; border: none; background: none; cursor: pointer; border-bottom: 3px solid transparent; }
    .tab-btn.active { border-bottom-color: #3498db; font-weight: bold; }
    .tab-content { display: none; }
    .tab-content.active { display: block; }
    .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 15px; }
    .user-details { background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    .detail-row { display: flex; margin-bottom: 10px; }
    .detail-label { font-weight: bold; width: 150px; color: #495057; }
    .detail-value { flex: 1; }
    .action-btns { display: flex; gap: 10px; margin-top: 20px; }
    
    /* Modal Styles with Scrollbar */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.5);
        overflow: auto;
    }
    
    .modal-content {
        background-color: white;
        margin: 2% auto;
        padding: 0;
        border-radius: 8px;
        width: 90%;
        max-width: 800px;
        max-height: 90vh;
        display: flex;
        flex-direction: column;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
    }
    
    .modal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 20px;
        border-bottom: 1px solid #eee;
        background: #f8f9fa;
        border-radius: 8px 8px 0 0;
        position: sticky;
        top: 0;
        z-index: 10;
    }
    
    .modal-header h2 {
        margin: 0;
        color: #2c3e50;
        font-size: 1.5em;
    }
    
    .close {
        color: #aaa;
        font-size: 28px;
        font-weight: bold;
        text-decoration: none;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    .close:hover {
        color: #333;
        background-color: #eee;
        border-radius: 50%;
    }
    
    .modal-body {
        padding: 20px;
        overflow-y: auto;
        flex: 1;
    }
    
    .modal-footer {
        padding: 20px;
        border-top: 1px solid #eee;
        background: #f8f9fa;
        border-radius: 0 0 8px 8px;
        position: sticky;
        bottom: 0;
    }
    
    /* Custom Scrollbar for Modal */
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }
    
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb {
        background: #c1c1c1;
        border-radius: 4px;
    }
    
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #a8a8a8;
    }
    
    /* For Firefox */
    .modal-body {
        scrollbar-width: thin;
        scrollbar-color: #c1c1c1 #f1f1f1;
    }
    
    /* Responsive adjustments */
    @media (max-width: 768px) {
        .modal-content {
            margin: 5% auto;
            width: 95%;
            max-width: 95%;
        }
        
        .detail-row {
            flex-direction: column;
        }
        
        .detail-label {
            width: 100%;
            margin-bottom: 5px;
        }
    }
</style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-users-cog"></i> User Management</h1>
        
        <?php 
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . $_SESSION['success'] . '</div>';
            unset($_SESSION['success']);
        }
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-danger">' . $_SESSION['error'] . '</div>';
            unset($_SESSION['error']);
        }
        ?>
        
        <!-- View User Modal -->
        <!-- View User Modal -->
<?php if ($action === 'view' && $userDetails): ?>
<div class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>User Details</h2>
            <a href="users.php?tab=users" class="close">&times;</a>
        </div>
        <div class="modal-body">
            <div class="user-details">
                <div class="detail-row">
                    <span class="detail-label">Name:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['first_name'] . ' ' . $userDetails['last_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Username:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['username']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Role:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['role_name']) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Gender:</span>
                    <span class="detail-value"><?= htmlspecialchars(ucfirst($userDetails['gender'] ?? 'Not specified')) ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Email:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['email'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['phone'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Date of Birth:</span>
                    <span class="detail-value"><?= $userDetails['date_of_birth'] ? date('M j, Y', strtotime($userDetails['date_of_birth'])) : 'Not specified' ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Address:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['address'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Emergency Contact:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['emergency_contact'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Emergency Phone:</span>
                    <span class="detail-value"><?= htmlspecialchars($userDetails['emergency_phone'] ?? 'Not specified') ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge <?= $userDetails['is_active'] ? 'status-active' : 'status-inactive' ?>">
                            <?= $userDetails['is_active'] ? 'Active' : 'Inactive' ?>
                        </span>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Member Since:</span>
                    <span class="detail-value"><?= date('M j, Y', strtotime($userDetails['created_at'])) ?></span>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <div class="action-btns">
                <a href="users.php?tab=users" class="btn btn-secondary">Close</a>
                <a href="users.php?tab=users&edit=<?= $userDetails['id'] ?>" class="btn btn-primary">Edit User</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Edit User Modal -->
<?php if ($action === 'edit' && $userDetails): ?>
<div class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <a href="users.php?tab=users" class="close">&times;</a>
        </div>
        <div class="modal-body">
            <form method="POST" id="editUserForm">
                <input type="hidden" name="user_id" value="<?= $userDetails['id'] ?>">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($userDetails['first_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($userDetails['last_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?= $userDetails['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $userDetails['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= $userDetails['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($userDetails['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($userDetails['date_of_birth'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>" <?= $userDetails['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" value="<?= htmlspecialchars($userDetails['emergency_contact'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Phone</label>
                        <input type="tel" name="emergency_phone" value="<?= htmlspecialchars($userDetails['emergency_phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <div>
                            <input type="checkbox" name="is_active" value="1" <?= $userDetails['is_active'] ? 'checked' : '' ?>>
                            Active
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($userDetails['address'] ?? '') ?></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <div class="action-btns">
                <a href="users.php?tab=users" class="btn btn-secondary">Cancel</a>
                <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

        <div class="tabs">
            <button class="tab-btn <?= $tab === 'users' ? 'active' : '' ?>" onclick="openTab('users')">
                <i class="fas fa-users"></i> Users
            </button>
            <button class="tab-btn <?= $tab === 'create' ? 'active' : '' ?>" onclick="openTab('create')">
                <i class="fas fa-user-plus"></i> Create User
            </button>
            <button class="tab-btn <?= $tab === 'roles' ? 'active' : '' ?>" onclick="openTab('roles')">
                <i class="fas fa-shield-alt"></i> Roles
            </button>
        </div>
        
        <!-- Users Tab -->
        <div id="users-tab" class="tab-content <?= $tab === 'users' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2>Existing Users</h2>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Username</th>
                                <th>Role</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['role_name']) ?></td>
                                <td>
                                    <?= htmlspecialchars($user['email']) ?><br>
                                    <?= htmlspecialchars($user['phone']) ?>
                                </td>
                                <td>
                                    <span class="status-badge <?= $user['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                        <?= $user['is_active'] ? 'Active' : 'Inactive' ?>
                                    </span>
                                </td>
                                <td class="action-btns">
                                    <a href="users.php?tab=users&view=<?= $user['id'] ?>" class="btn btn-info">
                                        <i class="fas fa-eye"></i> View
                                    </a>
                                    <a href="users.php?tab=users&edit=<?= $user['id'] ?>" class="btn btn-warning">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                    <?php if ($_SESSION['admin_id'] !== $user['id']): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                        <button type="submit" name="delete_user" class="btn btn-danger" 
                                                onclick="return confirm('Are you sure you want to delete this user?')">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Create User Tab -->
        <div id="create-tab" class="tab-content <?= $tab === 'create' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2>Create New User</h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone">
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth">
                            </div>
                            
                            <div class="form-group">
                                <label>Role *</label>
                                <select name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Emergency Contact</label>
                                <input type="text" name="emergency_contact">
                            </div>
                            
                            <div class="form-group">
                                <label>Emergency Phone</label>
                                <input type="tel" name="emergency_phone">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="create_user" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Create User
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- Roles Tab -->
        <div id="roles-tab" class="tab-content <?= $tab === 'roles' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2>Manage Roles</h2>
                    <a href="roles_management.php" class="btn btn-primary">
                        <i class="fas fa-cog"></i> Manage Role Permissions
                    </a>
                </div>
                <div class="card-body">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Role Name</th>
                                <th>Description</th>
                                <th>Permissions</th>
                                <th>Users</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($roles as $role): 
                                $userCount = $pdo->query("SELECT COUNT(*) FROM admin WHERE role_id = {$role['id']}")->fetchColumn();
                            ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($role['role_name']) ?></strong></td>
                                <td><?= htmlspecialchars($role['description']) ?></td>
                                <td>
                                    <?php
                                    $permissions = [];
                                    if ($role['can_manage_users']) $permissions[] = 'Users';
                                    if ($role['can_manage_services']) $permissions[] = 'Services';
                                    if ($role['can_manage_categories']) $permissions[] = 'Categories';
                                    if ($role['can_manage_orders']) $permissions[] = 'Orders';
                                    if ($role['can_manage_qr']) $permissions[] = 'QR Codes';
                                    if ($role['can_manage_reservations']) $permissions[] = 'Reservations';
                                    if ($role['can_manage_complaints']) $permissions[] = 'Complaints';
                                    if ($role['can_view_analytics']) $permissions[] = 'Analytics';
                                    if ($role['can_manage_settings']) $permissions[] = 'Settings';
                                    echo implode(', ', $permissions);
                                    ?>
                                </td>
                                <td><?= $userCount ?> users</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
function openTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);
}

// Close modal when clicking outside or pressing Escape
function setupModalHandlers() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            window.location.href = 'users.php?tab=users';
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            window.location.href = 'users.php?tab=users';
        }
    });
    
    // Prevent modal content clicks from closing modal
    document.querySelectorAll('.modal-content').forEach(content => {
        content.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });
}

// Initialize modal handlers when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupModalHandlers();
    
    // Auto-scroll to top when modal opens
    const modals = document.querySelectorAll('.modal[style*="display: block"]');
    if (modals.length > 0) {
        modals[0].scrollTop = 0;
    }
});
</script>
</body>
</html>