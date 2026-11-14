<?php
session_start();

// Redirect to login if not authenticated
function requireAuth() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: /HotelQRShutter/login.php');
        exit();
    }
}

// Password verification (now plain text comparison)
function verifyPassword($password, $stored_password) {
    return $password === $stored_password;
}

// Password hashing (no longer needed, but kept for compatibility)
function hashPassword($password) {
    return $password; // Just returns plain text
}

// Login function
function login($pdo, $username, $password) {
    $sql = "SELECT a.id, a.username, a.password_hash, r.role_name FROM admin a LEFT JOIN roles r ON r.id = a.role_id WHERE username = ?";
    $stmt = executeQuery($pdo, $sql, [$username]);
    $admin = $stmt->fetch();
    echo $password." ".$admin['password_hash'];
    if ($admin && verifyPassword($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        $_SESSION['admin_role'] = $admin['role_name'];
        return true;
    }else{
        return false;
    }
    
}

function generateStreamToken($adminId) {
    $_SESSION['stream_token'] = bin2hex(random_bytes(32));
    $_SESSION['stream_token_expiry'] = time() + 3600; // 1 hour expiry
    return $_SESSION['stream_token'];
}

function verifyStreamToken($token) {
    return isset($_SESSION['stream_token']) && 
           $_SESSION['stream_token'] === $token &&
           $_SESSION['stream_token_expiry'] > time();
}

// Add these functions to auth.php
// Replace existing auth functions with these:

function requireAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
    if ($_SESSION['admin_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}

function requireManagerOrAdmin() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
    if (!in_array($_SESSION['admin_role'], ['admin', 'manager'])) {
        header('Location: dashboard.php');
        exit();
    }
}

function requireStaffOrHigher() {
    if (!isset($_SESSION['admin_id'])) {
        header('Location: login.php');
        exit();
    }
    // All roles can access
}

function canManageServices() {
    return in_array($_SESSION['admin_role'], ['admin', 'manager']);
}

function canManageQR() {
    return in_array($_SESSION['admin_role'], ['admin', 'manager']);
}

function canManageOrders() {
    return true; // All roles can manage orders
}

// Logout function remains the same
function logout() {
    session_unset();
    session_destroy();
}
?>