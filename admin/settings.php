<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

$message = '';
$error = '';

// Get current admin details
$admin = getAdminDetails($pdo, $_SESSION['admin_id']);

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (verifyPassword($current_password, $admin['password_hash'])) {
        if ($new_password === $confirm_password) {
            if (strlen($new_password) >= 8) {
                $new_hash = hashPassword($new_password);
                updateAdminPassword($pdo, $admin['id'], $new_hash);
                $message = 'Password changed successfully!';
            } else {
                $error = 'New password must be at least 8 characters long.';
            }
        } else {
            $error = 'New passwords do not match.';
        }
    } else {
        $error = 'Current password is incorrect.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Settings - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Admin Settings</h1>
        
        <?php if ($message): ?>
            <div class="alert success"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <div class="admin-details">
            <h2>Your Account</h2>
            <div class="detail-row">
                <span class="detail-label">Username:</span>
                <span class="detail-value"><?= htmlspecialchars($admin['username']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Full Name:</span>
                <span class="detail-value"><?= htmlspecialchars($admin['full_name']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Email:</span>
                <span class="detail-value"><?= htmlspecialchars($admin['email']) ?></span>
            </div>
            <div class="detail-row">
                <span class="detail-label">Account Created:</span>
                <span class="detail-value"><?= date('Y-m-d H:i:s', strtotime($admin['created_at'])) ?></span>
            </div>
        </div>
        
        <div class="password-change">
            <h2>Change Password</h2>
            <form method="POST" action="settings.php">
                <div class="form-group">
                    <label for="current_password">Current Password:</label>
                    <input type="password" id="current_password" name="current_password" required>
                </div>
                
                <div class="form-group">
                    <label for="new_password">New Password:</label>
                    <input type="password" id="new_password" name="new_password" required>
                    <small>Must be at least 8 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm New Password:</label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <button type="submit" name="change_password" class="btn">Change Password</button>
            </form>
        </div>
    </div>
</body>
</html>