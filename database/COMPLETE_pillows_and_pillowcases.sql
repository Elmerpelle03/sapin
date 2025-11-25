-- ============================================
-- COMPLETE PILLOW & PILLOWCASE MATERIALS
-- With different sizes and free case handling
-- ============================================

-- ============================================
-- PART 1: PILLOWS WITHOUT FREE CASE
-- ============================================

-- Pillow Single/Standard (NO free case) - 0.5 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.5
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND LOWER(p.product_name) NOT LIKE '%free%case%'
  AND LOWER(p.product_name) NOT LIKE '%with%case%'
  AND (p.size LIKE '%single%' OR p.size LIKE '%standard%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.5;

-- Pillow Medium/Double (NO free case) - 0.6 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.6
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND LOWER(p.product_name) NOT LIKE '%free%case%'
  AND LOWER(p.product_name) NOT LIKE '%with%case%'
  AND (p.size LIKE '%medium%' OR p.size LIKE '%double%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.6;

-- Pillow Large/Queen (NO free case) - 0.7 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.7
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND LOWER(p.product_name) NOT LIKE '%free%case%'
  AND LOWER(p.product_name) NOT LIKE '%with%case%'
  AND (p.size LIKE '%large%' OR p.size LIKE '%queen%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.7;

-- Pillow Hotdog/King (NO free case) - 0.8 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.8
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND LOWER(p.product_name) NOT LIKE '%free%case%'
  AND LOWER(p.product_name) NOT LIKE '%with%case%'
  AND (p.size LIKE '%hotdog%' OR p.size LIKE '%king%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.8;

-- ============================================
-- PART 2: PILLOWS WITH FREE CASE
-- Add extra 0.3 yards for the free pillowcase
-- ============================================

-- Pillow Single WITH free case - 0.8 yards cotton (0.5 + 0.3)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.8
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND (LOWER(p.product_name) LIKE '%free%case%' 
       OR LOWER(p.product_name) LIKE '%with%case%'
       OR LOWER(p.product_name) LIKE '%free%pillowcase%'
       OR LOWER(p.product_name) LIKE '%with%pillowcase%')
  AND (p.size LIKE '%single%' OR p.size LIKE '%standard%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.8;

-- Pillow Medium WITH free case - 0.9 yards cotton (0.6 + 0.3)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.9
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND (LOWER(p.product_name) LIKE '%free%case%' 
       OR LOWER(p.product_name) LIKE '%with%case%'
       OR LOWER(p.product_name) LIKE '%free%pillowcase%'
       OR LOWER(p.product_name) LIKE '%with%pillowcase%')
  AND (p.size LIKE '%medium%' OR p.size LIKE '%double%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.9;

-- Pillow Large WITH free case - 1.0 yards cotton (0.7 + 0.3)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 1.0
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND (LOWER(p.product_name) LIKE '%free%case%' 
       OR LOWER(p.product_name) LIKE '%with%case%'
       OR LOWER(p.product_name) LIKE '%free%pillowcase%'
       OR LOWER(p.product_name) LIKE '%with%pillowcase%')
  AND (p.size LIKE '%large%' OR p.size LIKE '%queen%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 1.0;

-- Pillow Hotdog WITH free case - 1.1 yards cotton (0.8 + 0.3)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 1.1
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND (LOWER(p.product_name) LIKE '%free%case%' 
       OR LOWER(p.product_name) LIKE '%with%case%'
       OR LOWER(p.product_name) LIKE '%free%pillowcase%'
       OR LOWER(p.product_name) LIKE '%with%pillowcase%')
  AND (p.size LIKE '%hotdog%' OR p.size LIKE '%king%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 1.1;

-- ============================================
-- PART 3: CRUSHED FOAM FOR ALL PILLOWS
-- Same amount regardless of size or free case
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 266.67
FROM products p CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND m.material_name = 'Crushed Foam with Fiber'
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- PART 4: PILLOWCASES (Only Canadian Cotton)
-- Different sizes need different amounts
-- ============================================

-- Pillowcase Single/Standard - 0.3 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.3
FROM products p CROSS JOIN materials m
WHERE (LOWER(p.product_name) LIKE '%pillowcase%' 
       OR LOWER(p.product_name) LIKE '%pillow case%')
  AND (p.size LIKE '%single%' OR p.size LIKE '%standard%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.3;

-- Pillowcase Medium/Double - 0.35 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.35
FROM products p CROSS JOIN materials m
WHERE (LOWER(p.product_name) LIKE '%pillowcase%' 
       OR LOWER(p.product_name) LIKE '%pillow case%')
  AND (p.size LIKE '%medium%' OR p.size LIKE '%double%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.35;

-- Pillowcase Large/Queen - 0.4 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.4
FROM products p CROSS JOIN materials m
WHERE (LOWER(p.product_name) LIKE '%pillowcase%' 
       OR LOWER(p.product_name) LIKE '%pillow case%')
  AND (p.size LIKE '%large%' OR p.size LIKE '%queen%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.4;

-- Pillowcase Hotdog/King - 0.45 yards cotton
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT p.product_id, m.material_id, 0.45
FROM products p CROSS JOIN materials m
WHERE (LOWER(p.product_name) LIKE '%pillowcase%' 
       OR LOWER(p.product_name) LIKE '%pillow case%')
  AND (p.size LIKE '%hotdog%' OR p.size LIKE '%king%')
  AND m.material_name LIKE '%Canadian%cotton%'
ON DUPLICATE KEY UPDATE quantity_needed = 0.45;

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN m.material_name LIKE '%foam%' THEN CONCAT(pm.quantity_needed, ' grams')
        ELSE CONCAT(pm.quantity_needed, ' yards')
    END as display,
    CASE 
        WHEN LOWER(p.product_name) LIKE '%pillowcase%' 
             OR LOWER(p.product_name) LIKE '%pillow case%' THEN 'Pillowcase Only'
        WHEN LOWER(p.product_name) LIKE '%free%case%' 
             OR LOWER(p.product_name) LIKE '%with%case%' THEN 'Pillow + Free Case'
        ELSE 'Pillow Only'
    END as type
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
ORDER BY 
    CASE 
        WHEN LOWER(p.product_name) LIKE '%pillowcase%' THEN 2
        ELSE 1
    END,
    p.product_name, 
    m.material_name;

-- ============================================
-- EXPECTED RESULTS SUMMARY
-- ============================================
-- 
-- PILLOWS (no free case):
--   Single: 0.5 yards cotton + 266.67g foam
--   Medium: 0.6 yards cotton + 266.67g foam
--   Large: 0.7 yards cotton + 266.67g foam
--   Hotdog: 0.8 yards cotton + 266.67g foam
--
-- PILLOWS (with free case):
--   Single: 0.8 yards cotton + 266.67g foam
--   Medium: 0.9 yards cotton + 266.67g foam
--   Large: 1.0 yards cotton + 266.67g foam
--   Hotdog: 1.1 yards cotton + 266.67g foam
--
-- PILLOWCASES (only):
--   Single: 0.3 yards cotton (no foam)
--   Medium: 0.35 yards cotton (no foam)
--   Large: 0.4 yards cotton (no foam)
--   Hotdog: 0.45 yards cotton (no foam)
-- ============================================
