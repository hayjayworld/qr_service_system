<?php
// notifications.php (place in root directory)
header('Content-Type: text/event-stream');
header('Cache-Control: no-cache');

// Start session if needed
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Basic admin check
if (empty($_SESSION['admin_id'])) {
    die("event: error\ndata: Access denied\n\n");
}

// Release session lock
session_write_close();

// Simple notification stream
while (true) {
    if (connection_aborted()) exit();
    
    echo "event: ping\n";
    echo "data: " . time() . "\n\n";
    ob_flush();
    flush();
    sleep(1);
}