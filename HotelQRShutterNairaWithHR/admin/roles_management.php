<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_role'])) {
        $roleName = trim($_POST['role_name']);
        $description = trim($_POST['description']);
        
        $sql = "INSERT INTO roles (role_name, description, can_manage_users, can_manage_services, can_manage_categories, can_manage_orders, can_manage_qr, can_manage_rooms, can_manage_reservations, can_manage_complaints, can_view_analytics,can_manage_hr, can_manage_discounts, can_manage_settings) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $roleName, $description,
            $_POST['can_manage_users'] ?? 0,
            $_POST['can_manage_services'] ?? 0,
            $_POST['can_manage_categories'] ?? 0,
            $_POST['can_manage_orders'] ?? 0,
            $_POST['can_manage_qr'] ?? 0,
            $_POST['can_manage_rooms'] ?? 0,
            $_POST['can_manage_reservations'] ?? 0,
            $_POST['can_manage_complaints'] ?? 0,
            $_POST['can_view_analytics'] ?? 0,
            $_POST['can_manage_hr'] ?? 0,
            $_POST['can_manage_discounts'] ?? 0,
            $_POST['can_manage_settings'] ?? 0
        ]);
        $_SESSION['success'] = "Role created successfully!";
    }
    
    if (isset($_POST['update_role'])) {
        $roleId = (int)$_POST['role_id'];
        $sql = "UPDATE roles SET can_manage_users=?, can_manage_services=?, can_manage_categories=?, can_manage_orders=?, can_manage_qr=?, can_manage_rooms=?, can_manage_reservations=?, can_manage_complaints=?, can_view_analytics=?, can_manage_hr=?, can_manage_discounts=?, can_manage_settings=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $_POST['can_manage_users'] ?? 0,
            $_POST['can_manage_services'] ?? 0,
            $_POST['can_manage_categories'] ?? 0,
            $_POST['can_manage_orders'] ?? 0,
            $_POST['can_manage_qr'] ?? 0,
            $_POST['can_manage_rooms'] ?? 0,
            $_POST['can_manage_reservations'] ?? 0,
            $_POST['can_manage_complaints'] ?? 0,
            $_POST['can_view_analytics'] ?? 0,
            $_POST['can_manage_hr'] ?? 0,
            $_POST['can_manage_discounts'] ?? 0,
            $_POST['can_manage_settings'] ?? 0,
            $roleId
        ]);
        $_SESSION['success'] = "Role updated successfully!";
    }
    
    header('Location: roles_management.php');
    exit();
}

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Role Management</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Role Management</h1>
        <?php displayMessages(); ?>
        
        <div class="card">
            <h2>Create New Role</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Role Name</label>
                    <input type="text" name="role_name" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <button type="submit" name="create_role" class="btn btn-primary">Create Role</button>
            </form>
        </div>
        
        <div class="card">
            <h2>Role Permissions</h2>
            <?php foreach ($roles as $role): ?>
            <form method="POST" class="role-form">
                <input type="hidden" name="role_id" value="<?= $role['id'] ?>">
                <h3><?= htmlspecialchars($role['role_name']) ?></h3>
                
                <div class="permissions-grid">
                    <label><input type="checkbox" name="can_manage_users" value="1" <?= $role['can_manage_users'] ? 'checked' : '' ?>> Manage Users</label>
                    <label><input type="checkbox" name="can_manage_services" value="1" <?= $role['can_manage_services'] ? 'checked' : '' ?>> Manage Services</label>
                    <label><input type="checkbox" name="can_manage_categories" value="1" <?= $role['can_manage_categories'] ? 'checked' : '' ?>> Manage Categories</label>
                    <label><input type="checkbox" name="can_manage_orders" value="1" <?= $role['can_manage_orders'] ? 'checked' : '' ?>> Manage Orders</label>
                    <label><input type="checkbox" name="can_manage_qr" value="1" <?= $role['can_manage_qr'] ? 'checked' : '' ?>> Manage QR Codes</label>
                    <!--<label><input type="checkbox" name="can_manage_rooms" value="1" <?= $role['can_manage_rooms'] ? 'checked' : '' ?>> Manage Rooms</label>
                    <label><input type="checkbox" name="can_manage_reservations" value="1" <?= $role['can_manage_reservations'] ? 'checked' : '' ?>> Manage Reservations</label>-->
                    <label><input type="checkbox" name="can_manage_complaints" value="1" <?= $role['can_manage_complaints'] ? 'checked' : '' ?>> Manage Complaints</label>
                    <label><input type="checkbox" name="can_view_analytics" value="1" <?= $role['can_view_analytics'] ? 'checked' : '' ?>> View Analytics</label>
                    <label><input type="checkbox" name="can_manage_hr" value="1" <?= $role['can_manage_hr'] ? 'checked' : '' ?>> HR management</label>
                    <!--<label><input type="checkbox" name="can_manage_discounts" value="1" <?= $role['can_manage_discounts'] ? 'checked' : '' ?>> Discounts management</label>-->
                    <label><input type="checkbox" name="can_manage_settings" value="1" <?= $role['can_manage_settings'] ? 'checked' : '' ?>> Manage Settings</label>
                </div>
                
                <button type="submit" name="update_role" class="btn">Update Permissions</button>
            </form>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>