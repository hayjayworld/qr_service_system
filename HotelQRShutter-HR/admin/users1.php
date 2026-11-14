<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAdmin(); // Only admin can access this page

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_user'])) {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];
        $full_name = trim($_POST['full_name']);
        
        // Validate role permissions
        if ($_SESSION['admin_role'] === 'manager' && $role === 'admin') {
            die("Managers cannot create admin accounts");
        }
        
        if (!empty($username) && !empty($password)) {
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            $sql = "INSERT INTO admin (username, password_hash, full_name, role, created_by) 
                    VALUES (?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$username, $password, $full_name, $role, $_SESSION['admin_id']]);
            $message = "User created successfully!";
        } else {
            $error = "Username and password are required";
        }
    }

    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        deleteUser($pdo, $user_id);
        $_SESSION['message'] = 'User deleted successfully';
        header('Location: users.php');
        exit();
    }
}
// Get all users
$users = $pdo->query("SELECT * FROM admin ORDER BY role, username")->fetchAll();
?>

<?php include 'admin_header.php'; ?>

<div class="container">
    <h1><i class="fas fa-users-cog"></i> User Management</h1>
    
    <?php if (isset($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    
    <div class="card">
        <div class="card-header">
            <h2>Create New User</h2>
        </div>
        <div class="card-body">
            <form method="POST">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" name="username" required>
                </div>
                
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <label>Full Name:</label>
                    <input type="text" name="full_name" required>
                </div>
                
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="staff">Staff</option>
                        <?php if ($_SESSION['admin_role'] === 'admin'): ?>
                            <option value="manager">Manager</option>
                        <?php endif; ?>
                    </select>
                </div>
                
                <button type="submit" name="create_user" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Create User
                </button>
            </form>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h2>Existing Users</h2>
        </div>
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Full Name</th>
                        <th>Role</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['username']) ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td>
                            <span class="badge <?= $user['role'] ?>">
                                <?= ucfirst($user['role']) ?>
                            </span>
                        </td>
                        <td>
                            <?php 
                            if ($user['created_by']) {
                                $creator = $pdo->query("SELECT username FROM admin WHERE id = {$user['created_by']}")->fetch();
                                echo htmlspecialchars($creator['username']);
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($_SESSION['admin_id'] !== $user['id']): ?>
                            <form method="POST" action = "users.php" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <!-- <button type="submit" name="reset_password" class="btn btn-sm btn-warning">
                                    <i class="fas fa-key"></i> Reset Password
                                </button> -->
                                <button type="submit" name="delete_user" class="btn btn-sm btn-danger" 
                                        onclick="return confirm('Are you sure?')">
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

<?php include 'admin_footer.php'; ?>