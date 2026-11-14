<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle attendance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        $st = $pdo->prepare("SELECT id  FROM attendance WHERE admin_id = ? AND DATE(check_in) = CURDATE()");
        $st->execute([$_SESSION['admin_id']]);
        $existing = $st->fetch();
        if($existing){
            $_SESSION['error'] = "Already checked in for today";
        }else{
            $stmt = $pdo->prepare("INSERT INTO attendance (admin_id, check_in) VALUES (?, NOW())");
            $stmt->execute([$_SESSION['admin_id']]);
            $_SESSION['success'] = "Checked in successfully!";
        }
    }
    
    if (isset($_POST['check_out'])) {
        $st = $pdo->prepare("SELECT id  FROM attendance WHERE admin_id = ? AND DATE(check_out) = CURDATE()");
        $st->execute([$_SESSION['admin_id']]);
        $existing = $st->fetch();
        if($existing){
            $_SESSION['error'] = "Already checked out for today";
        }else{
            $stmt = $pdo->prepare("UPDATE attendance SET check_out = NOW(), hours_worked = TIMESTAMPDIFF(HOUR, check_in, NOW()) WHERE admin_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
            $stmt->execute([$_SESSION['admin_id']]);
            $_SESSION['success'] = "Checked out successfully!";
        }
    }
}

// Get attendance records
$admin_in = $_SESSION['admin_id'];
$attendance = $pdo->query("
    SELECT a.*, adm.first_name, adm.last_name, adm.employee_id AS employee_id 
    FROM attendance a 
    LEFT JOIN admin adm ON a.admin_id = adm.id 
    WHERE a.admin_id = '$admin_in'
    ORDER BY a.check_in DESC
    LIMIT 50
")->fetchAll();

$pageTitle = "Attendance Management - HR System";
?>

<!DOCTYPE html>
<html>
<head>
    <title>Attendance Management - HR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-clock"></i> Attendance Management</h1>

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
        
        <!-- Check-in/out buttons -->
        <div class="card">
            <h3>Today's Attendance</h3>
            <form method="POST" class="attendance-actions">
                <button type="submit" name="check_in" class="btn btn-success">Check In</button>
                <button type="submit" name="check_out" class="btn btn-danger">Check Out</button>
            </form>
        </div>
        
        <!-- Attendance records -->
        <div class="card">
            <h3>Attendance History</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Date</th>
                        <th>Check In</th>
                        <th>Check Out</th>
                        <th>Hours Worked</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($attendance as $record): ?>
                    <tr>
                        <td><?= htmlspecialchars($record['employee_id']) ?></td>
                        <td><?= date('M j, Y', strtotime($record['check_in'])) ?></td>
                        <td><?= date('H:i', strtotime($record['check_in'])) ?></td>
                        <td><?= $record['check_out'] ? date('H:i', strtotime($record['check_out'])) : '--' ?></td>
                        <td><?= number_format($record['hours_worked'], 2) ?> hrs</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>