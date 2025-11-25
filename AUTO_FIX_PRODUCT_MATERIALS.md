# Auto-Fix Product Materials Tool

## Problem
Your `product_materials` table is missing entries, so products can't deduct materials when stock is added. Instead of manually adding hundreds of products, use this automated tool!

## Solution: Automated Linking

### How It Works:
1. Finds all products without material links
2. Matches `products.material` field to `materials.material_name`
3. Automatically creates entries in `product_materials` table
4. Sets default quantity_needed to 1.68 per unit

## How to Use

### Option 1: Use the Web Interface (Easiest)

1. **Go to the fix page:**
   ```
   http://localhost/sapinbedsheets-main/admin/fix_product_materials.php
   ```

2. **Click "Check Status"** to see:
   - Total products
   - Products with materials
   - Products missing materials
   - List of affected products

3. **Click "Auto-Fix Links"** to:
   - Automatically link all products
   - See results immediately
   - View any failed products

### Option 2: Run SQL Script

1. **Open phpMyAdmin**
2. **Select your database**
3. **Go to SQL tab**
4. **Run this query:**

```sql
-- Auto-populate product_materials table
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    1.68 AS default_quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON p.material = m.material_name
WHERE pm.product_id IS NULL;
```

5. **Check results:**
```sql
SELECT COUNT(*) FROM product_materials;
```

## What Gets Created

### Before:
```
product_materials table: EMPTY or missing entries
```

### After:
```
product_materials table:
+------------+-------------+------------------+
| product_id | material_id | quantity_needed  |
+------------+-------------+------------------+
| 1          | 5           | 1.68            |
| 2          | 5           | 1.68            |
| 3          | 8           | 1.68            |
| 4          | 12          | 1.68            |
...
```

## Features

### ✅ **Automatic Matching**
- Exact match: "Blockout" → "Blockout"
- Case-insensitive: "blockout" → "Blockout"
- Partial match: "Block Out" → "Blockout"

### ✅ **Bulk Processing**
- Processes all products at once
- No manual entry needed
- Fast and efficient

### ✅ **Error Handling**
- Shows which products failed
- Explains why they failed
- Transaction safety (rollback on error)

### ✅ **Reporting**
- Shows how many linked successfully
- Lists failed products
- Provides reasons for failures

## Example Output

### Successful Run:
```
Successfully linked 134 products to materials

Successfully Linked: 134 products
Failed: 0 products
Total Processed: 134 products
```

### Partial Success:
```
Successfully linked 130 products to materials

Successfully Linked: 130 products
Failed: 4 products
Total Processed: 134 products

Failed Products:
- Custom Curtain - Material: "Special Fabric" (No matching material found)
- Test Product - Material: "Unknown" (No matching material found)
```

## Handling Failed Products

If some products fail to link:

### Reason 1: Material Name Mismatch
```
Product material: "Block Out"
Materials table: "Blockout"

Solution: Update product or material name to match exactly
```

### Reason 2: Material Doesn't Exist
```
Product material: "Special Fabric"
Materials table: Doesn't have "Special Fabric"

Solution: Add the material to materials table first
```

### Fix Failed Products:
```sql
-- Option 1: Update product to use existing material
UPDATE products 
SET material = 'Blockout' 
WHERE material = 'Block Out';

-- Option 2: Add missing material
INSERT INTO materials (material_name, stock, unit)
VALUES ('Special Fabric', 0, 'meters');

-- Then run auto-fix again
```

## Adjusting Quantity Needed

The default is 1.68 per unit. To adjust:

### For All Products:
```sql
UPDATE product_materials 
SET quantity_needed = 2.0;
```

### For Specific Material:
```sql
UPDATE product_materials pm
JOIN materials m ON pm.material_id = m.material_id
SET pm.quantity_needed = 2.5
WHERE m.material_name = 'Blockout';
```

### For Specific Product:
```sql
UPDATE product_materials 
SET quantity_needed = 3.0
WHERE product_id = 123;
```

## Files Created

### 1. **`admin/fix_product_materials.php`**
- Web interface for auto-fixing
- Check status button
- Auto-fix button
- Results display

### 2. **`admin/backend/auto_link_product_materials.php`**
- Backend logic for auto-linking
- Matches products to materials
- Creates product_materials entries

### 3. **`admin/backend/check_product_materials_status.php`**
- Checks current status
- Returns counts and lists

### 4. **`database/auto_populate_product_materials.sql`**
- SQL script for manual execution
- Can be run in phpMyAdmin

## Verification

### Check if it worked:
```sql
-- See all product-material links
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY p.product_name;

-- Count total links
SELECT COUNT(*) AS total_links FROM product_materials;

-- Find products still missing links
SELECT 
    p.product_id,
    p.product_name,
    p.material
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.product_id IS NULL;
```

## After Running Auto-Fix

### Test Stock Updates:
1. Go to Product Inventory
2. Click +10 on any product
3. Should now check and deduct materials ✅
4. No more "Insufficient materials" errors (unless actually insufficient)

### Verify Material Deduction:
1. Note material stock before update
2. Add 10 units to product
3. Check material stock after
4. Should decrease by: quantity_needed × 10 ✅

## Troubleshooting

### "No matching material found"
- Product's material field doesn't match any material in materials table
- Fix: Update product material or add material to materials table

### "Database error"
- Check database connection
- Ensure tables exist
- Check user permissions

### "0 products linked"
- All products already have material links
- Or no products match any materials
- Check products.material field values

## Upload These Files

1. **`admin/fix_product_materials.php`** - Main interface
2. **`admin/backend/auto_link_product_materials.php`** - Auto-link logic
3. **`admin/backend/check_product_materials_status.php`** - Status checker
4. **`database/auto_populate_product_materials.sql`** - SQL script

## Quick Start

**Fastest way:**
1. Upload all 4 files
2. Go to: `http://localhost/sapinbedsheets-main/admin/fix_product_materials.php`
3. Click "Auto-Fix Links"
4. Done! ✅

---

**Status:** ✅ Ready to use
**Impact:** High (Fixes all products at once)
**Time:** < 1 minute to fix hundreds of products
