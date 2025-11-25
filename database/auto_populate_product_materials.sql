-- Auto-populate product_materials table based on products.material field
-- This will link products to their materials automatically

-- Step 1: Check current state
SELECT 
    COUNT(*) AS total_products,
    COUNT(pm.product_id) AS products_with_materials,
    COUNT(*) - COUNT(pm.product_id) AS products_missing_materials
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id;

-- Step 2: See which products are missing material links
SELECT 
    p.product_id,
    p.product_name,
    p.material AS material_name_in_product
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.product_id IS NULL;

-- Step 3: Auto-insert product_materials entries with ACTUAL conversion rates
-- This matches products to materials based on size and material type

-- BEDSHEETS (Canadian Cotton)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    CASE 
        WHEN p.size LIKE '%single%' THEN 2.18
        WHEN p.size LIKE '%double%' THEN 2.27
        WHEN p.size LIKE '%family%' THEN 2.36
        WHEN p.size LIKE '%queen%' THEN 2.72
        WHEN p.size LIKE '%king%' THEN 3.21
        ELSE 2.18  -- Default for bedsheets
    END AS quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON (m.material_name LIKE '%Canadian%cotton%' OR m.material_name LIKE '%cotton%')
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%bedsheet%'
  AND (p.material LIKE '%cotton%' OR p.material LIKE '%Canadian%');

-- CURTAINS (Blockout/Blackout)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    CASE 
        WHEN p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%' THEN 2.35
        WHEN p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%' THEN 2.04
        WHEN p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%' THEN 1.68
        ELSE 1.68  -- Default for curtains
    END AS quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON (m.material_name LIKE '%Blockout%' OR m.material_name LIKE '%Blackout%' OR m.material_name LIKE '%block%')
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%curtain%'
  AND (p.material LIKE '%Blockout%' OR p.material LIKE '%Blackout%' OR p.material LIKE '%block%');

-- CURTAINS (US Katrina)
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    CASE 
        WHEN p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%' THEN 2.35
        WHEN p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%' THEN 2.04
        WHEN p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%' THEN 1.68
        ELSE 1.68  -- Default for curtains
    END AS quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON m.material_name LIKE '%Katrina%'
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%curtain%'
  AND p.material LIKE '%Katrina%';

-- PILLOWS (Crushed Foam Fiber)
-- 8 kilos (8000 grams) produces 20 pillows = 400 grams per pillow
-- NOTE: Only for PILLOW, not PILLOWCASE
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    400 AS quantity_needed  -- 400 grams per pillow
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON (m.material_name LIKE '%crushed%foam%' OR m.material_name LIKE '%foam%fiber%')
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%pillow%'
  AND p.product_name NOT LIKE '%pillowcase%'  -- Exclude pillowcases
  AND p.product_name NOT LIKE '%pillow case%'  -- Exclude pillow case (with space)
  AND (p.material LIKE '%crushed%' OR p.material LIKE '%foam%');

-- Step 4: Verify the results
SELECT 
    p.product_id,
    p.product_name,
    p.material AS product_material_field,
    m.material_name AS linked_material,
    pm.quantity_needed
FROM products p
JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY p.product_name;

-- Step 5: Check if any products still don't have materials
-- (This happens if products.material doesn't match any materials.material_name)
SELECT 
    p.product_id,
    p.product_name,
    p.material AS material_field,
    'No matching material found' AS issue
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.product_id IS NULL;
