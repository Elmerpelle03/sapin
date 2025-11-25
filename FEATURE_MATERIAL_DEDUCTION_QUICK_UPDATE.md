# Feature: Material Deduction for Quick Stock Updates

## Overview
Quick stock update buttons (+10, +50, custom) and bulk restock now automatically check and deduct materials from the material inventory, just like the edit product functionality. This ensures material tracking is accurate across all stock update methods.

## Problem Solved
**Before:** Quick stock updates bypassed material inventory
- Click +50 button → Product stock increases
- Material inventory unchanged ❌
- Inaccurate material tracking ❌
- Inconsistent with edit product behavior ❌

**After:** Quick stock updates deduct materials automatically
- Click +50 button → Check materials available
- Deduct materials from inventory ✅
- Log material usage ✅
- Consistent with edit product behavior ✅

## How It Works

### Material Deduction Flow:
```
1. User clicks +10 button on product
2. Backend calculates: stock_difference = 10
3. Check product_materials table for required materials
4. For each material:
   - Calculate: total_needed = quantity_needed × 10
   - Check if material stock >= total_needed
5. If insufficient materials:
   - Rollback transaction
   - Show error message
   - Stock NOT updated ❌
6. If sufficient materials:
   - Deduct materials from inventory
   - Log usage in material_usage_log
   - Update product stock
   - Commit transaction ✅
```

## Features Implemented

### 1. **Material Availability Check**
Before increasing stock, system checks if enough materials are available.

```php
// Check each required material
foreach ($materials as $mat) {
    $total_needed = $mat['quantity_needed'] * $stock_difference;
    
    if ($mat['stock'] < $total_needed) {
        // Not enough materials!
        $insufficient_materials[] = "{$mat['material_name']} (need {$total_needed}, have {$mat['stock']})";
    }
}
```

### 2. **Automatic Material Deduction**
When stock increases, materials are automatically deducted.

```php
// Deduct materials
foreach ($materials as $mat) {
    $total_needed = $mat['quantity_needed'] * $stock_difference;
    
    UPDATE materials 
    SET stock = stock - $total_needed 
    WHERE material_id = $mat['material_id']
}
```

### 3. **Material Usage Logging**
All material deductions are logged for tracking.

```php
INSERT INTO material_usage_log (
    product_id, 
    material_id, 
    quantity_used, 
    product_quantity_produced, 
    action_type, 
    created_by, 
    notes
) VALUES (
    123,
    456,
    50,
    10,
    'production',
    1,
    'Quick stock update: 5 → 15'
)
```

### 4. **Transaction Safety**
Uses database transactions to ensure data consistency.

```php
$pdo->beginTransaction();

try {
    // Check materials
    // Deduct materials
    // Update stock
    $pdo->commit(); ✅
} catch (Exception $e) {
    $pdo->rollBack(); ❌
}
```

## User Experience

### Scenario 1: Sufficient Materials
```
Product: Bedsheet
Current Stock: 5 units
Materials Available:
- Cotton Fabric: 100 meters (need 50 for 10 units)
- Thread: 200 meters (need 20 for 10 units)

Action: Click +10 button

Result:
1. Check materials: ✅ Sufficient
2. Deduct materials:
   - Cotton Fabric: 100 → 50 meters
   - Thread: 200 → 180 meters
3. Update stock: 5 → 15 units
4. Success message: "Stock Updated! 5 → 15 units"
5. Materials logged in usage log ✅
```

### Scenario 2: Insufficient Materials
```
Product: Pillow
Current Stock: 10 units
Materials Available:
- Fabric: 20 meters (need 50 for 10 units)
- Filling: 100 kg (need 30 for 10 units)

Action: Click +10 button

Result:
1. Check materials: ❌ Insufficient
2. Error message: "Insufficient materials to produce 10 units: Fabric (need 50, have 20)"
3. Stock NOT updated: Still 10 units
4. Materials NOT deducted
5. Transaction rolled back ✅
```

### Scenario 3: Bulk Restock
```
Products Selected: 5 products
Each needs +50 units

Process:
1. Product 1: Check materials → Deduct → Update ✅
2. Product 2: Check materials → Insufficient → Skip ❌
3. Product 3: Check materials → Deduct → Update ✅
4. Product 4: Check materials → Deduct → Update ✅
5. Product 5: Check materials → Insufficient → Skip ❌

Result:
- 3 products restocked successfully
- 2 products failed (insufficient materials)
- Materials deducted for successful ones only
```

## Works With All Update Methods

### ✅ **Quick Update Buttons**
- **+10 button** → Checks and deducts materials for 10 units
- **+50 button** → Checks and deducts materials for 50 units
- **Custom amount** → Checks and deducts materials for X units

### ✅ **Bulk Restock**
- Select multiple products
- Enter amount (e.g., 100)
- Each product checked individually
- Materials deducted for each successful update

### ✅ **Edit Product**
- Already had this feature
- Now consistent with quick updates

## Error Messages

### Insufficient Materials:
```
"Insufficient materials to produce 10 units: 
Cotton Fabric (need 50, have 20), 
Thread (need 30, have 15)"
```

### Database Error:
```
"Database error: [error details]"
```

### Success (with materials):
```
"Stock Updated!
Bedsheet
5 → 15 units"
```

## Material Usage Log

