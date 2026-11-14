<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireStaffOrHigher();

$reservationId = $_GET['id'] ?? 0;
$reservation = $pdo->query("
    SELECT r.*, rm.room_number, rm.room_type, rm.price_per_night 
    FROM reservations r
    JOIN rooms rm ON r.room_id = rm.id
    WHERE r.id = $reservationId
")->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    header("Location: reservations.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reservation Details</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="reservation-container">
        <h1>Reservation Details #<?= $reservation['id'] ?></h1>
        
        <div class="card">
            <div class="detail-row">
                <div class="detail-label">Room:</div>
                <div class="detail-value">
                    <?= $reservation['room_number'] ?> (<?= $reservation['room_type'] ?>)
                    - $<?= number_format($reservation['price_per_night'], 2) ?>/night
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Guest:</div>
                <div class="detail-value">
                    <?= $reservation['guest_name'] ?><br>
                    <?= $reservation['guest_email'] ?><br>
                    <?= $reservation['guest_phone'] ?>
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Dates:</div>
                <div class="detail-value">
                    <?= date('M j, Y', strtotime($reservation['check_in'])) ?> to 
                    <?= date('M j, Y', strtotime($reservation['check_out'])) ?>
                    (<?= date_diff(
                        date_create($reservation['check_in']), 
                        date_create($reservation['check_out'])
                    )->format('%a nights') ?>)
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Guests:</div>
                <div class="detail-value">
                    <?= $reservation['adults'] ?> Adults, 
                    <?= $reservation['children'] ?> Children
                </div>
            </div>

            <div class="detail-row">
                <div class="detail-label">ID Type:</div>
                <div class="detail-value">
                    <?= $reservation['id_type'] ?> 
                </div>
                <div class="detail-label">ID Number:</div>
                <div class="detail-value">
                    <?= $reservation['id_number'] ?> 
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Status:</div>
                <div class="detail-value">
                    <span class="status-badge status-<?= $reservation['status'] ?>">
                        <?= ucfirst($reservation['status']) ?>
                    </span>
                </div>
            </div>
            
            <?php if ($reservation['special_requests']): ?>
            <div class="detail-row">
                <div class="detail-label">Special Requests:</div>
                <div class="detail-value">
                    <?= nl2br(htmlspecialchars($reservation['special_requests'])) ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($reservation['discount_code']): ?>
            <div class="detail-row">
                <div class="detail-label">Discount code:</div>
                <div class="detail-value">
                    <?= $reservation['discount_code'] ?> 
                </div>
                <div class="detail-label">Discount Amount:</div>
                <div class="detail-value">
                    <?= $reservation['discount_amount'] ?> 
                </div>
            </div>
            <?php endif; ?>
            
            <div class="detail-row">
                <div class="detail-label">Total Amount:</div>
                <div class="detail-value">
                    <?= $reservation['total_amount'] ?> 
                </div>
            </div>
            
            <div class="detail-row">
                <div class="detail-label">Actions:</div>
                <div class="detail-value">
                    <a href="reservations.php" class="btn">Back to List</a>
                    <?php if ($reservation['status'] == 'pending'): ?>
                        <a href="update_reservation.php?id=<?= $reservation['id'] ?>&status=confirmed" class="btn success">
                            Confirm Reservation
                        </a>
                        <a href="update_reservation.php?id=<?= $reservation['id'] ?>&status=cancelled" class="btn danger">
                            Cancel Reservation
                        </a>
                    <?php elseif ($reservation['status'] == 'confirmed'): ?>
                        <a href="update_reservation.php?id=<?= $reservation['id'] ?>&status=completed" class="btn">
                            Mark as Completed
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</body>
</html>