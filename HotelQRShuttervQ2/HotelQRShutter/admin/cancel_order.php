<?php
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/functions.php';

requireAuth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $order_id = $_POST['order_id'] ?? null;
    if (!$order_id) {
        throw new Exception('Order ID is required');
    }

    // Soft delete (recommended) or hard delete:
    $sql = "UPDATE orders SET status = 'cancelled', cancelled_at = NOW() WHERE id = ?";
    executeQuery($pdo, $sql, [$order_id]);

    echo json_encode(['success' => true]);
    header('Location: orders.php');
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>