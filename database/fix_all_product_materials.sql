-- ============================================
-- COMPREHENSIVE FIX: Link ALL Products to Materials
-- This script will automatically link products to their materials
-- based on the product's material column
-- ============================================

-- ============================================
-- STEP 1: CURTAINS - All Sizes
-- ============================================

-- Curtains 5ft (1.68 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    1.68
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%curtain%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%5%ft%' OR
    LOWER(TRIM(p.size)) LIKE '%5ft%' OR
    LOWER(TRIM(p.size)) = '5ft'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 1.68;

-- Curtains 6ft (2.04 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.04
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%curtain%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%6%ft%' OR
    LOWER(TRIM(p.size)) LIKE '%6ft%' OR
    LOWER(TRIM(p.size)) = '6ft'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 2.04;

-- Curtains 7ft (2.35 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.35
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%curtain%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%7%ft%' OR
    LOWER(TRIM(p.size)) LIKE '%7ft%' OR
    LOWER(TRIM(p.size)) = '7ft'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 2.35;

-- Curtains 8ft (2.68 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.68
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%curtain%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%8%ft%' OR
    LOWER(TRIM(p.size)) LIKE '%8ft%' OR
    LOWER(TRIM(p.size)) = '8ft'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 2.68;

-- ============================================
-- STEP 2: BEDSHEETS - All Sizes
-- ============================================

-- Bedsheet Single (2.18 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.18
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%bedsheet%'
  AND LOWER(TRIM(p.size)) LIKE '%single%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.18;

-- Bedsheet Double (2.27 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.27
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%bedsheet%'
  AND LOWER(TRIM(p.size)) LIKE '%double%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.27;

-- Bedsheet Family (2.36 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.36
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%bedsheet%'
  AND LOWER(TRIM(p.size)) LIKE '%family%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.36;

-- Bedsheet Queen (2.72 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.72
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%bedsheet%'
  AND LOWER(TRIM(p.size)) LIKE '%queen%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.72;

-- Bedsheet King (3.21 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    3.21
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%bedsheet%'
  AND LOWER(TRIM(p.size)) LIKE '%king%'
ON DUPLICATE KEY UPDATE quantity_needed = 3.21;

-- ============================================
-- STEP 3: SOFA MATS - All Sizes
-- ============================================

-- Sofa mat 20x60 (2.5 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    2.5
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%sofamatt%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%20%60%' OR
    LOWER(TRIM(p.size)) LIKE '%20x60%'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 2.5;

-- Sofa mat 24x72 (3.0 yards per unit)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id, 
    3.0
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE LOWER(p.product_name) LIKE '%sofamatt%'
  AND (
    LOWER(TRIM(p.size)) LIKE '%24%72%' OR
    LOWER(TRIM(p.size)) LIKE '%24x72%'
  )
ON DUPLICATE KEY UPDATE quantity_needed = 3.0;

-- ============================================
-- STEP 4: ADD CRUSHED FOAM WITH FIBER TO PILLOWS AND SOFA MATS
-- 266.67g per piece (8000g = 30 pieces)
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67  -- 266.67g per piece
FROM products p
CROSS JOIN materials m
WHERE (LOWER(p.product_name) LIKE '%pillow%' 
       OR LOWER(p.product_name) LIKE '%sofamatt%')
  AND LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
  AND NOT EXISTS (
    SELECT 1 
    FROM product_materials pm 
    WHERE pm.product_id = p.product_id 
      AND pm.material_id = m.material_id
  )
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- STEP 5: SMART FALLBACK
-- Link any remaining products to their material
-- Automatically determines quantity based on product category and size
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id, 
    m.material_id,
    CASE
        -- CURTAINS - Based on size
        WHEN LOWER(p.product_name) LIKE '%curtain%' THEN
            CASE
                WHEN LOWER(TRIM(p.size)) LIKE '%5%ft%' OR LOWER(TRIM(p.size)) = '5ft' THEN 1.68
                WHEN LOWER(TRIM(p.size)) LIKE '%6%ft%' OR LOWER(TRIM(p.size)) = '6ft' THEN 2.04
                WHEN LOWER(TRIM(p.size)) LIKE '%7%ft%' OR LOWER(TRIM(p.size)) = '7ft' THEN 2.35
                WHEN LOWER(TRIM(p.size)) LIKE '%8%ft%' OR LOWER(TRIM(p.size)) = '8ft' THEN 2.68
                ELSE 2.0  -- Default for unknown curtain sizes
            END
        
        -- BEDSHEETS - Based on size
        WHEN LOWER(p.product_name) LIKE '%bedsheet%' THEN
            CASE
                WHEN LOWER(TRIM(p.size)) LIKE '%single%' THEN 2.18
                WHEN LOWER(TRIM(p.size)) LIKE '%double%' THEN 2.27
                WHEN LOWER(TRIM(p.size)) LIKE '%family%' THEN 2.36
                WHEN LOWER(TRIM(p.size)) LIKE '%queen%' THEN 2.72
                WHEN LOWER(TRIM(p.size)) LIKE '%king%' THEN 3.21
                ELSE 2.27  -- Default to double size
            END
        
        -- SOFA MATS - Based on size
        WHEN LOWER(p.product_name) LIKE '%sofamatt%' THEN
            CASE
                WHEN LOWER(TRIM(p.size)) LIKE '%20%60%' OR LOWER(TRIM(p.size)) LIKE '%20x60%' THEN 2.5
                WHEN LOWER(TRIM(p.size)) LIKE '%24%72%' OR LOWER(TRIM(p.size)) LIKE '%24x72%' THEN 3.0
                ELSE 2.5  -- Default sofa mat size
            END
        
        -- TABLE RUNNERS
        WHEN LOWER(p.product_name) LIKE '%table%runner%' THEN 1.5
        
        -- PILLOW CASES
        WHEN LOWER(p.product_name) LIKE '%pillow%' THEN 0.5
        
        -- DEFAULT for unknown product types
        ELSE 2.0
    END as quantity_needed
FROM products p
JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL  -- Only products not yet linked
  AND p.material IS NOT NULL
  AND p.material != ''
ON DUPLICATE KEY UPDATE quantity_needed = VALUES(quantity_needed);

-- ============================================
-- VERIFICATION QUERIES
-- ============================================

-- Show products that are now linked
SELECT 
    '✅ LINKED PRODUCTS' as status,
    COUNT(DISTINCT p.product_id) as count
FROM products p
INNER JOIN product_materials pm ON p.product_id = pm.product_id;

-- Show products still missing links
SELECT 
    '❌ STILL MISSING LINKS' as status,
    COUNT(DISTINCT p.product_id) as count
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL;

-- Detailed view of remaining unlinked products
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material as product_material,
    p.stock,
    'NEEDS MANUAL LINK' as action_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL
ORDER BY p.product_name, p.size;

-- Show all products with their material links
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material as product_material,
    m.material_name as linked_material,
    pm.quantity_needed,
    CASE 
        WHEN pm.id IS NOT NULL THEN '✅ LINKED'
        ELSE '❌ NOT LINKED'
    END as status
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
ORDER BY 
    CASE WHEN pm.id IS NULL THEN 0 ELSE 1 END,
    p.product_name, 
    p.size;
