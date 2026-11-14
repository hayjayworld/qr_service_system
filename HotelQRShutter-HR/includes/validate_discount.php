<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $code = trim($input['code']);
    $nights = (int)$input['nights'];
    $roomId = (int)$input['room_id'];
    
    // Get room price
    $roomPrice = getRoomPrice($pdo, $roomId);
    $originalPrice = $roomPrice * $nights;
    
    // Validate discount code
    $stmt = $pdo->prepare("SELECT * FROM discounts WHERE code = ? AND is_active = true");
    $stmt->execute([$code]);
    $discount = $stmt->fetch();
    
    if (!$discount) {
        echo json_encode([
            'valid' => false, 
            'message' => 'Invalid discount code',
            'original_price' => $originalPrice
        ]);
        exit;
    }
    
    // Check validity dates
    $now = time();
    if ($discount['valid_from'] && strtotime($discount['valid_from']) > $now) {
        echo json_encode([
            'valid' => false, 
            'message' => 'Discount code not yet valid',
            'original_price' => $originalPrice
        ]);
        exit;
    }
    
    if ($discount['valid_until'] && strtotime($discount['valid_until']) < $now) {
        echo json_encode([
            'valid' => false, 
            'message' => 'Discount code has expired',
            'original_price' => $originalPrice
        ]);
        exit;
    }
    
    // Check minimum stay
    if ($discount['min_stay'] > 0 && $nights < $discount['min_stay']) {
        echo json_encode([
            'valid' => false, 
            'message' => "Minimum stay of {$discount['min_stay']} nights required",
            'original_price' => $originalPrice
        ]);
        exit;
    }
    
    // Check usage limits
    if ($discount['max_uses'] && $discount['current_uses'] >= $discount['max_uses']) {
        echo json_encode([
            'valid' => false, 
            'message' => 'Discount code has reached maximum usage',
            'original_price' => $originalPrice
        ]);
        exit;
    }
    
    // Calculate discount amount
    if($discount){
        if ($discount['discount_type'] === 'percentage') {
            $discountAmount = ($originalPrice * $discount['discount_value']) / 100;
        } else {
            $discountAmount = min($discount['discount_value'], $originalPrice);
        }
        
        $finalPrice = $originalPrice - $discountAmount;
    }else {
        $discountAmount = 0;
        $finalPrice = $originalPrice;
    }
    
    
    echo json_encode([
        'valid' => true,
        'message' => 'Discount applied successfully!',
        'discount_amount' => $discountAmount,
        'final_price' => $finalPrice,
        'original_price' => $originalPrice,
        'discount' => $discount
    ]);
    exit;
}

echo json_encode(['valid' => false, 'message' => 'Invalid request']);