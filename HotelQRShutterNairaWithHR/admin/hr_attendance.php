<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle attendance actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_in'])) {
        $stmt = $pdo->prepare("INSERT INTO attendance (admin_id, check_in) VALUES (?, NOW())");
        $stmt->execute([$_SESSION['admin_id']]);
        $_SESSION['success'] = "Checked in successfully!";
    }
    
    if (isset($_POST['check_out'])) {
        $stmt = $pdo->prepare("UPDATE attendance SET check_out = NOW(), hours_worked = TIMESTAMPDIFF(HOUR, check_in, NOW()) WHERE admin_id = ? AND DATE(check_in) = CURDATE() AND check_out IS NULL");
        $stmt->execute([$_SESSION['admin_id']]);
        $_SESSION['success'] = "Checked out successfully!";
    }
}

// Get attendance records
$attendance = $pdo->query("
    SELECT a.*, adm.first_name, adm.last_name, adm.employee_id 
    FROM attendance a 
    JOIN admin adm ON a.admin_id = adm.id 
    ORDER BY a.check_in DESC 
    LIMIT 50
")->fetchAll();
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
                        <td><?= htmlspecialchars($record['first_name'] . ' ' . $record['last_name']) ?></td>
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