# Fix: Pillowcase vs Pillow Material Requirements

## Problem
Pillowcases were incorrectly linked to Crushed Foam material because the auto-populate script matched any product with "pillow" in the name.

**Wrong:**
- Pillowcase → Canadian Cotton + Crushed Foam ❌

**Correct:**
- Pillowcase → Canadian Cotton only ✅
- Pillow → Canadian Cotton + Crushed Foam ✅

## The Issue

The error message showed:
```
"Insufficient materials to produce 1 units: Crushed Foam with Fiber (need 266.67, have 3.33)"
```

For a product that is a **Pillowcase** (Canadian Cotton), not a **Pillow**.

## Root Cause

The auto-populate logic was:
```sql
WHERE p.product_name LIKE '%pillow%'
```

This matched:
- "Pillow" ✅
- "Pillowcase" ❌ (should not match)
- "Pillow Case" ❌ (should not match)

## Solution

### 1. Updated SQL Script

**File:** `database/auto_populate_product_materials.sql`

**Before:**
```sql
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%pillow%'
  AND (p.material LIKE '%crushed%' OR p.material LIKE '%foam%');
```

**After:**
```sql
WHERE pm.product_id IS NULL
  AND p.product_name LIKE '%pillow%'
  AND p.product_name NOT LIKE '%pillowcase%'  -- Exclude pillowcases
  AND p.product_name NOT LIKE '%pillow case%'  -- Exclude pillow case (with space)
  AND (p.material LIKE '%crushed%' OR p.material LIKE '%foam%');
```

### 2. Updated PHP Backend

**File:** `admin/backend/auto_link_product_materials.php`

**Before:**
```php
if (stripos($product_name, 'pillow') !== false) {
    return 400; // 400 grams per pillow
}
```

**After:**
```php
if (stripos($product_name, 'pillow') !== false && 
    stripos($product_name, 'pillowcase') === false && 
    stripos($product_name, 'pillow case') === false) {
    return 400; // 400 grams per pillow
}
```

### 3. Cleanup Script

**File:** `database/fix_pillowcase_crushed_foam.sql`

Removes existing wrong links:
```sql
DELETE pm FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE (p.product_name LIKE '%pillowcase%' OR p.product_name LIKE '%pillow case%')
  AND (m.material_name LIKE '%crushed%foam%' OR m.material_name LIKE '%foam%fiber%');
```

## How to Fix

### Quick Fix (Run in phpMyAdmin):

```sql
-- Remove crushed foam from pillowcases
DELETE pm FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE (p.product_name LIKE '%pillowcase%' OR p.product_name LIKE '%pillow case%')
  AND (m.material_name LIKE '%crushed%foam%' OR m.material_name LIKE '%foam%fiber%');
```

### Verify:

```sql
-- Check pillowcases (should only show Canadian Cotton)
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%pillowcase%'
ORDER BY p.product_name;

-- Check pillows (should show both Canadian Cotton AND Crushed Foam)
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%pillow%'
  AND p.product_name NOT LIKE '%pillowcase%'
ORDER BY p.product_name, m.material_name;
```

## Expected Results

### Pillowcase:
```
Product: "1 Large kumot + 2 Large Zipper Pillowcase"
Materials:
- Canadian Cotton: 2.0 yards ✅
```

### Pillow:
```
Product: "Pillow"
Materials:
- Canadian Cotton: 2.0 yards ✅
- Crushed Foam with Fiber: 400 grams ✅
```

## Material Deduction Examples

### Adding 10 Pillowcases:
```
Materials deducted:
- Canadian Cotton: 2.0 × 10 = 20 yards ✅
- Crushed Foam: 0 (not used) ✅
```

### Adding 10 Pillows:
```
Materials deducted:
- Canadian Cotton: 2.0 × 10 = 20 yards ✅
- Crushed Foam: 400 × 10 = 4,000 grams ✅
```

## Product Naming Convention

To avoid confusion in the future:

### Clear Naming:
- ✅ "Pillow" - Uses foam
- ✅ "Pillowcase" - No foam
- ✅ "Pillow Case" - No foam
- ✅ "Bedsheet with Pillowcase" - No foam (it's the case, not the pillow)

### Avoid Ambiguous:
- ❌ "Pillow Set" - Unclear if it includes the pillow or just the case

## Files Modified

1. **`database/auto_populate_product_materials.sql`** - Exclude pillowcases from crushed foam
2. **`admin/backend/auto_link_product_materials.php`** - Exclude pillowcases from crushed foam calculation
3. **`database/fix_pillowcase_crushed_foam.sql`** - Cleanup script (new)

## Upload These Files

1. `database/auto_populate_product_materials.sql` - Updated
2. `admin/backend/auto_link_product_materials.php` - Updated
3. `database/fix_pillowcase_crushed_foam.sql` - New cleanup script

## After Fixing

Try adding stock to a pillowcase:
- Should only check Canadian Cotton ✅
- Should NOT check Crushed Foam ✅
- Should work without errors ✅

---

**Status:** ✅ Fixed
**Impact:** High (Prevents wrong material deduction)
**Complexity:** Low (Simple exclusion logic)
