<?php
require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'] ?? null;
    $action = $_POST['action'] ?? null;
    $amount = $_POST['amount'] ?? 0;

    if (!$product_id || !$action) {
        echo json_encode(['success' => false, 'message' => 'Missing parameters']);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        // Get current stock
        $stmt = $pdo->prepare("SELECT stock, product_name FROM products WHERE product_id = :product_id FOR UPDATE");
        $stmt->execute([':product_id' => $product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Product not found']);
            exit;
        }

        $current_stock = $product['stock'];
        $new_stock = $current_stock;

        // Calculate new stock based on action
        switch ($action) {
            case 'add':
                $new_stock = $current_stock + intval($amount);
                break;
            case 'subtract':
                $new_stock = max(0, $current_stock - intval($amount));
                break;
            case 'set':
                $new_stock = max(0, intval($amount));
                break;
            default:
                $pdo->rollBack();
                echo json_encode(['success' => false, 'message' => 'Invalid action']);
                exit;
        }

        $stock_difference = $new_stock - $current_stock;
        
        // If stock is increasing, check and deduct materials
        if ($stock_difference > 0) {
            $material_stmt = $pdo->prepare("
                SELECT pm.material_id, pm.quantity_needed, m.stock, m.material_name
                FROM product_materials pm
                JOIN materials m ON pm.material_id = m.material_id
                WHERE pm.product_id = :product_id
                FOR UPDATE
            ");
            $material_stmt->execute(['product_id' => $product_id]);
            $materials = $material_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // DEBUG: Log what materials were found
            error_log("Product ID: {$product_id}, Materials found: " . count($materials));
            error_log("Materials: " . json_encode($materials));
            
            $insufficient_materials = [];
            $max_producible = PHP_INT_MAX; // Start with unlimited
            
            foreach ($materials as $mat) {
                $total_needed = $mat['quantity_needed'] * $stock_difference;
                
                // Calculate max units this material can produce
                $units_possible = floor($mat['stock'] / $mat['quantity_needed']);
                $max_producible = min($max_producible, $units_possible);
                
                if ($mat['stock'] < $total_needed) {
                    $shortage = $total_needed - $mat['stock'];
                    $insufficient_materials[] = [
                        'name' => $mat['material_name'],
                        'needed' => $total_needed,
                        'available' => $mat['stock'],
                        'shortage' => $shortage,
                        'max_units' => $units_possible
                    ];
                }
            }
            
            if (!empty($insufficient_materials)) {
                $pdo->rollBack();
                
                // Store insufficient materials in session for restock modal
                $_SESSION['insufficient_materials'] = $insufficient_materials;
                $_SESSION['trigger_restock_modal'] = true;
                $_SESSION['stock_update_context'] = [
                    'product_id' => $product_id,
                    'product_name' => $product['product_name'],
                    'requested_units' => $stock_difference
                ];
                
                // Build detailed error message
                $error_details = [];
                foreach ($insufficient_materials as $mat) {
                    $error_details[] = "{$mat['name']}: need {$mat['needed']}, have {$mat['available']} (short by {$mat['shortage']})";
                }
                
                echo json_encode([
                    'success' => false, 
                    'message' => "Cannot produce {$stock_difference} units. Insufficient materials.",
                    'detailed_message' => implode(' | ', $error_details),
                    'requested_units' => $stock_difference,
                    'max_producible' => $max_producible,
                    'insufficient_materials' => $insufficient_materials,
                    'product_name' => $product['product_name']
                ]);
                exit;
            }
            
            // Deduct materials
            foreach ($materials as $mat) {
                $total_needed = $mat['quantity_needed'] * $stock_difference;
                
                // DEBUG: Log deduction
                error_log("Deducting {$total_needed} of {$mat['material_name']} (ID: {$mat['material_id']})");
                
                $update_mat_stmt = $pdo->prepare("
                    UPDATE materials 
                    SET stock = stock - :quantity 
                    WHERE material_id = :material_id
                ");
                $update_mat_stmt->execute([
                    'quantity' => $total_needed,
                    'material_id' => $mat['material_id']
                ]);
                
                // DEBUG: Check if update worked
                error_log("Rows affected: " . $update_mat_stmt->rowCount());
                
                // Log the usage
                $log_stmt = $pdo->prepare("
                    INSERT INTO material_usage_log (product_id, material_id, quantity_used, product_quantity_produced, action_type, created_by, notes)
                    VALUES (:product_id, :material_id, :quantity_used, :product_quantity, 'production', :user_id, :notes)
                ");
                $log_stmt->execute([
                    'product_id' => $product_id,
                    'material_id' => $mat['material_id'],
                    'quantity_used' => $total_needed,
                    'product_quantity' => $stock_difference,
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'notes' => "Quick stock update: {$current_stock} → {$new_stock}"
                ]);
            }
        }

        // Update stock
        $update_stmt = $pdo->prepare("UPDATE products SET stock = :stock WHERE product_id = :product_id");
        $update_stmt->execute([
            ':stock' => $new_stock,
            ':product_id' => $product_id
        ]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Stock updated successfully',
            'product_name' => $product['product_name'],
            'old_stock' => $current_stock,
            'new_stock' => $new_stock,
            'materials_deducted' => $stock_difference > 0
        ]);

    } catch (PDOException $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
?>
