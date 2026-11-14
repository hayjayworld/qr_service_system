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
    
    // After form submission, process the discount and calculate prices
    $roomId = $_POST['room_id'];
    $checkIn = $_POST['check_in']; // DD/MM/YYYY format
    $checkOut = $_POST['check_out']; // DD/MM/YYYY format
    $gender = $_POST['gender'];
    $countryCode = $_POST['country_code'];
    $phoneNumber = $_POST['guest_phone'];
    $fullPhone = $countryCode . $phoneNumber;

    // Convert dates to database format (YYYY-MM-DD)
    $check_in_db = DateTime::createFromFormat('d/m/Y', $checkIn)->format('Y-m-d');
    $check_out_db = DateTime::createFromFormat('d/m/Y', $checkOut)->format('Y-m-d');

    // Get room price
    $roomPrice = getRoomPrice($pdo, $roomId);
    $nights = calculateNights($checkIn, $checkOut);
    $subtotal = $roomPrice * $nights;

    // Process 
    $discountCode = !empty($_POST['discount_code']) ? $_POST['discount_code'] : null;
    $discountAmount = (float)($_POST['discount_amount'] ?? 0);

    // Validate discount code if provided
    if (!empty($discountCode)) {
        $stmt = $pdo->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = true");
        $stmt->execute([$discountCode]);
        $discount = $stmt->fetch();
        
        if ($discount) {
            // Recalculate discount amount (server-side validation)
            if ($discount['discount_type'] === 'percentage') {
                $discountAmount = ($subtotal * $discount['discount_value']) / 100;
            } else {
                $discountAmount = min($discount['discount_value'], $subtotal);
            }
            
            // Update discount usage
            $pdo->prepare("UPDATE discounts SET current_uses = current_uses + 1 WHERE code = ?")
                ->execute([$discountCode]);
        } else {
            // Invalid discount code, reset values
            $discountCode = null;
            $discountAmount = 0;
        }
    } else {
        $discountAmount = 0;
    }

    // Calculate final total amount
    $totalAmount = $subtotal - $discountAmount;

    // Insert reservation with all pricing information
    $stmt = $pdo->prepare("
        INSERT INTO reservations (
            room_id, title, first_name, last_name, gender, guest_email, guest_phone, 
            check_in, check_out, adults, children, special_requests,
            discount_code, discount_amount, total_amount
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $stmt->execute([
        $roomId,
        $_POST['title'],
        $_POST['first_name'],
        $_POST['last_name'],
        $gender,
        $_POST['guest_email'],
        $fullPhone,
        $check_in_db,
        $check_out_db,
        $_POST['adults'],
        $_POST['children'] ?? 0,
        $_POST['special_requests'] ?? '',
        $discountCode,
        $discountAmount,
        $totalAmount
    ]);

    $reservationId = $pdo->lastInsertId();

    // Get the complete reservation details for email
    $reservation = $pdo->query("
        SELECT r.*, rm.room_number, rm.room_type 
        FROM reservations r
        JOIN rooms rm ON r.room_id = rm.id
        WHERE r.id = $reservationId
    ")->fetch(PDO::FETCH_ASSOC);

    // Send confirmation email with pricing details
    sendReservationEmail(
        $reservation['guest_email'],
        "Your Hotel Reservation Confirmation #".$reservation['id'],
        $reservation,
        $pdo,
        'new'
    );
    
    // Show success message
    echo "<script>alert('Reservation submitted successfully! A confirmation has been sent to your email.');</script>";
}

// Get available rooms with date conversion
$check_in = $_GET['check_in'] ?? date('d/m/Y');
$check_out = $_GET['check_out'] ?? date('d/m/Y', strtotime('+1 day'));
$nights = calculateNights($check_in, $check_out);

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
    .date-field {
     
      align-items: center;
      margin-bottom: 10px;
    }
    .date-field label {
      width: 90px;
    }
    .date-field input[type="text"] {
      padding: 8px;
      width: 200px;
      background: #f9f9f9;
      border: 1px solid #ccc;
      border-radius: 4px;
      cursor: pointer;
    }
    .date-field button {
      margin-left: 5px;
      padding: 8px;
      cursor: pointer;
      background: #0077cc;
      border: none;
      color: white;
      font-size: 16px;
      border-radius: 4px;
    }
    .date-field button:hover {
      background: #005fa3;
    }
    /* Hide the native date input */
    .hidden-date {
      display: none;
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

        .price-summary {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .price-row.total {
            border-top: 1px solid #ddd;
            padding-top: 10px;
            font-weight: bold;
            font-size: 1.1em;
        }

        .text-success {
            color: #28a745 !important;
        }

        .text-danger {
            color: #dc3545 !important;
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
                    <div class="date-field">
                        <label for="check_in">Check-in:</label>
                        <input type="text" id="check_in" placeholder="DD/MM/YYYY" name="check_in" value="<?= htmlspecialchars($check_in) ?>" readonly onclick="pickDate('check_in')">
                                            <input type="date" id="check_in_picker" class="hidden-date">
                        <button type="button" onclick="pickDate('check_in')">📅</button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="date-field">
                        <label for="check_out">Check-out:</label>
                        <input type="text" id="check_out" placeholder="DD/MM/YYYY" name="check_out" value="<?= htmlspecialchars($check_out) ?>" readonly onclick="pickDate('check_out')">
                        <input type="date" id="check_out_picker" class="hidden-date">
                        <button type="button" onclick="pickDate('check_out')">📅</button>
                    </div>
                </div>
                <div class="form-group">
                    <div class="date-field">
                        <button type="submit" class="btn">Check Availability</button>
                    </div>
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
                        <div class="room-price">₦<?= number_format($room['price_per_night'], 2) ?>/night</div>
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
                    <form method="POST" data-price="<?= $room['price_per_night'] ?>">
                        <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
                        <div class="form-row">
                            <label for="dialog_check_in_<?= $room['id'] ?>">Check-in Date</label>
                            <input type="text" id="dialog_check_in_<?= $room['id'] ?>" 
                                    name="check_in" 
                                    value="<?= $check_in ?>" 
                                    placeholder="DD/MM/YYYY" required>
                            </div>

                            <div class="form-row">
                            <label for="dialog_check_out_<?= $room['id'] ?>">Check-out Date</label>
                            <input type="text" id="dialog_check_out_<?= $room['id'] ?>" 
                                    name="check_out" 
                                    value="<?= $check_out ?>" 
                                    placeholder="DD/MM/YYYY" required>
                        </div>

                        
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
                            <label for="gender">Sex</label>
                            <select id="gender" name="gender" required>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="prefer_not_to_say">Decide Not to Say</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <label for="guest_email">Email Address</label>
                            <input type="email" id="guest_email" name="guest_email" required>
                        </div>
                        
                        <div class="form-row">
                        <label for="guest_phone">Phone Number</label>
                        <div style="display:flex; gap:5px;">
                            <select id="country_code" name="country_code" required>
                                <option value="+93">🇦🇫 Afghanistan (+93)</option>
                                <option value="+355">🇦🇱 Albania (+355)</option>
                                <option value="+213">🇩🇿 Algeria (+213)</option>
                                <option value="+376">🇦🇩 Andorra (+376)</option>
                                <option value="+244">🇦🇴 Angola (+244)</option>
                                <option value="+1-268">🇦🇬 Antigua & Barbuda (+1-268)</option>
                                <option value="+54">🇦🇷 Argentina (+54)</option>
                                <option value="+374">🇦🇲 Armenia (+374)</option>
                                <option value="+61">🇦🇺 Australia (+61)</option>
                                <option value="+43">🇦🇹 Austria (+43)</option>
                                <option value="+994">🇦🇿 Azerbaijan (+994)</option>
                                <option value="+1-242">🇧🇸 Bahamas (+1-242)</option>
                                <option value="+973">🇧🇭 Bahrain (+973)</option>
                                <option value="+880">🇧🇩 Bangladesh (+880)</option>
                                <option value="+1-246">🇧🇧 Barbados (+1-246)</option>
                                <option value="+375">🇧🇾 Belarus (+375)</option>
                                <option value="+32">🇧🇪 Belgium (+32)</option>
                                <option value="+501">🇧🇿 Belize (+501)</option>
                                <option value="+229">🇧🇯 Benin (+229)</option>
                                <option value="+975">🇧🇹 Bhutan (+975)</option>
                                <option value="+591">🇧🇴 Bolivia (+591)</option>
                                <option value="+387">🇧🇦 Bosnia & Herzegovina (+387)</option>
                                <option value="+267">🇧🇼 Botswana (+267)</option>
                                <option value="+55">🇧🇷 Brazil (+55)</option>
                                <option value="+673">🇧🇳 Brunei (+673)</option>
                                <option value="+359">🇧🇬 Bulgaria (+359)</option>
                                <option value="+226">🇧🇫 Burkina Faso (+226)</option>
                                <option value="+257">🇧🇮 Burundi (+257)</option>
                                <option value="+855">🇰🇭 Cambodia (+855)</option>
                                <option value="+237">🇨🇲 Cameroon (+237)</option>
                                <option value="+1">🇨🇦 Canada (+1)</option>
                                <option value="+238">🇨🇻 Cape Verde (+238)</option>
                                <option value="+236">🇨🇫 Central African Republic (+236)</option>
                                <option value="+235">🇹🇩 Chad (+235)</option>
                                <option value="+56">🇨🇱 Chile (+56)</option>
                                <option value="+86">🇨🇳 China (+86)</option>
                                <option value="+57">🇨🇴 Colombia (+57)</option>
                                <option value="+269">🇰🇲 Comoros (+269)</option>
                                <option value="+243">🇨🇩 Congo (DRC) (+243)</option>
                                <option value="+242">🇨🇬 Congo (Republic) (+242)</option>
                                <option value="+506">🇨🇷 Costa Rica (+506)</option>
                                <option value="+385">🇭🇷 Croatia (+385)</option>
                                <option value="+53">🇨🇺 Cuba (+53)</option>
                                <option value="+357">🇨🇾 Cyprus (+357)</option>
                                <option value="+420">🇨🇿 Czech Republic (+420)</option>
                                <option value="+45">🇩🇰 Denmark (+45)</option>
                                <option value="+253">🇩🇯 Djibouti (+253)</option>
                                <option value="+1-767">🇩🇲 Dominica (+1-767)</option>
                                <option value="+1-809">🇩🇴 Dominican Republic (+1-809)</option>
                                <option value="+593">🇪🇨 Ecuador (+593)</option>
                                <option value="+20">🇪🇬 Egypt (+20)</option>
                                <option value="+503">🇸🇻 El Salvador (+503)</option>
                                <option value="+240">🇬🇶 Equatorial Guinea (+240)</option>
                                <option value="+291">🇪🇷 Eritrea (+291)</option>
                                <option value="+372">🇪🇪 Estonia (+372)</option>
                                <option value="+251">🇪🇹 Ethiopia (+251)</option>
                                <option value="+679">🇫🇯 Fiji (+679)</option>
                                <option value="+358">🇫🇮 Finland (+358)</option>
                                <option value="+33">🇫🇷 France (+33)</option>
                                <option value="+241">🇬🇦 Gabon (+241)</option>
                                <option value="+220">🇬🇲 Gambia (+220)</option>
                                <option value="+995">🇬🇪 Georgia (+995)</option>
                                <option value="+49">🇩🇪 Germany (+49)</option>
                                <option value="+233">🇬🇭 Ghana (+233)</option>
                                <option value="+30">🇬🇷 Greece (+30)</option>
                                <option value="+1-473">🇬🇩 Grenada (+1-473)</option>
                                <option value="+502">🇬🇹 Guatemala (+502)</option>
                                <option value="+224">🇬🇳 Guinea (+224)</option>
                                <option value="+245">🇬🇼 Guinea-Bissau (+245)</option>
                                <option value="+592">🇬🇾 Guyana (+592)</option>
                                <option value="+509">🇭🇹 Haiti (+509)</option>
                                <option value="+504">🇭🇳 Honduras (+504)</option>
                                <option value="+36">🇭🇺 Hungary (+36)</option>
                                <option value="+354">🇮🇸 Iceland (+354)</option>
                                <option value="+91">🇮🇳 India (+91)</option>
                                <option value="+62">🇮🇩 Indonesia (+62)</option>
                                <option value="+98">🇮🇷 Iran (+98)</option>
                                <option value="+964">🇮🇶 Iraq (+964)</option>
                                <option value="+353">🇮🇪 Ireland (+353)</option>
                                <option value="+972">🇮🇱 Israel (+972)</option>
                                <option value="+39">🇮🇹 Italy (+39)</option>
                                <option value="+1-876">🇯🇲 Jamaica (+1-876)</option>
                                <option value="+81">🇯🇵 Japan (+81)</option>
                                <option value="+962">🇯🇴 Jordan (+962)</option>
                                <option value="+7">🇰🇿 Kazakhstan (+7)</option>
                                <option value="+254">🇰🇪 Kenya (+254)</option>
                                <option value="+686">🇰🇮 Kiribati (+686)</option>
                                <option value="+965">🇰🇼 Kuwait (+965)</option>
                                <option value="+996">🇰🇬 Kyrgyzstan (+996)</option>
                                <option value="+856">🇱🇦 Laos (+856)</option>
                                <option value="+371">🇱🇻 Latvia (+371)</option>
                                <option value="+961">🇱🇧 Lebanon (+961)</option>
                                <option value="+266">🇱🇸 Lesotho (+266)</option>
                                <option value="+231">🇱🇷 Liberia (+231)</option>
                                <option value="+218">🇱🇾 Libya (+218)</option>
                                <option value="+423">🇱🇮 Liechtenstein (+423)</option>
                                <option value="+370">🇱🇹 Lithuania (+370)</option>
                                <option value="+352">🇱🇺 Luxembourg (+352)</option>
                                <option value="+261">🇲🇬 Madagascar (+261)</option>
                                <option value="+265">🇲🇼 Malawi (+265)</option>
                                <option value="+60">🇲🇾 Malaysia (+60)</option>
                                <option value="+960">🇲🇻 Maldives (+960)</option>
                                <option value="+223">🇲🇱 Mali (+223)</option>
                                <option value="+356">🇲🇹 Malta (+356)</option>
                                <option value="+692">🇲🇭 Marshall Islands (+692)</option>
                                <option value="+222">🇲🇷 Mauritania (+222)</option>
                                <option value="+230">🇲🇺 Mauritius (+230)</option>
                                <option value="+52">🇲🇽 Mexico (+52)</option>
                                <option value="+691">🇫🇲 Micronesia (+691)</option>
                                <option value="+373">🇲🇩 Moldova (+373)</option>
                                <option value="+377">🇲🇨 Monaco (+377)</option>
                                <option value="+976">🇲🇳 Mongolia (+976)</option>
                                <option value="+382">🇲🇪 Montenegro (+382)</option>
                                <option value="+212">🇲🇦 Morocco (+212)</option>
                                <option value="+258">🇲🇿 Mozambique (+258)</option>
                                <option value="+95">🇲🇲 Myanmar (+95)</option>
                                <option value="+264">🇳🇦 Namibia (+264)</option>
                                <option value="+977">🇳🇵 Nepal (+977)</option>
                                <option value="+31">🇳🇱 Netherlands (+31)</option>
                                <option value="+64">🇳🇿 New Zealand (+64)</option>
                                <option value="+505">🇳🇮 Nicaragua (+505)</option>
                                <option value="+227">🇳🇪 Niger (+227)</option>
                                <option value="+234" selected>🇳🇬 Nigeria (+234)</option>
                                <option value="+47">🇳🇴 Norway (+47)</option>
                                <option value="+968">🇴🇲 Oman (+968)</option>
                                <option value="+92">🇵🇰 Pakistan (+92)</option>
                                <option value="+680">🇵🇼 Palau (+680)</option>
                                <option value="+970">🇵🇸 Palestine (+970)</option>
                                <option value="+507">🇵🇦 Panama (+507)</option>
                                <option value="+675">🇵🇬 Papua New Guinea (+675)</option>
                                <option value="+595">🇵🇾 Paraguay (+595)</option>
                                <option value="+51">🇵🇪 Peru (+51)</option>
                                <option value="+63">🇵🇭 Philippines (+63)</option>
                                <option value="+48">🇵🇱 Poland (+48)</option>
                                <option value="+351">🇵🇹 Portugal (+351)</option>
                                <option value="+974">🇶🇦 Qatar (+974)</option>
                                <option value="+40">🇷🇴 Romania (+40)</option>
                                <option value="+7">🇷🇺 Russia (+7)</option>
                                <option value="+250">🇷🇼 Rwanda (+250)</option>
                                <option value="+685">🇼🇸 Samoa (+685)</option>
                                <option value="+378">🇸🇲 San Marino (+378)</option>
                                <option value="+966">🇸🇦 Saudi Arabia (+966)</option>
                                <option value="+221">🇸🇳 Senegal (+221)</option>
                                <option value="+381">🇷🇸 Serbia (+381)</option>
                                <option value="+248">🇸🇨 Seychelles (+248)</option>
                                <option value="+232">🇸🇱 Sierra Leone (+232)</option>
                                <option value="+65">🇸🇬 Singapore (+65)</option>
                                <option value="+421">🇸🇰 Slovakia (+421)</option>
                                <option value="+386">🇸🇮 Slovenia (+386)</option>
                                <option value="+677">🇸🇧 Solomon Islands (+677)</option>
                                <option value="+252">🇸🇴 Somalia (+252)</option>
                                <option value="+27">🇿🇦 South Africa (+27)</option>
                                <option value="+82">🇰🇷 South Korea (+82)</option>
                                <option value="+211">🇸🇸 South Sudan (+211)</option>
                                <option value="+34">🇪🇸 Spain (+34)</option>
                                <option value="+94">🇱🇰 Sri Lanka (+94)</option>
                                <option value="+249">🇸🇩 Sudan (+249)</option>
                                <option value="+597">🇸🇷 Suriname (+597)</option>
                                <option value="+268">🇸🇿 Swaziland (+268)</option>
                                <option value="+46">🇸🇪 Sweden (+46)</option>
                                <option value="+41">🇨🇭 Switzerland (+41)</option>
                                <option value="+963">🇸🇾 Syria (+963)</option>
                                <option value="+886">🇹🇼 Taiwan (+886)</option>
                                <option value="+992">🇹🇯 Tajikistan (+992)</option>
                                <option value="+255">🇹🇿 Tanzania (+255)</option>
                                <option value="+66">🇹🇭 Thailand (+66)</option>
                                <option value="+228">🇹🇬 Togo (+228)</option>
                                <option value="+676">🇹🇴 Tonga (+676)</option>
                                <option value="+1-868">🇹🇹 Trinidad & Tobago (+1-868)</option>
                                <option value="+216">🇹🇳 Tunisia (+216)</option>
                                <option value="+90">🇹🇷 Turkey (+90)</option>
                                <option value="+993">🇹🇲 Turkmenistan (+993)</option>
                                <option value="+256">🇺🇬 Uganda (+256)</option>
                                <option value="+380">🇺🇦 Ukraine (+380)</option>
                                <option value="+971">🇦🇪 United Arab Emirates (+971)</option>
                                <option value="+44">🇬🇧 United Kingdom (+44)</option>
                                <option value="+1">🇺🇸 United States (+1)</option>
                                <option value="+598">🇺🇾 Uruguay (+598)</option>
                                <option value="+998">🇺🇿 Uzbekistan (+998)</option>
                                <option value="+678">🇻🇺 Vanuatu (+678)</option>
                                <option value="+379">🇻🇦 Vatican City (+379)</option>
                                <option value="+58">🇻🇪 Venezuela (+58)</option>
                                <option value="+84">🇻🇳 Vietnam (+84)</option>
                                <option value="+967">🇾🇪 Yemen (+967)</option>
                                <option value="+260">🇿🇲 Zambia (+260)</option>
                                <option value="+263">🇿🇼 Zimbabwe (+263)</option>
                            </select>

                            <input type="tel" id="guest_phone" name="guest_phone" placeholder="Phone Number" required>
                        </div>
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
                            <div>
                                <label for="id_type">ID Type</label>
                                <select name="id_type" id="id_type">
                                    <option value="national_id">National ID</option>
                                    <option value="passport">Passport</option>
                                    <option value="drivers_license">Driver's License</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <label for="id_number">ID Number</label>
                            <input type="text" id="id_number" name="id_number" placeholder="ID Number">
                        </div>

                        <div class="form-row">
                            <label for="institution">Company/Organization/addres (optional)</label>
                            <input type="text" id="institution" name="institution" placeholder="If booking for organization">
                        </div>
                        
                        <div class="form-row">
                            <label for="special_requests">Special Requests</label>
                            <textarea id="special_requests" name="special_requests" rows="3"></textarea>
                        </div>

                        <div class="form-row">
                            <label for="discount_code">Discount Code (Optional)</label>
                            <input type="text" id="discount_code" name="discount_code" placeholder="Enter discount code">
                            <button type="button" id="apply_discount" class="btn btn-outline">Apply Discount</button>
                            <div id="discount_message" style="margin-top: 5px; font-size: 0.9em;"></div>
                        </div>

                        <!-- Add hidden fields for discount data -->
                        <input type="hidden" name="discount_code" id="form_discount_code" value="">
                        <input type="hidden" name="discount_amount" id="form_discount_amount" value="0">

                        <!-- Add price display section -->
                        <div class="price-summary">
                            <div class="price-row">
                                <span>Room Price:</span>
                                <span id="room-price">₦<?= number_format($room['price_per_night'], 2) ?>/night</span>
                            </div>
                            <div class="price-row" id="discount-row" style="display: none;">
                                <span>Discount:</span>
                                <span id="discount-amount" class="text-success"></span>
                            </div>
                            <div class="price-row total">
                                <span>Total:</span>
                                <span id="total-price">₦<?= number_format($room['price_per_night'] * $nights, 2) ?></span>
                            </div>
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
        function pickDate(targetId) {
      const picker = document.getElementById(targetId + "_picker");

      // Open the date picker
      if (picker.showPicker) {
        picker.showPicker(); // Modern browsers (Chrome, Edge)
      } else {
        picker.click(); // Fallback
      }

      // When a date is selected
      picker.onchange = function () {
        const date = new Date(this.value);
        if (!isNaN(date)) {
          const day = String(date.getDate()).padStart(2, '0');
          const month = String(date.getMonth() + 1).padStart(2, '0');
          const year = date.getFullYear();
          document.getElementById(targetId).value = `${day}/${month}/${year}`;
        }
      };
        }
       

        function updateBookingTotal(form) {
        const checkIn = form.querySelector('[id^="dialog_check_in_"]').value;
        const checkOut = form.querySelector('[id^="dialog_check_out_"]').value;
        const pricePerNight = parseFloat(form.getAttribute('data-price')); // store in form
        const totalElement = form.querySelector('#total-price');
        
        if (checkIn && checkOut && isValidDate(checkIn) && isValidDate(checkOut)) {
            const nights = calculateNightsFromDates(checkIn, checkOut);
            if (nights > 0) {
            const total = pricePerNight * nights;
            totalElement.textContent = '₦' + total.toLocaleString();
            }
        }
        }

        function calculateNightsFromDates(checkIn, checkOut) {
        const start = new Date(checkIn.split('/').reverse().join('-'));
        const end = new Date(checkOut.split('/').reverse().join('-'));
        return Math.ceil((end - start) / (1000 * 60 * 60 * 24));
        }

        function isValidDate(dateString) {
        const pattern = /^(\d{2})\/(\d{2})\/(\d{4})$/;
        if (!pattern.test(dateString)) return false;
        const [day, month, year] = dateString.split('/').map(Number);
        const date = new Date(year, month - 1, day);
        return date.getFullYear() === year && date.getMonth() === month - 1 && date.getDate() === day;
        }

    // Date validation and formatting
    document.addEventListener('DOMContentLoaded', function() {
        const checkInInput = document.getElementById('check_in');
        const checkOutInput = document.getElementById('check_out');
      
      document.querySelectorAll('[id^="dialog_check_in_"], [id^="dialog_check_out_"]').forEach(input => {
            input.addEventListener('change', function () {
            const form = input.closest('form');
            updateBookingTotal(form);
            });
        });
        
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

        // Add this JavaScript to service_reservation.php
// Add this JavaScript to handle discount application
document.getElementById('apply_discount').addEventListener('click', function() {
    const discountCode = document.getElementById('discount_code').value.trim();
    const checkIn = document.getElementById('check_in').value;
    const checkOut = document.getElementById('check_out').value;
    const roomId = document.querySelector('input[name="room_id"]').value;
    
    if (!discountCode) {
        showDiscountMessage('Please enter a discount code', 'error');
        return;
    }
    
    if (!checkIn || !checkOut) {
        showDiscountMessage('Please select check-in and check-out dates first', 'error');
        return;
    }
    
    // Calculate nights for min_stay validation
    const nights = calculateNightsFromDates(checkIn, checkOut);
    
    fetch('../includes/validate_discount.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ 
            code: discountCode, 
            nights: nights,
            room_id: roomId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.valid) {
            showDiscountMessage(data.message, 'success');
            // Update hidden form fields
            document.getElementById('form_discount_code').value = discountCode;
            document.getElementById('form_discount_amount').value = data.discount_amount;
            // Update displayed price
            updateTotalPrice(data.final_price, data.discount_amount);
        } else {
            showDiscountMessage(data.message, 'error');
            // Clear discount fields
            document.getElementById('form_discount_code').value = '';
            document.getElementById('form_discount_amount').value = '0';
            updateTotalPrice(data.original_price, 0);
        }
    })
    .catch(error => {
        showDiscountMessage('Error validating discount code', 'error');
    });
});

// Helper function to calculate nights from dates
function calculateNightsFromDates(checkIn, checkOut) {
    const start = new Date(checkIn.split('/').reverse().join('-'));
    const end = new Date(checkOut.split('/').reverse().join('-'));
    return Math.ceil((end - start) / (1000 * 60 * 60 * 24));
}

// Function to update displayed price
function updateTotalPrice(finalPrice, discountAmount) {
    const totalElement = document.getElementById('total-price');
    const discountElement = document.getElementById('discount-amount');
    
    if (totalElement) {
        totalElement.textContent = '₦' + finalPrice.toFixed(2);
    }
    
    if (discountElement) {
        if (discountAmount > 0) {
            discountElement.textContent = '-₦' + discountAmount.toFixed(2);
            discountElement.style.display = 'block';
        } else {
            discountElement.style.display = 'none';
        }
    }
}

function showDiscountMessage(message, type) {
    const messageDiv = document.getElementById('discount_message');
    messageDiv.textContent = message;
    messageDiv.className = type === 'success' ? 'text-success' : 'text-danger';
}
        

// function showDiscountMessage(message, type) {
//     const messageDiv = document.getElementById('discount_message');
//     messageDiv.textContent = message;
//     messageDiv.className = type === 'success' ? 'text-success' : 'text-danger';
// }

// function updateTotalPrice(discountAmount) {
//     // Implement your total price update logic here
//     console.log('Discount applied:', discountAmount);
// }
    </script>
</body>
</html>