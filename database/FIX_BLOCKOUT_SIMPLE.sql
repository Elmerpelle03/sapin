-- SIMPLE FIX FOR BLOCKOUT PRODUCTS
-- Run each query ONE BY ONE in phpMyAdmin

-- Step 1: Find material IDs
SELECT material_id, material_name, stock FROM materials;
-- Look for:
-- - Blockout (or similar) - note the material_id
-- - US Katrina - note the material_id

-- Step 2: Check current wrong links
SELECT 
    pm.product_material_id,
    p.product_id,
    p.product_name,
    p.material AS product_says,
    pm.material_id AS current_material_id,
    m.material_name AS currently_linked_to
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.material LIKE '%blockout%';

-- Step 3: MANUAL FIX - Replace X with Blockout material_id, Y with Katrina material_id
-- Example: If Blockout is material_id 8 and Katrina is material_id 9:
-- UPDATE product_materials pm
-- JOIN products p ON pm.product_id = p.product_id
-- SET pm.material_id = 8
-- WHERE p.material LIKE '%blockout%'
--   AND pm.material_id = 9;

-- UNCOMMENT AND EDIT THE QUERY BELOW:
-- UPDATE product_materials pm
-- JOIN products p ON pm.product_id = p.product_id
-- SET pm.material_id = X  -- Replace X with your Blockout material_id
-- WHERE p.material LIKE '%blockout%'
--   AND pm.material_id = Y;  -- Replace Y with your Katrina material_id

-- Step 4: Verify after update
SELECT 
    p.product_name,
    p.material AS product_says,
    m.material_name AS now_linked_to,
    pm.quantity_needed
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.material LIKE '%blockout%'
LIMIT 20;
