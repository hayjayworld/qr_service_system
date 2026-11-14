<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';

// Get and validate location from query parameter
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Check if QR code exists in database
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
</head>
<body>
    <div class="container">
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
        
        <div class="categories-container">
            <?php foreach ($categories as $category): ?>
                <div class="category-section">
                    <h2><?= htmlspecialchars($category['name']) ?></h2>
                    <p><?= htmlspecialchars($category['description']) ?></p>
                    
                    <div class="services-grid">
                        <?php 
                        $services = getServicesByCategory($pdo, $category['id']);
                        foreach ($services as $service): 
                        ?>
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
        </div>
    </div>
    
    <script src="../assets/js/qr_menu.js"></script>
</body>
</html>