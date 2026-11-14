<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireStaffOrHigher();

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    $order_id = $_POST['order_id'];
    completeOrder($pdo, $order_id);
}

// Get all orders
$pendingOrders = getPendingOrders($pdo);
$completedOrders = getCompletedOrders($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Orders - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Manage Orders</h1>
        
        <div class="tabs">
            <button class="tab-btn active" onclick="openTab('pending-orders')">Pending Orders</button>
            <button class="tab-btn" onclick="openTab('completed-orders')">Completed Orders</button>
        </div>
        
        <div id="pending-orders" class="tab-content" style="display: block;">
            <?php if (count($pendingOrders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Time</th>
                            <th style='text-align:center;'>Actions</th>
                            <th>Cancel</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pendingOrders as $order): 
                        ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['location_label']) ?></td>
                                <td><?= (int)$order['item_count'] ?> item<?= (int)$order['item_count'] !== 1 ? 's' : '' ?></td>
                                <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
                                <td style='text-align:center;'>
                                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn small">View</a>
                                    <form method="POST" action="orders.php" style="display: inline;">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <button type="submit" name="complete_order" class="btn small success">Complete</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="POST" action="cancel_order.php" class="cancel-order-form">
                                        <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                        <?php if ($_SESSION['admin_role'] !== 'staff'): ?>
                                            <button type="submit" class="btn btn-sm btn-danger">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No pending orders at this time.</p>
            <?php endif; ?>
        </div>
        
        <div id="completed-orders" class="tab-content">
            <?php if (count($completedOrders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Ordered</th>
                            <th>Completed</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($completedOrders as $order): 
                        ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['location_label']) ?></td>
                                <td><?= (int)$order['item_count'] ?> item<?= (int)$order['item_count'] !== 1 ? 's' : '' ?></td>
                                <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
                                <td><?= date('H:i', strtotime($order['completed_at'])) ?></td>
                                <td>
                                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p>No completed orders yet.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>