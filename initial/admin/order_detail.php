<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

// Get order ID from query parameter
$order_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = getOrderById($pdo, $order_id);

if (!$order) {
    header('Location: orders.php');
    exit();
}

// Handle order completion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complete_order'])) {
    completeOrder($pdo, $order_id);
    header('Location: orders.php');
    exit();
}

// Calculate total
$total = 0;
foreach ($order['items'] as $item) {
    $total += $item['price'] * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Order Details #<?= $order['id'] ?></h1>
        
        <div class="order-details">
            <div class="detail-row">
                <span class="detail-label">Location:</span>
                <span class="detail-value"><?= htmlspecialchars($order['location_label']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Status:</span>
                <span class="detail-value"><?= ucfirst($order['status']) ?></span>
            </div>
            
            <div class="detail-row">
                <span class="detail-label">Order Time:</span>
                <span class="detail-value"><?= date('Y-m-d H:i:s', strtotime($order['created_at'])) ?></span>
            </div>
            
            <?php if ($order['status'] === 'completed'): ?>
                <div class="detail-row">
                    <span class="detail-label">Completed Time:</span>
                    <span class="detail-value"><?= date('Y-m-d H:i:s', strtotime($order['completed_at'])) ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($order['notes'])): ?>
                <div class="detail-row">
                    <span class="detail-label">Notes:</span>
                    <span class="detail-value"><?= htmlspecialchars($order['notes']) ?></span>
                </div>
            <?php endif; ?>
        </div>
        
        <h2>Order Items</h2>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Quantity</th>
                    <th>Price</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($order['items'] as $item): ?>
                    <tr>
                        <td><?= htmlspecialchars($item['service_name']) ?></td>
                        <td><?= $item['quantity'] ?></td>
                        <td>$<?= number_format($item['price'], 2) ?></td>
                        <td>$<?= number_format($item['price'] * $item['quantity'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="3" class="text-right"><strong>Total:</strong></td>
                    <td><strong>$<?= number_format($total, 2) ?></strong></td>
                </tr>
            </tfoot>
        </table>
        
        <div class="order-actions">
            <?php if ($order['status'] === 'pending'): ?>
                <form method="POST" action="order_detail.php?id=<?= $order['id'] ?>">
                    <button type="submit" name="complete_order" class="btn success">Mark as Completed</button>
                </form>
            <?php endif; ?>
            <a href="orders.php" class="btn">Back to Orders</a>
        </div>
    </div>
</body>
</html>