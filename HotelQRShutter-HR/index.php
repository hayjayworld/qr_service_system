<?php
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Redirect to admin dashboard if already logged in
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit();
}

// Redirect to login page
header('Location: login.php');
exit();
?>