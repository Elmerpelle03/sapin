-- ============================================
-- FIX: Add BOTH Blockout AND US Katrina materials to 3-in-1 Curtains
-- 3-in-1 curtains use both materials, so they need both linked
-- ============================================

-- Add Blockout material to all 3-in-1 curtains (7ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    2.35
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%')
ON DUPLICATE KEY UPDATE quantity_needed = 2.35;

-- Add US Katrina material to all 3-in-1 curtains (7ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    2.35
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%')
  AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.35;

-- Add Blockout material to all 3-in-1 curtains (6ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    2.04
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%')
ON DUPLICATE KEY UPDATE quantity_needed = 2.04;

-- Add US Katrina material to all 3-in-1 curtains (6ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    2.04
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%')
  AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.04;

-- Add Blockout material to all 3-in-1 curtains (5ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    1.68
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%')
ON DUPLICATE KEY UPDATE quantity_needed = 1.68;

-- Add US Katrina material to all 3-in-1 curtains (5ft)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    1.68
FROM products p
CROSS JOIN materials m
WHERE p.product_name LIKE '%3%in%1%curtain%' 
  AND (p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%')
  AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 1.68;

-- ============================================
-- VERIFICATION: Check 3-in-1 curtains now have both materials
-- ============================================
SELECT 
    p.product_name,
    p.size,
    GROUP_CONCAT(m.material_name ORDER BY m.material_name SEPARATOR ', ') as materials,
    COUNT(DISTINCT m.material_id) as material_count
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%3%in%1%curtain%'
GROUP BY p.product_id, p.product_name, p.size
ORDER BY p.product_name, p.size;
