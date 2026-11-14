<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

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

function getDailyRevenueData($pdo, $days = 30, $startDate, $endDate) {
    
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

function getOrderTrend($pdo, $days = 30, $startDate, $endDate) {
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

function getRoomStatus($pdo, $roomId) {
    $stmt = $pdo->prepare("
        SELECT status FROM reservations 
        WHERE room_id = ? 
        AND status IN ('confirmed', 'pending')
        AND check_out >= CURDATE()
        LIMIT 1
    ");
    $stmt->execute([$roomId]);
    $reservation = $stmt->fetch();
    
    return $reservation ? 'Booked' : 'Available';
}

function displayMessages() {
    if (isset($_SESSION['success'])) {
        echo '<div class="alert alert-success">'.$_SESSION['success'].'</div>';
        unset($_SESSION['success']);
    }
    if (isset($_SESSION['error'])) {
        echo '<div class="alert alert-danger">'.$_SESSION['error'].'</div>';
        unset($_SESSION['error']);
    }
}

function sendReservationEmail($to, $subject, $reservation, $action = 'new') {
    // Include PHPMailer files manually
    require_once 'phpmailer/PHPMailer.php';
    require_once 'phpmailer/SMTP.php';
    require_once 'phpmailer/Exception.php';
    
    // Use the classes
    
    
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';     // Gmail SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'ahmadbabatunde123@gmail.com'; // YOUR GMAIL
        $mail->Password   = 'dbmx szty qpjp nzua';    // YOUR APP PASSWORD
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('noreply@hotel.com', 'Your Hotel Name');
        $mail->addAddress($to, $reservation['first_name'] . ' ' . $reservation['last_name']);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        
        // Generate email content
        $cancelLink = ($action == 'new') ? 
            "<a href='".SITE_URL."/cancel_reservation.php?token=".md5($reservation['id'].$reservation['guest_email'])."'
            style='background-color: #e74c3c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 15px;'>
            Cancel Reservation</a>" : '';
        
        $check_in = date('d/m/Y', strtotime($reservation['check_in']));
        $check_out = date('d/m/Y', strtotime($reservation['check_out']));
        
        $mail->Body = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
                .header { background-color: #2c3e50; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
                .content { padding: 20px; }
                .detail { margin-bottom: 10px; }
                .detail-label { font-weight: bold; }
                .footer { margin-top: 20px; font-size: 0.9em; color: #777; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>Your Hotel Reservation</h2>
                </div>
                <div class='content'>
                    <p>Hello ".htmlspecialchars($reservation['title'])." ".htmlspecialchars($reservation['last_name']).",</p>
                    
                    ".($action == 'new' ? "<p>Thank you for your reservation. Here are your details:</p>" : 
                      "<p>Your reservation has been updated. Here are the current details:</p>")."
                    
                    <div class='detail'><span class='detail-label'>Reservation ID:</span> #".$reservation['id']."</div>
                    <div class='detail'><span class='detail-label'>Guest Name:</span> ".htmlspecialchars($reservation['title'])." ".htmlspecialchars($reservation['first_name'])." ".htmlspecialchars($reservation['last_name'])."</div>
                    <div class='detail'><span class='detail-label'>Room:</span> ".htmlspecialchars($reservation['room_number'])." (".htmlspecialchars($reservation['room_type']).")</div>
                    <div class='detail'><span class='detail-label'>Check-in:</span> ".$check_in."</div>
                    <div class='detail'><span class='detail-label'>Check-out:</span> ".$check_out."</div>
                    <div class='detail'><span class='detail-label'>Status:</span> ".ucfirst($reservation['status'])."</div>
                    <div class='detail'><span class='detail-label'>Email:</span> ".htmlspecialchars($reservation['guest_email'])."</div>
                    <div class='detail'><span class='detail-label'>Phone:</span> ".htmlspecialchars($reservation['guest_phone'])."</div>
                    
                    ".($reservation['special_requests'] ? 
                    "<div class='detail'><span class='detail-label'>Special Requests:</span> ".nl2br(htmlspecialchars($reservation['special_requests']))."</div>" : '')."
                    
                    <p>If you have any questions, please contact us at contact@yourhotel.com</p>
                    
                    ".$cancelLink."
                </div>
                <div class='footer'>
                    <p>© ".date('Y')." Your Hotel. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ";
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}
?>