<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

$employeeId = $_GET['id'] ?? 0;
if (!$employeeId) {
    header('Location: employees.php');
    exit();
}

// Get employee details
$stmt = $pdo->prepare("
    SELECT a.*, r.role_name 
    FROM admin a 
    LEFT JOIN roles r ON a.role_id = r.id 
    WHERE a.id = ?
");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch();

if (!$employee) {
    header('Location: employees.php');
    exit();
}

// Get employee documents
$documents = $pdo->prepare("SELECT * FROM employee_documents WHERE admin_id = ? ORDER BY created_at DESC");
$documents->execute([$employeeId]);
$documents = $documents->fetchAll();

// Get attendance summary
$attendanceSummary = $pdo->prepare("
    SELECT 
        COUNT(*) as total_days,
        SUM(hours_worked) as total_hours,
        AVG(hours_worked) as avg_hours
    FROM attendance 
    WHERE admin_id = ? AND MONTH(check_in) = MONTH(CURDATE())
");
$attendanceSummary->execute([$employeeId]);
$attendance = $attendanceSummary->fetch();

// Handle document upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['document'])) {
    $uploadDir = '../uploads/documents/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = time() . '_' . basename($_FILES['document']['name']);
    $targetPath = $uploadDir . $fileName;
    
    if (move_uploaded_file($_FILES['document']['tmp_name'], $targetPath)) {
        $stmt = $pdo->prepare("
            INSERT INTO employee_documents (admin_id, document_type, document_name, file_path, uploaded_by) 
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $employeeId,
            $_POST['document_type'],
            $_POST['document_name'],
            $targetPath,
            $_SESSION['admin_id']
        ]);
        $_SESSION['success'] = "Document uploaded successfully!";
    } else {
        $_SESSION['error'] = "Failed to upload document.";
    }
    
    header('Location: employee_profile.php?id=' . $employeeId);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?> - Employee Profile</title>
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
        .profile-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 10px; margin-bottom: 20px; }
        .profile-avatar { width: 100px; height: 100px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5em; }
        .profile-stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .document-card { background: white; padding: 15px; border-radius: 5px; margin-bottom: 10px; border-left: 4px solid #3498db; }
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
        <!-- Profile Header -->
        <div class="profile-header">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="profile-avatar">
                    <?= strtoupper(substr($employee['first_name'], 0, 1) . substr($employee['last_name'], 0, 1)) ?>
                </div>
                <div>
                    <h1 style="margin: 0; color: white;"><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></h1>
                    <p style="margin: 5px 0; opacity: 0.9;"><?= $employee['employee_id'] ?> • <?= $employee['position'] ?></p>
                    <p style="margin: 0; opacity: 0.8;"><?= $employee['department'] ?> Department</p>
                </div>
            </div>
        </div>

        <div class="tabs">
            <button class="tab-btn active" onclick="showTab('overview')">Overview</button>
            <button class="tab-btn" onclick="showTab('documents')">Documents</button>
            <button class="tab-btn" onclick="showTab('attendance')">Attendance</button>
            <button class="tab-btn" onclick="showTab('leave')">Leave History</button>
        </div>

        <!-- Overview Tab -->
        <div id="overview" class="tab-content active">
            <div class="profile-stats">
                <div class="stat-card">
                    <div class="stat-number">₦<?= number_format($employee['salary'], 2) ?></div>
                    <div>Monthly Salary</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $attendance['total_days'] ?? 0 ?></div>
                    <div>Days Worked (This Month)</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= number_format($attendance['total_hours'] ?? 0, 1) ?></div>
                    <div>Total Hours</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?= $employee['employment_type'] ? ucfirst(str_replace('_', ' ', $employee['employment_type'])) : 'N/A' ?></div>
                    <div>Employment Type</div>
                </div>
            </div>

            <div class="card">
                <h3>Personal Information</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label>Employee ID</label>
                        <p><?= $employee['employee_id'] ?></p>
                    </div>
                    <div class="form-group">
                        <label>Firstname</label>
                        <p><?= htmlspecialchars($employee['first_name']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Lastname</label>
                        <p><?= htmlspecialchars($employee['last_name']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Gender</label>
                        <p><?= htmlspecialchars($employee['gender']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <p><?= htmlspecialchars($employee['email']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <p><?= htmlspecialchars($employee['phone']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <p><?= $employee['date_of_birth'] ? date('M j, Y', strtotime($employee['date_of_birth'])) : 'N/A' ?></p>
                    </div>
                    <div class="form-group">
                        <label>Hire Date</label>
                        <p><?= $employee['hire_date'] ? date('M j, Y', strtotime($employee['hire_date'])) : 'N/A' ?></p>
                    </div>
                    <div class="form-group">
                        <label>Address</label>
                        <p><?= htmlspecialchars($employee['address']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Emergency contact</label>
                        <p><?= htmlspecialchars($employee['emergency_contact']) ?></p>
                    </div>
                    <div class="form-group">
                        <label>Emergency Phone</label>
                        <p><?= htmlspecialchars($employee['emergency_phone']) ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Documents Tab -->
        <div id="documents" class="tab-content">
            <div class="card">
                <h3>Employee Documents</h3>
                
                <!-- Upload Document Form -->
                <form method="POST" enctype="multipart/form-data" style="margin-bottom: 20px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Document Type</label>
                            <select name="document_type" required>
                                <option value="contract">Employment Contract</option>
                                <option value="id_card">ID Card</option>
                                <option value="certificate">Certificate</option>
                                <option value="cv">CV/Resume</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Document Name</label>
                            <input type="text" name="document_name" required>
                        </div>
                        <div class="form-group">
                            <label>Select File</label>
                            <input type="file" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">Upload Document</button>
                </form>

                <!-- Documents List -->
                <h4>Uploaded Documents</h4>
                <?php if ($documents): ?>
                    <?php foreach ($documents as $doc): ?>
                    <div class="document-card">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong><?= htmlspecialchars($doc['document_name']) ?></strong>
                                <br><small>Type: <?= ucfirst($doc['document_type']) ?> • Uploaded: <?= date('M j, Y', strtotime($doc['created_at'])) ?></small>
                            </div>
                            <div>
                                <a href="<?= $doc['file_path'] ?>" target="_blank" class="btn btn-sm">View</a>
                                <a href="<?= $doc['file_path'] ?>" download class="btn btn-sm">Download</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>No documents uploaded yet.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- Attendance Tab -->
        <div id="attendance" class="tab-content">
            <div class="card">
                <h3>Attendance History</h3>
                <?php
                $attendanceHistory = $pdo->prepare("
                    SELECT * FROM attendance 
                    WHERE admin_id = ? 
                    ORDER BY check_in DESC 
                    LIMIT 30
                ");
                $attendanceHistory->execute([$employeeId]);
                $history = $attendanceHistory->fetchAll();
                ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Hours Worked</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history as $record): ?>
                        <tr>
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

        <!-- Leave History Tab -->
        <div id="leave" class="tab-content">
            <div class="card">
                <h3>Leave History</h3>
                <?php
                $leaveHistory = $pdo->prepare("
                    SELECT * FROM leave_requests 
                    WHERE admin_id = ? 
                    ORDER BY created_at DESC
                ");
                $leaveHistory->execute([$employeeId]);
                $leaves = $leaveHistory->fetchAll();
                ?>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Leave Type</th>
                            <th>Dates</th>
                            <th>Duration</th>
                            <th>Status</th>
                            <th>Reason</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($leaves as $leave): 
                            $duration = (strtotime($leave['end_date']) - strtotime($leave['start_date'])) / (60 * 60 * 24) + 1;
                        ?>
                        <tr>
                            <td><?= ucfirst($leave['leave_type']) ?></td>
                            <td>
                                <?= date('M j', strtotime($leave['start_date'])) ?> -<br>
                                <?= date('M j', strtotime($leave['end_date'])) ?>
                            </td>
                            <td><?= $duration ?> days</td>
                            <td>
                                <span class="status-badge status-<?= $leave['status'] ?>">
                                    <?= ucfirst($leave['status']) ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars(substr($leave['reason'], 0, 30)) ?><?= strlen($leave['reason']) > 30 ? '...' : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
    function showTab(tabName) {
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Show selected tab
        document.getElementById(tabName).classList.add('active');
        
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.currentTarget.classList.add('active');
    }
    </script>
</body>
</html>