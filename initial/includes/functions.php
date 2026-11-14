<?php
require_once 'db.php';

// Get all categories
function getAllCategories($pdo) {
    $sql = "SELECT * FROM categories ORDER BY name";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
}

// Get services by category
function getServicesByCategory($pdo, $category_id) {
    $sql = "SELECT * FROM services WHERE category_id = ? AND is_active = TRUE ORDER BY name";
    $stmt = executeQuery($pdo, $sql, [$category_id]);
    return $stmt->fetchAll();
}


// Get service by ID
function getServiceById($pdo, $service_id) {
    $sql = "SELECT s.*, c.name AS category_name FROM services s 
            JOIN categories c ON s.category_id = c.id 
            WHERE s.id = ?";
    $stmt = executeQuery($pdo, $sql, [$service_id]);
    return $stmt->fetch();
}

// Get all QR codes
function getAllQRCodes($pdo) {
    $sql = "SELECT * FROM qr_codes ORDER BY label";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
}

// Create new QR code
function createQRCode($pdo, $label) {
    $sql = "INSERT INTO qr_codes (label) VALUES (?)";
    executeQuery($pdo, $sql, [$label]);
    return $pdo->lastInsertId();
}

// Get all pending orders
function getPendingOrders($pdo) {
    $sql = "SELECT o.id, o.location_label, o.created_at, o.status,
                   COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'pending'
            GROUP BY o.id
            ORDER BY o.created_at DESC";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
}

function getCompletedOrders($pdo) {
    $sql = "SELECT o.*, 
                   COALESCE(COUNT(oi.id), 0) as item_count,
                   COALESCE(SUM(oi.price * oi.quantity), 0) as total
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'completed'
            GROUP BY o.id
            ORDER BY o.completed_at DESC
            LIMIT 50";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
}

// Get order by ID
function getOrderById($pdo, $order_id) {
    // Get order info
    $sql = "SELECT o.* FROM orders o WHERE o.id = ?";
    $stmt = executeQuery($pdo, $sql, [$order_id]);
    $order = $stmt->fetch();
    
    if (!$order) return null;
    
    // Get order items
    $sql = "SELECT oi.*, s.name as service_name 
            FROM order_items oi
            JOIN services s ON oi.service_id = s.id
            WHERE oi.order_id = ?";
    $stmt = executeQuery($pdo, $sql, [$order_id]);
    $order['items'] = $stmt->fetchAll();
    
    return $order;
}

