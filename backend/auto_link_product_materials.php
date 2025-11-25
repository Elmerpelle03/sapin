<?php
/**
 * Auto-link products to materials based on products.material field
 * This will populate the product_materials table automatically
 */

require '../../config/db.php';
require '../../config/session_admin.php';

header('Content-Type: application/json');

try {
    $pdo->beginTransaction();
    
    // Get all products without material links
    $stmt = $pdo->query("
        SELECT 
            p.product_id,
            p.product_name,
            p.material,
            p.size
        FROM products p
        LEFT JOIN product_materials pm ON p.product_id = pm.product_id
        WHERE pm.product_id IS NULL
    ");
    
    $products_without_materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($products_without_materials)) {
        echo json_encode([
            'success' => true,
            'message' => 'All products already have material links!',
            'linked' => 0,
            'failed' => 0
        ]);
        exit;
    }
    
    // Get all available materials
    $materials_stmt = $pdo->query("SELECT material_id, material_name FROM materials");
    $materials = $materials_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Create a lookup array for materials
    $material_lookup = [];
    foreach ($materials as $mat) {
        $material_lookup[$mat['material_name']] = $mat['material_id'];
        // Also add lowercase version for case-insensitive matching
        $material_lookup[strtolower($mat['material_name'])] = $mat['material_id'];
    }
    
    $linked = 0;
    $failed = [];
    
    // Function to calculate quantity needed based on product type and size
    function calculateQuantityNeeded($product_name, $size, $material) {
        $product_name = strtolower($product_name);
        $size = strtolower($size);
        $material = strtolower($material);
        
        // BEDSHEETS (Canadian Cotton)
        if (stripos($product_name, 'bedsheet') !== false) {
            if (stripos($size, 'single') !== false) return 2.18;
            if (stripos($size, 'double') !== false) return 2.27;
            if (stripos($size, 'family') !== false) return 2.36;
            if (stripos($size, 'queen') !== false) return 2.72;
            if (stripos($size, 'king') !== false) return 3.21;
            return 2.18; // Default for bedsheets
        }
        
        // CURTAINS (Blockout/Katrina)
        if (stripos($product_name, 'curtain') !== false) {
            if (stripos($size, '7ft') !== false || stripos($size, '7 ft') !== false || stripos($size, '7') !== false) return 2.35;
            if (stripos($size, '6ft') !== false || stripos($size, '6 ft') !== false || stripos($size, '6') !== false) return 2.04;
            if (stripos($size, '5ft') !== false || stripos($size, '5 ft') !== false || stripos($size, '5') !== false) return 1.68;
            return 1.68; // Default for curtains
        }
        
        // PILLOWS (Crushed Foam Fiber)
        // 8 kilos (8000 grams) produces 20 pillows = 400 grams per pillow
        // NOTE: Only for PILLOW, not PILLOWCASE
        if (stripos($product_name, 'pillow') !== false && 
            stripos($product_name, 'pillowcase') === false && 
            stripos($product_name, 'pillow case') === false) {
            return 400; // 400 grams per pillow
        }
        
        return 1.68; // Default for unknown products
    }
    
    // Link each product to its material
    foreach ($products_without_materials as $product) {
        $product_material = trim($product['material']);
        $material_id = null;
        
        // Try exact match first
        if (isset($material_lookup[$product_material])) {
            $material_id = $material_lookup[$product_material];
        }
        // Try case-insensitive match
        elseif (isset($material_lookup[strtolower($product_material)])) {
            $material_id = $material_lookup[strtolower($product_material)];
        }
        // Try partial match
        else {
            foreach ($material_lookup as $mat_name => $mat_id) {
                if (stripos($mat_name, $product_material) !== false || 
                    stripos($product_material, $mat_name) !== false) {
                    $material_id = $mat_id;
                    break;
                }
            }
        }
        
        if ($material_id) {
            // Calculate the actual quantity needed based on product type and size
            $quantity_needed = calculateQuantityNeeded(
                $product['product_name'], 
                $product['size'] ?? '', 
                $product['material']
            );
            
            // Insert the link
            $insert_stmt = $pdo->prepare("
                INSERT INTO product_materials (product_id, material_id, quantity_needed)
                VALUES (:product_id, :material_id, :quantity_needed)
            ");
            
            $insert_stmt->execute([
                'product_id' => $product['product_id'],
                'material_id' => $material_id,
                'quantity_needed' => $quantity_needed
            ]);
            
            $linked++;
        } else {
            $failed[] = [
                'product_id' => $product['product_id'],
                'product_name' => $product['product_name'],
                'material' => $product_material,
                'reason' => 'No matching material found in materials table'
            ];
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Successfully linked {$linked} products to materials",
        'linked' => $linked,
        'failed' => count($failed),
        'failed_products' => $failed,
        'total_processed' => count($products_without_materials)
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
