# Material Conversion Reference Guide

## 📊 Production Conversions

### BEDSHEETS (Canadian Cotton)
**Total fabric: 109.36 yards**

| Size | Quantity Produced | Yards per Unit | Calculation |
|------|------------------|----------------|-------------|
| **Single** | 50 pcs | **2.18 yards** | 109.36 ÷ 50 = 2.18 |
| **Double** | 48 pcs | **2.27 yards** | 109.36 ÷ 48 = 2.27 |
| **Family** | 46 pcs | **2.36 yards** | 109.36 ÷ 46 = 2.36 |
| **Queen** | 40 pcs | **2.72 yards** | 109.36 ÷ 40 = 2.72 |
| **King** | 36 pcs | **3.21 yards** | 109.36 ÷ 36 = 3.21 |

---

### CURTAINS (Blockout Material OR US Katrina Material)
**Total fabric: 47 yards per roll**

| Size | Quantity Produced | Yards per Unit | Calculation |
|------|------------------|----------------|-------------|
| **7ft** | 20 pcs | **2.35 yards** | 47 ÷ 20 = 2.35 |
| **6ft** | 23 pcs | **2.04 yards** | 47 ÷ 23 = 2.04 |
| **5ft** | 28 pcs | **1.68 yards** | 47 ÷ 28 = 1.68 |

---

## 🎯 Quick Setup Instructions

### Option 1: Automatic (If product names match)

1. **Open phpMyAdmin**
2. **Select your database**
3. **Go to SQL tab**
4. **Copy & paste** from: `database/insert_product_materials.sql`
5. **Click Go**

**This works if your product names contain:**
- "single", "double", "family", "queen", "king" for bedsheets
- "7ft", "6ft", "5ft" for curtains
- "Canadian cotton", "Blockout", "Katrina" for materials

---

### Option 2: Manual (If product names are different)

#### Step 1: Find Your Product IDs
```sql
SELECT product_id, product_name, size FROM products 
WHERE product_name LIKE '%bedsheet%' OR product_name LIKE '%curtain%'
ORDER BY product_name;
```

**Example Result:**
```
product_id | product_name           | size
-----------|------------------------|-------
5          | Bedsheet Single        | Single
6          | Bedsheet Double        | Double
7          | Bedsheet Family        | Family
8          | Bedsheet Queen         | Queen
9          | Bedsheet King          | King
15         | Curtain 7ft Blockout   | 7ft
16         | Curtain 6ft Blockout   | 6ft
17         | Curtain 5ft Blockout   | 5ft
```

#### Step 2: Find Your Material IDs
```sql
SELECT material_id, material_name FROM materials;
```

**Example Result:**
```
material_id | material_name
------------|------------------
1           | Canadian cotton
2           | Blockout
3           | US Katrina
```

#### Step 3: Insert Material Requirements

**For Bedsheets (Canadian Cotton = material_id 1):**
```sql
INSERT INTO product_materials (product_id, material_id, quantity_needed) VALUES
(5, 1, 2.18),   -- Bedsheet Single
(6, 1, 2.27),   -- Bedsheet Double
(7, 1, 2.36),   -- Bedsheet Family
(8, 1, 2.72),   -- Bedsheet Queen
(9, 1, 3.21);   -- Bedsheet King
```

**For Curtains (Blockout = material_id 2):**
```sql
INSERT INTO product_materials (product_id, material_id, quantity_needed) VALUES
(15, 2, 2.35),  -- Curtain 7ft Blockout
(16, 2, 2.04),  -- Curtain 6ft Blockout
(17, 2, 1.68);  -- Curtain 5ft Blockout
```

**For Curtains (US Katrina = material_id 3):**
```sql
INSERT INTO product_materials (product_id, material_id, quantity_needed) VALUES
(18, 3, 2.35),  -- Curtain 7ft Katrina
(19, 3, 2.04),  -- Curtain 6ft Katrina
(20, 3, 1.68);  -- Curtain 5ft Katrina
```

---

## 📋 Production Examples

### Example 1: Producing 100 Bedsheet Singles

**Calculation:**
```
Material needed: 100 units × 2.18 yards = 218 yards
```

**Before Production:**
```
Canadian Cotton: 300 yards
Bedsheet Single stock: 50 units
```

**After Production:**
```
Canadian Cotton: 82 yards (300 - 218)
Bedsheet Single stock: 150 units (50 + 100)
```

