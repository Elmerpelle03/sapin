-- ============================================
-- DEBUG: Check Pillow Materials Setup
-- ============================================

-- Step 1: Check what pillow products exist
SELECT 
    product_id,
    product_name,
    size,
    material,
    'Pillow Products' as info
FROM products
WHERE LOWER(product_name) LIKE '%pillow%'
  AND LOWER(product_name) NOT LIKE '%pillowcase%'
  AND LOWER(product_name) NOT LIKE '%pillow case%'
ORDER BY product_name;

-- Step 2: Check what crushed foam materials exist
SELECT 
    material_id,
    material_name,
    stock,
    'Crushed Foam Materials' as info
FROM materials
WHERE LOWER(material_name) LIKE '%crushed%foam%' 
   OR LOWER(material_name) LIKE '%foam%fiber%'
   OR LOWER(material_name) LIKE '%foam%';

-- Step 3: Check current pillow material links
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    m.material_id,
    m.material_name,
    pm.quantity_needed,
    'Current Links' as info
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
ORDER BY p.product_name, m.material_name;

-- Step 4: Check if any pillows are missing crushed foam link
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    'Missing Crushed Foam Link' as issue
FROM products p
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND NOT EXISTS (
    SELECT 1 
    FROM product_materials pm
    JOIN materials m ON pm.material_id = m.material_id
    WHERE pm.product_id = p.product_id
      AND (LOWER(m.material_name) LIKE '%crushed%foam%' 
           OR LOWER(m.material_name) LIKE '%foam%fiber%'
           OR LOWER(m.material_name) LIKE '%foam%')
  );
