<?php
require_once 'db.php';
require_once 'functions.php';

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        throw new Exception('Invalid input data');
    }
    
    // Validate required fields
    if (empty($input['location_label']) || empty($input['items']) || !is_array($input['items'])) {
        throw new Exception('Missing required fields');
    }
    
    // Create the order
    $order_id = createOrder($pdo, $input['location_label'], $input['items'], $input['notes'] ?? '');
    
    // Return success response
    echo json_encode([
        'success' => true,
        'order_id' => $order_id
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>