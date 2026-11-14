<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Handle reservation submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservation'])) {
    $title = $_POST['title'];
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $guestName = $title . ' ' . $firstName . ' ' . $lastName;
    
    // Convert date format from dd/mm/yyyy to yyyy-mm-dd for database
    $check_in = DateTime::createFromFormat('d/m/Y', $_POST['check_in'])->format('Y-m-d');
    $check_out = DateTime::createFromFormat('d/m/Y', $_POST['check_out'])->format('Y-m-d');
    
    $stmt = $pdo->prepare("INSERT INTO reservations (room_id, title, first_name, last_name, guest_name, guest_email, guest_phone, check_in, check_out, adults, children, special_requests) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $_POST['room_id'],
        $title,
        $firstName,
        $lastName,
        $guestName,
        $_POST['guest_email'],
        $_POST['guest_phone'],
        $check_in,
        $check_out,
        $_POST['adults'],
        $_POST['children'] ?? 0,
        $_POST['special_requests'] ?? ''
    ]);
    
    // Get the newly created reservation
    $reservationId = $pdo->lastInsertId();
    $reservation = $pdo->query("
        SELECT r.*, rm.room_number, rm.room_type 
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        WHERE r.id = $reservationId
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Send confirmation email
    sendReservationEmail(
        $reservation['guest_email'],
        "Your Hotel Reservation Confirmation #".$reservation['id'],
        $reservation,
        'new'
    );
    
    // Show success message
    echo "<script>alert('Reservation submitted successfully! A confirmation has been sent to your email.');</script>";
}

// Get available rooms with date conversion
$check_in = $_GET['check_in'] ?? date('d/m/Y');
$check_out = $_GET['check_out'] ?? date('d/m/Y', strtotime('+1 day'));

// Convert to database format for query
$check_in_db = DateTime::createFromFormat('d/m/Y', $check_in)->format('Y-m-d');
$check_out_db = DateTime::createFromFormat('d/m/Y', $check_out)->format('Y-m-d');

$stmt = $pdo->prepare("
    SELECT r.* FROM rooms r
    WHERE r.id NOT IN (
        SELECT room_id FROM reservations 
        WHERE status IN ('confirmed', 'pending')
        AND (
            (check_in <= :check_in AND check_out >= :check_in) OR
            (check_in <= :check_out AND check_out >= :check_out) OR
            (check_in >= :check_in AND check_out <= :check_out)
        )
    )
");

$stmt->execute([
    ':check_in' => $check_in_db,
    ':check_out' => $check_out_db
]);

$availableRooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Room Reservation</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Main Container */
        .reservation-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        /* Header */
        .reservation-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .reservation-header h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        
        .location-badge {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
        }
        
        /* Date Selection */
        .date-selection {
            background: #f8f9fa;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        
        .date-selection h2 {
            margin-top: 0;
            color: #2c3e50;
            font-size: 1.2em;
        }
        
        .date-form {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            align-items: end;
        }
        
        .form-group {
            margin-bottom: 0;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #495057;
        }
        
        .form-group input[type="date"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        /* Rooms List */
        .rooms-list {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
        }
        
        .room-card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            overflow: hidden;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .room-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        .room-image {
            width: 100%;
            height: 200px;
            object-fit: cover;
        }
        
        .room-details {
            padding: 20px;
        }
        
        .room-title {
            font-size: 1.3em;
            margin: 0 0 10px;
            color: #2c3e50;
        }
        
        .room-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 0.9em;
            color: #6c757d;
        }
        
        .room-price {
            font-size: 1.4em;
            font-weight: bold;
            color: #2ecc71;
            margin: 10px 0;
        }
        
        .room-amenities {
            margin: 15px 0;
            font-size: 0.9em;
        }
        
        .room-amenities strong {
            display: block;
            margin-bottom: 5px;
            color: #495057;
        }
        
        .room-status {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
            margin-bottom: 15px;
        }
        
        .status-available {
            background: #d4edda;
            color: #155724;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #3498db;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s;
            width: 100%;
            text-align: center;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        /* Booking Modal */
        dialog {
            width: 90%;
            max-width: 500px;
            border: none;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.3);
        }
        
        dialog::backdrop {
            background: rgba(0,0,0,0.5);
        }
        
        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            margin: 0;
            color: #2c3e50;
        }
        
        .close-modal {
            background: none;
            border: none;
            font-size: 1.5em;
            cursor: pointer;
            color: #6c757d;
        }
        
        .form-row {
            margin-bottom: 15px;
        }
        
        .form-row label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-row input, 
        .form-row select, 
        .form-row textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }
        
        .form-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        
        .btn-secondary {
            background: #6c757d;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        
        
        .name-row {
            display: grid;
            grid-template-columns: 80px 1fr 1fr;
            gap: 10px;
        }
        /* Responsive Adjustments */
        @media (max-width: 768px) {
            .date-form {
                grid-template-columns: 1fr;
            }
            
            .rooms-list {
                grid-template-columns: 1fr;
            }
            
            .room-card {
                max-width: 100%;
            }
            .name-row {
                grid-template-columns: 1fr;
            }
        }
        
        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            grid-column: 1 / -1;
        }
        
        .empty-state i {
            font-size: 3em;
            color: #6c757d;
            margin-bottom: 15px;
        }

    </style>
