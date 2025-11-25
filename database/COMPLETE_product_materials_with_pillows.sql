-- ============================================
-- COMPLETE PRODUCT-MATERIALS LINKING
-- With Pillows (Canadian Cotton + Crushed Foam)
-- ============================================

-- ============================================
-- BEDSHEETS (Canadian Cotton) - YARDS
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.18
FROM products p CROSS JOIN materials m
WHERE p.size LIKE '%single%' AND p.product_name LIKE '%bedsheet%'
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.18;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.27
FROM products p CROSS JOIN materials m
WHERE p.size LIKE '%double%' AND p.product_name LIKE '%bedsheet%'
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.27;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.36
FROM products p CROSS JOIN materials m
WHERE p.size LIKE '%family%' AND p.product_name LIKE '%bedsheet%'
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.36;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.72
FROM products p CROSS JOIN materials m
WHERE p.size LIKE '%queen%' AND p.product_name LIKE '%bedsheet%'
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.72;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 3.21
FROM products p CROSS JOIN materials m
WHERE p.size LIKE '%king%' AND p.product_name LIKE '%bedsheet%'
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 3.21;

-- ============================================
-- CURTAINS (Blockout/Blackout) - YARDS
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.35
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%')
  AND (p.material LIKE '%Blockout%' OR p.material LIKE '%Blackout%' OR p.material LIKE '%block%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%' OR m.material_name LIKE '%block%')
ON DUPLICATE KEY UPDATE quantity_needed = 2.35;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.04
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%')
  AND (p.material LIKE '%Blockout%' OR p.material LIKE '%Blackout%' OR p.material LIKE '%block%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%' OR m.material_name LIKE '%block%')
ON DUPLICATE KEY UPDATE quantity_needed = 2.04;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 1.68
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%')
  AND (p.material LIKE '%Blockout%' OR p.material LIKE '%Blackout%' OR p.material LIKE '%block%')
  AND (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%' OR m.material_name LIKE '%block%')
ON DUPLICATE KEY UPDATE quantity_needed = 1.68;

-- ============================================
-- CURTAINS (US Katrina) - YARDS
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.35
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%')
  AND p.material LIKE '%Katrina%' AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.35;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 2.04
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%')
  AND p.material LIKE '%Katrina%' AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 2.04;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 1.68
FROM products p CROSS JOIN materials m
WHERE p.product_name LIKE '%curtain%' 
  AND (p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%')
  AND p.material LIKE '%Katrina%' AND m.material_name LIKE '%Katrina%'
ON DUPLICATE KEY UPDATE quantity_needed = 1.68;

-- ============================================
-- PILLOWS - PART 1: Canadian Cotton (Cover)
-- Different sizes need different amounts of fabric
-- ============================================

-- Pillow Single/Standard: 0.5 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.5
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%single%' OR p.size LIKE '%standard%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.5;

-- Pillow Medium/Double: 0.6 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.6
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%medium%' OR p.size LIKE '%double%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.6;

-- Pillow Large/Queen: 0.7 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.7
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%large%' OR p.size LIKE '%queen%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.7;

-- Pillow Hotdog/King: 0.8 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.8
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%hotdog%' OR p.size LIKE '%king%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.8;

-- ============================================
-- PILLOWS - PART 2: Crushed Foam (Filling)
-- ALL pillows need 266.67 grams of foam
-- 8 kilos = 30 pillows = 266.67 grams per pillow
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 266.67
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (LOWER(m.material_name) LIKE '%crushed%foam%' OR LOWER(m.material_name) LIKE '%foam%fiber%')
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- VERIFICATION QUERY
-- ============================================

SELECT 
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN m.material_name LIKE '%foam%' THEN CONCAT(pm.quantity_needed, ' grams')
        ELSE CONCAT(pm.quantity_needed, ' yards')
    END as unit_display
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY 
    CASE 
        WHEN p.product_name LIKE '%bedsheet%' THEN 1
        WHEN p.product_name LIKE '%curtain%' THEN 2
        WHEN p.product_name LIKE '%pillow%' AND p.product_name NOT LIKE '%pillowcase%' THEN 3
        ELSE 4
    END,
    p.product_name,
    m.material_name;
