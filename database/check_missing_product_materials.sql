-- ============================================
-- DIAGNOSTIC: Find Products Missing Material Links
-- Run this to see which products DON'T have materials defined
-- ============================================

-- ============================================
-- 1. PRODUCTS WITHOUT ANY MATERIAL LINKS
-- ============================================
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material as product_material_column,
    p.stock,
    'NO MATERIAL LINK' as issue
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL
  AND p.stock >= 0
ORDER BY p.product_name, p.size;

-- ============================================
-- 2. COUNT BY STATUS
-- ============================================
SELECT 
    'Products WITH material links' as status,
    COUNT(DISTINCT p.product_id) as count
FROM products p
INNER JOIN product_materials pm ON p.product_id = pm.product_id

UNION ALL

SELECT 
    'Products WITHOUT material links' as status,
    COUNT(DISTINCT p.product_id) as count
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL;

-- ============================================
-- 3. DETAILED VIEW - ALL PRODUCTS WITH LINK STATUS
-- ============================================
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material as product_material,
    p.stock,
    CASE 
        WHEN pm.id IS NOT NULL THEN 'LINKED'
        ELSE 'NOT LINKED'
    END as link_status,
    m.material_name as linked_to_material,
    pm.quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
ORDER BY 
    CASE WHEN pm.id IS NULL THEN 0 ELSE 1 END,
    p.product_name, 
    p.size;

-- ============================================
-- 4. AVAILABLE MATERIALS IN INVENTORY
-- ============================================
SELECT 
    material_id,
    material_name,
    stock,
    mu.unit
FROM materials m
LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
ORDER BY material_name;
