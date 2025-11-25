# Actual Material Conversion Rates

## Overview
The auto-fix tool now uses your **actual production conversion rates** instead of a default value. It automatically calculates the correct quantity needed based on product type and size.

## Conversion Rates Used

### BEDSHEETS (Canadian Cotton)
Based on 109.36 yards total:

| Size | Yards per Unit | Units per Roll | Calculation |
|------|----------------|----------------|-------------|
| **Single** | 2.18 | 50 | 109.36 ÷ 50 |
| **Double** | 2.27 | 48 | 109.36 ÷ 48 |
| **Family** | 2.36 | 46 | 109.36 ÷ 46 |
| **Queen** | 2.72 | 40 | 109.36 ÷ 40 |
| **King** | 3.21 | 36 | 109.36 ÷ 36 |

### CURTAINS (Blockout/US Katrina)
Based on 47 yards per roll:

| Size | Yards per Unit | Units per Roll | Calculation |
|------|----------------|----------------|-------------|
| **7ft** | 2.35 | 20 | 47 ÷ 20 |
| **6ft** | 2.04 | 23 | 47 ÷ 23 |
| **5ft** | 1.68 | 28 | 47 ÷ 28 |

## How It Works

### Automatic Detection:

**For Bedsheets:**
```
Product: "Bedsheet Queen Size"
Size field: "Queen"
Material: "Canadian Cotton"

Auto-calculates: 2.72 yards per unit ✅
```

**For Curtains (Blockout):**
```
Product: "Curtain 5ft Blockout"
Size field: "5ft"
Material: "Blockout"

Auto-calculates: 1.68 yards per unit ✅
```

**For Curtains (US Katrina):**
```
Product: "Curtain 7ft US Katrina"
Size field: "7ft"
Material: "US Katrina"

Auto-calculates: 2.35 yards per unit ✅
```

## SQL Script Logic

The updated `auto_populate_product_materials.sql` now uses CASE statements:

```sql
-- BEDSHEETS
CASE 
    WHEN p.size LIKE '%single%' THEN 2.18
    WHEN p.size LIKE '%double%' THEN 2.27
    WHEN p.size LIKE '%family%' THEN 2.36
    WHEN p.size LIKE '%queen%' THEN 2.72
    WHEN p.size LIKE '%king%' THEN 3.21
    ELSE 2.18  -- Default
END

-- CURTAINS
CASE 
    WHEN p.size LIKE '%7ft%' OR p.size LIKE '%7 ft%' OR p.size LIKE '%7%' THEN 2.35
    WHEN p.size LIKE '%6ft%' OR p.size LIKE '%6 ft%' OR p.size LIKE '%6%' THEN 2.04
    WHEN p.size LIKE '%5ft%' OR p.size LIKE '%5 ft%' OR p.size LIKE '%5%' THEN 1.68
    ELSE 1.68  -- Default
END
```

## PHP Backend Logic

The `auto_link_product_materials.php` has a function:

```php
function calculateQuantityNeeded($product_name, $size, $material) {
    // BEDSHEETS
    if (stripos($product_name, 'bedsheet') !== false) {
        if (stripos($size, 'single') !== false) return 2.18;
        if (stripos($size, 'double') !== false) return 2.27;
        if (stripos($size, 'family') !== false) return 2.36;
        if (stripos($size, 'queen') !== false) return 2.72;
        if (stripos($size, 'king') !== false) return 3.21;
        return 2.18; // Default
    }
    
    // CURTAINS
    if (stripos($product_name, 'curtain') !== false) {
        if (stripos($size, '7ft') !== false) return 2.35;
        if (stripos($size, '6ft') !== false) return 2.04;
        if (stripos($size, '5ft') !== false) return 1.68;
        return 1.68; // Default
    }
    
    return 1.68; // Default for unknown
}
```

## Examples

### Example 1: Bedsheet King Size
```
Product Name: "Bedsheet King Size"
Size: "King"
Material: "Canadian Cotton"

Calculation:
- Detects: "bedsheet" in name
- Detects: "king" in size
- Returns: 3.21 yards per unit

Result in database:
product_id: 123
material_id: 5 (Canadian Cotton)
quantity_needed: 3.21 ✅
```

### Example 2: Curtain 7ft Blockout
```
Product Name: "Curtain 7ft Blockout"
Size: "7ft"
Material: "Blockout"

Calculation:
- Detects: "curtain" in name
- Detects: "7ft" in size
- Returns: 2.35 yards per unit

Result in database:
product_id: 456
material_id: 8 (Blockout)
quantity_needed: 2.35 ✅
```

### Example 3: Bedsheet Double
```
Product Name: "Bedsheet Double"
Size: "Double"
Material: "Canadian Cotton"

Calculation:
- Detects: "bedsheet" in name
- Detects: "double" in size
- Returns: 2.27 yards per unit

Result in database:
product_id: 789
material_id: 5 (Canadian Cotton)
quantity_needed: 2.27 ✅
```

## Material Deduction Examples

### Adding 10 Bedsheet King Units:
```
Product: Bedsheet King
Quantity needed: 3.21 yards per unit
Adding: 10 units

Material deduction:
3.21 × 10 = 32.1 yards

Canadian Cotton stock:
Before: 100 yards
After: 67.9 yards ✅
```

### Adding 5 Curtain 7ft Units:
```
Product: Curtain 7ft Blockout
Quantity needed: 2.35 yards per unit
Adding: 5 units

Material deduction:
2.35 × 5 = 11.75 yards

Blockout stock:
Before: 50 yards
After: 38.25 yards ✅
```

## Verification

After running the auto-fix, verify the quantities:

```sql
SELECT 
    p.product_name,
    p.size,
    m.material_name,
    pm.quantity_needed,
    CASE 
        WHEN p.size LIKE '%king%' AND pm.quantity_needed = 3.21 THEN '✅ Correct'
        WHEN p.size LIKE '%queen%' AND pm.quantity_needed = 2.72 THEN '✅ Correct'
        WHEN p.size LIKE '%family%' AND pm.quantity_needed = 2.36 THEN '✅ Correct'
        WHEN p.size LIKE '%double%' AND pm.quantity_needed = 2.27 THEN '✅ Correct'
        WHEN p.size LIKE '%single%' AND pm.quantity_needed = 2.18 THEN '✅ Correct'
        WHEN p.size LIKE '%7ft%' AND pm.quantity_needed = 2.35 THEN '✅ Correct'
        WHEN p.size LIKE '%6ft%' AND pm.quantity_needed = 2.04 THEN '✅ Correct'
        WHEN p.size LIKE '%5ft%' AND pm.quantity_needed = 1.68 THEN '✅ Correct'
        ELSE '⚠️ Check'
    END AS status
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY p.product_name;
```

## Benefits

### ✅ **Accurate Material Tracking**
- Each size uses correct conversion rate
- King bedsheet uses 3.21 yards (not 1.68)
- 7ft curtain uses 2.35 yards (not 1.68)

### ✅ **Realistic Production**
- Matches actual production data
- Based on real roll sizes
- Reflects true material usage

### ✅ **Correct Inventory**
- Material deductions are accurate
- No over/under estimation
- Proper stock tracking

## Upload Updated Files

1. **`database/auto_populate_product_materials.sql`** - Updated with CASE logic
2. **`admin/backend/auto_link_product_materials.php`** - Updated with calculation function

---

**Now the auto-fix uses your ACTUAL conversion rates!** 🎯
