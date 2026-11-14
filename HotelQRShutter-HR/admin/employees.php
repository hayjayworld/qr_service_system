<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireHR();

// Handle employee actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        // Similar to user creation but with HR fields
        $employeeData = [
            'employee_id' => generateEmployeeID($pdo),
            'username' => $_POST['username'],
            'password' => $_POST['password'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'gender' => $_POST['gender'],
            'date_of_birth' => $_POST['date_of_birth'],
            'department' => $_POST['department'],
            'position' => $_POST['position'],
            'salary' => $_POST['salary'],
            'employment_type' => $_POST['employment_type'],
            'hire_date' => $_POST['hire_date'],
            'role_id' => (int)$_POST['role_id'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'emergency_contact' => $_POST['emergency_contact'],
            'emergency_phone' => $_POST['emergency_phone'],
            'address' => $_POST['address']
        ];
        
        if (!empty($_POST['username']) && !empty($_POST['password'])) {
            $sql = "INSERT INTO admin (employee_id, username, password_hash, first_name, 
            last_name, gender, date_of_birth, department, position, salary, employment_type, 
            hire_date, role_id, email, phone, emergency_contact, emergency_phone, address, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([...array_values($employeeData),$_SESSION['admin_id']]);
            
            $_SESSION['success'] = "Employee added successfully!";
        } else {
            $_SESSION['error'] = "Username and password are required";
        }
        header('Location: employees.php');
        exit();
        
    }

    if (isset($_POST['update_user'])) {
        $employeeData = [
            'employee_id' => $_POST['employee_id'],
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'gender' => $_POST['gender'],
            'date_of_birth' => $_POST['date_of_birth'],
            'department' => $_POST['department'],
            'position' => $_POST['position'],
            'salary' => $_POST['salary'],
            'employment_type' => $_POST['employment_type'],
            'hire_date' => $_POST['hire_date'],
            'role_id' => (int)$_POST['role_id'],
            'email' => $_POST['email'],
            'phone' => $_POST['phone'],
            'emergency_contact' => $_POST['emergency_contact'],
            'emergency_phone' => $_POST['emergency_phone'],
            'address' => $_POST['address'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0
        ];
        $userId = (int)$_POST['user_id'];
        
        $sql = "UPDATE admin SET employee_id = ?, first_name=?, last_name=?, gender=?, date_of_birth=?,
        department=?, position=?, salary=?, employment_type=?, hire_date=?,
         role_id=?, email=?, phone=?, emergency_contact=?, emergency_phone=?, address=?, is_active=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([...array_values($employeeData),$userId]);
        
        $_SESSION['success'] = "User updated successfully!";
        header('Location: employees.php?tab=employees');
        exit();
    }
}

// Get user details for view/edit
$userDetails = null;
$action = '';
$userId = 0;

if (isset($_GET['view']) && !empty($_GET['view'])) {
    $userId = (int)$_GET['view'];
    $action = 'view';
} elseif (isset($_GET['edit']) && !empty($_GET['edit'])) {
    $userId = (int)$_GET['edit'];
    $action = 'edit';
}

if ($userId > 0) {
    $stmt = $pdo->prepare("SELECT a.*, r.role_name FROM admin a LEFT JOIN roles r ON a.role_id = r.id WHERE a.id = ?");
    $stmt->execute([$userId]);
    $userDetails = $stmt->fetch();
    
    // If user not found, redirect to users list
    if (!$userDetails) {
        header('Location: users.php?tab=users');
        exit();
    }
}

// Get all employees with HR details
$employees = $pdo->query("
    SELECT a.*, r.role_name 
    FROM admin a 
    LEFT JOIN roles r ON a.role_id = r.id 
    ORDER BY a.department, a.first_name
")->fetchAll();

$roles = $pdo->query("SELECT * FROM roles ORDER BY role_name")->fetchAll();
$tab = $_GET['tab'] ?? 'employees';
$departments = $pdo->query("SELECT DISTINCT department FROM admin WHERE department IS NOT NULL")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Employee Management - HR System</title>
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
        <h1><i class="fas fa-users-cog"></i> Employee Management</h1>
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
        <!-- Edit User Modal -->
<?php if ($action === 'edit' && $userDetails): ?>
<div class="modal" style="display: block;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Edit User</h2>
            <a href="employees.php?tab=employees" class="close">&times;</a>
        </div>
        <div class="modal-body">
            <form method="POST" id="editUserForm">
                <input type="hidden" name="user_id" value="<?= $userDetails['id'] ?>">
                <div class="form-group">
                        <label>Employee ID</label>
                        <input type="text" name="employee_id" value="<?=  htmlspecialchars($userDetails['employee_id']) ?>" readonly>
                    </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?= htmlspecialchars($userDetails['first_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?= htmlspecialchars($userDetails['last_name']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male" <?= $userDetails['gender'] === 'male' ? 'selected' : '' ?>>Male</option>
                            <option value="female" <?= $userDetails['gender'] === 'female' ? 'selected' : '' ?>>Female</option>
                            <option value="other" <?= $userDetails['gender'] === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($userDetails['phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Email Address *</label>
                        <input type="email" name="email" value="<?= htmlspecialchars($userDetails['email']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth" value="<?= htmlspecialchars($userDetails['date_of_birth'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Role *</label>
                        <select name="role_id" required>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= $role['id'] ?>" <?= $userDetails['role_id'] == $role['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Department</label>
                        <input type="text" name="department" value="<?= htmlspecialchars($userDetails['department']) ?>"required>
                    </div>
                    <div class="form-group">
                        <label>Position</label>
                        <input type="text" name="position" value="<?= htmlspecialchars($userDetails['position']) ?>"required>
                    </div>
                    <div class="form-group">
                        <label>Salary</label>
                        <input type="number" name="salary" step="0.01" value="<?= htmlspecialchars($userDetails['salary']) ?>"required>
                    </div>
                    <div class="form-group">
                        <label>Employment Type</label>
                        <select name="employment_type" required>
                            <option value="full_time" <?= $userDetails['employment_type'] === 'full_time' ? 'selected' : '' ?>>Full Time</option>
                            <option value="part_time" <?= $userDetails['employment_type'] === 'part_time' ? 'selected' : '' ?>>Part Time</option>
                            <option value="contract" <?= $userDetails['employment_type'] === 'contract' ? 'selected' : '' ?>>Contract</option>
                            <option value="temporary" <?= $userDetails['employment_type'] === 'temporary' ? 'selected' : '' ?>>Temporary</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Hire Date</label>
                        <input type="date" name="hire_date" value="<?= htmlspecialchars($userDetails['hire_date']) ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" value="<?= htmlspecialchars($userDetails['emergency_contact'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Emergency Phone</label>
                        <input type="tel" name="emergency_phone" value="<?= htmlspecialchars($userDetails['emergency_phone'] ?? '') ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Status</label>
                        <div>
                            <input type="checkbox" name="is_active" value="1" <?= $userDetails['is_active'] ? 'checked' : '' ?>>
                            Active
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Address</label>
                    <textarea name="address" rows="3"><?= htmlspecialchars($userDetails['address'] ?? '') ?></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <div class="action-btns">
                <a href="employees.php?tab=employees" class="btn btn-secondary">Cancel</a>
                <button type="submit" form="editUserForm" name="update_user" class="btn btn-primary">Update User</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('employees')">All Employees</button>
            <button class="tab-btn" onclick="openTab('add-employee')">Add Employee</button>
        </div>
        
        <div id="employees-tab" class="tab-content <?= $tab === 'employees' ? 'active' : '' ?>">
            <!-- Employee list with HR details -->
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Employee ID</th>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Employment Type</th>
                        <th>Salary</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($employees as $employee): ?>
                    <tr>
                        <td><?= $employee['employee_id'] ?></td>
                        <td><?= htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']) ?></td>
                        <td><?= htmlspecialchars($employee['department'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($employee['position'] ?? 'N/A') ?></td>
                        <td><?= ucfirst(str_replace('_', ' ', $employee['employment_type'])) ?></td>
                        <td>$<?= number_format($employee['salary'], 2) ?></td>
                        <td>
                            <a href="employee_profile.php?id=<?= $employee['id'] ?>" class="btn btn-sm">Profile</a>
                            <a href="?tab=employees&edit=<?= $employee['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <div id="add-employee-tab" class="tab-content <?= $tab === 'add-employee' ? 'active' : '' ?>">
            <!-- Add employee form with HR fields -->
            <form method="POST" class="card">
                <h3>Add New Employee</h3>
                <div class="form-grid">

                            <div class="form-group">
                                    <label>Employee ID</label>
                                    <input type="text" value="<?= generateEmployeeID($pdo) ?>" readonly>
                                </div>
                            <div class="form-group">
                                <label>Username *</label>
                                <input type="text" name="username" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Password *</label>
                                <input type="password" name="password" required>
                            </div>
                            
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Gender</label>
                                <select name="gender">
                                    <option value="">Select Gender</option>
                                    <option value="male">Male</option>
                                    <option value="female">Female</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone">
                            </div>
                            
                            <div class="form-group">
                                <label>Email Address *</label>
                                <input type="email" name="email" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Date of Birth</label>
                                <input type="date" name="date_of_birth">
                            </div>
                            
                            <div class="form-group">
                                <label>Role *</label>
                                <select name="role_id" required>
                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= $role['id'] ?>"><?= htmlspecialchars($role['role_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Department</label>
                                <input type="text" name="department" required>
                            </div>
                            <div class="form-group">
                                <label>Position</label>
                                <input type="text" name="position" required>
                            </div>
                            <div class="form-group">
                                <label>Salary</label>
                                <input type="number" name="salary" step="0.01" required>
                            </div>
                            <div class="form-group">
                                <label>Employment Type</label>
                                <select name="employment_type" required>
                                    <option value="full_time">Full Time</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="contract">Contract</option>
                                    <option value="temporary">Temporary</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Hire Date</label>
                                <input type="date" name="hire_date" required>
                            </div>
                            
                            <div class="form-group">
                                <label>Emergency Contact</label>
                                <input type="text" name="emergency_contact">
                            </div>
                            
                            <div class="form-group">
                                <label>Emergency Phone</label>
                                <input type="tel" name="emergency_phone">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="3"></textarea>
                        </div>
                <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
            </form>
        </div>
    </div>
    <script>
function openTab(tabName) {
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab
    document.getElementById(tabName + '-tab').classList.add('active');
    
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.replaceState({}, '', url);

    event.currentTarget.classList.add('active');
}

// Close modal when clicking outside or pressing Escape
function setupModalHandlers() {
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal')) {
            window.location.href = 'employees.php?tab=employees';
        }
    }
    
    // Close modal with Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            window.location.href = 'employees.php?tab=employees';
        }
    });
    
    // Prevent modal content clicks from closing modal
    document.querySelectorAll('.modal-content').forEach(content => {
        content.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    });
}

// Initialize modal handlers when page loads
document.addEventListener('DOMContentLoaded', function() {
    setupModalHandlers();
    
    // Auto-scroll to top when modal opens
    const modals = document.querySelectorAll('.modal[style*="display: block"]');
    if (modals.length > 0) {
        modals[0].scrollTop = 0;
    }
});
</script>
</body>
</html>