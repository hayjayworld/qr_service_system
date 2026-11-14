<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireStaffOrHigher();

// Get stats for dashboard
$pendingOrders = count(getPendingOrders($pdo));
$totalServices = count(getAllServices($pdo));
$totalQRCodes = count(getAllQRCodes($pdo));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Hotel QR System</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card ">
                <h2>Pending Orders</h2>
                <div class="stat-value"><?= $pendingOrders ?></div>
                <a href="orders.php" class="stat-link">View Orders</a>
            </div>
            
            <div class="stat-card ">
                <h2>Total Services</h2>
                <div class="stat-value"><?= $totalServices ?></div>
                <a href="services.php" class="stat-link">Manage Services</a>
            </div>
            
            <div class="stat-card ">
                <h2>QR Codes</h2>
                <div class="stat-value"><?= $totalQRCodes ?></div>
                <a href="qr_generator.php" class="stat-link">Manage QR Codes</a>
            </div>
        </div>
        
        <div class="recent-orders">
            <h2>Recent Pending Orders</h2>
            <?php 
            $orders = getPendingOrders($pdo);
            if (count($orders) > 0): 
            ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Location</th>
                            <th>Items</th>
                            <th>Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($orders, 0, 5) as $order): 
                        ?>
                            <tr>
                                <td><?= $order['id'] ?></td>
                                <td><?= htmlspecialchars($order['location_label']) ?></td>
                                <td><?= (int)$order['item_count'] ?> item<?= (int)$order['item_count'] !== 1 ? 's' : '' ?></td>
                                <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <a href="order_detail.php?id=<?= $order['id'] ?>" class="btn small">View</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="text-right">
                    <a href="orders.php" class="btn">View All Orders</a>
                </div>
            <?php else: ?>
                <p>No pending orders at this time.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>