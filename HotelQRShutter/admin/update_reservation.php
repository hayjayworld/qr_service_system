<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireStaffOrHigher();

if (isset($_GET['id']) && isset($_GET['status'])) {
    $validStatuses = ['pending', 'confirmed', 'cancelled', 'completed'];
    $status = $_GET['status'];
    
    if (in_array($status, $validStatuses)) {
        // Update status
        $stmt = $pdo->prepare("UPDATE reservations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $_GET['id']]);
        
        // Get updated reservation
        $reservation = $pdo->query("
            SELECT r.*, rm.room_number, rm.room_type 
            FROM reservations r
            JOIN rooms rm ON r.room_id = rm.id
            WHERE r.id = ".$_GET['id']
        )->fetch(PDO::FETCH_ASSOC);
        
        // Send update email
        sendReservationEmail(
            $reservation['guest_email'],
            "Your Hotel Reservation Updated #".$reservation['id'],
            $reservation,
            $pdo,
            'update'
        );
        
        $_SESSION['success'] = "Reservation status updated and notification sent!";
    }
}

header("Location: " . ($_SERVER['HTTP_REFERER'] ?? 'reservations.php'));
exit();