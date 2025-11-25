# Crushed Foam with Fiber - Setup Guide

## 📋 Overview

Pillows and Sofa Mats require **TWO materials**:
1. **Fabric** (Canadian Cotton, US Katrina, etc.) - for the cover
2. **Crushed Foam with Fiber** - for the filling

**Consumption Rate:** 266.67g per piece (8000g = 30 pieces)

---

## 🎯 How It Works

### Example: Adding 10 Pillows

When you add 10 pillows to inventory, the system will deduct:

1. **Fabric Material** (e.g., Canadian Cotton)
   - Amount: 0.5 yards × 10 = 5 yards

2. **Crushed Foam with Fiber**
   - Amount: 266.67g × 10 = 2,666.7g

Both materials are deducted automatically!

---

## ✅ Step 1: Verify Crushed Foam Material Exists

Run this SQL query in phpMyAdmin:

```sql
SELECT 
    material_id,
    material_name,
    stock,
    mu.unit
FROM materials m
LEFT JOIN materialunits mu ON m.materialunit_id = mu.materialunit_id
WHERE LOWER(material_name) LIKE '%crushed%foam%fiber%';
```

### If Material Doesn't Exist:

You need to add it first. Run this SQL:

```sql
-- First, check what unit ID to use for grams
SELECT materialunit_id, unit 
FROM materialunits 
WHERE unit LIKE '%gram%' OR unit LIKE '%g%';

-- Then insert the material (replace [UNIT_ID] with the correct ID)
INSERT INTO materials (material_name, stock, materialunit_id) 
VALUES ('Crushed Foam with Fiber', 8000, [UNIT_ID]);
```

**Example:** If grams unit_id is 3:
```sql
INSERT INTO materials (material_name, stock, materialunit_id) 
VALUES ('Crushed Foam with Fiber', 8000, 3);
```

---

## ✅ Step 2: Link Crushed Foam to Existing Products

Run this SQL script to add crushed foam to all existing pillows and sofa mats:

**File:** `database/add_crushed_foam_to_products.sql`

Or copy and paste this into phpMyAdmin:

```sql
-- Add crushed foam to all pillows
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%pillow%'
  AND LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
  AND NOT EXISTS (
    SELECT 1 
    FROM product_materials pm 
    WHERE pm.product_id = p.product_id 
      AND pm.material_id = m.material_id
  )
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;

-- Add crushed foam to all sofa mats
INSERT INTO product_materials (product_id, material_id, quantity_needed)
SELECT 
    p.product_id,
    m.material_id,
    266.67
FROM products p
CROSS JOIN materials m
WHERE LOWER(p.product_name) LIKE '%sofa%'
  AND LOWER(p.product_name) LIKE '%mat%'
  AND LOWER(m.material_name) LIKE '%crushed%foam%fiber%'
  AND NOT EXISTS (
    SELECT 1 
    FROM product_materials pm 
    WHERE pm.product_id = p.product_id 
      AND pm.material_id = m.material_id
  )
ON DUPLICATE KEY UPDATE quantity_needed = 266.67;
```

---

## ✅ Step 3: Verify the Setup

Run this query to see all materials for pillows and sofa mats:

```sql
SELECT 
    p.product_id,
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN LOWER(m.material_name) LIKE '%foam%' THEN CONCAT(pm.quantity_needed, 'g')
        ELSE CONCAT(pm.quantity_needed, ' yards')
    END as unit
FROM products p
JOIN product_materials pm ON p.product_id = pm.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE LOWER(p.product_name) LIKE '%pillow%'
   OR (LOWER(p.product_name) LIKE '%sofa%' AND LOWER(p.product_name) LIKE '%mat%')
ORDER BY p.product_name, m.material_name;
```

**Expected Result:** Each pillow/sofa mat should have 2 rows:
- One for fabric (e.g., Canadian Cotton - 0.5 yards)
- One for Crushed Foam with Fiber (266.67g)

---

## 🧪 Test the System

### Test 1: Add New Pillow

1. Go to **Admin Panel** → **Products**
2. Click **Add Product**
3. Fill in:
   - Product Name: "Test Pillow"
   - Material: "Canadian Cotton" (or any fabric)
   - Stock: 10
