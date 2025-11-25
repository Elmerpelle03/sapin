<?php
/**
 * Fix Blockout products linked to wrong material
 */
require '../config/db.php';
require '../config/session_admin.php';

header('Content-Type: application/json');

try {
    $pdo->beginTransaction();
    
    // Get Blockout material ID
    $blockout_stmt = $pdo->query("
        SELECT material_id, material_name 
        FROM materials 
        WHERE material_name LIKE '%Blockout%' OR material_name LIKE '%Blackout%' 
        LIMIT 1
    ");
    $blockout_material = $blockout_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$blockout_material) {
        echo json_encode([
            'success' => false,
            'message' => 'Blockout material not found in materials table'
        ]);
        exit;
    }
    
    $blockout_id = $blockout_material['material_id'];
    
    // Get Katrina material ID
    $katrina_stmt = $pdo->query("
        SELECT material_id, material_name 
        FROM materials 
        WHERE material_name LIKE '%Katrina%' 
        LIMIT 1
    ");
    $katrina_material = $katrina_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$katrina_material) {
        echo json_encode([
            'success' => false,
            'message' => 'US Katrina material not found in materials table'
        ]);
        exit;
    }
    
    $katrina_id = $katrina_material['material_id'];
    
    // Find products with Blockout in material field but linked to Katrina
    $wrong_links_stmt = $pdo->prepare("
        SELECT 
            pm.id,
            p.product_id,
            p.product_name,
            p.material
        FROM product_materials pm
        JOIN products p ON pm.product_id = p.product_id
        WHERE (p.material LIKE '%Blockout%' OR p.material LIKE '%Blackout%')
          AND pm.material_id = :katrina_id
    ");
    $wrong_links_stmt->execute(['katrina_id' => $katrina_id]);
    $wrong_links = $wrong_links_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $fixed_count = 0;
    
    // Update each wrong link
    foreach ($wrong_links as $link) {
        // First, check if this product already has a Blockout entry
        $check_stmt = $pdo->prepare("
            SELECT id FROM product_materials 
            WHERE product_id = :product_id 
            AND material_id = :blockout_id
        ");
        $check_stmt->execute([
            'product_id' => $link['product_id'],
            'blockout_id' => $blockout_id
        ]);
        $existing_blockout = $check_stmt->fetch();
        
        if ($existing_blockout) {
            // Product already has Blockout entry, just delete the Katrina one
            $delete_stmt = $pdo->prepare("
                DELETE FROM product_materials WHERE id = :pm_id
            ");
            $delete_stmt->execute(['pm_id' => $link['id']]);
        } else {
            // No Blockout entry exists, update Katrina to Blockout
            $update_stmt = $pdo->prepare("
                UPDATE product_materials 
                SET material_id = :blockout_id 
                WHERE id = :pm_id
            ");
            $update_stmt->execute([
                'blockout_id' => $blockout_id,
                'pm_id' => $link['id']
            ]);
        }
        $fixed_count++;
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => "Fixed {$fixed_count} Blockout products",
        'fixed_count' => $fixed_count,
        'blockout_material' => $blockout_material['material_name'],
        'blockout_id' => $blockout_id,
        'katrina_id' => $katrina_id,
        'fixed_products' => array_column($wrong_links, 'product_name')
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
