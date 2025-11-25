-- ============================================
-- ADD MATERIALS FOR PILLOWS
-- Pillows need TWO materials:
-- 1. Canadian Cotton (for the cover/case)
-- 2. Crushed Foam (for the filling)
-- ============================================

-- First, let's see current pillow products
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material,
    'Current Pillow Products' as info
FROM products p
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
ORDER BY p.product_name;

-- ============================================
-- STEP 1: Add Canadian Cotton to Pillows
-- Pillows need cotton for the outer cover
-- Using same conversion as pillowcases
-- ============================================

-- Pillow Single: Same as Pillowcase Single (estimate: 0.5 yards)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    0.5  -- Adjust this based on your actual pillow cover size
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%single%' OR p.size LIKE '%standard%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.5;

-- Pillow Medium/Double: Slightly larger cover
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    0.6
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%medium%' OR p.size LIKE '%double%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.6;

-- Pillow Large/Queen: Larger cover
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    0.7
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%large%' OR p.size LIKE '%queen%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.7;

-- Pillow Hotdog/King: Largest cover
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    0.8
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (p.size LIKE '%hotdog%' OR p.size LIKE '%king%')
  AND (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
ON DUPLICATE KEY UPDATE quantity_needed = 0.8;

-- ============================================
-- STEP 2: Add Crushed Foam to ALL Pillows
-- 8 kilos = 30 pillows = 266.67 grams per pillow
-- ============================================

INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67  -- 8000g / 30 pillows = 266.67g per pillow
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND (LOWER(m.material_name) LIKE '%crushed%foam%' OR LOWER(m.material_name) LIKE '%foam%fiber%')
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- ============================================
-- VERIFICATION - Show all pillow materials
-- ============================================

SELECT 
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN m.material_name LIKE '%foam%' THEN CONCAT(pm.quantity_needed, ' grams')
        WHEN m.material_name LIKE '%cotton%' THEN CONCAT(pm.quantity_needed, ' yards')
        ELSE CONCAT(pm.quantity_needed, ' units')
    END as unit_display,
    'Pillow Materials' as type
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
ORDER BY p.product_name, m.material_name;

-- ============================================
-- Expected Result for Each Pillow:
-- 1. Canadian Cotton: 0.5-0.8 yards (depending on size)
-- 2. Crushed Foam: 266.67 grams
-- ============================================
