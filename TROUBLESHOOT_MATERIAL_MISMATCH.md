# Troubleshooting: Material Mismatch Error

## The Problem

You're getting an error:
```
"Insufficient materials to produce 5 more units: US Katrina (need 8.4, have 2.80)"
```

But you're trying to update a product that should use "Blockout" material, not "US Katrina".

## Why This Happens

There are two separate fields for materials in your system:

### 1. **`products.material`** (Display Field)
- This is what shows on the product card
- This is just text for display purposes
- Example: "Blockout", "Cotton", "Polyester"

### 2. **`product_materials` Table** (Actual Requirements)
- This defines what materials are ACTUALLY needed to produce the product
- This is what the system checks when adding stock
- Links to the `materials` table

**The mismatch:** The product's display field says "Blockout", but the `product_materials` table says it needs "US Katrina".

## How to Fix

### Option 1: Check Product-Material Mapping

Run this SQL query to see what materials the product actually requires:

```sql
-- Find the product (look for the one in the error)
SELECT product_id, product_name, material 
FROM products 
WHERE product_name LIKE '%Katrina%';

-- Check its material requirements
SELECT 
    p.product_id,
    p.product_name,
    p.material AS display_material,
    m.material_name AS actual_required_material,
    pm.quantity_needed,
    m.stock AS available_stock
FROM products p
JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%Katrina%';
```

### Option 2: Update Product-Material Relationship

If the product should use "Blockout" instead of "US Katrina":

```sql
-- 1. Find the material IDs
SELECT material_id, material_name, stock FROM materials;

-- 2. Find the product ID
SELECT product_id, product_name FROM products WHERE product_name LIKE '%Katrina%';

-- 3. Update the product_materials table
-- Replace XXX with product_id, YYY with correct material_id
UPDATE product_materials 
SET material_id = YYY  -- Blockout material ID
WHERE product_id = XXX;  -- Product ID

-- OR delete and re-insert
DELETE FROM product_materials WHERE product_id = XXX;

INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (XXX, YYY, 1.68);  -- Adjust quantity_needed as needed
```

### Option 3: Add More "US Katrina" Material

If the product SHOULD use "US Katrina", then add more stock to that material:

```sql
-- Check current stock
SELECT material_id, material_name, stock 
FROM materials 
WHERE material_name = 'US Katrina';

-- Add more stock (e.g., add 100 meters)
UPDATE materials 
SET stock = stock + 100 
WHERE material_name = 'US Katrina';
```

## Understanding the Error

```
"Insufficient materials to produce 5 more units: US Katrina (need 8.4, have 2.80)"
```

Breaking it down:
- **5 more units**: You're trying to add 5 products (maybe clicked +5 or custom amount)
- **US Katrina**: The material the product requires (from `product_materials` table)
- **need 8.4**: Total material needed = quantity_needed × 5 units
  - So quantity_needed per unit = 8.4 ÷ 5 = 1.68 meters
- **have 2.80**: Current stock of US Katrina in materials table

## Common Scenarios

### Scenario 1: Wrong Material Linked
```
Product Display: "Blockout Curtain"
products.material field: "Blockout"
product_materials table: Links to "US Katrina" ❌

Fix: Update product_materials to link to "Blockout" material
```

### Scenario 2: Material Name Mismatch
```
Product uses: "Blockout"
Materials table has: "Block Out" (with space) ❌

Fix: Standardize material names
```

### Scenario 3: Missing Material Link
```
Product exists
product_materials table: No entry for this product ❌

Fix: Add entry to product_materials table
```

## Steps to Diagnose

### 1. Identify the Product
Look at the product card in the image - what's the product name?

### 2. Check Database
```sql
-- Get product details
SELECT * FROM products WHERE product_name = 'EXACT_PRODUCT_NAME';

-- Check what materials it requires
SELECT 
    pm.*,
    m.material_name,
    m.stock
FROM product_materials pm
JOIN materials m ON pm.material_id = m.material_id
WHERE pm.product_id = YOUR_PRODUCT_ID;
```

### 3. Verify Material Inventory
```sql
-- Check US Katrina stock
SELECT * FROM materials WHERE material_name = 'US Katrina';

-- Check Blockout stock
SELECT * FROM materials WHERE material_name LIKE '%Blockout%';
```

## Quick Fix Options

### If Product Should Use Blockout:
1. Go to Material Inventory
2. Find "Blockout" material ID
3. Update `product_materials` table to use that ID

### If Product Should Use US Katrina:
1. Go to Material Inventory
2. Add more stock to "US Katrina"
3. Try adding product stock again

### If Unsure:
1. Check what other similar products use
2. Ask: "What material is this product actually made from?"
3. Update accordingly

## Prevention

To avoid this in the future:

### When Adding New Products:
1. Set the `products.material` field (display)
2. **Also** add entry to `product_materials` table (actual requirements)
3. Make sure material names match exactly

### When Editing Products:
1. If changing material, update BOTH:
   - `products.material` field
   - `product_materials` table entry

## SQL Helper Queries

### Find All Products with Material Mismatches:
```sql
SELECT 
    p.product_id,
    p.product_name,
    p.material AS display_material,
    GROUP_CONCAT(m.material_name) AS actual_materials
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
GROUP BY p.product_id
HAVING display_material NOT IN (actual_materials);
```

### Find Products Without Material Links:
```sql
SELECT 
    p.product_id,
    p.product_name,
    p.material
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.product_id IS NULL;
```

### Find All Material Requirements:
```sql
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed,
    m.stock,
    FLOOR(m.stock / pm.quantity_needed) AS max_producible
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY p.product_name;
```

## Contact Points

If you need to fix this:
1. **Check the product name** from the error
2. **Run the diagnostic queries** above
3. **Update the product_materials table** to link to correct material
4. **Or add more stock** to the required material

---

**The key issue:** The `product_materials` table determines what materials are actually needed, not the `products.material` display field. Make sure they match!
