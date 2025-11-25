-- ============================================
-- DEBUG: Why is Crushed Foam NOT Deducting?
-- Run this step by step
-- ============================================

-- STEP 1: Check if crushed foam material exists
SELECT 
    material_id,
    material_name,
    stock,
    '✅ Crushed Foam Material' as status
FROM materials
WHERE LOWER(material_name) LIKE '%foam%';
-- Expected: Should show "Crushed Foam with Fiber" with material_id = 12

-- STEP 2: Check if pillows exist (not pillowcases)
SELECT 
    product_id,
    product_name,
    size,
    stock,
    '✅ Pillow Products' as status
FROM products
WHERE LOWER(product_name) LIKE '%pillow%'
  AND LOWER(product_name) NOT LIKE '%pillowcase%'
  AND LOWER(product_name) NOT LIKE '%pillow case%'
ORDER BY product_name;
-- Expected: Should show your pillow products

-- STEP 3: Check if pillows are linked to crushed foam
SELECT 
    p.product_id,
    p.product_name,
    m.material_id,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN pm.id IS NULL THEN '❌ NOT LINKED'
        ELSE '✅ LINKED'
    END as link_status
FROM products p
CROSS JOIN materials m
LEFT JOIN product_materials pm ON p.product_id = pm.product_id AND m.material_id = pm.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND LOWER(m.material_name) LIKE '%foam%'
ORDER BY p.product_name;
-- Expected: Should show ✅ LINKED for all pillows
-- If it shows ❌ NOT LINKED, that's the problem!

-- STEP 4: Check ALL materials linked to ALL pillows
SELECT 
    p.product_id,
    p.product_name,
    m.material_name,
    pm.quantity_needed,
    m.stock as available_stock,
    CASE 
        WHEN m.material_name LIKE '%foam%' THEN '🔴 FOAM'
        WHEN m.material_name LIKE '%cotton%' THEN '🟢 COTTON'
        ELSE '⚪ OTHER'
    END as material_type
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
ORDER BY p.product_name, m.material_name;
-- Expected: Each pillow should show BOTH 🟢 COTTON and 🔴 FOAM
-- If you only see 🟢 COTTON, then foam is NOT linked!

-- STEP 5: If foam is NOT linked, run this to link it
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(p.product_name) NOT LIKE '%pillow case%'
  AND m.material_name = 'Crushed Foam with Fiber'
  AND NOT EXISTS (
    SELECT 1 FROM product_materials pm2
    WHERE pm2.product_id = p.product_id
      AND pm2.material_id = m.material_id
  );

-- STEP 6: Verify the link was created
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed,
    '✅ Successfully Linked' as status
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(p.product_name) NOT LIKE '%pillowcase%'
  AND LOWER(m.material_name) LIKE '%foam%'
ORDER BY p.product_name;