4. Submit

**Expected Result:**
- ✅ Product created
- ✅ Canadian Cotton deducted: 5 yards (0.5 × 10)
- ✅ Crushed Foam deducted: 2,666.7g (266.67 × 10)

### Test 2: Increase Pillow Stock

1. Edit an existing pillow
2. Increase stock from 10 to 20 (+10 units)
3. Save

**Expected Result:**
- ✅ Canadian Cotton deducted: 5 yards (0.5 × 10)
- ✅ Crushed Foam deducted: 2,666.7g (266.67 × 10)

### Test 3: Add New Sofa Mat

1. Add a new sofa mat (20x60)
2. Stock: 5

**Expected Result:**
- ✅ Fabric deducted: 12.5 yards (2.5 × 5)
- ✅ Crushed Foam deducted: 1,333.35g (266.67 × 5)

---

## 📊 Material Consumption Reference

### Pillows
**Per Piece:**
- Fabric: 0.5 yards
- Crushed Foam: 266.67g

**Example - 30 Pillows:**
- Fabric: 15 yards
- Crushed Foam: 8,000g (exactly 1 full batch)

### Sofa Mats
**Per Piece (20x60):**
- Fabric: 2.5 yards
- Crushed Foam: 266.67g

**Per Piece (24x72):**
- Fabric: 3.0 yards
- Crushed Foam: 266.67g

**Example - 30 Sofa Mats (20x60):**
- Fabric: 75 yards
- Crushed Foam: 8,000g (exactly 1 full batch)

---

## 🔍 Troubleshooting

### Issue: "Insufficient materials: Crushed Foam with Fiber"

**Cause:** Not enough crushed foam in inventory

**Solution:** Add more crushed foam stock:
```sql
UPDATE materials 
SET stock = stock + 8000 
WHERE LOWER(material_name) LIKE '%crushed%foam%fiber%';
```

### Issue: Crushed foam not deducting for existing products

**Cause:** Product not linked to crushed foam material

**Solution:** Run the SQL script from Step 2 again

### Issue: New products not deducting crushed foam

**Cause:** Code not updated or material name doesn't match

**Solution:** 
1. Verify material name contains "crushed", "foam", and "fiber"
2. Check `admin/backend/addproduct.php` was updated correctly

---

## 📝 Database Schema

### product_materials Table (Multiple Materials Per Product)

```
product_id | material_id | quantity_needed
-----------|-------------|----------------
1          | 1           | 0.5            (Canadian Cotton - 0.5 yards)
1          | 5           | 266.67         (Crushed Foam - 266.67g)
2          | 1           | 2.5            (Canadian Cotton - 2.5 yards)
2          | 5           | 266.67         (Crushed Foam - 266.67g)
```

This allows each product to have multiple materials!

---

## 🎓 For Your Professor

### Key Concepts Demonstrated:

1. **Multi-Material Products**
   - Products can require multiple raw materials
   - Each material tracked separately
   - All materials validated before production

2. **Automatic Material Detection**
   - System automatically adds crushed foam to pillows/sofa mats
   - Based on product name pattern matching
   - Consistent 266.67g per piece calculation

3. **Transaction Safety**
   - All materials checked before deduction
   - If ANY material insufficient, entire transaction rolled back
   - Prevents partial production

4. **Audit Trail**
   - Every material deduction logged
   - Separate log entries for each material
   - Complete production history

---

## ✅ Summary

**What Was Added:**
1. ✅ Crushed Foam with Fiber material support
2. ✅ Automatic linking for pillows and sofa mats
3. ✅ 266.67g per piece calculation (8000g = 30 pieces)
4. ✅ Multiple materials per product support

**How It Works:**
- When you add/increase stock of pillows or sofa mats
- System automatically deducts BOTH fabric AND crushed foam
- Validates both materials are available before production
- Logs both deductions separately

**Result:**
- Accurate tracking of ALL materials used
- Prevents over-production when ANY material is low
- Complete audit trail for all materials

---

**Status:** ✅ Ready to Use

**Last Updated:** 2025-10-11
