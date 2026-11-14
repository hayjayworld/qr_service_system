<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR(); // New authorization function

// Get HR statistics
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM admin WHERE is_active = true")->fetchColumn();
$activeLeaveRequests = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
$pendingPayroll = $pdo->query("SELECT COUNT(*) FROM payroll WHERE status = 'draft'")->fetchColumn();
$todayAttendance = $pdo->query("SELECT COUNT(*) FROM attendance WHERE DATE(check_in) = CURDATE()")->fetchColumn();

// Get recent leave requests
$recentLeaves = $pdo->query("
    SELECT lr.*, a.first_name, a.last_name, a.employee_id 
    FROM leave_requests lr 
    JOIN admin a ON lr.admin_id = a.id 
    ORDER BY lr.created_at DESC 
    LIMIT 5
")->fetchAll();

// Get upcoming birthdays
$upcomingBirthdays = $pdo->query("
    SELECT first_name, last_name, date_of_birth 
    FROM admin 
    WHERE MONTH(date_of_birth) = MONTH(CURDATE()) 
    AND DAY(date_of_birth) >= DAY(CURDATE())
    AND is_active = true
    ORDER BY DAY(date_of_birth) 
    LIMIT 5
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Dashboard - Hotel Management System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 20px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .stat-number { font-size: 2em; font-weight: bold; margin: 10px 0; }
        .quick-actions { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .quick-action-btn { padding: 15px; background: #3498db; color: white; text-decoration: none; border-radius: 5px; text-align: center; }
        .section { background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-users"></i> HR Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <i class="fas fa-users fa-2x"></i>
                <div class="stat-number"><?= $totalEmployees ?></div>
                <div>Total Employees</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-calendar-alt fa-2x"></i>
                <div class="stat-number"><?= $activeLeaveRequests ?></div>
                <div>Pending Leave Requests</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-money-bill-wave fa-2x"></i>
                <div class="stat-number"><?= $pendingPayroll ?></div>
                <div>Pending Payroll</div>
            </div>
            <div class="stat-card">
                <i class="fas fa-clock fa-2x"></i>
                <div class="stat-number"><?= $todayAttendance ?></div>
                <div>Today's Attendance</div>
            </div>
        </div>

        <div class="quick-actions">
            <a href="employees.php" class="quick-action-btn">
                <i class="fas fa-user-plus"></i><br>Add Employee
            </a>
            <a href="hr_attendance.php" class="quick-action-btn">
                <i class="fas fa-clock"></i><br>Attendance
            </a>
            <a href="leave_management.php" class="quick-action-btn">
                <i class="fas fa-calendar"></i><br>Leave Management
            </a>
            <a href="payroll.php" class="quick-action-btn">
                <i class="fas fa-calculator"></i><br>Payroll
            </a>
            <a href="reports.php" class="quick-action-btn">
                <i class="fas fa-chart-bar"></i><br>HR Reports
            </a>
        </div>

        <div class="dashboard-sections">
            <div class="section">
                <h3>Recent Leave Requests</h3>
                <?php if ($recentLeaves): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Leave Type</th>
                                <th>Dates</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLeaves as $leave): ?>
                            <tr>
                                <td><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></td>
                                <td><?= ucfirst($leave['leave_type']) ?></td>
                                <td><?= date('M j', strtotime($leave['start_date'])) ?> - <?= date('M j', strtotime($leave['end_date'])) ?></td>
                                <td>
                                    <span class="status-badge status-<?= $leave['status'] ?>">
                                        <?= ucfirst($leave['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="leave_management.php?action=view&id=<?= $leave['id'] ?>" class="btn btn-sm">View</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No recent leave requests.</p>
                <?php endif; ?>
            </div>

            <div class="section">
                <h3>Upcoming Birthdays</h3>
                <?php if ($upcomingBirthdays): ?>
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Employee</th>
                                <th>Birthday</th>
                                <th>Days Until</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($upcomingBirthdays as $employee): 
                                $birthday = new DateTime(date('Y') . '-' . date('m-d', strtotime($employee['date_of_birth'])));
                                $today = new DateTime();
                                $daysUntil = $today->diff($birthday)->days;
                            ?>
                            <tr>
                                <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                                <td><?= date('F j', strtotime($employee['date_of_birth'])) ?></td>
                                <td><?= $daysUntil ?> days</td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>No upcoming birthdays this month.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>