<?php
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');
header('Connection: keep-alive');
header('X-Accel-Buffering: no');

require_once __DIR__.'/../includes/db.php';
require_once __DIR__.'/../includes/auth.php';
require_once __DIR__.'/../includes/functions.php';

ignore_user_abort(true);
set_time_limit(0);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_id'])) {
    die("event: error\ndata: Unauthorized\n\n");
}

// Store the last check time in session
$sessionKey = 'last_notification_check_reservations';
if (!isset($_SESSION[$sessionKey])) {
    $_SESSION[$sessionKey] = date('Y-m-d H:i:s');
}

// Close session to free up resources for other requests
session_write_close();

try {
    $checkCount = 0;
    $maxChecks = 100; // Limit to 100 checks (~5 minutes) then reconnect
    
    while ($checkCount < $maxChecks) {
        if (connection_aborted()) {
            exit();
        }

        // Use a more efficient query with LIMIT
        $sql = "SELECT id, room_id, check_in, created_at 
                FROM reservations 
                WHERE status = 'pending' AND created_at > ? 
                ORDER BY created_at ASC 
                LIMIT 10";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$_SESSION[$sessionKey]]);
        $newReservations = $stmt->fetchAll();
        
        if (!empty($newReservations)) {
            foreach ($newReservations as $reservation) {
                $data = [
                    'id' => $reservation['id'],
                    'room_id' => $reservation['room_id'],
                    'check_in' => $reservation['check_in'],
                    'time' => date('H:i', strtotime($reservation['created_at']))
                ];
                
                echo "id: {$reservation['id']}\n";
                echo "event: new-reservation\n";
                echo "data: ".json_encode($data)."\n\n";
                
                ob_flush();
                flush();
            }
            
            // Update last check time
            $lastTime = end($newReservations)['created_at'];
            
            // Reopen session briefly to save the update
            session_start();
            $_SESSION[$sessionKey] = $lastTime;
            session_write_close();
        } else {
            // Send heartbeat less frequently when no data
            if ($checkCount % 5 === 0) { // Every 15 seconds
                echo ": heartbeat\n\n";
                ob_flush();
                flush();
            }
        }
        
        $checkCount++;
        sleep(3); // Increased sleep time
    }
    
    // Force client to reconnect after max checks
    echo "event: reconnect\n";
    echo "data: Reconnecting...\n\n";
    ob_flush();
    flush();
    
} catch (Exception $e) {
    error_log("SSE Error: ".$e->getMessage());
    // Don't die, just let the connection close naturally
}
?>