// Create new order
function createOrder($pdo, $location_label, $items, $notes = '') {
    $pdo->beginTransaction();
    try {
        // Insert order with NULL items (since we're using order_items)
        $sql = "INSERT INTO orders (location_label, items, notes) VALUES (?, NULL, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$location_label, $notes]);
        $orderId = $pdo->lastInsertId();
        
        // Insert into order_items
        $itemSql = "INSERT INTO order_items (order_id, service_id, price, quantity) 
                    VALUES (?, ?, ?, ?)";
        $itemStmt = $pdo->prepare($itemSql);
        
        foreach ($items as $item) {
            $itemStmt->execute([
                $orderId,
                $item['id'],
                $item['price'],
                $item['quantity']
            ]);
        }
        
        $pdo->commit();
        return $orderId;
    } catch(Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// Mark order as completed
function completeOrder($pdo, $order_id) {
    $sql = "UPDATE orders SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ?";
    executeQuery($pdo, $sql, [$order_id]);
}

// Get all services
function getAllServices($pdo) {
    $sql = "SELECT s.*, c.name AS category_name FROM services s 
            JOIN categories c ON s.category_id = c.id 
            ORDER BY c.name, s.name";
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
}

// Create new service
function createService($pdo, $category_id, $name, $description, $price) {
    $sql = "INSERT INTO services (category_id, name, description, price) VALUES (?, ?, ?, ?)";
    executeQuery($pdo, $sql, [$category_id, $name, $description, $price]);
    return $pdo->lastInsertId();
}

// Update service
function updateService($pdo, $service_id, $category_id, $name, $description, $price, $is_active) {
    $sql = "UPDATE services SET category_id = ?, name = ?, description = ?, price = ?, is_active = ? WHERE id = ?";
    executeQuery($pdo, $sql, [$category_id, $name, $description, $price, $is_active, $service_id]);
}

// Delete service
function deleteService($pdo, $service_id) {
    $sql = "DELETE FROM services WHERE id = ?";
    executeQuery($pdo, $sql, [$service_id]);
}

// Get admin details
function getAdminDetails($pdo, $admin_id) {
    $sql = "SELECT * FROM admin WHERE id = ?";
    $stmt = executeQuery($pdo, $sql, [$admin_id]);
    return $stmt->fetch();
}

// Update admin password
function updateAdminPassword($pdo, $admin_id, $new_password_hash) {
    $sql = "UPDATE admin SET password_hash = ? WHERE id = ?";
    executeQuery($pdo, $sql, [$new_password_hash, $admin_id]);
}

// Analytics Functions
function getDailySales($pdo, $date) {
    $sql = "SELECT 
                COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue,
                (SELECT COALESCE(SUM(oi2.price * oi2.quantity), 0)
                 FROM orders o2
                 JOIN order_items oi2 ON o2.id = oi2.order_id
                 WHERE DATE(o2.created_at) = DATE_SUB(?, INTERVAL 1 DAY)) as prev_revenue
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(o.created_at) = ?";
    
    $stmt = executeQuery($pdo, $sql, [$date, $date]);
    $data = $stmt->fetch();
    $current = (float)$data['total_revenue'];
    $previous = (float)$data['prev_revenue'];
    
    // Calculate growth percentage safely
    if ($previous == 0) {
        // If there were no sales yesterday, but sales today = infinite growth
        $growth = $current > 0 ? 100 : 0;
    } else {
        $growth = round((($current - $previous) / $previous * 100), 2);
    }
    
    return [
        'total_revenue' => $current,
        'growth' => $growth
    ];
}

function getMonthlySales($pdo, $startDate, $endDate) {
    $sql = "SELECT 
                COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue,
                (SELECT COALESCE(SUM(oi2.price * oi2.quantity), 0)
                 FROM orders o2
                 JOIN order_items oi2 ON o2.id = oi2.order_id
                 WHERE o2.created_at BETWEEN DATE_SUB(?, INTERVAL 1 MONTH) AND DATE_SUB(?, INTERVAL 1 MONTH)) as prev_revenue
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?";
    
    $stmt = executeQuery($pdo, $sql, [$startDate, $endDate, $startDate, $endDate]);
    $data = $stmt->fetch();
    
    $current = (float)$data['total_revenue'];
    $previous = (float)$data['prev_revenue'];
    
    // Calculate growth percentage safely
    if ($previous == 0) {
        // If there were no sales last month, but sales this month = infinite growth
        $growth = $current > 0 ? 100 : 0;
    } else {
        $growth = round((($current - $previous) / $previous * 100), 2);
    }
    
    return [
        'total_revenue' => $current,
        'growth' => $growth
    ];
}

function getTopServices($pdo, $startDate, $endDate, $limit = 5) {
    $sql = "SELECT 
                s.id,
                s.name,
                c.name as category_name,
                COUNT(DISTINCT o.id) as order_count,
                COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue,
                COALESCE(AVG(oi.price), 0) as avg_price
            FROM services s
            LEFT JOIN categories c ON s.category_id = c.id
            LEFT JOIN order_items oi ON s.id = oi.service_id
            LEFT JOIN orders o ON oi.order_id = o.id AND DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY s.id
            ORDER BY total_revenue DESC
            LIMIT 5";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    return $stmt->fetchAll();
}

function getRevenueByCategory($pdo, $startDate, $endDate) {
    $sql = "SELECT 
                c.name as category,
                SUM(oi.price * oi.quantity) as revenue
            FROM order_items oi
            JOIN services s ON oi.service_id = s.id
            JOIN categories c ON s.category_id = c.id
            JOIN orders o ON oi.order_id = o.id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY c.id
            ORDER BY revenue DESC";
    $stmt = executeQuery($pdo, $sql, [$startDate, $endDate]);
    return $stmt->fetchAll();
}

function getOrderStats($pdo) {
    $currentMonthStart = date('Y-m-01');
    $currentMonthEnd = date('Y-m-t');
    $lastMonthStart = date('Y-m-01', strtotime('-1 month'));
    $lastMonthEnd = date('Y-m-t', strtotime('-1 month'));
    
    // Current month stats
    $sql = "SELECT 
                COUNT(DISTINCT o.id) as total_orders,
                COALESCE(SUM(oi.price * oi.quantity), 0) as total_revenue,
                CASE 
                    WHEN COUNT(DISTINCT o.id) = 0 THEN 0
                    ELSE SUM(oi.price * oi.quantity) / COUNT(DISTINCT o.id)
                END as avg_order_value
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?";
    
    $stmt = executeQuery($pdo, $sql, [$currentMonthStart, $currentMonthEnd]);
    $current = $stmt->fetch();
    
    // Previous month stats
    $stmt = executeQuery($pdo, $sql, [$lastMonthStart, $lastMonthEnd]);
    $previous = $stmt->fetch();
    
    // Safe growth calculations
    $orderGrowth = ($previous['total_orders'] > 0) 
        ? round((($current['total_orders'] - $previous['total_orders']) / $previous['total_orders'] * 100), 2)
        : ($current['total_orders'] > 0 ? 100 : 0);
    
    $aovGrowth = ($previous['avg_order_value'] > 0)
        ? round((($current['avg_order_value'] - $previous['avg_order_value']) / $previous['avg_order_value'] * 100), 2)
        : ($current['avg_order_value'] > 0 ? 100 : 0);
    
    return [
        'total_orders' => (int)$current['total_orders'],
        'order_growth' => $orderGrowth,
        'avg_order_value' => (float)$current['avg_order_value'],
        'aov_growth' => $aovGrowth
    ];
}

function getRecentOrders($pdo, $limit = 10) {
    $sql = "SELECT 
                o.id,
                o.location_label,
                o.status,
                o.created_at,
                COALESCE(SUM(oi.price * oi.quantity), 0) as total,
                COUNT(oi.id) as item_count
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            GROUP BY o.id
            ORDER BY DATE(o.created_at) DESC
            LIMIT 10";
    
    $stmt = executeQuery($pdo, $sql);
    return $stmt->fetchAll();
    
    // Safely process items
    foreach ($orders as &$order) {
        $order['items'] = json_decode($order['items'] ?? '[]', true);
        $order['item_count'] = is_array($order['items']) ? count($order['items']) : 0;
    }
    
    return $orders;
}

function getDailyRevenueData($pdo, $days = 30) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    $sql = "SELECT 
                DATE(o.created_at) as date,
                COALESCE(SUM(oi.price * oi.quantity), 0) as revenue
            FROM orders o
            LEFT JOIN order_items oi ON o.id = oi.order_id
            WHERE DATE(o.created_at) BETWEEN ? AND ?
            GROUP BY DATE(o.created_at)
            ORDER BY DATE(o.created_at)";
    
    $stmt = executeQuery($pdo, $sql, [$startDate, $endDate]);
    return $stmt->fetchAll();
}

function getOrderTrend($pdo, $days = 30) {
    $endDate = date('Y-m-d');
    $startDate = date('Y-m-d', strtotime("-$days days"));
    
    $sql = "SELECT 
                DATE(created_at) as date,
                COUNT(id)  as revenue
            FROM orders 
            WHERE DATE(created_at) BETWEEN ? AND ?
            GROUP BY DATE(created_at) 
            ORDER BY DATE(created_at) ASC";
    
    $stmt = executeQuery($pdo, $sql,[$startDate, $endDate]);
    return $stmt->fetchAll();
}

function deleteQRCode($pdo, $qr_id) {
    $sql = "DELETE FROM qr_codes WHERE id = ?";
    executeQuery($pdo, $sql, [$qr_id]);
}

function deleteUser($pdo, $qr_id) {
    $sql = "DELETE FROM admin WHERE id = ?";
    executeQuery($pdo, $sql, [$qr_id]);
}

function getComplaintsByStatus($pdo, $status) {
    $sql = "SELECT * FROM complaints WHERE status = ? ORDER BY created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$status]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>