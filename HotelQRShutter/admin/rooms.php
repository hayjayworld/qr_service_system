<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

//requireAuth();
//requireAdmin(); // Only admins can manage rooms

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_room'])) {
        // Add new room
        $stmt = $pdo->prepare("INSERT INTO rooms (room_number, room_type, description, price_per_night, max_occupancy, amenities) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $_POST['room_number'],
            $_POST['room_type'],
            $_POST['description'],
            $_POST['price_per_night'],
            $_POST['max_occupancy'],
            $_POST['amenities']
        ]);
        $_SESSION['success'] = "Room added successfully!";
    } elseif (isset($_POST['update_room'])) {
        // Update room
        $stmt = $pdo->prepare("UPDATE rooms SET room_number=?, room_type=?, description=?, price_per_night=?, max_occupancy=?, amenities=? WHERE id=?");
        $stmt->execute([
            $_POST['room_number'],
            $_POST['room_type'],
            $_POST['description'],
            $_POST['price_per_night'],
            $_POST['max_occupancy'],
            $_POST['amenities'],
            $_POST['room_id']
        ]);
        $_SESSION['success'] = "Room updated successfully!";
    } elseif (isset($_GET['delete'])) {
        // Delete room
        $stmt = $pdo->prepare("DELETE FROM rooms WHERE id=?");
        $stmt->execute([$_GET['delete']]);
        $_SESSION['success'] = "Room deleted successfully!";
    }
    header("Location: rooms.php");
    exit();
}

// Get all rooms
$rooms = $pdo->query("SELECT * FROM rooms ORDER BY room_number")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Rooms - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Add this to your admin.css file */

        /* Rooms Table Styling */
        .card{
            padding:10px;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            font-size: 0.9em;
            box-shadow: 0 0 10px rgba(0,0,0,0.05);
        }

        .data-table th {
            background-color: #2c3e50;
            color: white;
            text-align: left;
            padding: 12px 15px;
            font-weight: 600;
        }

        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
            vertical-align: middle;
        }

        .data-table tr:hover {
            background-color: #f9f9f9;
        }

        /* Status Badges */
        .status-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: capitalize;
        }

        .status-available {
            background-color: #d4edda;
            color: #155724;
        }

        .status-booked {
            background-color: #f8d7da;
            color: #721c24;
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 8px;
        }

        .btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            font-size: 0.85em;
            cursor: pointer;
            transition: all 0.3s;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        .btn.small {
            padding: 5px 10px;
            font-size: 0.8em;
        }

        .btn-primary {
            background-color: #3498db;
            color: white;
        }

        .btn-primary:hover {
            background-color: #2980b9;
        }

        .btn-danger {
            background-color: #e74c3c;
            color: white;
        }

        .btn-danger:hover {
            background-color: #c0392b;
        }

        .btn i {
            font-size: 0.9em;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            .data-table {
                display: block;
                overflow-x: auto;
            }
            
            .actions {
                flex-direction: column;
                gap: 5px;
            }
            
            .btn.small {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <?php include 'admin_header.php'; ?>
    
    <div class="container">
        <h1>Manage Rooms</h1>
        
        <?php displayMessages(); ?>
        
        <div class="card">
            <h2>Add New Room</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" required>
                </div>
                <div class="form-group">
                    <label>Room Type</label>
                    <input type="text" name="room_type" required>
                </div>
                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label>Price Per Night ($)</label>
                    <input type="number" step="0.01" name="price_per_night" required>
                </div>
                <div class="form-group">
                    <label>Max Occupancy</label>
                    <input type="number" name="max_occupancy" required>
                </div>
                <div class="form-group">
                    <label>Amenities (comma separated)</label>
                    <input type="text" name="amenities" placeholder="e.g., WiFi, TV, AC">
                </div>
                <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
            </form>
        </div>
        
        <div class="card">
            <h2>All Rooms</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Room #</th>
                        <th>Type</th>
                        <th>Price/Night</th>
                        <th>Max Guests</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rooms as $room): ?>
                    <tr>
                        <td><?= htmlspecialchars($room['room_number']) ?></td>
                        <td><?= htmlspecialchars($room['room_type']) ?></td>
                        <td>$<?= number_format($room['price_per_night'], 2) ?></td>
                        <td><?= $room['max_occupancy'] ?></td>
                        <td>
                            <span class="status-badge <?= getRoomStatus($pdo, $room['id']) === 'Available' ? 'status-available' : 'status-booked' ?>">
                                <?= getRoomStatus($pdo, $room['id']) ?>
                            </span>
                        </td>
                        <td class="actions">
                            <a href="#" class="btn small btn-primary" onclick="editRoom(<?= $room['id'] ?>)">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="rooms.php?delete=<?= $room['id'] ?>" class="btn small btn-danger" onclick="return confirm('Are you sure?')">
                                <i class="fas fa-trash"></i> Delete
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Edit Room Modal -->
    <div id="editRoomModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit Room</h2>
            <form method="POST" id="editRoomForm">
                <input type="hidden" name="room_id" id="editRoomId">
                <!-- Form fields same as add form -->
                <button type="submit" name="update_room" class="btn btn-primary">Update Room</button>
            </form>
        </div>
    </div>

    <script src="../assets/js/admin.js"></script>
    <script>
    function editRoom(id) {
        // Fetch room data via AJAX and populate modal
        fetch(`get_room.php?id=${id}`)
            .then(response => response.json())
            .then(room => {
                document.getElementById('editRoomId').value = room.id;
                // Populate all other fields...
                document.getElementById('editRoomModal').style.display = 'block';
            });
    }
    </script>
</body>
</html>