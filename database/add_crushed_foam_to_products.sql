-- ============================================
-- ADD CRUSHED FOAM WITH FIBER TO PILLOWS AND SOFA MATS
-- 266.67g per piece (8000g = 30 pieces)
-- ============================================

-- First, verify the crushed foam material exists
SELECT 
    material_id,
    material_name,
    stock,
    'Crushed Foam Material' as info
FROM materials
WHERE LOWER(material_name) LIKE '%crushed%foam%fiber%';

-- If the above returns nothing, you need to add it first:
-- INSERT INTO materials (material_name, stock, materialunit_id) 
-- VALUES ('Crushed Foam with Fiber', 8000, [YOUR_UNIT_ID_FOR_GRAMS]);

-- ============================================
-- ADD CRUSHED FOAM TO ALL PILLOWS
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67  -- 266.67g per piece (8000g / 30 pieces)
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
  AND NOT EXISTS (
    -- Don't add if already linked
    SELECT 1 
    FROM product_materials pm 
    WHERE pm.product_id = p.product_id 
      AND pm.material_id = m.material_id
  )
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- ADD CRUSHED FOAM TO ALL SOFA MATS
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67  -- 266.67g per piece (8000g / 30 pieces)
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%sofamatt%'
  AND LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
  AND NOT EXISTS (
    -- Don't add if already linked
    SELECT 1 
    FROM product_materials pm 
    WHERE pm.product_id = p.product_id 
      AND pm.material_id = m.material_id
  )
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- VERIFICATION - Show products with crushed foam
-- ============================================

SELECT 
    p.product_id,
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CONCAT(pm.quantity_needed, 'g per piece') as consumption
FROM products p
JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
ORDER BY p.product_name;

-- ============================================
-- SHOW ALL MATERIALS FOR PILLOWS AND SOFA MATS
-- ============================================

SELECT 
    p.product_id,
    p.product_name,
    p.size,
    GROUP_CONCAT(
        CONCAT(m.material_name, ' (', pm.quantity_needed, ')')
        ORDER BY m.material_name
        SEPARATOR ', '
    ) as all_materials
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
   OR LOWER(p.product_name) LIKE '%sofamatt%'
GROUP BY p.product_id, p.product_name, p.size
ORDER BY p.product_name;

-- ============================================
-- CALCULATION VERIFICATION
-- ============================================

SELECT 
    '8000g crushed foam' as material,
    '30 pieces' as can_produce,
    '266.67g' as per_piece,
    '8000 / 30 = 266.67' as calculation;
