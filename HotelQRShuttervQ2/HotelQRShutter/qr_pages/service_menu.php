<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Get location from query parameter
if (!empty($location)) {
    $sql = "SELECT id FROM qr_codes WHERE label = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$location]);
    
    if ($stmt->rowCount() === 0) {
        // QR code doesn't exist or was deleted
        header('HTTP/1.0 403 Forbidden');
        die('Invalid QR code. This code is no longer valid.');
    }
} else {
    // No location provided
    header('Location: err.html'); // Redirect to home or show error
    exit();
}

// Handle complaint submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_complaint'])) {
    $complaint_text = trim($_POST['complaint_text']);
    $status = 'unread';
    
    if (!empty($complaint_text)) {
        $sql = "INSERT INTO complaints (location, complaint_text, status, created_at) 
                VALUES (?, ?, ?, NOW())";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$location, $complaint_text, $status]);
        
        // Show success message
        echo "<script>alert('Thank you for your feedback. We will address your complaint shortly.');</script>";
    }
}


// Get search term if submitted
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// Get all categories with their services
$categories = getAllCategories($pdo);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotel Services Menu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .header-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
            margin-bottom: 20px;
        }
        .logo-section {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .logo {
            height: 50px;
            width: auto;
        }
        .slogan {
            font-style: italic;
            color: #666;
        }
        .complaint-btn {
            padding: 10px 20px;
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }
        .complaint-btn:hover {
            background: #c0392b;
        }
        .complaint-modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            justify-content: center;
            align-items: center;
        }
        .complaint-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            width: 90%;
            max-width: 500px;
        }
        .complaint-form h2 {
            margin-top: 0;
            color: #e74c3c;
        }
        .complaint-form textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            min-height: 150px;
            margin-bottom: 15px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }
        .search-container {
            margin: 20px 0;
            padding: 15px;
            background: #f5f5f5;
            border-radius: 8px;
        }
        .search-form {
            display: flex;
            gap: 10px;
        }
        .search-input {
            flex: 1;
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        .search-button {
            padding: 10px 20px;
            background: #2c3e50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .search-button:hover {
            background: #1a252f;
        }
        .no-results {
            text-align: center;
            padding: 20px;
            font-style: italic;
            color: #666;
        }
    </style>
</head>
<body>   
    <div class="container">
        <div class="header-container">
            <div class="logo-section">
                <img src="../assets/images/logo.png" alt="Hotel Logo" class="logo">
                <div class="slogan">Your Comfort, Our Priority</div>
            </div>
            <a href="service_reservation.php?location=<?= urlencode($location) ?>" class="btn">Book a Room</a>
            <button class="complaint-btn" id="complaintBtn">
                <i class="fas fa-exclamation-circle"></i> Submit Complaint
            </button>
        </div>

        <!-- Complaint Modal -->
        <div id="complaintModal" class="complaint-modal">
            <div class="complaint-form">
                <h2>Submit Your Complaint</h2>
                <form method="POST" action="">
                    <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
                    <textarea name="complaint_text" placeholder="Please describe your issue or concern..." required></textarea>
                    <div class="form-actions">
                        <button type="button" id="cancelComplaint" class="btn">Cancel</button>
                        <button type="submit" name="submit_complaint" class="btn complaint-btn">Submit</button>
                    </div>
                </form>
            </div>
        </div>
        <header>
            <h1>Hotel Services Menu</h1>
            <?php if ($location): ?>
                <div class="location-badge">Location: <?= htmlspecialchars($location) ?></div>
            <?php endif; ?>
        </header>
		
        <div class="cart-container">
            <h2>Your Order</h2>
            <div id="cart-items"></div>
            <div id="cart-total">Total: $0.00</div>
            <textarea id="order-notes" placeholder="Special requests..."></textarea>
            <button id="submit-order" class="btn">Submit Order</button>
        </div>
		
        <!-- Search Form -->
        <div class="search-container">
            <form method="GET" class="search-form">
                <input type="hidden" name="location" value="<?= htmlspecialchars($location) ?>">
                <input type="text" name="search" class="search-input" placeholder="Search for services..." 
                       value="<?= htmlspecialchars($searchTerm) ?>">
                <button type="submit" class="search-button">
                    <i class="fas fa-search"></i> Search
                </button>
            </form>
        </div>
        
        
        <div class="categories-container">
            <?php 
            $hasResults = false;
            foreach ($categories as $category): 
                $services = getServicesByCategory($pdo, $category['id']);
                
                // Filter services if search term exists
                if ($searchTerm) {
                    $services = array_filter($services, function($service) use ($searchTerm) {
                        return stripos($service['name'], $searchTerm) !== false || 
                               stripos($service['description'], $searchTerm) !== false;
                    });
                    
                    // Skip category if no services match search
                    if (empty($services)) continue;
                }
                
                $hasResults = true;
            ?>
                <div class="category-section">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                    <p><?= htmlspecialchars($category['description']) ?></p>
                    
                    <div class="services-grid">
                        <?php foreach ($services as $service): ?>
                            <div class="service-card" data-service-id="<?= $service['id'] ?>">
                                <h3><?= htmlspecialchars($service['name']) ?></h3>
                                <p><?= htmlspecialchars($service['description']) ?></p>
                                <div class="price">$<?= number_format($service['price'], 2) ?></div>
                                <button class="btn add-to-cart">Add to Order</button>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            
            <?php if (!$hasResults): ?>
                <div class="no-results">
                    <p>No services found matching "<?= htmlspecialchars($searchTerm) ?>"</p>
                    <a href="?location=<?= urlencode($location) ?>" class="btn">Clear Search</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <script>
        // Complaint Modal Handling
        const complaintBtn = document.getElementById('complaintBtn');
        const complaintModal = document.getElementById('complaintModal');
        const cancelComplaint = document.getElementById('cancelComplaint');
        
        complaintBtn.addEventListener('click', () => {
            complaintModal.style.display = 'flex';
        });
        
        cancelComplaint.addEventListener('click', () => {
            complaintModal.style.display = 'none';
        });
        
        // Close modal when clicking outside the form
        window.addEventListener('click', (event) => {
            if (event.target === complaintModal) {
                complaintModal.style.display = 'none';
            }
        });
    </script>
    <script src="../assets/js/qr_menu.js"></script>
</body>
</html>