</head>
<body>
    <div class="reservation-container">
        <header class="reservation-header">
            <h1>Hotel Room Reservation</h1>
            <?php if ($location): ?>
                <div class="location-badge">Location: <?= htmlspecialchars($location) ?></div>
            <?php endif; ?>
        </header>

        <section class="date-selection">
            <h2>Select Your Dates</h2>
            <form method="GET" class="date-form">
                <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
                <div class="form-group">
                    <label for="check_in">Check-in Date (DD/MM/YYYY)</label>
                    <input type="text" id="check_in" name="check_in" required 
                           placeholder="DD/MM/YYYY"
                           value="<?= htmlspecialchars($check_in) ?>">
                </div>
                <div class="form-group">
                    <label for="check_out">Check-out Date (DD/MM/YYYY)</label>
                    <input type="text" id="check_out" name="check_out" required 
                           placeholder="DD/MM/YYYY"
                           value="<?= htmlspecialchars($check_out) ?>">
                </div>
                <div class="form-group">
                    <button type="submit" class="btn">Check Availability</button>
                </div>
            </form>
        </section>

        <section class="rooms-list">
            <?php if (empty($availableRooms)): ?>
                <div class="empty-state">
                    <i class="fas fa-bed"></i>
                    <h3>No rooms available for the selected dates</h3>
                    <p>Please try different dates or contact us for assistance.</p>
                </div>
            <?php else: ?>
                <?php foreach ($availableRooms as $room): ?>
                <article class="room-card">
                    <img src="<?= $room['image_path'] ?? '../assets/images/room-default.jpeg' ?>" 
                         alt="<?= htmlspecialchars($room['room_type']) ?>" class="room-image">
                    <div class="room-details">
                        <h3 class="room-title"><?= htmlspecialchars($room['room_number']) ?> - <?= htmlspecialchars($room['room_type']) ?></h3>
                        <div class="room-meta">
                            <span><i class="fas fa-user"></i> Max <?= $room['max_occupancy'] ?> guests</span>
                        </div>
                        <div class="room-price">$<?= number_format($room['price_per_night'], 2) ?>/night</div>
                        <div class="room-amenities">
                            <strong>Amenities:</strong>
                            <?= htmlspecialchars($room['amenities']) ?>
                        </div>
                        <span class="room-status status-available">Available</span>
                        <button class="btn" onclick="document.getElementById('bookRoom<?= $room['id'] ?>').showModal()">
                            <i class="fas fa-calendar-check"></i> Book Now
                        </button>
                    </div>
                </article>

                <!-- Booking Dialog -->
                <dialog id="bookRoom<?= $room['id'] ?>">
                    <div class="modal-header">
                        <h2>Book Room <?= htmlspecialchars($room['room_number']) ?></h2>
                        <button class="close-modal" onclick="document.getElementById('bookRoom<?= $room['id'] ?>').close()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <form method="POST">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                        <input type="hidden" name="check_in" value="<?= $check_in ?>">
                        <input type="hidden" name="check_out" value="<?= $check_out ?>">
                        
                        <div class="form-row name-row">
                            <div>
                                <label for="title">Title</label>
                                <select id="title" name="title" required>
                                    <option value="Mr">Mr</option>
                                    <option value="Mrs">Mrs</option>
                                    <option value="Miss">Miss</option>
                                    <option value="Ms">Ms</option>
                                    <option value="Dr">Dr</option>
                                    <option value="Prof">Prof</option>
                                </select>
                            </div>
                            <div>
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" required>
                            </div>
                            <div>
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" required>
                            </div>
                        </div>
                        
                        <div class="form-row">
                            <label for="guest_email">Email Address</label>
                            <input type="email" id="guest_email" name="guest_email" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="guest_phone">Phone Number</label>
                            <input type="tel" id="guest_phone" name="guest_phone" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="adults">Number of Adults</label>
                            <input type="number" id="adults" name="adults" min="1" 
                                   max="<?= $room['max_occupancy'] ?>" value="1" required>
                        </div>
                        
                        <div class="form-row">
                            <label for="children">Number of Children</label>
                            <input type="number" id="children" name="children" min="0" 
                                   max="<?= $room['max_occupancy'] - 1 ?>" value="0">
                        </div>
                        
                        <div class="form-row">
                            <label for="special_requests">Special Requests</label>
                            <textarea id="special_requests" name="special_requests" rows="3"></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="button" class="btn btn-secondary" 
                                    onclick="this.closest('dialog').close()">
                                Cancel
                            </button>
                            <button type="submit" name="submit_reservation" class="btn">
                                Confirm Booking
                            </button>
                        </div>
                    </form>
                </dialog>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </div>

    <script>
    // Date validation and formatting
    document.addEventListener('DOMContentLoaded', function() {
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
        
        // Add date validation
        [checkInInput, checkOutInput].forEach(input => {
            input.addEventListener('blur', function() {
                const value = this.value.trim();
                if (value && !isValidDate(value)) {
                    alert('Please enter a valid date in DD/MM/YYYY format');
                    this.value = '';
                    this.focus();
                }
            });
        });
        
        function isValidDate(dateString) {
            // DD/MM/YYYY format validation
            const pattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
            if (!pattern.test(dateString)) return false;
            
            const parts = dateString.split('/');
            const day = parseInt(parts[0], 10);
            const month = parseInt(parts[1], 10);
            const year = parseInt(parts[2], 10);
            
            // Check if valid date
            const date = new Date(year, month - 1, day);
            return date.getFullYear() === year && 
                   date.getMonth() === month - 1 && 
                   date.getDate() === day;
        }
    });
    </script>
</body>
</html>