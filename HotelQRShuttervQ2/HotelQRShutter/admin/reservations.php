<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();
requireStaffOrHigher();

// Handle filters
$roomFilter = $_GET['room'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$dateFilter = $_GET['date'] ?? '';

// Build query with filters
$query = "SELECT r.*, rm.room_number, rm.room_type FROM reservations r 
          JOIN rooms rm ON r.room_id = rm.id WHERE 1=1";
$params = [];

if ($roomFilter) {
    $query .= " AND rm.room_number LIKE ?";
    $params[] = "%$roomFilter%";
}

if ($statusFilter) {
    $query .= " AND r.status = ?";
    $params[] = $statusFilter;
}

if ($dateFilter) {
    $query .= " AND (r.check_in <= ? AND r.check_out >= ?)";
    $params[] = $dateFilter;
    $params[] = $dateFilter;
}

$query .= " ORDER BY r.check_in DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all rooms for filter dropdown
$rooms = $pdo->query("SELECT room_number FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reservation History - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="reservation-container">
        <h1>Reservation History</h1>
        
        <!-- Filters Section -->
        <div class="filter-section card">
            <h3>Filters</h3>
            <form method="GET" class="filter-grid">
                <div class="filter-group">
                    <label>Room Number:</label>
                    <select name="room" class="filter-control">
                        <option value="">All Rooms</option>
                        <?php foreach ($rooms as $room): ?>
                            <option value="<?= $room ?>" <?= $room == $roomFilter ? 'selected' : '' ?>>
                                <?= $room ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Status:</label>
                    <select name="status" class="filter-control">
                        <option value="">All Statuses</option>
                        <option value="pending" <?= $statusFilter == 'pending' ? 'selected' : '' ?>>Pending</option>
                        <option value="confirmed" <?= $statusFilter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                        <option value="cancelled" <?= $statusFilter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        <option value="completed" <?= $statusFilter == 'completed' ? 'selected' : '' ?>>Completed</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label>Date:</label>
                    <input type="date" name="date" class="filter-control" value="<?= $dateFilter ?>">
                </div>
                
                <button type="submit" class="btn btn-primary">Apply Filters</button>
                <a href="reservations.php" class="btn btn-primary">Reset</a>
            </form>
        </div>
        
        <!-- Reservations Table -->
        <div class="card">
            <table class="data-table reservation-table">
                <thead>
                    <tr>
                        <th>Reservation ID</th>
                        <th>Room</th>
                        <th>Guest</th>
                        <th>Check-In</th>
                        <th>Check-Out</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $res): ?>
                    <tr>
                        <td>#<?= $res['id'] ?></td>
                        <td><?= $res['room_number'] ?> (<?= $res['room_type'] ?>)</td>
                        <td><?= $res['guest_name'] ?><br><?= $res['guest_email'] ?></td>
                        <td><?= date('M j, Y', strtotime($res['check_in'])) ?></td>
                        <td><?= date('M j, Y', strtotime($res['check_out'])) ?></td>
                        <td>
                            <span class="status-badge status-<?= $res['status'] ?>">
                                <?= ucfirst($res['status']) ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <a href="reservation_detail.php?id=<?= $res['id'] ?>" class="action-btn small">
                                <i class="fas fa-eye"></i> View
                            </a>
                            <?php if ($res['status'] == 'pending'): ?>
                                <a href="update_reservation.php?id=<?= $res['id'] ?>&status=confirmed" class="action-btn small success">
                                    <i class="fas fa-check"></i> Confirm
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>