<?php
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = 'CLI';
require('config/db.local.php');

try {
    $stmt = $pdo->query('SHOW TABLES LIKE \'item_ratings\'');
    if ($stmt->rowCount() > 0) {
        echo "Table item_ratings exists.\n";
    } else {
        echo "Table item_ratings not found.\n";
    }

    // Check structure
    $stmt = $pdo->query('DESCRIBE item_ratings');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columns:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']}\n";
    }

    // Get an order_id for testing
    $stmt = $pdo->query('SELECT order_id, user_id FROM orders LIMIT 1');
    $order = $stmt->fetch();
    if ($order) {
        echo "Sample order_id: {$order['order_id']}, user_id: {$order['user_id']}\n";
    } else {
        echo "No orders found.\n";
    }

    // Check if ratings exist for this order
    if ($order) {
        $stmt = $pdo->prepare('SELECT COUNT(*) as count FROM item_ratings WHERE order_id = ?');
        $stmt->execute([$order['order_id']]);
        $count = $stmt->fetch()['count'];
        echo "Existing ratings for order {$order['order_id']}: $count\n";
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
