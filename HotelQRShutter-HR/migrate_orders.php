<?php
require_once 'includes/db.php';

// Get all orders with JSON items
$orders = $pdo->query("SELECT id, items FROM orders WHERE items IS NOT NULL")->fetchAll();

$pdo->beginTransaction();
$insertStmt = $pdo->prepare(
    "INSERT INTO order_items (order_id, service_id, price, quantity)
     VALUES (?, ?, ?, ?)"
);

$migrated = 0;
foreach ($orders as $order) {
    $items = json_decode($order['items'], true);
    
    foreach ($items as $item) {
        $insertStmt->execute([
            $order['id'],
            $item['id'], // service_id
            $item['price'],
            $item['quantity']
        ]);
    }
    $migrated++;
}

$pdo->commit();
echo "Migrated $migrated orders to order_items table\n";

// Optional: Remove JSON items after migration
// $pdo->exec("ALTER TABLE orders DROP COLUMN items");