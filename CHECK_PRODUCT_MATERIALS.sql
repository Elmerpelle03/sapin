-- Check which materials a product requires
-- Replace 'PRODUCT_NAME' with the actual product name from the error

-- 1. Find the product ID
SELECT product_id, product_name, material 
FROM products 
WHERE product_name LIKE '%Katrina%' OR product_name LIKE '%Blockout%';

-- 2. Check what materials this product requires
SELECT 
    p.product_id,
    p.product_name,
    p.material AS product_material_field,
    m.material_name AS required_material,
    pm.quantity_needed,
    m.stock AS available_stock
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%Katrina%' OR p.product_name LIKE '%Blockout%';

-- 3. Check all materials in inventory
SELECT material_id, material_name, stock 
FROM materials 
WHERE material_name LIKE '%Katrina%' OR material_name LIKE '%Blockout%';

-- 4. If you want to see ALL product-material relationships:
SELECT 
    p.product_id,
    p.product_name,
    p.material AS product_material_field,
    m.material_name AS required_material_from_table,
    pm.quantity_needed,
    m.stock
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
ORDER BY p.product_name;
