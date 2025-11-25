# How to Fix Products That Don't Deduct Materials

## 🐛 The Problem

When you add stock to some products, materials are deducted. But for other products, materials are NOT deducted.

**Root Cause:** Those products don't have materials defined in the `product_materials` table.

---

## 🔍 Step 1: Identify Which Products Are Missing

Run this SQL query in phpMyAdmin:

```sql
-- Find products WITHOUT material links
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    p.material as product_material,
    'NO MATERIAL LINK' as issue
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL
ORDER BY p.product_name;
```

**Or use the diagnostic file:**
- Open phpMyAdmin
- Select your database (`sapin_bedsheets`)
- Go to SQL tab
- Copy and paste from: `database/check_missing_product_materials.sql`
- Click "Go"

---

## ✅ Step 2: Fix ALL Products Automatically

Run this SQL script to automatically link all products to their materials:

**Option A: Use the comprehensive fix file**
1. Open phpMyAdmin
2. Select your database (`sapin_bedsheets`)
3. Go to SQL tab
4. Copy and paste from: `database/fix_all_product_materials.sql`
5. Click "Go"

**Option B: Run the existing fix file**
1. Open phpMyAdmin
2. Select your database (`sapin_bedsheets`)
3. Go to SQL tab
4. Copy and paste from: `database/fix_missing_product_materials.sql`
5. Click "Go"

---

## 🎯 Step 3: Verify the Fix

After running the fix, verify all products are now linked:

```sql
-- Check if any products are still missing links
SELECT 
    COUNT(*) as unlinked_products
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
WHERE pm.id IS NULL;
```

**Expected result:** `unlinked_products = 0`

---

## 📊 Understanding the Fix

The fix script automatically links products to materials based on:

1. **Product Type** (Curtain, Bedsheet, Sofa mat)
2. **Size** (Single, Double, Queen, King, 5ft, 6ft, 7ft, etc.)
3. **Material Column** (matches product.material to materials.material_name)

### Material Quantities Used:

**Curtains:**
- 5ft = 1.68 yards
- 6ft = 2.04 yards
- 7ft = 2.35 yards
- 8ft = 2.68 yards

**Bedsheets:**
- Single = 2.18 yards
- Double = 2.27 yards
- Family = 2.36 yards
- Queen = 2.72 yards
- King = 3.21 yards

**Sofa Mats:**
- 20x60 = 2.5 yards
- 24x72 = 3.0 yards

---

## 🔧 Manual Fix for Specific Products

If you need to manually link a specific product:

```sql
-- Example: Link product ID 123 to material ID 1 (Canadian Cotton)
-- with 2.5 yards needed per unit
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (123, 1, 2.5)
ON DUPLICATE KEY UPDATE quantity_needed = 2.5;
```

**To find material IDs:**
```sql
SELECT material_id, material_name, stock 
FROM materials 
ORDER BY material_name;
```

---

## 🧪 Test After Fixing

1. **Go to Admin Panel** → Products
2. **Edit a product** that previously didn't deduct materials
3. **Increase the stock** by a few units
4. **Check Materials Inventory**
5. **Verify materials were deducted** ✅

---

## 🚨 Common Issues

### Issue 1: "Material name doesn't match"
**Problem:** Product's material column doesn't exactly match material_name in materials table

**Solution:**
```sql
-- Check exact material names
SELECT DISTINCT 
    p.material as product_material,
    m.material_name
FROM products p
LEFT JOIN materials m ON LOWER(TRIM(p.material)) = LOWER(TRIM(m.material_name))
WHERE p.product_id = YOUR_PRODUCT_ID;
```

If they don't match, either:
- Update the product's material column to match
- Or manually insert the link using the correct material_id

### Issue 2: "Fix script didn't link some products"
**Problem:** Product name or size doesn't match the patterns in the script

**Solution:** Manually link those products:
```sql
-- Find the product and material IDs
SELECT p.product_id, p.product_name, p.size, p.material
FROM products p
WHERE p.product_id = YOUR_PRODUCT_ID;

SELECT material_id, material_name
FROM materials
WHERE material_name LIKE '%YOUR_MATERIAL%';

-- Then manually link
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (YOUR_PRODUCT_ID, YOUR_MATERIAL_ID, YOUR_QUANTITY)
ON DUPLICATE KEY UPDATE quantity_needed = YOUR_QUANTITY;
```

---

## 📋 Quick Reference

**Check if product has materials defined:**
```sql
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed
FROM products p
LEFT JOIN product_materials pm ON p.product_id = pm.product_id
LEFT JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_id = YOUR_PRODUCT_ID;
```

**Add material link to product:**
```sql
INSERT INTO product_materials (product_id, material_id, quantity_needed)
VALUES (PRODUCT_ID, MATERIAL_ID, QUANTITY)
ON DUPLICATE KEY UPDATE quantity_needed = QUANTITY;
```

**Remove material link from product:**
```sql
DELETE FROM product_materials 
WHERE product_id = YOUR_PRODUCT_ID 
  AND material_id = YOUR_MATERIAL_ID;
```

**Update quantity needed:**
```sql
UPDATE product_materials 
SET quantity_needed = NEW_QUANTITY
WHERE product_id = YOUR_PRODUCT_ID 
  AND material_id = YOUR_MATERIAL_ID;
```

---

## ✅ Summary

1. **Problem:** Some products don't have materials defined in `product_materials` table
2. **Solution:** Run `database/fix_all_product_materials.sql` to automatically link all products
3. **Verify:** Check that all products now have material links
4. **Test:** Add stock to a product and verify materials are deducted

After running the fix, **ALL products will deduct materials** when you add stock! 🎉
