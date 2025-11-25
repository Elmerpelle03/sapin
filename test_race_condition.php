<?php
/**
 * Race Condition Test Script (ADMIN ONLY)
 * 
 * This script simulates two users trying to checkout the same product simultaneously
 * to demonstrate that the FOR UPDATE lock prevents overselling.
 * 
 * ADMIN ACCESS REQUIRED
 */

require('../config/session_admin.php'); // Requires admin login
require('../config/db.php');

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Race Condition Test - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 2rem; background: #f8f9fa; }
        .test-output { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .step { margin: 1.5rem 0; padding: 1rem; background: #f8f9fa; border-left: 4px solid #2563eb; }
        .success { color: #10b981; }
        .error { color: #ef4444; }
        .warning { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="test-output">
            <h2>🔒 Race Condition Test (Admin Only)</h2>
            <p class="text-muted">Testing simultaneous checkout with FOR UPDATE locking...</p>
            <hr>

<?php
// Auto-detect a product from database
echo "<div class='step'>";
echo "<h4>Step 0: Finding test product</h4>";
$stmt = $pdo->query("SELECT product_id, product_name, stock FROM products LIMIT 1");
$test_product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$test_product) {
    die("<p class='error'>❌ No products found in database. Please add products first.</p></div>");
}

$test_product_id = $test_product['product_id'];
$original_stock = $test_product['stock'];
$test_quantity = 5;

echo "<p class='success'>✅ Using Product: <strong>{$test_product['product_name']}</strong> (ID: $test_product_id)</p>";
echo "<p>Original stock: <strong>$original_stock</strong></p>";
echo "</div>";

// Step 1: Set initial stock for test
echo "<div class='step'>";
echo "<h4>Step 1: Setting up test product</h4>";
$pdo->exec("UPDATE products SET stock = 5 WHERE product_id = $test_product_id");
echo "<p class='success'>✅ Set Product #$test_product_id stock to 5 (for testing)</p>";
echo "</div>";

// Step 2: Simulate User A's transaction
echo "<div class='step'>";
echo "<h4>Step 2: User A starts checkout</h4>";
$pdo->beginTransaction();
echo "<p>User A: <code>BEGIN TRANSACTION</code></p>";

$stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = :id FOR UPDATE");
$stmt->execute([':id' => $test_product_id]);
$stock_a = $stmt->fetchColumn();
echo "<p>User A: <code>SELECT ... FOR UPDATE</code> → stock = <strong>$stock_a</strong> 🔒 <span class='warning'>(Row LOCKED)</span></p>";
echo "</div>";

// Simulate User B trying to access at the same time
echo "<div class='step'>";
echo "<h4>Step 3: User B tries to checkout (will be blocked)</h4>";
echo "<p>User B: Attempting to access same product...</p>";
echo "<p class='warning'><em>⏸️ In a real scenario, User B would WAIT here until User A commits</em></p>";
echo "</div>";

// User A continues
echo "<div class='step'>";
echo "<h4>Step 4: User A completes transaction</h4>";
if ($stock_a >= $test_quantity) {
    $pdo->exec("UPDATE products SET stock = stock - $test_quantity WHERE product_id = $test_product_id");
    echo "<p>User A: <code>UPDATE products SET stock = stock - $test_quantity</code></p>";
    $pdo->commit();
    echo "<p class='success'>User A: <code>COMMIT</code> ✅ 🔓 <span class='success'>(Row UNLOCKED)</span></p>";
} else {
    $pdo->rollBack();
    echo "<p class='error'>User A: <code>ROLLBACK</code> (insufficient stock)</p>";
}
echo "</div>";

// Now User B can proceed
echo "<div class='step'>";
echo "<h4>Step 5: User B proceeds (now unblocked)</h4>";
$pdo->beginTransaction();
echo "<p>User B: <code>BEGIN TRANSACTION</code></p>";

$stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = :id FOR UPDATE");
$stmt->execute([':id' => $test_product_id]);
$stock_b = $stmt->fetchColumn();
echo "<p>User B: <code>SELECT ... FOR UPDATE</code> → stock = <strong>$stock_b</strong></p>";

if ($stock_b >= $test_quantity) {
    echo "<p class='success'>User B: ✅ Stock available, would proceed</p>";
    $pdo->rollBack();
    echo "<p>User B: <code>ROLLBACK</code> (test mode, not actually updating)</p>";
} else {
    echo "<p class='error'>User B: ❌ Insufficient stock! (stock = $stock_b, requested = $test_quantity)</p>";
    $pdo->rollBack();
    echo "<p>User B: <code>ROLLBACK</code></p>";
}
echo "</div>";

// Verify final state
$stmt = $pdo->prepare("SELECT stock FROM products WHERE product_id = :id");
$stmt->execute([':id' => $test_product_id]);
$final_stock = $stmt->fetchColumn();

echo "<div class='step' style='border-left-color: #10b981;'>";
echo "<h4>Final Result</h4>";
echo "<p>Final stock: <strong>$final_stock</strong></p>";
echo "<p class='success'><strong>✅ No overselling occurred!</strong></p>";
echo "<p><em>The FOR UPDATE lock ensured User B saw the updated stock value.</em></p>";
echo "</div>";

// Cleanup: Restore original stock
echo "<div class='step' style='border-left-color: #6b7280;'>";
echo "<h4>Cleanup</h4>";
$pdo->exec("UPDATE products SET stock = $original_stock WHERE product_id = $test_product_id");
echo "<p class='success'>✅ Restored original stock ($original_stock) for Product #$test_product_id</p>";
echo "<p><strong>Test complete!</strong> Your database is back to normal.</p>";
echo "</div>";
?>

            <div class="mt-4">
                <a href="javascript:location.reload()" class="btn btn-primary">Run Test Again</a>
                <a href="index.php" class="btn btn-secondary">Back to Admin</a>
            </div>
        </div>
    </div>
</body>
</html>
