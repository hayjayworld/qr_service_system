<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['create_discount'])) {
        $code = trim($_POST['code']);
        $description = trim($_POST['description']);
        $discountType = $_POST['discount_type'];
        $discountValue = (float)$_POST['discount_value'];
        $minStay = (int)$_POST['min_stay'];
        $validFrom = $_POST['valid_from'] ?: null;
        $validUntil = $_POST['valid_until'] ?: null;
        $maxUses = $_POST['max_uses'] ? (int)$_POST['max_uses'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        try {
            $sql = "INSERT INTO discounts (code, description, discount_type, discount_value, min_stay, valid_from, valid_until, max_uses, is_active, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$code, $description, $discountType, $discountValue, $minStay, $validFrom, $validUntil, $maxUses, $isActive, $_SESSION['admin_id']]);
            
            $_SESSION['success'] = "Discount code created successfully!";
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error creating discount: " . ($e->getCode() == 23000 ? "Discount code already exists" : $e->getMessage());
        }
        
        header('Location: discounts.php?tab=discounts');
        exit();
    }

    if (isset($_POST['update_discount'])) {
        $discountId = (int)$_POST['discount_id'];
        $description = trim($_POST['description']);
        $discountType = $_POST['discount_type'];
        $discountValue = (float)$_POST['discount_value'];
        $minStay = (int)$_POST['min_stay'];
        $validFrom = $_POST['valid_from'] ?: null;
        $validUntil = $_POST['valid_until'] ?: null;
        $maxUses = $_POST['max_uses'] ? (int)$_POST['max_uses'] : null;
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $sql = "UPDATE discounts SET description=?, discount_type=?, discount_value=?, min_stay=?, valid_from=?, valid_until=?, max_uses=?, is_active=? WHERE id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$description, $discountType, $discountValue, $minStay, $validFrom, $validUntil, $maxUses, $isActive, $discountId]);
        
        $_SESSION['success'] = "Discount updated successfully!";
        header('Location: discounts.php?tab=discounts');
        exit();
    }

    if (isset($_POST['delete_discount'])) {
        $discountId = (int)$_POST['discount_id'];
        
        $sql = "DELETE FROM discounts WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$discountId]);
        
        $_SESSION['success'] = "Discount deleted successfully!";
        header('Location: discounts.php?tab=discounts');
        exit();
    }
}

