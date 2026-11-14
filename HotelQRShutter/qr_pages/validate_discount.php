<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = trim($input['code']);
    $nights = (int)$input['nights'];
    
    // Validate discount code
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = true");
    $stmt->execute([$code]);
    $discount = $stmt->fetch();
    
    if (!$discount) {
        echo json_encode(['valid' => false, 'message' => 'Invalid discount code']);
        exit;
    }
    
    // Check validity dates
    $now = time();
    if ($discount['valid_from'] && strtotime($discount['valid_from']) > $now) {
        echo json_encode(['valid' => false, 'message' => 'Discount code not yet valid']);
        exit;
    }
    
    if ($discount['valid_until'] && strtotime($discount['valid_until']) < $now) {
        echo json_encode(['valid' => false, 'message' => 'Discount code has expired']);
        exit;
    }
    
    // Check minimum stay
    if ($discount['min_stay'] > 0 && $nights < $discount['min_stay']) {
        echo json_encode(['valid' => false, 'message' => "Minimum stay of {$discount['min_stay']} nights required"]);
        exit;
    }
    
    // Check usage limits
    if ($discount['max_uses'] && $discount['current_uses'] >= $discount['max_uses']) {
        echo json_encode(['valid' => false, 'message' => 'Discount code has reached maximum usage']);
        exit;
    }
    
    // Calculate discount amount
    $discountAmount = 0;
    if ($discount['discount_type'] === 'percentage') {
        // This will be calculated based on room price
        $discountAmount = $discount['discount_value'];
    } else {
        $discountAmount = $discount['discount_value'];
    }
    
    echo json_encode([
        'valid' => true,
        'message' => 'Discount applied successfully!',
        'discount' => $discount,
        'discount_amount' => $discountAmount
    ]);
    exit;
}

echo json_encode(['valid' => false, 'message' => 'Invalid request']);