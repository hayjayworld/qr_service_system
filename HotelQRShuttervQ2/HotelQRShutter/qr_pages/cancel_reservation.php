<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$token = $_GET['token'] ?? '';

if ($token) {
    // Find reservation by token (md5(id + email))
    $reservations = $pdo->query("SELECT * FROM reservations")->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($reservations as $res) {
        if (md5($res['id'].$res['guest_email']) === $token) {
            // Update status to cancelled
            $stmt = $pdo->prepare("UPDATE reservations SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$res['id']]);
            
            // Send cancellation confirmation
            sendReservationEmail(
                $res['guest_email'],
                "Your Hotel Reservation Cancelled #".$res['id'],
                array_merge($res, ['status' => 'cancelled']),
                'update'
            );
            
            echo "<script>alert('Your reservation has been cancelled.'); window.close();</script>";
            exit();
        }
    }
}

echo "<script>alert('Invalid cancellation link.'); window.close();</script>";