// Get all discounts
$discounts = $pdo->query("
    SELECT d.*, a.username as created_by_name 
    FROM discounts d 
    LEFT JOIN admin a ON d.created_by = a.id 
    ORDER BY d.created_at DESC
")->fetchAll();

// Get current tab from URL or default to 'discounts'
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'discounts';
$action = isset($_GET['action']) ? $_GET['action'] : '';
$discountId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
$discountDetails = null;

if ($discountId > 0) {
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE id = ?");
    $stmt->execute([$discountId]);
    $discountDetails = $stmt->fetch();
    
    // If editing, show create tab
    if ($discountDetails) {
        $tab = 'create';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Discount Management - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .tabs { display: flex; margin-bottom: 20px; border-bottom: 1px solid #ddd; }
        .tab-btn { 
            padding: 12px 24px; 
            border: none; 
            background: none; 
            cursor: pointer; 
            border-bottom: 3px solid transparent;
            font-size: 14px;
            color: #6c757d;
            transition: all 0.3s;
        }
        .tab-btn:hover {
            color: #495057;
            background-color: #f8f9fa;
        }
        .tab-btn.active { 
            border-bottom-color: #3498db; 
            font-weight: bold;
            color: #3498db;
        }
        .tab-content { 
            display: none;
            animation: fadeIn 0.3s ease-in;
        }
        .tab-content.active { 
            display: block; 
        }
        .form-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px; }
        
        .discount-card { background: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); border-left: 4px solid #3498db; }
        .discount-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
        .discount-code { font-size: 1.3em; font-weight: bold; color: #2c3e50; }
        .discount-value { font-size: 1.1em; font-weight: bold; color: #27ae60; background: #d4edda; padding: 4px 8px; border-radius: 4px; }
        .discount-details { color: #6c757d; font-size: 0.9em; line-height: 1.6; }
        .discount-details div { margin-bottom: 8px; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 0.8em; font-weight: bold; }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        .status-expired { background: #fff3cd; color: #856404; }
        .usage-bar { background: #e9ecef; height: 8px; border-radius: 4px; margin: 8px 0; overflow: hidden; }
        .usage-progress { background: #28a745; height: 100%; border-radius: 4px; transition: width 0.3s; }
        
        .text-success { color: #28a745; }
        .text-danger { color: #dc3545; }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .action-btns { display: flex; gap: 10px; margin-top: 15px; }
        .btn-sm { padding: 6px 12px; font-size: 0.85em; }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1><i class="fas fa-tag"></i> Discount Management</h1>
        
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
        
        <div class="tabs">
            <button class="tab-btn <?= $tab === 'discounts' ? 'active' : '' ?>" onclick="openTab('discounts')">
                <i class="fas fa-list"></i> All Discounts
            </button>
            <button class="tab-btn <?= $tab === 'create' ? 'active' : '' ?>" onclick="openTab('create')">
                <i class="fas fa-plus"></i> <?= $discountDetails ? 'Edit Discount' : 'Create Discount' ?>
            </button>
        </div>
        
        <!-- Discounts List Tab -->
        <div id="discounts-tab" class="tab-content <?= $tab === 'discounts' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2>All Discount Codes</h2>
                </div>
                <div class="card-body">
                    <?php if (empty($discounts)): ?>
                        <div class="empty-state">
                            <i class="fas fa-tag" style="font-size: 3em; color: #6c757d; margin-bottom: 15px;"></i>
                            <h3>No discount codes found</h3>
                            <p>Click "Create Discount" to add your first discount code.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($discounts as $discount): 
                            $isExpired = $discount['valid_until'] && strtotime($discount['valid_until']) < time();
                            $usagePercent = $discount['max_uses'] ? ($discount['current_uses'] / $discount['max_uses']) * 100 : 0;
                        ?>
                        <div class="discount-card">
                            <div class="discount-header">
                                <div>
                                    <span class="discount-code"><?= htmlspecialchars($discount['code']) ?></span>
                                    <span class="discount-value">
                                        <?= $discount['discount_type'] === 'percentage' ? 
                                            $discount['discount_value'] . '% OFF' : 
                                            '$' . $discount['discount_value'] . ' OFF' 
                                        ?>
                                    </span>
                                </div>
                                <span class="status-badge <?= !$discount['is_active'] ? 'status-inactive' : ($isExpired ? 'status-expired' : 'status-active') ?>">
                                    <?= !$discount['is_active'] ? 'Inactive' : ($isExpired ? 'Expired' : 'Active') ?>
                                </span>
                            </div>
                            
                            <div class="discount-details">
                                <?php if ($discount['description']): ?>
                                <p><strong>Description:</strong> <?= htmlspecialchars($discount['description']) ?></p>
                                <?php endif; ?>
                                
                                <div><strong>Min Stay:</strong> <?= $discount['min_stay'] ?> night<?= $discount['min_stay'] != 1 ? 's' : '' ?></div>
                                
                                <?php if ($discount['valid_from'] || $discount['valid_until']): ?>
                                <div><strong>Validity:</strong> 
                                    <?= $discount['valid_from'] ? date('M j, Y', strtotime($discount['valid_from'])) : 'No start date' ?> 
                                    - 
                                    <?= $discount['valid_until'] ? date('M j, Y', strtotime($discount['valid_until'])) : 'No end date' ?>
                                </div>
                                <?php endif; ?>
                                
                                <div><strong>Usage:</strong> 
                                    <?= $discount['current_uses'] ?> 
                                    <?= $discount['max_uses'] ? 'of ' . $discount['max_uses'] : '(unlimited)' ?>
                                </div>
                                
                                <?php if ($discount['max_uses']): ?>
                                <div class="usage-bar">
                                    <div class="usage-progress" style="width: <?= min($usagePercent, 100) ?>%"></div>
                                </div>
                                <?php endif; ?>
                                
                                <div><strong>Created by:</strong> <?= htmlspecialchars($discount['created_by_name'] ?? 'System') ?></div>
                                <div><strong>Created:</strong> <?= date('M j, Y', strtotime($discount['created_at'])) ?></div>
                            </div>
                            
                            <div class="action-btns">
                                <a href="discounts.php?edit=<?= $discount['id'] ?>" class="btn btn-warning btn-sm">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="discount_id" value="<?= $discount['id'] ?>">
                                    <button type="submit" name="delete_discount" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to delete this discount code?')">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Create/Edit Discount Tab -->
        <div id="create-tab" class="tab-content <?= $tab === 'create' ? 'active' : '' ?>">
            <div class="card">
                <div class="card-header">
                    <h2><?= $discountDetails ? 'Edit Discount' : 'Create New Discount' ?></h2>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($discountDetails): ?>
                            <input type="hidden" name="discount_id" value="<?= $discountDetails['id'] ?>">
                        <?php endif; ?>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Discount Code *</label>
                                <input type="text" name="code" value="<?= htmlspecialchars($discountDetails['code'] ?? '') ?>" 
                                       <?= $discountDetails ? 'readonly' : 'required' ?> 
                                       pattern="[A-Z0-9_-]{3,20}" 
                                       title="3-20 characters, uppercase letters, numbers, hyphens, or underscores">
                                <small>Unique code (e.g., SUMMER25, WELCOME10)</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Description</label>
                                <textarea name="description" rows="2" placeholder="Enter discount description"><?= htmlspecialchars($discountDetails['description'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label>Discount Type *</label>
                                <select name="discount_type" required>
                                    <option value="percentage" <?= ($discountDetails['discount_type'] ?? 'percentage') === 'percentage' ? 'selected' : '' ?>>Percentage (%)</option>
                                    <option value="fixed_amount" <?= ($discountDetails['discount_type'] ?? '') === 'fixed_amount' ? 'selected' : '' ?>>Fixed Amount ($)</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label>Discount Value *</label>
                                <input type="number" name="discount_value" step="0.01" min="0.01" 
                                       value="<?= htmlspecialchars($discountDetails['discount_value'] ?? '10') ?>" required
                                       placeholder="<?= ($discountDetails['discount_type'] ?? 'percentage') === 'percentage' ? '15' : '50' ?>">
                                <small>Percentage (e.g., 15) or Fixed amount (e.g., 50)</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Minimum Stay (nights)</label>
                                <input type="number" name="min_stay" min="1" value="<?= htmlspecialchars($discountDetails['min_stay'] ?? '1') ?>">
                                <small>Minimum number of nights required</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Valid From</label>
                                <input type="date" name="valid_from" value="<?= htmlspecialchars($discountDetails['valid_from'] ?? '') ?>">
                                <small>Leave empty for no start date</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Valid Until</label>
                                <input type="date" name="valid_until" value="<?= htmlspecialchars($discountDetails['valid_until'] ?? '') ?>">
                                <small>Leave empty for no expiration</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Maximum Uses</label>
                                <input type="number" name="max_uses" min="1" placeholder="Unlimited if empty" 
                                       value="<?= htmlspecialchars($discountDetails['max_uses'] ?? '') ?>">
                                <small>Maximum number of times this discount can be used</small>
                            </div>
                            
                            <div class="form-group">
                                <label>Status</label>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <input type="checkbox" name="is_active" value="1" id="is_active"
                                           <?= ($discountDetails['is_active'] ?? 1) ? 'checked' : '' ?>>
                                    <label for="is_active" style="margin: 0;">Active</label>
                                </div>
                                <small>Inactive discounts cannot be used</small>
                            </div>
                        </div>
                        
                        <div class="action-btns">
                            <button type="submit" name="<?= $discountDetails ? 'update_discount' : 'create_discount' ?>" class="btn btn-primary">
                                <i class="fas fa-save"></i> <?= $discountDetails ? 'Update' : 'Create' ?> Discount
                            </button>
                            
                            <a href="discounts.php?tab=discounts" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
    function openTab(tabName) {
        console.log('Opening tab:', tabName);
        
        // Hide all tabs
        document.querySelectorAll('.tab-content').forEach(tab => {
            tab.classList.remove('active');
        });
        
        // Remove active class from all tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
        });
        
        // Show selected tab
        const selectedTab = document.getElementById(tabName + '-tab');
        if (selectedTab) {
            selectedTab.classList.add('active');
        }
        
        // Add active class to the clicked button
        const clickedButton = event?.currentTarget;
        if (clickedButton) {
            clickedButton.classList.add('active');
        }
        
        // Update URL without page reload
        const url = new URL(window.location);
        url.searchParams.set('tab', tabName);
        window.history.replaceState({}, '', url);
    }
    
    // Initialize tabs on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Set active tab based on URL or default
        const urlParams = new URLSearchParams(window.location.search);
        const currentTab = urlParams.get('tab') || 'discounts';
        
        // Remove active class from all tabs and buttons first
        document.querySelectorAll('.tab-content, .tab-btn').forEach(element => {
            element.classList.remove('active');
        });
        
        // Activate current tab
        const tabContent = document.getElementById(currentTab + '-tab');
        const tabButton = document.querySelector(`.tab-btn[onclick="openTab('${currentTab}')"]`);
        
        if (tabContent) tabContent.classList.add('active');
        if (tabButton) tabButton.classList.add('active');
        
        // Auto-generate code suggestion for new discounts
        const codeInput = document.querySelector('input[name="code"]');
        if (codeInput && !codeInput.value && !<?= $discountDetails ? 'true' : 'false' ?>) {
            const suggestions = ['SUMMER25', 'WELCOME10', 'FALL15', 'WINTER20', 'SPRING30', 'HOLIDAY35'];
            codeInput.placeholder = suggestions[Math.floor(Math.random() * suggestions.length)];
        }
    });
    
    // Close any alerts after 5 seconds
    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(alert => {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(() => alert.remove(), 500);
        });
    }, 5000);
    </script>
</body>
</html>