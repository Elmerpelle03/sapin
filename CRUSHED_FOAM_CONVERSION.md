# Crushed Foam Fiber Conversion

## Conversion Rate

**Owner's Data:**
- 8 kilos of crushed foam fiber
- Produces 20 pillows
- **Conversion: 400 grams per pillow**

### Calculation:
```
8 kilos = 8,000 grams
8,000 grams ÷ 20 pillows = 400 grams per pillow
```

## How It Works

### Auto-Detection:
```
Product Name: "Pillow"
Material: "Crushed Foam Fiber"

Auto-calculates: 400 grams per pillow ✅
```

### Material Deduction Example:
```
Adding 10 pillows:
10 × 400 grams = 4,000 grams (4 kilos)

Crushed Foam stock:
Before: 8,000 grams (8 kilos)
After: 4,000 grams (4 kilos) ✅
```

## Database Entry

When auto-fix runs, it creates:
```
product_materials table:
+------------+-------------+------------------+
| product_id | material_id | quantity_needed  |
+------------+-------------+------------------+
| 45         | 12          | 400              |
| 46         | 12          | 400              |
| 47         | 12          | 400              |
```

Where:
- `product_id`: Pillow product IDs
- `material_id`: Crushed Foam Fiber material ID
- `quantity_needed`: 400 grams per pillow

## SQL Logic

```sql
-- PILLOWS (Crushed Foam Fiber)
-- 8 kilos (8000 grams) produces 20 pillows = 400 grams per pillow
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
  AND (p.material LIKE '%crushed%' OR p.material LIKE '%foam%');
```

## PHP Logic

```php
// PILLOWS (Crushed Foam Fiber)
// 8 kilos (8000 grams) produces 20 pillows = 400 grams per pillow
if (stripos($product_name, 'pillow') !== false) {
    return 400; // 400 grams per pillow
}
```

## Material Inventory Setup

Make sure your materials table has crushed foam in **grams**:

```sql
-- Check if crushed foam exists
SELECT * FROM materials WHERE material_name LIKE '%crushed%foam%';

-- If it doesn't exist, add it:
INSERT INTO materials (material_name, stock, unit, restock_alert)
VALUES ('Crushed Foam Fiber', 8000, 'grams', 2000);
-- 8000 grams = 8 kilos initial stock
-- 2000 grams = 2 kilos restock alert (enough for 5 pillows)
```

## Production Scenarios

### Scenario 1: Make 10 Pillows
```
Current stock: 8,000 grams (8 kilos)
Production: 10 pillows
Material needed: 10 × 400 = 4,000 grams

Check: 8,000 >= 4,000 ✅ Sufficient
Deduct: 8,000 - 4,000 = 4,000 grams remaining
Result: 10 pillows produced, 4 kilos foam left
```

### Scenario 2: Make 25 Pillows (Insufficient)
```
Current stock: 8,000 grams (8 kilos)
Production: 25 pillows
Material needed: 25 × 400 = 10,000 grams

Check: 8,000 < 10,000 ❌ Insufficient
Error: "Insufficient materials to produce 25 units: 
        Crushed Foam Fiber (need 10000, have 8000)"
Result: Production blocked, stock unchanged
```

### Scenario 3: Maximum Production
```
Current stock: 8,000 grams (8 kilos)
Maximum pillows: 8,000 ÷ 400 = 20 pillows

Production: 20 pillows
Material needed: 20 × 400 = 8,000 grams
Result: All foam used, 0 grams remaining
```

## All Conversion Rates Summary

| Product Type | Size/Type | Material | Quantity Needed | Unit |
|-------------|-----------|----------|-----------------|------|
| Bedsheet | Single | Canadian Cotton | 2.18 | yards |
| Bedsheet | Double | Canadian Cotton | 2.27 | yards |
| Bedsheet | Family | Canadian Cotton | 2.36 | yards |
| Bedsheet | Queen | Canadian Cotton | 2.72 | yards |
| Bedsheet | King | Canadian Cotton | 3.21 | yards |
| Curtain | 7ft | Blockout/Katrina | 2.35 | yards |
| Curtain | 6ft | Blockout/Katrina | 2.04 | yards |
| Curtain | 5ft | Blockout/Katrina | 1.68 | yards |
| **Pillow** | **All** | **Crushed Foam** | **400** | **grams** |

## Verification Query

After running auto-fix, verify pillow conversions:

```sql
SELECT 
    p.product_name,
    m.material_name,
    pm.quantity_needed,
    CONCAT(pm.quantity_needed, ' grams per pillow') as requirement,
    CONCAT(FLOOR(m.stock / pm.quantity_needed), ' pillows can be made') as max_production
FROM product_materials pm
JOIN products p ON pm.product_id = p.product_id
JOIN materials m ON pm.material_id = m.material_id
WHERE p.product_name LIKE '%pillow%'
ORDER BY p.product_name;
```

Expected output:
```
+----------------+-------------------+------------------+----------------------+------------------------+
| product_name   | material_name     | quantity_needed  | requirement          | max_production         |
+----------------+-------------------+------------------+----------------------+------------------------+
| Pillow         | Crushed Foam Fiber| 400              | 400 grams per pillow | 20 pillows can be made |
+----------------+-------------------+------------------+----------------------+------------------------+
```

## Upload Updated Files

1. **`database/auto_populate_product_materials.sql`** - Added crushed foam logic
2. **`admin/backend/auto_link_product_materials.php`** - Added pillow calculation

---

**Crushed Foam: 400 grams per pillow** ✅
