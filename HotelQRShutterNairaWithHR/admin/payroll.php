<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle payroll actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['process_payroll'])) {
        $employeeId = (int)$_POST['employee_id'];
        $payPeriodStart = $_POST['pay_period_start'];
        $payPeriodEnd = $_POST['pay_period_end'];
        $basicSalary = (float)$_POST['basic_salary'];
        $allowances = (float)$_POST['allowances'];
        $deductions = (float)$_POST['deductions'];
        $netSalary = $basicSalary + $allowances - $deductions;
        
        $stmt = $pdo->prepare("
            INSERT INTO payroll (admin_id, pay_period_start, pay_period_end, basic_salary, allowances, deductions, net_salary, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$employeeId, $payPeriodStart, $payPeriodEnd, $basicSalary, $allowances, $deductions, $netSalary, $_SESSION['admin_id']]);
        
        $_SESSION['success'] = "Payroll processed successfully!";
    }
    
    if (isset($_POST['mark_paid'])) {
        $payrollId = (int)$_POST['payroll_id'];
        $stmt = $pdo->prepare("UPDATE payroll SET status = 'paid', payment_date = CURDATE() WHERE id = ?");
        $stmt->execute([$payrollId]);
        $_SESSION['success'] = "Payroll marked as paid!";
    }
    
    header('Location: payroll.php');
    exit();
}

// Get payroll records
$payrolls = $pdo->query("
    SELECT p.*, a.first_name, a.last_name, a.employee_id, a.department,
           creator.first_name as creator_first, creator.last_name as creator_last
    FROM payroll p
    JOIN admin a ON p.admin_id = a.id
    JOIN admin creator ON p.created_by = creator.id
    ORDER BY p.pay_period_end DESC
")->fetchAll();

$employees = $pdo->query("SELECT id, first_name, last_name, employee_id, salary FROM admin WHERE is_active = true")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payroll Management - HR System</title>
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
        <h1><i class="fas fa-money-bill-wave"></i> Payroll Management</h1>
        
        <?php displayMessages(); ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="showSection('payroll-list')">Payroll Records</button>
            <button class="tab-btn" onclick="showSection('process-payroll')">Process Payroll</button>
            <button class="tab-btn" onclick="showSection('payroll-reports')">Reports</button>
        </div>

        <!-- Payroll Records Section -->
        <div id="payroll-list" class="tab-content active">
            <div class="card">
                <h3>Payroll History</h3>
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Pay Period</th>
                            <th>Basic Salary</th>
                            <th>Allowances</th>
                            <th>Deductions</th>
                            <th>Net Salary</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payrolls as $payroll): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($payroll['first_name'] . ' ' . $payroll['last_name']) ?></strong><br>
                                <small><?= $payroll['employee_id'] ?></small>
                            </td>
                            <td>
                                <?= date('M j, Y', strtotime($payroll['pay_period_start'])) ?> -<br>
                                <?= date('M j, Y', strtotime($payroll['pay_period_end'])) ?>
                            </td>
                            <td>$<?= number_format($payroll['basic_salary'], 2) ?></td>
                            <td>$<?= number_format($payroll['allowances'], 2) ?></td>
                            <td>$<?= number_format($payroll['deductions'], 2) ?></td>
                            <td><strong>$<?= number_format($payroll['net_salary'], 2) ?></strong></td>
                            <td>
                                <span class="status-badge status-<?= $payroll['status'] ?>">
                                    <?= ucfirst($payroll['status']) ?>
                                </span>
                                <?php if ($payroll['payment_date']): ?>
                                    <br><small>Paid: <?= date('M j, Y', strtotime($payroll['payment_date'])) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($payroll['status'] === 'processed'): ?>
                                    <form method="POST" style="display: inline;">
                                        <input type="hidden" name="payroll_id" value="<?= $payroll['id'] ?>">
                                        <button type="submit" name="mark_paid" class="btn btn-success btn-sm">Mark Paid</button>
                                    </form>
                                <?php endif; ?>
                                <button class="btn btn-info btn-sm" onclick="viewPayslip(<?= $payroll['id'] ?>)">Payslip</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Process Payroll Section -->
        <div id="process-payroll" class="tab-content">
            <div class="card">
                <h3>Process New Payroll</h3>
                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Employee</label>
                            <select name="employee_id" id="employeeSelect" required onchange="loadEmployeeSalary()">
                                <option value="">Select Employee</option>
                                <?php foreach ($employees as $employee): ?>
                                    <option value="<?= $employee['id'] ?>" data-salary="<?= $employee['salary'] ?>">
                                        <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?> (<?= $employee['employee_id'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Pay Period Start</label>
                            <input type="date" name="pay_period_start" required>
                        </div>
                        <div class="form-group">
                            <label>Pay Period End</label>
                            <input type="date" name="pay_period_end" required>
                        </div>
                        <div class="form-group">
                            <label>Basic Salary</label>
                            <input type="number" name="basic_salary" id="basicSalary" step="0.01" required>
                        </div>
                        <div class="form-group">
                            <label>Allowances</label>
                            <input type="number" name="allowances" id="allowances" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Deductions</label>
                            <input type="number" name="deductions" id="deductions" step="0.01" value="0">
                        </div>
                        <div class="form-group">
                            <label>Net Salary</label>
                            <input type="number" id="netSalary" step="0.01" readonly>
                        </div>
                    </div>
                    <button type="submit" name="process_payroll" class="btn btn-primary">Process Payroll</button>
                </form>
            </div>
        </div>

        <!-- Reports Section -->
        <div id="payroll-reports" class="tab-content">
            <div class="card">
                <h3>Payroll Reports</h3>
                <div class="report-actions">
                    <button class="btn" onclick="generateMonthlyReport()">Monthly Report</button>
                    <button class="btn" onclick="generateEmployeeReport()">Employee Report</button>
                    <button class="btn" onclick="generateTaxReport()">Tax Report</button>
                </div>
                <div id="reportResults"></div>
            </div>
        </div>
    </div>

    <script>
    function showSection(sectionId) {
        // Hide all sections
        document.querySelectorAll('.tab-content').forEach(section => {
            section.classList.remove('active');
        });
        
        // Show selected section
        document.getElementById(sectionId).classList.add('active');
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
    }
    
    function loadEmployeeSalary() {
        const select = document.getElementById('employeeSelect');
        const selectedOption = select.options[select.selectedIndex];
        const salary = selectedOption.getAttribute('data-salary');
        
        if (salary) {
            document.getElementById('basicSalary').value = salary;
            calculateNetSalary();
        }
    }
    
    function calculateNetSalary() {
        const basicSalary = parseFloat(document.getElementById('basicSalary').value) || 0;
        const allowances = parseFloat(document.getElementById('allowances').value) || 0;
        const deductions = parseFloat(document.getElementById('deductions').value) || 0;
        const netSalary = basicSalary + allowances - deductions;
        
        document.getElementById('netSalary').value = netSalary.toFixed(2);
    }
    
    // Add event listeners for real-time calculation
    document.getElementById('basicSalary').addEventListener('input', calculateNetSalary);
    document.getElementById('allowances').addEventListener('input', calculateNetSalary);
    document.getElementById('deductions').addEventListener('input', calculateNetSalary);
    
    function viewPayslip(payrollId) {
        window.open('payslip.php?id=' + payrollId, '_blank');
    }
    
    function generateMonthlyReport() {
        alert('Generating monthly payroll report...');
        // Implement report generation
    }
    </script>
</body>
</html>