---

### Example 2: Producing 50 Curtains 7ft (Blockout)

**Calculation:**
```
Material needed: 50 units × 2.35 yards = 117.5 yards
```

**Before Production:**
```
Blockout Material: 150 yards
Curtain 7ft stock: 20 units
```

**After Production:**
```
Blockout Material: 32.5 yards (150 - 117.5)
Curtain 7ft stock: 70 units (20 + 50)
```

---

### Example 3: Mixed Production

**Producing:**
- 30 Bedsheet Singles (30 × 2.18 = 65.4 yards)
- 20 Bedsheet Queens (20 × 2.72 = 54.4 yards)
- Total needed: 119.8 yards

**Before:**
```
Canadian Cotton: 200 yards
```

**After:**
```
Canadian Cotton: 80.2 yards (200 - 119.8)
```

---

## 🔍 Verification Queries

### Check What Materials Are Defined:
```sql
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed,
    CONCAT(pm.quantity_needed, ' yards per unit') as requirement
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
ORDER BY m.material_name, p.product_name;
```

### Calculate How Many Units You Can Produce:
```sql
SELECT 
    p.product_name,
    m.material_name,
    m.stock as available_yards,
    pm.quantity_needed as yards_per_unit,
    FLOOR(m.stock / pm.quantity_needed) as max_units_can_produce
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE m.stock > 0
ORDER BY max_units_can_produce DESC;
```

**Example Output:**
```
product_name      | material_name    | available_yards | yards_per_unit | max_units_can_produce
------------------|------------------|-----------------|----------------|----------------------
Curtain 5ft       | Blockout         | 100.00          | 1.68           | 59
Bedsheet Single   | Canadian cotton  | 150.00          | 2.18           | 68
Curtain 6ft       | Blockout         | 100.00          | 2.04           | 49
Bedsheet Double   | Canadian cotton  | 150.00          | 2.27           | 66
Curtain 7ft       | Blockout         | 100.00          | 2.35           | 42
Bedsheet Family   | Canadian cotton  | 150.00          | 2.36           | 63
Bedsheet Queen    | Canadian cotton  | 150.00          | 2.72           | 55
Bedsheet King     | Canadian cotton  | 150.00          | 3.21           | 46
```

---

## 📊 Material Planning

### How Much Material to Order?

**Formula:**
```
Material Needed = (Units to Produce) × (Yards per Unit)
```

**Example: Planning for 1 month**

**Target Production:**
- 200 Bedsheet Singles
- 150 Bedsheet Queens
- 100 Bedsheet Kings
- 50 Curtains 7ft

**Material Calculation:**

**Canadian Cotton:**
```
Singles: 200 × 2.18 = 436 yards
Queens:  150 × 2.72 = 408 yards
Kings:   100 × 3.21 = 321 yards
Total: 1,165 yards needed
```

**Blockout Material:**
```
Curtains 7ft: 50 × 2.35 = 117.5 yards
Total: 117.5 yards needed
```

**Order Recommendation:**
```
Canadian Cotton: 1,200 yards (1,165 + 3% buffer)
Blockout: 125 yards (117.5 + 6% buffer)
```

---

## ⚠️ Important Notes

### 1. **These are PRODUCTION conversions**
- Used when you ADD stock (produce new items)
- NOT used when customers buy (that's sales)

### 2. **Waste/Scrap Factor**
- These numbers assume perfect cutting
- Real production may have 2-5% waste
- Consider adding buffer when ordering materials

### 3. **Different Materials**
- Bedsheets use Canadian Cotton
- Curtains can use EITHER Blockout OR Katrina
- Make sure to link correct material to correct product

### 4. **Size Variations**
- Larger sizes use more material
- King size (3.21 yards) uses 47% more than Single (2.18 yards)
- Plan inventory accordingly

---

## 🎯 Quick Reference Card

**Print this for easy reference:**

```
BEDSHEETS (Canadian Cotton)
├─ Single:  2.18 yards
├─ Double:  2.27 yards
├─ Family:  2.36 yards
├─ Queen:   2.72 yards
└─ King:    3.21 yards

CURTAINS (Blockout or Katrina)
├─ 7ft:     2.35 yards
├─ 6ft:     2.04 yards
└─ 5ft:     1.68 yards
```

---

**Keep this document handy for production planning and material ordering!** 📋✅