### Log Entry Example:
```
product_id: 123
material_id: 456
quantity_used: 50 meters
product_quantity_produced: 10 units
action_type: 'production'
created_by: 1 (admin user)
notes: 'Quick stock update: 5 → 15'
created_at: 2025-10-17 14:30:00
```

### View in Material Inventory:
- Go to Material Inventory page
- See usage history for each material
- Track when and how materials were used

## Technical Details

### Database Tables Used:
1. **products** - Product stock updated
2. **materials** - Material stock deducted
3. **product_materials** - Material requirements per product
4. **material_usage_log** - Usage tracking

### Transaction Flow:
```sql
BEGIN TRANSACTION;

-- Lock product row
SELECT stock FROM products WHERE product_id = 123 FOR UPDATE;

-- Lock material rows
SELECT * FROM product_materials pm
JOIN materials m ON pm.material_id = m.material_id
WHERE pm.product_id = 123 FOR UPDATE;

-- Check materials
IF (all materials sufficient) THEN
    -- Deduct materials
    UPDATE materials SET stock = stock - X WHERE material_id = Y;
    
    -- Log usage
    INSERT INTO material_usage_log (...);
    
    -- Update product
    UPDATE products SET stock = stock + 10 WHERE product_id = 123;
    
    COMMIT;
ELSE
    ROLLBACK;
END IF;
```

### Row Locking:
- Uses `FOR UPDATE` to prevent race conditions
- Ensures accurate material counts
- Prevents overselling materials

## Files Modified

### **`admin/backend/quick_stock_update.php`**

**Changes:**
1. Added transaction support (`beginTransaction`, `commit`, `rollBack`)
2. Added `FOR UPDATE` locks for data consistency
3. Added material availability check
4. Added material deduction logic
5. Added material usage logging
6. Added error handling for insufficient materials

**Line ~18:** Start transaction
```php
$pdo->beginTransaction();
```

**Line ~54-82:** Check material availability
```php
if ($stock_difference > 0) {
    // Get required materials
    // Check if sufficient
    // Return error if insufficient
}
```

**Line ~84-111:** Deduct materials and log
```php
// Deduct from materials table
// Insert into material_usage_log
```

**Line ~121:** Commit transaction
```php
$pdo->commit();
```

**Line ~133:** Rollback on error
```php
$pdo->rollBack();
```

## Benefits

### ✅ **Accurate Material Tracking**
- All stock increases tracked
- Material inventory always accurate
- No manual material adjustments needed

### ✅ **Prevents Overselling**
- Can't increase stock without materials
- Protects against impossible production
- Realistic inventory management

### ✅ **Complete Audit Trail**
- Every material usage logged
- Track who, when, and why
- Full production history

### ✅ **Consistent Behavior**
- Edit product: Deducts materials ✅
- Quick update: Deducts materials ✅
- Bulk restock: Deducts materials ✅
- All methods work the same way

### ✅ **Business Logic Enforcement**
- Can't create products from nothing
- Material requirements enforced
- Production capacity tracked

## Testing Checklist

- [ ] +10 button checks materials
- [ ] +10 button deducts materials
- [ ] +50 button checks materials
- [ ] +50 button deducts materials
- [ ] Custom amount checks materials
- [ ] Custom amount deducts materials
- [ ] Bulk restock checks materials per product
- [ ] Bulk restock deducts materials per product
- [ ] Error shown when insufficient materials
- [ ] Stock NOT updated when insufficient materials
- [ ] Materials logged in material_usage_log
- [ ] Transaction rolls back on error
- [ ] Material inventory decreases correctly
- [ ] Can view usage log in Material Inventory

## Example Scenarios

### Example 1: Make 10 Bedsheets
```
Bedsheet Requirements (per unit):
- Cotton Fabric: 5 meters
- Thread: 2 meters
- Zipper: 1 piece

Current Materials:
- Cotton Fabric: 100 meters
- Thread: 50 meters
- Zipper: 20 pieces

Action: Click +10 on Bedsheet

Calculation:
- Cotton Fabric needed: 5 × 10 = 50 meters ✅ (have 100)
- Thread needed: 2 × 10 = 20 meters ✅ (have 50)
- Zipper needed: 1 × 10 = 10 pieces ✅ (have 20)

Result:
- Bedsheet stock: +10 units ✅
- Cotton Fabric: 100 → 50 meters
- Thread: 50 → 30 meters
- Zipper: 20 → 10 pieces
```

### Example 2: Insufficient Materials
```
Pillow Requirements (per unit):
- Fabric: 3 meters
- Filling: 2 kg

Current Materials:
- Fabric: 10 meters
- Filling: 5 kg

Action: Click +10 on Pillow

Calculation:
- Fabric needed: 3 × 10 = 30 meters ❌ (only have 10)
- Filling needed: 2 × 10 = 20 kg ❌ (only have 5)

Result:
- Error: "Insufficient materials to produce 10 units: Fabric (need 30, have 10), Filling (need 20, have 5)"
- Pillow stock: NOT updated
- Materials: NOT deducted
```

## Upload This File

### Modified:
1. **`admin/backend/quick_stock_update.php`** - Material deduction added

### No Frontend Changes:
- Buttons work the same way
- Error messages show automatically
- Success messages unchanged

---

**Status:** ✅ Implemented
**Impact:** Very High (Critical for accurate inventory)
**Complexity:** Medium (Database transactions, material logic)
**Business Value:** Ensures realistic production tracking
