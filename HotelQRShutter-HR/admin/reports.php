<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Date range for reports
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-t');

// Get report data
$departmentStats = $pdo->prepare("
    SELECT department, COUNT(*) as employee_count, AVG(salary) as avg_salary
    FROM admin 
    WHERE is_active = true AND department IS NOT NULL
    GROUP BY department
");
$departmentStats->execute();
$departments = $departmentStats->fetchAll();

// Attendance summary
$attendanceReport = $pdo->prepare("
    SELECT a.department, 
           COUNT(DISTINCT att.admin_id) as employees_with_attendance,
           AVG(att.hours_worked) as avg_hours
    FROM attendance att
    JOIN admin a ON att.admin_id = a.id
    WHERE DATE(att.check_in) BETWEEN ? AND ?
    GROUP BY a.department
");
$attendanceReport->execute([$startDate, $endDate]);
$attendanceData = $attendanceReport->fetchAll();

// Leave analysis
$leaveAnalysis = $pdo->prepare("
    SELECT leave_type, status, COUNT(*) as count
    FROM leave_requests 
    WHERE start_date BETWEEN ? AND ?
    GROUP BY leave_type, status
");
$leaveAnalysis->execute([$startDate, $endDate]);
$leaveData = $leaveAnalysis->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>HR Reports & Analytics</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-chart-bar"></i> HR Reports & Analytics</h1>
        
        <!-- Date Filter -->
        <div class="card">
            <h3>Report Period</h3>
            <form method="GET" class="date-filter">
                <div class="form-group">
                    <label>Start Date</label>
                    <input type="date" name="start_date" value="<?= $startDate ?>">
                </div>
                <div class="form-group">
                    <label>End Date</label>
                    <input type="date" name="end_date" value="<?= $endDate ?>">
                </div>
                <button type="submit" class="btn btn-primary">Generate Report</button>
                <button type="button" class="btn" onclick="exportToExcel()">Export to Excel</button>
            </form>
        </div>

        <!-- Summary Stats -->
        <div class="stats-grid">
            <?php
            $totalEmployees = $pdo->query("SELECT COUNT(*) FROM admin WHERE is_active = true")->fetchColumn();
            $activeLeaves = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn();
            $totalPayroll = $pdo->query("SELECT SUM(net_salary) FROM payroll WHERE status = 'paid' AND payment_date BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
            $avgAttendance = $pdo->query("SELECT AVG(hours_worked) FROM attendance WHERE DATE(check_in) BETWEEN '$startDate' AND '$endDate'")->fetchColumn();
            ?>
            <div class="stat-card">
                <div class="stat-number"><?= $totalEmployees ?></div>
                <div>Total Employees</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $activeLeaves ?></div>
                <div>Pending Leaves</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">$<?= number_format($totalPayroll, 2) ?></div>
                <div>Total Payroll</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= number_format($avgAttendance, 1) ?>h</div>
                <div>Avg Daily Hours</div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="charts-grid">
            <div class="card">
                <h3>Department Distribution</h3>
                <canvas id="departmentChart" width="400" height="200"></canvas>
            </div>
            <div class="card">
                <h3>Leave Types Analysis</h3>
                <canvas id="leaveChart" width="400" height="200"></canvas>
            </div>
        </div>

        <!-- Detailed Reports -->
        <div class="card">
            <h3>Department-wise Analysis</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Department</th>
                        <th>Employees</th>
                        <th>Avg Salary</th>
                        <th>Avg Daily Hours</th>
                        <th>Attendance Rate</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($departments as $dept): 
                        $attendance = array_filter($attendanceData, function($item) use ($dept) {
                            return $item['department'] === $dept['department'];
                        });
                        $attendance = $attendance ? array_values($attendance)[0] : null;
                    ?>
                    <tr>
                        <td><?= htmlspecialchars($dept['department']) ?></td>
                        <td><?= $dept['employee_count'] ?></td>
                        <td>$<?= number_format($dept['avg_salary'], 2) ?></td>
                        <td><?= $attendance ? number_format($attendance['avg_hours'], 1) : '0' ?> hours</td>
                        <td><?= $attendance ? round(($attendance['employees_with_attendance'] / $dept['employee_count']) * 100, 1) : '0' ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Leave Analysis -->
        <div class="card">
            <h3>Leave Analysis</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Leave Type</th>
                        <th>Pending</th>
                        <th>Approved</th>
                        <th>Rejected</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $leaveTypes = ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'other'];
                    foreach ($leaveTypes as $type):
                        $pending = array_filter($leaveData, function($item) use ($type) {
                            return $item['leave_type'] === $type && $item['status'] === 'pending';
                        });
                        $approved = array_filter($leaveData, function($item) use ($type) {
                            return $item['leave_type'] === $type && $item['status'] === 'approved';
                        });
                        $rejected = array_filter($leaveData, function($item) use ($type) {
                            return $item['leave_type'] === $type && $item['status'] === 'rejected';
                        });
                        
                        $pendingCount = $pending ? array_values($pending)[0]['count'] : 0;
                        $approvedCount = $approved ? array_values($approved)[0]['count'] : 0;
                        $rejectedCount = $rejected ? array_values($rejected)[0]['count'] : 0;
                        $total = $pendingCount + $approvedCount + $rejectedCount;
                    ?>
                    <tr>
                        <td><?= ucfirst($type) ?></td>
                        <td><?= $pendingCount ?></td>
                        <td><?= $approvedCount ?></td>
                        <td><?= $rejectedCount ?></td>
                        <td><strong><?= $total ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    // Department Distribution Chart
    const departmentCtx = document.getElementById('departmentChart').getContext('2d');
    const departmentChart = new Chart(departmentCtx, {
        type: 'pie',
        data: {
            labels: <?= json_encode(array_column($departments, 'department')) ?>,
            datasets: [{
                data: <?= json_encode(array_column($departments, 'employee_count')) ?>,
                backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
            }]
        }
    });

    // Leave Analysis Chart
    const leaveCtx = document.getElementById('leaveChart').getContext('2d');
    const leaveChart = new Chart(leaveCtx, {
        type: 'bar',
        data: {
            labels: ['Sick', 'Vacation', 'Personal', 'Maternity', 'Paternity', 'Other'],
            datasets: [{
                label: 'Approved',
                data: [<?php
                    $types = ['sick', 'vacation', 'personal', 'maternity', 'paternity', 'other'];
                    $approvedData = [];
                    foreach ($types as $type) {
                        $approved = array_filter($leaveData, function($item) use ($type) {
                            return $item['leave_type'] === $type && $item['status'] === 'approved';
                        });
                        $approvedData[] = $approved ? array_values($approved)[0]['count'] : 0;
                    }
                    echo implode(',', $approvedData);
                ?>],
                backgroundColor: '#36A2EB'
            }, {
                label: 'Pending',
                data: [<?php
                    $pendingData = [];
                    foreach ($types as $type) {
                        $pending = array_filter($leaveData, function($item) use ($type) {
                            return $item['leave_type'] === $type && $item['status'] === 'pending';
                        });
                        $pendingData[] = $pending ? array_values($pending)[0]['count'] : 0;
                    }
                    echo implode(',', $pendingData);
                ?>],
                backgroundColor: '#FFCE56'
            }]
        }
    });

    function exportToExcel() {
        // Simple table export implementation
        alert('Export functionality would be implemented here. This would typically generate an Excel file with all report data.');
        // In a real implementation, you would use a library like SheetJS or generate a CSV
    }
    </script>

    <style>
    .charts-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 20px; 
        margin: 20px 0; 
    }
    .date-filter { 
        display: flex; 
        gap: 15px; 
        align-items: end; 
    }
    @media (max-width: 768px) {
        .charts-grid { grid-template-columns: 1fr; }
        .date-filter { flex-direction: column; }
    }
    </style>
</body>
</html>