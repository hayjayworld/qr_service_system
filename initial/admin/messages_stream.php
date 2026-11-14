<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no'); // Important for Nginx servers

require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

ignore_user_abort(true);
set_time_limit(0);

// Only start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verify admin authentication
if (!isset($_SESSION['admin_id'])) {
    die("event: error\ndata: Unauthorized\n\n");
}

$lastEventId = $_SERVER['HTTP_LAST_EVENT_ID'] ?? 0;

try {
        // Check connection status
        if (connection_aborted()) {
            exit();
        }

        $sql = "SELECT * FROM complaints WHERE status = 'unread' AND id > ? ORDER BY created_at DESC";
        $stmt = executeQuery($pdo, $sql, [$lastEventId]);
        $newOrders = $stmt->fetchAll();
        
        if (!empty($newOrders)) {
            foreach ($newOrders as $order) {
                $data = [
                    'id' => $order['id'],
                    'location' => $order['location_label'],
                    'items' => json_decode($order['items'], true),
                    'time' => date('H:i', strtotime($order['created_at']))
                ];
                
                echo "id: {$order['id']}\n";
                echo "event: new-order\n";
                echo "data: ".json_encode($data)."\n\n";
                
                ob_flush();
                flush();
                
                $lastEventId = $order['id'];
            }
        }
        
        // Sleep for 3 seconds (reduce server load)
        sleep(3);
} catch (Exception $e) {
    error_log("SSE Error: ".$e->getMessage());
    die("event: error\ndata: Server error\n\n");
}
?>