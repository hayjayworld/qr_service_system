<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle leave actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_leave'])) {
        $leaveId = (int)$_POST['leave_id'];
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'approved', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id'], $leaveId]);
        $_SESSION['success'] = "Leave request approved!";
    }
    
    if (isset($_POST['reject_leave'])) {
        $leaveId = (int)$_POST['leave_id'];
        $stmt = $pdo->prepare("UPDATE leave_requests SET status = 'rejected', approved_by = ?, approved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id'], $leaveId]);
        $_SESSION['success'] = "Leave request rejected!";
    }
    
    if (isset($_POST['add_leave'])) {
        $stmt = $pdo->prepare("INSERT INTO leave_requests (admin_id, leave_type, start_date, end_date, reason) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['employee_id'],
            $_POST['leave_type'],
            $_POST['start_date'],
            $_POST['end_date'],
            $_POST['reason']
        ]);
        $_SESSION['success'] = "Leave request submitted!";
    }
    
    header('Location: leave_management.php');
    exit();
}

// Get leave requests
$statusFilter = $_GET['status'] ?? 'all';
$baseQuery = "
    SELECT lr.*, a.first_name, a.last_name, a.employee_id, a.department,
           approver.first_name as approver_first, approver.last_name as approver_last
    FROM leave_requests lr 
    JOIN admin a ON lr.admin_id = a.id 
    LEFT JOIN admin approver ON lr.approved_by = approver.id
";

if ($statusFilter !== 'all') {
    $baseQuery .= " WHERE lr.status = ?";
    $stmt = $pdo->prepare($baseQuery . " ORDER BY lr.created_at DESC");
    $stmt->execute([$statusFilter]);
} else {
    $stmt = $pdo->prepare($baseQuery . " ORDER BY lr.created_at DESC");
    $stmt->execute();
}

$leaveRequests = $stmt->fetchAll();

// Get employees for dropdown
$employees = $pdo->query("SELECT id, first_name, last_name, employee_id FROM admin WHERE is_active = true")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Leave Management - HR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .leave-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin: 20px 0; }
        .stat-card { background: white; padding: 15px; border-radius: 5px; text-align: center; }
        .leave-type-badge { padding: 3px 8px; border-radius: 10px; font-size: 0.8em; }
        .leave-sick { background: #ffeaa7; color: #e17055; }
        .leave-vacation { background: #a29bfe; color: white; }
        .leave-personal { background: #fd79a8; color: white; }
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
        <h1><i class="fas fa-calendar-alt"></i> Leave Management</h1>
        
        <?php displayMessages(); ?>
        
        <div class="leave-stats">
            <?php
            $stats = [
                'pending' => $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'")->fetchColumn(),
                'approved' => $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'approved'")->fetchColumn(),
                'rejected' => $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'rejected'")->fetchColumn(),
                'total' => $pdo->query("SELECT COUNT(*) FROM leave_requests")->fetchColumn()
            ];
            ?>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['pending'] ?></div>
                <div>Pending Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['approved'] ?></div>
                <div>Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['rejected'] ?></div>
                <div>Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $stats['total'] ?></div>
                <div>Total Requests</div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn <?= $statusFilter === 'all' ? 'active' : '' ?>" onclick="window.location='leave_management.php?status=all'">All Leaves</button>
            <button class="tab-btn <?= $statusFilter === 'pending' ? 'active' : '' ?>" onclick="window.location='leave_management.php?status=pending'">Pending</button>
            <button class="tab-btn <?= $statusFilter === 'approved' ? 'active' : '' ?>" onclick="window.location='leave_management.php?status=approved'">Approved</button>
            <button class="tab-btn <?= $statusFilter === 'rejected' ? 'active' : '' ?>" onclick="window.location='leave_management.php?status=rejected'">Rejected</button>
            <button class="tab-btn" onclick="openModal('addLeaveModal')">Add Leave</button>
        </div>

        <div class="card">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Leave Type</th>
                        <th>Dates</th>
                        <th>Duration</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($leaveRequests as $leave): 
                        $duration = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / (60 * 60 * 24) + 1;
                    ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($leave['first_name'] . ' ' . $leave['last_name']) ?></strong><br>
                            <small><?= $leave['employee_id'] ?> • <?= $leave['department'] ?></small>
                        </td>
                        <td>
                            <span class="leave-type-badge leave-<?= $leave['leave_type'] ?>">
                                <?= ucfirst($leave['leave_type']) ?>
                            </span>
                        </td>
                        <td>
                            <?= date('M j, Y', strtotime($leave['start_date'])) ?><br>
                            to<br>
                            <?= date('M j, Y', strtotime($leave['end_date'])) ?>
                        </td>
                        <td><?= $duration ?> day<?= $duration != 1 ? 's' : '' ?></td>
                        <td><?= htmlspecialchars(substr($leave['reason'], 0, 50)) ?><?= strlen($leave['reason']) > 50 ? '...' : '' ?></td>
                        <td>
                            <span class="status-badge status-<?= $leave['status'] ?>">
                                <?= ucfirst($leave['status']) ?>
                            </span>
                            <?php if ($leave['approved_by']): ?>
                                <br><small>By: <?= $leave['approver_first'] . ' ' . $leave['approver_last'] ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($leave['status'] === 'pending'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                    <button type="submit" name="approve_leave" class="btn btn-success btn-sm">Approve</button>
                                </form>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="leave_id" value="<?= $leave['id'] ?>">
                                    <button type="submit" name="reject_leave" class="btn btn-danger btn-sm">Reject</button>
                                </form>
                            <?php endif; ?>
                            <button class="btn btn-info btn-sm" onclick="viewLeaveDetails(<?= $leave['id'] ?>)">View</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Add Leave Modal -->
    <div id="addLeaveModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add Leave Request</h2>
                <span class="close" onclick="closeModal('addLeaveModal')">&times;</span>
            </div>
            <div class="modal-body">
                                <form method="POST">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Employee</label>
                        <select name="employee_id" required>
                            <?php foreach ($employees as $employee): ?>
                                <option value="<?= $employee['id'] ?>">
                                    <?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?> (<?= $employee['employee_id'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Leave Type</label>
                        <select name="leave_type" required>
                            <option value="sick">Sick Leave</option>
                            <option value="vacation">Vacation</option>
                            <option value="personal">Personal</option>
                            <option value="maternity">Maternity</option>
                            <option value="paternity">Paternity</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Start Date</label>
                        <input type="date" name="start_date" required>
                    </div>
                    <div class="form-group">
                        <label>End Date</label>
                        <input type="date" name="end_date" required>
                    </div>
                    <div class="form-group">
                        <label>Reason</label>
                        <textarea name="reason" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal('addLeaveModal')">Cancel</button>
                    <button type="submit" name="add_leave" class="btn btn-primary">Submit Leave Request</button>
                </div>
            </form>
            </div>
        </div>
    </div>

    <script>
    function openModal(modalId) {
        document.getElementById(modalId).style.display = 'block';
    }
    
    function closeModal(modalId) {
        document.getElementById(modalId).style.display = 'none';
    }
    
    function viewLeaveDetails(leaveId) {
        // Implement leave details view
        alert('Viewing leave details for ID: ' + leaveId);
    }
    </script>
</body>
</html>